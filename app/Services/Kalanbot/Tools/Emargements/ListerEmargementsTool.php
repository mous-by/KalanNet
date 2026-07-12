<?php

namespace App\Services\Kalanbot\Tools\Emargements;

use App\Http\Controllers\EmargementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerEmargementsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emargements_lister';
    }

    public function module(): string
    {
        return 'emargements';
    }

    public function description(): string
    {
        return "Lister/filtrer les émargements (heures de cours déclarées par les enseignants), avec un résumé "
            . "(total, en attente de validation, heures validées).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER'],
                'id_classe' => ['type' => 'INTEGER'],
                'id_matiere' => ['type' => 'INTEGER'],
                'valide' => ['type' => 'INTEGER', 'description' => '0 = en attente, 1 = validé.'],
                'date_debut' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'date_fin' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_enseignant' => 'nullable|integer',
            'id_classe' => 'nullable|integer',
            'id_matiere' => 'nullable|integer',
            'valide' => 'nullable|integer|in:0,1',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->droit === 'enseignant' || $user->userHasPermission('emargement_faire');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(EmargementController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['emargements'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $emargements = $items->map(fn ($e) => [
            'id_emargement' => $e->id_emargement,
            'enseignant' => optional($e->enseignant)->nom_prenom_enseignant,
            'classe' => optional($e->classe)->nom_classe,
            'matiere' => optional($e->matiere)->nom_matiere,
            'date' => $e->date_emargement,
            'nombre_heure' => $e->nombre_heure,
            'valide' => (bool) $e->valide,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($emargements) . ' émargement(s) trouvé(s).',
            'data' => ['emargements' => $emargements, 'resume' => $data['emargementSummary'] ?? []],
        ];
    }
}
