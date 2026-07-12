<?php

namespace App\Services\Kalanbot\Tools\Abonnements;

use App\Http\Controllers\AbonnementController;
use App\Models\AbonnementPaiement;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApprouverPaiementAbonnementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'abonnements_paiement_approuver';
    }

    public function module(): string
    {
        return 'abonnements';
    }

    public function description(): string
    {
        return "Approuver un paiement d'abonnement manuel en attente : active l'abonnement de l'école "
            . "concernée. Réservé au SupAdmin ou aux comptes ayant le droit de validation des abonnements.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'reference' => ['type' => 'STRING', "description" => "Référence du paiement (obtenue via abonnements_consulter)."],
                'review_note' => ['type' => 'STRING'],
            ],
            'required' => ['reference'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'reference' => 'required|string|max:100',
            'review_note' => 'nullable|string|max:1000',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('abonnements_validation');
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
        $paiement = AbonnementPaiement::with(['ecole', 'offre'])->where('reference', $args['reference'] ?? null)->first();

        return sprintf(
            "⚠️ Je vais approuver le paiement d'abonnement de l'école %s (offre %s), ce qui activera "
            . "immédiatement son accès à KalanNet. Confirmez-vous ?",
            optional($paiement?->ecole)->nomEcole ?? 'inconnue',
            optional($paiement?->offre)->nom ?? '?'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        try {
            $paiement = AbonnementPaiement::where('reference', $validated['reference'])->firstOrFail();
        } catch (ModelNotFoundException) {
            return ['success' => false, 'error' => 'not_found', 'message' => 'Paiement introuvable pour cette référence.'];
        }

        $request = $this->makeRequest(['review_note' => $validated['review_note'] ?? null]);

        $outcome = $this->callController(fn () => app(AbonnementController::class)->approvePaiement($request, $paiement, app(\App\Services\Abonnements\AbonnementPaymentService::class)));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Paiement validé avec succès. L'abonnement est activé.",
            'data' => [],
        ];
    }
}
