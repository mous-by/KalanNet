<?php

namespace App\Support;

/**
 * Version du site actuellement en ligne : le commit Git déployé (chaque « git pull » la change). Les pages ouvertes la
 * comparent à la leur pour se mettre à jour sans que l'utilisateur ait à recharger.
 */
class VersionApp
{
    private static ?string $version = null;

    public static function actuelle(): string
    {
        return self::$version ??= self::lire();
    }

    private static function lire(): string
    {
        $git = base_path('.git');
        $head = is_file($git.'/HEAD') ? trim((string) @file_get_contents($git.'/HEAD')) : '';
        if (preg_match('/^[0-9a-f]{40}$/', $head)) {
            return substr($head, 0, 12);
        }
        if (str_starts_with($head, 'ref: ')) {
            $ref = substr($head, 5);
            $fichier = $git.'/'.$ref;
            if (is_file($fichier)) {
                return substr(trim((string) @file_get_contents($fichier)), 0, 12);
            }
            // Références regroupées (après un « git gc ») : « <commit> refs/heads/main ».
            foreach (@file($git.'/packed-refs', FILE_IGNORE_NEW_LINES) ?: [] as $ligne) {
                if (str_ends_with($ligne, ' '.$ref)) {
                    return substr($ligne, 0, 12);
                }
            }
        }

        // Sans dépôt Git : la date du dernier déploiement des dépendances.
        return 'm'.(string) @filemtime(base_path('composer.lock'));
    }
}
