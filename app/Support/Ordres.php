<?php

namespace App\Support;

/**
 * Règles communes aux ordres d'enseignement (clés de classe.ordreEnseignement).
 *
 * Le Jardin (préscolaire) fonctionne comme le Fondamental I : notes sur 10,
 * compositions mensuelles, un seul maître par classe, rattaché au CAP.
 */
class Ordres
{
    public const JARDIN = 'jardin';

    /** Ordres notés sur 10, par compositions mensuelles. */
    public const SUR_DIX = [self::JARDIN, 'fondamentale1'];

    /** Ordres rattachés au CAP (en-tête des documents, nom de l'école fondamentale). */
    public const SOUS_CAP = [self::JARDIN, 'fondamentale1', 'fondamentale2'];

    /** Types d'école qui ne proposent pas de Jardin. */
    private const SANS_JARDIN = ['École de Santé', 'Secondaire Technique et Professionnel', 'Technique et Professionnelle'];

    public static function surDix(?string $ordre): bool
    {
        return in_array($ordre, self::SUR_DIX, true);
    }

    public static function sousCap(?string $ordre): bool
    {
        return in_array($ordre, self::SOUS_CAP, true);
    }

    public static function proposeJardin(?string $typeEcole): bool
    {
        return !in_array($typeEcole, self::SANS_JARDIN, true);
    }
}
