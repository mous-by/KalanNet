<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerPaiementsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_paiements_lister';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Lister les paiements de scolarité enregistrés récemment.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('paiements_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(FinanceController::class)->listePaiements($this->makeGetRequest()));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['paiements'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $paiements = $items->map(fn ($p) => [
            'id_paiement' => $p->id_paiement,
            'eleve' => trim(optional($p->eleve)->prenom_eleve . ' ' . optional($p->eleve)->nom_eleve),
            'classe' => optional($p->classe)->nom_classe,
            'motif' => $p->motif,
            'montant' => (float) ($p->montant_paye ?? $p->montant),
            'date' => $p->date_paiement,
            'reference' => $p->reference,
            'numero_recu' => $p->numero_recu,
            'statut' => $p->statut,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($paiements) . ' paiement(s) trouvé(s) (page courante).',
            'data' => ['paiements' => $paiements],
        ];
    }
}
