<?php

namespace App\Services\Assistance;

use App\Models\Ecole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Accès d'assistance : un SupAdmin ouvre le compte d'un client pour l'aider,
 * sans son mot de passe. Chaque accès est motivé, limité dans le temps et
 * journalisé (acces_assistance, visible des seuls SupAdmin). L'école n'est
 * pas prévenue et la date de dernière connexion du client n'est pas modifiée.
 */
class AccesAssistance
{
    public const DUREE_MINUTES = 60;

    /** Rôles qu'on ne peut jamais ouvrir par l'assistance. */
    public const ROLES_EXCLUS = ['SupAdmin', 'DAE', 'DCAP'];

    /** Clés de session d'un compte connecté (voir AuthController::performLogin). */
    private const CLES_SESSION = ['idUtilisateur', 'nomPrenom', 'email', 'droit', 'idEcole', 'nomEcole', 'typeEcole', 'logoEcole'];

    public static function enCours(): ?array
    {
        $acces = session('assistance');

        return is_array($acces) ? $acces : null;
    }

    /** Un SupAdmin peut-il ouvrir ce compte ? Renvoie le motif du refus, ou null. */
    public static function refus(User $superviseur, User $cible): ?string
    {
        return match (true) {
            $superviseur->droit !== 'SupAdmin' => __('assistance.refus_role'),
            self::enCours() !== null => __('assistance.refus_deja'),
            $cible->idUtilisateur === $superviseur->idUtilisateur, in_array($cible->droit, self::ROLES_EXCLUS, true) => __('assistance.refus_cible'),
            (int) $cible->statut !== 1 => __('assistance.refus_inactif'),
            default => null,
        };
    }

    public function ouvrir(Request $request, User $superviseur, User $cible, string $motif): void
    {
        $cible->loadMissing(['enseignant', 'parent']);
        $idEcole = $cible->idEcole ?: $cible->enseignant?->id_ecole ?: $cible->parent?->idEcole;
        $ecole = $idEcole ? Ecole::withoutGlobalScopes()->find($idEcole) : null;

        $idAcces = DB::table('acces_assistance')->insertGetId([
            'id_superviseur' => $superviseur->idUtilisateur, 'id_utilisateur' => $cible->idUtilisateur, 'id_ecole' => $idEcole,
            'motif' => $motif, 'ip' => $request->ip(), 'debut_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sessionSuperviseur = $request->session()->only(self::CLES_SESSION);

        // Connexion au compte du client, sans toucher à sa date de dernière connexion.
        Auth::login($cible);
        $request->session()->put([
            'idUtilisateur' => $cible->idUtilisateur, 'nomPrenom' => $cible->nomPrenom, 'email' => $cible->email, 'droit' => $cible->droit,
            'idEcole' => $idEcole, 'nomEcole' => $ecole?->nomEcole, 'typeEcole' => $ecole?->typeEcole, 'logoEcole' => $ecole?->logoEcole,
            'assistance' => [
                'id' => $idAcces,
                'id_superviseur' => $superviseur->idUtilisateur,
                'nom_superviseur' => $superviseur->nomPrenom,
                'nom_cible' => $cible->nomPrenom,
                'nom_ecole' => $ecole?->nomEcole,
                'expire' => now()->addMinutes(self::DUREE_MINUTES)->timestamp,
                'session_superviseur' => $sessionSuperviseur,
            ],
        ]);

    }

    /** Revient au compte du SupAdmin et clôt l'accès dans le journal. */
    public function fermer(Request $request, string $raison = 'termine'): void
    {
        $acces = self::enCours();
        if (!$acces) {
            return;
        }

        DB::table('acces_assistance')->where('id', $acces['id'])->whereNull('fin_at')
            ->update(['fin_at' => now(), 'fin_raison' => $raison, 'updated_at' => now()]);

        $superviseur = User::withoutGlobalScopes()->find($acces['id_superviseur']);
        $request->session()->forget(array_merge(self::CLES_SESSION, ['assistance']));
        if ($superviseur && $raison !== 'deconnexion') {
            Auth::login($superviseur);
            $request->session()->put(array_filter($acces['session_superviseur'], fn ($v) => $v !== null));
        }
    }

    public static function expire(): bool
    {
        $acces = self::enCours();

        return $acces !== null && now()->timestamp >= (int) $acces['expire'];
    }
}
