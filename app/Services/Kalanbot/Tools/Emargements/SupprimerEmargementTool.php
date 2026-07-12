<?php

namespace App\Services\Kalanbot\Tools\Emargements;

use App\Http\Controllers\EmargementController;
use App\Models\Emargement;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SupprimerEmargementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emargements_supprimer';
    }

    public function module(): string
    {
        return 'emargements';
    }

    public function description(): string
    {
        return "Supprimer un émargement non encore validé. Un émargement déjà validé ne peut pas être supprimé.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_emargement' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_emargement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_emargement' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['emargement_supprimer', 'emargement_faire']);
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
        $emargement = Emargement::with(['enseignant', 'classe', 'matiere'])->find($args['id_emargement'] ?? null);

        return sprintf(
            "⚠️ Cette action est irréversible. Je vais supprimer l'émargement de %s (%s / %s, %s h). Confirmez-vous ?",
            $emargement?->enseignant?->nom_prenom_enseignant ?? 'enseignant inconnu',
            $emargement?->classe?->nom_classe ?? '?',
            $emargement?->matiere?->nom_matiere ?? '?',
            $emargement?->nombre_heure ?? '?'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EmargementController::class)->destroy((int) $validated['id_emargement']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Émargement supprimé avec succès.',
            'data' => [],
        ];
    }
}
