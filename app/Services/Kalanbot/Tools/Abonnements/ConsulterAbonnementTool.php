<?php

namespace App\Services\Kalanbot\Tools\Abonnements;

use App\Http\Controllers\AbonnementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConsulterAbonnementTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'abonnements_consulter';
    }

    public function module(): string
    {
        return 'abonnements';
    }

    public function description(): string
    {
        return "Consulter l'abonnement KalanNet de l'école : offre active, statut, historique des paiements. "
            . "Pour un SupAdmin/DAE/DCAP avec droit de validation, inclut aussi la file d'attente des paiements "
            . "manuels à valider.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'status' => ['type' => 'STRING', 'description' => "Filtrer la file d'attente admin par statut (optionnel, ex: en_attente)."],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'status' => 'nullable|string|max:20',
        ];
    }

    public function authorize(User $user): bool
    {
        return in_array($user->droit, ['SupAdmin', 'Admin', 'Gestionnaire'], true)
            || $user->userHasAnyPermission(['abonnements_apercu', 'abonnements_paiement']);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest($validated);

        $outcome = $this->callController(fn () => app(AbonnementController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $abonnement = $data['abonnement'] ?? null;

        return [
            'success' => true,
            'message' => 'Abonnement récupéré.',
            'data' => [
                'abonnement_actif' => $abonnement ? [
                    'offre' => optional($abonnement->offre)->nom,
                    'statut' => $abonnement->statut,
                    'debut' => $abonnement->debut_at,
                    'fin' => $abonnement->fin_at,
                ] : null,
                'offres_disponibles' => collect($data['offres'] ?? [])->map(fn ($o) => [
                    'id' => $o->id, 'code' => $o->code, 'nom' => $o->nom, 'montant' => (float) $o->montant, 'duree_jours' => $o->duree_jours,
                ])->values()->all(),
                'derniers_paiements' => collect($data['paiements'] ?? [])->map(fn ($p) => [
                    'reference' => $p->reference, 'offre' => optional($p->offre)->nom, 'montant' => (float) $p->montant, 'statut' => $p->statut,
                ])->values()->all(),
                'file_attente_validation' => collect($data['adminPaiements'] ?? [])->map(fn ($p) => [
                    'reference' => $p->reference, 'ecole' => optional($p->ecole)->nomEcole, 'montant' => (float) $p->montant, 'statut' => $p->statut,
                ])->values()->all(),
            ],
        ];
    }
}
