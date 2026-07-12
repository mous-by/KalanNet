<?php

namespace App\Services\Kalanbot\Tools\Programmes;

use App\Http\Controllers\ProgrammeController;
use App\Models\ClasseOfficielle;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerProgrammeTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'programmes_creer';
    }

    public function module(): string
    {
        return 'programmes';
    }

    public function description(): string
    {
        return "Créer un programme officiel (matières et leçons) pour une classe officielle.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe_officielle' => ['type' => 'INTEGER'],
                'matieres' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_matiere' => ['type' => 'INTEGER'],
                            'lecons' => [
                                'type' => 'ARRAY',
                                'description' => 'Titres des leçons, dans l\'ordre.',
                                'items' => ['type' => 'STRING'],
                            ],
                        ],
                        'required' => ['id_matiere', 'lecons'],
                    ],
                ],
            ],
            'required' => ['id_classe_officielle', 'matieres'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe_officielle' => 'required|integer|exists:classes_officielles,id_classe_officielle',
            'matieres' => 'required|array|min:1',
            'matieres.*.id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'matieres.*.lecons' => 'required|array|min:1',
            'matieres.*.lecons.*' => 'required|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['programmes_creation', 'programme_creation', 'programme_création']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = ClasseOfficielle::find($args['id_classe_officielle'] ?? null);

        return sprintf(
            "Je vais créer le programme officiel de « %s » avec %d matière(s). Confirmez-vous ?",
            $classe?->nom_classe_officielle ?? 'classe inconnue',
            count($args['matieres'] ?? [])
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        // Le contrôleur attend matieres.*.lecons.*.titre (objets), on adapte la liste de titres simples.
        $payload = $validated;
        $payload['matieres'] = collect($validated['matieres'])->map(fn ($m) => [
            'id_matiere' => $m['id_matiere'],
            'lecons' => collect($m['lecons'])->map(fn ($titre) => ['titre' => $titre])->all(),
        ])->all();

        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(ProgrammeController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Programme officiel créé avec succès.',
            'data' => [],
        ];
    }
}
