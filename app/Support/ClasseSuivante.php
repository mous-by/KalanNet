<?php

namespace App\Support;

/**
 * Classe suivante après l'enregistrement d'une classe : « 3ème année » donne
 * « 4ème année », « 1ère année A » donne « 2ème année A ». Le cycle suit le
 * niveau quand il change (6ème -> 7ème : Fondamental II) ; au-delà, l'école
 * choisit elle-même. Au Jardin : Petite -> Moyenne -> Grande section -> 1ère année.
 */
class ClasseSuivante
{
    public static function niveau(?string $nom): ?int
    {
        return preg_match('/^\s*(\d{1,2})/u', (string) $nom, $m) ? (int) $m[1] : null;
    }

    /** Sections du Jardin, dans l'ordre. */
    private const SECTIONS = ['petite', 'moyenne', 'grande'];

    private static function section(?string $nom): ?int
    {
        foreach (self::SECTIONS as $i => $section) {
            if (preg_match('/\b'.$section.'\s+section\b/iu', (string) $nom)) {
                return $i;
            }
        }

        return null;
    }

    public static function nom(?string $nom): ?string
    {
        $section = self::section($nom);
        if ($section !== null) {
            $suite = trim((string) preg_replace('/^.*?\b(petite|moyenne|grande)\s+section\b/iu', '', (string) $nom));
            $suivante = self::SECTIONS[$section + 1] ?? null;

            return $suivante !== null
                ? ucfirst($suivante).' section'.($suite !== '' ? ' '.$suite : '')
                : '1ère année'.($suite !== '' ? ' '.$suite : '');
        }

        $niveau = self::niveau($nom);
        if ($niveau === null || $niveau >= 12) {
            return null;
        }

        // Remplace le numéro et son suffixe (ère, er, re, ème, eme, e, è) ; garde la suite (« année A »...).
        return preg_replace('/^\s*\d{1,2}\s*(ère|ere|er|re|ème|eme|e|è)?/iu', ($niveau + 1).'ème', (string) $nom, 1);
    }

    public static function ordre(?string $ordre, ?string $nom): ?string
    {
        if ($ordre === Ordres::JARDIN) {
            return self::section($nom) === count(self::SECTIONS) - 1 ? 'fondamentale1' : $ordre;
        }

        $suivant = (self::niveau($nom) ?? 0) + 1;

        return match (true) {
            $ordre === 'fondamentale1' && $suivant >= 7 => 'fondamentale2',
            $ordre === 'fondamentale2' && $suivant >= 10 => null,
            default => $ordre,
        };
    }
}
