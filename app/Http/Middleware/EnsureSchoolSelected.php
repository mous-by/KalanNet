<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            // Bypass school selection for SupAdmin, DAE, and DCAP users
            if (in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP'], true)) {
                return $next($request);
            }

            if (!session()->has('idEcole')) {
                // Log out if school is not selected for regular school users
                $locale = $user->locale_preference
                    ?: $request->session()->get('locale', $request->cookie('locale', config('app.fallback_locale', 'fr')));
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()
                    ->route('login')
                    ->with('error', 'Veuillez vous reconnecter et sélectionner une école.')
                    ->withCookie(Cookie::make('locale', $locale, 60 * 24 * 365));
            }
        }

        return $next($request);
    }
}
