<?php

namespace App\Services\Kalanbot\Tools\FinancesCaisse;

use App\Http\Controllers\FinanceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

/**
 * ⚠️ FinanceController::storeEncaissement() n'a AUCUNE vérification de permission dans
 * le contrôleur (trou de sécurité existant). Autorisation durcie ici, sans modifier le
 * contrôleur, sur la permission catalogue la plus proche (encaissement_creation).
 */
class CreerEncaissementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_encaissement_creer';
    }

    public function module(): string
    {
        return 'finances_caisse';
    }

    public function description(): string
    {
        return "Enregistrer un encaissement manuel en caisse (recette hors paiement de scolarité, ex: don, "
            . "subvention ponctuelle). Crédite immédiatement la caisse.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_caisse' => ['type' => 'INTEGER'],
                'id_annee_scolaire' => ['type' => 'INTEGER'],
                'type_operation' => ['type' => 'STRING'],
                'date_encaissement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'motif_encaissement' => ['type' => 'STRING'],
                'montant_encaissement' => ['type' => 'NUMBER'],
            ],
            'required' => ['id_caisse', 'id_annee_scolaire', 'type_operation', 'date_encaissement', 'motif_encaissement', 'montant_encaissement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_caisse' => 'required|integer|exists:caisse,id_caisse',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'type_operation' => 'required|string|max:255',
            'date_encaissement' => 'required|date',
            'motif_encaissement' => 'required|string|max:255',
            'montant_encaissement' => 'required|numeric|min:1',
        ];
    }

    public function authorize(User $user): bool
    {
        return in_array($user->droit, ['SupAdmin', 'Admin', 'Gestionnaire'], true) || $user->userHasPermission('encaissement_creation');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function requiresDoubleConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return sprintf(
            "💰 Je vais enregistrer un encaissement de %s FCFA (%s), qui créditera immédiatement la caisse. Confirmez-vous ?",
            number_format((float) ($args['montant_encaissement'] ?? 0), 0, ',', ' '),
            $args['motif_encaissement'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storeEncaissement($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Encaissement ajouté avec succès.',
            'data' => [],
        ];
    }
}
