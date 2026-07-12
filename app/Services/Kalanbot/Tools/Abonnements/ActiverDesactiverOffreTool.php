<?php

namespace App\Services\Kalanbot\Tools\Abonnements;

use App\Http\Controllers\AbonnementController;
use App\Models\AbonnementOffre;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ActiverDesactiverOffreTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'abonnements_offre_activer_desactiver';
    }

    public function module(): string
    {
        return 'abonnements';
    }

    public function description(): string
    {
        return "Activer ou désactiver une formule d'abonnement (bascule son statut actif/inactif). "
            . "Réservé au SupAdmin.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_offre' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_offre'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_offre' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('abonnements_configuration');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $offre = AbonnementOffre::find($args['id_offre'] ?? null);
        $action = $offre && $offre->actif ? 'désactiver' : 'activer';

        return "Je vais {$action} la formule « " . ($offre?->nom ?? 'inconnue') . " ». Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(function () use ($validated) {
            $offre = AbonnementOffre::findOrFail($validated['id_offre']);

            return app(AbonnementController::class)->toggleOffre($offre);
        });
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Statut de la formule mis à jour.',
            'data' => [],
        ];
    }
}
