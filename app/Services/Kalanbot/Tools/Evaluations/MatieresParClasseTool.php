<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class MatieresParClasseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_matieres_par_classe';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Lister les matières enseignées dans une classe (et par l'enseignant connecté s'il s'agit d'un "
            . "enseignant). Utile avant de programmer une évaluation pour connaître les id_matiere disponibles.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER', 'description' => "Identifiant de la classe."],
            ],
            'required' => ['id_classe'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer',
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

        $outcome = $this->callController(fn () => app(EvaluationController::class)->matieresByClasse((int) $validated['id_classe']));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractJsonData($outcome['result']);

        return [
            'success' => true,
            'message' => count($data['matiere'] ?? []) . ' matière(s) trouvée(s).',
            'data' => ['matieres' => $data['matiere'] ?? []],
        ];
    }
}
