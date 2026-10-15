<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\TerritoireController as WebTerritoireController;
use App\Support\Territoire\AnnoncesTerritoire;
use App\Support\Territoire\StatistiquesTerritoire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Espace DAE / DCAP de l'application mobile : chiffres du territoire,
 * effectifs (lecture seule) et annonces de l'académie ou du CAP.
 */
class TerritoireController extends WebTerritoireController
{
    public function resume(Request $request)
    {
        $user = $this->superviseur($request);

        if ($user->droit === 'DAE') {
            return response()->json([
                'role' => 'DAE',
                'territoire' => DB::table('academie')->where('id_academie', $user->id_academie)->value('nom_academie'),
                'stats' => StatistiquesTerritoire::academie((int) $user->id_academie),
                'cibles' => AnnoncesTerritoire::CIBLES,
            ]);
        }

        return response()->json([
            'role' => 'DCAP',
            'territoire' => DB::table('cap')->where('id_cap', $user->id_cap)->value('nom_cap'),
            'stats' => StatistiquesTerritoire::cap((int) $user->id_cap),
            'cibles' => collect(AnnoncesTerritoire::CIBLES)->except('dcap')->all(),
        ]);
    }

    public function effectifsApi(Request $request)
    {
        $user = $this->superviseur($request);
        $capChoisi = $user->droit === 'DAE' ? ($request->integer('cap') ?: null) : (int) $user->id_cap;
        $recherche = trim((string) $request->query('recherche', ''));

        $ecoles = DB::table('ecole as e')
            ->leftJoin('cap as c', 'c.id_cap', '=', 'e.id_cap')
            ->when($user->droit === 'DAE', fn ($q) => $q->where('e.id_academie', $user->id_academie))
            ->when($capChoisi, fn ($q) => $q->where('e.id_cap', $capChoisi))
            ->when($recherche !== '', fn ($q) => $q->where('e.nomEcole', 'like', '%'.$recherche.'%'))
            ->orderBy('c.nom_cap')->orderBy('e.nomEcole')
            ->get(['e.idEcole', 'e.nomEcole', 'e.statut', 'c.nom_cap']);

        return response()->json([
            'caps' => $user->droit === 'DAE' ? DB::table('cap')->where('id_academie', $user->id_academie)->orderBy('nom_cap')->get(['id_cap', 'nom_cap']) : [],
            'effectifs' => StatistiquesTerritoire::effectifs($ecoles)->values(),
        ]);
    }

    public function annoncesApi(Request $request)
    {
        $user = $this->superviseur($request);

        return response()->json([
            'annonces' => AnnoncesTerritoire::publiees($user),
            'annonces_academie' => $user->droit === 'DCAP' ? AnnoncesTerritoire::visibles($user, null, 10)->values() : [],
            'cibles' => $user->droit === 'DAE' ? AnnoncesTerritoire::CIBLES : collect(AnnoncesTerritoire::CIBLES)->except('dcap')->all(),
        ]);
    }

    public function storeAnnonceApi(Request $request)
    {
        $this->superviseur($request);
        parent::storeAnnonce($request);

        return response()->json(['message' => 'Annonce publiée.'], 201);
    }

    public function destroyAnnonceApi(Request $request, int $id)
    {
        $this->superviseur($request);
        parent::destroyAnnonce($id);

        return response()->json(['message' => 'Annonce supprimée.']);
    }

    private function superviseur(Request $request)
    {
        $user = $request->user();
        abort_unless(($user->droit === 'DAE' && $user->id_academie) || ($user->droit === 'DCAP' && $user->id_cap), 403);

        return $user;
    }
}
