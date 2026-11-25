<?php

namespace App\Http\Middleware;

use App\Support\SchemaExiste;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', []);
        $fallback = config('app.fallback_locale', 'fr');
        $user = Auth::user();

        if ($user) {
            $locale = $request->session()->get('locale', $request->cookie('locale', $fallback));
            if (SchemaExiste::colonne($user->getTable(), 'locale_preference') && $user->locale_preference) {
                $locale = $user->locale_preference;
            }
        } else {
            // Visiteur non connecté (page de connexion) : français par défaut, quelle que soit la langue
            // du dernier utilisateur de l'appareil ; une autre langue seulement si elle est choisie ici.
            $locale = $request->session()->get('locale_selected') ? $request->session()->get('locale', $fallback) : $fallback;
        }

        if (!array_key_exists($locale, $supportedLocales)) {
            $locale = $fallback;
        }

        $request->session()->put('locale', $locale);
        if ($user) {
            Cookie::queue('locale', $locale, 60 * 24 * 365);
        }
        App::setLocale($locale);

        $direction = $supportedLocales[$locale]['dir'] ?? 'ltr';
        View::share('currentLocale', $locale);
        View::share('currentDirection', $direction);
        View::share('supportedLocales', $supportedLocales);

        return $next($request);
    }
}
