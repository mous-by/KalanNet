<?php

namespace Tests\Integration;

use App\Support\SchemaExiste;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Audit 2026-10 : vérifications de schéma en cache, invalidées après une migration. */
class SchemaExisteTest extends IntegrationTestCase
{
    public function test_cache_et_invalidation(): void
    {
        $this->assertTrue(SchemaExiste::table('eleve'));
        $this->assertFalse(SchemaExiste::table('table_qui_n_existe_pas'));
        $this->assertTrue(SchemaExiste::colonne('eleve', 'id_classe'));
        $this->assertFalse(SchemaExiste::colonne('eleve', 'colonne_absente'));

        $version = (int) Cache::get('schema_existe:version', 0);
        $this->assertNull(Cache::get("schema_existe:$version:table:table_qui_n_existe_pas"), 'une absence n\'est jamais gardée');

        DB::enableQueryLog();
        SchemaExiste::table('eleve');
        $this->assertCount(0, DB::getQueryLog(), 'deuxième vérification : aucune requête');

        event(new MigrationsEnded('up'));
        $this->assertSame($version + 1, (int) Cache::get('schema_existe:version'));
        DB::flushQueryLog();
        SchemaExiste::table('eleve');
        $this->assertNotEmpty(DB::getQueryLog(), 'après une migration : revérifié en base');
    }
}
