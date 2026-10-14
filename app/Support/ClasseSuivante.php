<?php

namespace App\Support;

/**
 * Classe suivante après l'enregistrement d'une classe : « 3ème année » donne
 * « 4ème année », « 1ère année A » donne « 2ème année A ». Le cycle suit le
 * niveau quand il change (6ème -> 7ème : Fondamental II) ; au-delà, l'école
 * choisit elle-même.
 */
class ClasseSuivante
{
    public static function niveau(?string $nom): ?int
    {
        return preg_match('/^\s*(\d{1,2})/u', (string) $nom, $m) ? (int) $m[1] : null;
    }

    public static function nom(?string $nom): ?string
    {
        $niveau = self::niveau($nom);
        if ($niveau === null || $niveau >= 12) {
            return null;
        }

        // Remplace le numéro et son suffixe (ère, er, re, ème, eme, e, è) ; garde la suite (« année A »...).
        return preg_replace('/^\s*\d{1,2}\s*(ère|ere|er|re|ème|eme|e|è)?/iu', ($niveau + 1).'ème', (string) $nom, 1);
    }

    public static function ordre(?string $ordre, ?string $nom): ?string
    {
        $suivant = (self::niveau($nom) ?? 0) + 1;

        return match (true) {
            $ordre === 'fondamentale1' && $suivant >= 7 => 'fondamentale2',
            $ordre === 'fondamentale2' && $suivant >= 10 => null,
            default => $ordre,
        };
    }
}
