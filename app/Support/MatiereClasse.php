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
}
