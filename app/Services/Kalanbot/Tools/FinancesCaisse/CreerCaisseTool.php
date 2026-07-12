<?php

namespace App\Services\Kalanbot\Tools\FinancesCaisse;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

/**
 * ⚠️ FinanceController::storeCaisse() n'a AUCUNE vérification de permission dans le
 * contrôleur (trou de sécurité existant, hors périmètre de ce correctif). Conformément
 * à la politique validée, l'autorisation est durcie ici au niveau de l'outil, sans
 * modifier le contrôleur : restreinte aux rôles habituellement responsables des finances.
 */
class CreerCaisseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_caisse_creer';
    }

    public function module(): string
    {
        return 'finances_caisse';
    }

    public function description(): string
    {
        return "Créer la caisse unique de l'école (montant initial). Une seule caisse par école ; refusé si "
            . "une caisse existe déjà.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'libelle' => ['type' => 'STRING'],
                'montant_initial' => ['type' => 'NUMBER'],
                'status' => ['type' => 'INTEGER', 'description' => '1 = active, 0 = inactive.'],
            ],
            'required' => ['libelle', 'montant_initial'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'libelle' => 'required|string|max:255',
            'montant_initial' => 'required|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
        ];
    }

    public function authorize(User $user): bool
    {
        return in_array($user->droit, ['SupAdmin', 'Admin', 'Gestionnaire'], true) || $user->userHasPermission('caisses_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return sprintf(
            "Je vais créer la caisse « %s » avec un montant initial de %s FCFA. Confirmez-vous ?",
            $args['libelle'] ?? '',
            number_format((float) ($args['montant_initial'] ?? 0), 0, ',', ' ')
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $payload = array_merge(['status' => 1], $validated);
        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeCaisse($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Caisse créée avec succès.',
            'data' => [],
        ];
    }
}
