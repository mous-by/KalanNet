<?php

namespace App\Services\Assistance;

use App\Models\AppNotification;
use App\Models\Ecole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Accès d'assistance : un SupAdmin utilise le compte d'un client pour l'aider,
 * sans son mot de passe, après l'accord d'un Admin de l'école.
 *
 * 1. demander()  : demande motivée, valable 2 minutes ; les Admins de l'école
 *                  sont prévenus (fenêtre à l'écran et notification).
 * 2. repondre()  : un Admin de l'école accepte ou refuse.
 * 3. ouvrir()    : une fois acceptée (et dans les 5 minutes), le SupAdmin entre
 *                  dans le compte, au plus 60 minutes ; une seule fois.
 * 4. fermer()    : retour au compte du SupAdmin.
 * Tout est journalisé dans acces_assistance.
 */
class AccesAssistance
{
    public const DUREE_MINUTES = 60;

    public const DUREE_DEMANDE_SECONDES = 120;

    /** Délai pour entrer dans le compte une fois la demande acceptée. */
    private const DELAI_OUVERTURE_MINUTES = 5;

    /** Rôles qu'on ne peut jamais ouvrir par l'assistance. */
    public const ROLES_EXCLUS = ['SupAdmin', 'DAE', 'DCAP'];

    /** Clés de session d'un compte connecté (voir AuthController::performLogin). */
    private const CLES_SESSION = ['idUtilisateur', 'nomPrenom', 'email', 'droit', 'idEcole', 'nomEcole', 'typeEcole', 'logoEcole'];

    public static function enCours(): ?array
    {
        $acces = session('assistance');

        return is_array($acces) ? $acces : null;
    }

    public static function ecoleDe(User $cible): ?int
    {
        $cible->loadMissing(['enseignant', 'parent']);
        $id = $cible->idEcole ?: $cible->enseignant?->id_ecole ?: $cible->parent?->idEcole;

        return $id ? (int) $id : null;
    }

    /** Admins actifs de l'école, qui peuvent accepter une demande. */
    public static function approbateurs(?int $idEcole)
    {
        return $idEcole
            ? User::withoutGlobalScopes()->where('idEcole', $idEcole)->where('droit', 'Admin')->where('statut', 1)->pluck('idUtilisateur')
            : collect();
    }

    /** Un SupAdmin peut-il demander l'accès à ce compte ? Renvoie le motif du refus, ou null. */
    public static function refus(User $superviseur, User $cible): ?string
    {
        return match (true) {
            $superviseur->droit !== 'SupAdmin' => __('assistance.refus_role'),
            self::enCours() !== null => __('assistance.refus_deja'),
            $cible->idUtilisateur === $superviseur->idUtilisateur, in_array($cible->droit, self::ROLES_EXCLUS, true) => __('assistance.refus_cible'),
            (int) $cible->statut !== 1 => __('assistance.refus_inactif'),
            self::approbateurs(self::ecoleDe($cible))->isEmpty() => __('assistance.refus_sans_admin'),
            default => null,
        };
    }

    public function demander(Request $request, User $superviseur, User $cible, string $motif): int
    {
        $idEcole = self::ecoleDe($cible);
        // Une seule demande en attente à la fois pour un même compte.
        DB::table('acces_assistance')->where('id_utilisateur', $cible->idUtilisateur)->where('statut', 'en_attente')
            ->update(['statut' => 'expire', 'updated_at' => now()]);

        $id = DB::table('acces_assistance')->insertGetId([
            'id_superviseur' => $superviseur->idUtilisateur, 'id_utilisateur' => $cible->idUtilisateur, 'id_ecole' => $idEcole,
            'motif' => $motif, 'statut' => 'en_attente', 'expire_demande_at' => now()->addSeconds(self::DUREE_DEMANDE_SECONDES),
            'ip' => $request->ip(), 'debut_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (self::approbateurs($idEcole) as $idAdmin) {
            AppNotification::create([
                'user_id' => $idAdmin,
                'type' => 'acces_assistance_demande',
                'title' => __('assistance.notif_titre'),
                'message' => __('assistance.notif_message', ['compte' => $cible->nomPrenom, 'motif' => $motif]),
                'link' => '/dashboard',
            ]);
        }

        return $id;
    }

    /** La demande, avec son statut à jour (une demande sans réponse expire). */
    public static function demande(int $id): ?object
    {
        $acces = DB::table('acces_assistance')->where('id', $id)->first();
        if ($acces && $acces->statut === 'en_attente' && now()->greaterThanOrEqualTo($acces->expire_demande_at)) {
            DB::table('acces_assistance')->where('id', $id)->where('statut', 'en_attente')->update(['statut' => 'expire', 'updated_at' => now()]);
            $acces->statut = 'expire';
        }

        return $acces;
    }

    /** Demande en attente que cet Admin peut accepter (la plus récente). */
    public static function demandePour(User $admin): ?object
    {
        if ($admin->droit !== 'Admin' || self::enCours()) {
            return null;
        }
        $id = DB::table('acces_assistance')->where('id_ecole', $admin->idEcole)->where('statut', 'en_attente')
            ->where('expire_demande_at', '>', now())->orderByDesc('id')->value('id');
        if (!$id) {
            return null;
        }

        return DB::table('acces_assistance as a')->join('utilisateurs as c', 'c.idUtilisateur', '=', 'a.id_utilisateur')
            ->where('a.id', $id)->first(['a.id', 'a.motif', 'a.expire_demande_at', 'c.nomPrenom as compte', 'c.droit as role']);
    }

    /** Réponse d'un Admin de l'école. Renvoie false si la demande n'est plus en attente. */
    public function repondre(User $admin, int $id, bool $accepter): bool
    {
        $acces = self::demande($id);
        if (!$acces || $acces->statut !== 'en_attente' || $admin->droit !== 'Admin' || (int) $admin->statut !== 1
            || (int) $admin->idEcole !== (int) $acces->id_ecole) {
            return false;
        }

        return DB::table('acces_assistance')->where('id', $id)->where('statut', 'en_attente')->update([
            'statut' => $accepter ? 'accepte' : 'refuse', 'decide_par' => $admin->idUtilisateur, 'decide_at' => now(), 'updated_at' => now(),
        ]) === 1;
    }

    /** Entre dans le compte du client si la demande a été acceptée. */
    public function ouvrir(Request $request, User $superviseur, int $id): bool
    {
        $acces = self::demande($id);
        if (!$acces || $acces->statut !== 'accepte' || (int) $acces->id_superviseur !== (int) $superviseur->idUtilisateur
            || now()->diffInMinutes($acces->decide_at, true) > self::DELAI_OUVERTURE_MINUTES) {
            return false;
        }
        $cible = User::withoutGlobalScopes()->find($acces->id_utilisateur);
        if (!$cible || self::refus($superviseur, $cible)) {
            return false;
        }
        // Une seule entrée par demande.
        if (DB::table('acces_assistance')->where('id', $id)->where('statut', 'accepte')->update(['statut' => 'en_cours', 'updated_at' => now()]) !== 1) {
            return false;
        }

        $idEcole = self::ecoleDe($cible);
        $ecole = $idEcole ? Ecole::withoutGlobalScopes()->find($idEcole) : null;
        $sessionSuperviseur = $request->session()->only(self::CLES_SESSION);

        // Connexion au compte du client, sans toucher à sa date de dernière connexion.
        Auth::login($cible);
        $request->session()->put([
            'idUtilisateur' => $cible->idUtilisateur, 'nomPrenom' => $cible->nomPrenom, 'email' => $cible->email, 'droit' => $cible->droit,
            'idEcole' => $idEcole, 'nomEcole' => $ecole?->nomEcole, 'typeEcole' => $ecole?->typeEcole, 'logoEcole' => $ecole?->logoEcole,
            'assistance' => [
                'id' => $id,
                'id_superviseur' => $superviseur->idUtilisateur,
                'nom_superviseur' => $superviseur->nomPrenom,
                'nom_cible' => $cible->nomPrenom,
                'nom_ecole' => $ecole?->nomEcole,
                'expire' => now()->addMinutes(self::DUREE_MINUTES)->timestamp,
                'session_superviseur' => $sessionSuperviseur,
            ],
        ]);

        return true;
    }

    /** Revient au compte du SupAdmin et clôt l'accès dans le journal. */
    public function fermer(Request $request, string $raison = 'termine'): void
    {
        $acces = self::enCours();
        if (!$acces) {
            return;
        }

        DB::table('acces_assistance')->where('id', $acces['id'])->whereNull('fin_at')
            ->update(['statut' => 'termine', 'fin_at' => now(), 'fin_raison' => $raison, 'updated_at' => now()]);

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
