<?php

namespace App\Support;

/**
 * Classe suivante après l'enregistrement d'une classe : « 3ème année » donne
 * « 4ème année », « 1ère année A » donne « 2ème année A ». Le cycle suit le
 * niveau quand il change (6ème -> 7ème : Fondamental II) ; au-delà, l'école
 * choisit elle-même. Au Jardin : Petite (ou Moyenne) -> Grande section -> 1ère année.
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

    /**
     * Nom proposé pour une nouvelle classe du Jardin : la première section
     * qui manque à l'école (Petite, puis Grande), sinon la lettre suivante
     * (« Petite section B », « Grande section B »...).
     */
    public static function nomJardinPropose(iterable $nomsExistants): string
    {
        $existants = collect($nomsExistants)->map(fn ($n) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $n))))->all();
        foreach (array_merge([''], range('B', 'Z')) as $lettre) {
            foreach (['Petite section', 'Grande section'] as $section) {
                $candidat = trim($section.' '.$lettre);
                $variantes = [mb_strtolower($candidat)];
                if ($lettre === '') {
                    $variantes[] = mb_strtolower($section.' A');
                }
                if (!array_intersect($variantes, $existants)) {
                    return $candidat;
                }
            }
        }

        return 'Petite section';
    }

    public static function nom(?string $nom): ?string
    {
        $section = self::section($nom);
        if ($section !== null) {
            $suite = trim((string) preg_replace('/^.*?\b(petite|moyenne|grande)\s+section\b/iu', '', (string) $nom));
            $suivante = $section < 2 ? 'grande' : null;

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

    /**
     * Ce qui distingue une classe des autres du même niveau, sans le numéro : « 7ème année B » et « 8e B » donnent « b »,
     * « 10e LL » donne « ll », « 7ème année » donne une chaîne vide.
     */
    public static function groupe(?string $nom): string
    {
        $nom = mb_strtolower(\Illuminate\Support\Str::ascii((string) $nom));
        $nom = (string) preg_replace('/^\D*\d+\s*(ere|er|re|eme|e|nde|nd)?\b\s*(annee)?/', '', $nom);

        return (string) preg_replace('/[^a-z0-9]+/', '', $nom);
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
