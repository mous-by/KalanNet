<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\Evaluation;
use App\Models\LigneEvaluation;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ValiderNotesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_valider_notes';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Valider les notes saisies d'une évaluation, ce qui les rend disponibles pour les bulletins. "
            . "Réservé aux responsables pédagogiques.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_evaluation' => ['type' => 'INTEGER', 'description' => "Identifiant de l'évaluation à valider."],
            ],
            'required' => ['id_evaluation'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_evaluation' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'evaluation_validation_notes', 'valider_note_saisi', 'valider_notes_saisies',
        ]);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $description = $this->describeEvaluation((int) ($args['id_evaluation'] ?? 0));

        return "Je vais valider les notes de l'évaluation {$description}. Elles deviendront disponibles pour les bulletins. Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EvaluationController::class)->validateNotes((int) $validated['id_evaluation']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Notes validées avec succès. Elles sont désormais disponibles pour les bulletins.',
            'data' => [],
        ];
    }

    private function describeEvaluation(int $idEvaluation): string
    {
        $evaluation = Evaluation::find($idEvaluation);
        $ligne = LigneEvaluation::with(['classe', 'matiere'])->where('id_evaluation', $idEvaluation)->first();

        return sprintf(
            '« %s » (%s / %s)',
            $evaluation?->libeller ?? '#' . $idEvaluation,
            $ligne?->classe?->nom_classe ?? 'classe inconnue',
            $ligne?->matiere?->nom_matiere ?? 'matière inconnue'
        );
    }
}
