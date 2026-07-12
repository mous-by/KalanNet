<?php

namespace App\Services\Kalanbot\Tools\Matieres;

use App\Http\Controllers\MatiereController;
use App\Models\Matiere;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SupprimerMatiereTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'matieres_supprimer';
    }

    public function module(): string
    {
        return 'matieres';
    }

    public function description(): string
    {
        return "Supprimer une matière. Refusé automatiquement si elle est utilisée dans une classe ou une évaluation.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => ['id_matiere' => ['type' => 'INTEGER']],
            'required' => ['id_matiere'],
        ];
    }

    public function validationRules(): array
    {
        return ['id_matiere' => 'required|integer'];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('matieres_supprimer');
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
        $matiere = Matiere::find($args['id_matiere'] ?? null);

        return sprintf(
            "⚠️ Cette action est irréversible. Je vais supprimer la matière « %s » (refusé si utilisée dans une "
            . "classe ou une évaluation). Confirmez-vous ?",
            $matiere?->nom_matiere ?? 'inconnue'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(MatiereController::class)->destroy((int) $validated['id_matiere']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Matière supprimée avec succès.',
            'data' => [],
        ];
    }
}
