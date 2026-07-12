<?php

namespace App\Services\Kalanbot\Tools\Communication;

use App\Http\Controllers\AnnouncementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class AnnoncesCreerTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'annonces_creer';
    }

    public function module(): string
    {
        return 'communication';
    }

    public function description(): string
    {
        return "Créer une annonce à destination d'un public cible (tous, parents, enseignants ou gestionnaires). "
            . "Si le statut est 'publie', l'annonce est immédiatement visible/notifiée au public cible : action "
            . "de diffusion de masse potentielle. Utiliser 'brouillon' pour préparer sans diffuser.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'titre' => ['type' => 'STRING'],
                'contenu' => ['type' => 'STRING'],
                'public_cible' => ['type' => 'STRING', 'description' => 'tous, parents, enseignants ou gestionnaires.'],
                'statut_annonce' => ['type' => 'STRING', 'description' => 'publie, brouillon ou archive.'],
            ],
            'required' => ['titre', 'contenu', 'public_cible', 'statut_annonce'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'public_cible' => 'required|string|in:tous,parents,enseignants,gestionnaires',
            'statut_annonce' => 'required|string|in:publie,brouillon,archive',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('annonces_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function requiresDoubleConfirmation(): bool
    {
        // Une annonce publiée peut toucher tout un public (parents/enseignants/école entière) :
        // traitée comme une action de masse potentielle, donc double confirmation systématique.
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $publicLabels = ['tous' => 'toute l\'école', 'parents' => 'tous les parents', 'enseignants' => 'tous les enseignants', 'gestionnaires' => 'les gestionnaires'];
        $public = $publicLabels[$args['public_cible'] ?? ''] ?? ($args['public_cible'] ?? '?');

        if (($args['statut_annonce'] ?? '') === 'publie') {
            return sprintf(
                "📢 Je vais publier immédiatement l'annonce « %s » auprès de %s. Confirmez-vous cette diffusion ?",
                $args['titre'] ?? '',
                $public
            );
        }

        return sprintf(
            "Je vais enregistrer l'annonce « %s » en brouillon (public visé : %s), non diffusée pour l'instant. Confirmez-vous ?",
            $args['titre'] ?? '',
            $public
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(AnnouncementController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Annonce enregistrée avec succès.',
            'data' => [],
        ];
    }
}
