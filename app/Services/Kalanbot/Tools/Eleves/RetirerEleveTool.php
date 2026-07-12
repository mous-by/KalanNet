<?php

namespace App\Services\Kalanbot\Tools\Eleves;

use App\Http\Controllers\EleveController;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class RetirerEleveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'eleves_retirer';
    }

    public function module(): string
    {
        return 'eleves';
    }

    public function description(): string
    {
        return "Retirer un élève de la liste active (l'élève reste dans l'historique et peut être réintégré "
            . "plus tard via eleves_reintegrer). N'est pas une suppression définitive.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_eleve' => ['type' => 'INTEGER', 'description' => "Identifiant de l'élève à retirer."],
            ],
            'required' => ['id_eleve'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_eleve' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('eleves_supprimer');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $eleve = Eleve::find($args['id_eleve'] ?? null);
        $nom = $eleve ? trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve) : 'cet élève';

        return "Je vais retirer {$nom} de la liste active des élèves. Le dossier n'est pas supprimé et une "
            . "réintégration restera possible plus tard. Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(EleveController::class)->destroy((int) $validated['id_eleve']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Élève retiré de la liste active avec succès.',
            'data' => [],
        ];
    }
}
