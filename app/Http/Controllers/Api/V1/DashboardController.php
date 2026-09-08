<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\DashboardController as WebDashboardController;
use App\Models\Abonnement;
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

        return response()->json($this->adminDashboardData($user, $schoolId));
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
