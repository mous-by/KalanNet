<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use App\Models\LigneClasse;
use App\Models\LigneEvaluation;
use App\Models\MatiereOrdre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MatiereController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search');
        $ordresAutorises = $this->ordresAutorises();

        $matieres = Matiere::query()
            ->with('ordres')
            ->when($search, function ($query) use ($search) {
                $query->where('nom_matiere', 'like', "%{$search}%");
            })
            ->when($user->droit !== 'SupAdmin' && !empty($ordresAutorises), function ($query) use ($ordresAutorises) {
                $query->whereHas('ordres', fn ($inner) => $inner->whereIn('ordre_enseignement', $ordresAutorises));
            })
            ->orderBy('nom_matiere')
            ->paginate(20);

        return view('pedagogie.matieres', [
            'matieres' => $matieres,
            'allOrdres' => $this->allOrdres(),
            'ordresAutorises' => $ordresAutorises,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMatiere($request);

        DB::transaction(function () use ($data) {
            $matiere = Matiere::create([
                'nom_matiere' => $data['nom_matiere'],
                'id_ecole' => Auth::user()->droit === 'SupAdmin' ? null : session('idEcole'),
            ]);

            $this->syncOrdres($matiere, $data['ordre_enseignement']);
        });

        return redirect()->route('pedagogie.matieres')->with('success', 'Insertion faite avec succès.');
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

        return redirect()->route('pedagogie.matieres')->with('success', 'La matière a été modifiée avec succès.');
    }

    public function destroy($id)
    {
        $matiere = Matiere::findOrFail($id);

        $usedInClasses = LigneClasse::where('id_matiere', $matiere->id_matiere)->exists();
        $usedInEvaluations = LigneEvaluation::where('id_matiere', $matiere->id_matiere)->exists();

        if ($usedInClasses || $usedInEvaluations) {
            return redirect()->route('pedagogie.matieres')
                ->with('error', 'Impossible de supprimer cette matière car elle est utilisée dans une classe ou une évaluation.');
        }

        DB::transaction(function () use ($matiere) {
            $matiere->ordres()->delete();
            $matiere->delete();
        });

        return redirect()->route('pedagogie.matieres')->with('success', 'La matière a été supprimée avec succès.');
    }

    private function validateMatiere(Request $request): array
    {
        return $request->validate([
            'nom_matiere' => 'required|string|max:50',
            'ordre_enseignement' => 'required|array|min:1',
            'ordre_enseignement.*' => 'required|string|in:' . implode(',', array_keys($this->allOrdres())),
        ], [
            'ordre_enseignement.required' => 'Veuillez sélectionner au moins un ordre d’enseignement.',
        ]);
    }

    private function syncOrdres(Matiere $matiere, array $ordres): void
    {
        foreach (array_unique($ordres) as $ordre) {
            MatiereOrdre::create([
                'id_matiere' => $matiere->id_matiere,
                'ordre_enseignement' => $ordre,
            ]);
        }
    }

    private function allOrdres(): array
    {
        return [
            'Fondamentale I' => 'Fondamentale I',
            'Fondamentale II' => 'Fondamentale II',
            'Secondaire Generale' => 'Secondaire Générale',
            'Secondaire Technique et Professionnel' => 'Secondaire Technique et Professionnel',
        ];
    }

    private function ordresAutorises(): array
    {
        $user = Auth::user();
        $typeEcole = $user->ecole->typeEcole ?? null;

        if ($user->droit === 'SupAdmin' || $typeEcole === 'Complexe Scolaire') {
            return array_keys($this->allOrdres());
        }

        if ($typeEcole === 'Fondamentale') {
            return ['Fondamentale I', 'Fondamentale II'];
        }

        if (in_array($typeEcole, ['Lycée Privée', 'Secondaire Generale', 'Secondaire Technique et Professionnel'], true)) {
            return ['Secondaire Generale', 'Secondaire Technique et Professionnel'];
        }

        return $typeEcole ? [$typeEcole] : array_keys($this->allOrdres());
    }
}
