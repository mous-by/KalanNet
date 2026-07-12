<?php

namespace App\Services\Kalanbot\Tools\Presences;

use App\Http\Controllers\PresenceController;
use App\Models\Presence;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ValiderPresenceTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'presences_valider';
    }

    public function module(): string
    {
        return 'presences';
    }

    public function description(): string
    {
        return "Valider une présence en attente. Réservé aux responsables pédagogiques (pas aux enseignants).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_presence' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_presence'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_presence' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit !== 'enseignant'
            && ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['presence_validation_admin', 'presence_apercu']));
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $presence = Presence::with(['enseignant', 'classe'])->find($args['id_presence'] ?? null);

        return sprintf(
            "Je vais valider la présence de %s (%s, %s h). Confirmez-vous ?",
            $presence?->enseignant?->nom_prenom_enseignant ?? 'enseignant inconnu',
            $presence?->classe?->nom_classe ?? '?',
            $presence?->nombre_heure ?? '?'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(PresenceController::class)->validatePresence((int) $validated['id_presence']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Présence validée avec succès.',
            'data' => [],
        ];
    }
}
