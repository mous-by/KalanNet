<?php

namespace App\Services\Kalanbot\Tools\Classes;

use App\Http\Controllers\ClasseController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerClassesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'classes_lister';
    }

    public function module(): string
    {
        return 'classes';
    }

    public function description(): string
    {
        return "Lister les classes de l'école avec leur effectif.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('classes_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(ClasseController::class)->index());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $classes = collect($data['classes'] ?? [])->map(fn ($c) => [
            'id_classe' => $c->id_classe,
            'nom_classe' => $c->nom_classe,
            'ordre_enseignement' => $c->ordreEnseignement,
            'effectif' => $c->eleves_count ?? null,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($classes) . ' classe(s) trouvée(s).',
            'data' => ['classes' => $classes],
        ];
    }
}
