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
    /** Durée de validité d'un mot de passe temporaire. */
    public const DUREE_TEMPORAIRE_HEURES = 72;

    /** Champs à enregistrer pour un mot de passe temporaire : à changer à la 1re connexion, valable 72 heures. */
    public static function champsTemporaire(string $motDePasse): array
    {
        return [
            'pwd' => \Illuminate\Support\Facades\Hash::make($motDePasse),
            'doit_changer_mdp' => true,
            'mdp_temporaire_expire_at' => now()->addHours(self::DUREE_TEMPORAIRE_HEURES),
        ];
    }

    /** Mot de passe temporaire dont le délai est passé : la connexion est refusée. */
    public static function temporaireExpire(User $user): bool
    {
        return (bool) $user->doit_changer_mdp
            && $user->mdp_temporaire_expire_at
            && now()->greaterThan($user->mdp_temporaire_expire_at);
    }

    /** « 18/10/2026 à 14:30 » : fin de validité à afficher. */
    public static function finValidite(?User $user = null): string
    {
        $date = $user?->mdp_temporaire_expire_at ?? now()->addHours(self::DUREE_TEMPORAIRE_HEURES);

        return \Illuminate\Support\Carbon::parse($date)->format('d/m/Y à H:i');
    }

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
