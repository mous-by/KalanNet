<?php

namespace App\Services\Kalanbot\Tools\Classes;

use App\Http\Controllers\ClasseController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class FicheClasseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'classes_fiche';
    }

    public function module(): string
    {
        return 'classes';
    }

    public function description(): string
    {
        return "Afficher le détail d'une classe : matières enseignées, coefficients, enseignants affectés.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => ['id_classe' => ['type' => 'INTEGER']],
            'required' => ['id_classe'],
        ];
    }

    public function validationRules(): array
    {
        return ['id_classe' => 'required|integer'];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('classes_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $outcome = $this->callController(fn () => app(ClasseController::class)->show((int) $validated['id_classe']));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $classe = $data['classe'] ?? null;

        $matieres = collect($classe?->ligneClasses ?? [])->map(fn ($ligne) => [
            'matiere' => optional($ligne->matiere)->nom_matiere,
            'enseignant' => optional($ligne->enseignant)->nom_prenom_enseignant,
            'coefficient' => $ligne->coefficient,
        ])->values()->all();

        return [
            'success' => true,
            'message' => 'Fiche récupérée.',
            'data' => [
                'classe' => [
                    'id_classe' => $classe?->id_classe,
                    'nom_classe' => $classe?->nom_classe,
                    'ordre_enseignement' => $classe?->ordreEnseignement,
                ],
                'matieres' => $matieres,
            ],
        ];
    }
}
