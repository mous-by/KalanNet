<?php

namespace App\Services\Kalanbot\Tools\Programmes;

use App\Http\Controllers\ProgrammeController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ListerProgrammesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'programmes_lister';
    }

    public function module(): string
    {
        return 'programmes';
    }

    public function description(): string
    {
        return "Lister les programmes officiels (matières et leçons) regroupés par classe officielle, avec filtre optionnel.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe_officielle' => ['type' => 'INTEGER', 'description' => 'Filtrer sur une classe officielle (optionnel).'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return ['id_classe_officielle' => 'nullable|integer'];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'programmes_apercu', 'programme_apercu', 'appercu_programm',
            'programmes_creation', 'programmes_modification', 'programmes_supprimer',
        ]);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(ProgrammeController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $programmes = collect($data['programmes'] ?? [])->map(function ($rows, $idClasseOfficielle) {
            $premier = $rows->first();

            return [
                'id_classe_officielle' => (int) $idClasseOfficielle,
                'classe_officielle' => optional($premier?->classeOfficielle)->nom_classe_officielle,
                'matieres' => $rows->map(fn ($row) => [
                    'id_programme_classe' => $row->id_programme_classe,
                    'matiere' => optional($row->matiere)->nom_matiere,
                    'nombre_lecons' => $row->lecons->count(),
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'success' => true,
            'message' => count($programmes) . ' classe(s) officielle(s) avec programme.',
            'data' => ['programmes' => $programmes],
        ];
    }
}
