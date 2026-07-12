<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\Paiement;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierPaiementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_paiements_modifier';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Modifier les métadonnées d'un paiement existant (référence, date, motif, classe, année). "
            . "N'affecte pas le montant ni la caisse.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_paiement' => ['type' => 'INTEGER'],
                'reference' => ['type' => 'STRING'],
                'date_paiement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'motif' => ['type' => 'STRING'],
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_paiement', 'reference', 'date_paiement', 'motif', 'id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_paiement' => 'required|integer',
            'reference' => 'required|string|max:100',
            'date_paiement' => 'required|date',
            'motif' => 'required|string|max:50',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('paiements_faire');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $paiement = Paiement::find($args['id_paiement'] ?? null);

        return "Je vais modifier le paiement reçu N° " . ($paiement?->numero_recu ?? '?')
            . " : nouvelle référence « " . ($args['reference'] ?? '') . " », motif « " . ($args['motif'] ?? '')
            . " ». Le montant n'est pas modifié. Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->updatePaiement($request, (int) $validated['id_paiement']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Paiement modifié avec succès.',
            'data' => [],
        ];
    }
}
