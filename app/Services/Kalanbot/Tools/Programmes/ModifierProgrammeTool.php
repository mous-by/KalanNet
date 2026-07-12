<?php

namespace App\Services\Kalanbot\Tools\Programmes;

use App\Http\Controllers\ProgrammeController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierProgrammeTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'programmes_modifier';
    }

    public function module(): string
    {
        return 'programmes';
    }

    public function description(): string
    {
        return "Modifier un programme officiel (remplace intégralement matières et leçons : supprime les "
            . "anciennes et recrée les nouvelles). Utiliser programmes_lister d'abord pour connaître le contenu actuel.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_programme' => ['type' => 'INTEGER'],
                'id_classe_officielle' => ['type' => 'INTEGER'],
                'matieres' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_matiere' => ['type' => 'INTEGER'],
                            'lecons' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        ],
                        'required' => ['id_matiere', 'lecons'],
                    ],
                ],
            ],
            'required' => ['id_programme', 'id_classe_officielle', 'matieres'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_programme' => 'required|integer',
            'id_classe_officielle' => 'required|integer|exists:classes_officielles,id_classe_officielle',
            'matieres' => 'required|array|min:1',
            'matieres.*.id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'matieres.*.lecons' => 'required|array|min:1',
            'matieres.*.lecons.*' => 'required|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['programmes_modification', 'programme_modification', 'programme_modifier']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return "⚠️ Ceci va remplacer toutes les matières et leçons du programme #" . ($args['id_programme'] ?? '?')
            . " par " . count($args['matieres'] ?? []) . " matière(s). Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        $payload = $validated;
        $payload['matieres'] = collect($validated['matieres'])->map(fn ($m) => [
            'id_matiere' => $m['id_matiere'],
            'lecons' => collect($m['lecons'])->map(fn ($titre) => ['titre' => $titre])->all(),
        ])->all();

        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(ProgrammeController::class)->update($request, (int) $validated['id_programme']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Programme officiel modifié avec succès.',
            'data' => [],
        ];
    }
}
