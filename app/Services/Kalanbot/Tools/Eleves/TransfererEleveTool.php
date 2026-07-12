<?php

namespace App\Services\Kalanbot\Tools\Eleves;

use App\Http\Controllers\EleveController;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class TransfererEleveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'eleves_transferer';
    }

    public function module(): string
    {
        return 'eleves';
    }

    public function description(): string
    {
        return "Transférer un élève actif vers un autre établissement (motif, destination, appréciation de conduite). "
            . "L'élève quitte la liste active et peut être réintégré plus tard.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_eleve' => ['type' => 'INTEGER', 'description' => "Identifiant de l'élève à transférer."],
                'motif' => ['type' => 'STRING', 'description' => 'Motif du transfert.'],
                'destination' => ['type' => 'STRING', 'description' => "Établissement ou lieu de destination."],
                'travail' => ['type' => 'STRING', 'description' => 'Appréciation du travail (optionnel).'],
                'conduite' => ['type' => 'STRING', 'description' => 'Appréciation de la conduite.'],
            ],
            'required' => ['id_eleve', 'motif', 'destination', 'conduite'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_eleve' => 'required|integer',
            'motif' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'travail' => 'nullable|string|max:255',
            'conduite' => 'required|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('eleves_modification');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $eleve = Eleve::find($args['id_eleve'] ?? null);
        $nom = $eleve ? trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve) : 'cet élève';

        return sprintf(
            "Je vais transférer %s vers « %s » (motif : %s). L'élève quittera la liste active. Confirmez-vous ?",
            $nom,
            $args['destination'] ?? '',
            $args['motif'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeRequest($validated);

        $outcome = $this->callController(fn () => app(EleveController::class)->transfer($request, (int) $validated['id_eleve']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Élève transféré avec succès.',
            'data' => [],
        ];
    }
}
