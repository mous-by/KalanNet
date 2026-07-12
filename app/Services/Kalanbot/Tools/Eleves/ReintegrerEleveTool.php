<?php

namespace App\Services\Kalanbot\Tools\Eleves;

use App\Http\Controllers\EleveController;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ReintegrerEleveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'eleves_reintegrer';
    }

    public function module(): string
    {
        return 'eleves';
    }

    public function description(): string
    {
        return "Réintégrer dans la liste active un élève précédemment transféré, en le réaffectant à une classe "
            . "et une année scolaire.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_eleve' => ['type' => 'INTEGER', 'description' => "Identifiant de l'élève transféré à réintégrer."],
                'id_classe' => ['type' => 'INTEGER', 'description' => 'Classe de réintégration.'],
                'id_annee' => ['type' => 'INTEGER', 'description' => "Année scolaire de réintégration."],
                'motif_retour' => ['type' => 'STRING', 'description' => 'Motif du retour (optionnel).'],
            ],
            'required' => ['id_eleve', 'id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_eleve' => 'required|integer',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'motif_retour' => 'nullable|string|max:255',
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

        return "Je vais réintégrer {$nom} dans la liste active (classe id {$args['id_classe']}). Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $payload = array_merge(['motif_retour' => null], $validated);
        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(EleveController::class)->reintegrate($request, (int) $validated['id_eleve']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Élève réintégré dans la liste active avec succès.',
            'data' => [],
        ];
    }
}
