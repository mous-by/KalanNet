<?php

namespace App\Support;

/**
 * Les académies sont enregistrées sous la forme « AE de Ségou ». Les documents
 * officiels écrivent déjà « Académie d'Enseignement de … » : on retire donc le
 * préfixe du nom pour éviter « Académie d'Enseignement de AE de Ségou ».
 */
class NomAcademie
{
    public static function sansPrefixe(?string $nom): string
    {
        return trim((string) preg_replace(
            '/^\s*(a\.?\s?e\.?|acad[ée]mie(\s+d[’\']\s*enseignement)?)\s+(d[’\']\s*|de\s+)/iu',
            '',
            (string) $nom
        ));
    }

    /**
     * Même chose pour les CAP (« CAP de Kayes » → « Kayes »). Un nom sans « de »
     * reste entier : « CAP Diré » → « Diré ».
     */
    public static function capSansPrefixe(?string $nom): string
    {
        return trim((string) preg_replace('/^\s*cap\s+(?:de\s+|d[’\']\s*)?/iu', '', (string) $nom));
    }
}
