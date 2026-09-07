<?php

namespace App\Http\Middleware\Api;

use App\Support\Api\Authorizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A lot of pre-existing web controller code reads the "active school" via
 * session('idEcole') (set once at web login) instead of a request param —
 * and BelongsToSchool's global scope, plus several controllers, fall back
 * to it directly. API requests carry no session cookie, but this seeds the
 * same key in-memory for this request only (no StartSession/cookie runs on
 * the api group, so nothing is persisted or shared between requests) — so
 * that reused web logic keeps working unmodified when called from the API.
 */
class SeedSchoolSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !session()->has('idEcole')) {
            session(['idEcole' => Authorizer::resolveEcoleId($user, $request)]);
        }

        return $next($request);
    }
}
