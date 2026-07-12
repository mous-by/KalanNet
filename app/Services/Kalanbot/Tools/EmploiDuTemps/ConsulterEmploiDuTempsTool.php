<?php

namespace App\Services\Kalanbot\Tools\EmploiDuTemps;

use App\Http\Controllers\TimetableController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConsulterEmploiDuTempsTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'emploi_du_temps_consulter';
    }

    public function module(): string
    {
        return 'emploi_du_temps';
    }

    public function description(): string
    {
        return "Consulter l'emploi du temps d'une classe pour une année scolaire (liste des cours par jour).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER', 'description' => "Identifiant de l'année scolaire."],
            ],
            'required' => ['id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer',
            'id_annee' => 'required|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->droit === 'enseignant' || $user->userHasPermission('planning_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        // TimetableController::index() lit la sélection classe/année depuis la session
        // (remplie normalement par un POST de filtre) : on la positionne nous-mêmes.
        session([
            'timetable_id_classe' => $validated['id_classe'],
            'timetable_id_annee' => $validated['id_annee'],
        ]);

        $outcome = $this->callController(fn () => app(TimetableController::class)->index($this->makeGetRequest()));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $timetable = collect($data['timetable'] ?? []);

        $cours = $timetable->flatMap(function ($items, $jour) {
            return collect($items)->map(fn ($c) => [
                'jour' => $jour,
                'matiere' => optional($c->matiere)->nom_matiere,
                'enseignant' => optional($c->enseignant)->nom_prenom_enseignant,
                'heure_debut' => $c->heure_debut,
                'heure_fin' => $c->heure_fin,
                'id' => $c->id,
            ]);
        })->values()->all();

        return [
            'success' => true,
            'message' => count($cours) . ' cours trouvé(s).',
            'data' => ['cours' => $cours],
        ];
    }
}
