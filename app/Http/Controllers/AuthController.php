<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login', [
            'selected_theme' => request()->query('theme'),
        ]);
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

        $identifier = trim($credentials['identifier']);

        // Eager load ecole relationship without global scope restrictions during lookup
        $users = User::with(['ecole' => function ($q) {
            $q->withoutGlobalScopes();
        }])
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('telephone', $identifier);
            })
            ->get();

        if ($users->isEmpty()) {
            return back()->withErrors([
                'identifier' => 'Email, téléphone ou mot de passe incorrect.',
            ])->onlyInput('identifier');
        }

        $users = $users->filter(fn (User $user) => Hash::check($credentials['pwd'], $user->pwd))->values();

        if ($users->isEmpty()) {
            return back()->withErrors([
                'identifier' => 'Email, téléphone ou mot de passe incorrect.',
            ])->onlyInput('identifier');
        }

        // Check if user has multiple schools
        if ($users->count() > 1) {
            return view('auth.login', [
                'ecoles_modal' => $users,
                'selected_theme' => $request->input('theme_preference'),
            ]);
        }

        if ((int) $users[0]->statut === 0) {
            return back()->with('error', 'Votre compte est inactif.');
        }

        // Single school, direct login
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
            return redirect()->route('login')->with('error', 'Erreur lors de la sélection de l\'école.');
        }

        if ($user->statut == 0) {
            return redirect()->route('login')->with('error', 'Votre compte est inactif pour cette école.');
        }

        return $this->performLogin($user, $request);
    }

    protected function performLogin($user, Request $request)
    {
        if ($request->filled('theme_preference')) {
            $user->theme_preference = $request->input('theme_preference');
        }

        $user->last_login_at = now();
        $user->last_activity = now();
        $user->save();

        Auth::login($user);

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

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['theme' => $theme]);
    }
}
