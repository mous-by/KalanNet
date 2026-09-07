<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\DashboardController as WebDashboardController;
use App\Support\Api\Authorizer;
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
}
