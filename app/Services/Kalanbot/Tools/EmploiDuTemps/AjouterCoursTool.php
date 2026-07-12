<?php

namespace App\Services\Kalanbot\Tools\EmploiDuTemps;

use App\Http\Controllers\TimetableController;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class AjouterCoursTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emploi_du_temps_ajouter';
    }

    public function module(): string
    {
        return 'emploi_du_temps';
    }

    public function description(): string
    {
        return "Ajouter un cours à l'emploi du temps d'une classe. Non disponible pour les enseignants.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_matiere' => ['type' => 'INTEGER'],
                'id_enseignant' => ['type' => 'INTEGER', 'description' => 'Optionnel.'],
                'id_annee_scolaire' => ['type' => 'INTEGER'],
                'jour' => ['type' => 'STRING', 'description' => 'Lundi, Mardi, Mercredi, Jeudi, Vendredi ou Samedi.'],
                'heure_debut' => ['type' => 'STRING', 'description' => 'Format HH:MM.'],
                'heure_fin' => ['type' => 'STRING', 'description' => 'Format HH:MM, après heure_debut.'],
            ],
            'required' => ['id_classe', 'id_matiere', 'id_annee_scolaire', 'jour', 'heure_debut', 'heure_fin'],
        ];
    }

    public function validationRules(): array
    {
        return [
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
        $classe = Classe::find($args['id_classe'] ?? null);
        $matiere = Matiere::find($args['id_matiere'] ?? null);

        return sprintf(
            "Je vais ajouter un cours de %s pour la classe %s, le %s de %s à %s. Confirmez-vous ?",
            $matiere?->nom_matiere ?? 'matière inconnue',
            $classe?->nom_classe ?? 'classe inconnue',
            $args['jour'] ?? '',
            $args['heure_debut'] ?? '',
            $args['heure_fin'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(TimetableController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Cours ajouté à l'emploi du temps avec succès.",
            'data' => [],
        ];
    }
}
