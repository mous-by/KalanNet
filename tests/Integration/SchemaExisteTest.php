<?php

namespace Tests\Integration;

use App\Support\SchemaExiste;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Audit 2026-10 : vérifications de schéma regroupées dans une seule entrée de
 * cache, invalidée après une migration ; aucun changement fonctionnel.
 */
class SchemaExisteTest extends IntegrationTestCase
{
    /** Nouvelle requête HTTP : PHP-FPM repart de variables statiques vides. */
    private function nouvelleRequete(): void
    {
        foreach (['memoire' => [], 'positives' => null] as $propriete => $valeur) {
            (new \ReflectionProperty(SchemaExiste::class, $propriete))->setValue(null, $valeur);
        }
    }

    private function requetesSchema(callable $action): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $action();
        $log = collect(DB::getQueryLog());
        DB::disableQueryLog();

        return [
            'information_schema' => $log->filter(fn ($q) => str_contains($q['query'], 'information_schema'))->count(),
            'cache' => $log->filter(fn ($q) => preg_match('/from `cache`|into `cache`/', $q['query']))->count(),
        ];
    }

    public function test_memes_reponses_que_schema(): void
    {
        SchemaExiste::oublier();
        foreach (['eleve', 'conduite', 'note_classe_choix', 'table_qui_n_existe_pas'] as $table) {
            $this->assertSame(\Illuminate\Support\Facades\Schema::hasTable($table), SchemaExiste::table($table), $table);
        }
        foreach ([['eleve', 'id_classe'], ['ligne_evaluation', 'validation_status'], ['eleve', 'colonne_absente']] as [$table, $colonne]) {
            $this->assertSame(\Illuminate\Support\Facades\Schema::hasColumn($table, $colonne), SchemaExiste::colonne($table, $colonne), "$table.$colonne");
        }
    }

    public function test_une_seule_entree_de_cache_sans_les_absences(): void
    {
        SchemaExiste::oublier();
        SchemaExiste::table('eleve');
        SchemaExiste::colonne('eleve', 'id_classe');
        SchemaExiste::table('table_qui_n_existe_pas');

        $entree = Cache::get(SchemaExiste::CLE);
        $this->assertSame(['table:eleve' => true, 'colonne:eleve.id_classe' => true], $entree);
    }

    public function test_requete_suivante_sans_information_schema(): void
    {
        SchemaExiste::oublier();
        SchemaExiste::table('eleve');
        SchemaExiste::colonne('eleve', 'id_classe');

        $this->nouvelleRequete();
        $compte = $this->requetesSchema(function () {
            SchemaExiste::table('eleve');
            SchemaExiste::colonne('eleve', 'id_classe');
            SchemaExiste::table('eleve');
        });
        $this->assertSame(0, $compte['information_schema']);
    }

    public function test_invalidation_apres_migration(): void
    {
        SchemaExiste::oublier();
        SchemaExiste::table('eleve');
        $this->assertNotNull(Cache::get(SchemaExiste::CLE));

        event(new MigrationsEnded('up'));
        $this->assertNull(Cache::get(SchemaExiste::CLE), 'entrée oubliée après une migration');

        $compte = $this->requetesSchema(fn () => SchemaExiste::table('eleve'));
        $this->assertSame(1, $compte['information_schema'], 'revérifié en base après une migration');
    }

    /** Cache en base de données, comme en production : une lecture par page. */
    public function test_cache_en_base_une_lecture_par_page(): void
    {
        config(['cache.default' => 'database']);
        Cache::forgetDriver('database');
        SchemaExiste::oublier();

        $admin = $this->adminComplet(1);
        $this->nouvelleRequete();
        $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/eleves')->assertOk(); // remplit le cache

        $this->nouvelleRequete();
        $compte = $this->requetesSchema(fn () => $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/eleves')->assertOk());
        $this->assertSame(0, $compte['information_schema'], 'aucune requête information_schema');
        $this->assertLessThanOrEqual(1, DB::table('cache')->where('key', 'like', '%'.SchemaExiste::CLE.'%')->count(), 'une seule entrée');
        $this->assertLessThanOrEqual(1, $this->lecturesEntree($admin), 'une seule lecture de l\'entrée par page');
    }

    private function lecturesEntree($admin): int
    {
        $this->nouvelleRequete();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/eleves');
        $n = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], '`cache`') && in_array(config('cache.prefix').SchemaExiste::CLE, $q['bindings'], true))->count();
        DB::disableQueryLog();

        return $n;
    }
}
