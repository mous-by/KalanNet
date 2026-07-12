<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class EncaisserSubventionEtatTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_subventions_etat_encaisser';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Enregistrer un paiement global de subvention État, réparti automatiquement (FIFO) sur les "
            . "échéances subventionnées ouvertes. Utiliser finances_subventions_etat_consulter d'abord.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'annee_scolaire_id' => ['type' => 'INTEGER'],
                'classe_id' => ['type' => 'INTEGER'],
                'date_paiement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'montant_recu' => ['type' => 'NUMBER'],
                'reference_etat' => ['type' => 'STRING'],
                'observation' => ['type' => 'STRING'],
            ],
            'required' => ['annee_scolaire_id', 'date_paiement', 'montant_recu'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'annee_scolaire_id' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'classe_id' => 'nullable|integer|exists:classe,id_classe',
            'date_paiement' => 'required|date',
            'montant_recu' => 'required|numeric|min:1',
            'reference_etat' => 'nullable|string|max:100',
            'observation' => 'nullable|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['subventions_etat_encaisser', 'paiements_faire']);
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
            "💰 Je vais encaisser %s FCFA de subvention État, répartis automatiquement sur les échéances "
            . "subventionnées ouvertes, créditant la caisse active. Confirmez-vous ?",
            number_format((float) ($args['montant_recu'] ?? 0), 0, ',', ' ')
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeSubventionEtat($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Subvention État encaissée avec succès.',
            'data' => [],
        ];
    }
}
