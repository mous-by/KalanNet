<?php

namespace App\Support;

use App\Models\Ecole;
use App\Models\Pays;
use Closure;

/**
 * Equivalent telephonique de App\Support\Devise : une ecole -> son pays ->
 * son format de numero (indicatif, longueur locale, premier chiffre
 * autorise). Remplace App\Rules\MaliPhone, qui ne connaissait que le Mali.
 *
 * Repli sur le Mali si l'ecole/le pays ne peut pas etre resolu, pour garder
 * exactement le comportement actuel partout ou aucun contexte d'ecole n'est
 * disponible (ex: identification par telephone a la connexion, revendeurs
 * qui ne sont rattaches a aucun pays precis).
 */
class Telephone
{
    public static function normalize(string $value, Ecole|Pays|int|null $ecole = null): string
    {
        $cleaned = preg_replace('/[\s\-\.]/', '', $value) ?? '';

        if ($ecole !== null) {
            return static::stripPrefix($cleaned, Devise::resolvePays($ecole));
        }

        // Contexte inconnu (ex: recherche d'un compte par telephone a la
        // connexion, avant meme de savoir a quelle ecole/pays il appartient) :
        // on essaie chaque indicatif connu, plutot que de ne reconnaitre que
        // celui du Mali.
        foreach (Pays::where('actif', true)->get() as $pays) {
            $stripped = static::stripPrefix($cleaned, $pays);
            if ($stripped !== $cleaned) {
                return $stripped;
            }
        }

        return $cleaned;
    }

    public static function validate(string $attribute, mixed $value, Closure $fail, Ecole|Pays|int|null $ecole = null): void
    {
        $pays = Devise::resolvePays($ecole);
        $cleaned = static::stripPrefix(preg_replace('/[\s\-\.]/', '', (string) $value) ?? '', $pays);

        if (!preg_match('/^[0-9]{' . $pays->telephone_longueur . '}$/', $cleaned)) {
            $fail("Le numéro de téléphone {$pays->nom} doit contenir {$pays->telephone_longueur} chiffres (indicatif {$pays->indicatif_telephone} optionnel).");
            return;
        }

        if ($pays->telephone_premier_chiffre_min !== null && (int) $cleaned[0] < (int) $pays->telephone_premier_chiffre_min) {
            $fail("Le numéro de téléphone {$pays->nom} est invalide (le préfixe ne correspond à aucun opérateur connu).");
        }
    }

    protected static function stripPrefix(string $cleaned, Pays $pays): string
    {
        $indicatif = $pays->indicatif_telephone; // ex: +223
        $indicatifSansPlus = ltrim($indicatif, '+'); // ex: 223

        if (str_starts_with($cleaned, $indicatif)) {
            return substr($cleaned, strlen($indicatif));
        }
        if (str_starts_with($cleaned, '00' . $indicatifSansPlus)) {
            return substr($cleaned, strlen('00' . $indicatifSansPlus));
        }

        return $cleaned;
    }
}
