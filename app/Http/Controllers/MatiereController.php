<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use App\Models\LigneClasse;
use App\Models\LigneEvaluation;
use App\Models\MatiereOrdre;
use App\Support\ExamenNational;
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
        $this->authorizePermission('matieres_creation');
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
        $this->authorizePermission('matieres_modification');
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
        $this->authorizePermission('matieres_supprimer');
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

    protected function validateMatiere(Request $request): array
    {
        return $request->validate([
            'nom_matiere' => 'required|string|max:50',
            'ordre_enseignement' => 'required|array|min:1',
            'ordre_enseignement.*' => 'required|string|in:' . implode(',', array_keys($this->allOrdres())),
        ], [
            'ordre_enseignement.required' => 'Veuillez sélectionner au moins un ordre d’enseignement.',
        ]);
    }

    protected function syncOrdres(Matiere $matiere, array $ordres): void
    {
        foreach (array_unique($ordres) as $ordre) {
            MatiereOrdre::create([
                'id_matiere' => $matiere->id_matiere,
                'ordre_enseignement' => $ordre,
            ]);
        }
    }

    /**
     * Cles fixes : ce sont les valeurs reellement stockees dans
     * matiere_ordre.ordre_enseignement (jamais migrees vers les slugs de
     * Classe.ordreEnseignement, contrairement a classes_officielles) --
     * seuls les LIBELLES affiches s'adaptent au pays de l'ecole.
     */
    protected function allOrdres(): array
    {
        $idEcole = session('idEcole') ?: Auth::user()->idEcole;
        $labels = ExamenNational::ordresLabels($idEcole);

        return [
            'Fondamentale I' => $labels['fondamentale1'],
            'Fondamentale II' => $labels['fondamentale2'],
            'Secondaire Generale' => $labels['secondairegenerale'],
            'Secondaire Technique et Professionnel' => $labels['secondairetechniqueetprofessionnel'],
        ];
    }

    protected function authorizePermission(string $permission): void
    {
        $user = Auth::user();
        if (!$user || ($user->droit !== 'SupAdmin' && !$user->userHasPermission($permission))) {
            abort(403, 'Permission insuffisante.');
        }
    }

    protected function ordresAutorises(): array
    {
        $user = Auth::user();
        $typeEcole = $user->ecole->typeEcole ?? null;

        if ($user->droit === 'SupAdmin' || $typeEcole === 'Complexe Scolaire') {
            return array_keys($this->allOrdres());
        }

        // Meme logique país-aware que ClasseController::ordresDisponibles() --
        // "Primaire"/"Secondaire Generale" hors Mali reutilisent les memes
        // libelles Fondamentale I/II que le Mali (c'est ce que produit
        // ordreMatiereMap() pour ces classes), pour que les matieres
        // assignees a un ordre restent compatibles quel que soit le pays.
        if (in_array($typeEcole, ['Primaire', 'Secondaire Generale'], true) && !ExamenNational::estMali($user->ecole)) {
            return $typeEcole === 'Primaire'
                ? ['Fondamentale I']
                : ['Fondamentale II', 'Secondaire Generale'];
        }

        if ($typeEcole === 'Fondamentale' || $typeEcole === 'Collège') {
            return ['Fondamentale I', 'Fondamentale II'];
        }

        if (in_array($typeEcole, ['Lycée Privée', 'Secondaire Generale', 'Secondaire Technique et Professionnel'], true)) {
            return ['Secondaire Generale', 'Secondaire Technique et Professionnel'];
        }

        return $typeEcole ? [$typeEcole] : array_keys($this->allOrdres());
    }
}
