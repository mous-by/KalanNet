<?php

namespace App\Support;

use App\Models\Ecole;

/**
 * Modèles de carte scolaire (format carte bancaire 85,6 × 54 mm). Chaque modèle
 * a ses couleurs par défaut, que l'école peut changer ; rien n'y est écrit d'autre
 * que ce que l'école a enregistré (nom, logo, en-tête officiel, signature).
 */
class ModelesCarte
{
    public const MODELES = [
        'classique' => [
            'nom' => 'Classique',
            'description' => 'Blanc et vagues de couleur : convient à tous les établissements.',
            'couleurs' => ['#1d4ed8', '#38bdf8'],
        ],
        'elegant' => [
            'nom' => 'Élégant',
            'description' => 'Bleu nuit et or, bandeau du matricule : pour les établissements privés.',
            'couleurs' => ['#0f2a4a', '#c9a227'],
        ],
        'franco_arabe' => [
            'nom' => 'Franco-arabe',
            'description' => 'Titre en français et en arabe, fond vert : pour les médersas et écoles franco-arabes.',
            'couleurs' => ['#14532d', '#c9a227'],
        ],
        'sante' => [
            'nom' => 'École de santé',
            'description' => 'Filière et niveau d’études, tracé d’électrocardiogramme : pour les écoles de santé.',
            'couleurs' => ['#0369a1', '#38bdf8'],
        ],
        'primaire' => [
            'nom' => 'Primaire',
            'description' => 'Coloré et gai, soleil et crayons : pour les plus jeunes.',
            'couleurs' => ['#f97316', '#2563eb'],
        ],
        'recto_verso' => [
            'nom' => 'Recto / verso',
            'description' => 'Carte verticale avec un dos : établissement, téléphone, adresse et contact du parent.',
            'couleurs' => ['#1e3a8a', '#3b82f6'],
            'recto_verso' => true,
        ],
    ];

    /** Anciens modèles (liens et applications existants) → modèle actuel. */
    public const ANCIENS = [
        'institutionnel' => 'classique',
        'moderne' => 'classique',
        'epure' => 'classique',
        'horizon' => 'classique',
        'compact' => 'classique',
        'vertical' => 'recto_verso',
    ];

    public static function codes(): array
    {
        return array_merge(array_keys(self::MODELES), array_keys(self::ANCIENS));
    }

    public static function normaliser(?string $code): string
    {
        $code = self::ANCIENS[$code] ?? $code;

        return isset(self::MODELES[$code]) ? $code : 'classique';
    }

    /** Modèle proposé d'office selon l'école. */
    public static function parDefaut(?Ecole $ecole): string
    {
        return match (true) {
            $ecole && ModelesBulletin::estEcoleSante($ecole->typeEcole) => 'sante',
            $ecole && (bool) $ecole->franco_arabe => 'franco_arabe',
            default => 'classique',
        };
    }

    public static function couleurs(string $code): array
    {
        return self::MODELES[self::normaliser($code)]['couleurs'];
    }

    public static function rectoVerso(string $code): bool
    {
        return !empty(self::MODELES[self::normaliser($code)]['recto_verso']);
    }

    /** Couleur éclaircie (mélange avec le blanc, $part de 0 à 1). */
    public static function eclaircir(string $hex, float $part): string
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        $m = fn ($c) => (int) round($c + (255 - $c) * $part);

        return sprintf('#%02x%02x%02x', $m($r), $m($g), $m($b));
    }

    /** Couleur assombrie (mélange avec le noir). */
    public static function assombrir(string $hex, float $part): string
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        $m = fn ($c) => (int) round($c * (1 - $part));

        return sprintf('#%02x%02x%02x', $m($r), $m($g), $m($b));
    }
}
