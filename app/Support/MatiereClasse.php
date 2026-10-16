<?php

namespace App\Support;

use App\Models\LigneClasse;
use Illuminate\Validation\ValidationException;

/**
 * Une matière n'est utilisable pour une classe (emploi du temps, appel
 * d'épreuve…) que si elle fait partie des matières de cette classe : c'est ce
 * qui garantit le bon catalogue (franco-arabe, classique, École de Santé).
 */
class MatiereClasse
{
    public static function verifier(int $classeId, mixed $matiereId, string $champ = 'id_matiere'): void
    {
        $existe = LigneClasse::where('id_classe', $classeId)
            ->where('id_matiere', (int) $matiereId)
            ->exists();

        if (!$existe) {
            throw ValidationException::withMessages([
                $champ => "Cette matière n'est pas enseignée dans cette classe. Ajoutez-la d'abord aux matières de la classe.",
            ]);
        }
    }

    /**
     * Grille d'emploi du temps : chaque matière choisie doit être une matière
     * de la classe. Une matière déjà placée dans la grille (avant d'être
     * retirée de la classe) reste acceptée, pour ne pas bloquer l'enregistrement.
     */
    public static function verifierGrille(int $classeId, int $anneeId, array $slots): void
    {
        $permises = LigneClasse::where('id_classe', $classeId)->pluck('id_matiere')
            ->merge(\App\Models\EmploiDuTemps::where('id_classe', $classeId)->where('id_annee_scolaire', $anneeId)->pluck('id_matiere'))
            ->map(fn ($id) => (int) $id)->flip();

        foreach ($slots as $heures) {
            foreach ((array) $heures as $case) {
                $idMatiere = (int) ($case['id_matiere'] ?? 0);
                if ($idMatiere && !$permises->has($idMatiere)) {
                    throw ValidationException::withMessages([
                        'id_matiere' => "Une matière choisie n'est pas enseignée dans cette classe. Ajoutez-la d'abord aux matières de la classe.",
                    ]);
                }
            }
        }
    }
}
