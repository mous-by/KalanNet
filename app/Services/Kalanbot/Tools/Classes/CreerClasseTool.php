<?php

namespace App\Services\Kalanbot\Tools\Classes;

use App\Http\Controllers\ClasseController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerClasseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'classes_creer';
    }

    public function module(): string
    {
        return 'classes';
    }

    public function description(): string
    {
        return "Créer une classe avec ses matières, coefficients et enseignants affectés.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'nom_classe' => ['type' => 'STRING'],
                'ordre_enseignement' => ['type' => 'STRING', 'description' => "Ex: fondamentale1, fondamentale2, secondairegenerale, secondairetechniqueetprofessionnel."],
                'matieres' => [
                    'type' => 'ARRAY',
                    'description' => 'Matières enseignées dans cette classe.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_matiere' => ['type' => 'INTEGER'],
                            'id_enseignant' => ['type' => 'INTEGER', 'description' => 'Optionnel.'],
                            'coefficient' => ['type' => 'NUMBER', 'description' => 'Entre 0 et 5.'],
                        ],
                        'required' => ['id_matiere', 'coefficient'],
                    ],
                ],
            ],
            'required' => ['nom_classe', 'ordre_enseignement', 'matieres'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'nom_classe' => 'required|string|max:50',
            'ordre_enseignement' => 'required|string|max:50',
            'matieres' => 'required|array|min:1',
            'matieres.*.id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'matieres.*.id_enseignant' => 'nullable|integer|exists:enseignants,id_enseignant',
            'matieres.*.coefficient' => 'required|numeric|min:0|max:5',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('classes_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return sprintf(
            "Je vais créer la classe « %s » (%s) avec %d matière(s). Confirmez-vous ?",
            $args['nom_classe'] ?? '',
            $args['ordre_enseignement'] ?? '',
            count($args['matieres'] ?? [])
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $matieres = collect($validated['matieres']);

        $request = $this->makeRequest([
            'nom_classe' => $validated['nom_classe'],
            'ordre_enseignement' => $validated['ordre_enseignement'],
            'id_matiere' => $matieres->pluck('id_matiere')->all(),
            'id_enseignants' => $matieres->map(fn ($m) => $m['id_enseignant'] ?? null)->all(),
            'coefficient' => $matieres->pluck('coefficient')->all(),
        ]);

        $outcome = $this->callController(fn () => app(ClasseController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Classe « {$validated['nom_classe']} » créée avec succès.",
            'data' => [],
        ];
    }
}
