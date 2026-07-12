<?php

namespace App\Services\Kalanbot\Tools\Emargements;

use App\Http\Controllers\EmargementController;
use App\Models\Emargement;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ValiderEmargementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emargements_valider';
    }

    public function module(): string
    {
        return 'emargements';
    }

    public function description(): string
    {
        return "Valider un émargement en attente. Réservé aux responsables pédagogiques (pas aux enseignants).";
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
        return $user->droit !== 'enseignant'
            && ($user->droit === 'SupAdmin' || $user->userHasPermission('emargement_validation_admin'));
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $emargement = Emargement::with(['enseignant', 'classe', 'matiere'])->find($args['id_emargement'] ?? null);

        return sprintf(
            "Je vais valider l'émargement de %s (%s / %s, %s h). Confirmez-vous ?",
            $emargement?->enseignant?->nom_prenom_enseignant ?? 'enseignant inconnu',
            $emargement?->classe?->nom_classe ?? '?',
            $emargement?->matiere?->nom_matiere ?? '?',
            $emargement?->nombre_heure ?? '?'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EmargementController::class)->validateEmargement((int) $validated['id_emargement']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Émargement validé avec succès.',
            'data' => [],
        ];
    }
}
