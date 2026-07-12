<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ElevesParClasseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_eleves_par_classe';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Lister les élèves actifs d'une classe pour une année scolaire donnée. Utile pour identifier un "
            . "élève par son nom (recherche approximative possible parmi la liste retournée) avant de programmer "
            . "une évaluation ou de saisir une note.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER', 'description' => "Identifiant de la classe."],
                'id_annee_scolaire' => ['type' => 'INTEGER', 'description' => "Identifiant de l'année scolaire."],
            ],
            'required' => ['id_classe', 'id_annee_scolaire'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
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

        $outcome = $this->callController(fn () => app(EvaluationController::class)->students($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractJsonData($outcome['result']);

        return [
            'success' => true,
            'message' => count($data['eleves'] ?? []) . ' élève(s) trouvé(s).',
            'data' => ['eleves' => $data['eleves'] ?? []],
        ];
    }
}
