<?php

namespace App\Services\Kalanbot\Tools\FinancesCaisse;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConsulterCaisseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_caisse_consulter';
    }

    public function module(): string
    {
        return 'finances_caisse';
    }

    public function description(): string
    {
        return "Consulter le registre de caisse de l'école : solde et derniers mouvements (encaissements et décaissements).";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('caisses_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(FinanceController::class)->showCaisse());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $caisse = $data['caisse'] ?? null;
        $mouvements = collect($data['mouvements'] ?? [])->take(15)->map(fn ($m) => [
            'type' => $m->type,
            'date' => $m->date,
            'montant' => (float) $m->montant,
            'motif' => $m->motif,
        ])->values()->all();

        return [
            'success' => true,
            'message' => $caisse ? 'Caisse trouvée.' : "Aucune caisse n'existe encore pour cette école.",
            'data' => [
                'caisse' => $caisse ? ['libelle' => $caisse->libelle, 'solde' => (float) $caisse->montant_net] : null,
                'derniers_mouvements' => $mouvements,
            ],
        ];
    }
}
