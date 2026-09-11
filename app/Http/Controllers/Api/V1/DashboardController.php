<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\DashboardController as WebDashboardController;
use App\Models\Abonnement;
use App\Models\AbonnementPaiement;
use App\Models\Ecole;
use App\Models\Revendeur;
use App\Support\Api\Authorizer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends WebDashboardController
{
    public function index(?Request $request = null)
    {
        $request ??= request();
        $user = $request->user();
        $schoolId = Authorizer::resolveEcoleId($user, $request);

        if ($user->droit === 'enseignant') {
            return response()->json($this->teacherDashboardData($user, $schoolId));
        }

        if ($user->droit === 'parent') {
            return response()->json($this->parentDashboardData($user, $schoolId));
        }

        if ($user->droit === 'SupAdmin') {
            return response()->json($this->supAdminDashboardData($user));
        }

        if ($user->droit === 'revendeur') {
            return response()->json($this->revendeurDashboardData($user));
        }

        return response()->json($this->adminDashboardData($user, $schoolId));
    }

    /**
     * Meme perimetre que RevendeurController::dashboard() (web) : uniquement
     * les ecoles apportees par ce revendeur — jamais les donnees des autres
     * ecoles de la plateforme (contrairement a adminDashboardData(), qui
     * suppose toujours un idEcole de session et ne convient pas ici).
     */
    protected function revendeurDashboardData($user): array
    {
        if (!$user->id_revendeur) {
            return ['ecoles' => [], 'total_ecoles' => 0, 'abonnements_actifs' => 0, 'paiements_en_attente' => 0];
        }

        $revendeur = Revendeur::find($user->id_revendeur);
        $ecoles = Ecole::withoutGlobalScopes()->where('id_revendeur', $user->id_revendeur)->orderBy('nomEcole')->get();
        $ecoleIds = $ecoles->pluck('idEcole');

        $abonnementsActifs = Abonnement::whereIn('ecole_id', $ecoleIds)->where('statut', 'actif')->count();
        $pendingValidations = AbonnementPaiement::with(['offre', 'ecole'])
            ->whereIn('ecole_id', $ecoleIds)
            ->where('statut', 'en_attente')
            ->orderByDesc('id')
            ->get();

        return [
            'revendeur' => $revendeur,
            'ecoles' => $ecoles,
            'total_ecoles' => $ecoles->count(),
            'abonnements_actifs' => $abonnementsActifs,
            'paiements_en_attente' => $pendingValidations,
        ];
    }

    public function updateSubscriptionDates(Request $request, Abonnement $abonnement)
    {
        if ($request->user()?->droit !== 'SupAdmin') {
            abort(403);
        }

        $data = $request->validate([
            'debut_at' => 'required|date',
            'fin_at' => 'required|date|after:debut_at',
        ]);

        $abonnement->update([
            'statut' => 'actif',
            'debut_at' => Carbon::parse($data['debut_at'])->startOfDay(),
            'fin_at' => Carbon::parse($data['fin_at'])->endOfDay(),
        ]);

        $this->notifySchoolUsersSubscriptionUpdated($abonnement);

        return response()->json($abonnement->fresh(['ecole', 'offre']));
    }
}
