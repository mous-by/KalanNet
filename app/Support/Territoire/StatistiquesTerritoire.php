<?php

namespace App\Support\Territoire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres d'une académie (espace DAE) ou d'un CAP (espace DCAP), calculés sur
 * toutes leurs écoles (même logique que GESCO). Requêtes directes, sans le
 * cloisonnement par école : ces espaces couvrent plusieurs écoles.
 */
class StatistiquesTerritoire
{
    public static function academie(int $idAcademie): array
    {
        $ecoles = DB::table('ecole')->where('id_academie', $idAcademie)->pluck('idEcole');

        $parCap = DB::table('cap as c')
            ->where('c.id_academie', $idAcademie)
            ->orderBy('c.nom_cap')
            ->get(['c.id_cap', 'c.nom_cap', 'c.localite_cap'])
            ->map(function ($cap) {
                $ecolesCap = DB::table('ecole')->where('id_cap', $cap->id_cap)->pluck('idEcole');

                return (object) [
                    'id_cap' => $cap->id_cap,
                    'nom_cap' => $cap->nom_cap,
                    'localite' => $cap->localite_cap,
                    'ecoles' => $ecolesCap->count(),
                    'enseignants' => self::enseignants($ecolesCap),
                    'eleves' => self::eleves($ecolesCap),
                    'directeur' => DB::table('utilisateurs')->where('droit', 'DCAP')->where('id_cap', $cap->id_cap)->value('nomPrenom'),
                ];
            });

        return [
            'caps' => $parCap->count(),
            'ecoles' => $ecoles->count(),
            'ecoles_publiques' => DB::table('ecole')->where('id_academie', $idAcademie)->whereRaw("LOWER(TRIM(COALESCE(statut,''))) = 'public'")->count(),
            'enseignants' => self::enseignants($ecoles),
            'eleves' => self::eleves($ecoles),
            'classes' => DB::table('classe')->whereIn('idEcole', $ecoles)->count(),
            'par_cap' => $parCap,
        ];
    }

    public static function cap(int $idCap): array
    {
        $ecoles = DB::table('ecole')->where('id_cap', $idCap)->orderBy('nomEcole')->get(['idEcole', 'nomEcole', 'typeEcole', 'statut', 'adresse', 'telephone', 'email']);
        $ids = $ecoles->pluck('idEcole');

        $liste = $ecoles->map(fn ($e) => (object) [
            'id' => $e->idEcole,
            'nom' => $e->nomEcole,
            'type' => $e->typeEcole,
            'publique' => strtolower(trim((string) $e->statut)) === 'public',
            'adresse' => $e->adresse,
            'telephone' => $e->telephone,
            'email' => $e->email,
            'enseignants' => self::enseignants(collect([$e->idEcole])),
            'eleves' => self::eleves(collect([$e->idEcole])),
        ]);

        return [
            'ecoles' => $ecoles->count(),
            'ecoles_publiques' => $liste->where('publique', true)->count(),
            'ecoles_privees' => $liste->where('publique', false)->count(),
            'enseignants' => self::enseignants($ids),
            'eleves' => self::eleves($ids),
            'classes' => DB::table('classe')->whereIn('idEcole', $ids)->count(),
            'liste_ecoles' => $liste,
        ];
    }

    /**
     * Effectifs détaillés, en lecture seule : pour chaque école, ses classes
     * (filles, garçons, total d'élèves actifs) et ses enseignants (femmes, hommes).
     */
    public static function effectifs(Collection $ecoles): Collection
    {
        $ids = $ecoles->pluck('idEcole');
        $estFeminin = "LOWER(REPLACE(REPLACE(%s, 'é', 'e'), 'É', 'e')) LIKE 'f%%'";

        $eleves = DB::table('eleve')
            ->whereIn('id_ecole', $ids)
            ->where(fn ($q) => $q->where('etat_dossier', 0)->orWhereNull('etat_dossier'))
            ->groupBy('id_classe')
            ->selectRaw('id_classe, COUNT(*) as total, SUM(CASE WHEN '.sprintf($estFeminin, 'genre_eleve').' THEN 1 ELSE 0 END) as filles')
            ->get()->keyBy('id_classe');

        $classes = DB::table('classe as c')
            ->leftJoin('filieres as f', 'f.id_filiere', '=', 'c.id_filiere')
            ->whereIn('c.idEcole', $ids)
            ->orderBy('c.nom_classe')
            ->get(['c.id_classe', 'c.idEcole', 'c.nom_classe', 'c.ordreEnseignement', 'f.nom_filiere'])
            ->groupBy('idEcole');

        $enseignants = DB::table('enseignants')
            ->whereIn('id_ecole', $ids)
            ->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))
            ->groupBy('id_ecole')
            ->selectRaw('id_ecole, COUNT(*) as total, SUM(CASE WHEN '.sprintf($estFeminin, 'COALESCE(genre_enseignant, \'\')').' THEN 1 ELSE 0 END) as femmes')
            ->get()->keyBy('id_ecole');

        return $ecoles->map(function ($ecole) use ($classes, $eleves, $enseignants) {
            $lignes = ($classes[$ecole->idEcole] ?? collect())->map(function ($classe) use ($eleves) {
                $e = $eleves[$classe->id_classe] ?? null;

                return (object) [
                    'nom' => $classe->nom_classe,
                    'niveau' => $classe->nom_filiere ?? self::ORDRES[$classe->ordreEnseignement] ?? $classe->ordreEnseignement,
                    'filles' => (int) ($e->filles ?? 0),
                    'garcons' => (int) ($e->total ?? 0) - (int) ($e->filles ?? 0),
                    'total' => (int) ($e->total ?? 0),
                ];
            })->values();
            $ens = $enseignants[$ecole->idEcole] ?? null;

            return (object) [
                'id' => $ecole->idEcole,
                'nom' => $ecole->nomEcole,
                'cap' => $ecole->nom_cap ?? null,
                'publique' => strtolower(trim((string) ($ecole->statut ?? ''))) === 'public',
                'classes' => $lignes,
                'eleves' => $lignes->sum('total'),
                'filles' => $lignes->sum('filles'),
                'garcons' => $lignes->sum('garcons'),
                'enseignants' => (int) ($ens->total ?? 0),
                'enseignantes' => (int) ($ens->femmes ?? 0),
            ];
        });
    }

    private const ORDRES = [
        'jardin' => 'Jardin',
        'fondamentale1' => 'Fondamental I',
        'fondamentale2' => 'Fondamental II',
        'secondairegenerale' => 'Secondaire général',
        'secondairetechniqueetprofessionnel' => 'Secondaire technique',
    ];

    private static function enseignants($idsEcoles): int
    {
        return DB::table('enseignants')->whereIn('id_ecole', $idsEcoles)
            ->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))
            ->count();
    }

    private static function eleves($idsEcoles): int
    {
        return DB::table('eleve')->whereIn('id_ecole', $idsEcoles)
            ->where(fn ($q) => $q->where('etat_dossier', 0)->orWhereNull('etat_dossier'))
            ->count();
    }
}
