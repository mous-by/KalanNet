<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Emargement;
use App\Models\Enseignant;
use App\Models\LigneClasse;
use App\Models\Matiere;
use App\Models\Permission;
use App\Models\ProgrammeLecon;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('emargement_faire');
        $data = $this->validatedData($request);
        $data['id_ecole'] = session('idEcole') ?: Auth::user()->idEcole;
        $data['valide'] = 0;

        Emargement::create($data);

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

        $emargement->update($this->validatedData($request));

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
            'id_lecon' => 'required|integer|exists:programme_lecons,id_lecon',
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

        $leconExists = DB::table('classe as c')
            ->join('programme_classes as pc', function ($join) use ($data) {
                $join->on('c.id_classe_officielle', '=', 'pc.id_classe')
                    ->where('pc.id_matiere', '=', $data['id_matiere']);
            })
            ->join('programme_lecons as pl', 'pc.id_programme_classe', '=', 'pl.id_programme_classe')
            ->where('c.id_classe', $data['id_classe'])
            ->where('pl.id_lecon', $data['id_lecon'])
            ->exists();

        if (!$leconExists) {
            throw ValidationException::withMessages([
                'id_lecon' => 'Cette leçon ne correspond pas à la classe et à la matière sélectionnées.',
            ]);
        }

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

        $leconsByClasseMatiere = DB::table('classe as c')
            ->join('programme_classes as pc', function ($join) {
                $join->on('c.id_classe_officielle', '=', 'pc.id_classe');
            })
            ->join('programme_lecons as pl', 'pc.id_programme_classe', '=', 'pl.id_programme_classe')
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('c.idEcole', $idEcole))
            ->select('c.id_classe', 'pc.id_matiere', 'pl.id_lecon', 'pl.numero', 'pl.titre')
            ->orderBy('pl.numero')
            ->get()
            ->groupBy(fn ($lecon) => $lecon->id_classe . '_' . $lecon->id_matiere)
            ->map(fn ($items) => $items->map(fn ($lecon) => [
                'id' => (int) $lecon->id_lecon,
                'label' => trim(($lecon->numero ? $lecon->numero . ' - ' : '') . $lecon->titre),
            ])->values())
            ->toArray();

        return [
            'classesByTeacher' => collect($classesByTeacher)->map(fn ($items) => array_values($items))->toArray(),
            'matieresByTeacherClasse' => collect($matieresByTeacherClasse)->map(fn ($items) => array_values($items))->toArray(),
            'leconsByClasseMatiere' => $leconsByClasseMatiere,
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
