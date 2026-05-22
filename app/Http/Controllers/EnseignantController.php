<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use App\Models\LigneClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class EnseignantController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Enseignant::query();

        if (in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP'], true)) {
            // Handled by global query scopes automatically!
        } elseif ($user->droit === 'Admin' || $user->droit === 'Gestionnaire') {
            // Handled by global query scopes automatically!
        } elseif ($user->droit === 'enseignant') {
            $query->where('id_enseignant', $user->id_enseignant);
        } else {
            return abort(403, 'Accès non autorisé');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($inner) use ($search) {
                $inner->where('nom_prenom_enseignant', 'LIKE', "%{$search}%")
                    ->orWhere('email_enseignant', 'LIKE', "%{$search}%")
                    ->orWhere('telephone_enseignant', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        $enseignants = $query->orderBy('nom_prenom_enseignant')->paginate(20)->withQueryString();

        return view('enseignants.index', compact('enseignants'));
    }

    public function create()
    {
        $this->authorizeTeacherManagement('create');
        $ecole = Auth::user()->ecole;
        return view('enseignants.form', [
            'enseignant' => new Enseignant(),
            'mode' => 'create',
            'ecole' => $ecole,
            'contratsAutorises' => $this->contratsAutorises($ecole),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeTeacherManagement('create');
        $data = $this->validateEnseignant($request);
        $data['avatar_enseignant'] = $this->storeAvatar($request);
        $data['pwd'] = Hash::make('123456');
        $data['id_ecole'] = session('idEcole');
        $data['matricule'] = $data['matricule'] ?: $this->generateMatricule($data);

        Enseignant::create($this->mapFields($data));

        return redirect()->route('enseignants.index')->with('success', 'Enseignant ajouté avec succès.');
    }

    public function show($id)
    {
        $enseignant = Enseignant::with([
            'ecole',
            'emargements.classe',
            'emargements.matiere',
            'emargements.trimestre',
            'presences.classe',
            'presences.trimestre',
        ])
            ->findOrFail($id);
        
        // Authorization check
        $this->authorizeEnseignant($enseignant);

        $lignesClasses = LigneClasse::with(['classe', 'matiere'])
            ->where('id_enseignants', $enseignant->id_enseignant)
            ->orderBy('id_classe')
            ->get();

        $recentEmargements = $enseignant->emargements()
            ->with(['classe', 'matiere', 'trimestre', 'lecon'])
            ->orderByDesc('date_emargement')
            ->limit(8)
            ->get();

        $emargementStats = [
            'total' => $enseignant->emargements()->count(),
            'valides' => $enseignant->emargements()->where('valide', 1)->count(),
            'heures' => $enseignant->emargements()->sum('nombre_heure'),
        ];

        $recentPresences = $enseignant->presences()
            ->with(['classe', 'trimestre', 'lecons'])
            ->orderByDesc('date_presence')
            ->limit(8)
            ->get();

        $presenceStats = [
            'total' => $enseignant->presences()->count(),
            'valides' => $enseignant->presences()->where('valide', 1)->count(),
            'heures' => $enseignant->presences()->sum('nombre_heure'),
        ];
        $programmeProgress = $this->teacherProgrammeProgress($enseignant, $lignesClasses);
        $validatedHours = (float) $enseignant->emargements()->where('valide', 1)->sum('nombre_heure');
        $vctPayment = [
            'eligible' => $enseignant->type_contrat_enseignant === 'VCT',
            'heures_validees' => $validatedHours,
            'prix_heure' => (float) ($enseignant->prix_heure ?? 0),
            'montant' => $enseignant->type_contrat_enseignant === 'VCT' ? $validatedHours * (float) ($enseignant->prix_heure ?? 0) : 0,
        ];

        return view('enseignants.show', compact(
            'enseignant',
            'lignesClasses',
            'recentEmargements',
            'emargementStats',
            'recentPresences',
            'presenceStats',
            'programmeProgress',
            'vctPayment'
        ));
    }

    public function edit($id)
    {
        $this->authorizeTeacherManagement('update');
        $enseignant = Enseignant::findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        return view('enseignants.form', [
            'enseignant' => $enseignant,
            'mode' => 'edit',
            'ecole' => Auth::user()->ecole,
            'contratsAutorises' => $this->contratsAutorises(Auth::user()->ecole),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeTeacherManagement('update');
        $enseignant = Enseignant::findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        $data = $this->validateEnseignant($request, $enseignant->id_enseignant);
        $data['matricule'] = $data['matricule'] ?: $this->generateMatricule($data);

        $mapped = $this->mapFields($data);
        $avatar = $this->storeAvatar($request, $enseignant->avatar_enseignant);
        if ($avatar) {
            $mapped['avatar_enseignant'] = $avatar;
        }

        $enseignant->update($mapped);

        return redirect()->route('enseignants.edit', $enseignant->id_enseignant)->with('success', 'Modification effectuée avec succès.');
    }

    public function archive($id)
    {
        $this->authorizeTeacherManagement('archive');
        $enseignant = Enseignant::findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        $enseignant->update([
            'is_deleted' => 1,
            'deleted_at' => now(),
        ]);

        return redirect()->route('enseignants.index')->with('success', 'Enseignant archivé avec succès.');
    }

    public function reactivate($id)
    {
        $this->authorizeTeacherManagement('archive');
        $enseignant = Enseignant::findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        $enseignant->update([
            'is_deleted' => 0,
            'deleted_at' => null,
        ]);

        return redirect()->route('enseignants.index')->with('success', 'Enseignant réactivé avec succès.');
    }

    private function validateEnseignant(Request $request, ?int $ignoreId = null): array
    {
        $contratsAutorises = implode(',', array_keys($this->contratsAutorises(Auth::user()->ecole)));
        return $request->validate([
            'nom_prenom' => 'required|string|max:200',
            'genre' => 'required|string|in:Feminin,Masculin,Féminin',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('enseignants', 'email_enseignant')->ignore($ignoreId, 'id_enseignant')],
            'telephone' => ['required', 'digits:8', Rule::unique('enseignants', 'telephone_enseignant')->ignore($ignoreId, 'id_enseignant')],
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:100',
            'diplome' => 'required|string|max:100',
            'type_contrat' => 'required|string|in:' . $contratsAutorises,
            'salaire' => 'nullable|numeric|min:0',
            'duree_contrat' => 'nullable|string|max:100',
            'nombre_heure' => 'nullable|numeric|min:0',
            'prix_heure' => 'nullable|numeric|min:0',
            'matricule' => ['nullable', 'string', 'max:100', Rule::unique('enseignants', 'matricule')->ignore($ignoreId, 'id_enseignant')],
            'avatar' => 'nullable|image|max:5120',
            'statut_matrimonial' => 'nullable|string|max:50',
            'nombre_enfants' => 'nullable|integer|min:0',
            'pere_nom_prenom' => 'nullable|string|max:255',
            'mere_nom_prenom' => 'nullable|string|max:255',
            'specialite' => 'nullable|string|max:255',
            'service_employeur' => 'nullable|string|max:255',
            'anciennete_annees' => 'nullable|integer|min:0',
        ]);
    }

    private function contratsAutorises($ecole): array
    {
        $statut = strtolower((string) ($ecole->statut ?? ''));
        $type = strtolower((string) ($ecole->typeEcole ?? ''));
        $isPublic = str_contains($statut, 'public') || str_contains($type, 'public');
        $isPrivate = str_contains($statut, 'priv') || str_contains($type, 'priv');

        if ($isPublic && !$isPrivate) {
            return ['FONCTIONNAIRE' => 'FONCTIONNAIRE', 'VCT' => 'VCT'];
        }

        return ['CDI' => 'CDI', 'CDD' => 'CDD', 'VCT' => 'VCT'];
    }

    private function teacherProgrammeProgress(Enseignant $enseignant, $lignesClasses)
    {
        return $lignesClasses->map(function ($ligne) use ($enseignant) {
            $lessons = DB::table('classe as c')
                ->join('programme_classes as pc', function ($join) use ($ligne) {
                    $join->on('c.id_classe_officielle', '=', 'pc.id_classe')
                        ->where('pc.id_matiere', '=', $ligne->id_matiere);
                })
                ->join('programme_lecons as pl', 'pc.id_programme_classe', '=', 'pl.id_programme_classe')
                ->where('c.id_classe', $ligne->id_classe)
                ->select('pl.id_lecon', 'pl.numero', 'pl.titre')
                ->orderBy('pl.numero')
                ->get();

            $completedIds = $enseignant->emargements()
                ->where('id_classe', $ligne->id_classe)
                ->where('id_matiere', $ligne->id_matiere)
                ->where('valide', 1)
                ->pluck('id_lecon')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $total = $lessons->count();
            $completed = $lessons->whereIn('id_lecon', $completedIds)->count();
            $next = $lessons->first(fn ($lesson) => !$completedIds->contains((int) $lesson->id_lecon));

            return [
                'classe' => $ligne->classe,
                'matiere' => $ligne->matiere,
                'total' => $total,
                'completed' => $completed,
                'percent' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'next' => $next,
            ];
        });
    }

    private function mapFields(array $data): array
    {
        $isPublic = $data['type_contrat'] === 'FONCTIONNAIRE';
        $isSalariedPrivateContract = in_array($data['type_contrat'], ['CDI', 'CDD'], true);

        $mapped = [
            'nom_prenom_enseignant' => $data['nom_prenom'],
            'genre_enseignant' => $data['genre'] === 'Féminin' ? 'Feminin' : $data['genre'],
            'email_enseignant' => $data['email'] ?? null,
            'telephone_enseignant' => $data['telephone'],
            'date_naissance_enseignant' => $data['date_naissance'],
            'lieu_naissance_enseignant' => $data['lieu_naissance'],
            'diplome_enseignant' => $data['diplome'],
            'type_contrat_enseignant' => $data['type_contrat'],
            'matricule' => $data['matricule'],
            'salaire_enseignant' => $isSalariedPrivateContract ? ($data['salaire'] ?? null) : null,
            'duree_contrat' => (!$isPublic && $data['type_contrat'] === 'CDD') ? ($data['duree_contrat'] ?? null) : null,
            'nombre_heure' => (!$isPublic && $data['type_contrat'] === 'VCT') ? ($data['nombre_heure'] ?? null) : null,
            'prix_heure' => (!$isPublic && $data['type_contrat'] === 'VCT') ? ($data['prix_heure'] ?? null) : null,
            'specialite' => $data['specialite'] ?? null,
            'statut_matrimonial' => $isPublic ? ($data['statut_matrimonial'] ?? null) : null,
            'nombre_enfants' => $isPublic ? ($data['nombre_enfants'] ?? 0) : null,
            'pere_nom_prenom' => $isPublic ? ($data['pere_nom_prenom'] ?? null) : null,
            'mere_nom_prenom' => $isPublic ? ($data['mere_nom_prenom'] ?? null) : null,
            'service_employeur' => $isPublic ? ($data['service_employeur'] ?? null) : null,
            'anciennete_annees' => $isPublic ? ($data['anciennete_annees'] ?? 0) : null,
        ];

        foreach (['avatar_enseignant', 'pwd', 'id_ecole'] as $optionalField) {
            if (array_key_exists($optionalField, $data)) {
                $mapped[$optionalField] = $data[$optionalField];
            }
        }

        return $mapped;
    }

    private function generateMatricule(array $data): string
    {
        $age = now()->diffInYears(\Carbon\Carbon::parse($data['date_naissance']));
        $genre = strtoupper(substr($data['genre'], 0, 1));
        $lieu = strtoupper(substr(preg_replace('/\s+/', '', $data['lieu_naissance']), 0, 3));
        $contrat = strtoupper(substr($data['type_contrat'], 0, 3));

        return 'Mle' . $age . $genre . '-' . $lieu . '-' . $contrat;
    }

    private function storeAvatar(Request $request, ?string $currentAvatar = null): ?string
    {
        if (!$request->hasFile('avatar')) {
            return $currentAvatar ?: 'assets/images/avatars/avatar-1.png';
        }

        $directory = public_path('images_enseignant');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file('avatar');
        $name = uniqid('enseignant_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $name);

        return 'images_enseignant/' . $name;
    }

    private function authorizeEnseignant(Enseignant $enseignant): void
    {
        $user = Auth::user();
        if ($user->droit === 'SupAdmin') {
            return;
        }

        if ($user->droit === 'DAE') {
            $school = \App\Models\Ecole::withoutGlobalScopes()->find($enseignant->id_ecole);
            if ($school && $school->id_academie === $user->id_academie) {
                return;
            }
        }

        if ($user->droit === 'DCAP') {
            $school = \App\Models\Ecole::withoutGlobalScopes()->find($enseignant->id_ecole);
            if ($school && $school->id_cap === $user->id_cap) {
                return;
            }
        }

        if ($user->id_enseignant === $enseignant->id_enseignant) {
            return;
        }

        if (in_array($user->droit, ['Admin', 'Gestionnaire'], true) && session('idEcole') === $enseignant->id_ecole) {
            return;
        }

        abort(403, 'Accès non autorisé');
    }

    private function authorizeTeacherManagement(string $action): void
    {
        $user = Auth::user();

        if ($user->droit === 'SupAdmin') {
            return;
        }

        $permissions = [
            'create' => ['enseignants_creation', 'enseignants_création'],
            'update' => ['enseignants_modification'],
            'archive' => ['enseignants_archiver_ou_reactiver', 'enseignants_archiver ou réactiver'],
        ][$action] ?? [];

        if ($user->userHasAnyPermission($permissions)) {
            return;
        }

        abort(403, 'Permission insuffisante.');
    }
}
