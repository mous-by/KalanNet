<?php

namespace App\Services\Kalanbot\Tools\FinancesBanques;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerBanqueTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_banque_creer';
    }

    public function module(): string
    {
        return 'finances_banques';
    }

    public function description(): string
    {
        return "Créer un compte bancaire pour l'école.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'numero_compte' => ['type' => 'STRING'],
                'nom_banque' => ['type' => 'STRING'],
                'montant_initial' => ['type' => 'NUMBER'],
            ],
            'required' => ['numero_compte', 'nom_banque', 'montant_initial'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'numero_compte' => 'required|string|max:255',
            'nom_banque' => 'required|string|max:255',
            'montant_initial' => 'required|numeric|min:0',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('banques_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return sprintf(
            "Je vais créer le compte bancaire « %s » (n° %s) avec un solde initial de %s FCFA. Confirmez-vous ?",
            $args['nom_banque'] ?? '',
            $args['numero_compte'] ?? '',
            number_format((float) ($args['montant_initial'] ?? 0), 0, ',', ' ')
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeBanque($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Banque ajoutée avec succès.',
            'data' => [],
        ];
    }
}
