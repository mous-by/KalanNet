<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ContexteEleveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_paiements_contexte_eleve';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Consulter la situation financière d'un élève : frais applicables, plan de paiement, échéances et "
            . "reste à payer par échéance (fournit les identifiants d'échéance nécessaires pour "
            . "finances_paiements_enregistrer). À utiliser avant tout paiement.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_eleve' => ['type' => 'INTEGER'],
                'annee_scolaire_id' => ['type' => 'INTEGER', 'description' => 'Optionnel, année courante par défaut.'],
            ],
            'required' => ['id_eleve'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_eleve' => 'required|integer',
            'annee_scolaire_id' => 'nullable|integer|exists:anneescolaire,id_anneeScolaire',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('paiements_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['annee_scolaire_id' => $validated['annee_scolaire_id'] ?? null]);

        $outcome = $this->callController(fn () => app(FinanceController::class)->contexteEleve($request, (int) $validated['id_eleve']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Situation financière récupérée.',
            'data' => $this->extractJsonData($outcome['result']),
        ];
    }
}
