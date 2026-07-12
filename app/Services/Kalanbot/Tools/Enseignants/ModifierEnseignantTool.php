<?php

namespace App\Services\Kalanbot\Tools\Enseignants;

use App\Http\Controllers\EnseignantController;
use App\Models\Enseignant;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ModifierEnseignantTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'enseignants_modifier';
    }

    public function module(): string
    {
        return 'enseignants';
    }

    public function description(): string
    {
        return "Modifier la fiche d'un enseignant. Utiliser enseignants_fiche d'abord pour connaître les "
            . "valeurs actuelles des champs que tu ne modifies pas.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_enseignant' => ['type' => 'INTEGER'],
                'nom_prenom' => ['type' => 'STRING'],
                'genre' => ['type' => 'STRING', 'description' => 'Masculin ou Féminin.'],
                'telephone' => ['type' => 'STRING'],
                'email' => ['type' => 'STRING'],
                'date_naissance' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'lieu_naissance' => ['type' => 'STRING'],
                'diplome' => ['type' => 'STRING'],
                'type_contrat' => ['type' => 'STRING', 'description' => 'FONCTIONNAIRE, VCT, CDI ou CDD.'],
                'salaire' => ['type' => 'NUMBER'],
                'salaire_mois_mode' => ['type' => 'INTEGER', 'description' => '9 ou 12.'],
                'duree_contrat' => ['type' => 'STRING'],
                'nombre_heure' => ['type' => 'NUMBER'],
                'prix_heure' => ['type' => 'NUMBER'],
                'matricule' => ['type' => 'STRING'],
                'specialite' => ['type' => 'STRING'],
            ],
            'required' => ['id_enseignant', 'nom_prenom', 'genre', 'telephone', 'date_naissance', 'lieu_naissance', 'diplome', 'type_contrat'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_enseignant' => 'required|integer',
            'nom_prenom' => 'required|string|max:200',
            'genre' => 'required|string|in:Feminin,Masculin,Féminin',
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:100',
            'diplome' => 'required|string|max:100',
            'type_contrat' => 'required|string|in:FONCTIONNAIRE,VCT,CDI,CDD',
            'salaire' => 'nullable|numeric|min:0',
            'salaire_mois_mode' => 'nullable|integer|in:9,12',
            'duree_contrat' => 'nullable|string|max:100',
            'nombre_heure' => 'nullable|numeric|min:0',
            'prix_heure' => 'nullable|numeric|min:0',
            'matricule' => 'nullable|string|max:100',
            'specialite' => 'nullable|string|max:255',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('enseignants_modification');
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        $enseignant = Enseignant::find($args['id_enseignant'] ?? null);
        $nomActuel = $enseignant?->nom_prenom_enseignant ?? 'cet enseignant';

        return "Je vais mettre à jour la fiche de {$nomActuel} : nouveau nom « " . ($args['nom_prenom'] ?? '')
            . " », contrat « " . ($args['type_contrat'] ?? '') . " ». Confirmez-vous ?";
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $payload = array_merge(['matricule' => null, 'email' => ''], $validated);
        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(EnseignantController::class)->update($request, (int) $validated['id_enseignant']));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => 'Enseignant modifié avec succès.',
            'data' => [],
        ];
    }
}
