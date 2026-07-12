<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class TableauBordTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_tableau_bord';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Vue d'ensemble financière de l'école : solde de la caisse active, 10 derniers paiements, total "
            . "des recettes et dépenses validées.";
    }

    public function parametersSchema(): array
    {
        return ['type' => 'OBJECT', 'properties' => []];
    }

    public function validationRules(): array
    {
        return [];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['paiements_apercu', 'caisses_apercu']);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(FinanceController::class)->index());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $caisse = $data['caisse'] ?? null;

        return [
            'success' => true,
            'message' => 'Tableau de bord financier récupéré.',
            'data' => [
                'caisse' => $caisse ? [
                    'libelle' => $caisse->libelle,
                    'solde' => (float) $caisse->montant_net,
                ] : null,
                'total_recettes' => (float) ($data['totalRecettes'] ?? 0),
                'total_depenses' => (float) ($data['totalDepenses'] ?? 0),
                'derniers_paiements' => collect($data['recentPaiements'] ?? [])->map(fn ($p) => [
                    'id_paiement' => $p->id_paiement,
                    'eleve' => trim(optional($p->eleve)->prenom_eleve . ' ' . optional($p->eleve)->nom_eleve),
                    'montant' => (float) ($p->montant_paye ?? $p->montant),
                    'date' => $p->date_paiement,
                    'statut' => $p->statut,
                ])->values()->all(),
            ],
        ];
    }
}
