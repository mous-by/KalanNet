<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ClasseOfficielle;
use App\Models\Ecole;
use App\Models\Matiere;
use App\Models\Enseignant;
use App\Models\LigneClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClasseController extends Controller
{
    public function index()
    {
        $idEcole = session('idEcole');
        $user = Auth::user();

        if ($user->droit === 'SupAdmin') {
            $classes = Classe::with(['ecole', 'classeOfficielle'])->withCount('eleves')->get();
        } else {
            $classes = Classe::with('classeOfficielle')->where('idEcole', $idEcole)->withCount('eleves')->get();
        }

        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        $idEcole = session('idEcole');
        $user = Auth::user();

        $matieres = $this->matieresDisponibles($user, $idEcole);
        $enseignants = $this->enseignantsDisponibles($user, $idEcole);
        $ordres = $this->ordresDisponibles($user, $idEcole);
        $ordreMatiereMap = $this->ordreMatiereMap();

        return view('classes.form', [
            'classe' => new Classe(),
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'ordres' => $ordres,
            'ordreMatiereMap' => $ordreMatiereMap,
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

        $classesOfficielles = ClasseOfficielle::orderBy('ordre_enseignement')
            ->orderBy('nom_classe_officielle')
            ->get();

        return view('classes.associations', compact('ecoles', 'idEcole', 'classes', 'classesOfficielles'));
    }

    public function updateAssociations(Request $request)
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin') {
            abort(403, 'Seul le SuperAdmin peut associer les classes aux classes officielles.');
        }

        $idEcole = $request->integer('id_ecole') ?: session('idEcole') ?: $user->idEcole;

        $data = $request->validate([
            'id_ecole' => 'nullable|integer|exists:ecole,idEcole',
            'associations' => 'required|array',
            'associations.*' => 'nullable|integer|exists:classes_officielles,id_classe_officielle',
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
        $data = $this->validateClasse($request);
        $idEcole = session('idEcole');
        $this->ensureOrdreAllowed($data['ordre_enseignement'], Auth::user(), $idEcole);

        DB::transaction(function () use ($data, $idEcole) {
            $classe = Classe::create([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $data['ordre_enseignement'],
                'idEcole' => $idEcole,
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

        return view('classes.form', [
            'classe' => $classe,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'ordres' => $ordres,
            'ordreMatiereMap' => $ordreMatiereMap,
            'lignes' => $classe->ligneClasses,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);

        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        $data = $this->validateClasse($request);
        $this->ensureOrdreAllowed($data['ordre_enseignement'], $user, $classe->idEcole);

        DB::transaction(function () use ($classe, $data) {
            $classe->update([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $data['ordre_enseignement'],
            ]);

            $classe->ligneClasses()->delete();
            $this->syncLignesClasse($classe, $data);
        });

        return redirect()->route('classes.edit', $classe->id_classe)->with('success', 'Classe modifiée avec succès.');
    }

    public function destroy($id)
    {
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

    private function validateClasse(Request $request): array
    {
        return $request->validate([
            'nom_classe' => 'required|string|max:50',
            'ordre_enseignement' => 'required|string|max:50',
            'id_matiere' => 'required|array|min:1',
            'id_matiere.*' => 'required|integer|exists:matiere,id_matiere',
            'id_enseignants' => 'nullable|array',
            'id_enseignants.*' => 'nullable|integer|exists:enseignants,id_enseignant',
            'coefficient' => 'required|array|min:1',
            'coefficient.*' => 'required|numeric|min:0|max:5',
        ], [
            'id_matiere.required' => 'Au moins une matière doit être sélectionnée.',
            'coefficient.*.max' => 'Le coefficient ne peut pas dépasser 5.',
        ]);
    }

    private function syncLignesClasse(Classe $classe, array $data): void
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

    private function matieresDisponibles($user, ?int $idEcole)
    {
        return Matiere::query()
            ->with('ordres')
            ->when($user->droit !== 'SupAdmin', function ($query) use ($idEcole) {
                $query->where(function ($inner) use ($idEcole) {
                    $inner->whereNull('id_ecole')->orWhere('id_ecole', $idEcole);
                });
            })
            ->orderBy('nom_matiere')
            ->get();
    }

    private function enseignantsDisponibles($user, ?int $idEcole)
    {
        return Enseignant::query()
            ->where('is_deleted', 0)
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('id_ecole', $idEcole))
            ->orderBy('nom_prenom_enseignant')
            ->get();
    }

    private function ordresDisponibles($user, ?int $idEcole = null): array
    {
        $ecole = $this->resolveEcole($user, $idEcole);
        $typeEcole = $ecole->typeEcole ?? null;

        if ($typeEcole === 'Complexe Scolaire' || ($user->droit === 'SupAdmin' && !$ecole)) {
            return [
                'fondamentale1' => 'Fondamentale I (1 à 6)',
                'fondamentale2' => 'Fondamentale II (7 à 9)',
                'secondairegenerale' => 'Secondaire Général',
                'secondairetechniqueetprofessionnel' => 'Secondaire Technique et Professionnel',
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

    private function ensureOrdreAllowed(string $ordre, $user, ?int $idEcole = null): void
    {
        if (!array_key_exists($ordre, $this->ordresDisponibles($user, $idEcole))) {
            abort(422, "L'ordre d'enseignement sélectionné ne correspond pas au type de l'école.");
        }
    }


    private function resolveEcole($user, ?int $idEcole = null): ?Ecole
    {
        $resolvedId = $idEcole ?: session('idEcole') ?: $user->idEcole;

        if ($resolvedId) {
            return Ecole::withoutGlobalScopes()->find($resolvedId);
        }

        return $user->ecole;
    }

    private function ordreMatiereMap(): array
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
