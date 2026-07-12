<?php

namespace App\Services\Kalanbot\Tools\Abonnements;

use App\Http\Controllers\AbonnementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class DetailPaiementAbonnementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'abonnements_paiement_detail';
    }

    public function module(): string
    {
        return 'abonnements';
    }

    public function description(): string
    {
        return "Afficher le détail d'un paiement d'abonnement par sa référence.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'reference' => ['type' => 'STRING'],
            ],
            'required' => ['reference'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'reference' => 'required|string|max:100',
        ];
    }

    public function authorize(User $user): bool
    {
        return in_array($user->droit, ['SupAdmin', 'Admin', 'Gestionnaire'], true)
            || $user->userHasAnyPermission(['abonnements_apercu', 'abonnements_paiement']);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(AbonnementController::class)->paiement($validated['reference']));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paiement = $data['paiement'] ?? null;

        return [
            'success' => true,
            'message' => 'Paiement récupéré.',
            'data' => $paiement ? [
                'reference' => $paiement->reference,
                'offre' => optional($paiement->offre)->nom,
                'montant' => (float) $paiement->montant,
                'devise' => $paiement->devise,
                'statut' => $paiement->statut,
                'mode_paiement' => $paiement->mode_paiement,
            ] : [],
        ];
    }
}
