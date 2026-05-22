<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\LigneClasse;
use App\Models\Permission;
use App\Models\Presence;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresenceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $this->authorizePermission('presence_apercu');

        $query = $this->scopeForUser(
            Presence::with(['enseignant', 'classe', 'trimestre', 'anneeScolaire', 'lecons']),
            $user,
            $idEcole
        );

        $query
            ->when($request->filled('id_enseignant'), fn ($q) => $q->where('id_enseignant', $request->id_enseignant))
            ->when($request->filled('id_classe'), fn ($q) => $q->where('id_classe', $request->id_classe))
            ->when($request->filled('valide'), fn ($q) => $q->where('valide', $request->valide))
            ->when($request->filled('date_debut'), fn ($q) => $q->whereDate('date_presence', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn ($q) => $q->whereDate('date_presence', '<=', $request->date_fin));

        $presences = $query
            ->orderByDesc('date_presence')
            ->paginate(20)
            ->withQueryString();

        return view('enseignants.presences', [
            'presences' => $presences,
            'enseignants' => $this->enseignantsForUser($user, $idEcole)->orderBy('nom_prenom_enseignant')->get(),
            'classes' => $this->classesForUser($user, $idEcole)->orderBy('nom_classe')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'presenceFormData' => $this->presenceFormData($user, $idEcole),
            'presencePermissions' => $this->presencePermissions($user),
            'presenceSummary' => $this->presenceSummary(clone $query),
            'currentAcademicYearId' => $this->currentAcademicYearId(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('presence_apercu');
        $data = $this->validatedData($request);
        $data['id_ecole'] = session('idEcole') ?: Auth::user()->idEcole;
        $data['valide'] = 0;

        DB::transaction(function () use ($request, $data) {
            $presence = Presence::create($data);
            $this->syncLecons($presence, $request);
        });

        return redirect()->route('enseignants.presences')->with('success', 'Présence enregistrée avec succès.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission($this->presenceActionPermission('edit'));
        $presence = Presence::findOrFail($id);
        $this->authorizePresence($presence);

        if ($presence->valide) {
            return redirect()->route('enseignants.presences')->with('error', 'Une présence validée ne peut plus être modifiée.');
        }

        DB::transaction(function () use ($request, $presence) {
            $presence->update($this->validatedData($request));
            $presence->lecons()->delete();
            $this->syncLecons($presence, $request);
        });

        return redirect()->route('enseignants.presences')->with('success', 'Présence modifiée avec succès.');
    }

    public function validatePresence(int $id)
    {
        $this->authorizePermission($this->presenceActionPermission('validate'));
        $presence = Presence::findOrFail($id);
        $this->authorizePresence($presence, true);

        $presence->update(['valide' => 1]);

        return redirect()->route('enseignants.presences')->with('success', 'Présence validée avec succès.');
    }

    public function destroy(int $id)
    {
        $this->authorizePermission($this->presenceActionPermission('delete'));
        $presence = Presence::findOrFail($id);
        $this->authorizePresence($presence);

        if ($presence->valide) {
            return redirect()->route('enseignants.presences')->with('error', 'Impossible de supprimer une présence validée.');
        }

        DB::transaction(function () use ($presence) {
            $lockedPresence = Presence::query()
                ->whereKey($presence->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPresence->valide) {
                throw ValidationException::withMessages([
                    'presence' => 'Impossible de supprimer une présence déjà validée.',
                ]);
            }

            $lockedPresence->lecons()->delete();
            $lockedPresence->delete();
        });

        return redirect()->route('enseignants.presences')->with('success', 'Présence supprimée avec succès.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'date_presence' => 'required|date',
            'nombre_heure' => 'required|numeric|min:0.1667|max:24',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'id_anneeScolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'lecons' => 'required|array|min:1',
            'lecons.*.titre' => 'required|string|max:255',
            'lecons.*.nombre_heure' => 'required|numeric|min:0.1667|max:24',
            'lecons.*.progression' => 'nullable|numeric|min:0|max:100',
        ]);

        $user = Auth::user();
        if ($user->droit === 'enseignant') {
            $data['id_enseignant'] = $user->id_enseignant;
        }

        $assigned = LigneClasse::query()
            ->where('id_enseignants', $data['id_enseignant'])
            ->where('id_classe', $data['id_classe'])
            ->exists();

        if (!$assigned) {
            throw ValidationException::withMessages([
                'id_classe' => 'Cette classe n’est pas affectée à cet enseignant.',
            ]);
        }

        return $data;
    }

    private function syncLecons(Presence $presence, Request $request): void
    {
        $totalHours = 0;

        foreach ($request->input('lecons', []) as $lecon) {
            $totalHours += (float) $lecon['nombre_heure'];
            $presence->lecons()->create([
                'titre' => $lecon['titre'],
                'nombre_heure' => $lecon['nombre_heure'],
                'progression' => $lecon['progression'] ?? 0,
            ]);
        }

        if (abs((float) $presence->nombre_heure - $totalHours) > 0.01) {
            $presence->update(['nombre_heure' => $totalHours]);
        }
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

    private function authorizePresence(Presence $presence, bool $validation = false): void
    {
        $user = Auth::user();

        if ($user->droit === 'SupAdmin') {
            return;
        }

        if ($validation && $user->droit === 'enseignant') {
            abort(403);
        }

        if ($user->droit === 'enseignant' && $presence->id_enseignant === $user->id_enseignant) {
            return;
        }

        if ($presence->id_ecole === (session('idEcole') ?: $user->idEcole)) {
            return;
        }

        abort(403);
    }

    private function presenceFormData($user, ?int $idEcole): array
    {
        $lines = LigneClasse::with(['enseignant', 'classe'])
            ->when($user->droit === 'enseignant', fn ($query) => $query->where('id_enseignants', $user->id_enseignant))
            ->when($user->droit !== 'SupAdmin' && $user->droit !== 'enseignant', fn ($query) => $query->whereHas('classe', fn ($classe) => $classe->where('idEcole', $idEcole)))
            ->get()
            ->filter(fn ($line) => $line->enseignant && $line->classe);

        $classesByTeacher = [];
        foreach ($lines as $line) {
            $teacherId = (int) $line->id_enseignants;
            $classId = (int) $line->id_classe;
            $classesByTeacher[$teacherId][$classId] = [
                'id' => $classId,
                'label' => $line->classe->nom_classe,
            ];
        }

        return [
            'classesByTeacher' => collect($classesByTeacher)->map(fn ($items) => array_values($items))->toArray(),
        ];
    }

    private function presenceSummary($query): array
    {
        $rows = $query->with('lecons')->get();

        return [
            'total' => $rows->count(),
            'pending' => $rows->where('valide', false)->count(),
            'validated' => $rows->where('valide', true)->count(),
            'hours' => (float) $rows->sum('nombre_heure'),
            'lessons' => $rows->sum(fn ($presence) => $presence->lecons->count()),
        ];
    }

    private function currentAcademicYearId(): ?int
    {
        $today = now()->toDateString();

        return AnneeScolaire::query()
            ->whereDate('date_debut', '<=', $today)
            ->whereDate('date_fin', '>=', $today)
            ->value('id_anneeScolaire');
    }

    private function presencePermissions($user): array
    {
        return [
            'create' => $this->hasPermission($user, 'presence_apercu'),
            'validate' => $this->hasPermission($user, $this->presenceActionPermission('validate')),
            'edit' => $this->hasPermission($user, $this->presenceActionPermission('edit')),
            'delete' => $this->hasPermission($user, $this->presenceActionPermission('delete')),
        ];
    }

    private function presenceActionPermission(string $action): string
    {
        $permission = [
            'validate' => 'presence_validation_admin',
            'edit' => 'presence_modification',
            'delete' => 'presence_supprimer',
        ][$action];

        return Permission::where('name', $permission)->exists() ? $permission : 'presence_apercu';
    }

    private function hasPermission($user, string $permission): bool
    {
        if ($user->droit === 'enseignant' && $permission === 'presence_apercu') {
            return true;
        }

        return $user->droit === 'SupAdmin' || $user->userHasPermission($permission);
    }

    private function authorizePermission(string $permission): void
    {
        if (!$this->hasPermission(Auth::user(), $permission)) {
            abort(403);
        }
    }
}
