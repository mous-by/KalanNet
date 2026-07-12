<?php

namespace App\Services\Kalanbot\Tools\Enseignants;

use App\Http\Controllers\EnseignantController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class FicheEnseignantTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'enseignants_fiche';
    }

    public function module(): string
    {
        return 'enseignants';
    }

    public function description(): string
    {
        return "Afficher la fiche d'un enseignant : classes/matières affectées, statistiques d'émargements et "
            . "de présences, progression du programme, situation salariale du mois en cours.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER', 'description' => "Identifiant de l'enseignant."],
            ],
            'required' => ['id_enseignant'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_enseignant' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin'
            || in_array($user->droit, ['DAE', 'DCAP', 'Admin', 'Gestionnaire', 'enseignant'], true)
            || $user->userHasPermission('enseignants_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EnseignantController::class)->show((int) $validated['id_enseignant']));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $enseignant = $data['enseignant'] ?? null;
        $bulletin = $data['monthlyBulletin'] ?? [];

        return [
            'success' => true,
            'message' => 'Fiche récupérée.',
            'data' => [
                'enseignant' => [
                    'id_enseignant' => $enseignant?->id_enseignant,
                    'nom' => $enseignant?->nom_prenom_enseignant,
                    'type_contrat' => $enseignant?->type_contrat_enseignant,
                    'archive' => (bool) ($enseignant?->is_deleted ?? false),
                ],
                'emargements' => $data['emargementStats'] ?? [],
                'presences' => $data['presenceStats'] ?? [],
                'salaire_mois_courant' => [
                    'periode' => $bulletin['label'] ?? null,
                    'montant_du' => $bulletin['amount_due'] ?? 0,
                    'paye' => $bulletin['paid'] ?? 0,
                    'reste' => $bulletin['remaining'] ?? 0,
                    'statut' => $bulletin['status'] ?? null,
                ],
            ],
        ];
    }
}
