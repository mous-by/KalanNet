<?php

namespace App\Support;

use App\Models\Classe;

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
            'format' => 'A5',
            'calcul' => 'standard',
            'description' => 'Le bulletin habituel de KalanNet, inchangé : M.Class, M.Compo, moyenne, coefficient, appréciation, rang et moyenne du premier.',
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
            'format' => 'A5',
            'calcul' => 'standard',
            'description' => 'Visuel pour les parents : photo de l’élève, barre de niveau par matière, évolution depuis la période précédente.',
        ],
    ];

    /** Au-delà, un bulletin A5 déborde sur une seconde page : on conseille l'équivalent A4. */
    public const MAX_MATIERES_A5 = 16;

    public const EQUIVALENT_A4 = [
        'standard' => 'classique',
        'essentiel' => 'classique',
        'moderne' => 'classique',
        'domaines_compact' => 'domaines',
    ];

    public static function existe(?string $code): bool
    {
        return $code !== null && isset(self::MODELES[$code]);
    }

    /** Modèle retenu pour la classe : le dernier choisi, sinon le Standard (le bulletin habituel). */
    public static function pourClasse(Classe $classe): string
    {
        return self::existe($classe->modele_bulletin) ? $classe->modele_bulletin : 'standard';
    }

    public static function calcul(string $code): string
    {
        return self::MODELES[$code]['calcul'] ?? 'standard';
    }

    public static function format(string $code): string
    {
        return self::MODELES[$code]['format'] ?? 'A5';
    }

    public static function modeNote(Classe $classe): string
    {
        return $classe->note_classe_mode === 'unique' ? 'unique' : 'moyenne';
    }
}
