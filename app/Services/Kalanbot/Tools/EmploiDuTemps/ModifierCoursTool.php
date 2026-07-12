<?php

namespace App\Services\Kalanbot\Tools\EmploiDuTemps;

use App\Http\Controllers\TimetableController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierCoursTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emploi_du_temps_modifier';
    }

    public function module(): string
    {
        return 'emploi_du_temps';
    }

    public function description(): string
    {
        return "Modifier un cours existant de l'emploi du temps. Non disponible pour les enseignants.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id' => ['type' => 'INTEGER', 'description' => 'Identifiant du cours.'],
                'id_classe' => ['type' => 'INTEGER'],
                'id_matiere' => ['type' => 'INTEGER'],
                'id_enseignant' => ['type' => 'INTEGER'],
                'id_annee_scolaire' => ['type' => 'INTEGER'],
                'jour' => ['type' => 'STRING'],
                'heure_debut' => ['type' => 'STRING', 'description' => 'Format HH:MM.'],
                'heure_fin' => ['type' => 'STRING', 'description' => 'Format HH:MM.'],
            ],
            'required' => ['id', 'id_classe', 'id_matiere', 'id_annee_scolaire', 'jour', 'heure_debut', 'heure_fin'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id' => 'required|integer',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'id_enseignant' => 'nullable|integer|exists:enseignants,id_enseignant',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'jour' => 'required|string|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi',
            'heure_debut' => 'required',
            'heure_fin' => 'required|after:heure_debut',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit !== 'enseignant'
            && ($user->droit === 'SupAdmin' || $user->userHasPermission('planning_creation'));
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return "Je vais modifier le cours #{$args['id']} : {$args['jour']} de {$args['heure_debut']} à {$args['heure_fin']}. Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(TimetableController::class)->update($request, (int) $validated['id']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Cours modifié avec succès.',
            'data' => [],
        ];
    }
}
