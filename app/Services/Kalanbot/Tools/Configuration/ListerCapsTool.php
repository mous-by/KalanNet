<?php

namespace App\Services\Kalanbot\Tools\Configuration;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerCapsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'configuration_caps_lister';
    }

    public function module(): string
    {
        return 'configuration';
    }

    public function description(): string
    {
        return "Lister les CAP (référentiel), avec recherche par nom/code.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('dcap_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(ConfigurationController::class)->caps($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['caps'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $caps = $items->map(fn ($c) => [
            'id_cap' => $c->id_cap,
            'nom' => $c->nom_cap,
            'code' => $c->code_cap,
            'academie' => optional($c->academie)->nom_academie,
        ])->values()->all();

        return ['success' => true, 'message' => count($caps) . ' CAP trouvé(s).', 'data' => ['caps' => $caps]];
    }
}
