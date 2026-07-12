<?php

namespace App\Services\Kalanbot\Tools\Evaluations;

use App\Http\Controllers\EvaluationController;
use App\Models\LigneEvaluation;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SaisirNotesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'evaluations_saisir_notes';
    }

    public function module(): string
    {
        return 'evaluations';
    }

    public function description(): string
    {
        return "Saisir ou modifier les notes d'une ou plusieurs élèves pour une évaluation déjà programmée. "
            . "Utiliser d'abord evaluations_detail pour obtenir les id_ligneEvaluation correspondant à chaque élève.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_evaluation' => ['type' => 'INTEGER', 'description' => "Identifiant de l'évaluation."],
                'notes' => [
                    'type' => 'ARRAY',
                    'description' => 'Liste des notes à enregistrer, une entrée par élève.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_ligneEvaluation' => ['type' => 'INTEGER', 'description' => "Identifiant de la ligne élève/évaluation (obtenu via evaluations_detail)."],
                            'note' => ['type' => 'NUMBER', 'description' => 'Note obtenue par cet élève (laisser vide/null si absent ou non noté).'],
                        ],
                        'required' => ['id_ligneEvaluation'],
                    ],
                ],
            ],
            'required' => ['id_evaluation', 'notes'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_evaluation' => 'required|integer',
            'notes' => 'required|array|min:1',
            'notes.*.id_ligneEvaluation' => 'required|integer',
            'notes.*.note' => 'nullable|numeric|min:0',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('evaluation_modification');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $notes = collect($args['notes'] ?? []);
        $lignes = LigneEvaluation::with('eleve')
            ->whereIn('id_ligneEvaluation', $notes->pluck('id_ligneEvaluation'))
            ->get()
            ->keyBy('id_ligneEvaluation');

        $resume = $notes->map(function ($entry) use ($lignes) {
            $ligne = $lignes->get($entry['id_ligneEvaluation'] ?? null);
            $nom = $ligne ? trim($ligne->eleve?->prenom_eleve . ' ' . $ligne->eleve?->nom_eleve) : 'élève inconnu';
            $note = $entry['note'] ?? null;

            return $nom . ' : ' . ($note === null ? 'aucune note' : $note);
        })->implode(', ');

        return "Je vais enregistrer les notes suivantes : {$resume}. Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $notes = collect($validated['notes']);

        $request = $this->makeRequest([
            'id_ligneEvaluation' => $notes->pluck('id_ligneEvaluation')->all(),
            'note' => $notes->pluck('note')->all(),
        ]);

        $outcome = $this->callController(
            fn () => app(EvaluationController::class)->update($request, (int) $validated['id_evaluation'])
        );
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => $notes->count() . ' note(s) enregistrée(s) avec succès.',
            'data' => [],
        ];
    }
}
