<?php

namespace App\Services\Kalanbot\Tools\Classes;

use App\Http\Controllers\ClasseController;
use App\Models\Classe;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierClasseTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'classes_modifier';
    }

    public function module(): string
    {
        return 'classes';
    }

    public function description(): string
    {
        return "Modifier une classe (nom, ordre d'enseignement, matières/coefficients/enseignants). "
            . "Remplace intégralement la liste des matières : utiliser classes_fiche d'abord pour connaître "
            . "les matières actuelles si tu veux les conserver.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'nom_classe' => ['type' => 'STRING'],
                'ordre_enseignement' => ['type' => 'STRING'],
                'matieres' => [
                    'type' => 'ARRAY',
                    'description' => 'Liste complète des matières après modification.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id_matiere' => ['type' => 'INTEGER'],
                            'id_enseignant' => ['type' => 'INTEGER'],
                            'coefficient' => ['type' => 'NUMBER'],
                        ],
                        'required' => ['id_matiere', 'coefficient'],
                    ],
                ],
            ],
            'required' => ['id_classe', 'nom_classe', 'ordre_enseignement', 'matieres'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer',
            'nom_classe' => 'required|string|max:50',
            'ordre_enseignement' => 'required|string|max:50',
            'matieres' => 'required|array|min:1',
            'matieres.*.id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'matieres.*.id_enseignant' => 'nullable|integer|exists:enseignants,id_enseignant',
            'matieres.*.coefficient' => 'required|numeric|min:0|max:5',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('classes_modification');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $classe = Classe::find($args['id_classe'] ?? null);

        return sprintf(
            "Je vais modifier la classe %s : nouveau nom « %s », %d matière(s). Confirmez-vous ?",
            $classe?->nom_classe ?? 'inconnue',
            $args['nom_classe'] ?? '',
            count($args['matieres'] ?? [])
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $matieres = collect($validated['matieres']);

        $request = $this->makeRequest([
            'nom_classe' => $validated['nom_classe'],
            'ordre_enseignement' => $validated['ordre_enseignement'],
            'id_matiere' => $matieres->pluck('id_matiere')->all(),
            'id_enseignants' => $matieres->map(fn ($m) => $m['id_enseignant'] ?? null)->all(),
            'coefficient' => $matieres->pluck('coefficient')->all(),
        ]);

        $outcome = $this->callController(fn () => app(ClasseController::class)->update($request, (int) $validated['id_classe']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Classe modifiée avec succès.',
            'data' => [],
        ];
    }
}
