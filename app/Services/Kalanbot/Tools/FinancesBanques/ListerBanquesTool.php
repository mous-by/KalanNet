<?php

namespace App\Services\Kalanbot\Tools\FinancesBanques;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerBanquesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_banques_lister';
    }

    public function module(): string
    {
        return 'finances_banques';
    }

    public function description(): string
    {
        return "Lister les comptes bancaires de l'école avec leur solde.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('banques_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(FinanceController::class)->banques());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $banques = collect($data['banques'] ?? [])->map(fn ($b) => [
            'id_banque' => $b->id_banques,
            'nom_banque' => $b->nom_banque,
            'numero_compte' => $b->numero_compte,
            'solde' => (float) $b->solde,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($banques) . ' compte(s) bancaire(s).',
            'data' => ['banques' => $banques],
        ];
    }
}
