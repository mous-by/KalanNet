<?php

namespace App\Services\Kalanbot\Tools\FinancesVersementsRetraits;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerRetraitsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_retraits_lister';
    }

    public function module(): string
    {
        return 'finances_versements_retraits';
    }

    public function description(): string
    {
        return "Lister les retraits bancaires (validés et en attente).";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('retraits_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(FinanceController::class)->retraits());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $retraits = collect($data['retraits'] ?? [])->map(fn ($r) => [
            'id_retrait' => $r->id_retrait,
            'banque' => optional($r->banque)->nom_banque,
            'montant' => (float) $r->montant_retrait,
            'date' => $r->date_retrait,
            'motif' => $r->motif_retrait,
            'valide' => (bool) $r->valide,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($retraits) . ' retrait(s) trouvé(s).',
            'data' => ['retraits' => $retraits],
        ];
    }
}
