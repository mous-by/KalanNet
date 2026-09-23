<?php

namespace App\Support;

use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Pays;
use Illuminate\Support\Str;

/**
 * Equivalent Devise/Telephone pour les examens nationaux de fin de cycle
 * (DEF/BAC au Mali) : une ecole -> son pays -> le niveau (numero de classe)
 * et le nom de chacun de ses deux examens (intermediaire, final).
 *
 * Contrairement a Devise/Telephone, PAS de repli sur le Mali quand le pays
 * n'a rien de configure : se tromper de niveau/nom d'examen impacterait
 * directement la classification (diplome/redoublement) de vrais eleves.
 * Un pays non configure signifie simplement "pas de logique d'examen
 * national pour ce pays" (repli sur la decision par moyenne, comme les
 * classes non-terminales l'ont toujours ete).
 */
class ExamenNational
{
    /** @return array{grade:int,nom:string}|null */
    public static function intermediaire(Ecole|Pays|int|null $ecole): ?array
    {
        $pays = Devise::resolvePays($ecole);

        return $pays->niveau_examen_intermediaire && $pays->nom_examen_intermediaire
            ? ['grade' => $pays->niveau_examen_intermediaire, 'nom' => $pays->nom_examen_intermediaire]
            : null;
    }

    /** @return array{grade:int,nom:string}|null */
    public static function final(Ecole|Pays|int|null $ecole): ?array
    {
        $pays = Devise::resolvePays($ecole);

        return $pays->niveau_examen_final && $pays->nom_examen_final
            ? ['grade' => $pays->niveau_examen_final, 'nom' => $pays->nom_examen_final]
            : null;
    }

    /** Nom de l'examen (ex: "DEF", "BAC") sanctionnant cette classe, ou null si ce n'est pas une classe d'examen. */
    public static function pourClasse(Classe $classe, Ecole|Pays|int|null $ecole): ?string
    {
        $niveau = static::extractClasseLevel($classe->nom_classe);
        if ($niveau === null) {
            return null;
        }

        $intermediaire = static::intermediaire($ecole);
        if ($intermediaire && $intermediaire['grade'] === $niveau) {
            return $intermediaire['nom'];
        }

        $final = static::final($ecole);
        if ($final && $final['grade'] === $niveau) {
            return $final['nom'];
        }

        return null;
    }

    /** Vrai si $nom est l'examen FINAL du pays (reussite = sortie du systeme, pas juste passage au cycle suivant). */
    public static function estFinal(string $nom, Ecole|Pays|int|null $ecole): bool
    {
        return (static::final($ecole)['nom'] ?? null) === $nom;
    }

    /** Les noms d'examens configures pour ce pays (ex: ['DEF', 'BAC']), sans filtre par type d'ecole. */
    public static function niveauxConfigures(Ecole|Pays|int|null $ecole): array
    {
        return array_values(array_filter([
            static::intermediaire($ecole)['nom'] ?? null,
            static::final($ecole)['nom'] ?? null,
        ]));
    }

    /** Meme chose, mais filtre par type d'ecole (une ecole purement secondaire ne propose pas l'examen intermediaire, etc). */
    public static function niveauxDisponibles(Ecole|Pays|int|null $ecole, ?string $typeEcole): array
    {
        $intermediaire = static::intermediaire($ecole)['nom'] ?? null;
        $final = static::final($ecole)['nom'] ?? null;
        $type = Str::lower(Str::ascii((string) $typeEcole));

        if (str_contains($type, 'complexe')) {
            return array_values(array_filter([$intermediaire, $final]));
        }

        if (str_contains($type, 'fondamentale ii') || str_contains($type, 'college')) {
            return array_values(array_filter([$intermediaire]));
        }

        if (str_contains($type, 'secondaire') || str_contains($type, 'lycee') || str_contains($type, 'technique')) {
            return array_values(array_filter([$final]));
        }

        return array_values(array_filter([$intermediaire, $final]));
    }

    public static function extractClasseLevel(?string $nom): ?int
    {
        return $nom && preg_match('/\d+/', Str::ascii($nom), $matches) ? (int) $matches[0] : null;
    }
}
