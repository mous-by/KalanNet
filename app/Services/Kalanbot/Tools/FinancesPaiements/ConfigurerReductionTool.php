<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConfigurerReductionTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_reduction_configurer';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Configurer une règle de réduction de frais selon le statut de paiement d'un élève (ex: boursier, "
            . "orphelin). Ne concerne pas les écoles publiques.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'annee_scolaire_id' => ['type' => 'INTEGER'],
                'statut_paiement' => ['type' => 'STRING', 'description' => 'Ex: boursier, subventionne, gratuit.'],
                'type_reduction' => ['type' => 'STRING', 'description' => 'Ex: pourcentage, montant_fixe.'],
                'valeur' => ['type' => 'NUMBER'],
                'payeur_libelle' => ['type' => 'STRING'],
            ],
            'required' => ['statut_paiement', 'type_reduction', 'valeur'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'annee_scolaire_id' => 'nullable|integer|exists:anneescolaire,id_anneeScolaire',
            'statut_paiement' => 'required|string|max:40',
            'type_reduction' => 'required|string|max:40',
            'valeur' => 'required|numeric|min:0',
            'payeur_libelle' => 'nullable|string|max:120',
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
        return sprintf(
            "Je vais configurer une réduction « %s » de %s pour le statut « %s ». Confirmez-vous ?",
            $args['type_reduction'] ?? '',
            $args['valeur'] ?? '',
            $args['statut_paiement'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeReductionConfig($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Règle de réduction enregistrée.',
            'data' => [],
        ];
    }
}
