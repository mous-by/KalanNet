<?php

namespace App\Services\Kalanbot\Tools\FinancesVersementsRetraits;

use App\Http\Controllers\FinanceController;
use App\Models\Banque;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerRetraitTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_retrait_creer';
    }

    public function module(): string
    {
        return 'finances_versements_retraits';
    }

    public function description(): string
    {
        return "Effectuer un retrait bancaire. Déduit immédiatement le compte si l'utilisateur est Admin, "
            . "sinon soumis en attente de validation.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_banque' => ['type' => 'INTEGER'],
                'date_retrait' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'montant_retrait' => ['type' => 'NUMBER'],
                'motif_retrait' => ['type' => 'STRING'],
            ],
            'required' => ['id_banque', 'date_retrait', 'montant_retrait', 'motif_retrait'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_banque' => 'required|integer|exists:banques,id_banques',
            'date_retrait' => 'required|date',
            'montant_retrait' => 'required|numeric|min:1',
            'motif_retrait' => 'required|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('retraits_creation');
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
        $banque = Banque::find($args['id_banque'] ?? null);

        return sprintf(
            "💸 Je vais effectuer un retrait de %s FCFA sur le compte %s (motif : %s). Confirmez-vous ?",
            number_format((float) ($args['montant_retrait'] ?? 0), 0, ',', ' '),
            $banque?->nom_banque ?? 'inconnu',
            $args['motif_retrait'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeRetrait($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Retrait effectué avec succès.',
            'data' => [],
        ];
    }
}
