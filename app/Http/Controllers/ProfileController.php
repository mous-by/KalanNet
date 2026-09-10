<?php

namespace App\Http\Controllers;

use App\Rules\MaliPhone;
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
