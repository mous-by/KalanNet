<?php

namespace App\Services\Kalanbot\Tools\Matieres;

use App\Http\Controllers\MatiereController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerMatieresTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'matieres_lister';
    }

    public function module(): string
    {
        return 'matieres';
    }

    public function description(): string
    {
        return "Lister/rechercher les matières disponibles pour l'école (filtrées par ordre d'enseignement autorisé).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'recherche' => ['type' => 'STRING', 'description' => 'Nom de matière (optionnel).'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return ['recherche' => 'nullable|string|max:100'];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('matieres_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(MatiereController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['matieres'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $matieres = $items->map(fn ($m) => [
            'id_matiere' => $m->id_matiere,
            'nom_matiere' => $m->nom_matiere,
            'ordres' => $m->ordres->pluck('ordre_enseignement')->values()->all(),
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($matieres) . ' matière(s) trouvée(s).',
            'data' => ['matieres' => $matieres],
        ];
    }
}
