<?php

namespace App\Services\Kalanbot\Tools\Enseignants;

use App\Http\Controllers\EnseignantController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class RechercherEnseignantsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'enseignants_rechercher';
    }

    public function module(): string
    {
        return 'enseignants';
    }

    public function description(): string
    {
        return "Rechercher des enseignants par nom, email, téléphone ou matricule.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'recherche' => ['type' => 'STRING', 'description' => 'Nom, email, téléphone ou matricule (optionnel).'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'recherche' => 'nullable|string|max:100',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin'
            || in_array($user->droit, ['DAE', 'DCAP', 'Admin', 'Gestionnaire', 'enseignant'], true)
            || $user->userHasPermission('enseignants_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest(['search' => $validated['recherche'] ?? null]);

        $outcome = $this->callController(fn () => app(EnseignantController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['enseignants'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $enseignants = $items->map(fn ($e) => [
            'id_enseignant' => $e->id_enseignant,
            'nom' => $e->nom_prenom_enseignant,
            'telephone' => $e->telephone_enseignant,
            'email' => $e->email_enseignant,
            'matricule' => $e->matricule,
            'type_contrat' => $e->type_contrat_enseignant,
            'archive' => (bool) $e->is_deleted,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($enseignants) . ' enseignant(s) trouvé(s).',
            'data' => ['enseignants' => $enseignants],
        ];
    }
}
