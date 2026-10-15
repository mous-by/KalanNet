<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ClasseOfficielle;
use App\Models\Ecole;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Enseignant;
use App\Models\LigneClasse;
use App\Support\ClasseSuivante;
use App\Support\Domaines;
use App\Support\ExamenNational;
use App\Support\Ordres;
use App\Support\SchoolOrderAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClasseController extends Controller
{
    public function index()
    {
        $this->authorizePermission('classes_apercu');
        $idEcole = session('idEcole');
        $user = Auth::user();

        if ($user->droit === 'SupAdmin') {
            $classes = Classe::with(['ecole', 'classeOfficielle', 'filiere'])->withCount('eleves')->get();
        } else {
            $classes = Classe::with(['classeOfficielle', 'filiere'])->where('idEcole', $idEcole)->withCount('eleves')->get();
        }

        return view('classes.index', compact('classes'));
    }

    public function create(Request $request)
    {
        $this->authorizePermission('classes_creation');
        $idEcole = session('idEcole');
        $user = Auth::user();
        $suite = $request->filled('depuis') ? $this->suiteDe((int) $request->query('depuis'), $idEcole) : null;
        $ecole = $this->resolveEcole($user, $idEcole);
        $estSante = $this->estSante($ecole);

        $matieres = $this->matieresDisponibles($user, $idEcole, $estSante);
        $enseignants = $this->enseignantsDisponibles($user, $idEcole);
        $ordres = $this->ordresDisponibles($user, $idEcole);
        $ordreMatiereMap = $this->ordreMatiereMap();
        $filieres = $this->filieresDisponibles($idEcole);

        return view('classes.form', [
            'classe' => $suite['classe'] ?? new Classe(),
            'lignesSuite' => $suite['lignes'] ?? null,
            'classeSource' => $suite['source'] ?? null,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'ordres' => $ordres,
            'ordreMatiereMap' => $ordreMatiereMap,
            'filieres' => $filieres,
            'estSante' => $estSante,
            'lignes' => collect(),
            'mode' => 'create',
        ] + $this->optionsDomaines($ecole, $estSante));
    }

    public function associations(Request $request)
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin') {
            abort(403, 'Seul le SuperAdmin peut associer les classes aux classes officielles.');
        }

        $idEcole = $request->integer('id_ecole') ?: session('idEcole') ?: $user->idEcole;

        // Le referentiel "classes officielles" est le curriculum publie par le
        // ministere malien : seules les ecoles maliennes non-Ecole de Sante
        // y ont quelque chose a associer (les ecoles de sante ont leurs
        // filieres, les autres pays n'ont aucun referentiel gouvernemental
        // modelise dans l'appli).
        $maliId = \App\Models\Pays::where('code_iso', 'ML')->value('id');
        $ecoles = Ecole::query()
            ->where('typeEcole', '!=', 'École de Santé')
            ->where(function ($q) use ($maliId) {
                $q->whereNull('id_pays')->orWhere('id_pays', $maliId);
            })
            ->orderBy('nomEcole')
            ->get();

        if (!$ecoles->contains('idEcole', $idEcole)) {
            $idEcole = $ecoles->first()?->idEcole;
        }

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

        $classe = DB::transaction(function () use ($data, $idEcole, $estSante) {
            $classe = Classe::create([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $estSante ? null : $data['ordre_enseignement'],
                'idEcole' => $idEcole,
                'id_filiere' => $estSante ? $data['id_filiere'] : null,
                'serie' => $this->serieValide($data),
            ] + $this->champsDeuxMaitres($data));

            $this->syncLignesClasse($classe, $data);

            return $classe;
        });

        // Proposer de créer la classe suivante (même matières) si elle n'existe pas encore.
        $nomSuivant = ClasseSuivante::nom($classe->nom_classe);
        $existe = $nomSuivant && Classe::where('idEcole', $idEcole)->where('nom_classe', $nomSuivant)->exists();

        return redirect()->route('classes.index')
            ->with('success', 'Classe et matières enregistrées avec succès.')
            ->with('classe_suivante', $nomSuivant && !$existe ? [
                'id' => $classe->id_classe,
                'nom' => $classe->nom_classe,
                'suivante' => $nomSuivant,
            ] : null);
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
        $this->authorizePermission('classes_modification');
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
        ] + $this->optionsDomaines($classe->ecole, $this->estSante($classe->ecole)));
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
                'serie' => $this->serieValide($data),
            ] + $this->champsDeuxMaitres($data));

            $dejaPresentes = $classe->ligneClasses()->pluck('id_matiere')->all();
            $domainesPrecedents = $classe->ligneClasses()->pluck('id_domaine', 'id_matiere')->all();
            $groupesPrecedents = $classe->ligneClasses()->pluck('groupe', 'id_matiere')->all();
            $classe->ligneClasses()->delete();
            $this->syncLignesClasse($classe, $data, $dejaPresentes, $domainesPrecedents, $groupesPrecedents);
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
            'id_matiere' => 'exclude_if:ordre_enseignement,jardin|required|array|min:1',
            'id_matiere.*' => 'required|integer|exists:matiere,id_matiere',
            'id_enseignants' => 'nullable|array',
            'id_enseignants.*' => 'nullable|integer|exists:enseignants,id_enseignant',
            'coefficient' => 'exclude_if:ordre_enseignement,jardin|required|array|min:1',
            'coefficient.*' => 'required|numeric|min:0|max:5',
            'serie' => ['nullable', Rule::in(array_keys(\App\Support\Series::SERIES))],
            'groupe' => 'nullable|array',
            'groupe.*' => 'nullable|in:principale,secondaire',
            'id_domaine' => 'nullable|array',
            'id_domaine.*' => ['nullable', 'integer', Rule::exists('domaines', 'id_domaine')->where('id_ecole', $idEcole)],
            'deux_maitres' => 'nullable|boolean',
            // Jardin : un, deux maîtres ou plus pour la classe.
            'id_maitres' => 'nullable|array',
            'id_maitres.*' => ['nullable', 'integer', Rule::exists('enseignants', 'id_enseignant')->where('id_ecole', $idEcole)],
            'id_maitre_arabe' => ['exclude_unless:deux_maitres,1', 'required', 'integer', Rule::exists('enseignants', 'id_enseignant')->where('id_ecole', $idEcole)],
            'id_maitre_francais' => ['exclude_unless:deux_maitres,1', 'required', 'integer', 'different:id_maitre_arabe', Rule::exists('enseignants', 'id_enseignant')->where('id_ecole', $idEcole)],
        ];

        if ($estSante) {
            $rules['id_filiere'] = ['required', 'integer', Rule::exists('filieres', 'id_filiere')->where('id_ecole', $idEcole)];
            $rules['ordre_enseignement'] = 'nullable|string|max:50';
        } else {
            $rules['ordre_enseignement'] = 'required|string|max:50';
        }

        return $request->validate($rules, [
            'id_matiere.required' => 'Au moins une matière doit être sélectionnée.',
            'coefficient.*.max' => 'Le coefficient ne peut pas dépasser 5.',
            'id_filiere.required' => 'La filière est obligatoire pour une École de Santé.',
            'id_filiere.exists' => 'La filière sélectionnée n’appartient pas à cette école.',
            'id_maitre_arabe.required' => 'Choisissez le maître de la partie arabe.',
            'id_maitre_francais.required' => 'Choisissez le maître de la partie française.',
            'id_maitre_francais.different' => 'Le maître de la partie française doit être différent du maître de la partie arabe.',
        ]);
    }

    /**
     * Classe suivante à partir d'une classe de l'école : nom et cycle
     * suivants, mêmes matières (coefficient, domaine, groupe). Enseignants :
     * en Fondamental I, un enseignant qui n'a encore aucune classe est proposé
     * pour toutes les matières ; ailleurs, ils restent à choisir.
     */
    protected function suiteDe(int $idSource, ?int $idEcole): ?array
    {
        $source = Classe::with('ligneClasses.matiere')->where('idEcole', $idEcole)->find($idSource);
        if (!$source) {
            return null;
        }

        $ordre = ClasseSuivante::ordre($source->ordreEnseignement, $source->nom_classe);
        $classe = new Classe([
            'nom_classe' => ClasseSuivante::nom($source->nom_classe) ?? '',
            'ordreEnseignement' => $ordre,
            'id_filiere' => $source->id_filiere,
            'deux_maitres' => $source->deux_maitres,
        ]);

        $enseignantLibre = null;
        if (Ordres::surDix($ordre) && !$source->deux_maitres) {
            $occupes = DB::table('ligneclasse as lc')
                ->join('classe as c', 'c.id_classe', '=', 'lc.id_classe')
                ->where('c.idEcole', $idEcole)
                ->whereNotNull('lc.id_enseignants')
                ->pluck('lc.id_enseignants');
            $enseignantLibre = Enseignant::where('id_ecole', $idEcole)->where('is_deleted', 0)
                ->whereNotIn('id_enseignant', $occupes)
                ->orderBy('nom_prenom_enseignant')
                ->value('id_enseignant');
        }

        $lignes = $source->ligneClasses->map(fn ($ligne) => [
            'id_matiere' => $ligne->id_matiere,
            'nom_matiere' => $ligne->matiere->nom_matiere ?? '',
            'id_enseignants' => $enseignantLibre,
            'coefficient' => $ligne->coefficient ?? 1,
            'id_domaine' => $ligne->id_domaine,
            'groupe' => $ligne->groupe,
        ])->filter(fn ($row) => $row['nom_matiere'] !== '')->values();

        return ['classe' => $classe, 'lignes' => $lignes, 'source' => $source];
    }

    /** Domaines de l'école (colonne « Domaine ») et option « deux maîtres » (écoles franco-arabes). */
    protected function optionsDomaines(?Ecole $ecole, bool $estSante): array
    {
        // Les domaines ne concernent que les écoles franco-arabes.
        $domaines = $ecole && !$estSante && $ecole->franco_arabe
            ? Domaines::pourEcole((int) $ecole->idEcole)
            : collect();

        return [
            'domaines' => $domaines,
            'francoArabe' => (bool) ($ecole->franco_arabe ?? false),
        ];
    }

    /** Série (11ème / 12ème du secondaire général) : seulement pour ces classes. */
    protected function serieValide(array $data): ?string
    {
        $serie = $data['serie'] ?? null;
        if (!$serie || !str_starts_with((string) ($data['ordre_enseignement'] ?? ''), 'secondairegenerale')) {
            return null;
        }
        $niveau = \App\Support\ClasseSuivante::niveau($data['nom_classe'] ?? '');

        // Série des classes de 11ème seulement (elle oriente vers les séries de 12ème).
        return $niveau === 11 && (\App\Support\Series::SERIES[$serie][1] ?? null) === 11 ? $serie : null;
    }

    protected function champsDeuxMaitres(array $data): array
    {
        $deuxMaitres = !empty($data['deux_maitres']);

        return [
            'deux_maitres' => $deuxMaitres,
            'id_maitre_arabe' => $deuxMaitres ? $data['id_maitre_arabe'] : null,
            'id_maitre_francais' => $deuxMaitres ? $data['id_maitre_francais'] : null,
        ];
    }

    protected function filieresDisponibles(?int $idEcole)
    {
        return Filiere::where('id_ecole', $idEcole)->orderBy('nom_filiere')->get();
    }

    protected function estSante(?Ecole $ecole): bool
    {
        return ($ecole->typeEcole ?? null) === 'École de Santé';
    }

    /**
     * @param  int[]  $dejaPresentes  matières que la classe avait avant la modification :
     *                                 toujours conservées, même hors du catalogue actuel.
     */
    protected function syncLignesClasse(Classe $classe, array $data, array $dejaPresentes = [], array $domainesPrecedents = [], array $groupesPrecedents = []): void
    {
        $expectedOrdre = $this->ordreMatiereMap()[$classe->ordreEnseignement] ?? null;
        // Mêmes matières que celles proposées dans le formulaire : catalogue
        // franco-arabe ou classique, École de Santé, matières de l'école.
        $ecole = Ecole::withoutGlobalScopes()->find($classe->idEcole);
        $autorisees = $this->matieresDisponibles(Auth::user(), (int) $classe->idEcole, $this->estSante($ecole))
            ->pluck('id_matiere')->map(fn ($id) => (int) $id)->flip();
        $dejaPresentes = array_flip(array_map('intval', $dejaPresentes));
        $parties = DB::table('domaines')->where('id_ecole', $classe->idEcole)->pluck('partie', 'id_domaine')->all();
        // Jardin : ni matières ni notes, mais un ou plusieurs maîtres.
        if (Ordres::sansNotes($classe->ordreEnseignement)) {
            // Une application qui n'envoie pas encore la liste garde les maîtres déjà enregistrés.
            if (array_key_exists('id_maitres', $data)) {
                $classe->maitres()->sync(array_values(array_unique(array_filter(array_map('intval', $data['id_maitres'] ?? [])))));
            }

            return;
        }
        $classe->maitres()->detach();
        foreach ($data['id_matiere'] ?? [] as $index => $idMatiere) {
            $conservee = isset($dejaPresentes[(int) $idMatiere]);
            if (!$conservee && !$autorisees->has((int) $idMatiere)) {
                continue;
            }
            if ($expectedOrdre && !$conservee) {
                $valid = Matiere::whereKey($idMatiere)
                    ->whereHas('ordres', fn ($query) => $query->where('ordre_enseignement', $expectedOrdre))
                    ->exists();
                if (!$valid) {
                    continue;
                }
            }
            // Colonne « Domaine » absente du formulaire : on garde le domaine déjà choisi.
            $idDomaine = array_key_exists('id_domaine', $data)
                ? ($data['id_domaine'][$index] ?? null)
                : ($domainesPrecedents[(int) $idMatiere] ?? null);
            $idEnseignant = $data['id_enseignants'][$index] ?? null;
            // Classe à deux maîtres : une matière sans enseignant choisi revient
            // au maître de la partie (arabe / française) de son domaine.
            if (!$idEnseignant && $classe->deux_maitres && $idDomaine) {
                $idEnseignant = match ($parties[(int) $idDomaine] ?? null) {
                    'arabe' => $classe->id_maitre_arabe,
                    'francais' => $classe->id_maitre_francais,
                    default => null,
                };
            }
            LigneClasse::create([
                'id_classe' => $classe->id_classe,
                'id_matiere' => $idMatiere,
                'id_enseignants' => $idEnseignant,
                'coefficient' => $data['coefficient'][$index] ?? 1,
                'id_domaine' => $idDomaine,
                // Groupe (matière principale / secondaire) : conservé si la colonne n'est pas envoyée.
                'groupe' => array_key_exists('groupe', $data)
                    ? (($data['groupe'][$index] ?? null) ?: null)
                    : ($groupesPrecedents[(int) $idMatiere] ?? null),
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
            ->when(!$estSante && $idEcole, fn ($query) => $query->visiblesPourEcole(Ecole::withoutGlobalScopes()->find($idEcole)))
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
        $ordres = $this->ordresDuType($user, $idEcole);
        $ecole = $this->resolveEcole($user, $idEcole);

        // Le Jardin accompagne chaque type d'école (Fondamental I + Jardin,
        // Collège + Jardin...) ; le complexe scolaire l'a déjà avec tous ses ordres.
        if ($ecole && !isset($ordres[Ordres::JARDIN]) && Ordres::proposeJardin($ecole->typeEcole)
            && $ecole->typeEcole !== 'Complexe Scolaire') {
            $ordres = [Ordres::JARDIN => ExamenNational::ordresLabels($ecole)[Ordres::JARDIN]] + $ordres;
        }

        return $ordres;
    }

    protected function ordresDuType($user, ?int $idEcole = null): array
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
            'jardin' => 'Jardin',
            'fondamentale1' => 'Fondamentale I',
            'fondamentale2' => 'Fondamentale II',
            'secondairegenerale' => 'Secondaire Generale',
            'secondairetechniqueetprofessionnel' => 'Secondaire Technique et Professionnel',
            'secondaire' => 'Secondaire Generale',
            'technique' => 'Secondaire Technique et Professionnel',
        ];
    }
}
