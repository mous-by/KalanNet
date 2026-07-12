<?php

namespace App\Services\Kalanbot\Tools\Emargements;

use App\Http\Controllers\EmargementController;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerEmargementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emargements_creer';
    }

    public function module(): string
    {
        return 'emargements';
    }

    public function description(): string
    {
        return "Émarger : déclarer les heures de cours effectuées par un enseignant dans une classe/matière, sur "
            . "une leçon existante (id_lecon) ou une nouvelle leçon (new_lecon_titre). Si l'utilisateur connecté "
            . "est lui-même enseignant, l'émargement lui est automatiquement attribué.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER', 'description' => "Requis sauf si l'utilisateur connecté est l'enseignant lui-même."],
                'id_classe' => ['type' => 'INTEGER'],
                'id_matiere' => ['type' => 'INTEGER'],
                'chapitre' => ['type' => 'STRING'],
                'id_lecon' => ['type' => 'INTEGER', 'description' => 'Leçon existante du programme (optionnel).'],
                'new_lecon_titre' => ['type' => 'STRING', 'description' => "Titre d'une nouvelle leçon si aucune leçon existante ne convient."],
                'nombre_heure' => ['type' => 'NUMBER', 'description' => 'Entre 0.25 et 24.'],
                'id_trimestre' => ['type' => 'INTEGER'],
                'id_anneeScolaire' => ['type' => 'INTEGER'],
                'date_emargement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
            ],
            'required' => ['id_classe', 'id_matiere', 'nombre_heure', 'id_trimestre', 'id_anneeScolaire', 'date_emargement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_enseignant' => 'nullable|integer|exists:enseignants,id_enseignant',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'chapitre' => 'nullable|string|max:255',
            'id_lecon' => 'nullable|integer',
            'new_lecon_titre' => 'nullable|string|max:255',
            'nombre_heure' => 'required|numeric|min:0.25|max:24',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'id_anneeScolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_emargement' => 'required|date',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->droit === 'enseignant' || $user->userHasPermission('emargement_faire');
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
            "Je vais enregistrer un émargement de %s heure(s) en %s pour la classe %s, le %s. Confirmez-vous ?",
            $args['nombre_heure'] ?? '?',
            $matiere?->nom_matiere ?? 'matière inconnue',
            $classe?->nom_classe ?? 'classe inconnue',
            $args['date_emargement'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        // Le contrôleur exige id_enseignant dans la requête avant de le réattribuer à
        // l'enseignant connecté (le formulaire web le pré-remplit via un champ caché) :
        // on reproduit ce comportement ici.
        if (empty($validated['id_enseignant']) && $user->id_enseignant) {
            $validated['id_enseignant'] = $user->id_enseignant;
        }

        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(EmargementController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Émargement enregistré avec succès.',
            'data' => [],
        ];
    }
}
