<?php

namespace App\Services\Kalanbot\Tools\Eleves;

use App\Http\Controllers\EleveController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierEleveTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'eleves_modifier';
    }

    public function module(): string
    {
        return 'eleves';
    }

    public function description(): string
    {
        return "Modifier les informations d'un élève (identité, classe, année, statut de paiement). "
            . "Remplace l'ensemble de la fiche : utiliser eleves_fiche d'abord pour connaître les valeurs actuelles "
            . "des champs que tu ne modifies pas.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_eleve' => ['type' => 'INTEGER', 'description' => "Identifiant de l'élève."],
                'prenom_eleve' => ['type' => 'STRING'],
                'nom_eleve' => ['type' => 'STRING'],
                'genre_eleve' => ['type' => 'STRING', 'description' => 'Masculin ou Féminin.'],
                'matricule' => ['type' => 'STRING'],
                'date_naissance' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'lieu_naiss' => ['type' => 'STRING'],
                'adresse_eleve' => ['type' => 'STRING'],
                'cas_social' => ['type' => 'STRING'],
                'mode_paiement' => ['type' => 'STRING'],
                'statut_paiement' => ['type' => 'STRING', 'description' => 'normal, subventionne, boursier ou gratuit.'],
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
                'date_inscription' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
            ],
            'required' => ['id_eleve', 'prenom_eleve', 'nom_eleve', 'genre_eleve', 'id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_eleve' => 'required|integer',
            'prenom_eleve' => 'required|string|max:255',
            'nom_eleve' => 'required|string|max:255',
            'genre_eleve' => 'required|string|in:Masculin,Féminin',
            'matricule' => 'nullable|string|max:50',
            'date_naissance' => 'nullable|date',
            'lieu_naiss' => 'nullable|string|max:255',
            'adresse_eleve' => 'nullable|string|max:255',
            'cas_social' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:255',
            'statut_paiement' => 'nullable|string|in:normal,subventionne,boursier,gratuit',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_inscription' => 'nullable|date',
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
        return sprintf(
            "Je vais mettre à jour la fiche de %s %s : classe id %s, statut de paiement « %s ». Confirmez-vous ?",
            $args['prenom_eleve'] ?? '',
            $args['nom_eleve'] ?? '',
            $args['id_classe'] ?? '?',
            $args['statut_paiement'] ?? 'normal'
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);

        // Le contrôleur accède directement à certaines clés optionnelles sans "??" :
        // on les complète pour éviter des clés absentes quand Gemini les omet.
        $payload = array_merge([
            'matricule' => null,
            'lieu_naiss' => null,
            'cas_social' => null,
        ], $validated);

        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(EleveController::class)->update($request, (int) $validated['id_eleve']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Élève modifié avec succès.',
            'data' => [],
        ];
    }
}
