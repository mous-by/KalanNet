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
    /** Clés des lignes (noms des champs du formulaire, pas des heures) ; 10 affichées au moins, 16 au plus. */
    public const LIGNES = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];

    public const LIGNES_MIN = 10;

    public const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    /** « 8:00 », « 8h00 », « 08h », « 8.00 », « 08:00:00 » -> « 08:00 » ; null si l'heure est invalide. */
    public static function normaliserHeure(?string $valeur): ?string
    {
        if (!preg_match('/^\s*(\d{1,2})\s*[:hH.]?\s*(\d{2})?(?::\d{2})?\s*$/', (string) $valeur, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $min = (int) ($m[2] ?? 0);

        return $h <= 23 && $min <= 59 ? sprintf('%02d:%02d', $h, $min) : null;
    }

    /**
     * Disposition de la grille : chaque cours enregistré (web ou mobile) trouve
     * une case. Un cours va dans la première ligne dont la plage contient son
     * heure de début et dont la case est libre ce jour-là ; sinon une ligne est
     * ajoutée à son horaire. Les récréations suivent leur ligne.
     *
     * @return array{lignes: array<string, array>, horaires: array<string, array{debut: string, fin: string}>, recreations: array<string, int>, placement: array<string, array<string, object>>, non_places: int}
     */
    public static function disposer(int $idClasse, int $idAnnee, Collection $cours): array
    {
        $base = self::lire($idClasse, $idAnnee, $cours);
        $plages = [];
        foreach (self::LIGNES as $cle) {
            if (isset($base['horaires'][$cle])) {
                $plages[] = ['debut' => substr($base['horaires'][$cle]['debut'], 0, 5), 'fin' => substr($base['horaires'][$cle]['fin'], 0, 5), 'recre' => (int) ($base['recreations'][$cle] ?? 0)];
            }
        }

        $cours = $cours->filter(fn ($c) => self::normaliserHeure($c->heure_debut ?? null) && in_array($c->jour, self::JOURS, true))
            ->sortBy(fn ($c) => array_search($c->jour, self::JOURS, true).' '.self::normaliserHeure($c->heure_debut))->values();

        // Lignes ajoutées pour les cours sans case, puis nouveau placement, jusqu'à ce que tous soient placés.
        $tours = 0;
        while (true) {
            usort($plages, fn ($a, $b) => self::minutes($a['debut']) <=> self::minutes($b['debut']));
            [$placement, $restants] = self::placer($plages, $cours);
            if ($restants === [] || count($plages) >= count(self::LIGNES) || ++$tours > 20) {
                break;
            }
            foreach ($restants as $c) {
                if (count($plages) >= count(self::LIGNES)) {
                    break;
                }
                $debut = self::normaliserHeure($c->heure_debut);
                $fin = self::normaliserHeure($c->heure_fin);
                $finMinutes = $fin && self::minutes($fin) > self::minutes($debut) ? self::minutes($fin) : self::minutes($debut) + 60;
                $plages[] = ['debut' => $debut, 'fin' => sprintf('%02d:%02d', intdiv($finMinutes, 60) % 24, $finMinutes % 60), 'recre' => 0];
            }
        }

        $lignes = []; $horaires = []; $recreations = []; $parCle = [];
        foreach (array_slice(self::LIGNES, 0, max(self::LIGNES_MIN, count($plages))) as $i => $cle) {
            $lignes[$cle] = isset($plages[$i]) ? ['debut' => $plages[$i]['debut'], 'fin' => $plages[$i]['fin']] : [];
            if (isset($plages[$i])) {
                $horaires[$cle] = $lignes[$cle];
                if ($plages[$i]['recre'] > 0) {
                    $recreations[$cle] = $plages[$i]['recre'];
                }
            }
        }
        foreach ($placement as $jour => $parIndex) {
            foreach ($parIndex as $i => $c) {
                $parCle[$jour][self::LIGNES[$i]] = $c;
            }
        }

        return ['lignes' => $lignes, 'horaires' => $horaires, 'recreations' => $recreations, 'placement' => $parCle, 'non_places' => count($restants)];
    }

    /** @return array{0: array<string, array<int, object>>, 1: array<int, object>} */
    private static function placer(array $plages, Collection $cours): array
    {
        $placement = []; $restants = [];
        foreach ($cours as $c) {
            $debut = self::minutes(self::normaliserHeure($c->heure_debut));
            $place = false;
            foreach ($plages as $i => $p) {
                if ($debut >= self::minutes($p['debut']) && $debut < max(self::minutes($p['fin']), self::minutes($p['debut']) + 1) && !isset($placement[$c->jour][$i])) {
                    $placement[$c->jour][$i] = $c;
                    $place = true;
                    break;
                }
            }
            if (!$place) {
                $restants[] = $c;
            }
        }

        return [$placement, $restants];
    }


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

    /**
     * À partir de cette durée, une pause n'est plus une récréation : c'est la
     * grande pause de la mi-journée (ex. 12 h – 14 h), après laquelle vient le soir.
     */
    public const PAUSE_SOIR_MINUTES = 60;

    public static function estPauseSoir(int $minutes): bool
    {
        return $minutes >= self::PAUSE_SOIR_MINUTES;
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
            ->filter(fn ($c) => self::normaliserHeure($c->heure_debut ?? null) && self::normaliserHeure($c->heure_fin ?? null))
            ->groupBy(fn ($c) => self::normaliserHeure($c->heure_debut))
            ->map(fn ($groupe, $debut) => ['debut' => $debut, 'fin' => $groupe->map(fn ($c) => self::normaliserHeure($c->heure_fin))->max()])
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
