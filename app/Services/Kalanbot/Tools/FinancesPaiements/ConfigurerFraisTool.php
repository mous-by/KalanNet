<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\Classe;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConfigurerFraisTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_frais_configurer';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Créer ou mettre à jour un frais scolaire (montant, obligatoire) pour une classe (école privée) ou "
            . "une cotisation type école publique (Cooperative, Inscription, Badge, Tenue, Activites, Autres "
            . "cotisations), pour une année scolaire.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'classe_id' => ['type' => 'INTEGER', 'description' => 'Requis pour une école privée ; sans objet pour une école publique.'],
                'annee_scolaire_id' => ['type' => 'INTEGER'],
                'type_frais' => ['type' => 'STRING'],
                'montant' => ['type' => 'NUMBER'],
                'obligatoire' => ['type' => 'BOOLEAN'],
            ],
            'required' => ['annee_scolaire_id', 'type_frais', 'montant'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'classe_id' => 'nullable|integer|exists:classe,id_classe',
            'annee_scolaire_id' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'type_frais' => 'required|string|max:80',
            'montant' => 'required|numeric|min:0',
            'obligatoire' => 'nullable|boolean',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('paiements_faire');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = !empty($args['classe_id']) ? Classe::find($args['classe_id']) : null;

        return sprintf(
            "Je vais configurer le frais « %s » à %s FCFA%s. Confirmez-vous ?",
            $args['type_frais'] ?? '',
            number_format((float) ($args['montant'] ?? 0), 0, ',', ' '),
            $classe ? " pour la classe {$classe->nom_classe}" : ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeFraisScolaire($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Frais scolaire enregistré.',
            'data' => [],
        ];
    }
}
