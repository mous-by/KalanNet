<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\EvaluationCommuneController as WebEvaluationCommuneController;
use App\Support\Territoire\EvaluationsCommunes;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Évaluations communes dans l'application mobile : création et suivi par le
 * DAE / DCAP, téléchargement du sujet et du corrigé par les écoles. Mêmes
 * règles que le site (module soumis à la permission « Voir le module
 * Évaluations communes »).
 */
class EvaluationCommuneController extends WebEvaluationCommuneController
{
    /* ---------------------------- DAE / DCAP ---------------------------- */

    public function territoireIndex()
    {
        $user = $this->superviseur();

        return response()->json([
            'evaluations' => EvaluationsCommunes::pourTerritoire($user)->values(),
            'niveaux' => DB::table('classes_officielles')
                ->when($user->droit === 'DCAP', fn ($q) => $q->whereIn('ordre_enseignement', EvaluationsCommunes::ORDRES_FONDAMENTAL))
                ->orderBy('ordre_enseignement')->orderBy('id_classe_officielle')
                ->get(['id_classe_officielle', 'nom_classe_officielle', 'ordre_enseignement']),
            'matieres' => DB::table('matiere')->whereNull('id_ecole')->orderBy('nom_matiere')->get(['id_matiere', 'nom_matiere']),
            'caps' => $user->droit === 'DAE' ? DB::table('cap')->where('id_academie', $user->id_academie)->orderBy('nom_cap')->get(['id_cap', 'nom_cap']) : [],
        ]);
    }

    public function territoireStore(Request $request)
    {
        [$id, $message] = $this->creer($request);

        return response()->json(['id' => $id, 'message' => $message], 201);
    }

    public function territoireShow(int $id)
    {
        $user = $this->superviseur();
        $evaluation = EvaluationsCommunes::pourTerritoire($user)->firstWhere('id', $id);
        abort_unless($evaluation, 404);

        return response()->json([
            'evaluation' => $evaluation,
            'gerable' => EvaluationsCommunes::peutGerer($user, $evaluation),
            'a_publier' => EvaluationsCommunes::aPublierParDcap($user, $evaluation),
            'passe_par_cap' => EvaluationsCommunes::passeParCap($evaluation),
            'caps' => $user->droit === 'DAE' && $evaluation->origine === 'academie' ? EvaluationsCommunes::caps($evaluation)->values() : [],
            'ecoles' => EvaluationsCommunes::suivi($evaluation, $user->droit === 'DCAP' && $evaluation->origine === 'academie' ? (int) $user->id_cap : null)->values(),
        ]);
    }

    public function territoirePublier(Request $request, int $id)
    {
        parent::publierAuxEcoles($request, $id);

        return response()->json(['message' => 'Évaluation publiée à vos écoles. Leurs Admin ont été prévenus.']);
    }

    public function territoireCorrige(Request $request, int $id)
    {
        parent::storeCorrige($request, $id);

        return response()->json(['message' => "Corrigé enregistré. Il sera visible par les écoles après la fin de l'épreuve."]);
    }

    public function territoireAnnuler(int $id)
    {
        parent::annuler($id);

        return response()->json(['message' => "Évaluation annulée : les écoles n'y ont plus accès."]);
    }

    /* ------------------------------ École ------------------------------ */

    public function ecoleListe()
    {
        $idEcole = $this->ecoleAutorisee();
        $accuses = DB::table('evaluation_commune_accuses')->where('id_ecole', $idEcole)->get()->keyBy('id_evaluation');

        return response()->json([
            'evaluations' => EvaluationsCommunes::pourEcole($idEcole)->map(function ($e) use ($accuses) {
                $e->telecharge_at = $accuses[$e->id]->telecharge_at ?? null;

                return $e;
            })->values(),
        ]);
    }

    /** Sujet ou corrigé pour l'école : refus en JSON tant qu'ils ne sont pas disponibles. */
    public function ecoleTelecharger(int $id, string $piece)
    {
        $idEcole = $this->ecoleAutorisee();
        $evaluation = EvaluationsCommunes::pourEcole($idEcole)->firstWhere('id', $id);
        abort_unless($evaluation, 404);

        if ($piece === 'sujet' && !$evaluation->sujet_disponible) {
            return response()->json(['message' => 'Le sujet sera disponible le '.Carbon::parse($evaluation->liberation_sujet_at)->format('d/m/Y à H:i').'.'], 422);
        }
        if ($piece === 'corrige' && !$evaluation->corrige_disponible) {
            return response()->json(['message' => "Le corrigé n'est pas encore disponible."], 422);
        }

        EvaluationsCommunes::accuser($evaluation->id, $idEcole, (int) Auth::id());

        return $this->servir($evaluation, $piece);
    }
}
