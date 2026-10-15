<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * DAE et DCAP supervisent leur territoire en lecture : tableau de bord,
 * effectifs, évaluations communes, annonces de territoire. Ils n'accèdent à
 * aucun écran de gestion d'école (élèves, enseignants, notes, finances…) et
 * ne peuvent rien y créer, modifier ni supprimer — y compris par l'assistant.
 */
class RestreindreSuperviseurs
{
    public const ROLES = ['DAE', 'DCAP'];

    /** Routes web permises (noms ; « territoire.* » couvre tout leur espace). */
    private const ROUTES_WEB = [
        'dashboard', 'territoire.*', 'documentation.*',
        'profile.edit', 'profile.update', 'profile.password.update',
        'password.force', 'password.force.update',
        'logout', 'login', 'login.post', 'login.select-school',
        'annonces.read-visible', 'notifications.read',
        'language.update', 'theme.store',
    ];

    /** Chemins API permis (application mobile). */
    private const CHEMINS_API = [
        'api/v1/auth/*', 'api/v1/notifications', 'api/v1/notifications/*', 'api/v1/dashboard',
        'api/v1/territoire', 'api/v1/territoire/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? Auth::user();

        if (!$user || !in_array($user->droit, self::ROLES, true)) {
            return $next($request);
        }

        $permis = $request->is('api/*')
            ? $request->is(...self::CHEMINS_API)
            : $request->routeIs(...self::ROUTES_WEB);

        if ($permis) {
            return $next($request);
        }

        $message = "Cet écran est réservé aux écoles. En tant que {$user->droit}, vous consultez les effectifs et les évaluations communes depuis votre espace.";

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        return $request->isMethod('GET')
            ? redirect()->route('dashboard')->with('error', $message)
            : abort(403, $message);
    }
}
