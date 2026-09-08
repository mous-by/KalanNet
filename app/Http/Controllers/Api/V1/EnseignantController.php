<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\EnseignantController as WebEnseignantController;
use App\Models\Enseignant;
use App\Models\LigneClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EnseignantController extends WebEnseignantController
{
    public function index(Request $request)
    {
        $user = request()->user();
        $query = Enseignant::query();

        if (in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP', 'Admin', 'Gestionnaire'], true)) {
            // Handled by the BelongsToSchool global scope.
        } elseif ($user->droit === 'enseignant') {
            $query->where('id_enseignant', $user->id_enseignant);
        } else {
            abort(403, 'Accès non autorisé');
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

        return response()->json($enseignants);
    }

    public function store(Request $request)
    {
        $this->authorizeTeacherManagement('create');
        $data = $this->validateEnseignant($request);
        $data['avatar_enseignant'] = $this->storeAvatar($request);
        $data['pwd'] = Hash::make('123456');
        $data['id_ecole'] = session('idEcole') ?: request()->user()->idEcole;
        $data['matricule'] = ($data['matricule'] ?? null) ?: $this->generateMatricule($data);

        $enseignant = Enseignant::create($this->mapFields($data));

        return response()->json($enseignant, 201);
    }

    public function show($id)
    {
        $enseignant = Enseignant::with(['ecole.academieRef', 'ecole.capRef'])->findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        $lignesClasses = LigneClasse::with(['classe', 'matiere'])
            ->where('id_enseignants', $enseignant->id_enseignant)
            ->orderBy('id_classe')
            ->get();

        $emargementStats = [
            'total' => $enseignant->emargements()->count(),
            'valides' => $enseignant->emargements()->where('valide', 1)->count(),
            'heures' => $enseignant->emargements()->sum('nombre_heure'),
        ];

        $presenceStats = [
            'total' => $enseignant->presences()->count(),
            'valides' => $enseignant->presences()->where('valide', 1)->count(),
            'heures' => $enseignant->presences()->sum('nombre_heure'),
        ];

        $recentEmargements = $enseignant->emargements()
            ->with(['classe', 'matiere', 'trimestre'])
            ->orderByDesc('date_emargement')
            ->limit(8)
            ->get();

        $recentPresences = $enseignant->presences()
            ->with(['classe', 'trimestre'])
            ->orderByDesc('date_presence')
            ->limit(8)
            ->get();

        return response()->json([
            'enseignant' => $enseignant,
            'lignes_classes' => $lignesClasses,
            'emargement_stats' => $emargementStats,
            'presence_stats' => $presenceStats,
            'recent_emargements' => $recentEmargements,
            'recent_presences' => $recentPresences,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeTeacherManagement('update');
        $enseignant = Enseignant::findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        $data = $this->validateEnseignant($request, $enseignant->id_enseignant);
        $data['matricule'] = ($data['matricule'] ?? null) ?: $this->generateMatricule($data);

        $mapped = $this->mapFields($data);
        $avatar = $this->storeAvatar($request, $enseignant->avatar_enseignant);
        if ($avatar) {
            $mapped['avatar_enseignant'] = $avatar;
        }

        $enseignant->update($mapped);

        return response()->json($enseignant->fresh());
    }

    public function archive($id)
    {
        $this->authorizeTeacherManagement('archive');
        $enseignant = Enseignant::findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        $enseignant->update(['is_deleted' => 1, 'deleted_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function reactivate($id)
    {
        $this->authorizeTeacherManagement('archive');
        $enseignant = Enseignant::findOrFail($id);
        $this->authorizeEnseignant($enseignant);

        $enseignant->update(['is_deleted' => 0, 'deleted_at' => null]);

        return response()->json(['success' => true]);
    }
}
