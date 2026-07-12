<?php

namespace App\Services\Kalanbot\Tools\Classes;

use App\Http\Controllers\ClasseController;
use App\Models\Classe;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SupprimerClasseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'classes_supprimer';
    }

    public function module(): string
    {
        return 'classes';
    }

    public function description(): string
    {
        return "Supprimer une classe. Refusé automatiquement si des élèves y sont inscrits.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => ['id_classe' => ['type' => 'INTEGER']],
            'required' => ['id_classe'],
        ];
    }

    public function validationRules(): array
    {
        return ['id_classe' => 'required|integer'];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('classes_supprimer');
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
        $classe = Classe::find($args['id_classe'] ?? null);

        return sprintf(
            "⚠️ Cette action est irréversible. Je vais supprimer la classe « %s » (l'opération sera refusée si des "
            . "élèves y sont inscrits). Confirmez-vous ?",
            $classe?->nom_classe ?? 'inconnue'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(ClasseController::class)->destroy((int) $validated['id_classe']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Classe supprimée avec succès.',
            'data' => [],
        ];
    }
}
