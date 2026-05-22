<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Emargement;
use App\Models\EmploiDuTemps;
use App\Models\Enseignant;
use App\Models\LigneClasse;
use App\Models\Matiere;
use App\Models\Permission;
use App\Models\ProgrammeClasse;
use App\Models\ProgrammeLecon;
use App\Models\ProgrammeOfficiel;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmargementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $this->authorizePermission('emargement_faire');

        $query = $this->scopeForUser(
            Emargement::with(['enseignant', 'classe', 'matiere', 'trimestre', 'anneeScolaire', 'lecon']),
            $user,
            $idEcole
        );

        $query
            ->when($request->filled('id_enseignant'), fn ($q) => $q->where('id_enseignant', $request->id_enseignant))
            ->when($request->filled('id_classe'), fn ($q) => $q->where('id_classe', $request->id_classe))
            ->when($request->filled('id_matiere'), fn ($q) => $q->where('id_matiere', $request->id_matiere))
            ->when($request->filled('valide'), fn ($q) => $q->where('valide', $request->valide))
            ->when($request->filled('date_debut'), fn ($q) => $q->whereDate('date_emargement', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn ($q) => $q->whereDate('date_emargement', '<=', $request->date_fin));

        $emargements = $query
            ->orderByDesc('date_emargement')
            ->paginate(20)
            ->withQueryString();

        return view('enseignants.emargements', [
            'emargements' => $emargements,
            'enseignants' => $this->enseignantsForUser($user, $idEcole)->orderBy('nom_prenom_enseignant')->get(),
            'classes' => $this->classesForUser($user, $idEcole)->orderBy('nom_classe')->get(),
            'matieres' => Matiere::orderBy('nom_matiere')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'lecons' => ProgrammeLecon::orderBy('numero')->orderBy('titre')->get(),
            'emargementFormData' => $this->emargementFormData($user, $idEcole),
            'emargementPermissions' => $this->emargementPermissions($user),
            'currentAcademicYearId' => $this->currentAcademicYearId(),
            'smartSuggestion' => $this->smartSuggestion($user, $idEcole),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('emargement_faire');

        DB::transaction(function () use ($request) {
            $data = $this->validatedData($request);
            $data['id_ecole'] = session('idEcole') ?: Auth::user()->idEcole;
            $data['valide'] = 0;

            Emargement::create($data);
        });

        return redirect()->route('enseignants.emargements')->with('success', 'Émargement enregistré avec succès.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission($this->emargementActionPermission('edit'));
        $emargement = Emargement::findOrFail($id);
        $this->authorizeEmargement($emargement);

        if ($emargement->valide) {
            return redirect()->route('enseignants.emargements')->with('error', 'Un émargement validé ne peut plus être modifié.');
        }

        DB::transaction(function () use ($request, $emargement) {
            $emargement->update($this->validatedData($request));
        });

        return redirect()->route('enseignants.emargements')->with('success', 'Émargement modifié avec succès.');
    }

    public function validateEmargement(int $id)
    {
        $this->authorizePermission('emargement_validation_admin');
        $emargement = Emargement::findOrFail($id);
        $this->authorizeEmargement($emargement, true);

        $emargement->update(['valide' => 1]);

        return redirect()->route('enseignants.emargements')->with('success', 'Émargement validé avec succès.');
    }

    public function destroy(int $id)
    {
        $this->authorizePermission($this->emargementActionPermission('delete'));
        $emargement = Emargement::findOrFail($id);
        $this->authorizeEmargement($emargement);

        if ($emargement->valide) {
            return redirect()->route('enseignants.emargements')->with('error', 'Impossible de supprimer un émargement validé.');
        }

        $emargement->delete();

        return redirect()->route('enseignants.emargements')->with('success', 'Émargement supprimé avec succès.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'chapitre' => 'nullable|string|max:255',
            'id_lecon' => 'nullable|integer|exists:programme_lecons,id_lecon',
            'new_lecon_titre' => 'nullable|string|max:255',
            'nombre_heure' => 'required|numeric|min:0.25|max:24',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'id_anneeScolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_emargement' => 'required|date',
        ]);

        $user = Auth::user();
        if ($user->droit === 'enseignant') {
            $data['id_enseignant'] = $user->id_enseignant;
        }

        $assigned = LigneClasse::query()
            ->where('id_enseignants', $data['id_enseignant'])
            ->where('id_classe', $data['id_classe'])
            ->where('id_matiere', $data['id_matiere'])
            ->exists();

        if (!$assigned) {
            throw ValidationException::withMessages([
                'id_matiere' => 'Cette matière n’est pas assignée à cet enseignant pour cette classe.',
            ]);
        }

        $data['id_lecon'] = $this->resolveLessonId($data);

        unset($data['new_lecon_titre']);

        return $data;
    }

    private function scopeForUser($query, $user, ?int $idEcole)
    {
        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        if ($user->droit === 'enseignant') {
            return $query->where('id_enseignant', $user->id_enseignant);
        }

        return $query->where('id_ecole', $idEcole);
    }

    private function enseignantsForUser($user, ?int $idEcole)
    {
        $query = Enseignant::query();

        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        if ($user->droit === 'enseignant') {
            return $query->where('id_enseignant', $user->id_enseignant);
        }

        return $query->where('id_ecole', $idEcole);
    }

    private function classesForUser($user, ?int $idEcole)
    {
        $query = Classe::query();

        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        return $query->where('idEcole', $idEcole);
    }

    private function authorizeEmargement(Emargement $emargement, bool $validation = false): void
    {
        $user = Auth::user();

        if ($user->droit === 'SupAdmin') {
            return;
        }

        if ($validation && $user->droit === 'enseignant') {
            abort(403);
        }

        if ($user->droit === 'enseignant' && $emargement->id_enseignant === $user->id_enseignant) {
            return;
        }

        if ($emargement->id_ecole === (session('idEcole') ?: $user->idEcole)) {
            return;
        }

        abort(403);
    }

    private function emargementFormData($user, ?int $idEcole): array
    {
        $assignments = $this->ligneClasseForUser($user, $idEcole)
            ->with(['enseignant', 'classe', 'matiere'])
            ->get()
            ->filter(fn ($line) => $line->enseignant && $line->classe && $line->matiere);

        $classesByTeacher = [];
        $matieresByTeacherClasse = [];

        foreach ($assignments as $line) {
            $teacherId = (int) $line->id_enseignants;
            $classId = (int) $line->id_classe;
            $matterId = (int) $line->id_matiere;

            $classesByTeacher[$teacherId][$classId] = [
                'id' => $classId,
                'label' => $line->classe->nom_classe,
            ];

            $matieresByTeacherClasse[$teacherId . '_' . $classId][$matterId] = [
                'id' => $matterId,
                'label' => $line->matiere->nom_matiere,
            ];
        }

        $completedByClasseMatiere = Emargement::query()
            ->whereIn('id_enseignant', $assignments->pluck('id_enseignants')->unique()->filter())
            ->get(['id_classe', 'id_matiere', 'id_lecon'])
            ->groupBy(fn ($emargement) => $emargement->id_classe . '_' . $emargement->id_matiere)
            ->map(fn ($items) => $items->pluck('id_lecon')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all())
            ->all();

        $rawLeconsByClasseMatiere = DB::table('classe as c')
            ->join('programme_classes as pc', function ($join) {
                $join->on('c.id_classe_officielle', '=', 'pc.id_classe');
            })
            ->join('programme_lecons as pl', 'pc.id_programme_classe', '=', 'pl.id_programme_classe')
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('c.idEcole', $idEcole))
            ->select('c.id_classe', 'pc.id_matiere', 'pl.id_lecon', 'pl.numero', 'pl.titre')
            ->orderBy('pl.numero')
            ->get()
            ->groupBy(fn ($lecon) => $lecon->id_classe . '_' . $lecon->id_matiere);

        $leconsByClasseMatiere = $rawLeconsByClasseMatiere
            ->map(function ($items, $key) use ($completedByClasseMatiere) {
                $completedLessonIds = $completedByClasseMatiere[$key] ?? [];

                return $items->map(function ($lecon) use ($completedLessonIds) {
                    $done = in_array((int) $lecon->id_lecon, $completedLessonIds, true);

                    return [
                        'id' => (int) $lecon->id_lecon,
                        'label' => trim(($lecon->numero ? $lecon->numero . ' - ' : '') . $lecon->titre),
                        'done' => $done,
                    ];
                })->values();
            })
            ->toArray();

        $progressByClasseMatiere = $rawLeconsByClasseMatiere
            ->map(function ($items, $key) use ($completedByClasseMatiere) {
                $completedLessonIds = $completedByClasseMatiere[$key] ?? [];
                $total = $items->count();
                $completed = $items->filter(fn ($lecon) => in_array((int) $lecon->id_lecon, $completedLessonIds, true))->count();
                $next = $items->first(fn ($lecon) => !in_array((int) $lecon->id_lecon, $completedLessonIds, true));

                return [
                    'total' => $total,
                    'completed' => $completed,
                    'percent' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                    'next_lecon_id' => $next ? (int) $next->id_lecon : null,
                ];
            })
            ->toArray();

        return [
            'classesByTeacher' => collect($classesByTeacher)->map(fn ($items) => array_values($items))->toArray(),
            'matieresByTeacherClasse' => collect($matieresByTeacherClasse)->map(fn ($items) => array_values($items))->toArray(),
            'leconsByClasseMatiere' => $leconsByClasseMatiere,
            'progressByClasseMatiere' => $progressByClasseMatiere,
        ];
    }

    private function ligneClasseForUser($user, ?int $idEcole)
    {
        $query = LigneClasse::query();

        if ($user->droit === 'enseignant') {
            return $query->where('id_enseignants', $user->id_enseignant);
        }

        if ($user->droit !== 'SupAdmin') {
            return $query->whereHas('classe', fn ($classe) => $classe->where('idEcole', $idEcole));
        }

        return $query;
    }

    private function currentAcademicYearId(): ?int
    {
        $today = now()->toDateString();

        return AnneeScolaire::query()
            ->whereDate('date_debut', '<=', $today)
            ->whereDate('date_fin', '>=', $today)
            ->value('id_anneeScolaire');
    }

    private function resolveLessonId(array $data): int
    {
        if (!empty($data['id_lecon'])) {
            $leconExists = DB::table('classe as c')
                ->join('programme_classes as pc', function ($join) use ($data) {
                    $join->on('c.id_classe_officielle', '=', 'pc.id_classe')
                        ->where('pc.id_matiere', '=', $data['id_matiere']);
                })
                ->join('programme_lecons as pl', 'pc.id_programme_classe', '=', 'pl.id_programme_classe')
                ->where('c.id_classe', $data['id_classe'])
                ->where('pl.id_lecon', $data['id_lecon'])
                ->exists();

            if ($leconExists) {
                return (int) $data['id_lecon'];
            }

            throw ValidationException::withMessages([
                'id_lecon' => 'Cette leçon ne correspond pas à la classe et à la matière sélectionnées.',
            ]);
        }

        if (blank($data['new_lecon_titre'] ?? null)) {
            throw ValidationException::withMessages([
                'id_lecon' => 'Sélectionnez une leçon ou créez la leçon effectuée.',
            ]);
        }

        $programmeClasse = $this->resolveProgrammeClasse((int) $data['id_classe'], (int) $data['id_matiere']);
        $normalizedTitle = $this->normalizeLessonTitle($data['new_lecon_titre']);

        $existing = $programmeClasse->lecons()
            ->get()
            ->first(fn ($lecon) => $this->normalizeLessonTitle($lecon->titre) === $normalizedTitle);

        if ($existing) {
            return (int) $existing->id_lecon;
        }

        $nextNumber = ((int) $programmeClasse->lecons()->max('numero')) + 1;

        return (int) ProgrammeLecon::create([
            'id_programme_classe' => $programmeClasse->id_programme_classe,
            'numero' => $nextNumber,
            'titre' => trim($data['new_lecon_titre']),
        ])->id_lecon;
    }

    private function resolveProgrammeClasse(int $idClasse, int $idMatiere): ProgrammeClasse
    {
        $classe = Classe::with('classeOfficielle')->findOrFail($idClasse);

        if (!$classe->id_classe_officielle) {
            throw ValidationException::withMessages([
                'id_classe' => 'Cette classe n’est pas associée à une classe officielle.',
            ]);
        }

        $programmeClasse = ProgrammeClasse::query()
            ->where('id_classe', $classe->id_classe_officielle)
            ->where('id_matiere', $idMatiere)
            ->first();

        if ($programmeClasse) {
            return $programmeClasse;
        }

        $programme = ProgrammeOfficiel::create([
            'date_creation' => now(),
            'id_utilisateur' => Auth::id(),
            'officiel' => 1,
        ]);

        return ProgrammeClasse::create([
            'id_programme' => $programme->id_programme,
            'id_classe' => $classe->id_classe_officielle,
            'id_matiere' => $idMatiere,
            'pour_toutes_ecoles' => 1,
        ]);
    }

    private function normalizeLessonTitle(?string $title): string
    {
        return Str::of($title ?? '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function smartSuggestion($user, ?int $idEcole): array
    {
        if (!$user->id_enseignant) {
            return [];
        }

        $currentAcademicYearId = $this->currentAcademicYearId();
        $jour = $this->currentFrenchDayName();
        $now = now()->format('H:i:s');

        $course = EmploiDuTemps::query()
            ->where('id_enseignant', $user->id_enseignant)
            ->when($currentAcademicYearId, fn ($query) => $query->where('id_annee_scolaire', $currentAcademicYearId))
            ->where('jour', $jour)
            ->whereTime('heure_debut', '<=', $now)
            ->whereTime('heure_fin', '>=', $now)
            ->orderBy('heure_debut')
            ->first();

        if (!$course) {
            $course = EmploiDuTemps::query()
                ->where('id_enseignant', $user->id_enseignant)
                ->when($currentAcademicYearId, fn ($query) => $query->where('id_annee_scolaire', $currentAcademicYearId))
                ->where('jour', $jour)
                ->whereTime('heure_debut', '>=', $now)
                ->orderBy('heure_debut')
                ->first();
        }

        if (!$course) {
            return [];
        }

        $start = \Carbon\Carbon::parse($course->heure_debut);
        $end = \Carbon\Carbon::parse($course->heure_fin);

        return [
            'teacher' => (int) $course->id_enseignant,
            'classe' => (int) $course->id_classe,
            'matiere' => (int) $course->id_matiere,
            'nombre_heure' => max(0.25, round($start->diffInMinutes($end) / 60, 2)),
            'source' => 'emploi_du_temps',
        ];
    }

    private function currentFrenchDayName(): string
    {
        return [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ][(int) now()->isoWeekday()];
    }

    private function emargementPermissions($user): array
    {
        return [
            'create' => $this->hasPermission($user, 'emargement_faire'),
            'validate' => $this->hasPermission($user, 'emargement_validation_admin'),
            'edit' => $this->hasPermission($user, $this->emargementActionPermission('edit')),
            'delete' => $this->hasPermission($user, $this->emargementActionPermission('delete')),
            'payment' => $this->hasPermission($user, 'emargement_paiement enseignant'),
            'payment_state' => $this->hasPermission($user, 'emargement_etat de payement'),
        ];
    }

    private function emargementActionPermission(string $action): string
    {
        $permission = [
            'edit' => 'emargement_modification',
            'delete' => 'emargement_supprimer',
        ][$action];

        if (Permission::where('name', $permission)->exists()) {
            return $permission;
        }

        return 'emargement_faire';
    }

    private function hasPermission($user, string $permission): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission($permission);
    }

    private function authorizePermission(string $permission): void
    {
        if (!$this->hasPermission(Auth::user(), $permission)) {
            abort(403);
        }
    }
}
