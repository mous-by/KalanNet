<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grille de l'emploi du temps d'une classe : horaires de chaque ligne et
 * récréations, enregistrés en base (emploi_du_temps_grilles).
 *
 * Les lignes gardent leurs clés fixes (« 08:00 » … « 17:00 », noms des champs
 * du formulaire) ; seuls leurs horaires réels changent. Sans grille enregistrée
 * (emplois du temps saisis avant), les horaires sont reconstitués à partir des
 * cours : la n-ième heure de début distincte occupe la n-ième ligne, et un écart
 * entre deux lignes devient une récréation.
 */
class GrilleEmploiDuTemps
{
    public const LIGNES = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

    /** @return array{horaires: array<string, array{debut: string, fin: string}>, recreations: array<string, int>} */
    public static function lire(int $idClasse, int $idAnnee, ?Collection $cours = null): array
    {
        if (SchemaExiste::table('emploi_du_temps_grilles')) {
            $grille = DB::table('emploi_du_temps_grilles')->where('id_classe', $idClasse)->where('id_annee_scolaire', $idAnnee)->first();
            if ($grille) {
                return [
                    'horaires' => (array) json_decode((string) $grille->horaires, true),
                    'recreations' => array_map('intval', (array) json_decode((string) $grille->recreations, true)),
                ];
            }
        }

        return self::depuisCours($cours ?? collect());
    }

    public static function enregistrer(int $idClasse, int $idAnnee, array $horaires, array $recreations): void
    {
        $horaires = array_filter(
            array_intersect_key($horaires, array_flip(self::LIGNES)),
            fn ($h) => is_array($h) && self::heure($h['debut'] ?? null) && self::heure($h['fin'] ?? null)
        );
        $recreations = array_filter(
            array_map('intval', array_intersect_key($recreations, array_flip(self::LIGNES))),
            fn ($minutes) => $minutes > 0 && $minutes <= 240
        );

        DB::table('emploi_du_temps_grilles')->updateOrInsert(
            ['id_classe' => $idClasse, 'id_annee_scolaire' => $idAnnee],
            ['horaires' => json_encode($horaires), 'recreations' => json_encode($recreations), 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /** Horaires et récréations déduits des cours enregistrés. */
    public static function depuisCours(Collection $cours): array
    {
        $plages = $cours
            ->filter(fn ($c) => $c->heure_debut && $c->heure_fin)
            ->groupBy(fn ($c) => substr((string) $c->heure_debut, 0, 5))
            ->map(fn ($groupe, $debut) => ['debut' => $debut, 'fin' => substr((string) $groupe->max('heure_fin'), 0, 5)])
            ->sortKeys()
            ->values()
            ->take(count(self::LIGNES));

        $horaires = []; $recreations = [];
        foreach ($plages as $i => $plage) {
            $cle = self::LIGNES[$i];
            $horaires[$cle] = $plage;
            $suivante = $plages[$i + 1] ?? null;
            if ($suivante) {
                $ecart = self::minutes($suivante['debut']) - self::minutes($plage['fin']);
                if ($ecart > 0 && $ecart <= 240) {
                    $recreations[$cle] = $ecart;
                }
            }
        }

        return ['horaires' => $horaires, 'recreations' => $recreations];
    }

    private static function heure(?string $valeur): bool
    {
        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string) $valeur);
    }

    private static function minutes(string $heure): int
    {
        [$h, $m] = array_map('intval', explode(':', $heure) + [0, 0]);

        return $h * 60 + $m;
    }
}
