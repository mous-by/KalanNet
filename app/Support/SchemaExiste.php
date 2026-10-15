<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Schema::hasTable / hasColumn sans interroger information_schema à chaque page.
 *
 * - Mémoire de la requête : une seule vérification par table ou colonne.
 * - Cache applicatif : seules les réponses positives sont gardées (une table
 *   créée plus tard est donc vue tout de suite). Le cache est invalidé à
 *   chaque « php artisan migrate » (AppServiceProvider, MigrationsEnded) et
 *   par « php artisan optimize:clear » / « cache:clear ».
 */
class SchemaExiste
{
    private const VERSION = 'schema_existe:version';

    /** @var array<string, bool> */
    private static array $memoire = [];

    private static ?int $version = null;

    public static function table(string $table): bool
    {
        return self::verifier("table:$table", fn () => Schema::hasTable($table));
    }

    public static function colonne(string $table, string $colonne): bool
    {
        return self::verifier("colonne:$table.$colonne", fn () => Schema::hasColumn($table, $colonne));
    }

    /** Après une migration : toutes les réponses gardées sont oubliées. */
    public static function oublier(): void
    {
        self::$memoire = [];
        self::$version = (int) Cache::get(self::VERSION, 0) + 1;
        Cache::forever(self::VERSION, self::$version);
    }

    private static function verifier(string $cle, callable $verification): bool
    {
        if (isset(self::$memoire[$cle])) {
            return self::$memoire[$cle];
        }

        self::$version ??= (int) Cache::get(self::VERSION, 0);
        $cleCache = 'schema_existe:'.self::$version.':'.$cle;
        if (Cache::get($cleCache) === true) {
            return self::$memoire[$cle] = true;
        }

        $existe = (bool) $verification();
        if ($existe) {
            Cache::forever($cleCache, true);
        }

        return self::$memoire[$cle] = $existe;
    }
}
