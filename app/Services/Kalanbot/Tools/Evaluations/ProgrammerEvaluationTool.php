<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ProgrammerEvaluationTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_programmer';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Programmer une nouvelle évaluation pour une classe et une matière (crée l'en-tête de l'évaluation "
            . "et une ligne vide par élève actif de la classe). Ne saisit aucune note : utiliser ensuite "
            . "evaluations_saisir_notes. Nécessite id_classe, id_matiere, id_annee_scolaire et id_note (barème) "
            . "obtenus au préalable si besoin.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'libeller' => ['type' => 'STRING', 'description' => "Intitulé de l'évaluation (ex: 'Composition du 1er trimestre')."],
                'date_evaluation' => ['type' => 'STRING', 'description' => "Date de l'évaluation au format AAAA-MM-JJ."],
                'heure_debut' => ['type' => 'STRING', 'description' => "Heure de début au format HH:MM."],
                'heure_fin' => ['type' => 'STRING', 'description' => "Heure de fin au format HH:MM (après l'heure de début)."],
                'id_classe' => ['type' => 'INTEGER', 'description' => 'Identifiant de la classe.'],
                'id_matiere' => ['type' => 'INTEGER', 'description' => 'Identifiant de la matière.'],
                'id_annee_scolaire' => ['type' => 'INTEGER', 'description' => "Identifiant de l'année scolaire."],
                'id_trimestre' => ['type' => 'INTEGER', 'description' => "Identifiant du trimestre (obligatoire sauf classes évaluées au mois)."],
                'mois' => ['type' => 'INTEGER', 'description' => "Mois 1-12 (obligatoire uniquement pour les classes évaluées au mois, ex: Fondamentale I)."],
                'id_note' => ['type' => 'INTEGER', 'description' => 'Identifiant du type de note/barème à utiliser.'],
            ],
            'required' => ['libeller', 'date_evaluation', 'heure_debut', 'heure_fin', 'id_classe', 'id_matiere', 'id_annee_scolaire', 'id_note'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'libeller' => 'required|string|max:150',
            'date_evaluation' => 'required|date',
            'heure_debut' => 'required',
            'heure_fin' => 'required|after:heure_debut',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'nullable|integer|exists:trimestre,id_trimestre',
            'mois' => 'nullable|integer|between:1,12',
            'id_note' => 'required|integer|exists:note,id_note',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('evaluation_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = Classe::find($args['id_classe'] ?? null);
        $matiere = Matiere::find($args['id_matiere'] ?? null);
        $note = Note::find($args['id_note'] ?? null);
        $periode = !empty($args['mois'])
            ? 'mois ' . $args['mois']
            : (!empty($args['id_trimestre']) ? 'trimestre n°' . $args['id_trimestre'] : 'période non précisée');

        return sprintf(
            "Je vais programmer l'évaluation « %s » en %s pour la classe %s, le %s de %s à %s (%s, barème %s). "
            . "Une ligne sera créée pour chaque élève actif de la classe, sans note. Confirmez-vous ?",
            $args['libeller'] ?? '',
            $matiere?->nom_matiere ?? 'matière inconnue',
            $classe?->nom_classe ?? 'classe inconnue',
            $args['date_evaluation'] ?? '',
            $args['heure_debut'] ?? '',
            $args['heure_fin'] ?? '',
            $periode,
            $note?->codeNote ?? $note?->typeNote ?? '?'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(EvaluationController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Évaluation « {$validated['libeller']} » programmée avec succès. Les notes peuvent maintenant être saisies.",
            'data' => [],
        ];
    }
}
