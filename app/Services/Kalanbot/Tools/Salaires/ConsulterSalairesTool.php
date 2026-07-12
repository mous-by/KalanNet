<?php

namespace App\Services\Kalanbot\Tools\Salaires;

use App\Http\Controllers\TeacherSalaryController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConsulterSalairesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'salaires_consulter';
    }

    public function module(): string
    {
        return 'salaires';
    }

    public function description(): string
    {
        return "Consulter, pour un mois/année/source donnés, le montant dû, payé et restant à payer par "
            . "enseignant (émargements ou cahier de présence selon la source).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'mois' => ['type' => 'STRING', 'description' => "Mois sur 2 chiffres (ex: '07'). Défaut : mois courant."],
                'annee' => ['type' => 'INTEGER', 'description' => 'Défaut : année courante.'],
                'source' => ['type' => 'STRING', 'description' => 'emargement ou presence (optionnel, déduit sinon).'],
                'id_enseignant' => ['type' => 'INTEGER', 'description' => 'Filtrer sur un enseignant (optionnel).'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'mois' => 'nullable|date_format:m',
            'annee' => 'nullable|integer|min:2000|max:2100',
            'source' => 'nullable|string|in:emargement,presence',
            'id_enseignant' => 'nullable|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'emargement_paiement enseignant', 'presence_paiement enseignant',
            'emargement_paiement_enseignant', 'presence_paiement_enseignant',
            'emargement_etat de payement', 'presence_etat de payement',
            'emargement_etat_de_payement', 'presence_etat_de_payement',
            'paiements_faire',
        ]);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(TeacherSalaryController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $rows = collect($data['salaryRows'] ?? []);

        $salaires = $rows->map(fn (array $row) => [
            'id_enseignant' => $row['enseignant']->id_enseignant,
            'enseignant' => $row['enseignant']->nom_prenom_enseignant,
            'contrat' => $row['contract'],
            'source' => $row['source'],
            'heures_validees' => $row['hours'],
            'montant_du' => $row['amount_due'],
            'paye' => $row['paid'],
            'reste' => $row['remaining'],
            'statut' => $row['status'],
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($salaires) . ' enseignant(s) sur cette période.',
            'data' => ['periode' => $data['filters'] ?? [], 'salaires' => $salaires, 'resume' => $data['summary'] ?? []],
        ];
    }
}
