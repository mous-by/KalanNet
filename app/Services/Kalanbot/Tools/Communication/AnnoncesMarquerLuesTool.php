<?php

namespace App\Services\Kalanbot\Tools\Communication;

use App\Http\Controllers\AnnouncementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class AnnoncesMarquerLuesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'annonces_marquer_lues';
    }

    public function module(): string
    {
        return 'communication';
    }

    public function description(): string
    {
        return "Marquer comme lues toutes les annonces actuellement visibles par l'utilisateur connecté.";
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
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $outcome = $this->callController(fn () => app(AnnouncementController::class)->markVisibleAsRead());
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Annonces marquées comme lues.',
            'data' => [],
        ];
    }
}
