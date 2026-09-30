<?php

namespace App\Support;

use App\Models\Paiement;
use App\Models\Planification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Champ « Cas social » de l'élève : Normal, Dispensé, Malade, ou « Autre ».
 * « Autre » fait saisir la nature du cas social et le frais correspondant :
 * pour l'année, l'élève paie ce frais à la place du montant de sa formule.
 * Le frais est gardé sur la ligne d'inscription de l'année (ligne_inscription).
 */
class CasSocial
{
    public const PREDEFINIS = ['normal', 'Dipenser', 'Malade'];

    public const AUTRE = 'autre';

    /** Une valeur hors liste (nature saisie) est un cas « Autre ». */
    public static function estAutre(?string $valeur): bool
    {
        return $valeur !== null && trim($valeur) !== '' && !in_array($valeur, self::PREDEFINIS, true);
    }

    public static function regles(): array
    {
        return [
            'cas_social' => 'nullable|string|max:255',
            'cas_social_nature' => 'nullable|required_if:cas_social,' . self::AUTRE . '|string|max:255',
            'cas_social_montant' => 'nullable|numeric|min:0',
        ];
    }

    public static function messages(): array
    {
        return [
            'cas_social_nature.required_if' => __('eleves.cas_social_nature_obligatoire'),
        ];
    }

    /**
     * Lit le formulaire : [valeur à mettre sur eleve.cas_social, nature, frais].
     * Le frais ne s'applique qu'avec une formule (école privée) et jamais à un
     * élève subventionné (l'État paie tout).
     *
     * @return array{0: string, 1: ?string, 2: ?float}
     */
    public static function depuisFormulaire(array $data, bool $avecFrais): array
    {
        $choix = $data['cas_social'] ?? null;
        if ($choix !== self::AUTRE) {
            return [$choix ?: 'normal', null, null];
        }

        $nature = trim((string) ($data['cas_social_nature'] ?? ''));
        $montant = $avecFrais && isset($data['cas_social_montant']) && $data['cas_social_montant'] !== ''
            ? round((float) $data['cas_social_montant'], 2)
            : null;

        if ($avecFrais && $montant === null) {
            throw ValidationException::withMessages(['cas_social_montant' => __('eleves.cas_social_montant_obligatoire')]);
        }

        return [$nature, $nature, $montant];
    }

    /**
     * Le frais du cas social est une remise : il ne dépasse pas le montant de
     * la formule, et ne descend pas sous ce qui a déjà été payé dessus.
     */
    public static function verifierMontant(?float $montant, ?int $planificationId, ?int $eleveId, int $anneeId): void
    {
        if ($montant === null || !$planificationId) {
            return;
        }

        $formule = Planification::find($planificationId);
        if ($formule && $montant > (float) $formule->montant_planification) {
            throw ValidationException::withMessages(['cas_social_montant' => __('eleves.cas_social_montant_trop_eleve', [
                'montant' => number_format((float) $formule->montant_planification, 0, ',', ' '),
            ])]);
        }

        if ($eleveId) {
            $dejaPaye = self::dejaPaye($eleveId, $planificationId, $anneeId);
            if ($montant + 0.001 < $dejaPaye) {
                throw ValidationException::withMessages(['cas_social_montant' => __('eleves.cas_social_montant_deja_paye', [
                    'montant' => number_format($dejaPaye, 0, ',', ' '),
                ])]);
            }
        }
    }

    /** Enregistre (ou retire) le cas social sur la ligne d'inscription de l'année. */
    public static function enregistrer(int $eleveId, int $anneeId, ?string $nature, ?float $montant): void
    {
        DB::table('ligne_inscription')
            ->where('id_eleve', $eleveId)
            ->where('id_annee', $anneeId)
            ->update(['cas_social_nature' => $nature, 'montant_cas_social' => $montant]);
    }

    /** @return object{cas_social_nature: ?string, montant_cas_social: ?string, id_planification: ?int}|null */
    public static function ligne(int $eleveId, int $anneeId): ?object
    {
        return DB::table('ligne_inscription')
            ->where('id_eleve', $eleveId)
            ->where('id_annee', $anneeId)
            ->first(['cas_social_nature', 'montant_cas_social', 'id_planification']);
    }

    /**
     * Montant que l'élève doit pour cette formule et cette année : le frais du
     * cas social s'il y en a un sur sa formule de l'année, sinon la formule.
     */
    public static function montantDu(Planification $planification, int $eleveId, int $anneeId): float
    {
        $ligne = self::ligne($eleveId, $anneeId);

        return self::montantPourLigne($planification, $ligne);
    }

    public static function montantPourLigne(Planification $planification, ?object $ligne): float
    {
        if ($ligne && $ligne->montant_cas_social !== null
            && (int) $ligne->id_planification === (int) $planification->id_planification) {
            return (float) $ligne->montant_cas_social;
        }

        return (float) $planification->montant_planification;
    }

    public static function aUnFrais(?object $ligne): bool
    {
        return $ligne !== null && isset($ligne->montant_cas_social) && $ligne->montant_cas_social !== null;
    }

    private static function dejaPaye(int $eleveId, int $planificationId, int $anneeId): float
    {
        return (float) Paiement::where('id_eleve', $eleveId)
            ->where('id_planification', $planificationId)
            ->where('id_annee', $anneeId)
            ->where(fn ($q) => $q->whereNull('statut')->orWhere('statut', 'valide'))
            ->sum(DB::raw('COALESCE(montant_paye, montant, 0)'));
    }
}
