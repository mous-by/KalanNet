<?php

namespace App\Services\Kalanbot\Tools\FinancesPaiements;

use App\Http\Controllers\FinanceController;
use App\Models\Paiement;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class AnnulerPaiementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'finances_paiements_annuler';
    }

    public function module(): string
    {
        return 'finances_paiements';
    }

    public function description(): string
    {
        return "Annuler comptablement un paiement : le montant est retiré de la caisse (contre-écriture). "
            . "Action irréversible.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_paiement' => ['type' => 'INTEGER'],
                'motif_annulation' => ['type' => 'STRING'],
            ],
            'required' => ['id_paiement', 'motif_annulation'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_paiement' => 'required|integer',
            'motif_annulation' => 'required|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('paiements_annuler');
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
        $paiement = Paiement::with('eleve')->find($args['id_paiement'] ?? null);
        $eleve = $paiement?->eleve;
        $nomEleve = $eleve ? trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve) : 'élève inconnu';
        $montant = (float) ($paiement?->montant_paye ?? $paiement?->montant ?? 0);

        return sprintf(
            "⚠️ Cette action est irréversible. Je vais annuler comptablement le paiement de %s FCFA de %s "
            . "(reçu N° %s), retiré de la caisse. Motif : « %s ». Confirmez-vous ?",
            number_format($montant, 0, ',', ' '),
            $nomEleve,
            $paiement?->numero_recu ?? '?',
            $args['motif_annulation'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(FinanceController::class)->cancelPaiement($request, (int) $validated['id_paiement']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Paiement annulé comptablement.',
            'data' => [],
        ];
    }
}
