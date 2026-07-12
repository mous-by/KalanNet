<?php

namespace App\Services\Kalanbot\Tools\Enseignants;

use App\Http\Controllers\EnseignantController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class CreerEnseignantTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'enseignants_creer';
    }

    public function module(): string
    {
        return 'enseignants';
    }

    public function description(): string
    {
        return "Créer une fiche enseignant. Un mot de passe temporaire (123456) est généré automatiquement.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'nom_prenom' => ['type' => 'STRING'],
                'genre' => ['type' => 'STRING', 'description' => 'Masculin ou Féminin.'],
                'telephone' => ['type' => 'STRING', 'description' => 'Numéro malien.'],
                'email' => ['type' => 'STRING'],
                'date_naissance' => ['type' => 'STRING', 'description' => 'Format AAAA-MM-JJ.'],
                'lieu_naissance' => ['type' => 'STRING'],
                'diplome' => ['type' => 'STRING'],
                'type_contrat' => ['type' => 'STRING', 'description' => 'FONCTIONNAIRE, VCT, CDI ou CDD selon le type d\'école.'],
                'salaire' => ['type' => 'NUMBER', 'description' => 'Salaire mensuel (CDI/CDD).'],
                'salaire_mois_mode' => ['type' => 'INTEGER', 'description' => '9 ou 12 mois (CDI/CDD).'],
                'duree_contrat' => ['type' => 'STRING', 'description' => 'Durée du contrat (CDD).'],
                'nombre_heure' => ['type' => 'NUMBER', 'description' => 'Volume horaire hebdomadaire (VCT).'],
                'prix_heure' => ['type' => 'NUMBER', 'description' => 'Prix par heure (VCT).'],
                'matricule' => ['type' => 'STRING', 'description' => 'Laisser vide pour génération automatique.'],
                'specialite' => ['type' => 'STRING'],
            ],
            'required' => ['nom_prenom', 'genre', 'telephone', 'date_naissance', 'lieu_naissance', 'diplome', 'type_contrat'],
        ];
    }

    public function validationRules(): array
    {
        return [
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
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['enseignants_creation', 'enseignants_création']);
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return sprintf(
            "Je vais créer l'enseignant %s (%s), téléphone %s. Un mot de passe temporaire sera généré. Confirmez-vous ?",
            $args['nom_prenom'] ?? '',
            $args['type_contrat'] ?? '',
            $args['telephone'] ?? ''
        );
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        // La colonne email_enseignant est NOT NULL en base malgré la règle "nullable" :
        // le formulaire web envoie toujours une chaîne vide plutôt qu'une clé absente.
        $payload = array_merge(['matricule' => null, 'email' => ''], $validated);
        $request = $this->makeRequest($payload);

        $outcome = $this->callController(fn () => app(EnseignantController::class)->store($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        return [
            'success' => true,
            'message' => "Enseignant {$validated['nom_prenom']} créé avec succès (mot de passe temporaire : 123456).",
            'data' => [],
        ];
    }
}
