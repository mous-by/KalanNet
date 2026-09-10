<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\EmargementController as WebEmargementController;
use App\Models\AnneeScolaire;
use App\Models\Emargement;
use App\Models\ProgrammeLecon;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmargementController extends WebEmargementController
{
    public function index(Request $request)
    {
        $user = $request->user();
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

        $summaryRows = (clone $query)->with('enseignant')->get();
        $emargementSummary = [
            'total' => $summaryRows->count(),
            'pending' => $summaryRows->where('valide', false)->count(),
            'validated_hours' => $summaryRows->where('valide', true)->sum('nombre_heure'),
            'lessons' => $summaryRows->pluck('id_lecon')->filter()->unique()->count(),
        ];

        $emargements = $query->orderByDesc('date_emargement')->paginate(20)->withQueryString();

        return response()->json([
            'emargements' => $emargements,
            'enseignants' => $this->enseignantsForUser($user, $idEcole)->orderBy('nom_prenom_enseignant')->get(),
            'classes' => $this->classesForUser($user, $idEcole)->orderBy('nom_classe')->get(),
            'matieres' => $this->matieresForUser($user, $idEcole)->orderBy('nom_matiere')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'lecons' => ProgrammeLecon::orderBy('numero')->orderBy('titre')->get(),
            'formData' => $this->emargementFormData($user, $idEcole),
            'permissions' => $this->emargementPermissions($user),
            'summary' => $emargementSummary,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('emargement_faire');

        $emargement = DB::transaction(function () use ($request) {
            $data = $this->validatedData($request);
            $data['id_ecole'] = session('idEcole') ?: $request->user()->idEcole;
            $data['valide'] = 0;

            $emargement = Emargement::create($data);
            $this->notifyEmargementValidators($emargement);

            return $emargement;
        });

        return response()->json($emargement->fresh(['enseignant', 'classe', 'matiere']), 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission($this->emargementActionPermission('edit'));
        $emargement = Emargement::findOrFail($id);
        $this->authorizeEmargement($emargement);

        if ($emargement->valide) {
            return response()->json(['message' => 'Un émargement validé ne peut plus être modifié.'], 422);
        }

        DB::transaction(function () use ($request, $emargement) {
            $emargement->update($this->validatedData($request, $emargement->id_emargement));
        });

        return response()->json($emargement->fresh(['enseignant', 'classe', 'matiere']));
    }

    public function validateEmargement(int $id)
    {
        $this->authorizePermission('emargement_validation_admin');
        $emargement = Emargement::findOrFail($id);
        $this->authorizeEmargement($emargement, true);

        $emargement->update(['valide' => 1]);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $this->authorizePermission($this->emargementActionPermission('delete'));
        $emargement = Emargement::findOrFail($id);
        $this->authorizeEmargement($emargement);

        if ($emargement->valide) {
            return response()->json(['message' => 'Impossible de supprimer un émargement validé.'], 422);
        }

        $deleted = Emargement::query()->whereKey($emargement->getKey())->where('valide', 0)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Impossible de supprimer un émargement déjà validé.'], 422);
        }

        return response()->json(['success' => true]);
    }
}
