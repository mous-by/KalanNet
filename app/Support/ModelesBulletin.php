<?php

namespace App\Support;

use App\Models\Classe;
use App\Models\Ecole;

/**
 * Modèles de bulletin proposés à l'impression. Le modèle ne change que la
 * présentation, sauf pour les modèles « par domaines » qui calculent la
 * moyenne générale comme la moyenne des domaines.
 */
class ModelesBulletin
{
    public const MODELES = [
        'standard' => [
            'nom' => 'Standard',
            'format' => 'A4',
            'orientation' => 'landscape',
            'deux_exemplaires' => true,
            'calcul' => 'standard',
            'description' => 'Le bulletin habituel de KalanNet, inchangé, en deux exemplaires A5 côte à côte sur une feuille A4 paysage (un pour les parents, un pour l’école).',
        ],
        'classique' => [
            'nom' => 'Classique',
            'format' => 'A4',
            'calcul' => 'standard',
            'description' => 'Le bulletin complet : notes de classe et de composition, coefficients, moyenne, rang, statistiques de la classe et décision.',
        ],
        'domaines' => [
            'nom' => 'Par domaines bilingue',
            'format' => 'A4',
            'calcul' => 'domaines',
            'description' => 'Modèle médersa : en-tête français / arabe, matières groupées par domaine, moyenne des domaines et une appréciation par maître.',
        ],
        'essentiel' => [
            'nom' => 'Essentiel',
            'format' => 'A5',
            'calcul' => 'standard',
            'description' => 'Compact et clair : moyenne par matière, moyenne générale, rang et appréciation. Idéal pour peu de matières.',
        ],
        'domaines_compact' => [
            'nom' => 'Par domaines compact',
            'format' => 'A5',
            'calcul' => 'domaines',
            'description' => 'La version courte du modèle médersa : chaque domaine avec sa moyenne, détail des matières, deux appréciations.',
        ],
        'moderne' => [
            'nom' => 'Moderne',
            'format' => 'A4',
            'orientation' => 'landscape',
            'deux_exemplaires' => true,
            'calcul' => 'standard',
            'description' => 'Deux exemplaires A5 du même bulletin côte à côte sur une feuille A4 paysage (un pour les parents, un pour l’école) : photo de l’élève, barre de niveau par matière, évolution depuis la période précédente.',
        ],
    ];

    /** Au-delà, un bulletin A5 déborde sur une seconde page : on conseille l'équivalent A4. */
    public const MAX_MATIERES_A5 = 16;

    public const EQUIVALENT_A4 = [
        'standard' => 'classique',
        'essentiel' => 'classique',
        'domaines_compact' => 'domaines',
    ];

    private static array $francoArabe = [];

    public static function existe(?string $code): bool
    {
        return $code !== null && isset(self::MODELES[$code]);
    }

    /** Les modèles « par domaines » sont réservés aux écoles franco-arabes. */
    public static function disponibles(?int $idEcole): array
    {
        return self::estFrancoArabe($idEcole)
            ? self::MODELES
            : array_filter(self::MODELES, fn ($m) => $m['calcul'] !== 'domaines');
    }

    public static function disponible(?string $code, ?int $idEcole): bool
    {
        return $code !== null && isset(self::disponibles($idEcole)[$code]);
    }

    /**
     * Modèle par défaut : Par domaines bilingue pour les classes du fondamental
     * d'une école franco-arabe, Standard pour toutes les autres.
     */
    public static function parDefaut(Classe $classe): string
    {
        return self::estFrancoArabe((int) $classe->idEcole) && in_array($classe->ordreEnseignement, ['fondamentale1', 'fondamentale2'], true)
            ? 'domaines'
            : 'standard';
    }

    /** Modèle retenu pour la classe : le dernier choisi (s'il est permis à l'école), sinon celui par défaut. */
    public static function pourClasse(Classe $classe): string
    {
        return self::disponible($classe->modele_bulletin, (int) $classe->idEcole) ? $classe->modele_bulletin : self::parDefaut($classe);
    }

    public static function estFrancoArabe(?int $idEcole): bool
    {
        if (!$idEcole) {
            return false;
        }

        return self::$francoArabe[$idEcole] ??= (bool) Ecole::withoutGlobalScopes()->whereKey($idEcole)->value('franco_arabe');
    }

    public static function calcul(string $code): string
    {
        return self::MODELES[$code]['calcul'] ?? 'standard';
    }

    public static function format(string $code): string
    {
        return self::MODELES[$code]['format'] ?? 'A5';
    }

    public static function orientation(string $code): string
    {
        return self::MODELES[$code]['orientation'] ?? 'portrait';
    }

    /** Deux exemplaires A5 du même bulletin côte à côte sur une feuille A4 paysage. */
    public static function deuxExemplaires(string $code): bool
    {
        return !empty(self::MODELES[$code]['deux_exemplaires']);
    }

    /** Contenu imprimé sur une demi-feuille A4 (format A5, ou deux exemplaires A5). */
    public static function demiPage(string $code): bool
    {
        return self::format($code) === 'A5' || self::deuxExemplaires($code);
    }

    public static function modeNote(Classe $classe): string
    {
        return $classe->note_classe_mode === 'unique' ? 'unique' : 'moyenne';
    }
}
