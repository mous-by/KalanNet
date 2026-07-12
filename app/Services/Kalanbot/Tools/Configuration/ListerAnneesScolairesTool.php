<?php

namespace App\Services\Kalanbot\Tools\Configuration;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerAnneesScolairesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'configuration_annees_scolaires_lister';
    }

    public function module(): string
    {
        return 'configuration';
    }

    public function description(): string
    {
        return "Lister les années scolaires disponibles, avec l'année en cours identifiée.";
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('annees_scolaires_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(ConfigurationController::class)->annees($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['annees'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();
        $anneeEnCours = $data['anneeEnCours'] ?? null;

        $annees = $items->map(fn ($a) => [
            'id_annee' => $a->id_anneeScolaire,
            'annee' => $a->annee,
            'date_debut' => $a->date_debut,
            'date_fin' => $a->date_fin,
            'en_cours' => $anneeEnCours && $anneeEnCours->id_anneeScolaire === $a->id_anneeScolaire,
        ])->values()->all();

        return ['success' => true, 'message' => count($annees) . ' année(s) scolaire(s) trouvée(s).', 'data' => ['annees' => $annees]];
    }
}
