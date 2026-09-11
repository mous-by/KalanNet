<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceMode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
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

        // Le SupAdmin garde toujours un accès complet — sinon personne ne
        // pourrait jamais désactiver la maintenance depuis l'application.
        if (Auth::check() && Auth::user()->droit === 'SupAdmin') {
            return $next($request);
        }

        // La connexion doit rester joignable : un SupAdmin qui n'est pas
        // encore authentifié doit pouvoir se connecter pour désactiver le mode.
        if ($request->routeIs('login', 'login.post', 'login.select-school', 'logout')) {
            return $next($request);
        }

        $message = $etat->message ?: MaintenanceMode::DEFAULT_MESSAGE;

        return response()->view('maintenance', ['message' => $message], 503);
    }
}
