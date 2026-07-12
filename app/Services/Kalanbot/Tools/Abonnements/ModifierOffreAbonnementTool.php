<?php

namespace App\Services\Kalanbot\Tools\Abonnements;

use App\Http\Controllers\AbonnementController;
use App\Models\AbonnementOffre;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierOffreAbonnementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'abonnements_offre_modifier';
    }

    public function module(): string
    {
        return 'abonnements';
    }

    public function description(): string
    {
        return "Modifier une formule d'abonnement KalanNet existante (réservé au SupAdmin).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_offre' => ['type' => 'INTEGER'],
                'code' => ['type' => 'STRING'],
                'nom' => ['type' => 'STRING'],
                'description' => ['type' => 'STRING'],
                'montant' => ['type' => 'NUMBER'],
                'devise' => ['type' => 'STRING'],
                'duree_jours' => ['type' => 'INTEGER'],
                'actif' => ['type' => 'BOOLEAN'],
            ],
            'required' => ['id_offre', 'code', 'nom', 'montant', 'devise', 'duree_jours'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_offre' => 'required|integer',
            'code' => 'required|string|max:40',
            'nom' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'montant' => 'required|numeric|min:1',
            'devise' => 'required|string|max:8',
            'duree_jours' => 'required|integer|min:1|max:3650',
            'actif' => 'nullable|boolean',
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

        return "Je vais modifier la formule « " . ($offre?->nom ?? 'inconnue') . " » : nouveau montant "
            . ($args['montant'] ?? '?') . " " . ($args['devise'] ?? '') . ". Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(function () use ($request, $validated) {
            $offre = AbonnementOffre::findOrFail($validated['id_offre']);

            return app(AbonnementController::class)->updateOffre($request, $offre);
        });
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Formule d'abonnement mise à jour.",
            'data' => [],
        ];
    }
}
