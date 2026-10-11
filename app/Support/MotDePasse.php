<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Règles des mots de passe (même modèle que Gestion_magasin) :
 * - au moins 8 caractères, avec des lettres et des chiffres ;
 * - un compte créé ou réinitialisé par un Admin reçoit un mot de passe
 *   temporaire, affiché une seule fois, à changer à la première connexion ;
 * - un changement de mot de passe coupe les autres accès du compte.
 */
class MotDePasse
{
    public static function regle(): Password
    {
        return Password::min(8)->letters()->numbers();
    }

    /** 12 caractères, lettres et chiffres, sans caractères ambigus (0/O, 1/l/I). */
    public static function temporaire(): string
    {
        $lettres = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $chiffres = '23456789';
        $tous = $lettres.$chiffres;

        $caracteres = [$lettres[random_int(0, strlen($lettres) - 1)], $chiffres[random_int(0, strlen($chiffres) - 1)]];
        while (count($caracteres) < 12) {
            $caracteres[] = $tous[random_int(0, strlen($tous) - 1)];
        }
        shuffle($caracteres);

        return implode('', $caracteres);
    }

    /**
     * Coupe les accès du compte : jetons de l'app mobile et, si les sessions
     * web sont en base, sessions web (sauf celle/celui qu'on garde).
     */
    public static function revoquerAcces(User $user, ?string $sessionGardee = null, ?int $jetonGarde = null): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->getKey())
            ->when($jetonGarde, fn ($q) => $q->where('id', '!=', $jetonGarde))
            ->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->when($sessionGardee, fn ($q) => $q->where('id', '!=', $sessionGardee))
                ->delete();
        }
    }
}
