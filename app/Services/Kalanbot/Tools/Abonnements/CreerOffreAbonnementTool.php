<?php

namespace App\Services\Kalanbot\Tools\Abonnements;

use App\Http\Controllers\AbonnementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerOffreAbonnementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'abonnements_offre_creer';
    }

    public function module(): string
    {
        return 'abonnements';
    }

    public function description(): string
    {
        return "Créer une nouvelle formule d'abonnement KalanNet (catalogue global, réservé au SupAdmin).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'code' => ['type' => 'STRING'],
                'nom' => ['type' => 'STRING'],
                'description' => ['type' => 'STRING'],
                'montant' => ['type' => 'NUMBER'],
                'devise' => ['type' => 'STRING', 'description' => 'Ex: XOF, FCFA.'],
                'duree_jours' => ['type' => 'INTEGER'],
                'actif' => ['type' => 'BOOLEAN'],
            ],
            'required' => ['code', 'nom', 'montant', 'devise', 'duree_jours'],
        ];
    }

    public function validationRules(): array
    {
        return [
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
        return sprintf(
            "Je vais créer la formule d'abonnement « %s » (code %s) à %s %s pour %s jour(s). Confirmez-vous ?",
            $args['nom'] ?? '',
            $args['code'] ?? '',
            $args['montant'] ?? '',
            $args['devise'] ?? '',
            $args['duree_jours'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(AbonnementController::class)->storeOffre($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Formule d'abonnement créée.",
            'data' => [],
        ];
    }
}
