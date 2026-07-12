<?php

namespace App\Services\Kalanbot\Tools\Eleves;

use App\Http\Controllers\EleveController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class FicheEleveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'eleves_fiche';
    }

    public function module(): string
    {
        return 'eleves';
    }

    public function description(): string
    {
        return "Afficher la fiche complète d'un élève : classe, situation financière (montant dû, payé, reste), "
            . "échéances en retard, moyennes récentes et alertes de dossier.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_eleve' => ['type' => 'INTEGER', 'description' => "Identifiant de l'élève (obtenu via eleves_rechercher si inconnu)."],
            ],
            'required' => ['id_eleve'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_eleve' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin'
            || $user->droit === 'parent'
            || $user->userHasAnyPermission(['eleves_apercu', 'eleves_dossier', 'dossiers_eleves_apercu']);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EleveController::class)->show((int) $validated['id_eleve']));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $eleve = $data['eleve'] ?? null;
        $summary = $data['paymentSummary'] ?? [];
        $echeances = collect($data['echeancesResume'] ?? []);
        $alerts = collect($data['dossierAlerts'] ?? [])->pluck('text')->values()->all();

        return [
            'success' => true,
            'message' => 'Fiche récupérée.',
            'data' => [
                'eleve' => [
                    'id_eleve' => $eleve?->id_eleve,
                    'nom_complet' => trim(($eleve?->prenom_eleve ?? '') . ' ' . ($eleve?->nom_eleve ?? '')),
                    'matricule' => $eleve?->matricule,
                    'classe' => optional($eleve?->classe)->nom_classe,
                    'statut_dossier' => match ((int) ($eleve?->etat_dossier ?? 0)) {
                        1 => 'transféré',
                        2 => 'retiré',
                        default => 'actif',
                    },
                    'statut_paiement' => $eleve?->statut_paiement,
                ],
                'finances' => [
                    'montant_du' => $summary['montant_final'] ?? 0,
                    'montant_paye' => $summary['montant_paye'] ?? 0,
                    'reste_a_payer' => $summary['reste'] ?? 0,
                    'statut' => $summary['statut'] ?? null,
                    'echeances_en_retard' => $echeances->where('statut', 'retard')->count(),
                ],
                'alertes' => $alerts,
            ],
        ];
    }
}
