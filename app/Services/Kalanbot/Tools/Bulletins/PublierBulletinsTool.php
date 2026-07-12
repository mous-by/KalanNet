<?php

namespace App\Services\Kalanbot\Tools\Bulletins;

use App\Http\Controllers\BulletinController;
use App\Models\Classe;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class PublierBulletinsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'bulletins_publier';
    }

    public function module(): string
    {
        return 'bulletins';
    }

    public function description(): string
    {
        return "Publier les bulletins d'une classe pour une période : les parents concernés pourront les "
            . "consulter et les télécharger immédiatement.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
                'id_trimestre' => ['type' => 'INTEGER', 'description' => "Requis sauf si mois fourni."],
                'mois' => ['type' => 'INTEGER', 'description' => '1-12.'],
            ],
            'required' => ['id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'nullable|integer|exists:trimestre,id_trimestre',
            'mois' => 'nullable|integer|between:1,12',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('bulletins_publication');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = Classe::find($args['id_classe'] ?? null);

        return sprintf(
            "📢 Je vais publier les bulletins de la classe %s pour cette période : les parents pourront les "
            . "consulter et les télécharger immédiatement. Confirmez-vous ?",
            $classe?->nom_classe ?? 'inconnue'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest(array_merge(['mois' => null, 'id_trimestre' => null], $validated));

        $outcome = $this->callController(fn () => app(BulletinController::class)->publishClassBulletins((int) $validated['id_classe'], $request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Bulletins publiés pour les parents.',
            'data' => [],
        ];
    }
}
