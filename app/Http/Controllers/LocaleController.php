<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale)
    {
        abort_unless(array_key_exists($locale, config('app.supported_locales', [])), 404);

        $request->session()->put('locale', $locale);
        $request->session()->put('locale_selected', true);
        app()->setLocale($locale);

        $user = Auth::user();
        if ($user && Schema::hasColumn($user->getTable(), 'locale_preference')) {
            $user->locale_preference = $locale;
            $user->save();
        }

        return back()->withCookie(Cookie::make('locale', $locale, 60 * 24 * 365));
    }
}
