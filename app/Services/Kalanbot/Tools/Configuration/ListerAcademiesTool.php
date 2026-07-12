<?php

namespace App\Services\Kalanbot\Tools\Configuration;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerAcademiesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'configuration_academies_lister';
    }

    public function module(): string
    {
        return 'configuration';
    }

    public function description(): string
    {
        return "Lister les académies (référentiel), avec recherche par nom/code.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('academies_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(ConfigurationController::class)->academies($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['academies'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $academies = $items->map(fn ($a) => [
            'id_academie' => $a->id_academie,
            'nom' => $a->nom_academie,
            'code' => $a->code_academie,
            'localite' => $a->localite_academie,
        ])->values()->all();

        return ['success' => true, 'message' => count($academies) . ' académie(s) trouvée(s).', 'data' => ['academies' => $academies]];
    }
}
