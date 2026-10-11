<?php

namespace App\Http\Controllers;

use App\Rules\PaysPhone;
use App\Support\Telephone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        abort_unless($user->peutVoirProfil(), 403);

        return view('profile.edit', ['user' => $user, 'modifierProfil' => $user->peutModifierProfil()]);
    }

    public function updateInfo(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->peutModifierProfil(), 403);

        if ($request->filled('telephone')) {
            $request->merge(['telephone' => Telephone::normalize($request->input('telephone'), $user->idEcole)]);
        }

        $data = $request->validate([
            'nomPrenom' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('utilisateurs', 'email')->ignore($user->idUtilisateur, 'idUtilisateur')],
            'telephone' => ['nullable', 'string', 'max:20', new PaysPhone($user->idEcole)],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $this->storePhoto($request);
        } else {
            unset($data['image']);
        }

        $user->update($data);

        return back()->with('success', 'Vos informations ont été mises à jour.');
    }

    protected function storePhoto(Request $request): string
    {
        $directory = public_path('images_utilisateurs');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file('image');
        $name = uniqid('user_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $name);

        return 'images_utilisateurs/' . $name;
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->peutModifierProfil(), 403);

        $this->changerMotDePasse($request, $user);

        return back()->with('success', 'Votre mot de passe a été mis à jour.');
    }

    /** Mot de passe donné par un Admin : page obligatoire avant tout le reste. */
    public function forcePassword()
    {
        $user = Auth::user();
        if (!$user->doit_changer_mdp) {
            return redirect()->route('dashboard');
        }

        return view('auth.changer-mot-de-passe');
    }

    public function forcePasswordUpdate(Request $request)
    {
        $user = Auth::user();
        if (!$user->doit_changer_mdp) {
            return redirect()->route('dashboard');
        }

        $this->changerMotDePasse($request, $user);

        return redirect()->route('dashboard')->with('success', __('securite.change'));
    }

    /**
     * Règles communes (profil, changement obligatoire, app mobile) : 8
     * caractères au moins avec lettres et chiffres, mot de passe actuel exact,
     * nouveau différent de l'actuel. Les autres accès du compte sont coupés.
     */
    public static function changerMotDePasse(Request $request, $user): void
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', \App\Support\MotDePasse::regle()],
        ], [
            'password.different' => __('securite.identique'),
        ], [
            'current_password' => mb_strtolower(__('securite.actuel_court')),
            'password' => mb_strtolower(__('securite.nouveau')),
        ]);

        if (!Hash::check($data['current_password'], $user->pwd)) {
            throw ValidationException::withMessages(['current_password' => __('securite.actuel_incorrect')]);
        }

        $user->pwd = Hash::make($data['password']);
        $user->doit_changer_mdp = false;
        $user->save();

        $jeton = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;
        \App\Support\MotDePasse::revoquerAcces(
            $user,
            $request->hasSession() ? $request->session()->getId() : null,
            $jeton instanceof \Laravel\Sanctum\PersonalAccessToken ? (int) $jeton->id : null,
        );
    }
}
