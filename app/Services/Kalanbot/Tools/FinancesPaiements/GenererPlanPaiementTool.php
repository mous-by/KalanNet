<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;
use App\Services\Paiements\EcheanceService;

class GenererPlanPaiementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_paiements_generer_plan';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Générer ou régénérer le plan de paiement (échéancier) d'un élève pour une année scolaire, à "
            . "partir des frais scolaires configurés. Utiliser finances_paiements_contexte_eleve d'abord pour "
            . "vérifier les frais applicables.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'eleve_id' => ['type' => 'INTEGER'],
                'annee_scolaire_id' => ['type' => 'INTEGER'],
                'mode_paiement' => ['type' => 'STRING', 'description' => 'annuel, semestriel, trimestriel, mensuel ou personnalise.'],
            ],
            'required' => ['eleve_id', 'annee_scolaire_id', 'mode_paiement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'eleve_id' => 'required|integer|exists:eleve,id_eleve',
            'annee_scolaire_id' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'mode_paiement' => 'required|in:' . implode(',', EcheanceService::MODES),
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
        $eleve = Eleve::find($args['eleve_id'] ?? null);
        $nom = $eleve ? trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve) : 'cet élève';

        return "Je vais générer le plan de paiement de {$nom} (mode : " . ($args['mode_paiement'] ?? '') . "), "
            . "basé sur les frais scolaires configurés. Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->generatePlanPaiement($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Plan de paiement généré avec succès.',
            'data' => [],
        ];
    }
}
