<?php

/*
 * MIGRATION PROPOSÉE — NON ACTIVE.
 *
 * Elle est rangée dans docs/audit/ pour ne PAS être exécutée par
 * « php artisan migrate ». Après validation (voir docs/audit/index-base-de-donnees.md) :
 *   1. sauvegarde de la base ;
 *   2. exécuter docs/audit/verifications-avant-index.sql (lecture seule) ;
 *   3. copier ce fichier dans database/migrations/ puis « php artisan migrate » ;
 *   4. retour arrière éventuel : « php artisan migrate:rollback --step=1 ».
 *
 * Uniquement des index NON uniques : aucune donnée n'est modifiée ni refusée.
 * Chaque index est créé en ligne (ALGORITHM=INPLACE, LOCK=NONE, MariaDB 10.4 /
 * InnoDB) : lectures et écritures continuent pendant la création. Un index
 * déjà présent, ou dont la table / une colonne manque, est ignoré.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** table => [nom de l'index => colonnes] */
    private const INDEX = [
        'utilisateurs' => [
            'idx_utilisateurs_email' => ['email'],
            'idx_utilisateurs_telephone' => ['telephone'],
            'idx_utilisateurs_ecole' => ['idEcole'],
        ],
        'eleve' => [
            'idx_eleve_classe_annee' => ['id_classe', 'id_annee', 'etat_dossier'],
            'idx_eleve_ecole_annee' => ['id_ecole', 'id_annee', 'etat_dossier'],
        ],
        'matiere' => [
            'idx_matiere_ecole' => ['id_ecole'],
        ],
        'matiere_ordre' => [
            'idx_matiere_ordre_matiere' => ['id_matiere', 'ordre_enseignement'],
        ],
        'controle_eleve' => [
            // Index déjà prévus par 2026_05_25_000003, jamais créés car la table existait avant.
            'idx_controle_eleve_periode' => ['id_ecole', 'id_classe', 'id_annee_scolaire', 'id_trimestre'],
            'idx_controle_eleve_eleve' => ['id_eleve', 'id_controle'],
        ],
        'conduite' => [
            'idx_conduite_periode' => ['id_classe', 'id_annee_scolaire', 'id_trimestre', 'id_eleve'],
        ],
        'ligne_reinscription' => [
            'idx_ligne_reinscription_eleve_annee' => ['id_eleve', 'id_annee'],
        ],
        // Pas d'index supplémentaire sur ligne_evaluation : mesuré sans gain en lecture
        // (l'index existant sur id_eleve suffit) et coûteux en écriture.
    ];

    public function up(): void
    {
        foreach (self::INDEX as $table => $index) {
            foreach ($index as $nom => $colonnes) {
                if (!Schema::hasTable($table) || Schema::hasIndex($table, $nom) || !Schema::hasColumns($table, $colonnes)) {
                    continue;
                }
                $liste = implode(', ', array_map(fn ($c) => "`$c`", $colonnes));
                DB::statement("ALTER TABLE `$table` ADD INDEX `$nom` ($liste), ALGORITHM=INPLACE, LOCK=NONE");
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEX as $table => $index) {
            foreach (array_keys($index) as $nom) {
                if (Schema::hasTable($table) && Schema::hasIndex($table, $nom)) {
                    DB::statement("ALTER TABLE `$table` DROP INDEX `$nom`, ALGORITHM=INPLACE, LOCK=NONE");
                }
            }
        }
    }
};
