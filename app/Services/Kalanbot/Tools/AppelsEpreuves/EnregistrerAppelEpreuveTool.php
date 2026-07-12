<?php

namespace App\Services\Kalanbot\Tools\AppelsEpreuves;

use App\Http\Controllers\AppelEpreuveController;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class EnregistrerAppelEpreuveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'appels_epreuves_enregistrer';
    }

    public function module(): string
    {
        return 'appels_epreuves';
    }

    public function description(): string
    {
        return "Enregistrer l'appel d'épreuve d'une classe (statut de présence de chaque élève à une "
            . "composition/épreuve). Recalcule automatiquement les notes de conduite. Utiliser "
            . "evaluations_eleves_par_classe pour obtenir la liste des élèves et leurs identifiants, et l'outil de "
            . "lecture des statuts disponibles si besoin.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_matiere' => ['type' => 'INTEGER'],
                'id_annee_scolaire' => ['type' => 'INTEGER'],
                'id_trimestre' => ['type' => 'INTEGER'],
                'date' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'libelle' => ['type' => 'STRING', 'description' => "Intitulé de l'épreuve."],
                'heure_debut' => ['type' => 'STRING', 'description' => 'Format HH:MM.'],
                'heure_fin' => ['type' => 'STRING', 'description' => 'Format HH:MM.'],
                'notifier_parent' => ['type' => 'BOOLEAN', 'description' => 'Notifier les parents des élèves marqués absents/en retard.'],
                'statuts' => [
                    'type' => 'ARRAY',
                    'description' => 'Statut de chaque élève pour cette épreuve.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_eleve' => ['type' => 'INTEGER'],
                            'id_controle' => ['type' => 'INTEGER', 'description' => 'Identifiant du statut (ex: présent, absent, en retard).'],
                        ],
                        'required' => ['id_eleve', 'id_controle'],
                    ],
                ],
            ],
            'required' => ['id_classe', 'id_matiere', 'id_annee_scolaire', 'id_trimestre', 'date', 'libelle', 'heure_debut', 'heure_fin', 'statuts'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'date' => 'required|date',
            'libelle' => 'required|string|max:255',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'notifier_parent' => 'nullable|boolean',
            'statuts' => 'required|array|min:1',
            'statuts.*.id_eleve' => 'required|integer',
            'statuts.*.id_controle' => 'required|integer|exists:controle,id_controle',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['controle_creation', 'controle_création']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = Classe::find($args['id_classe'] ?? null);
        $matiere = Matiere::find($args['id_matiere'] ?? null);
        $nb = count($args['statuts'] ?? []);

        return sprintf(
            "Je vais enregistrer l'appel d'épreuve « %s » (%s) pour %d élève(s) de la classe %s, le %s. Les notes "
            . "de conduite seront recalculées%s. Confirmez-vous ?",
            $args['libelle'] ?? '',
            $matiere?->nom_matiere ?? 'matière inconnue',
            $nb,
            $classe?->nom_classe ?? 'classe inconnue',
            $args['date'] ?? '',
            !empty($args['notifier_parent']) ? ' et les parents concernés seront notifiés' : ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $statuts = collect($validated['statuts'])->mapWithKeys(fn ($s) => [$s['id_eleve'] => $s['id_controle']])->all();

        $request = $this->makeRequest([
            'id_classe' => $validated['id_classe'],
            'id_matiere' => $validated['id_matiere'],
            'id_annee_scolaire' => $validated['id_annee_scolaire'],
            'id_trimestre' => $validated['id_trimestre'],
            'date' => $validated['date'],
            'libelle' => $validated['libelle'],
            'heure_debut' => $validated['heure_debut'],
            'heure_fin' => $validated['heure_fin'],
            'notifier_parent' => $validated['notifier_parent'] ?? false,
            'statuts' => $statuts,
        ]);

        $outcome = $this->callController(fn () => app(AppelEpreuveController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Appel d'épreuve enregistré. Les notes de conduite ont été recalculées.",
            'data' => [],
        ];
    }
}
