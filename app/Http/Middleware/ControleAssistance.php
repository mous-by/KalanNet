<?php

namespace App\Http\Middleware;

use App\Services\Assistance\AccesAssistance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pendant un accès d'assistance : fin automatique après la durée prévue,
 * déconnexion = retour au compte du SupAdmin, et quelques actions interdites
 * (mot de passe du client, permissions, suppression de comptes).
 */
class ControleAssistance
{
    private const ROUTES_INTERDITES = [
        'password.force', 'password.force.update', 'profile.password.update',
        'configuration.utilisateurs.mot-de-passe', 'configuration.utilisateurs.destroy',
        'configuration.utilisateurs.permissions', 'configuration.utilisateurs.permissions.update', 'configuration.utilisateurs.permissions.assigner',
        'assistance.demander', 'assistance.attente', 'assistance.statut', 'assistance.ouvrir', 'assistance.repondre',
    ];

    public function __construct(private AccesAssistance $assistance)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!AccesAssistance::enCours()) {
            return $next($request);
        }

        if (AccesAssistance::expire()) {
            $this->assistance->fermer($request, 'expire');

            return redirect()->route('configuration.utilisateurs')->with('error', __('assistance.expire'));
        }

        if ($request->routeIs('logout')) {
            $this->assistance->fermer($request);

            return redirect()->route('configuration.utilisateurs')->with('success', __('assistance.ferme'));
        }

        if ($request->routeIs(...self::ROUTES_INTERDITES)) {
            return $request->expectsJson()
                ? response()->json(['message' => __('assistance.interdit')], 403)
                : redirect()->route('dashboard')->with('error', __('assistance.interdit'));
        }

        return $next($request);
    }
}
