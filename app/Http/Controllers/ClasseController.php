<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ClasseOfficielle;
use App\Models\Ecole;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Enseignant;
use App\Models\LigneClasse;
use App\Support\ExamenNational;
use App\Support\SchoolOrderAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClasseController extends Controller
{
    public function index()
    {
        $idEcole = session('idEcole');
        $user = Auth::user();

        if ($user->droit === 'SupAdmin') {
            $classes = Classe::with(['ecole', 'classeOfficielle', 'filiere'])->withCount('eleves')->get();
        } else {
            $classes = Classe::with(['classeOfficielle', 'filiere'])->where('idEcole', $idEcole)->withCount('eleves')->get();
        }

        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        $idEcole = session('idEcole');
        $user = Auth::user();
        $ecole = $this->resolveEcole($user, $idEcole);
        $estSante = $this->estSante($ecole);

        $matieres = $this->matieresDisponibles($user, $idEcole, $estSante);
        $enseignants = $this->enseignantsDisponibles($user, $idEcole);
        $ordres = $this->ordresDisponibles($user, $idEcole);
        $ordreMatiereMap = $this->ordreMatiereMap();
        $filieres = $this->filieresDisponibles($idEcole);

        return view('classes.form', [
            'classe' => new Classe(),
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'ordres' => $ordres,
            'ordreMatiereMap' => $ordreMatiereMap,
            'filieres' => $filieres,
            'estSante' => $estSante,
            'lignes' => collect(),
            'mode' => 'create',
        ]);
    }

    public function associations(Request $request)
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin') {
            abort(403, 'Seul le SuperAdmin peut associer les classes aux classes officielles.');
        }

        $idEcole = $request->integer('id_ecole') ?: session('idEcole') ?: $user->idEcole;

        $ecoles = Ecole::query()
            ->orderBy('nomEcole')
            ->get();

        $classes = Classe::query()
            ->where('idEcole', $idEcole)
            ->with('classeOfficielle')
            ->orderBy('ordreEnseignement')
            ->orderBy('nom_classe')
            ->get();

        // Scope par le pays de L'ECOLE SELECTIONNEE (pas forcement celle de la
        // session) : ce sont ses propres classes qu'on associe, donc son
        // propre referentiel de classes officielles -- jamais celui d'un
        // autre pays.
        $classesOfficielles = ClasseOfficielle::where('id_pays', \App\Support\Devise::resolvePays($idEcole)->id)
            ->orderBy('ordre_enseignement')
            ->orderBy('nom_classe_officielle')
            ->get();

        $ordresLabels = ExamenNational::ordresLabels($idEcole);

        return view('classes.associations', compact('ecoles', 'idEcole', 'classes', 'classesOfficielles', 'ordresLabels'));
    }

    public function updateAssociations(Request $request)
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin') {
            abort(403, 'Seul le SuperAdmin peut associer les classes aux classes officielles.');
        }

        $idEcole = $request->integer('id_ecole') ?: session('idEcole') ?: $user->idEcole;
        $paysId = \App\Support\Devise::resolvePays($idEcole)->id;

        $data = $request->validate([
            'id_ecole' => 'nullable|integer|exists:ecole,idEcole',
            'associations' => 'required|array',
            'associations.*' => ['nullable', 'integer', Rule::exists('classes_officielles', 'id_classe_officielle')->where('id_pays', $paysId)],
        ]);

        DB::transaction(function () use ($data, $idEcole) {
            foreach ($data['associations'] as $idClasse => $idClasseOfficielle) {
                Classe::where('idEcole', $idEcole)
                    ->where('id_classe', $idClasse)
                    ->update(['id_classe_officielle' => $idClasseOfficielle ?: null]);
            }
        });

        return redirect()->route('classes.associations', ['id_ecole' => $idEcole])
            ->with('success', 'Associations des classes enregistrées avec succès.');
    }

    public function store(Request $request)
    {
        $this->authorizePermission('classes_creation');
        $idEcole = session('idEcole');
        $ecole = $this->resolveEcole(Auth::user(), $idEcole);
        $estSante = $this->estSante($ecole);
        $data = $this->validateClasse($request, $ecole);

        if (!$estSante) {
            $this->ensureOrdreAllowed($data['ordre_enseignement'], Auth::user(), $idEcole);
        }

        DB::transaction(function () use ($data, $idEcole, $estSante) {
            $classe = Classe::create([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $estSante ? null : $data['ordre_enseignement'],
                'idEcole' => $idEcole,
                'id_filiere' => $estSante ? $data['id_filiere'] : null,
                'annee' => $estSante ? $data['annee'] : null,
            ]);

            $this->syncLignesClasse($classe, $data);
        });

        return redirect()->route('classes.index')->with('success', 'Classe et matières enregistrées avec succès.');
    }

    public function show($id)
    {
        $classe = Classe::with(['ligneClasses.matiere', 'ligneClasses.enseignant', 'ecole'])->findOrFail($id);
        
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            return abort(403);
        }

        return view('classes.show', compact('classe'));
    }

    public function edit($id)
    {
        $classe = Classe::with(['ligneClasses.matiere', 'ligneClasses.enseignant', 'ecole'])->findOrFail($id);

        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        $matieres = $this->matieresDisponibles($user, session('idEcole'));
        $enseignants = $this->enseignantsDisponibles($user, session('idEcole'));
        $ordres = $this->ordresDisponibles($user, session('idEcole'));
        $ordreMatiereMap = $this->ordreMatiereMap();
        $filieres = $this->filieresDisponibles($classe->idEcole);

        return view('classes.form', [
            'classe' => $classe,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'ordres' => $ordres,
            'ordreMatiereMap' => $ordreMatiereMap,
            'filieres' => $filieres,
            'estSante' => $this->estSante($classe->ecole),
            'lignes' => $classe->ligneClasses,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('classes_modification');
        $classe = Classe::findOrFail($id);

        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        $estSante = $this->estSante($classe->ecole);
        $data = $this->validateClasse($request, $classe->ecole);

        if (!$estSante) {
            $this->ensureOrdreAllowed($data['ordre_enseignement'], $user, $classe->idEcole);
        }

        DB::transaction(function () use ($classe, $data, $estSante) {
            $classe->update([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $estSante ? null : $data['ordre_enseignement'],
                'id_filiere' => $estSante ? $data['id_filiere'] : null,
                'annee' => $estSante ? $data['annee'] : null,
            ]);

            $classe->ligneClasses()->delete();
            $this->syncLignesClasse($classe, $data);
        });

        return redirect()->route('classes.edit', $classe->id_classe)->with('success', 'Classe modifiée avec succès.');
    }

    public function destroy($id)
    {
        $this->authorizePermission('classes_supprimer');
        $classe = Classe::withCount('eleves')->findOrFail($id);

        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        if ($classe->eleves_count > 0) {
            return redirect()->route('classes.index')->with('error', 'Impossible de supprimer cette classe car des élèves y sont inscrits.');
        }

        DB::transaction(function () use ($classe) {
            $classe->ligneClasses()->delete();
            $classe->delete();
        });

        return redirect()->route('classes.index')->with('success', 'Classe supprimée avec succès.');
    }

    protected function validateClasse(Request $request, ?Ecole $ecole = null): array
    {
        $estSante = $this->estSante($ecole);
        $idEcole = $ecole?->idEcole ?? session('idEcole');

        $rules = [
            'nom_classe' => 'required|string|max:50',
            'id_matiere' => 'required|array|min:1',
            'id_matiere.*' => 'required|integer|exists:matiere,id_matiere',
            'id_enseignants' => 'nullable|array',
            'id_enseignants.*' => 'nullable|integer|exists:enseignants,id_enseignant',
            'coefficient' => 'required|array|min:1',
            'coefficient.*' => 'required|numeric|min:0|max:5',
        ];

        if ($estSante) {
            $rules['id_filiere'] = ['required', 'integer', Rule::exists('filieres', 'id_filiere')->where('id_ecole', $idEcole)];
            $rules['annee'] = 'required|integer|min:1|max:8';
            $rules['ordre_enseignement'] = 'nullable|string|max:50';
        } else {
            $rules['ordre_enseignement'] = 'required|string|max:50';
        }

        return $request->validate($rules, [
            'id_matiere.required' => 'Au moins une matière doit être sélectionnée.',
            'coefficient.*.max' => 'Le coefficient ne peut pas dépasser 5.',
            'id_filiere.required' => 'La filière est obligatoire pour une École de Santé.',
            'id_filiere.exists' => 'La filière sélectionnée n’appartient pas à cette école.',
            'annee.required' => 'L’année est obligatoire pour une École de Santé.',
        ]);
    }

    protected function filieresDisponibles(?int $idEcole)
    {
        return Filiere::where('id_ecole', $idEcole)->orderBy('nom_filiere')->get();
    }

    protected function estSante(?Ecole $ecole): bool
    {
        return ($ecole->typeEcole ?? null) === 'École de Santé';
    }

    protected function syncLignesClasse(Classe $classe, array $data): void
    {
        $expectedOrdre = $this->ordreMatiereMap()[$classe->ordreEnseignement] ?? null;
        foreach ($data['id_matiere'] as $index => $idMatiere) {
            if ($expectedOrdre) {
                $valid = Matiere::whereKey($idMatiere)
                    ->whereHas('ordres', fn ($query) => $query->where('ordre_enseignement', $expectedOrdre))
                    ->exists();
                if (!$valid) {
                    continue;
                }
            }
            LigneClasse::create([
                'id_classe' => $classe->id_classe,
                'id_matiere' => $idMatiere,
                'id_enseignants' => $data['id_enseignants'][$index] ?? null,
                'coefficient' => $data['coefficient'][$index] ?? 1,
            ]);
        }
    }

    protected function matieresDisponibles($user, ?int $idEcole, bool $estSante = false)
    {
        return Matiere::query()
            ->with('ordres')
            // Une Ecole de Sante ne voit jamais le referentiel Fondamentale/
            // Secondaire (global ou d'une autre ecole) : seulement ses propres
            // matieres, plus les matieres globales explicitement marquees
            // "École de Santé" par le SupAdmin (catalogue partage entre toutes
            // les ecoles de sante, meme principe que le referentiel malien
            // partage entre toutes les ecoles classiques).
            ->when($estSante, function ($query) use ($idEcole) {
                $query->where(function ($inner) use ($idEcole) {
                    $inner->where('id_ecole', $idEcole)
                        ->orWhere(function ($global) {
                            $global->whereNull('id_ecole')
                                ->whereHas('ordres', fn ($o) => $o->where('ordre_enseignement', 'École de Santé'));
                        });
                });
            })
            ->when(!$estSante && $user->droit !== 'SupAdmin', function ($query) use ($idEcole) {
                $query->where(function ($inner) use ($idEcole) {
                    $inner->whereNull('id_ecole')->orWhere('id_ecole', $idEcole);
                });
            })
            ->orderBy('nom_matiere')
            ->get();
    }

    protected function enseignantsDisponibles($user, ?int $idEcole)
    {
        return Enseignant::query()
            ->where('is_deleted', 0)
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('id_ecole', $idEcole))
            ->orderBy('nom_prenom_enseignant')
            ->get();
    }

    protected function ordresDisponibles($user, ?int $idEcole = null): array
    {
        $ecole = $this->resolveEcole($user, $idEcole);
        $typeEcole = $ecole->typeEcole ?? null;

        if ($typeEcole === 'Complexe Scolaire' || ($user->droit === 'SupAdmin' && !$ecole)) {
            $orders = ExamenNational::ordresLabels($ecole);

            if (SchoolOrderAccess::userNeedsOrderFilter($user, $ecole)) {
                $allowed = SchoolOrderAccess::allowedOrders($user, $ecole);
                return array_intersect_key($orders, array_flip($allowed));
            }

            return $orders;
        }

        // Hors Mali, "Primaire" et "Secondaire Generale" sont les types
        // proposes a la creation d'ecole (voir ecole-modal.blade.php) --
        // reutilise les memes slugs fondamentale1/fondamentale2/
        // secondairegenerale que le Mali (donc tout ce qui en depend deja --
        // seuil de passage, LV2 par ordre, etc. -- continue de fonctionner
        // sans changement), avec des libelles adaptes au decoupage reel du
        // pays de l'ecole (ExamenNational) plutot que la terminologie malienne.
        if (($typeEcole === 'Primaire' || $typeEcole === 'Secondaire Generale') && !ExamenNational::estMali($ecole)) {
            $labels = ExamenNational::ordresLabels($ecole);

            if ($typeEcole === 'Primaire') {
                return ['fondamentale1' => $labels['fondamentale1']];
            }

            return [
                'fondamentale2' => $labels['fondamentale2'],
                'secondairegenerale' => $labels['secondairegenerale'],
            ];
        }

        if ($typeEcole === 'Collège') {
            return [
                'fondamentale1' => 'Fondamentale I (1 à 6)',
                'fondamentale2' => 'Fondamentale II (7 à 9)',
            ];
        }

        if ($typeEcole === 'Fondamentale I') {
            return ['fondamentale1' => 'Fondamentale I (1 à 6)'];
        }

        if ($typeEcole === 'Fondamentale II') {
            return ['fondamentale2' => 'Fondamentale II (7 à 9)'];
        }

        if ($typeEcole === 'Fondamentale') {
            return ['fondamentale1' => 'Fondamentale I (1 à 6)'];
        }

        if (in_array($typeEcole, ['Secondaire Generale', 'Lycée Privée'], true)) {
            return ['secondairegenerale' => 'Secondaire Général'];
        }

        if (in_array($typeEcole, ['Secondaire Technique et Professionnel', 'Technique et Professionnelle'], true)) {
            return ['secondairetechniqueetprofessionnel' => 'Secondaire Technique et Professionnel'];
        }

        return [
            'fondamentale1' => 'Fondamentale I (1 à 6)',
        ];
    }

    protected function ensureOrdreAllowed(string $ordre, $user, ?int $idEcole = null): void
    {
        if (!array_key_exists($ordre, $this->ordresDisponibles($user, $idEcole))) {
            abort(422, "L'ordre d'enseignement sélectionné ne correspond pas au type de l'école.");
        }
    }

    protected function authorizePermission(string $permission): void
    {
        $user = Auth::user();
        if (!$user || ($user->droit !== 'SupAdmin' && !$user->userHasPermission($permission))) {
            abort(403, 'Permission insuffisante.');
        }
    }


    protected function resolveEcole($user, ?int $idEcole = null): ?Ecole
    {
        $resolvedId = $idEcole ?: session('idEcole') ?: $user->idEcole;

        if ($resolvedId) {
            return Ecole::withoutGlobalScopes()->find($resolvedId);
        }

        return $user->ecole;
    }

    protected function ordreMatiereMap(): array
    {
        return [
            'fondamentale1' => 'Fondamentale I',
            'fondamentale2' => 'Fondamentale II',
            'secondairegenerale' => 'Secondaire Generale',
            'secondairetechniqueetprofessionnel' => 'Secondaire Technique et Professionnel',
            'secondaire' => 'Secondaire Generale',
            'technique' => 'Secondaire Technique et Professionnel',
        ];
    }
}
