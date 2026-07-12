<?php

namespace App\Services\Kalanbot\Tools\ResultatsNationaux;

use App\Http\Controllers\ResultatNationalController;
use App\Models\Classe;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class EnregistrerResultatsNationauxTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'resultats_nationaux_enregistrer';
    }

    public function module(): string
    {
        return 'resultats_nationaux';
    }

    public function description(): string
    {
        return "Enregistrer les résultats aux examens nationaux (DEF ou BAC) pour une ou plusieurs élèves d'une "
            . "classe : décision (admis/échec), moyenne, observation.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
                'niveau_examen' => ['type' => 'STRING', 'description' => 'DEF ou BAC.'],
                'date_resultat' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ (optionnel, défaut aujourd\'hui).'],
                'resultats' => [
                    'type' => 'ARRAY',
                    'description' => 'Résultat par élève.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_eleve' => ['type' => 'INTEGER'],
                            'decision' => ['type' => 'STRING', 'description' => 'admis ou échec.'],
                            'moyenne' => ['type' => 'NUMBER', 'description' => 'Sur 20 (optionnel).'],
                            'observation' => ['type' => 'STRING'],
                        ],
                        'required' => ['id_eleve', 'decision'],
                    ],
                ],
            ],
            'required' => ['id_classe', 'id_annee', 'niveau_examen', 'resultats'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'niveau_examen' => 'required|string|in:DEF,BAC',
            'date_resultat' => 'nullable|date',
            'resultats' => 'required|array|min:1',
            'resultats.*.id_eleve' => 'required|integer',
            'resultats.*.decision' => 'required|string|in:admis,échec,echec',
            'resultats.*.moyenne' => 'nullable|numeric|min:0|max:20',
            'resultats.*.observation' => 'nullable|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'resultats_nationaux_apercu', 'resultats nationaux_apercu',
            'resultats_def_terminal_apercu', 'reinscriptions_apercu', 'inscriptions_reinscrire',
        ]);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = Classe::find($args['id_classe'] ?? null);
        $nb = count($args['resultats'] ?? []);

        return sprintf(
            "Je vais enregistrer les résultats %s pour %d élève(s) de la classe %s. Confirmez-vous ?",
            $args['niveau_examen'] ?? '',
            $nb,
            $classe?->nom_classe ?? 'classe inconnue'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $resultats = collect($validated['resultats'])->mapWithKeys(fn ($r) => [
            $r['id_eleve'] => [
                'decision' => $r['decision'],
                'moyenne' => $r['moyenne'] ?? null,
                'observation' => $r['observation'] ?? null,
            ],
        ])->all();

        $request = $this->makeRequest([
            'id_classe' => $validated['id_classe'],
            'id_annee' => $validated['id_annee'],
            'niveau_examen' => $validated['niveau_examen'],
            'date_resultat' => $validated['date_resultat'] ?? null,
            'resultats' => $resultats,
        ]);

        $outcome = $this->callController(fn () => app(ResultatNationalController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => count($validated['resultats']) . ' résultat(s) national(aux) enregistré(s).',
            'data' => [],
        ];
    }
}
