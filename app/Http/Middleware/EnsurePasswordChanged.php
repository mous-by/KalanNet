<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mot de passe donné par quelqu'un d'autre (création ou réinitialisation par
 * un Admin) : tant que l'utilisateur ne l'a pas remplacé, il ne peut rien
 * faire d'autre que le changer ou se déconnecter.
 */
class EnsurePasswordChanged
{
    private const ROUTES_PERMISES = ['password.force', 'password.force.update', 'logout', 'login', 'login.post', 'login.select-school'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->doit_changer_mdp && !$request->routeIs(...self::ROUTES_PERMISES)) {
            return $request->expectsJson()
                ? response()->json(['message' => __('securite.mdp_a_changer'), 'code' => 'password_change_required'], 403)
                : redirect()->route('password.force');
        }

        return $next($request);
    }
}
