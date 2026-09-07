<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum authenticates the request onto its own guard, but a lot of
 * pre-existing web business logic (Global Scopes, controller helpers)
 * calls the bare Auth::user()/Auth::check() helpers, which resolve the
 * default "web" guard and would otherwise see no one. This bridges the
 * Sanctum-resolved user onto the default guard for this request only —
 * nothing is persisted (no session middleware runs on the api group).
 */
class BridgeSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            Auth::setUser($user);
        }

        return $next($request);
    }
}
