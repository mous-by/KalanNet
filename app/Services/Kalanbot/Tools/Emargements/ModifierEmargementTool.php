<?php

namespace App\Services\Kalanbot\Tools\Emargements;

use App\Http\Controllers\EmargementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierEmargementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emargements_modifier';
    }

    public function module(): string
    {
        return 'emargements';
    }

    public function description(): string
    {
        return "Modifier un émargement non encore validé.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_emargement' => ['type' => 'INTEGER'],
                'id_enseignant' => ['type' => 'INTEGER'],
                'id_classe' => ['type' => 'INTEGER'],
                'id_matiere' => ['type' => 'INTEGER'],
                'chapitre' => ['type' => 'STRING'],
                'id_lecon' => ['type' => 'INTEGER'],
                'new_lecon_titre' => ['type' => 'STRING'],
                'nombre_heure' => ['type' => 'NUMBER'],
                'id_trimestre' => ['type' => 'INTEGER'],
                'id_anneeScolaire' => ['type' => 'INTEGER'],
                'date_emargement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
            ],
            'required' => ['id_emargement', 'id_classe', 'id_matiere', 'nombre_heure', 'id_trimestre', 'id_anneeScolaire', 'date_emargement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_emargement' => 'required|integer',
            'id_enseignant' => 'nullable|integer|exists:enseignants,id_enseignant',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'chapitre' => 'nullable|string|max:255',
            'id_lecon' => 'nullable|integer',
            'new_lecon_titre' => 'nullable|string|max:255',
            'nombre_heure' => 'required|numeric|min:0.25|max:24',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'id_anneeScolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_emargement' => 'required|date',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['emargement_modification', 'emargement_faire']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return "Je vais modifier l'émargement #{$args['id_emargement']} (nouvelle durée : " . ($args['nombre_heure'] ?? '?') . " heure(s)). Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        if (empty($validated['id_enseignant']) && $user->id_enseignant) {
            $validated['id_enseignant'] = $user->id_enseignant;
        }

        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(EmargementController::class)->update($request, (int) $validated['id_emargement']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Émargement modifié avec succès.',
            'data' => [],
        ];
    }
}
