<?php

namespace App\Support\Territoire;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Évaluations communes. Le circuit dépend de l'ordre d'enseignement :
 * - fondamental (1ère à 9ème, géré par les CAP) : le DAE adresse l'évaluation
 *   aux CAP (tous ou certains), chaque DCAP la publie à ses écoles (avec ses
 *   consignes s'il le souhaite) ; une école ne la voit qu'une fois publiée par
 *   son CAP. Un DCAP peut aussi créer une évaluation pour son seul CAP ;
 * - secondaire (général, technique) : le DAE l'envoie directement aux
 *   établissements de l'académie qui ont des classes de cet ordre.
 * Le sujet n'est téléchargeable par l'école qu'à partir de l'heure de libération,
 * le corrigé après la fin de l'épreuve ; chaque téléchargement est enregistré.
 * Les fichiers sont stockés hors du dossier public.
 */
class EvaluationsCommunes
{
    public const DOSSIER = 'evaluations_communes';

    /** Ordres gérés par les CAP. */
    public const ORDRES_FONDAMENTAL = ['fondamentale1', 'fondamentale2'];

    /** Le fondamental passe par les CAP ; le secondaire va directement aux établissements. */
    public static function passeParCap(object $evaluation): bool
    {
        return $evaluation->origine === 'cap'
            || $evaluation->ordre_enseignement === null
            || in_array($evaluation->ordre_enseignement, self::ORDRES_FONDAMENTAL, true);
    }

    /** Évaluations gérées ou suivies par un DAE (son académie) ou un DCAP (les siennes + celles adressées à son CAP). */
    public static function pourTerritoire(User $user): Collection
    {
        $requete = DB::table('evaluations_communes as e')->orderByDesc('e.debut_at')->select('e.*');

        if ($user->droit === 'DAE') {
            $requete->where('e.origine', 'academie')->where('e.id_academie', $user->id_academie);
        } elseif ($user->droit === 'DCAP') {
            $requete->leftJoin('evaluation_commune_caps as ec', fn ($j) => $j->on('ec.id_evaluation', '=', 'e.id')->where('ec.id_cap', '=', $user->id_cap))
                ->addSelect('ec.relayee_at', 'ec.consignes_cap', DB::raw('ec.id_cap is not null as adressee_au_cap'))
                ->where(function ($q) use ($user) {
                    $q->where(fn ($cap) => $cap->where('e.origine', 'cap')->where('e.id_cap', $user->id_cap))
                        ->orWhere(fn ($aca) => $aca->where('e.origine', 'academie')->whereNotNull('ec.id_cap'));
                });
        } else {
            return collect();
        }

        return $requete->get()->map(fn ($e) => self::enrichir($e));
    }

    /**
     * Évaluations qu'une école peut voir : celles de son CAP, celles du
     * fondamental publiées par son CAP, et celles du secondaire de son académie
     * si elle a des classes de cet ordre.
     */
    public static function pourEcole(int $idEcole): Collection
    {
        $ecole = DB::table('ecole')->where('idEcole', $idEcole)->first(['id_cap', 'id_academie']);
        if (!$ecole) {
            return collect();
        }
        $idCap = $ecole->id_cap ?: 0;
        $ordresEcole = DB::table('classe')->where('idEcole', $idEcole)->whereNotNull('ordreEnseignement')->distinct()->pluck('ordreEnseignement')
            ->reject(fn ($ordre) => in_array($ordre, self::ORDRES_FONDAMENTAL, true))->values()->all();

        return DB::table('evaluations_communes as e')
            ->leftJoin('evaluation_commune_caps as ec', fn ($j) => $j->on('ec.id_evaluation', '=', 'e.id')->where('ec.id_cap', '=', $idCap))
            ->where('e.statut', 'publiee')
            ->where(function ($q) use ($idCap, $ecole, $ordresEcole) {
                $q->where(fn ($cap) => $cap->where('e.origine', 'cap')->where('e.id_cap', $idCap))
                    ->orWhere(fn ($aca) => $aca->where('e.origine', 'academie')->whereNotNull('ec.relayee_at'))
                    ->orWhere(fn ($sec) => $sec->where('e.origine', 'academie')->where('e.id_academie', $ecole->id_academie)
                        ->whereIn('e.ordre_enseignement', $ordresEcole ?: ['__aucun__']));
            })
            ->orderByDesc('e.debut_at')
            ->get(['e.*', 'ec.relayee_at', 'ec.consignes_cap'])
            ->map(fn ($e) => self::enrichir($e));
    }

    /** CAP auxquels une évaluation d'académie est adressée, avec leur publication. */
    public static function caps(object $evaluation): Collection
    {
        return DB::table('evaluation_commune_caps as ec')
            ->join('cap as c', 'c.id_cap', '=', 'ec.id_cap')
            ->leftJoin('utilisateurs as u', 'u.idUtilisateur', '=', 'ec.relayee_par')
            ->where('ec.id_evaluation', $evaluation->id)
            ->orderBy('c.nom_cap')
            ->get(['c.id_cap', 'c.nom_cap', 'ec.relayee_at', 'u.nomPrenom as relayee_par']);
    }

    /**
     * Écoles qui ont reçu l'évaluation (CAP qui l'a publiée), avec leur accusé.
     * $seulementCap : un DCAP ne suit que ses écoles.
     */
    public static function suivi(object $evaluation, ?int $seulementCap = null): Collection
    {
        $requete = DB::table('ecole as ec')->leftJoin('cap as c', 'c.id_cap', '=', 'ec.id_cap');

        if (self::passeParCap($evaluation)) {
            $capsPublies = $evaluation->origine === 'cap'
                ? collect([$evaluation->id_cap])
                : DB::table('evaluation_commune_caps')->where('id_evaluation', $evaluation->id)->whereNotNull('relayee_at')->pluck('id_cap');
            if ($seulementCap) {
                $capsPublies = $capsPublies->intersect([$seulementCap]);
            }
            $requete->whereIn('ec.id_cap', $capsPublies);
        } else {
            // Secondaire : établissements de l'académie qui ont des classes de cet ordre.
            $requete->where('ec.id_academie', $evaluation->id_academie)
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe as cl')->whereColumn('cl.idEcole', 'ec.idEcole')->where('cl.ordreEnseignement', $evaluation->ordre_enseignement));
        }

        $ecoles = $requete
            ->orderBy('c.nom_cap')->orderBy('ec.nomEcole')
            ->get(['ec.idEcole', 'ec.nomEcole', 'c.nom_cap']);

        $accuses = DB::table('evaluation_commune_accuses')->where('id_evaluation', $evaluation->id)->get()->keyBy('id_ecole');
        $avecNiveau = $evaluation->id_classe_officielle
            ? DB::table('classe')->whereIn('idEcole', $ecoles->pluck('idEcole'))->where('id_classe_officielle', $evaluation->id_classe_officielle)->distinct()->pluck('idEcole')->flip()
            : collect();

        return $ecoles->map(fn ($e) => (object) [
            'id' => $e->idEcole,
            'nom' => $e->nomEcole,
            'cap' => $e->nom_cap,
            'a_le_niveau' => $evaluation->id_classe_officielle ? isset($avecNiveau[$e->idEcole]) : null,
            'telecharge_at' => $accuses[$e->idEcole]->telecharge_at ?? null,
        ]);
    }

    /** Créateur : le DAE de l'académie (origine académie) ou le DCAP du CAP (origine CAP). */
    public static function peutGerer(User $user, object $evaluation): bool
    {
        return ($user->droit === 'DAE' && $evaluation->origine === 'academie' && (int) $evaluation->id_academie === (int) $user->id_academie)
            || ($user->droit === 'DCAP' && $evaluation->origine === 'cap' && (int) $evaluation->id_cap === (int) $user->id_cap);
    }

    /** Un DCAP peut publier à ses écoles une évaluation de l'académie adressée à son CAP et pas encore publiée. */
    public static function aPublierParDcap(User $user, object $evaluation): bool
    {
        return $user->droit === 'DCAP'
            && $evaluation->origine === 'academie'
            && $evaluation->statut === 'publiee'
            && !empty($evaluation->adressee_au_cap)
            && empty($evaluation->relayee_at);
    }

    /** Enregistre le téléchargement d'une école. */
    public static function accuser(int $idEvaluation, int $idEcole, int $idUtilisateur): void
    {
        $existant = DB::table('evaluation_commune_accuses')->where('id_evaluation', $idEvaluation)->where('id_ecole', $idEcole)->first();
        $maintenant = now();

        DB::table('evaluation_commune_accuses')->updateOrInsert(
            ['id_evaluation' => $idEvaluation, 'id_ecole' => $idEcole],
            [
                'id_utilisateur' => $idUtilisateur,
                'consulte_at' => $existant?->consulte_at ?? $maintenant,
                'telecharge_at' => $existant?->telecharge_at ?? $maintenant,
                'updated_at' => $maintenant,
            ] + ($existant ? [] : ['created_at' => $maintenant])
        );
    }

    private static function enrichir(object $e): object
    {
        $debut = Carbon::parse($e->debut_at);
        $e->fin_at = $debut->copy()->addMinutes((int) $e->duree_minutes);
        $e->sujet_disponible = now()->greaterThanOrEqualTo(Carbon::parse($e->liberation_sujet_at));
        $e->corrige_disponible = $e->corrige_fichier && now()->greaterThanOrEqualTo($e->fin_at);
        $e->etat = $e->statut === 'annulee' ? 'Annulée'
            : (now()->lessThan(Carbon::parse($e->liberation_sujet_at)) ? 'Sujet pas encore libéré'
            : (now()->lessThan($debut) ? 'Sujet disponible'
            : (now()->lessThan($e->fin_at) ? 'En cours' : 'Terminée')));

        if ($e->origine === 'cap') {
            $e->portee = 'CAP : '.DB::table('cap')->where('id_cap', $e->id_cap)->value('nom_cap');
        } elseif (!self::passeParCap($e)) {
            $e->portee = "Établissements du secondaire de l'académie";
        } else {
            $caps = DB::table('evaluation_commune_caps')->where('id_evaluation', $e->id)->get(['relayee_at']);
            $e->portee = $caps->count().' CAP · '.$caps->whereNotNull('relayee_at')->count().' ont publié';
        }

        return $e;
    }
}
