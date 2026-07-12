<?php

namespace App\Services\Kalanbot\Tools\EmploiDuTemps;

use App\Http\Controllers\TimetableController;
use App\Models\EmploiDuTemps;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class SupprimerCoursTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emploi_du_temps_supprimer';
    }

    public function module(): string
    {
        return 'emploi_du_temps';
    }

    public function description(): string
    {
        return "Supprimer un cours de l'emploi du temps. Non disponible pour les enseignants.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => ['id' => ['type' => 'INTEGER', 'description' => 'Identifiant du cours.']],
            'required' => ['id'],
        ];
    }

    public function validationRules(): array
    {
        return ['id' => 'required|integer'];
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
        $cours = EmploiDuTemps::with(['classe', 'matiere'])->find($args['id'] ?? null);

        return sprintf(
            "Je vais supprimer le cours de %s (%s, %s-%s) pour la classe %s. Confirmez-vous ?",
            $cours?->matiere?->nom_matiere ?? 'matière inconnue',
            $cours?->jour ?? '?',
            $cours?->heure_debut ?? '?',
            $cours?->heure_fin ?? '?',
            $cours?->classe?->nom_classe ?? 'classe inconnue'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest([]);

        $outcome = $this->callController(fn () => app(TimetableController::class)->destroy($request, (int) $validated['id']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Cours supprimé avec succès.',
            'data' => [],
        ];
    }
}
