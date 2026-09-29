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
        $matieres = $this->matieresFiltrees($request)->paginate(20)->withQueryString();

        return view('pedagogie.matieres', [
            'matieres' => $matieres,
            'allOrdres' => $this->allOrdres(),
            'ordresAutorises' => $this->ordresAutorises(),
            'filtres' => $request->only(['search', 'type', 'ordre']),
            'ecoleFrancoArabe' => (bool) $this->currentEcole()?->franco_arabe,
        ]);
    }

    /**
     * Liste des matières visibles par l'utilisateur, avec les filtres de
     * l'écran : nom, type (classiques / franco-arabes) et ordre d'enseignement.
     */
    protected function matieresFiltrees(Request $request)
    {
        $user = Auth::user();
        $ordresAutorises = $this->ordresAutorises();
        $search = trim((string) $request->get('search'));
        $type = $request->get('type');
        $ordre = $request->get('ordre');

        return Matiere::query()
            ->with('ordres')
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->visiblesPourEcole($this->currentEcole()))
            ->when($search !== '', fn ($query) => $query->where('nom_matiere', 'like', "%{$search}%"))
            ->when($type === 'franco_arabe', fn ($query) => $query->where('est_franco_arabe', true))
            ->when($type === 'classique', fn ($query) => $query->where('est_franco_arabe', false))
            ->when($ordre && array_key_exists($ordre, $this->allOrdres()), fn ($query) => $query->whereHas('ordres', fn ($inner) => $inner->where('ordre_enseignement', $ordre)))
            ->when($user->droit !== 'SupAdmin' && !empty($ordresAutorises), function ($query) use ($ordresAutorises) {
                $query->whereHas('ordres', fn ($inner) => $inner->whereIn('ordre_enseignement', $ordresAutorises));
            })
            ->orderBy('nom_matiere');
    }

    /**
     * SupAdmin : case du formulaire. Admin : une matière créée par une école
     * franco-arabe est franco-arabe.
     */
    protected function estFrancoArabe(Request $request): bool
    {
        return Auth::user()->droit === 'SupAdmin'
            ? $request->boolean('est_franco_arabe')
            : (bool) $this->currentEcole()?->franco_arabe;
    }

    public function store(Request $request)
    {
        $this->authorizePermission('matieres_creation');
        $data = $this->validateMatiere($request);

        DB::transaction(function () use ($data) {
            $matiere = Matiere::create([
                'nom_matiere' => $data['nom_matiere'],
                'id_ecole' => Auth::user()->droit === 'SupAdmin' ? null : session('idEcole'),
                'est_franco_arabe' => $this->estFrancoArabe(request()),
            ]);

            $this->syncOrdres($matiere, $data['ordre_enseignement'] ?? []);
        });

        return redirect()->route('pedagogie.matieres')->with('success', 'Insertion faite avec succès.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('matieres_modification');
        $matiere = Matiere::findOrFail($id);
        if (!$this->peutGererMatiere($matiere)) {
            return redirect()->route('pedagogie.matieres')->with('error', __('matieres.commune_non_modifiable'));
        }
        $data = $this->validateMatiere($request);

        DB::transaction(function () use ($matiere, $data, $request) {
            $matiere->update(array_merge(
                ['nom_matiere' => $data['nom_matiere']],
                Auth::user()->droit === 'SupAdmin' ? ['est_franco_arabe' => $request->boolean('est_franco_arabe')] : []
            ));
            $matiere->ordres()->delete();
            $this->syncOrdres($matiere, $data['ordre_enseignement'] ?? []);
        });

        return redirect()->route('pedagogie.matieres')->with('success', 'La matière a été modifiée avec succès.');
    }

    public function destroy($id)
    {
        $this->authorizePermission('matieres_supprimer');
        $matiere = Matiere::findOrFail($id);
        if (!$this->peutGererMatiere($matiere)) {
            return redirect()->route('pedagogie.matieres')->with('error', __('matieres.commune_non_modifiable'));
        }

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

    /**
     * Les matières communes (id_ecole null) servent à toutes les écoles : seul
     * le SupAdmin peut les modifier ou les supprimer. Une école gère les siennes.
     */
    protected function peutGererMatiere(Matiere $matiere): bool
    {
        if (Auth::user()->droit === 'SupAdmin') {
            return true;
        }

        return $matiere->id_ecole !== null && (int) $matiere->id_ecole === (int) session('idEcole');
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

    /**
     * L'ecole actuellement geree : celle selectionnee en session, jamais
     * seulement Auth::user()->ecole -- sinon un SupAdmin (qui n'a pas de
     * propre ecole) navigue toujours "hors Ecole de Sante", meme quand il
     * gere une ecole de sante via la selection d'ecole.
     */
    protected function currentEcole(): ?\App\Models\Ecole
    {
        $idEcole = session('idEcole') ?: Auth::user()->idEcole;

        return $idEcole ? \App\Models\Ecole::withoutGlobalScopes()->find($idEcole) : null;
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
            // Pas un ordre d'enseignement au sens Fondamentale/Secondaire :
            // sert uniquement a marquer une matiere (Anatomie, Pharmacologie...)
            // comme partagee par toutes les Ecoles de Sante, sur le meme
            // principe que les matieres globales du Mali (id_ecole=null) --
            // voir ClasseController::matieresDisponibles().
            'École de Santé' => 'École de Santé',
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
        $typeEcole = $this->currentEcole()->typeEcole ?? null;

        if ($user->droit === 'SupAdmin') {
            return array_keys($this->allOrdres());
        }

        if ($typeEcole === 'École de Santé') {
            return ['École de Santé'];
        }

        if ($typeEcole === 'Complexe Scolaire') {
            return ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale', 'Secondaire Technique et Professionnel'];
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
