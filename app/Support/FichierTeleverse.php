<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Nom sûr d'un fichier envoyé et rangé dans public/ (photos, logos, cachets, pièces jointes d'annonces, reçus).
 *
 * L'extension vient du CONTENU du fichier (type détecté par le serveur), jamais du nom choisi par l'utilisateur :
 * une « image » nommée photo.html ou photo.svg passait la règle `image` et était servie comme une page web du site
 * (script exécuté chez l'administrateur qui l'ouvre). Seules les extensions de la liste sont acceptées.
 */
class FichierTeleverse
{
    public const IMAGES = ['jpg', 'png', 'webp', 'gif'];

    public const DOCUMENTS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'txt'];

    /** Variantes ramenées à une seule extension. */
    private const SYNONYMES = ['jpeg' => 'jpg', 'jpe' => 'jpg'];

    /**
     * @param  list<string>  $permises
     *
     * @throws ValidationException si le contenu n'est pas d'un type permis
     */
    public static function nom(UploadedFile $fichier, string $prefixe, array $permises = self::IMAGES, string $champ = 'fichier'): string
    {
        $extension = strtolower((string) $fichier->guessExtension());
        $extension = self::SYNONYMES[$extension] ?? $extension;

        if ($extension === '' || !in_array($extension, $permises, true)) {
            throw ValidationException::withMessages([
                $champ => 'Type de fichier non accepté ('.implode(', ', $permises).').',
            ]);
        }

        return uniqid($prefixe, true).'.'.$extension;
    }
}
