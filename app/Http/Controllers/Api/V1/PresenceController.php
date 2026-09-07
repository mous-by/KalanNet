<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\PresenceController as WebPresenceController;
use App\Models\AnneeScolaire;
use App\Models\Presence;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresenceController extends WebPresenceController
{
    public function index(Request $request)
    {
        $user = $request->user();
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

        $presences = $query->orderByDesc('date_presence')->paginate(20)->withQueryString();

        return response()->json([
            'presences' => $presences,
            'enseignants' => $this->enseignantsForUser($user, $idEcole)->orderBy('nom_prenom_enseignant')->get(),
            'classes' => $this->classesForUser($user, $idEcole)->orderBy('nom_classe')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'permissions' => $this->presencePermissions($user),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('presence_apercu');
        $data = $this->validatedData($request);
        $data['id_ecole'] = session('idEcole') ?: $request->user()->idEcole;
        $data['valide'] = 0;

        $presence = DB::transaction(function () use ($request, $data) {
            $presence = Presence::create($data);
            $this->syncLecons($presence, $request);

            return $presence;
        });

        return response()->json($presence->fresh(['enseignant', 'classe', 'lecons']), 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission($this->presenceActionPermission('edit'));
        $presence = Presence::findOrFail($id);
        $this->authorizePresence($presence);

        if ($presence->valide) {
            return response()->json(['message' => 'Une présence validée ne peut plus être modifiée.'], 422);
        }

        DB::transaction(function () use ($request, $presence) {
            $presence->update($this->validatedData($request));
            $presence->lecons()->delete();
            $this->syncLecons($presence, $request);
        });

        return response()->json($presence->fresh(['enseignant', 'classe', 'lecons']));
    }

    public function validatePresence(int $id)
    {
        $this->authorizePermission($this->presenceActionPermission('validate'));
        $presence = Presence::findOrFail($id);
        $this->authorizePresence($presence, true);

        $presence->update(['valide' => 1]);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $this->authorizePermission($this->presenceActionPermission('delete'));
        $presence = Presence::findOrFail($id);
        $this->authorizePresence($presence);

        if ($presence->valide) {
            return response()->json(['message' => 'Impossible de supprimer une présence validée.'], 422);
        }

        DB::transaction(function () use ($presence) {
            $lockedPresence = Presence::query()->whereKey($presence->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedPresence->valide) {
                throw ValidationException::withMessages(['presence' => 'Impossible de supprimer une présence déjà validée.']);
            }

            $lockedPresence->lecons()->delete();
            $lockedPresence->delete();
        });

        return response()->json(['success' => true]);
    }
}
