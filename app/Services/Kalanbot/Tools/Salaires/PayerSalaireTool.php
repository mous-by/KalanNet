<?php

namespace App\Services\Kalanbot\Tools\Salaires;

use App\Http\Controllers\TeacherSalaryController;
use App\Models\Enseignant;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class PayerSalaireTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'salaires_payer';
    }

    public function module(): string
    {
        return 'salaires';
    }

    public function description(): string
    {
        return "Enregistrer un versement de salaire pour un enseignant, sur une période et une source données. "
            . "Utiliser salaires_consulter d'abord pour connaître le reste à payer (le montant versé ne peut pas "
            . "le dépasser) et vérifier que le solde de la caisse active est suffisant.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER'],
                'mois' => ['type' => 'STRING', 'description' => "Mois sur 2 chiffres (ex: '07')."],
                'annee' => ['type' => 'INTEGER'],
                'source' => ['type' => 'STRING', 'description' => 'emargement ou presence.'],
                'montant_verse' => ['type' => 'NUMBER', 'description' => 'Montant en FCFA, ne doit pas dépasser le reste à payer.'],
                'date_paiement' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
            ],
            'required' => ['id_enseignant', 'mois', 'annee', 'source', 'montant_verse', 'date_paiement'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'mois' => 'required|date_format:m',
            'annee' => 'required|integer|min:2000|max:2100',
            'source' => 'required|string|in:emargement,presence',
            'montant_verse' => 'required|numeric|min:1',
            'date_paiement' => 'required|date',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'emargement_paiement enseignant', 'presence_paiement enseignant',
            'emargement_paiement_enseignant', 'presence_paiement_enseignant',
            'paiements_faire',
        ]);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $enseignant = Enseignant::find($args['id_enseignant'] ?? null);
        $sourceLabel = ($args['source'] ?? '') === 'presence' ? 'cahier de présence' : 'émargements';

        return sprintf(
            "💰 Je vais enregistrer un versement de %s FCFA pour %s, salaire de %s/%s (source : %s), débité de la "
            . "caisse active de l'école. Confirmez-vous ?",
            number_format((float) ($args['montant_verse'] ?? 0), 0, ',', ' '),
            $enseignant?->nom_prenom_enseignant ?? 'enseignant inconnu',
            $args['mois'] ?? '?',
            $args['annee'] ?? '?',
            $sourceLabel
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(TeacherSalaryController::class)->storePayment($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Versement de salaire enregistré avec succès.',
            'data' => [],
        ];
    }
}
