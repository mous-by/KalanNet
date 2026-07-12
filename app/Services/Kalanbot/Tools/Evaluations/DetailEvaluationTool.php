<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class DetailEvaluationTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_detail';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Afficher le détail d'une évaluation : élèves concernés, notes déjà saisies (id_ligneEvaluation "
            . "nécessaire pour saisir/modifier une note) et statut de validation.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_evaluation' => ['type' => 'INTEGER', 'description' => "Identifiant de l'évaluation."],
            ],
            'required' => ['id_evaluation'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_evaluation' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('evaluation_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EvaluationController::class)->show((int) $validated['id_evaluation']));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $details = collect($data['details'] ?? []);

        $lignes = $details->map(fn ($ligne) => [
            'id_ligneEvaluation' => $ligne->id_ligneEvaluation,
            'eleve' => trim(optional($ligne->eleve)->prenom_eleve . ' ' . optional($ligne->eleve)->nom_eleve),
            'matricule' => optional($ligne->eleve)->matricule,
            'note' => $ligne->note,
            'bareme' => optional($ligne->noteType)->valeur ?? 20,
            'statut_validation' => $ligne->validation_status,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($lignes) . ' élève(s) sur cette évaluation.',
            'data' => [
                'evaluation' => [
                    'id_evaluation' => optional($data['evaluation'] ?? null)->id_evaluation,
                    'libelle' => optional($data['evaluation'] ?? null)->libeller,
                    'date_evaluation' => optional($data['evaluation'] ?? null)->date_evaluation,
                    'classe' => optional($data['classe'] ?? null)->nom_classe,
                    'matiere' => optional($data['matiere'] ?? null)->nom_matiere,
                ],
                'notes' => $lignes,
            ],
        ];
    }
}
