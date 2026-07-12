<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\EcheancePaiement;
use App\Models\ParentModel;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class EnregistrerPaiementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_paiements_enregistrer';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Enregistrer un règlement de scolarité sur une échéance précise (identifiant obtenu via "
            . "finances_paiements_contexte_eleve). Le montant ne peut pas dépasser le reste à payer de "
            . "l'échéance. Débite la caisse active de l'école.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'echeance_id' => ['type' => 'INTEGER', 'description' => "Obtenu via finances_paiements_contexte_eleve."],
                'date_paiement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'montant_paye' => ['type' => 'NUMBER', 'description' => 'Ne doit pas dépasser le reste à payer de l\'échéance.'],
                'mode_reglement' => ['type' => 'STRING', 'description' => 'Ex: especes, mobile_money, cheque, virement.'],
                'motif' => ['type' => 'STRING'],
                'parent_id' => ['type' => 'INTEGER', 'description' => "Identifiant du parent payeur (optionnel, sinon nom_payeur/telephone requis)."],
                'nom_payeur' => ['type' => 'STRING', 'description' => 'Requis si parent_id absent.'],
                'telephone' => ['type' => 'STRING', 'description' => 'Requis si parent_id absent (numéro malien).'],
            ],
            'required' => ['echeance_id', 'date_paiement', 'montant_paye', 'mode_reglement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'echeance_id' => 'required|integer|exists:echeances_paiement,id',
            'date_paiement' => 'required|date',
            'motif' => 'nullable|string|max:100',
            'montant_paye' => 'required|numeric|min:1',
            'mode_reglement' => 'required|string|max:40',
            'parent_id' => 'nullable|integer|exists:parents,id_parent',
            'nom_payeur' => 'nullable|string|max:100',
            'telephone' => 'nullable|string|max:20',
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

    public function requiresDoubleConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $echeance = EcheancePaiement::with('planPaiement.eleve')->find($args['echeance_id'] ?? null);
        $eleve = $echeance?->planPaiement?->eleve;
        $nomEleve = $eleve ? trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve) : 'élève inconnu';
        $payeur = 'inconnu';
        if (!empty($args['parent_id'])) {
            $payeur = ParentModel::find($args['parent_id'])?->nom_prenom_parent ?? 'parent inconnu';
        } elseif (!empty($args['nom_payeur'])) {
            $payeur = $args['nom_payeur'];
        }

        return sprintf(
            "💰 Je vais enregistrer un paiement de %s FCFA (%s) pour %s, échéance « %s », payé par %s. Ceci "
            . "créditera la caisse active de l'école. Confirmez-vous ?",
            number_format((float) ($args['montant_paye'] ?? 0), 0, ',', ' '),
            $args['mode_reglement'] ?? '',
            $nomEleve,
            $echeance?->libelle ?? '?',
            $payeur
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->storePaiement($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Règlement enregistré avec succès.',
            'data' => [],
        ];
    }
}
