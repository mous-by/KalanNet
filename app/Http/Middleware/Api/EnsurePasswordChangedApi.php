<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Version API (mobile) : tant que le mot de passe donné par un Admin n'est pas
 * remplacé, seuls « qui suis-je », le changement de mot de passe et la
 * déconnexion répondent.
 */
class EnsurePasswordChangedApi
{
    private const CHEMINS_PERMIS = ['api/v1/auth/me', 'api/v1/auth/password', 'api/v1/auth/logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->doit_changer_mdp && !$request->is(...self::CHEMINS_PERMIS)) {
            return response()->json(['message' => __('securite.mdp_a_changer'), 'code' => 'password_change_required'], 403);
        }

        return $next($request);
    }
}
