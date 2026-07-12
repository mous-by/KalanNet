<?php

namespace App\Services\Kalanbot\Contracts;

use App\Models\User;

/**
 * Contrat commun à tous les outils Kalanbot exposés à Gemini (function calling).
 * Chaque outil enveloppe une action existante de l'application : il ne doit
 * jamais réimplémenter la logique métier, seulement l'appeler.
 */
interface KalanbotToolInterface
{
    /** Identifiant unique transmis à Gemini (ex: "evaluations_saisir_notes"). */
    public function name(): string;

    /** Module fonctionnel (ex: "evaluations") utilisé pour le routage en 2 étapes. */
    public function module(): string;

    /** Description en français destinée à Gemini pour choisir l'outil. */
    public function description(): string;

    /** Schéma des paramètres au format Gemini (OpenAPI simplifié). */
    public function parametersSchema(): array;

    /** Règles de validation Laravel appliquées aux arguments avant exécution. */
    public function validationRules(): array;

    /** Déclaration Gemini complète (name + description + parameters). */
    public function declaration(): array;

    /** True si une action d'écriture nécessite une confirmation explicite de l'utilisateur. */
    public function requiresConfirmation(): bool;

    /**
     * True pour les suppressions définitives et actions de masse : après le premier "oui",
     * une seconde confirmation stricte (mot-clé "CONFIRMER") est exigée avant exécution.
     */
    public function requiresDoubleConfirmation(): bool;

    /**
     * Autorisation CÔTÉ SERVEUR, basée sur les rôles/permissions existants.
     * Le prompt Gemini n'est jamais une barrière de sécurité : cette méthode l'est.
     */
    public function authorize(User $user): bool;

    /** Reformulation lisible de l'action à confirmer avant exécution. */
    public function confirmationMessage(array $args, User $user): string;

    /**
     * Exécute l'action en réutilisant la logique métier existante.
     * Retourne toujours ['success' => bool, 'message' => string, 'data' => mixed].
     */
    public function execute(array $args, User $user): array;
}
