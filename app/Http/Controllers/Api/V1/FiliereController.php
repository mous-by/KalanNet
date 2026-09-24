<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\FiliereController as WebFiliereController;
use App\Models\Classe;
use App\Models\Filiere;
use Illuminate\Http\Request;

class FiliereController extends WebFiliereController
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $filieres = Filiere::query()
            ->when($search, fn ($query) => $query->where('nom_filiere', 'like', "%{$search}%"))
            ->orderBy('nom_filiere')
            ->paginate(20);

        return response()->json($filieres);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('filieres_creation');
        $data = $this->validateFiliere($request);

        $filiere = Filiere::create([
            'nom_filiere' => $data['nom_filiere'],
            'id_ecole' => session('idEcole'),
            'actif' => true,
        ]);

        return response()->json($filiere, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('filieres_modification');
        $filiere = Filiere::findOrFail($id);
        $data = $this->validateFiliere($request);

        $filiere->update(['nom_filiere' => $data['nom_filiere']]);

        return response()->json($filiere->fresh());
    }

    public function destroy($id)
    {
        $this->authorizePermission('filieres_supprimer');
        $filiere = Filiere::findOrFail($id);

        $usedInClasses = Classe::where('id_filiere', $filiere->id_filiere)->exists();
        if ($usedInClasses) {
            return response()->json([
                'message' => 'Impossible de supprimer cette filière car elle est utilisée par une classe.',
            ], 422);
        }

        $filiere->delete();

        return response()->json(['success' => true]);
    }
}
