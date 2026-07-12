<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class HistoriquePaiementsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_historique';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Consulter l'historique des paiements, filtrable par classe, année scolaire et statut.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'classe_id' => ['type' => 'INTEGER'],
                'annee_scolaire_id' => ['type' => 'INTEGER'],
                'statut' => ['type' => 'STRING', 'description' => 'Ex: valide, annule.'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'classe_id' => 'nullable|integer',
            'annee_scolaire_id' => 'nullable|integer',
            'statut' => 'nullable|string|max:20',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('historique_paiement_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->historiquePaiements($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['paiements'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $paiements = $items->map(fn ($p) => [
            'eleve' => trim((optional($p->eleve)->prenom_eleve ?? '') . ' ' . (optional($p->eleve)->nom_eleve ?? '')),
            'montant' => (float) ($p->montant_paye ?? $p->montant ?? 0),
            'date' => $p->date_paiement ?? null,
            'statut' => $p->statut ?? null,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($paiements) . ' paiement(s) dans l\'historique (page courante).',
            'data' => ['paiements' => $paiements],
        ];
    }
}
