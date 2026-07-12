<?php

namespace App\Services\Kalanbot\Tools\Enseignants;

use App\Http\Controllers\EnseignantController;
use App\Models\Enseignant;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ArchiverEnseignantTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'enseignants_archiver';
    }

    public function module(): string
    {
        return 'enseignants';
    }

    public function description(): string
    {
        return "Archiver (désactiver) le compte d'un enseignant. Réversible via enseignants_reactiver.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_enseignant'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_enseignant' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin'
            || $user->userHasAnyPermission(['enseignants_archiver_ou_reactiver', 'enseignants_archiver ou réactiver']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $enseignant = Enseignant::find($args['id_enseignant'] ?? null);
        $nom = $enseignant?->nom_prenom_enseignant ?? 'cet enseignant';

        return "Je vais archiver {$nom} (le compte pourra être réactivé plus tard). Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EnseignantController::class)->archive((int) $validated['id_enseignant']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Enseignant archivé avec succès.',
            'data' => [],
        ];
    }
}
