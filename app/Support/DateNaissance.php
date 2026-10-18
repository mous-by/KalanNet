<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Date de naissance d'un élève. Stockée soit en AAAA-MM-JJ, soit en
 * « vers AAAA » quand seule l'année est connue (élève « né vers 2010 »).
 *
 * Saisies acceptées : 22/04/2009, 22-04-2009, 22.04.2009, 2009-04-22,
 * 22/avr/2009, 22 avril 2009, 22-Apr-2009, vers 2009, né(e) vers 2009,
 * v. 2009, 2009 (année seule), ou une date Excel.
 */
class DateNaissance
{
    public const PREFIXE_VERS = 'vers ';

    private const MOIS = [
        'janvier' => 1, 'janv' => 1, 'jan' => 1, 'january' => 1,
        'fevrier' => 2, 'fevr' => 2, 'fev' => 2, 'feb' => 2, 'february' => 2,
        'mars' => 3, 'mar' => 3, 'march' => 3,
        'avril' => 4, 'avr' => 4, 'apr' => 4, 'april' => 4,
        'mai' => 5, 'may' => 5,
        'juin' => 6, 'jun' => 6, 'june' => 6,
        'juillet' => 7, 'juil' => 7, 'jul' => 7, 'july' => 7,
        'aout' => 8, 'aou' => 8, 'aug' => 8, 'august' => 8,
        'septembre' => 9, 'sept' => 9, 'sep' => 9, 'september' => 9,
        'octobre' => 10, 'oct' => 10, 'october' => 10,
        'novembre' => 11, 'nov' => 11, 'november' => 11,
        'decembre' => 12, 'dec' => 12, 'december' => 12,
    ];

    /**
     * Valeur à enregistrer (AAAA-MM-JJ ou « vers AAAA »), null si vide.
     *
     * @throws ValidationException si la saisie n'est pas reconnue.
     */
    public static function normaliser(mixed $valeur, string $champ = 'date_naissance'): ?string
    {
        if ($valeur instanceof \DateTimeInterface) {
            return self::date((int) $valeur->format('Y'), (int) $valeur->format('m'), (int) $valeur->format('d'), $champ);
        }

        $texte = trim((string) $valeur);
        if ($texte === '') {
            return null;
        }

        // Année seule (aussi quand Excel l'a lue comme un nombre).
        if (preg_match('/^\d{4}$/', $texte)) {
            return self::annee((int) $texte, $champ);
        }

        // Date Excel (numéro de série).
        if (is_numeric($texte)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $texte);
            } catch (\Throwable) {
                throw self::erreur($champ);
            }

            return self::date((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'), $champ);
        }

        $simple = Str::lower(Str::ascii($texte));
        $simple = preg_replace('/\s+/', ' ', $simple) ?? $simple;

        // « vers 2010 », « né vers 2010 », « née vers 2010 », « v. 2010 », « env. 2010 », « ~2010 », « around 2010 ».
        if (preg_match('/^(?:nee?\(?e?\)? )?(?:vers|v\.?|env\.?|environ|~|around|circa|ca\.?) ?(\d{4})$/', $simple, $m)) {
            return self::annee((int) $m[1], $champ);
        }

        // JJ/MM/AAAA (séparateurs / - . ou espace).
        if (preg_match('#^(\d{1,2})[/.\- ](\d{1,2})[/.\- ](\d{4})$#', $simple, $m)) {
            return self::date((int) $m[3], (int) $m[2], (int) $m[1], $champ);
        }

        // AAAA-MM-JJ ou AAAA/MM/JJ.
        if (preg_match('#^(\d{4})[/.\-](\d{1,2})[/.\-](\d{1,2})$#', $simple, $m)) {
            return self::date((int) $m[1], (int) $m[2], (int) $m[3], $champ);
        }

        // Mois en lettres : 22/avr/2009, 22 avril 2009, 22-Apr-2009, 1er mai 2009.
        if (preg_match('#^(\d{1,2})(?:er)?[/.\- ]([a-z]+)\.?[/.\- ](\d{4})$#', $simple, $m) && isset(self::MOIS[$m[2]])) {
            return self::date((int) $m[3], self::MOIS[$m[2]], (int) $m[1], $champ);
        }

        throw self::erreur($champ);
    }

    /** Règle de validation Laravel acceptant tous les formats ci-dessus. */
    public static function regle(): \Closure
    {
        return function (string $attribut, mixed $valeur, \Closure $echec) {
            try {
                self::normaliser($valeur, $attribut);
            } catch (ValidationException) {
                $echec(__('eleves.date_naissance_invalide'));
            }
        };
    }

    public static function estApproximative(?string $valeur): bool
    {
        return str_starts_with((string) $valeur, self::PREFIXE_VERS);
    }

    /** Affichage : 22/04/2009, ou « Vers 2009 ». */
    public static function afficher(?string $valeur, ?string $vide = ''): ?string
    {
        $valeur = trim((string) $valeur);
        if ($valeur === '') {
            return $vide;
        }

        if (self::estApproximative($valeur)) {
            return __('eleves.ne_vers', ['annee' => substr($valeur, strlen(self::PREFIXE_VERS))]);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valeur, $m)) {
            return "{$m[3]}/{$m[2]}/{$m[1]}";
        }

        // Anciennes saisies libres : affichées telles quelles plutôt que de planter.
        return $valeur;
    }

    /** Valeur à mettre dans un champ texte de formulaire. */
    public static function pourSaisie(?string $valeur): string
    {
        $valeur = trim((string) $valeur);
        if (self::estApproximative($valeur)) {
            return $valeur;
        }

        return (string) self::afficher($valeur);
    }

    private static function annee(int $annee, string $champ): string
    {
        if ($annee < 1900 || $annee > (int) date('Y')) {
            throw self::erreur($champ);
        }

        return self::PREFIXE_VERS . $annee;
    }

    private static function date(int $annee, int $mois, int $jour, string $champ): string
    {
        if (!checkdate($mois, $jour, $annee) || $annee < 1900 || sprintf('%04d-%02d-%02d', $annee, $mois, $jour) > date('Y-m-d')) {
            throw self::erreur($champ);
        }

        return sprintf('%04d-%02d-%02d', $annee, $mois, $jour);
    }

    private static function erreur(string $champ): ValidationException
    {
        return ValidationException::withMessages([$champ => __('eleves.date_naissance_invalide')]);
    }
}
