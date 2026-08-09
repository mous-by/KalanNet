<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\User;
use App\Rules\MaliPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login', [
            'selected_theme' => request()->cookie('theme_preference'),
            'selected_locale' => app()->getLocale(),
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'login:' . $this->normalizedLoginIdentifier((string) $request->input('identifier', '')) . '|' . $request->ip();
    }

    private function normalizedLoginIdentifier(string $identifier): string
    {
        $identifier = strtolower(trim($identifier));

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $identifier;
        }

        return MaliPhone::normalize($identifier);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'pwd' => ['required'],
            'theme_preference' => ['nullable', 'string', 'in:bleu-sombre,light,dark,vert,violet,rouge,orange'],
        ], [
            'identifier.required' => 'Veuillez saisir votre email ou votre numéro de téléphone.',
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'identifier' => __('messages.auth.too_many_attempts', ['seconds' => $seconds]),
            ])->onlyInput('identifier');
        }

        $identifier = trim($credentials['identifier']);
        $phoneIdentifier = MaliPhone::normalize($identifier);

        // Eager load ecole relationship without global scope restrictions during lookup
        $users = User::with(['ecole' => function ($q) {
            $q->withoutGlobalScopes();
        }])
            ->where(function ($query) use ($identifier, $phoneIdentifier) {
                $query->where('email', $identifier)
                    ->orWhere('telephone', $phoneIdentifier);
            })
            ->get();

        if ($users->isEmpty()) {
            RateLimiter::hit($throttleKey, 180);
            return back()->withErrors([
                'identifier' => __('messages.auth.invalid_credentials'),
            ])->onlyInput('identifier');
        }

        $users = $users->filter(fn (User $user) => Hash::check($credentials['pwd'], $user->pwd))->values();

        if ($users->isEmpty()) {
            RateLimiter::hit($throttleKey, 180);
            return back()->withErrors([
                'identifier' => __('messages.auth.invalid_credentials'),
            ])->onlyInput('identifier');
        }

        // Check if user has multiple schools
        if ($users->count() > 1) {
            return view('auth.login', [
                'ecoles_modal' => $users,
                'selected_theme' => $request->input('theme_preference'),
                'selected_locale' => app()->getLocale(),
            ]);
        }

        if ((int) $users[0]->statut === 0) {
            return back()->with('error', __('messages.auth.inactive_account'));
        }

        // Single school, direct login
        RateLimiter::clear($throttleKey);
        return $this->performLogin($users[0], $request);
    }

    public function selectSchool(Request $request)
    {
        $request->validate([
            'idUtilisateur' => 'required|exists:utilisateurs,idUtilisateur',
            'idEcole' => 'required|exists:ecole,idEcole',
            'theme_preference' => ['nullable', 'string', 'in:bleu-sombre,light,dark,vert,violet,rouge,orange'],
        ]);

        $user = User::withoutGlobalScopes()
                    ->where('idUtilisateur', $request->idUtilisateur)
                    ->where('idEcole', $request->idEcole)
                    ->first();

        if (!$user) {
            return redirect()->route('login')->with('error', __('messages.auth.school_selection_error'));
        }

        if ($user->statut == 0) {
            return redirect()->route('login')->with('error', __('messages.auth.inactive_school_account'));
        }

        RateLimiter::clear($this->throttleKey($request));
        return $this->performLogin($user, $request);
    }

    protected function performLogin($user, Request $request)
    {
        if ($request->filled('theme_preference')) {
            $user->theme_preference = $request->input('theme_preference');
        }

        $locale = $request->session()->get('locale');
        if ($locale && array_key_exists($locale, config('app.supported_locales', [])) && Schema::hasColumn($user->getTable(), 'locale_preference')) {
            $user->locale_preference = $locale;
        }

        $user->last_login_at = now();
        $user->last_activity = now();
        $user->save();

        Auth::login($user);

        $request->session()->put('show_pwa_install_modal', true);

        $user->loadMissing(['enseignant', 'parent']);
        $idEcole = $user->idEcole ?: $user->enseignant?->id_ecole ?: $user->parent?->idEcole;

        // Retrieve school details bypassing global scopes
        $ecole = $idEcole ? \App\Models\Ecole::withoutGlobalScopes()->find($idEcole) : null;

        // Put necessary info in session like legacy did
        $request->session()->put([
            'idUtilisateur' => $user->idUtilisateur,
            'nomPrenom' => $user->nomPrenom,
            'email' => $user->email,
            'droit' => $user->droit,
            'idEcole' => $idEcole,
            'nomEcole' => $ecole->nomEcole ?? null,
            'typeEcole' => $ecole->typeEcole ?? null,
            'logoEcole' => $ecole->logoEcole ?? null,
        ]);

        if (in_array($user->droit, ['enseignant', 'parent'], true)) {
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        if ($this->subscriptionIsBlocked($user, (int) $idEcole)) {
            $request->session()->forget('url.intended');

            return redirect()
                ->route('abonnements.index')
                ->with('open_subscription_renewal_modal', true)
                ->with('error', "Votre abonnement a expiré. Veuillez renouveler l'abonnement pour continuer.");
        }

        return redirect()->intended(route('dashboard'));
    }

    private function subscriptionIsBlocked(User $user, int $schoolId): bool
    {
        if ($schoolId <= 0 || in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP'], true) || !Schema::hasTable('abonnements')) {
            return false;
        }

        // Licence à vie (offre ACHAT) : abonnement actif SANS date de fin => jamais bloqué.
        $hasLifetime = Abonnement::query()
            ->where('ecole_id', $schoolId)
            ->where('statut', 'actif')
            ->whereNull('fin_at')
            ->exists();

        if ($hasLifetime) {
            return false;
        }

        $subscription = Abonnement::query()
            ->where('ecole_id', $schoolId)
            ->where('statut', 'actif')
            ->whereNotNull('fin_at')
            ->orderByDesc('fin_at')
            ->first();

        return !$subscription || $subscription->fin_at->copy()->endOfDay()->isPast();
    }

    public function logout(Request $request)
    {
        $theme = Auth::user()?->theme_preference ?: 'vert';
        $locale = Auth::user()?->locale_preference
            ?: $request->session()->get('locale', $request->cookie('locale', config('app.fallback_locale', 'fr')));

        if (!array_key_exists($locale, config('app.supported_locales', []))) {
            $locale = config('app.fallback_locale', 'fr');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withCookie(Cookie::make('locale', $locale, 60 * 24 * 365))
            ->withCookie(Cookie::make('theme_preference', $theme, 60 * 24 * 365));
    }
}
