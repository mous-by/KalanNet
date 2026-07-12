<?php

namespace App\Services\Kalanbot\Tools\AppelsEpreuves;

use App\Http\Controllers\AppelEpreuveController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerAppelsEpreuvesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'appels_epreuves_lister';
    }

    public function module(): string
    {
        return 'appels_epreuves';
    }

    public function description(): string
    {
        return "Lister/rechercher les appels d'épreuve (présence des élèves à une épreuve/composition). "
            . "Au moins un filtre est requis (sinon la liste est vide).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_matiere' => ['type' => 'INTEGER'],
                'id_annee_scolaire' => ['type' => 'INTEGER'],
                'id_trimestre' => ['type' => 'INTEGER'],
                'nom_eleve' => ['type' => 'STRING'],
                'date_debut' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'date_fin' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'nullable|integer',
            'id_matiere' => 'nullable|integer',
            'id_annee_scolaire' => 'nullable|integer',
            'id_trimestre' => 'nullable|integer',
            'nom_eleve' => 'nullable|string|max:100',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['controle_apercu', 'controle_creation', 'controle_création']);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest($validated);

        $outcome = $this->callController(fn () => app(AppelEpreuveController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['appels'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $appels = $items->map(fn ($a) => [
            'id_controle_eleve' => $a->id_controle_eleve,
            'eleve' => trim(optional($a->eleve)->prenom_eleve . ' ' . optional($a->eleve)->nom_eleve),
            'classe' => optional($a->classe)->nom_classe,
            'matiere' => optional($a->matiere)->nom_matiere,
            'date' => $a->date,
            'libelle' => $a->libelle,
            'statut' => optional($a->statutControle)->type_controle,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($appels) . " appel(s) d'épreuve trouvé(s).",
            'data' => ['appels' => $appels],
        ];
    }
}
