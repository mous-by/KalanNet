<?php

namespace App\Services\Kalanbot\Tools\Programmes;

use App\Http\Controllers\ProgrammeController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SupprimerProgrammeTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'programmes_supprimer';
    }

    public function module(): string
    {
        return 'programmes';
    }

    public function description(): string
    {
        return "Supprimer définitivement un programme officiel et toutes ses leçons. Action irréversible.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => ['id_programme' => ['type' => 'INTEGER']],
            'required' => ['id_programme'],
        ];
    }

    public function validationRules(): array
    {
        return ['id_programme' => 'required|integer'];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['programmes_supprimer', 'programme_supprimer', 'programmes_suppression', 'programme_suppression']);
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
        return "⚠️ Cette action est irréversible. Je vais supprimer définitivement le programme officiel #"
            . ($args['id_programme'] ?? '?') . " et toutes ses leçons. Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(ProgrammeController::class)->destroy((int) $validated['id_programme']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Programme officiel supprimé avec succès.',
            'data' => [],
        ];
    }
}
