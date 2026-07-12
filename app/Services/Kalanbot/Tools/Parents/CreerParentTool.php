<?php

namespace App\Services\Kalanbot\Tools\Parents;

use App\Http\Controllers\ParentController;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerParentTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'parents_creer';
    }

    public function module(): string
    {
        return 'parents';
    }

    public function description(): string
    {
        return "Créer un parent et le rattacher à un ou plusieurs élèves déjà inscrits (utiliser eleves_rechercher "
            . "pour trouver leurs identifiants). Un élève ne peut être rattaché qu'à un seul parent.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'nom_prenom_parent' => ['type' => 'STRING'],
                'telephone_parent' => ['type' => 'STRING', 'description' => 'Numéro malien.'],
                'email_parent' => ['type' => 'STRING'],
                'genre' => ['type' => 'STRING'],
                'enfants' => [
                    'type' => 'ARRAY',
                    'description' => 'Élèves à rattacher à ce parent.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_eleve' => ['type' => 'INTEGER'],
                            'lien_parent' => ['type' => 'STRING', 'description' => 'Ex: Père, Mère, Tuteur.'],
                            'informer' => ['type' => 'STRING', 'description' => 'Oui ou Non.'],
                        ],
                        'required' => ['id_eleve', 'lien_parent'],
                    ],
                ],
            ],
            'required' => ['nom_prenom_parent', 'telephone_parent', 'enfants'],
        ];
    }

    public function validationRules(): array
    {
        return [
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
        return $user->droit === 'SupAdmin' || $user->userHasPermission('parents_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $enfants = collect($args['enfants'] ?? []);
        $eleves = Eleve::whereIn('id_eleve', $enfants->pluck('id_eleve'))->get()->keyBy('id_eleve');
        $noms = $enfants->map(function ($e) use ($eleves) {
            $eleve = $eleves->get($e['id_eleve'] ?? null);

            return $eleve ? trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve) : 'élève inconnu';
        })->implode(', ');

        return sprintf(
            "Je vais créer le parent %s (tél. %s), rattaché à : %s. Un mot de passe temporaire sera généré. Confirmez-vous ?",
            $args['nom_prenom_parent'] ?? '',
            $args['telephone_parent'] ?? '',
            $noms
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

        $outcome = $this->callController(fn () => app(ParentController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Parent {$validated['nom_prenom_parent']} créé avec succès.",
            'data' => [],
        ];
    }
}
