<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Schema::hasTable / hasColumn sans interroger information_schema à chaque page.
 *
 * - Une seule entrée de cache contient toutes les réponses positives : elle est
 *   lue une fois par requête (une requête SQL quand le cache est en base de
 *   données, comme en production) et réécrite seulement quand une nouvelle
 *   table ou colonne est vue pour la première fois.
 * - Les réponses négatives ne sont jamais gardées : une table créée plus tard
 *   est vue tout de suite.
 * - Le cache est oublié après chaque « php artisan migrate » (AppServiceProvider,
 *   MigrationsEnded) et par « php artisan optimize:clear » / « cache:clear ».
 */
class SchemaExiste
{
    public const CLE = 'schema_existe';

    /** @var array<string, bool> réponses de la requête en cours (positives et négatives) */
    private static array $memoire = [];

    /** @var array<string, true>|null réponses positives lues dans le cache */
    private static ?array $positives = null;

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
        self::$positives = null;
        Cache::forget(self::CLE);
    }

    private static function verifier(string $cle, callable $verification): bool
    {
        if (isset(self::$memoire[$cle])) {
            return self::$memoire[$cle];
        }

        self::$positives ??= (array) Cache::get(self::CLE, []);
        if (isset(self::$positives[$cle])) {
            return self::$memoire[$cle] = true;
        }

        $existe = (bool) $verification();
        if ($existe) {
            self::$positives[$cle] = true;
            Cache::forever(self::CLE, self::$positives);
        }

        return self::$memoire[$cle] = $existe;
    }
}
