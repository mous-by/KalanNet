<?php

namespace App\Support;

/**
 * Variables communes aux modèles de bulletin : nom de l'établissement selon
 * le cycle, académie / CAP, logo, titre de la période, rang affiché.
 */
class PresentationBulletin
{
    public static function variables(array $bulletin): array
    {
        $ecole = $bulletin['ecole'];
        $ordre = $bulletin['ordre'];
        $apercu = $bulletin['apercu'];

        $academie = NomAcademie::sansPrefixe($ecole->academieRef->nom_academie ?? $ecole->academie ?? '');
        $cap = trim(preg_replace('/^\s*cap\s+(d[’\']?|de)?\s*/iu', '', (string) ($ecole->capRef->nom_cap ?? $ecole->cap ?? '')));

        $estFondamental = Ordres::sousCap($ordre);
        $estTechnique = str_starts_with((string) $ordre, 'secondairetechnique');
        $estLycee = !$estTechnique && str_starts_with((string) $ordre, 'secondaire');
        $cycle = trim(preg_replace('/\s*\(.*\)\s*$/', '', ExamenNational::ordresLabels($ecole)[$ordre] ?? ''));

        if (strtolower((string) $ecole->typeEcole) === 'complexe scolaire') {
            $nomEcole = __('bulletins.complexe_scolaire_prefix', ['name' => $ecole->nomComplexe ?: $ecole->nomEcole]);
            $sousTitre = match (true) {
                $estFondamental && !empty($ecole->nomFondamental) => __('bulletins.ecole_fondamentale_prefix', ['name' => $ecole->nomFondamental]).($cycle !== '' ? ' — '.$cycle : ''),
                $estLycee && !empty($ecole->nomLycee) => __('bulletins.lycee_prefix', ['name' => $ecole->nomLycee]),
                $estTechnique && !empty($ecole->nomProfessionnel) => __('bulletins.ecole_professionnelle_prefix', ['name' => $ecole->nomProfessionnel]),
                default => $cycle !== '' ? $cycle : null,
            };
        } else {
            $nomEcole = match (true) {
                $estFondamental && !empty($ecole->nomFondamental) => __('bulletins.ecole_fondamentale_prefix', ['name' => $ecole->nomFondamental]),
                $estLycee && !empty($ecole->nomLycee) => __('bulletins.lycee_prefix', ['name' => $ecole->nomLycee]),
                $estTechnique && !empty($ecole->nomProfessionnel) => __('bulletins.ecole_professionnelle_prefix', ['name' => $ecole->nomProfessionnel]),
                default => $ecole->nomEcole,
            };
            $sousTitre = null;
        }

        $periode = !empty($apercu->mois_nom) ? 'Composition du mois de '.$apercu->mois_nom : 'Bulletin du '.($apercu->nom_trimestre ?? '');
        $rang = $bulletin['rang'] ?? null;

        return $bulletin + [
            'nomEcole' => $nomEcole,
            'sousTitre' => $sousTitre,
            'academie' => $academie !== '' ? __('bulletins.academie_de', ['name' => $academie]) : null,
            'capLabel' => $estFondamental && $cap !== '' ? __('bulletins.cap_de', ['name' => $cap]) : null,
            'enteteGauche' => $ecole->pays?->entete_document_gauche,
            'enteteDroite' => $ecole->pays?->entete_document_droite,
            'logo' => self::logo($ecole->logoEcole ?? null),
            'periodeTitre' => $periode,
            'anneeScolaire' => $apercu->annee ?? '',
            'rangTexte' => $rang ? $rang.($rang === 1 ? 'er' : 'e').(($bulletin['exaequo'] ?? false) ? ' ex' : '') : '-',
            'surDix' => Ordres::surDix($ordre),
            'telephone' => $ecole->telephone ?? null,
            'signataire' => self::signataire($ecole, $ordre),
        ];
    }

    /** Qui signe le bulletin : le proviseur au secondaire, le directeur des études en école de santé, sinon le directeur. */
    public static function signataire(object $ecole, ?string $ordre): string
    {
        return match (true) {
            ModelesBulletin::estEcoleSante($ecole->typeEcole ?? null) => 'Le Directeur des Études',
            str_starts_with((string) $ordre, 'secondaire') => 'Le Proviseur',
            default => 'Le Directeur',
        };
    }

    /** Note formatée « 12,50 » ; « - » sans note. */
    public static function note($valeur, int $decimales = 2): string
    {
        return $valeur === null ? '-' : number_format((float) $valeur, $decimales, ',', ' ');
    }

    private static array $logos = [];

    private static function logo(?string $chemin): ?string
    {
        if (!$chemin || !is_file($fichier = public_path($chemin))) {
            return null;
        }

        return self::$logos[$fichier] ??= (function () use ($fichier) {
            $extension = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));
            $mime = $extension === 'png' ? 'image/png' : ($extension === 'webp' ? 'image/webp' : 'image/jpeg');

            return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($fichier));
        })();
    }
}
