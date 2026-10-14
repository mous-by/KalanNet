<?php

namespace App\Support;

use ArPHP\I18N\Arabic;
use Illuminate\Support\Carbon;

/**
 * Le moteur PDF (dompdf) n'assemble pas les lettres arabes et ne sait pas
 * écrire de droite à gauche : on remplace chaque passage arabe par ses formes
 * liées, déjà mises dans l'ordre d'affichage. Le texte latin n'est pas touché.
 */
class ArabePdf
{
    private static ?Arabic $arabic = null;

    public static function texte(?string $texte): string
    {
        $texte = (string) $texte;
        if (!preg_match('/\p{Arabic}/u', $texte)) {
            return $texte;
        }

        return preg_replace_callback(
            '/\p{Arabic}[\p{Arabic}\s\x{060C}\x{061B}\x{061F}\(\)\-]*\p{Arabic}|\p{Arabic}/u',
            fn ($m) => self::arabic()->utf8Glyphs($m[0]),
            $texte
        );
    }

    /** Date hégirienne en lettres arabes, déjà mise en forme pour le PDF (ex. « 1448 »). */
    public static function anneeHegirienne(Carbon $date): string
    {
        return self::arabic()->date('Y', $date->getTimestamp());
    }

    private static function arabic(): Arabic
    {
        return self::$arabic ??= new Arabic();
    }
}
