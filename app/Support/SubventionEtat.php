<?php

namespace App\Support;

use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Planification;
use Illuminate\Support\Facades\DB;

/**
 * Un élève subventionné voit la totalité de ses frais scolaires de l'année
 * payée par l'État. Ce statut n'existe que dans les écoles privées, et
 * seulement pour les classes du secondaire (lycée général ou technique).
 *
 * L'État paie souvent en retard (une subvention 2024 peut arriver en 2026) :
 * la table subvention_etat_eleve garde donc, année par année, qui était
 * subventionné et dans quelle classe, indépendamment de la situation actuelle.
 */
class SubventionEtat
{
    public static function classeEligible(?Classe $classe, ?Ecole $ecole = null): bool
    {
        if (!$classe || !str_starts_with((string) $classe->ordreEnseignement, 'secondaire')) {
            return false;
        }

        $ecole ??= Ecole::find($classe->idEcole);

        return $ecole !== null && !$ecole->estPublique();
    }

    /**
     * Formule appliquée d'office à un élève subventionné, que l'utilisateur
     * n'a donc pas à choisir : la formule annuelle de la classe (coût complet
     * de l'année), ou à défaut la seule formule existante.
     */
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
