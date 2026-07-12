<?php

namespace App\Services\Kalanbot\Tools\Parents;

use App\Http\Controllers\ParentController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class RechercherParentsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'parents_rechercher';
    }

    public function module(): string
    {
        return 'parents';
    }

    public function description(): string
    {
        return "Rechercher des parents par nom, téléphone, email, ou par le nom/matricule d'un de leurs enfants. "
            . "Utiliser avant parents_modifier/parents_supprimer si l'identifiant du parent n'est pas connu.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'recherche' => ['type' => 'STRING', 'description' => 'Nom, téléphone, email ou nom d\'enfant (optionnel).'],
                'id_classe' => ['type' => 'INTEGER', 'description' => 'Filtrer les parents ayant un enfant dans cette classe (optionnel).'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'recherche' => 'nullable|string|max:100',
            'id_classe' => 'nullable|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('parents_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest([
            'search' => $validated['recherche'] ?? null,
            'id_classe' => $validated['id_classe'] ?? null,
        ]);

        $outcome = $this->callController(fn () => app(ParentController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['parents'] ?? null;
        $items = $paginator ? collect($paginator->items()) : collect();

        $parents = $items->map(fn ($parent) => [
            'id_parent' => $parent->id_parent,
            'nom' => $parent->nom_prenom_parent,
            'telephone' => $parent->telephone_parent,
            'email' => $parent->email_parent,
            'nombre_enfants' => $parent->eleves_count ?? $parent->eleves->count(),
            'enfants' => $parent->eleves->map(fn ($e) => trim($e->prenom_eleve . ' ' . $e->nom_eleve) . ' (' . (optional($e->classe)->nom_classe ?? '?') . ')')->values()->all(),
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($parents) . ' parent(s) trouvé(s).',
            'data' => ['parents' => $parents],
        ];
    }
}
