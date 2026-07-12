<?php

namespace App\Services\Kalanbot\Tools\Configuration;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerStatusControlesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'configuration_status_controles_lister';
    }

    public function module(): string
    {
        return 'configuration';
    }

    public function description(): string
    {
        return "Lister les statuts de contrôle configurés (ex: Présent, Absence, Retard), utilisés pour les "
            . "appels d'épreuve.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'recherche' => ['type' => 'STRING'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return ['recherche' => 'nullable|string|max:100'];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('status_controles_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(ConfigurationController::class)->statusControles($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['statusControles'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $statuts = $items->map(fn ($s) => [
            'id_controle' => $s->id_controle,
            'type' => $s->type_controle,
            'alerte' => $s->alertControle,
            'penalite_conduite' => (float) $s->penalite_conduite,
        ])->values()->all();

        return ['success' => true, 'message' => count($statuts) . ' statut(s) de contrôle trouvé(s).', 'data' => ['statuts' => $statuts]];
    }
}
