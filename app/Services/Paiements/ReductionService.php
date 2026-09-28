<?php

namespace App\Services\Paiements;

use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\ReductionPaiementConfig;

class ReductionService
{
    public function apply(Eleve $eleve, float $montantTotal, int $anneeScolaireId): array
    {
        if (Ecole::find($eleve->id_ecole)?->estPublique()) {
            return [
                'statut_paiement' => 'normal',
                'type_reduction' => 'aucune',
                'reduction' => 0.0,
                'montant_final' => $montantTotal,
                'payeur_type' => 'parent',
                'payeur_libelle' => null,
            ];
        }

        $statut = $eleve->statut_paiement ?: 'normal';

        // Subventionné : l'État paie la totalité, aucune règle de réduction.
        if ($statut === 'subventionne') {
            return [
                'statut_paiement' => $statut,
                'type_reduction' => 'aucune',
                'reduction' => 0.0,
                'montant_final' => $montantTotal,
                'payeur_type' => 'etat',
                'payeur_libelle' => null,
            ];
        }

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
