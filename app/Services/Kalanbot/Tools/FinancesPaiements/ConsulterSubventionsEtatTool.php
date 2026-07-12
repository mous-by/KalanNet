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
        return "Consulter les échéances subventionnées par l'État restant dues, pour une année scolaire "
            . "(et éventuellement une classe).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'annee_scolaire_id' => ['type' => 'INTEGER'],
                'classe_id' => ['type' => 'INTEGER'],
            ],
            'required' => ['annee_scolaire_id'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'annee_scolaire_id' => 'required|integer',
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
            'eleve' => trim((optional($row->plan->eleve)->prenom_eleve ?? '') . ' ' . (optional($row->plan->eleve)->nom_eleve ?? '')),
            'classe' => optional($row->plan->classe)->nom_classe,
            'echeance' => $row->echeance->libelle,
            'montant_prevu' => (float) $row->echeance->montant_prevu,
            'deja_paye' => (float) $row->deja_paye,
            'reste' => (float) $row->reste,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($rows) . ' échéance(s) subventionnée(s) restant due(s).',
            'data' => ['echeances' => $rows],
        ];
    }
}
