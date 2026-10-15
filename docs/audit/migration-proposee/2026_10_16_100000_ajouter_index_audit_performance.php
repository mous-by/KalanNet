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
 * InnoDB) : lectures et écritures continuent pendant la création.
 *
 * up()   : ignore un index si son nom existe déjà, si la table ou une colonne
 *          manque, ou si un index existant commence déjà par les mêmes colonnes
 *          (pas de double emploi).
 * down() : ne supprime qu'un index portant l'un de ces noms ET exactement ces
 *          colonnes, c'est-à-dire ceux que up() a créés.
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
            // Prévu par 2026_05_25_000003, jamais créé car la table existait avant.
            'idx_controle_eleve_periode' => ['id_ecole', 'id_classe', 'id_annee_scolaire', 'id_trimestre'],
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
                if (!Schema::hasTable($table) || !Schema::hasColumns($table, $colonnes)
                    || $this->colonnesIndex($table, $nom) !== null || $this->dejaCouvert($table, $colonnes)) {
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
            foreach ($index as $nom => $colonnes) {
                if (Schema::hasTable($table) && $this->colonnesIndex($table, $nom) === $colonnes) {
                    DB::statement("ALTER TABLE `$table` DROP INDEX `$nom`, ALGORITHM=INPLACE, LOCK=NONE");
                }
            }
        }
    }

    /** Colonnes de l'index (dans l'ordre), ou null s'il n'existe pas. */
    private function colonnesIndex(string $table, string $nom): ?array
    {
        $colonnes = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())->where('table_name', $table)->where('index_name', $nom)
            ->orderBy('seq_in_index')->pluck('column_name')->all();

        return $colonnes ?: null;
    }

    /** Un index existant commence-t-il déjà par ces colonnes, dans cet ordre ? */
    private function dejaCouvert(string $table, array $colonnes): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())->where('table_name', $table)
            ->orderBy('index_name')->orderBy('seq_in_index')->get(['index_name', 'column_name'])
            ->groupBy('index_name')
            ->contains(fn ($c) => array_slice($c->pluck('column_name')->all(), 0, count($colonnes)) === $colonnes);
    }
};
