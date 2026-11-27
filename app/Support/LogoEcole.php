<?php

namespace App\Support;

/** Logo d'une école pour les documents (PDF, Excel) : seulement si le fichier existe vraiment. */
class LogoEcole
{
    /** @var array<string, string> */
    private static array $cache = [];

    /** Chemin du fichier sur le disque, ou null. */
    public static function chemin(?object $ecole): ?string
    {
        $logo = $ecole->logoEcole ?? null;
        if (!$logo) {
            return null;
        }
        $fichier = public_path(ltrim((string) $logo, '/'));

        return is_file($fichier) ? $fichier : null;
    }

    /** Image intégrée (data URI) pour dompdf, ou null. Les formats que dompdf ne lit pas sont ignorés. */
    public static function dataUri(?object $ecole): ?string
    {
        $fichier = self::chemin($ecole);
        $mime = match (strtolower(pathinfo((string) $fichier, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => null,
        };
        if (!$fichier || !$mime) {
            return null;
        }

        return self::$cache[$fichier] ??= 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($fichier));
    }
}
