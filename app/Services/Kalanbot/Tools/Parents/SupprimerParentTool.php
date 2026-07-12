<?php

namespace App\Services\Kalanbot\Tools\Parents;

use App\Http\Controllers\ParentController;
use App\Models\ParentModel;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SupprimerParentTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'parents_supprimer';
    }

    public function module(): string
    {
        return 'parents';
    }

    public function description(): string
    {
        return "Supprimer définitivement un parent (détache tous ses enfants). Action irréversible.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_parent' => ['type' => 'INTEGER', 'description' => 'Identifiant du parent à supprimer.'],
            ],
            'required' => ['id_parent'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_parent' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('parents_supprimer');
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
        $parent = ParentModel::with('eleves')->find($args['id_parent'] ?? null);
        $nom = $parent?->nom_prenom_parent ?? 'ce parent';
        $nombreEnfants = $parent?->eleves->count() ?? 0;

        return sprintf(
            "⚠️ Cette action est irréversible. Je vais supprimer définitivement le parent %s et le détacher de "
            . "%d enfant(s) (les élèves ne sont pas supprimés, seul le lien est retiré). Confirmez-vous ?",
            $nom,
            $nombreEnfants
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(ParentController::class)->destroy((int) $validated['id_parent']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Parent supprimé avec succès.',
            'data' => [],
        ];
    }
}
