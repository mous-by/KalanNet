<?php

namespace App\Services\Kalanbot\Tools\Presences;

use App\Http\Controllers\PresenceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerPresencesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'presences_lister';
    }

    public function module(): string
    {
        return 'presences';
    }

    public function description(): string
    {
        return "Lister/filtrer le cahier de présence (leçons faites, heures), avec un résumé (total, en attente, "
            . "validées, heures).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER'],
                'id_classe' => ['type' => 'INTEGER'],
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
            'valide' => 'nullable|integer|in:0,1',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->droit === 'enseignant' || $user->userHasPermission('presence_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(PresenceController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['presences'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $presences = $items->map(fn ($p) => [
            'id_presence' => $p->id_presence,
            'enseignant' => optional($p->enseignant)->nom_prenom_enseignant,
            'classe' => optional($p->classe)->nom_classe,
            'date' => $p->date_presence,
            'nombre_heure' => $p->nombre_heure,
            'nombre_lecons' => $p->lecons->count(),
            'valide' => (bool) $p->valide,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($presences) . ' présence(s) trouvée(s).',
            'data' => ['presences' => $presences, 'resume' => $data['presenceSummary'] ?? []],
        ];
    }
}
