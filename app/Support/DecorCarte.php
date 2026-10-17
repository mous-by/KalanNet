<?php

namespace App\Support;

/**
 * Fonds décoratifs des cartes scolaires, en SVG (le moteur PDF ne sait pas
 * dessiner de courbes ni de dégradés en CSS). Unité : 0,1 mm.
 * Carte horizontale : 856 × 540 ; verticale (recto / verso) : 540 × 856.
 */
class DecorCarte
{
    public static function recto(string $modele, string $c1, string $c2, bool $filigrane = false): string
    {
        $clair = fn ($c, $p) => ModelesCarte::eclaircir($c, $p);
        $fonce = fn ($c, $p) => ModelesCarte::assombrir($c, $p);

        $corps = match ($modele) {
            'elegant' => self::rect(0, 0, 856, 540, $c1)
                .'<path d="M0,166 L856,166 L856,425 C640,470 330,395 0,462 Z" fill="#ffffff"/>'
                .'<path d="M0,462 C330,395 640,470 856,425" fill="none" stroke="'.$c2.'" stroke-width="9"/>'
                .'<path d="M0,490 C300,430 600,505 856,455" fill="none" stroke="'.$c2.'" stroke-width="3"/>'
                .'<line x1="0" y1="166" x2="856" y2="166" stroke="'.$c2.'" stroke-width="5"/>',
            'franco_arabe' => self::rect(0, 0, 856, 540, $clair($c2, 0.88))
                .'<rect x="18" y="168" width="820" height="356" rx="26" ry="26" fill="'.$c1.'"/>'
                .'<rect x="30" y="180" width="796" height="332" rx="20" ry="20" fill="none" stroke="'.$c2.'" stroke-width="3"/>'
                .'<line x1="40" y1="5" x2="816" y2="5" stroke="'.$c2.'" stroke-width="3"/>',
            'sante' => self::rect(0, 0, 856, 540, '#ffffff')
                .self::rect(0, 0, 856, 14, $c1)
                .'<path d="M0,400 C240,350 520,450 856,372 L856,540 L0,540 Z" fill="'.$clair($c2, 0.82).'"/>'
                .'<path d="M0,430 C260,385 540,470 856,405" fill="none" stroke="'.$clair($c2, 0.45).'" stroke-width="4"/>'
                .'<polyline points="764,128 782,128 792,106 803,150 814,96 825,144 833,128 846,128" fill="none" stroke="'.$c2.'" stroke-width="5" stroke-linejoin="round"/>',
            'primaire' => self::rect(0, 0, 856, 540, '#fffdf5')
                .'<circle cx="0" cy="0" r="130" fill="'.$clair($c1, 0.55).'"/>'
                .'<circle cx="10" cy="20" r="80" fill="'.$clair($c1, 0.75).'"/>'
                .'<circle cx="826" cy="124" r="15" fill="#facc15"/>'
                .self::rayons(826, 124, 20, 27, '#facc15')
                .'<path d="M0,470 C180,430 360,520 560,540 L0,540 Z" fill="'.$clair('#facc15', 0.55).'"/>'
                .self::crayon(700, 540, -62, '#ef4444').self::crayon(745, 540, -70, '#facc15')
                .self::crayon(790, 540, -78, '#22c55e').self::crayon(835, 540, -86, $c2),
            default => self::rect(0, 0, 856, 540, '#ffffff') // classique
                .'<path d="M856,0 L470,0 C600,40 750,110 856,210 Z" fill="'.$clair($c1, 0.87).'"/>'
                .'<path d="M520,0 C640,50 770,120 856,190" fill="none" stroke="'.$clair($c2, 0.35).'" stroke-width="5"/>'
                .'<path d="M0,330 C150,430 290,500 400,540 L0,540 Z" fill="'.$c1.'"/>'
                .'<path d="M0,300 C160,410 310,495 440,540" fill="none" stroke="'.$c2.'" stroke-width="10"/>'
                .'<path d="M0,410 C110,470 200,515 260,540 L0,540 Z" fill="'.$fonce($c1, 0.25).'"/>',
        };

        return self::svg(856, 540, $corps.($filigrane ? self::filigrane(856, 540) : ''));
    }

