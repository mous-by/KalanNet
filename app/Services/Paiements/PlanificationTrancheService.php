<?php

namespace App\Services\Paiements;

use App\Models\Planification;
use Illuminate\Support\Carbon;

class PlanificationTrancheService
{
    /**
     * Repartit le total deja verse sur les tranches, dans l'ordre (la 1ere
     * tranche est soldee avant la 2e, etc.). Retourne null pour une formule
     * sans tranches (mensuelle, trimestrielle, annuelle, cooperative...).
     */
    public function summarize(Planification $planification, float $paid, ?Carbon $today = null): ?array
    {
        $tranches = $planification->relationLoaded('tranches')
            ? $planification->tranches
            : $planification->tranches()->get();

        if ($tranches->isEmpty()) {
            return null;
        }

        $today = ($today ?? now())->copy()->startOfDay();
        $available = max(0.0, $paid);
        $lines = [];
        $current = null;
        $soldees = 0;
        $montantEnRetard = 0.0;

        foreach ($tranches->sortBy('numero') as $tranche) {
            $montant = (float) $tranche->montant;
            $applied = min($montant, $available);
            $available -= $applied;

            $reste = round($montant - $applied, 2);
            $limite = Carbon::parse($tranche->date_limite)->startOfDay();
            $enRetard = $reste > 0 && $limite->lt($today);

            $line = [
                'numero' => (int) $tranche->numero,
                'libelle' => $tranche->libelle,
                'montant' => $montant,
                'paye' => round($applied, 2),
                'reste' => $reste,
                'date_limite' => $limite->toDateString(),
                'statut' => $reste <= 0 ? 'soldee' : ($applied > 0 ? 'partielle' : 'a_payer'),
                'en_retard' => $enRetard,
            ];
            $lines[] = $line;

            if ($reste <= 0) {
                $soldees++;
            } elseif ($current === null) {
                $current = $line;
            }
            if ($enRetard) {
                $montantEnRetard += $reste;
            }
        }

        return [
            'tranches' => $lines,
            'total' => count($lines),
            'soldees' => $soldees,
            'courante' => $current,
            'en_retard' => $montantEnRetard > 0,
            'montant_en_retard' => round($montantEnRetard, 2),
        ];
    }

    /**
     * Classe CSS de la ligne : rouge des qu'une tranche est depassee,
     * orange si la tranche en cours arrive a echeance dans 7 jours ou moins.
     */
    public function delayClass(array $summary, ?Carbon $today = null): string
    {
        if ($summary['en_retard']) {
            return 'table-danger';
        }

        $courante = $summary['courante'];
        if ($courante === null) {
            return '';
        }

        $today = ($today ?? now())->copy()->startOfDay();
        $limite = Carbon::parse($courante['date_limite'])->startOfDay();

        return $today->diffInDays($limite, false) <= 7 ? 'table-warning' : '';
    }
}
