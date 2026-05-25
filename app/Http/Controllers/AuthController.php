<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'email' => ['required', 'email'],
            'pwd' => ['required'],
            'theme_preference' => ['nullable', 'string', 'in:bleu-sombre,light,dark,vert,violet,rouge,orange'],
        ]);

        // Eager load ecole relationship without global scope restrictions during lookup
        $users = User::with(['ecole' => function ($q) {
            $q->withoutGlobalScopes();
        }])->where('email', $credentials['email'])->get();

        if ($users->isEmpty()) {
            return back()->withErrors([
                'email' => 'Email ou mot de passe incorrect.',
            ])->onlyInput('email');
        }

        // Check password on the first user found (all accounts with same email should have same password)
        if (!Hash::check($credentials['pwd'], $users[0]->pwd)) {
            return back()->withErrors([
                'email' => 'Email ou mot de passe incorrect.',
            ])->onlyInput('email');
        }

        // Check if user has multiple schools
        if ($users->count() > 1) {
            return view('auth.login', [
                'ecoles_modal' => $users,
                'selected_theme' => $request->input('theme_preference'),
            ]);
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
            $user->save();
        }

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

        return redirect()->intended(route('dashboard'));
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
