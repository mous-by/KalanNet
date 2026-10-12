<?php

namespace App\Support\Territoire;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Évaluations communes : un DAE (toute l'académie ou certains CAP) ou un DCAP
 * (son CAP) diffuse un sujet à toutes les écoles visées.
 * - Le sujet n'est téléchargeable par l'école qu'à partir de l'heure de libération.
 * - Le corrigé n'est visible par l'école qu'après la fin de l'épreuve.
 * - Chaque consultation / téléchargement d'une école est enregistré (accusé).
 * Les fichiers sont stockés hors du dossier public (storage/app/evaluations_communes).
 */
class EvaluationsCommunes
{
    public const DOSSIER = 'evaluations_communes';

    /** Évaluations gérées ou suivies par un DAE / DCAP (les siennes et, pour un DCAP, celles de l'académie qui visent son CAP). */
    public static function pourTerritoire(User $user): Collection
    {
        $requete = DB::table('evaluations_communes as e')->orderByDesc('e.debut_at');

        if ($user->droit === 'DAE') {
            $requete->where('e.origine', 'academie')->where('e.id_academie', $user->id_academie);
        } elseif ($user->droit === 'DCAP') {
            $idAcademie = DB::table('cap')->where('id_cap', $user->id_cap)->value('id_academie');
            $requete->where(function ($q) use ($user, $idAcademie) {
                $q->where(fn ($cap) => $cap->where('e.origine', 'cap')->where('e.id_cap', $user->id_cap))
                    ->orWhere(fn ($aca) => $aca->where('e.origine', 'academie')->where('e.id_academie', $idAcademie)
                        ->where(fn ($portee) => $portee->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('evaluation_commune_caps as c')->whereColumn('c.id_evaluation', 'e.id'))
                            ->orWhereExists(fn ($s) => $s->select(DB::raw(1))->from('evaluation_commune_caps as c')->whereColumn('c.id_evaluation', 'e.id')->where('c.id_cap', $user->id_cap))));
            });
        } else {
            return collect();
        }

        return $requete->get()->map(fn ($e) => self::enrichir($e));
    }

    /** Évaluations qui visent une école (non annulées). */
    public static function pourEcole(int $idEcole): Collection
    {
        $ecole = DB::table('ecole')->where('idEcole', $idEcole)->first(['id_cap', 'id_academie']);
        if (!$ecole) {
            return collect();
        }

        return DB::table('evaluations_communes as e')
            ->where('e.statut', 'publiee')
            ->where(function ($q) use ($ecole) {
                $q->where(fn ($cap) => $cap->where('e.origine', 'cap')->where('e.id_cap', $ecole->id_cap))
                    ->orWhere(fn ($aca) => $aca->where('e.origine', 'academie')->where('e.id_academie', $ecole->id_academie)
                        ->where(fn ($portee) => $portee->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('evaluation_commune_caps as c')->whereColumn('c.id_evaluation', 'e.id'))
                            ->orWhereExists(fn ($s) => $s->select(DB::raw(1))->from('evaluation_commune_caps as c')->whereColumn('c.id_evaluation', 'e.id')->where('c.id_cap', $ecole->id_cap))));
            })
            ->orderByDesc('e.debut_at')
            ->get()
            ->map(fn ($e) => self::enrichir($e));
    }

    /** Écoles visées par l'évaluation, avec leur accusé (et, pour un DCAP, seulement celles de son CAP). */
    public static function suivi(object $evaluation, ?int $seulementCap = null): Collection
    {
        $caps = DB::table('evaluation_commune_caps')->where('id_evaluation', $evaluation->id)->pluck('id_cap');

        $ecoles = DB::table('ecole as ec')
            ->leftJoin('cap as c', 'c.id_cap', '=', 'ec.id_cap')
            ->when($evaluation->origine === 'cap', fn ($q) => $q->where('ec.id_cap', $evaluation->id_cap))
            ->when($evaluation->origine === 'academie', fn ($q) => $q->where('ec.id_academie', $evaluation->id_academie)
                ->when($caps->isNotEmpty(), fn ($s) => $s->whereIn('ec.id_cap', $caps)))
            ->when($seulementCap, fn ($q) => $q->where('ec.id_cap', $seulementCap))
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
            'consulte_at' => $accuses[$e->idEcole]->consulte_at ?? null,
            'telecharge_at' => $accuses[$e->idEcole]->telecharge_at ?? null,
        ]);
    }

    /** Le DAE / DCAP connecté a créé cette évaluation (ou relève du territoire qui l'a créée). */
    public static function peutGerer(User $user, object $evaluation): bool
    {
        return ($user->droit === 'DAE' && $evaluation->origine === 'academie' && (int) $evaluation->id_academie === (int) $user->id_academie)
            || ($user->droit === 'DCAP' && $evaluation->origine === 'cap' && (int) $evaluation->id_cap === (int) $user->id_cap);
    }

    /** Enregistre la consultation / le téléchargement d'une école. */
    public static function accuser(int $idEvaluation, int $idEcole, int $idUtilisateur, bool $telechargement): void
    {
        $existant = DB::table('evaluation_commune_accuses')->where('id_evaluation', $idEvaluation)->where('id_ecole', $idEcole)->first();
        $maintenant = now();
        $valeurs = ['id_utilisateur' => $idUtilisateur, 'updated_at' => $maintenant];
        if (!$existant?->consulte_at) {
            $valeurs['consulte_at'] = $maintenant;
        }
        if ($telechargement && !$existant?->telecharge_at) {
            $valeurs['telecharge_at'] = $maintenant;
        }

        DB::table('evaluation_commune_accuses')->updateOrInsert(
            ['id_evaluation' => $idEvaluation, 'id_ecole' => $idEcole],
            $valeurs + ($existant ? [] : ['created_at' => $maintenant])
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
        $e->portee = $e->origine === 'cap'
            ? 'CAP : '.DB::table('cap')->where('id_cap', $e->id_cap)->value('nom_cap')
            : (($caps = DB::table('evaluation_commune_caps as ec')->join('cap as c', 'c.id_cap', '=', 'ec.id_cap')->where('ec.id_evaluation', $e->id)->pluck('c.nom_cap'))->isNotEmpty()
                ? 'CAP : '.$caps->implode(', ')
                : 'Toute l\'académie');

        return $e;
    }
}
