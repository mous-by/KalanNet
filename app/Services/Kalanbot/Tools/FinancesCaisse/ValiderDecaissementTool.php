<?php

namespace App\Services\Kalanbot\Tools\FinancesCaisse;

use App\Http\Controllers\FinanceController;
use App\Models\Decaissement;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ValiderDecaissementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_decaissement_valider';
    }

    public function module(): string
    {
        return 'finances_caisse';
    }

    public function description(): string
    {
        return "Valider une dépense en attente : le montant est déduit de la caisse. Refusé si le solde est insuffisant.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_decaissement' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_decaissement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_decaissement' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->droit === 'Admin' || $user->userHasPermission('decaissements_validation');
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
        $decaissement = Decaissement::find($args['id_decaissement'] ?? null);

        return sprintf(
            "💸 Je vais valider la dépense « %s » de %s FCFA, déduite de la caisse. Confirmez-vous ?",
            $decaissement?->motif_decaissement ?? '?',
            number_format((float) ($decaissement?->montant_decaissement ?? 0), 0, ',', ' ')
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(FinanceController::class)->validateDecaissement((int) $validated['id_decaissement']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Dépense validée et déduite de la caisse.',
            'data' => [],
        ];
    }
}
