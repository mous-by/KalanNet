<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', []);
        $fallback = config('app.fallback_locale', 'fr');
        $locale = $request->session()->get('locale', $request->cookie('locale', $fallback));

        $user = Auth::user();
        if ($user && Schema::hasColumn($user->getTable(), 'locale_preference') && $user->locale_preference) {
            $locale = $user->locale_preference;
        }

        if (!array_key_exists($locale, $supportedLocales)) {
            $locale = $fallback;
        }

        $request->session()->put('locale', $locale);
        Cookie::queue('locale', $locale, 60 * 24 * 365);
        App::setLocale($locale);

        $direction = $supportedLocales[$locale]['dir'] ?? 'ltr';
        View::share('currentLocale', $locale);
        View::share('currentDirection', $direction);
        View::share('supportedLocales', $supportedLocales);

        return $next($request);
    }
}
