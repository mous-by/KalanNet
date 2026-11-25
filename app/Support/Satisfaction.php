<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Avis de satisfaction sur KalanNet. La question n'arrive jamais au milieu d'un travail : seulement juste après
 * une action réussie (message « succès » affiché), une fois que l'utilisateur a déjà travaillé un peu dans la
 * session, et pas plus d'une fois tous les trois mois (deux semaines après « Plus tard »).
 */
class Satisfaction
{
    /** Rôles à qui l'on demande un avis (pas le SupAdmin, ni les parents). */
    public const ROLES = ['Admin', 'Gestionnaire', 'enseignant', 'DAE', 'DCAP', 'revendeur'];

    /** Actions réussies dans la session avant de pouvoir demander. */
    public const ACTIONS_AVANT_DEMANDE = 3;

    /** Délai avant de redemander, selon la dernière réponse (jours). */
    public const DELAIS = ['repondu' => 90, 'plus_tard' => 14, 'jamais' => 180];

    /** Ce que l'on peut cocher : points forts (note 4–5) et points à améliorer (note 1–3). */
    public const POINTS_FORTS = [
        'facile' => 'Facile à utiliser',
        'temps' => 'Me fait gagner du temps',
        'bulletins' => 'Notes et bulletins',
        'finances' => 'Paiements et finances',
        'mobile' => 'Application mobile',
        'assistance' => 'Assistance de CSB',
    ];

    public const POINTS_FAIBLES = [
        'lent' => 'Lenteur',
        'difficile' => 'Difficile à comprendre',
        'manque' => 'Il manque une fonction',
        'erreurs' => 'Erreurs ou blocages',
        'affichage' => 'Affichage sur téléphone ou tablette',
        'assistance_lente' => 'Assistance trop lente',
    ];

    public const LIBELLES_NOTE = [1 => 'Très déçu', 2 => 'Déçu', 3 => 'Correct', 4 => 'Satisfait', 5 => 'Très satisfait'];

    public static function disponible(): bool
    {
        return SchemaExiste::table('avis_satisfaction');
    }

    /**
     * Faut-il proposer l'avis sur la page en cours ? Compte aussi l'action réussie qui vient d'avoir lieu.
     */
    public static function aProposer(?User $user): bool
    {
        if (!$user || !in_array($user->droit, self::ROLES, true) || !self::disponible()) {
            return false;
        }
        // Pendant un accès d'assistance, c'est le SupAdmin qui manipule le compte : pas d'avis.
        if (\App\Services\Assistance\AccesAssistance::enCours()) {
            return false;
        }
        $session = session();
        if (!$session->has('success') || $session->get('satisfaction_proposee')) {
            return false;
        }

        $actions = (int) $session->get('satisfaction_actions', 0) + 1;
        $session->put('satisfaction_actions', $actions);
        if ($actions < self::ACTIONS_AVANT_DEMANDE) {
            return false;
        }

        $dernier = DB::table('avis_satisfaction')->where('user_id', $user->idUtilisateur)->latest('created_at')->latest('id')->first();
        if ($dernier && now()->lt(\Carbon\Carbon::parse($dernier->created_at)->addDays(self::DELAIS[$dernier->statut] ?? 90))) {
            return false;
        }

        $session->put('satisfaction_proposee', true);

        return true;
    }

    /** Module de la page en cours (premier mot du nom de route), pour savoir après quelle action l'avis est donné. */
    public static function contexte(): ?string
    {
        $route = request()->route()?->getName();

        return $route ? substr(explode('.', $route)[0], 0, 60) : null;
    }

    /**
     * Chiffres du rapport sur une période.
     *
     * @return array<string, mixed>
     */
    public static function rapport(?CarbonInterface $depuis = null, ?CarbonInterface $jusqua = null): array
    {
        $avis = DB::table('avis_satisfaction as a')
            ->leftJoin('ecole as e', 'e.idEcole', '=', 'a.id_ecole')
            ->where('a.statut', 'repondu')->whereNotNull('a.note')
            ->when($depuis, fn ($q) => $q->where('a.created_at', '>=', $depuis->copy()->startOfDay()))
            ->when($jusqua, fn ($q) => $q->where('a.created_at', '<=', $jusqua->copy()->endOfDay()))
            ->get(['a.*', 'e.nomEcole']);

        $total = $avis->count();
        $repartition = collect(range(1, 5))->mapWithKeys(fn ($n) => [$n => $avis->where('note', $n)->count()])->all();
        $satisfaits = $avis->where('note', '>=', 4)->count();
        $points = fn (array $catalogue) => collect($avis)->flatMap(fn ($a) => (array) json_decode((string) $a->points, true))
            ->filter(fn ($p) => isset($catalogue[$p]))->countBy()->sortDesc()
            ->map(fn ($n, $cle) => ['libelle' => $catalogue[$cle], 'nombre' => $n])->values()->all();

        return [
            'total' => $total,
            'moyenne' => $total ? round($avis->avg('note'), 1) : null,
            'pct_satisfaits' => $total ? (int) round(100 * $satisfaits / $total) : null,
            'nb_ecoles' => $avis->pluck('id_ecole')->filter()->unique()->count(),
            'repartition' => $repartition,
            'points_forts' => $points(self::POINTS_FORTS),
            'points_faibles' => $points(self::POINTS_FAIBLES),
            'par_mois' => $avis->groupBy(fn ($a) => substr((string) $a->created_at, 0, 7))->sortKeys()
                ->map(fn (Collection $g) => ['nombre' => $g->count(), 'moyenne' => round($g->avg('note'), 1)])->all(),
            'par_role' => $avis->groupBy('droit')->map(fn (Collection $g) => ['nombre' => $g->count(), 'moyenne' => round($g->avg('note'), 1)])->all(),
            'depuis' => $depuis?->toDateString(),
            'jusqua' => $jusqua?->toDateString(),
        ];
    }
}
