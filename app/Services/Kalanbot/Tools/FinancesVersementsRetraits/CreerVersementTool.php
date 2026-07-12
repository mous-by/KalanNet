<?php

namespace App\Services\Kalanbot\Tools\FinancesVersementsRetraits;

use App\Http\Controllers\FinanceController;
use App\Models\Banque;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerVersementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_versement_creer';
    }

    public function module(): string
    {
        return 'finances_versements_retraits';
    }

    public function description(): string
    {
        return "Effectuer un versement : transfère des fonds de la caisse active vers un compte bancaire. "
            . "Refusé si le solde de caisse est insuffisant.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_banque' => ['type' => 'INTEGER'],
                'montant_versement' => ['type' => 'NUMBER'],
                'motif_versement' => ['type' => 'STRING'],
            ],
            'required' => ['id_banque', 'montant_versement', 'motif_versement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_banque' => 'required|integer|exists:banques,id_banques',
            'montant_versement' => 'required|numeric|min:1',
            'motif_versement' => 'required|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('versements_creation');
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
            "💰 Je vais verser %s FCFA de la caisse vers le compte %s (motif : %s). Confirmez-vous ?",
            number_format((float) ($args['montant_versement'] ?? 0), 0, ',', ' '),
            $banque?->nom_banque ?? 'inconnu',
            $args['motif_versement'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeVersement($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Versement effectué avec succès.',
            'data' => [],
        ];
    }
}
