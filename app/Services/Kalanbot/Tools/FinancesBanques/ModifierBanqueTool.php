<?php

namespace App\Services\Kalanbot\Tools\FinancesBanques;

use App\Http\Controllers\FinanceController;
use App\Models\Banque;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierBanqueTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_banque_modifier';
    }

    public function module(): string
    {
        return 'finances_banques';
    }

    public function description(): string
    {
        return "Modifier un compte bancaire (numéro, nom, ou solde). Attention : le solde est modifié "
            . "directement, sans passer par un versement/retrait.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_banque' => ['type' => 'INTEGER'],
                'numero_compte' => ['type' => 'STRING'],
                'nom_banque' => ['type' => 'STRING'],
                'solde' => ['type' => 'NUMBER'],
            ],
            'required' => ['id_banque', 'numero_compte', 'nom_banque', 'solde'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_banque' => 'required|integer',
            'numero_compte' => 'required|string|max:255',
            'nom_banque' => 'required|string|max:255',
            'solde' => 'required|numeric|min:0',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('banques_modification');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $banque = Banque::find($args['id_banque'] ?? null);

        return sprintf(
            "⚠️ Je vais modifier le compte %s : nouveau solde %s FCFA (remplacement direct, pas un mouvement). Confirmez-vous ?",
            $banque?->nom_banque ?? 'inconnu',
            number_format((float) ($args['solde'] ?? 0), 0, ',', ' ')
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->updateBanque($request, (int) $validated['id_banque']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Banque modifiée avec succès.',
            'data' => [],
        ];
    }
}
