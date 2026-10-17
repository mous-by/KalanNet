<?php

namespace App\Support;

/**
 * Code-barres Code 128 (jeu B : lettres, chiffres, ponctuation) en image SVG,
 * pour les PDF (cartes scolaires) : lisible par les lecteurs de codes-barres.
 */
class CodeBarres
{
    /** Largeurs barre / espace / barre… des 107 symboles (106 = arrêt). */
    private const MOTIFS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const DEPART_B = 104;

    private const ARRET = 106;

    /** Valeurs des symboles (départ, données, contrôle, arrêt) ; caractères hors jeu B remplacés par « ? ». */
    public static function symboles(string $texte): array
    {
        $valeurs = [self::DEPART_B];
        foreach (str_split(self::ascii($texte)) as $caractere) {
            $code = ord($caractere);
            $valeurs[] = ($code >= 32 && $code <= 126 ? $code : 63) - 32;
        }
        $controle = self::DEPART_B;
        foreach (array_slice($valeurs, 1) as $position => $valeur) {
            $controle += ($position + 1) * $valeur;
        }
        $valeurs[] = $controle % 103;
        $valeurs[] = self::ARRET;

        return $valeurs;
    }

    /** Image SVG (data URI) : $hauteur en modules, marges blanches de 10 modules de chaque côté. */
    public static function svg(string $texte, int $hauteur = 40, string $couleur = '#000000'): string
    {
        $x = 10;
        $barres = '';
        foreach (self::symboles($texte) as $valeur) {
            foreach (str_split(self::MOTIFS[$valeur]) as $i => $largeur) {
                if ($i % 2 === 0) {
                    $barres .= '<rect x="'.$x.'" y="0" width="'.$largeur.'" height="'.$hauteur.'"/>';
                }
                $x += (int) $largeur;
            }
        }
        $largeurTotale = $x + 10;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$largeurTotale.'" height="'.$hauteur.'" viewBox="0 0 '.$largeurTotale.' '.$hauteur.'">'
            .'<rect x="0" y="0" width="'.$largeurTotale.'" height="'.$hauteur.'" fill="#ffffff"/>'
            .'<g fill="'.$couleur.'">'.$barres.'</g></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private static function ascii(string $texte): string
    {
        $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte) : false;

        return $ascii !== false ? $ascii : preg_replace('/[^\x20-\x7E]/', '?', $texte);
    }
}
