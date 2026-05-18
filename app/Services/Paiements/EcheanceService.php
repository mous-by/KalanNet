<?php

namespace App\Services\Paiements;

use App\Models\AnneeScolaire;
use App\Models\PlanPaiement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class EcheanceService
{
    public const MODES = ['annuel', 'semestriel', 'trimestriel', 'mensuel', 'personnalise'];

    public function generate(PlanPaiement $plan, AnneeScolaire $annee, ?array $customEcheances = null): Collection
    {
        if (!in_array($plan->mode_paiement, self::MODES, true)) {
            throw new InvalidArgumentException('Mode de paiement invalide.');
        }

        $items = $plan->mode_paiement === 'personnalise'
            ? $this->customItems($customEcheances ?? [])
            : $this->automaticItems($plan->mode_paiement, (float) $plan->montant_final, $annee);

        $plan->echeances()->delete();

        return collect($items)->map(fn ($item) => $plan->echeances()->create($item));
    }

    private function automaticItems(string $mode, float $amount, AnneeScolaire $annee): array
    {
        $count = match ($mode) {
            'annuel' => 1,
            'semestriel' => 2,
            'trimestriel' => 3,
            'mensuel' => max(1, $this->schoolMonthCount($annee)),
            default => 1,
        };

        $start = CarbonImmutable::parse($annee->date_debut);
        $end = CarbonImmutable::parse($annee->date_fin);
        $base = floor(($amount / $count) * 100) / 100;
        $items = [];
        $allocated = 0.0;

        for ($i = 1; $i <= $count; $i++) {
            $value = $i === $count ? round($amount - $allocated, 2) : $base;
            $allocated += $value;

            $date = $count === 1
                ? $end
                : $start->addMonthsNoOverflow($i - 1)->endOfMonth();

            if ($date->greaterThan($end)) {
                $date = $end;
            }

            $items[] = [
                'libelle' => $this->label($mode, $i),
                'montant_prevu' => $value,
                'date_limite' => $date->toDateString(),
                'statut' => $value <= 0 ? 'paye' : 'en_attente',
            ];
        }

        return $items;
    }

    private function customItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => isset($item['libelle'], $item['montant_prevu'], $item['date_limite']))
            ->map(fn ($item) => [
                'libelle' => trim((string) $item['libelle']),
                'montant_prevu' => max(0, (float) $item['montant_prevu']),
                'date_limite' => $item['date_limite'],
                'statut' => ((float) $item['montant_prevu']) <= 0 ? 'paye' : 'en_attente',
            ])
            ->values()
            ->all();
    }

    private function schoolMonthCount(AnneeScolaire $annee): int
    {
        $start = CarbonImmutable::parse($annee->date_debut)->startOfMonth();
        $end = CarbonImmutable::parse($annee->date_fin)->startOfMonth();

        return $start->diffInMonths($end) + 1;
    }

    private function label(string $mode, int $index): string
    {
        return match ($mode) {
            'annuel' => 'Paiement annuel',
            'semestriel' => 'Semestre ' . $index,
            'trimestriel' => 'Trimestre ' . $index,
            'mensuel' => 'Mensualite ' . $index,
            default => 'Echeance ' . $index,
        };
    }
}
