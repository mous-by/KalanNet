<?php

namespace App\Services\Kalanbot\Tools\Configuration;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerEcolesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'configuration_ecoles_lister';
    }

    public function module(): string
    {
        return 'configuration';
    }

    public function description(): string
    {
        return "Lister les écoles (référentiel), avec recherche par nom.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('ecoles_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(ConfigurationController::class)->ecoles($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['ecoles'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $ecoles = $items->map(fn ($e) => [
            'id_ecole' => $e->idEcole,
            'nom' => $e->nomEcole,
            'type' => $e->typeEcole,
            'statut' => $e->statut,
        ])->values()->all();

        return ['success' => true, 'message' => count($ecoles) . ' école(s) trouvée(s).', 'data' => ['ecoles' => $ecoles]];
    }
}
