<?php

namespace App\Support;

use App\Models\Eleve;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Photo d'un élève (fiche, liste, cartes scolaires) : ajoutée ou changée après l'inscription, depuis la fiche, le
 * formulaire de modification, l'écran « Photos de la classe » (web) ou l'application mobile.
 *
 * La photo est redressée (orientation EXIF des téléphones), réduite à 800 px de côté au plus et enregistrée en JPEG
 * dans public/image_eleves ; l'ancienne photo de l'élève est supprimée (jamais l'avatar par défaut).
 */
class PhotoEleve
{
    public const DOSSIER = 'image_eleves';

    private const COTE_MAX = 800;

    /** Photos « par défaut » posées à l'inscription quand aucune photo n'est envoyée. */
    public static function manquante(?string $image): bool
    {
        $image = trim((string) $image);

        return $image === '' || $image === 'default.png' || str_starts_with($image, 'assets/images/avatars/');
    }

    /** @return array<string, mixed> */
    public static function regles(bool $obligatoire = true): array
    {
        return ['photo' => [$obligatoire ? 'required' : 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240']];
    }

    public static function url(?string $image): ?string
    {
        return self::manquante($image) ? null : asset($image);
    }

    public static function enregistrer(Eleve $eleve, UploadedFile $fichier): string
    {
        $dossier = public_path(self::DOSSIER);
        if (!File::exists($dossier)) {
            File::makeDirectory($dossier, 0755, true);
        }

        $nom = 'eleve_'.$eleve->id_eleve.'_'.Str::lower(Str::random(10)).'.jpg';
        if (!self::reduire($fichier, $dossier.'/'.$nom)) {
            // Format que GD ne sait pas lire : le fichier est gardé tel quel.
            $nom = 'eleve_'.$eleve->id_eleve.'_'.Str::lower(Str::random(10)).'.'.strtolower($fichier->getClientOriginalExtension() ?: 'jpg');
            $fichier->move($dossier, $nom);
        }

        $ancienne = $eleve->image;
        $eleve->image = self::DOSSIER.'/'.$nom;
        $eleve->save();

        if (!self::manquante($ancienne) && str_starts_with((string) $ancienne, self::DOSSIER.'/') && $ancienne !== $eleve->image) {
            File::delete(public_path($ancienne));
        }

        return $eleve->image;
    }

    private static function reduire(UploadedFile $fichier, string $destination): bool
    {
        $chemin = $fichier->getRealPath();
        $type = @exif_imagetype($chemin);
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($chemin),
            IMAGETYPE_PNG => @imagecreatefrompng($chemin),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($chemin) : false,
            default => false,
        };
        if (!$image) {
            return false;
        }

        // Photo prise au téléphone : l'orientation est dans les données EXIF, pas dans les pixels.
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = (int) (@exif_read_data($chemin)['Orientation'] ?? 1);
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0) {
                $tournee = imagerotate($image, $angle, 0);
                if ($tournee) {
                    imagedestroy($image);
                    $image = $tournee;
                }
            }
        }

        $largeur = imagesx($image);
        $hauteur = imagesy($image);
        $ratio = min(1, self::COTE_MAX / max($largeur, $hauteur));
        $l = max(1, (int) round($largeur * $ratio));
        $h = max(1, (int) round($hauteur * $ratio));

        $sortie = imagecreatetruecolor($l, $h);
        imagefill($sortie, 0, 0, imagecolorallocate($sortie, 255, 255, 255));
        imagecopyresampled($sortie, $image, 0, 0, 0, 0, $l, $h, $largeur, $hauteur);
        $ok = imagejpeg($sortie, $destination, 82);
        imagedestroy($image);
        imagedestroy($sortie);

        return $ok;
    }
}
