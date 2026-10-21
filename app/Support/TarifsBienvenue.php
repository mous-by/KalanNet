<?php

namespace App\Support;

use App\Models\Ecole;
use Illuminate\Support\Facades\DB;

/**
 * Tarifs annoncés à l'Admin d'une nouvelle école (message de bienvenue) : frais
 * d'adhésion la première année, puis l'abonnement annuel. Chaque école ne voit
 * que ceux de son statut (public ou privé).
 */
class TarifsBienvenue
{
    /** Montants en F CFA. L'abonnement annuel vient des offres d'abonnement s'il y en a une. */
    public const TARIFS = [
        'public' => ['adhesion' => 15000, 'annuel' => 10000],
        'prive' => ['adhesion' => 75000, 'annuel' => 25000],
    ];

    public static function statut(?Ecole $ecole): string
    {
        return strtolower(trim((string) $ecole?->statut)) === 'public' ? 'public' : 'prive';
    }

    /** @return array{statut: string, adhesion: int, annuel: int, premiere_annee: int} */
    public static function pour(?Ecole $ecole): array
    {
        $statut = self::statut($ecole);
        $tarif = self::TARIFS[$statut];

        $annuel = DB::table('abonnement_offres')
            ->where('actif', 1)->where('duree_jours', 365)->where('type_ecole_cible', $statut)
            ->orderBy('id')->value('montant');
        if ($annuel !== null && (float) $annuel > 0) {
            $tarif['annuel'] = (int) round((float) $annuel);
        }

        return ['statut' => $statut, 'adhesion' => $tarif['adhesion'], 'annuel' => $tarif['annuel'], 'premiere_annee' => $tarif['adhesion'] + $tarif['annuel']];
    }

    public static function montant(int $montant): string
    {
        return number_format($montant, 0, ',', ' ').' F CFA';
    }
}
