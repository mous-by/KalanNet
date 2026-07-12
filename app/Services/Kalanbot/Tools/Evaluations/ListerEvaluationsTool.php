<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerEvaluationsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_lister';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Lister les évaluations déjà programmées, avec filtres optionnels par classe, matière, trimestre ou mois. "
            . "À utiliser pour retrouver l'identifiant d'une évaluation avant de consulter ou saisir des notes.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER', 'description' => "Identifiant de la classe (optionnel)."],
                'id_matiere' => ['type' => 'INTEGER', 'description' => "Identifiant de la matière (optionnel)."],
                'id_trimestre' => ['type' => 'INTEGER', 'description' => "Identifiant du trimestre (optionnel)."],
                'mois' => ['type' => 'INTEGER', 'description' => "Mois (1-12), pour les classes évaluées au mois plutôt qu'au trimestre."],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'nullable|integer',
            'id_matiere' => 'nullable|integer',
            'id_trimestre' => 'nullable|integer',
            'mois' => 'nullable|integer|between:1,12',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('evaluation_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(EvaluationController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['evaluations'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $evaluations = $items->map(fn ($ligne) => [
            'id_evaluation' => $ligne->id_evaluation,
            'libelle' => optional($ligne->evaluation)->libeller,
            'date_evaluation' => optional($ligne->evaluation)->date_evaluation,
            'classe' => optional($ligne->classe)->nom_classe,
            'matiere' => optional($ligne->matiere)->nom_matiere,
            'trimestre' => optional($ligne->trimestre)->nom_trimestre,
            'mois' => $ligne->mois,
            'statut_validation' => $ligne->validation_status,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($evaluations) . ' évaluation(s) trouvée(s).',
            'data' => ['evaluations' => $evaluations],
        ];
    }
}
