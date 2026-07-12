<?php

namespace App\Services\Kalanbot\Tools\Presences;

use App\Http\Controllers\PresenceController;
use App\Models\Classe;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerPresenceTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'presences_creer';
    }

    public function module(): string
    {
        return 'presences';
    }

    public function description(): string
    {
        return "Enregistrer une présence (cahier de texte) : leçons faites par un enseignant dans une classe, "
            . "avec le nombre d'heures par leçon. Si l'utilisateur connecté est lui-même enseignant, la présence "
            . "lui est automatiquement attribuée.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER', 'description' => "Requis sauf si l'utilisateur connecté est l'enseignant lui-même."],
                'id_classe' => ['type' => 'INTEGER'],
                'date_presence' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'nombre_heure' => ['type' => 'NUMBER', 'description' => "Total d'heures (doit correspondre à la somme des leçons)."],
                'id_trimestre' => ['type' => 'INTEGER'],
                'id_anneeScolaire' => ['type' => 'INTEGER'],
                'lecons' => [
                    'type' => 'ARRAY',
                    'description' => 'Leçons effectuées pendant cette séance.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'titre' => ['type' => 'STRING'],
                            'nombre_heure' => ['type' => 'NUMBER'],
                            'progression' => ['type' => 'NUMBER', 'description' => 'Pourcentage 0-100 (optionnel).'],
                        ],
                        'required' => ['titre', 'nombre_heure'],
                    ],
                ],
            ],
            'required' => ['id_classe', 'date_presence', 'nombre_heure', 'id_trimestre', 'id_anneeScolaire', 'lecons'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_enseignant' => 'nullable|integer|exists:enseignants,id_enseignant',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'date_presence' => 'required|date',
            'nombre_heure' => 'required|numeric|min:0.1667|max:24',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'id_anneeScolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'lecons' => 'required|array|min:1',
            'lecons.*.titre' => 'required|string|max:255',
            'lecons.*.nombre_heure' => 'required|numeric|min:0.1667|max:24',
            'lecons.*.progression' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->droit === 'enseignant' || $user->userHasPermission('presence_apercu');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = Classe::find($args['id_classe'] ?? null);
        $lecons = collect($args['lecons'] ?? [])->pluck('titre')->implode(', ');

        return sprintf(
            "Je vais enregistrer une présence de %s heure(s) pour la classe %s le %s (leçons : %s). Confirmez-vous ?",
            $args['nombre_heure'] ?? '?',
            $classe?->nom_classe ?? 'classe inconnue',
            $args['date_presence'] ?? '',
            $lecons ?: 'aucune'
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

        $outcome = $this->callController(fn () => app(PresenceController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Présence enregistrée avec succès.',
            'data' => [],
        ];
    }
}
