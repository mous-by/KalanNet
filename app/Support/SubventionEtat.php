<?php

namespace App\Support;

use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Planification;
use Illuminate\Support\Facades\DB;

/**
 * Les élèves subventionnés reçoivent une part égale des versements globaux
 * de l'État, sans formule de paiement ni dette familiale. Ce statut n'existe que dans les écoles privées, et
 * seulement pour les classes du secondaire (lycée général ou technique).
 *
 * L'État paie souvent en retard (une subvention 2024 peut arriver en 2026) :
 * la table subvention_etat_eleve garde donc, année par année, qui était
 * subventionné et dans quelle classe, indépendamment de la situation actuelle.
 */
class SubventionEtat
{
    /** @param list<int> $partsDejaRecuesCentimes @return list<int> parts en centimes */
    public static function repartirEgalement(float $montant, int $nombreEleves, array $partsDejaRecuesCentimes = []): array
    {
        if ($montant <= 0 || $nombreEleves <= 0) {
            return [];
        }

        $centimes = (int) round($montant * 100);
        $part = intdiv($centimes, $nombreEleves);
        $reste = $centimes % $nombreEleves;
        $ordrePartsRestantes = range(0, $nombreEleves - 1);
        usort($ordrePartsRestantes, fn ($a, $b) => ($partsDejaRecuesCentimes[$a] ?? 0) <=> ($partsDejaRecuesCentimes[$b] ?? 0) ?: $a <=> $b);
        $parts = array_fill(0, $nombreEleves, $part);

        foreach (array_slice($ordrePartsRestantes, 0, $reste) as $index) {
            $parts[$index]++;
        }

        return $parts;
    }

    public static function classeEligible(?Classe $classe, ?Ecole $ecole = null): bool
    {
        if (!$classe || !str_starts_with((string) $classe->ordreEnseignement, 'secondaire')) {
            return false;
        }

        $ecole ??= Ecole::find($classe->idEcole);

        return $ecole !== null && !$ecole->estPublique();
    }

    /** Formule annuelle utilisée uniquement pour remplacer un frais de cas social non subventionné. */
    public static function formulePourSubventionne(int $classeId, int $anneeId): ?int
    {
        $formules = Planification::where('id_classe', $classeId)->where('id_annee', $anneeId)->get();
        $annuelle = $formules->first(fn ($formule) => strtolower(trim((string) $formule->motif)) === 'annuelle');

        if ($annuelle) {
            return (int) $annuelle->id_planification;
        }

        return $formules->count() === 1 ? (int) $formules->first()->id_planification : null;
    }

    public static function estPrisEnCharge(int $eleveId, int $anneeId): bool
    {
        return DB::table('subvention_etat_eleve')
            ->where('id_eleve', $eleveId)
            ->where('id_annee', $anneeId)
            ->exists();
    }

    /** @return array<int, true> ids des élèves subventionnés pour l'année donnée */
    public static function elevesPrisEnCharge(int $anneeId, array $eleveIds): array
    {
        if ($eleveIds === []) {
            return [];
        }

        return DB::table('subvention_etat_eleve')
            ->where('id_annee', $anneeId)
            ->whereIn('id_eleve', $eleveIds)
            ->pluck('id_eleve')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /**
     * Aligne l'historique sur la fiche de l'élève pour son année en cours ;
     * les années passées ne sont jamais modifiées (l'État peut encore les payer).
     */
    public static function synchroniser(Eleve $eleve): void
    {
        if (!$eleve->id_annee) {
            return;
        }

        $classe = Classe::find($eleve->id_classe);
        $cle = ['id_eleve' => $eleve->id_eleve, 'id_annee' => $eleve->id_annee];

        if (($eleve->statut_paiement ?? 'normal') === 'subventionne' && self::classeEligible($classe)) {
            DB::table('subvention_etat_eleve')->updateOrInsert($cle, [
                'id_classe' => $eleve->id_classe,
                'id_ecole' => $eleve->id_ecole,
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            return;
        }

        DB::table('subvention_etat_eleve')->where($cle)->delete();
    }
}
