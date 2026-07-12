<?php

namespace App\Services\Kalanbot\Tools\Bulletins;

use App\Http\Controllers\BulletinController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ElevesDisponiblesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'bulletins_eleves_disponibles';
    }

    public function module(): string
    {
        return 'bulletins';
    }

    public function description(): string
    {
        return "Lister les élèves d'une classe disponibles pour la génération de bulletins, pour une période donnée.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
                'id_trimestre' => ['type' => 'INTEGER'],
                'mois' => ['type' => 'INTEGER', 'description' => '1-12.'],
            ],
            'required' => ['id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer',
            'id_annee' => 'required|integer',
            'id_trimestre' => 'nullable|integer',
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

        $outcome = $this->callController(fn () => app(BulletinController::class)->studentsForBulletin((int) $validated['id_classe'], $request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $students = collect($this->extractJsonData($outcome['result']))->map(fn ($s) => [
            'id_eleve' => $s['id'],
            'nom_complet' => trim(($s['prenom'] ?? '') . ' ' . ($s['nom'] ?? '')),
            'matricule' => $s['matricule'] ?? null,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($students) . ' élève(s) disponible(s).',
            'data' => ['eleves' => $students],
        ];
    }
}
