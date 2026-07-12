<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\Evaluation;
use App\Models\LigneEvaluation;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SupprimerEvaluationTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_supprimer';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Supprimer définitivement une évaluation et toutes les notes associées. Action irréversible.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_evaluation' => ['type' => 'INTEGER', 'description' => "Identifiant de l'évaluation à supprimer."],
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
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['evaluation_supprimer']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function requiresDoubleConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $idEvaluation = (int) ($args['id_evaluation'] ?? 0);
        $evaluation = Evaluation::find($idEvaluation);
        $ligne = LigneEvaluation::with(['classe', 'matiere'])->where('id_evaluation', $idEvaluation)->first();
        $nombreNotes = LigneEvaluation::where('id_evaluation', $idEvaluation)->whereNotNull('note')->count();

        return sprintf(
            "⚠️ Cette action est irréversible. Je vais supprimer définitivement l'évaluation « %s » (%s / %s) "
            . "ainsi que les %d note(s) déjà saisies. Confirmez-vous ?",
            $evaluation?->libeller ?? '#' . $idEvaluation,
            $ligne?->classe?->nom_classe ?? 'classe inconnue',
            $ligne?->matiere?->nom_matiere ?? 'matière inconnue',
            $nombreNotes
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EvaluationController::class)->destroy((int) $validated['id_evaluation']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Évaluation supprimée avec succès.',
            'data' => [],
        ];
    }
}
