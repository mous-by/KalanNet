<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\MatiereController as WebMatiereController;
use App\Models\LigneClasse;
use App\Models\LigneEvaluation;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatiereController extends WebMatiereController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->get('search');
        $ordresAutorises = $this->ordresAutorises();

        $matieres = Matiere::query()
            ->with('ordres')
            ->when($search, fn ($q) => $q->where('nom_matiere', 'like', "%{$search}%"))
            ->when($user->droit !== 'SupAdmin' && !empty($ordresAutorises), fn ($q) => $q->whereHas(
                'ordres',
                fn ($inner) => $inner->whereIn('ordre_enseignement', $ordresAutorises)
            ))
            ->orderBy('nom_matiere')
            ->get();

        return response()->json([
            'data' => $matieres,
            'ordres_autorises' => $ordresAutorises,
            'ordres_disponibles' => $this->allOrdres(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMatiere($request);
        $user = $request->user();

        $matiere = DB::transaction(function () use ($data, $user) {
            $matiere = Matiere::create([
                'nom_matiere' => $data['nom_matiere'],
                'id_ecole' => $user->droit === 'SupAdmin' ? null : session('idEcole'),
            ]);

            $this->syncOrdres($matiere, $data['ordre_enseignement']);

            return $matiere;
        });

        return response()->json($matiere->load('ordres'), 201);
    }

    public function update(Request $request, $id)
    {
        $matiere = Matiere::findOrFail($id);
        $data = $this->validateMatiere($request);

        DB::transaction(function () use ($matiere, $data) {
            $matiere->update(['nom_matiere' => $data['nom_matiere']]);
            $matiere->ordres()->delete();
            $this->syncOrdres($matiere, $data['ordre_enseignement']);
        });

        return response()->json($matiere->fresh('ordres'));
    }

    public function destroy($id)
    {
        $matiere = Matiere::findOrFail($id);

        $usedInClasses = LigneClasse::where('id_matiere', $matiere->id_matiere)->exists();
        $usedInEvaluations = LigneEvaluation::where('id_matiere', $matiere->id_matiere)->exists();

        if ($usedInClasses || $usedInEvaluations) {
            return response()->json([
                'message' => 'Impossible de supprimer cette matière car elle est utilisée dans une classe ou une évaluation.',
            ], 422);
        }

        DB::transaction(function () use ($matiere) {
            $matiere->ordres()->delete();
            $matiere->delete();
        });

        return response()->json(['success' => true]);
    }
}
