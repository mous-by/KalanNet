<?php

namespace App\Support\Territoire;

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
