<?php

namespace App\Services\Kalanbot\Tools\Configuration;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerTypesNotesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'configuration_types_notes_lister';
    }

    public function module(): string
    {
        return 'configuration';
    }

    public function description(): string
    {
        return "Lister les types de notes/barèmes configurés pour l'école (utilisés lors de la programmation "
            . "d'une évaluation, ex: devoir/composition/NT10).";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('types_notes_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(ConfigurationController::class)->typesNotes($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['typesNotes'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $types = $items->map(fn ($t) => [
            'id_note' => $t->id_note,
            'type' => $t->typeNote,
            'code' => $t->codeNote,
            'bareme' => (float) $t->valeur,
        ])->values()->all();

        return ['success' => true, 'message' => count($types) . ' type(s) de note trouvé(s).', 'data' => ['types_notes' => $types]];
    }
}
