<?php

namespace App\Services\Kalanbot\Tools\FinancesVersementsRetraits;

use App\Http\Controllers\FinanceController;
use App\Models\Retrait;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ValiderRetraitTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_retrait_valider';
    }

    public function module(): string
    {
        return 'finances_versements_retraits';
    }

    public function description(): string
    {
        return "Valider un retrait bancaire en attente : le montant est déduit du compte bancaire.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_retrait' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_retrait'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_retrait' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('retraits_modification');
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
        $retrait = Retrait::with('banque')->find($args['id_retrait'] ?? null);

        return sprintf(
            "💸 Je vais valider le retrait de %s FCFA sur le compte %s. Confirmez-vous ?",
            number_format((float) ($retrait?->montant_retrait ?? 0), 0, ',', ' '),
            $retrait?->banque?->nom_banque ?? 'inconnu'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(FinanceController::class)->validateRetrait((int) $validated['id_retrait']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Retrait validé avec succès.',
            'data' => [],
        ];
    }
}
