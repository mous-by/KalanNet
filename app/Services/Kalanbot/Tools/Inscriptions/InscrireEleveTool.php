<?php

namespace App\Services\Kalanbot\Tools\Inscriptions;

use App\Http\Controllers\InscriptionController;
use App\Models\Classe;
use App\Models\ParentModel;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class InscrireEleveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'inscriptions_inscrire';
    }

    public function module(): string
    {
        return 'inscriptions';
    }

    public function description(): string
    {
        return "Inscrire un nouvel élève dans une classe pour une année scolaire, avec rattachement optionnel à "
            . "un parent déjà existant (utiliser parents_rechercher pour trouver son id).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'prenom_eleve' => ['type' => 'STRING'],
                'nom_eleve' => ['type' => 'STRING'],
                'genre_eleve' => ['type' => 'STRING', 'description' => 'Masculin ou Féminin.'],
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
                'date_naissance' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ (optionnel).'],
                'lieu_naiss' => ['type' => 'STRING'],
                'adresse_eleve' => ['type' => 'STRING'],
                'cas_social' => ['type' => 'STRING'],
                'mode_paiement' => ['type' => 'STRING'],
                'date_inscription' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ (optionnel, défaut aujourd\'hui).'],
                'matricule' => ['type' => 'STRING', 'description' => 'Laisser vide pour génération automatique.'],
                'parent_id' => ['type' => 'INTEGER', 'description' => 'Identifiant du parent à rattacher (optionnel).'],
                'lien_parent' => ['type' => 'STRING', 'description' => "Ex: Père, Mère, Tuteur (si parent_id fourni)."],
                'informer' => ['type' => 'STRING', 'description' => 'Oui ou Non : informer ce parent des notifications.'],
                'id_planification' => ['type' => 'INTEGER', 'description' => 'Planification de frais applicable (requis selon les écoles).'],
            ],
            'required' => ['prenom_eleve', 'nom_eleve', 'genre_eleve', 'id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'prenom_eleve' => 'required|string|max:255',
            'nom_eleve' => 'required|string|max:255',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'genre_eleve' => 'required|string|in:Masculin,Féminin',
            'date_naissance' => 'nullable|date_format:Y-m-d',
            'lieu_naiss' => 'nullable|string|max:255',
            'adresse_eleve' => 'nullable|string|max:255',
            'cas_social' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:255',
            'date_inscription' => 'nullable|date_format:Y-m-d',
            'matricule' => 'nullable|string|max:50',
            'parent_id' => 'nullable|integer|exists:parents,id_parent',
            'lien_parent' => 'nullable|string|max:100',
            'informer' => 'nullable|string|in:Oui,Non',
            'id_planification' => 'nullable|integer|exists:planification,id_planification',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('inscriptions_inscrire');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = Classe::find($args['id_classe'] ?? null);
        $parentNote = '';
        if (!empty($args['parent_id'])) {
            $parent = ParentModel::find($args['parent_id']);
            if ($parent) {
                $parentNote = ", rattaché au parent {$parent->nom_prenom_parent}";
            }
        }

        return sprintf(
            "Je vais inscrire %s %s en %s%s. Confirmez-vous ?",
            $args['prenom_eleve'] ?? '',
            $args['nom_eleve'] ?? '',
            $classe?->nom_classe ?? 'classe inconnue',
            $parentNote
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        // Le contrôleur accède directement à certaines clés optionnelles (lieu_naiss,
        // cas_social) sans "??" : Gemini omettant les champs non fournis plutôt que
        // d'envoyer null, on complète explicitement pour éviter des clés absentes.
        $payload = array_merge([
            'lieu_naiss' => null,
            'adresse_eleve' => null,
            'cas_social' => null,
            'mode_paiement' => null,
            'date_naissance' => null,
            'date_inscription' => null,
            'matricule' => null,
        ], $validated);

        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(InscriptionController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "{$validated['prenom_eleve']} {$validated['nom_eleve']} a été inscrit(e) avec succès.",
            'data' => [],
        ];
    }
}
