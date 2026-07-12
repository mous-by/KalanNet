<?php

namespace App\Services\Kalanbot\Tools\Presences;

use App\Http\Controllers\PresenceController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierPresenceTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'presences_modifier';
    }

    public function module(): string
    {
        return 'presences';
    }

    public function description(): string
    {
        return "Modifier une présence non encore validée (remplace intégralement ses leçons).";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_presence' => ['type' => 'INTEGER'],
                'id_enseignant' => ['type' => 'INTEGER'],
                'id_classe' => ['type' => 'INTEGER'],
                'date_presence' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'nombre_heure' => ['type' => 'NUMBER'],
                'id_trimestre' => ['type' => 'INTEGER'],
                'id_anneeScolaire' => ['type' => 'INTEGER'],
                'lecons' => [
                    'type' => 'ARRAY',
                    'description' => 'Liste complète des leçons après modification.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'titre' => ['type' => 'STRING'],
                            'nombre_heure' => ['type' => 'NUMBER'],
                            'progression' => ['type' => 'NUMBER'],
                        ],
                        'required' => ['titre', 'nombre_heure'],
                    ],
                ],
            ],
            'required' => ['id_presence', 'id_classe', 'date_presence', 'nombre_heure', 'id_trimestre', 'id_anneeScolaire', 'lecons'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_presence' => 'required|integer',
            'id_enseignant' => 'nullable|integer|exists:enseignants,id_enseignant',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'date_presence' => 'required|date',
            'nombre_heure' => 'required|numeric|min:0.1667|max:24',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'id_anneeScolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'lecons' => 'required|array|min:1',
            'lecons.*.titre' => 'required|string|max:255',
            'lecons.*.nombre_heure' => 'required|numeric|min:0.1667|max:24',
            'lecons.*.progression' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['presence_modification', 'presence_apercu']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return "Je vais modifier la présence #{$args['id_presence']} (nouveau total : " . ($args['nombre_heure'] ?? '?') . " heure(s)). Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        if (empty($validated['id_enseignant']) && $user->id_enseignant) {
            $validated['id_enseignant'] = $user->id_enseignant;
        }

        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(PresenceController::class)->update($request, (int) $validated['id_presence']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Présence modifiée avec succès.',
            'data' => [],
        ];
    }
}
