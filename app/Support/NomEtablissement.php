<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Nom d'école affiché sur les documents. Le préfixe (« École Fondamentale : », « Lycée : »…)
 * n'est ajouté que si le nom saisi ne dit pas déjà ce qu'est l'établissement
 * (« ECOLE PRIVÉE AMINATA FOFANA » reste tel quel).
 */
class NomEtablissement
{
    private const MOTS = ['ecole', 'complexe', 'lycee', 'college', 'groupe scolaire', 'institut', 'medersa', 'etablissement',
        'centre', 'cours', 'academie', 'jardin', 'ifm', 'iftp', 'cfp', 'school', 'madrasa', 'madrassa', 'mahad'];

    public static function dejaQualifie(?string $nom): bool
    {
        $debut = Str::lower(Str::ascii(trim((string) $nom)));
        foreach (self::MOTS as $mot) {
            if (preg_match('/^'.preg_quote($mot, '/').'\b/', $debut)) {
                return true;
            }
        }

        return false;
    }

    /** Ex. : avecPrefixe('bulletins.lycee_prefix', 'Askia') → « Lycée : Askia » ; avecPrefixe(..., 'Lycée Askia') → « Lycée Askia ». */
    public static function avecPrefixe(string $cleTraduction, ?string $nom): string
    {
        return self::dejaQualifie($nom) ? trim((string) $nom) : __($cleTraduction, ['name' => $nom]);
    }

    /** Même règle avec un préfixe écrit en clair (« École fondamentale », « Lycée : »…). */
    public static function prefixer(string $prefixe, ?string $nom): string
    {
        return self::dejaQualifie($nom) ? trim((string) $nom) : $prefixe.$nom;
    }
}
