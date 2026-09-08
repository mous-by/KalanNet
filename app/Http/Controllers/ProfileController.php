<?php

namespace App\Http\Controllers;

use App\Rules\MaliPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function updateInfo(Request $request)
    {
        $user = Auth::user();

        if ($request->filled('telephone')) {
            $request->merge(['telephone' => MaliPhone::normalize($request->input('telephone'))]);
        }

        $data = $request->validate([
            'nomPrenom' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('utilisateurs', 'email')->ignore($user->idUtilisateur, 'idUtilisateur')],
            'telephone' => ['nullable', 'string', 'max:20', new MaliPhone()],
        ]);

        $user->update($data);

        return back()->with('success', 'Vos informations ont été mises à jour.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->pwd)) {
            throw ValidationException::withMessages([
                'current_password' => 'Le mot de passe actuel est incorrect.',
            ]);
        }

        $user->pwd = Hash::make($data['password']);
        $user->save();

        return back()->with('success', 'Votre mot de passe a été mis à jour.');
    }
}
