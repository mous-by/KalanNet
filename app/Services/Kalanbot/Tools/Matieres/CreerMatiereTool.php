<?php

namespace App\Services\Kalanbot\Tools\Matieres;

use App\Http\Controllers\MatiereController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerMatiereTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'matieres_creer';
    }

    public function module(): string
    {
        return 'matieres';
    }

    public function description(): string
    {
        return "Créer une matière et les ordres d'enseignement où elle est dispensée.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'nom_matiere' => ['type' => 'STRING'],
                'ordre_enseignement' => [
                    'type' => 'ARRAY',
                    'description' => "Un ou plusieurs parmi : Fondamentale I, Fondamentale II, Secondaire Generale, Secondaire Technique et Professionnel.",
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['nom_matiere', 'ordre_enseignement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'nom_matiere' => 'required|string|max:50',
            'ordre_enseignement' => 'required|array|min:1',
            'ordre_enseignement.*' => 'required|string|in:Fondamentale I,Fondamentale II,Secondaire Generale,Secondaire Technique et Professionnel',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('matieres_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return sprintf(
            "Je vais créer la matière « %s » pour : %s. Confirmez-vous ?",
            $args['nom_matiere'] ?? '',
            implode(', ', $args['ordre_enseignement'] ?? [])
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(MatiereController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Matière « {$validated['nom_matiere']} » créée avec succès.",
            'data' => [],
        ];
    }
}
