<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Filiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FiliereController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $filieres = Filiere::query()
            ->when($search, fn ($query) => $query->where('nom_filiere', 'like', "%{$search}%"))
            ->orderBy('nom_filiere')
            ->paginate(20);

        return view('pedagogie.filieres', compact('filieres'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('filieres_creation');
        $data = $this->validateFiliere($request);

        Filiere::create([
            'nom_filiere' => $data['nom_filiere'],
            'id_ecole' => session('idEcole'),
            'actif' => true,
        ]);

        return redirect()->route('pedagogie.filieres')->with('success', 'Filière créée avec succès.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('filieres_modification');
        $filiere = Filiere::findOrFail($id);
        $data = $this->validateFiliere($request);

        $filiere->update(['nom_filiere' => $data['nom_filiere']]);

        return redirect()->route('pedagogie.filieres')->with('success', 'Filière modifiée avec succès.');
    }

    public function destroy($id)
    {
        $this->authorizePermission('filieres_supprimer');
        $filiere = Filiere::findOrFail($id);

        $usedInClasses = Classe::where('id_filiere', $filiere->id_filiere)->exists();
        if ($usedInClasses) {
            return redirect()->route('pedagogie.filieres')
                ->with('error', 'Impossible de supprimer cette filière car elle est utilisée par une classe.');
        }

        $filiere->delete();

        return redirect()->route('pedagogie.filieres')->with('success', 'Filière supprimée avec succès.');
    }

    protected function validateFiliere(Request $request): array
    {
        return $request->validate([
            'nom_filiere' => 'required|string|max:100',
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        $user = Auth::user();
        if (!$user || ($user->droit !== 'SupAdmin' && !$user->userHasPermission($permission))) {
            abort(403, 'Permission insuffisante.');
        }
    }
}
