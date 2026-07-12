<?php

namespace App\Services\Kalanbot\Tools\Bulletins;

use App\Http\Controllers\BulletinController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class MoyennesClasseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'bulletins_moyennes_classe';
    }

    public function module(): string
    {
        return 'bulletins';
    }

    public function description(): string
    {
        return "Calculer les moyennes et le classement d'une classe pour une période (trimestre ou mois).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
                'id_trimestre' => ['type' => 'INTEGER', 'description' => "Requis sauf si mois fourni."],
                'mois' => ['type' => 'INTEGER', 'description' => '1-12, pour les classes évaluées au mois.'],
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
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'bulletins_apercu', 'bulletins_generation', 'bulletins_génération',
            'bulletins_pdf', 'bulletins_impression', 'bulletins_publication',
            'generer_bulletins', 'générer_bulletins', 'bulletins_acces_bulletin',
        ]);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest([
            'id_annee' => $validated['id_annee'],
            'id_trimestre' => $validated['id_trimestre'] ?? null,
            'mois' => $validated['mois'] ?? null,
        ]);

        $outcome = $this->callController(fn () => app(BulletinController::class)->data((int) $validated['id_classe'], $request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $rows = $this->extractJsonData($outcome['result']);

        return [
            'success' => true,
            'message' => count($rows) . ' élève(s) classé(s).',
            'data' => ['classement' => $rows],
        ];
    }
}
