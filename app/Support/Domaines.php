<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Domaines (UE) qui regroupent les matières d'une classe sur le bulletin
 * « par domaines ». Chaque domaine peut appartenir à une partie (arabe ou
 * française) : dans une classe à deux maîtres, les matières d'un domaine
 * reviennent au maître de sa partie. Chaque école a sa propre liste, créée
 * à partir des domaines du bulletin de médersa à la première utilisation.
 */
class Domaines
{
    public const PARTIES = [
        'arabe' => 'Partie arabe',
        'francais' => 'Partie française',
    ];

    /** code => [nom, nom arabe, partie], dans l'ordre du bulletin. */
    public const DEFAUTS = [
        'islamique' => ['Éducation et civilisation islamique', 'التربية والحضارة الإسلامية', 'arabe'],
        'langue_arabe' => ['Langue et communication (arabe)', 'اللغة والتواصل', 'arabe'],
        'sciences_humaines' => ['Sciences humaines', 'العلوم الإنسانية', 'arabe'],
        'developpement_personne' => ['Développement de la personne', 'تنمية الشخصية', 'arabe'],
        'arts_arabe' => ['Arts (arabe)', 'الفنون', 'arabe'],
        'arts' => ['Arts', 'الفنون', 'francais'],
        'langue_francaise' => ['Langue et communication (français)', 'اللغة الفرنسية والتواصل', 'francais'],
        'sciences_maths' => ['Sciences mathématiques et technologie', 'العلوم الرياضية والتكنولوجيا', 'francais'],
    ];

    /** Domaines de l'école, dans l'ordre d'affichage (créés au premier appel). */
    public static function pourEcole(int $idEcole): Collection
    {
        if (!DB::table('domaines')->where('id_ecole', $idEcole)->exists()) {
            $maintenant = now();
            $lignes = [];
            foreach (self::DEFAUTS as $code => [$nom, $nomAr, $partie]) {
                $lignes[] = [
                    'id_ecole' => $idEcole,
                    'code' => $code,
                    'nom' => $nom,
                    'nom_ar' => $nomAr,
                    'partie' => $partie,
                    'ordre' => count($lignes) + 1,
                    'created_at' => $maintenant,
                    'updated_at' => $maintenant,
                ];
            }
            DB::table('domaines')->insert($lignes);
        }

        return self::existants($idEcole);
    }

    /** Domaines déjà créés par l'école, sans créer la liste par défaut. */
    public static function existants(int $idEcole): Collection
    {
        return DB::table('domaines')->where('id_ecole', $idEcole)
            ->orderByRaw("case partie when 'arabe' then 0 when 'francais' then 1 else 2 end")
            ->orderBy('ordre')->orderBy('nom')
            ->get();
    }

    /** Domaine de l'école qui correspond au domaine par défaut d'une matière. */
    public static function defautPourMatiere(Collection $domaines, ?string $code): ?int
    {
        return $code ? $domaines->firstWhere('code', $code)?->id_domaine : null;
    }

    /** Sépare « Coran-Karim القرآن الكريم » en ['Coran-Karim', 'القرآن الكريم']. */
    public static function separerNom(?string $nom): array
    {
        $nom = trim((string) $nom);
        if (!preg_match('/\p{Arabic}/u', $nom)) {
            return [$nom, null];
        }
        if (preg_match('/^([^\p{Arabic}]*?)\s*(\p{Arabic}[\p{Arabic}\s\p{P}]*)$/u', $nom, $m)) {
            return [trim($m[1]) !== '' ? trim($m[1]) : null, trim($m[2])];
        }

        return [$nom, null];
    }
}
