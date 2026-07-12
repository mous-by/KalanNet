<?php

namespace App\Services\Kalanbot\Tools\Communication;

use App\Http\Controllers\AnnouncementController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;
use Illuminate\Support\Facades\DB;

class AnnoncesPublierTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'annonces_publier';
    }

    public function module(): string
    {
        return 'communication';
    }

    public function description(): string
    {
        return "Publier une annonce existante (brouillon ou archivée) : la rend immédiatement visible au public "
            . "cible déjà défini pour cette annonce.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_annonce' => ['type' => 'INTEGER'],
            ],
            'required' => ['id_annonce'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_annonce' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('annonces_creation');
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
        $annonce = DB::table('annonces_admin_gestionnaire')->where('id_annonce', $args['id_annonce'] ?? null)->first();

        return sprintf(
            "📢 Je vais publier l'annonce « %s », la rendant immédiatement visible au public visé (%s). Confirmez-vous ?",
            $annonce?->titre ?? '?',
            $annonce?->public_cible ?? '?'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(AnnouncementController::class)->publish((int) $validated['id_annonce']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Annonce publiée.',
            'data' => [],
        ];
    }
}
