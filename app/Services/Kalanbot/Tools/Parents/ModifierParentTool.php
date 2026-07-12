<?php

namespace App\Services\Kalanbot\Tools\Parents;

use App\Http\Controllers\ParentController;
use App\Models\ParentModel;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierParentTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'parents_modifier';
    }

    public function module(): string
    {
        return 'parents';
    }

    public function description(): string
    {
        return "Modifier les coordonnées d'un parent et/ou la liste des élèves qui lui sont rattachés. "
            . "Attention : remplace intégralement la liste des enfants rattachés, y compris ceux déjà existants "
            . "(les récupérer d'abord via parents_rechercher si tu ne veux pas les perdre).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_parent' => ['type' => 'INTEGER'],
                'nom_prenom_parent' => ['type' => 'STRING'],
                'telephone_parent' => ['type' => 'STRING'],
                'email_parent' => ['type' => 'STRING'],
                'genre' => ['type' => 'STRING'],
                'enfants' => [
                    'type' => 'ARRAY',
                    'description' => 'Liste complète des élèves rattachés après modification.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_eleve' => ['type' => 'INTEGER'],
                            'lien_parent' => ['type' => 'STRING'],
                            'informer' => ['type' => 'STRING', 'description' => 'Oui ou Non.'],
                        ],
                        'required' => ['id_eleve', 'lien_parent'],
                    ],
                ],
            ],
            'required' => ['id_parent', 'nom_prenom_parent', 'telephone_parent', 'enfants'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_parent' => 'required|integer',
            'nom_prenom_parent' => 'required|string|max:255',
            'telephone_parent' => 'required|string|max:20',
            'email_parent' => 'nullable|email|max:255',
            'genre' => 'nullable|string|max:50',
            'enfants' => 'required|array|min:1',
            'enfants.*.id_eleve' => 'required|integer',
            'enfants.*.lien_parent' => 'required|string|max:100',
            'enfants.*.informer' => 'nullable|string|in:Oui,Non',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('parents_modification');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $parent = ParentModel::find($args['id_parent'] ?? null);
        $nomActuel = $parent?->nom_prenom_parent ?? 'ce parent';

        return sprintf(
            "Je vais mettre à jour %s : nouveau nom « %s », téléphone « %s », avec %d enfant(s) rattaché(s). Confirmez-vous ?",
            $nomActuel,
            $args['nom_prenom_parent'] ?? '',
            $args['telephone_parent'] ?? '',
            count($args['enfants'] ?? [])
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $enfants = collect($validated['enfants']);

        $request = $this->makeRequest([
            'nom_prenom_parent' => $validated['nom_prenom_parent'],
            'telephone_parent' => $validated['telephone_parent'],
            'email_parent' => $validated['email_parent'] ?? null,
            'genre' => $validated['genre'] ?? null,
            'id_eleve' => $enfants->pluck('id_eleve')->all(),
            'lien_parent' => $enfants->pluck('lien_parent')->all(),
            'informer' => $enfants->map(fn ($e) => $e['informer'] ?? 'Non')->all(),
        ]);

        $outcome = $this->callController(fn () => app(ParentController::class)->update($request, (int) $validated['id_parent']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Parent modifié avec succès.',
            'data' => [],
        ];
    }
}
