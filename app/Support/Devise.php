<?php

namespace App\Support;

use App\Models\Ecole;
use App\Models\Pays;

/**
 * Point d'entree unique pour l'affichage monetaire : une ecole -> son pays ->
 * sa devise (symbole, decimales). Remplace les "FCFA"/number_format(...,0,...)
 * ecrits en dur, pour permettre a une ecole d'un autre pays (Guinee...) de
 * s'afficher dans sa propre devise sans toucher chaque vue individuellement.
 *
 * Repli sur le Mali si l'ecole/le pays ne peut pas etre resolu (compte
 * SupAdmin sans ecole active, ancienne donnee sans id_pays...) : c'etait le
 * comportement implicite de toute l'application avant l'existence meme de
 * cette classe, donc le comportement par defaut ne doit pas changer.
 */
class Devise
{
    protected static ?Pays $maliFallback = null;

    public static function format(float|int $montant, Ecole|int|null $ecole = null): string
    {
        return static::resolvePays($ecole)->formatMontant((float) $montant);
    }

    public static function symbole(Ecole|int|null $ecole = null): string
    {
        return static::resolvePays($ecole)->devise_symbole;
    }

    public static function code(Ecole|int|null $ecole = null): string
    {
        return static::resolvePays($ecole)->devise_code;
    }

    public static function decimales(Ecole|int|null $ecole = null): int
    {
        return static::resolvePays($ecole)->devise_decimales;
    }

    public static function resolvePays(Ecole|int|null $ecole = null): Pays
    {
        if ($ecole === null) {
            $ecole = session('idEcole');
        }
        if (is_int($ecole) || is_string($ecole)) {
            $ecole = Ecole::withoutGlobalScopes()->with('pays')->find($ecole);
        }
        if ($ecole instanceof Ecole && !$ecole->relationLoaded('pays')) {
            $ecole->load('pays');
        }

        return $ecole?->pays ?? static::mali();
    }

    public static function mali(): Pays
    {
        if (static::$maliFallback === null) {
            static::$maliFallback = Pays::where('code_iso', 'ML')->first()
                ?? new Pays([
                    'nom' => 'Mali', 'code_iso' => 'ML', 'indicatif_telephone' => '+223',
                    'telephone_longueur' => 8, 'telephone_premier_chiffre_min' => 2,
                    'devise_code' => 'XOF', 'devise_symbole' => 'FCFA', 'devise_decimales' => 0,
                ]);
        }

        return static::$maliFallback;
    }
}
