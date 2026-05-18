<?php

namespace App\Services\Paiements;

use App\Models\Eleve;
use App\Models\ReductionPaiementConfig;

class ReductionService
{
    public function apply(Eleve $eleve, float $montantTotal, int $anneeScolaireId): array
    {
        $statut = $eleve->statut_paiement ?: 'normal';

        $config = ReductionPaiementConfig::query()
            ->where('ecole_id', $eleve->id_ecole)
            ->where('statut_paiement', $statut)
            ->where('actif', true)
            ->where(function ($query) use ($anneeScolaireId) {
                $query->where('annee_scolaire_id', $anneeScolaireId)
                    ->orWhereNull('annee_scolaire_id');
            })
            ->orderByRaw('annee_scolaire_id IS NULL')
            ->first();

        $type = $config?->type_reduction ?? match ($statut) {
            'gratuit' => 'gratuite_totale',
            default => 'aucune',
        };

        $valeur = (float) ($config?->valeur ?? 0);
        $reduction = match ($type) {
            'pourcentage' => round($montantTotal * min(100, max(0, $valeur)) / 100, 2),
            'fixe', 'gratuite_partielle' => min($montantTotal, max(0, $valeur)),
            'gratuite_totale' => $montantTotal,
            default => 0.0,
        };

        $payeurType = match ($statut) {
            'subventionne' => 'etat',
            'boursier' => 'organisme',
            'gratuit' => 'aucun',
            default => 'parent',
        };

        return [
            'statut_paiement' => $statut,
            'type_reduction' => $type,
            'reduction' => $reduction,
            'montant_final' => max(0, $montantTotal - $reduction),
            'payeur_type' => $payeurType,
            'payeur_libelle' => $config?->payeur_libelle,
        ];
    }
}
