<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConsulterSubventionsEtatTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_subventions_etat_consulter';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Consulter les frais des élèves subventionnés restant dus par l'État, pour une année scolaire "
            . "(ou toutes les années si non précisée, l'État payant souvent en retard) et éventuellement une classe.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'annee_scolaire_id' => ['type' => 'INTEGER'],
                'classe_id' => ['type' => 'INTEGER'],
            ],
            'required' => [],
        ];
    }

    public function validationRules(): array
    {
        return [
            'annee_scolaire_id' => 'nullable|integer',
            'classe_id' => 'nullable|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['subventions_etat_apercu', 'paiements_apercu']);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->subventionsEtat($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $rows = collect($data['subventionRows'] ?? [])->map(fn ($row) => [
            'eleve' => trim(($row->eleve?->prenom_eleve ?? '') . ' ' . ($row->eleve?->nom_eleve ?? '')),
            'annee' => $row->annee?->annee,
            'classe' => $row->classe?->nom_classe,
            'formule' => $row->libelle,
            'montant_prevu' => (float) $row->montant_prevu,
            'deja_paye' => (float) $row->deja_paye,
            'reste' => (float) $row->reste,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($rows) . ' élève(s) subventionné(s) dont les frais restent dus par l\'État.',
            'data' => ['eleves' => $rows],
        ];
    }
}
