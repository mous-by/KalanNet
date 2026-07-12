<?php

namespace App\Services\Kalanbot\Tools\FinancesVersementsRetraits;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerVersementsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_versements_lister';
    }

    public function module(): string
    {
        return 'finances_versements_retraits';
    }

    public function description(): string
    {
        return "Lister les versements effectués de la caisse vers les comptes bancaires.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('versements_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(FinanceController::class)->versements());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $versements = collect($data['versements'] ?? [])->map(fn ($v) => [
            'id_versement' => $v->id_versement,
            'banque' => optional($v->banque)->nom_banque,
            'montant' => (float) $v->montant_versement,
            'date' => $v->date_versement,
            'motif' => $v->motif_versement,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($versements) . ' versement(s) trouvé(s).',
            'data' => ['versements' => $versements],
        ];
    }
}
