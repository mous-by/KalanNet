<?php

namespace App\Services\Kalanbot\Tools\Configuration;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerClassesOfficiellesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'configuration_classes_officielles_lister';
    }

    public function module(): string
    {
        return 'configuration';
    }

    public function description(): string
    {
        return "Lister les classes officielles (référentiel national, ex: 7ème année), utilisées pour "
            . "l'association des classes de l'école et les programmes officiels.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('classes_officielles_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(ConfigurationController::class)->classesOfficielles($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['classesOfficielles'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $classes = $items->map(fn ($c) => [
            'id_classe_officielle' => $c->id_classe_officielle,
            'nom' => $c->nom_classe_officielle,
            'ordre_enseignement' => $c->ordre_enseignement,
        ])->values()->all();

        return ['success' => true, 'message' => count($classes) . ' classe(s) officielle(s) trouvée(s).', 'data' => ['classes_officielles' => $classes]];
    }
}
