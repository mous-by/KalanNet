<?php

namespace App\Support;

use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Pays;
use Illuminate\Support\Str;

/**
 * Equivalent Devise/Telephone pour les examens nationaux de fin de cycle
 * (DEF/BAC au Mali) : une ecole -> son pays -> le niveau (numero de classe)
 * et le nom de chacun de ses examens. Le Mali n'en a que deux (intermediaire,
 * final), mais plusieurs autres pays en ont un 3e en fin de primaire (6e
 * annee, type CEPE) -- d'ou le tiers "primaire", non configure pour le Mali.
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
    /**
     * Le Mali est le seul pays dont la structure scolaire (types d'ecole,
     * ordres d'enseignement, seuils de passage...) est modelisee finement
     * dans l'appli -- utilise pour brancher vers ce comportement precis vs
     * un comportement generique base sur les numeros de classe pour les
     * autres pays (ex: ClasseController::ordresDisponibles()).
     */
    public static function estMali(Ecole|Pays|int|null $ecole): bool
    {
        return Devise::resolvePays($ecole)->code_iso === 'ML';
    }

    /**
     * Libelles (adaptes au pays) des 4 "ordres d'enseignement" reutilises tels
     * quels pour tout pays (fondamentale1/2, secondairegenerale,
     * secondairetechniqueetprofessionnel -- voir ClasseController et
     * SchoolOrderAccess::ORDERS, dont les cles restent les memes partout).
     * Seul l'AFFICHAGE change hors Mali, jamais le decoupage fonctionnel.
     *
     * @return array<string,string>
     */
    public static function ordresLabels(Ecole|Pays|int|null $ecole): array
    {
        if (static::estMali($ecole)) {
            return [
                'fondamentale1' => 'Fondamentale I (1 à 6)',
                'fondamentale2' => 'Fondamentale II (7 à 9)',
                'secondairegenerale' => 'Secondaire Général',
                'secondairetechniqueetprofessionnel' => 'Secondaire Technique et Professionnel',
            ];
        }

        $intermediaire = static::intermediaire($ecole)['grade'] ?? 9;
        $final = static::final($ecole)['grade'] ?? 12;

        return [
            'fondamentale1' => 'Primaire (1 à 6)',
            'fondamentale2' => "Secondaire 1er cycle (7 à {$intermediaire})",
            'secondairegenerale' => 'Secondaire 2nd cycle (' . ($intermediaire + 1) . " à {$final})",
            'secondairetechniqueetprofessionnel' => 'Secondaire Technique et Professionnel',
        ];
    }

    /** @return array{grade:int,nom:string}|null */
    public static function primaire(Ecole|Pays|int|null $ecole): ?array
    {
        $pays = Devise::resolvePays($ecole);

        return $pays->niveau_examen_primaire && $pays->nom_examen_primaire
            ? ['grade' => $pays->niveau_examen_primaire, 'nom' => $pays->nom_examen_primaire]
            : null;
    }

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

        $primaire = static::primaire($ecole);
        if ($primaire && $primaire['grade'] === $niveau) {
            return $primaire['nom'];
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

    /** Les noms d'examens configures pour ce pays (ex: ['DEF', 'BAC'], ou ['CEPE', 'BEPC', 'BAC']), sans filtre par type d'ecole. */
    public static function niveauxConfigures(Ecole|Pays|int|null $ecole): array
    {
        return array_values(array_filter([
            static::primaire($ecole)['nom'] ?? null,
            static::intermediaire($ecole)['nom'] ?? null,
            static::final($ecole)['nom'] ?? null,
        ]));
    }

    /** Meme chose, mais filtre par type d'ecole (une ecole purement secondaire ne propose pas les examens plus bas). */
    public static function niveauxDisponibles(Ecole|Pays|int|null $ecole, ?string $typeEcole): array
    {
        $primaire = static::primaire($ecole)['nom'] ?? null;
        $intermediaire = static::intermediaire($ecole)['nom'] ?? null;
        $final = static::final($ecole)['nom'] ?? null;
        $type = Str::lower(Str::ascii((string) $typeEcole));

        // Ecole de Sante : filiere/annee, pas de cycle Fondamentale/Secondaire
        // -- aucun des examens nationaux (DEF/BAC ou equivalents) ne s'y applique.
        if (str_contains($type, 'sante')) {
            return [];
        }

        if (str_contains($type, 'complexe')) {
            return array_values(array_filter([$primaire, $intermediaire, $final]));
        }

        // Hors Mali, "Secondaire Generale" designe un seul etablissement
        // couvrant tout le secondaire (7e a la Terminale, cf. ecole-modal),
        // donc les deux examens du secondaire (jamais celui du primaire,
        // hors de son perimetre) -- contrairement au Mali ou ce type
        // n'existe qu'a partir de la 10e (l'examen intermediaire y releve du
        // type distinct "Fondamentale II"/"College", plus bas).
        if (str_contains($type, 'secondaire generale') && !static::estMali($ecole)) {
            return array_values(array_filter([$intermediaire, $final]));
        }

        if (str_contains($type, 'fondamentale ii') || str_contains($type, 'college')) {
            return array_values(array_filter([$intermediaire]));
        }

        // "Fondamentale I" (Mali) et "Primaire" (autres pays) couvrent tous
        // les deux les niveaux 1 a 6 : seul l'examen de primaire peut s'y
        // trouver (inexistant/non configure pour le Mali, donc [] pour lui,
        // inchange). Comparaison exacte (pas str_contains) : "fondamentale i"
        // est un prefixe de "fondamentale ii", une correspondance partielle
        // capturerait aussi a tort le type "Fondamentale II".
        if ($type === 'fondamentale i' || $type === 'primaire') {
            return array_values(array_filter([$primaire]));
        }

        if (str_contains($type, 'secondaire') || str_contains($type, 'lycee') || str_contains($type, 'technique')) {
            return array_values(array_filter([$final]));
        }

        return array_values(array_filter([$primaire, $intermediaire, $final]));
    }

    public static function extractClasseLevel(?string $nom): ?int
    {
        return $nom && preg_match('/\d+/', Str::ascii($nom), $matches) ? (int) $matches[0] : null;
    }
}
