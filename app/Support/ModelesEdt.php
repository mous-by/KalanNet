<?php

namespace App\Support;

/**
 * Modèles du PDF de l'emploi du temps (A4 paysage, une page). Seules les
 * informations de l'école y figurent (nom, logo, en-tête officiel).
 *
 * - entete « officiel » : ministère / république en haut, nom de l'école centré ;
 *   « logo » : logo et nom de l'école à gauche, année et classe à droite.
 * - cellules « couleurs » : une couleur par matière ; « texte » : sobre.
 */
class ModelesEdt
{
    public const MODELES = [
        'classique' => [
            'nom' => 'Classique officiel',
            'description' => 'En-tête du ministère et de la république, sobre et officiel.',
            'couleurs' => ['#1e40af', '#dbeafe'],
            'entete' => 'officiel',
            'cellules' => 'texte',
        ],
        'moderne' => [
            'nom' => 'Moderne coloré',
            'description' => 'Logo et nom de l’école en avant, classe et année mises en valeur.',
            'couleurs' => ['#15803d', '#dcfce7'],
            'entete' => 'logo',
            'cellules' => 'texte',
        ],
        'detaille' => [
            'nom' => 'Détaillé',
            'description' => 'Une couleur par matière et l’enseignant de chaque cours.',
            'couleurs' => ['#14365d', '#e0e7ff'],
            'entete' => 'officiel',
            'cellules' => 'couleurs',
        ],
        'primaire' => [
            'nom' => 'Primaire',
            'description' => 'Gai et coloré, soleil et crayons : pour les plus jeunes.',
            'couleurs' => ['#ea580c', '#ffedd5'],
            'entete' => 'logo',
            'cellules' => 'couleurs',
            'decor' => 'primaire',
        ],
        'technique' => [
            'nom' => 'Professionnel / technique',
            'description' => 'Violet et engrenage : pour les lycées techniques et professionnels.',
            'couleurs' => ['#6d28d9', '#ede9fe'],
            'entete' => 'logo',
            'cellules' => 'texte',
            'decor' => 'technique',
        ],
        'bilingue' => [
            'nom' => 'Bilingue français / anglais',
            'description' => 'Jours, titres et pauses en français et en anglais.',
            'couleurs' => ['#b91c1c', '#fee2e2'],
            'entete' => 'logo',
            'cellules' => 'texte',
            'bilingue' => true,
        ],
    ];

    public static function normaliser(?string $code): ?string
    {
        return isset(self::MODELES[$code]) ? $code : null;
    }

    /** Modèle proposé d'office selon la classe. */
    public static function parDefaut(?string $ordre): string
    {
        return match (true) {
            $ordre === 'fondamentale1' => 'primaire',
            str_starts_with((string) $ordre, 'secondairetechnique') => 'technique',
            default => 'detaille',
        };
    }

    public static function pour(?string $code, ?string $ordre): array
    {
        $code = self::normaliser($code) ?? self::parDefaut($ordre);

        return ['code' => $code] + self::MODELES[$code];
    }
}
