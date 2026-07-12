<?php

namespace App\Services\Kalanbot\Tools\Matieres;

use App\Http\Controllers\MatiereController;
use App\Models\Matiere;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierMatiereTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'matieres_modifier';
    }

    public function module(): string
    {
        return 'matieres';
    }

    public function description(): string
    {
        return "Modifier le nom et les ordres d'enseignement d'une matière (remplace les ordres existants).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_matiere' => ['type' => 'INTEGER'],
                'nom_matiere' => ['type' => 'STRING'],
                'ordre_enseignement' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['id_matiere', 'nom_matiere', 'ordre_enseignement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_matiere' => 'required|integer',
            'nom_matiere' => 'required|string|max:50',
            'ordre_enseignement' => 'required|array|min:1',
            'ordre_enseignement.*' => 'required|string|in:Fondamentale I,Fondamentale II,Secondaire Generale,Secondaire Technique et Professionnel',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('matieres_modification');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $matiere = Matiere::find($args['id_matiere'] ?? null);

        return sprintf(
            "Je vais modifier la matière %s : nouveau nom « %s ». Confirmez-vous ?",
            $matiere?->nom_matiere ?? 'inconnue',
            $args['nom_matiere'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(MatiereController::class)->update($request, (int) $validated['id_matiere']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Matière modifiée avec succès.',
            'data' => [],
        ];
    }
}
