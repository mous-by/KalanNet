<?php

namespace App\Http\Middleware\Api;

use App\Models\MaintenanceMode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceModeApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Schema::hasTable('maintenance_mode')) {
            return $next($request);
        }

        $etat = MaintenanceMode::current();
        if (!$etat->actif) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->droit === 'SupAdmin') {
            return $next($request);
        }

        return response()->json([
            'message' => $etat->message ?: MaintenanceMode::DEFAULT_MESSAGE,
            'maintenance' => true,
        ], 503);
    }
}
