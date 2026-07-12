<?php

namespace App\Services\Kalanbot\Tools\FinancesCaisse;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerDepensesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_depenses_lister';
    }

    public function module(): string
    {
        return 'finances_caisse';
    }

    public function description(): string
    {
        return "Lister les dépenses (décaissements) de la caisse active, validées et en attente.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('decaissements_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(FinanceController::class)->depenses());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $depenses = collect($data['depenses'] ?? [])->map(fn ($d) => [
            'id_decaissement' => $d->id_decaissement,
            'motif' => $d->motif_decaissement,
            'montant' => (float) $d->montant_decaissement,
            'date' => $d->date_decaissement,
            'valide' => (bool) $d->valide,
            'cree_par' => optional($d->utilisateur)->nomPrenom,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($depenses) . ' dépense(s) trouvée(s).',
            'data' => ['depenses' => $depenses],
        ];
    }
}
