<?php

namespace App\Services\Kalanbot\Tools\FinancesCaisse;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerDecaissementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_decaissement_creer';
    }

    public function module(): string
    {
        return 'finances_caisse';
    }

    public function description(): string
    {
        return "Soumettre une dépense (décaissement). Validée et déduite immédiatement de la caisse si "
            . "l'utilisateur a le droit de validation, sinon mise en attente de validation.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_caisse' => ['type' => 'INTEGER'],
                'id_annee_scolaire' => ['type' => 'INTEGER'],
                'date_decaissement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'motif_decaissement' => ['type' => 'STRING'],
                'montant_decaissement' => ['type' => 'NUMBER'],
            ],
            'required' => ['id_caisse', 'id_annee_scolaire', 'date_decaissement', 'motif_decaissement', 'montant_decaissement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_caisse' => 'required|integer|exists:caisse,id_caisse',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_decaissement' => 'required|date',
            'motif_decaissement' => 'required|string|max:255',
            'montant_decaissement' => 'required|numeric|min:1',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('decaissements_creation');
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
        return sprintf(
            "💸 Je vais soumettre une dépense de %s FCFA (%s). Si vous avez le droit de validation, elle sera "
            . "déduite immédiatement de la caisse ; sinon elle attendra une validation. Confirmez-vous ?",
            number_format((float) ($args['montant_decaissement'] ?? 0), 0, ',', ' '),
            $args['motif_decaissement'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeDecaissement($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Dépense soumise avec succès.',
            'data' => [],
        ];
    }
}
