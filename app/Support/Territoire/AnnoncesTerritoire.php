<?php

namespace App\Support\Territoire;

use App\Support\SchemaExiste;
use App\Models\Ecole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Annonces de l'académie (publiées par le DAE) et du CAP (publiées par le
 * DCAP), diffusées aux écoles de leur territoire selon le public ciblé.
 * Les annonces sont renvoyées sous une forme commune (« id_annonce » préfixé
 * par la source pour ne jamais se confondre avec celles d'une école).
 */
class AnnoncesTerritoire
{
    /** Publics proposés : clé enregistrée => libellé. « dcap » : annonces d'académie seulement. */
    public const CIBLES = [
        'tous' => 'Tout le monde',
        'admin' => 'Administrateurs (promoteurs)',
        'gestionnaires' => 'Gestionnaires',
        'enseignants' => 'Enseignants',
        'parents' => 'Parents',
        'dcap' => 'Directeurs de CAP (DCAP)',
    ];

    /** Type enregistré dans annonces_lues pour chaque source. */
    private const TYPE_LECTURE = ['academie' => 'ACADEMIE', 'cap' => 'CAP'];

    /** Publics qui concernent cet utilisateur. */
    public static function ciblesDe(User $user): array
    {
        return array_values(array_filter(['tous', match ($user->droit) {
            'Admin' => 'admin',
            'Gestionnaire' => 'gestionnaires',
            'enseignant' => 'enseignants',
            'parent' => 'parents',
            'DCAP' => 'dcap',
            default => null,
        }]));
    }

    /**
     * Annonces visibles par un utilisateur : celles du CAP et de l'académie de
     * son école ; pour un DCAP, celles de l'académie de son CAP.
     */
    public static function visibles(User $user, ?int $schoolId, ?int $limite = null, bool $nonLuesSeulement = false): Collection
    {
        [$idCap, $idAcademie] = self::territoireDe($user, $schoolId);
        $cibles = self::ciblesDe($user);
        $annonces = collect();

        if ($idCap && $user->droit !== 'DCAP') {
            $annonces = $annonces->merge(self::requete('cap', 'id_cap', $idCap, $cibles, $user, $nonLuesSeulement));
        }
        if ($idAcademie) {
            $annonces = $annonces->merge(self::requete('academie', 'id_academie', $idAcademie, $cibles, $user, $nonLuesSeulement));
        }

        $annonces = $annonces->sortByDesc('date_publication')->values();

        return $limite ? $annonces->take($limite)->values() : $annonces;
    }

    /** Marque comme lues toutes les annonces territoriales visibles non lues. */
    public static function marquerLues(User $user, ?int $schoolId): void
    {
        if (!SchemaExiste::table('annonces_lues')) {
            return;
        }

        foreach (self::visibles($user, $schoolId, null, true) as $annonce) {
            DB::table('annonces_lues')->updateOrInsert(
                ['id_utilisateur' => $user->idUtilisateur, 'id_annonce' => $annonce->id, 'type_annonce' => self::TYPE_LECTURE[$annonce->source]],
                ['date_lecture' => now()]
            );
        }
    }

    /** Annonces publiées sur le territoire du DAE ou du DCAP connecté (pour son espace). */
    public static function publiees(User $user): Collection
    {
        [$table, $colonne, $id] = self::tablePour($user);
        if (!$table || !$id || !SchemaExiste::table($table)) {
            return collect();
        }

        return DB::table($table.' as a')
            ->leftJoin('utilisateurs as u', 'u.idUtilisateur', '=', 'a.id_utilisateur')
            ->where('a.'.$colonne, $id)
            ->orderByDesc('a.date_publication')
            ->limit(30)
            ->get(['a.*', 'u.nomPrenom as auteur']);
    }

    /** [table, colonne, id] des annonces que l'utilisateur peut publier (DAE : académie ; DCAP : CAP). */
    public static function tablePour(User $user): array
    {
        return match ($user->droit) {
            'DAE' => ['annonces_academie', 'id_academie', $user->id_academie],
            'DCAP' => ['annonces_cap', 'id_cap', $user->id_cap],
            default => [null, null, null],
        };
    }

    private static function territoireDe(User $user, ?int $schoolId): array
    {
        if ($user->droit === 'DCAP') {
            return [$user->id_cap, $user->id_cap ? DB::table('cap')->where('id_cap', $user->id_cap)->value('id_academie') : null];
        }
        if (!$schoolId) {
            return [null, null];
        }
        $ecole = Ecole::withoutGlobalScopes()->find($schoolId, ['idEcole', 'id_cap', 'id_academie']);

        return [$ecole?->id_cap, $ecole?->id_academie];
    }

    private static function requete(string $source, string $colonne, int $id, array $cibles, User $user, bool $nonLuesSeulement): Collection
    {
        $table = 'annonces_'.$source;
        if (!SchemaExiste::table($table)) {
            return collect();
        }

        $nomTerritoire = $source === 'cap'
            ? DB::table('cap')->where('id_cap', $id)->value('nom_cap')
            : DB::table('academie')->where('id_academie', $id)->value('nom_academie');

        return DB::table($table.' as a')
            ->leftJoin('utilisateurs as u', 'u.idUtilisateur', '=', 'a.id_utilisateur')
            ->where('a.'.$colonne, $id)
            ->whereIn('a.public_cible', $cibles)
            ->when(SchemaExiste::colonne($table, 'statut_annonce'), fn ($q) => $q->where(fn ($s) => $s->whereNull('a.statut_annonce')->orWhere('a.statut_annonce', 'active')))
            ->when($nonLuesSeulement && SchemaExiste::table('annonces_lues'), fn ($q) => $q->whereNotExists(fn ($lues) => $lues->select(DB::raw(1))
                ->from('annonces_lues as l')
                ->whereColumn('l.id_annonce', 'a.id_annonce')
                ->where('l.id_utilisateur', $user->idUtilisateur)
                ->where('l.type_annonce', self::TYPE_LECTURE[$source])))
            ->orderByDesc('a.date_publication')
            ->limit(20)
            ->get(['a.*', 'u.nomPrenom as auteur'])
            ->map(function ($a) use ($source, $nomTerritoire) {
                $a->id = $a->id_annonce;
                $a->id_annonce = $source.'-'.$a->id_annonce;
                $a->source = $source;
                $a->source_label = ($source === 'cap' ? 'CAP : ' : 'Académie : ').$nomTerritoire;
                $a->public_cible_label = self::CIBLES[$a->public_cible] ?? $a->public_cible;

                return $a;
            });
    }
}