    /** Drapeau en filigrane : trois bandes pâles en biais au centre de la carte. */
    private static function filigrane(int $l, int $h): string
    {
        $bandes = '';
        foreach (['#14b53a', '#fcd116', '#ce1126'] as $i => $couleur) {
            $x = $l * 0.30 + $i * 60;
            $bandes .= sprintf('<polygon points="%.1f,%d %.1f,%d %.1f,%d %.1f,%d" fill="%s" fill-opacity="0.10"/>',
                $x + 120, 0, $x + 180, 0, $x + 60, $h, $x, $h, $couleur);
        }

        return $bandes;
    }

    /** Carte verticale (recto / verso). */
    public static function vertical(bool $verso, string $c1, string $c2, bool $filigrane = false): string
    {
        $haut = $verso ? 150 : 210;
        $corps = self::rect(0, 0, 540, 856, '#ffffff')
            .'<path d="M0,0 L540,0 L540,'.($haut - 30).' C380,'.($haut + 20).' 160,'.($haut - 40).' 0,'.($haut + 10).' Z" fill="'.$c1.'"/>'
            .'<path d="M0,'.($haut + 22).' C160,'.($haut - 28).' 380,'.($haut + 32).' 540,'.($haut - 18).'" fill="none" stroke="'.$c2.'" stroke-width="8"/>'
            .'<path d="M0,856 L0,'.(856 - 46).' C180,'.(856 - 80).' 360,'.(856 - 20).' 540,'.(856 - 60).' L540,856 Z" fill="'.$c1.'"/>';

        return self::svg(540, 856, $corps.($filigrane && !$verso ? self::filigrane(540, 856) : ''));
    }

    private static function svg(int $l, int $h, string $corps): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="'.$l.'" height="'.$h.'" viewBox="0 0 '.$l.' '.$h.'">'.$corps.'</svg>'
        );
    }

    private static function rect(int $x, int $y, int $l, int $h, string $couleur): string
    {
        return '<rect x="'.$x.'" y="'.$y.'" width="'.$l.'" height="'.$h.'" fill="'.$couleur.'"/>';
    }

    private static function rayons(int $cx, int $cy, int $r1, int $r2, string $couleur): string
    {
        $lignes = '';
        for ($a = 0; $a < 360; $a += 30) {
            $t = deg2rad($a);
            $lignes .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="6" stroke-linecap="round"/>',
                $cx + $r1 * cos($t), $cy + $r1 * sin($t), $cx + $r2 * cos($t), $cy + $r2 * sin($t), $couleur);
        }

        return $lignes;
    }

    /** Crayon de couleur posé en biais depuis le bas de la carte. */
    private static function crayon(int $x, int $y, int $angle, string $couleur): string
    {
        $t = deg2rad($angle);
        $dx = cos($t);
        $dy = sin($t);
        $nx = -$dy * 13;
        $ny = $dx * 13;
        $p = fn ($d) => [$x + $dx * $d, $y + $dy * $d];
        [$ax, $ay] = $p(0);
        [$bx, $by] = $p(150);
        [$cx, $cy] = $p(185);
        $corps = sprintf('<polygon points="%.1f,%.1f %.1f,%.1f %.1f,%.1f %.1f,%.1f" fill="%s"/>',
            $ax + $nx, $ay + $ny, $bx + $nx, $by + $ny, $bx - $nx, $by - $ny, $ax - $nx, $ay - $ny, $couleur);
        $pointe = sprintf('<polygon points="%.1f,%.1f %.1f,%.1f %.1f,%.1f" fill="#f5d0a9"/>',
            $bx + $nx, $by + $ny, $cx, $cy, $bx - $nx, $by - $ny);

        return $corps.$pointe;
    }
}
