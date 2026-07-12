<?php

namespace App\Services\Kalanbot\Tools\Communication;

use App\Http\Controllers\AnnouncementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class AnnoncesListerTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'annonces_lister';
    }

    public function module(): string
    {
        return 'communication';
    }

    public function description(): string
    {
        return "Lister les annonces de l'école (publiées, brouillons, archivées).";
    }

    public function parametersSchema(): array
    {
        return ['type' => 'OBJECT', 'properties' => []];
    }

    public function validationRules(): array
    {
        return [];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('annonces_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(AnnouncementController::class)->index());
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $paginator = $data['annonces'] ?? null;
        $items = $paginator && method_exists($paginator, 'items') ? collect($paginator->items()) : collect();

        $annonces = $items->map(fn ($a) => [
            'id_annonce' => $a->id_annonce,
            'titre' => $a->titre,
            'public_cible' => $a->public_cible,
            'statut' => $a->statut_annonce ?? null,
            'date_publication' => $a->date_publication ?? null,
            'auteur' => $a->auteur ?? null,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($annonces) . ' annonce(s) trouvée(s).',
            'data' => ['annonces' => $annonces],
        ];
    }
}
