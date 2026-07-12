<?php

namespace App\Services\Kalanbot\Tools\Bulletins;

use App\Http\Controllers\BulletinController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ClassesDisponiblesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'bulletins_classes_disponibles';
    }

    public function module(): string
    {
        return 'bulletins';
    }

    public function description(): string
    {
        return "Lister les classes éligibles à la génération de bulletins, avec leur effectif actif.";
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
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'bulletins_apercu', 'bulletins_generation', 'bulletins_génération',
            'bulletins_pdf', 'bulletins_impression', 'bulletins_publication',
            'generer_bulletins', 'générer_bulletins', 'bulletins_acces_bulletin',
        ]);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(BulletinController::class)->classes());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $classes = collect($data['classes'] ?? [])->map(fn ($c) => [
            'id_classe' => $c->id_classe,
            'nom_classe' => $c->nom_classe,
            'effectif' => $c->eleves_count ?? null,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($classes) . ' classe(s) disponible(s).',
            'data' => ['classes' => $classes],
        ];
    }
}
