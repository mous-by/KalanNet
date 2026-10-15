<?php

namespace App\Support;

use App\Models\Classe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Séries du secondaire général et tendance d'orientation : la 10ème oriente
 * vers une série de 11ème, chaque série de 11ème se divise en deux séries de
 * 12ème. Chaque série est caractérisée par des matières ; l'école peut
 * ajuster la liste (sinon : liste de départ retrouvée d'après le nom des
 * matières de ses classes).
 */
class Series
{
    /** code => [libellé, niveau, série de 11ème d'origine]. */
    public const SERIES = [
        'sciences' => ['11ème Sciences', 11, null],
        'ses' => ['11ème SES (Sciences économiques et sociales)', 11, null],
        'll' => ['11ème LL (Langues et Littérature)', 11, null],
        'texp' => ['TExp (Sciences expérimentales)', 12, 'sciences'],
        'tse' => ['TSE (Sciences exactes)', 12, 'sciences'],
        'tss' => ['TSS (Sciences sociales)', 12, 'ses'],
        'tseco' => ['TSECO (Sciences économiques)', 12, 'ses'],
        'lat' => ['LAT (Langues et Arts)', 12, 'll'],
        'tll' => ['LL (Langues et Littérature)', 12, 'll'],
    ];

    /** Liste de départ : familles de matières de chaque série. */
    private const FAMILLES_PAR_SERIE = [
        'sciences' => ['maths', 'physique_chimie', 'svt'],
        'ses' => ['economie', 'histoire_geo', 'maths'],
        'll' => ['francais', 'anglais', 'histoire_geo'],
        'texp' => ['svt', 'physique_chimie'],
        'tse' => ['maths', 'physique_chimie'],
        'tss' => ['histoire_geo', 'sociologie', 'philosophie'],
        'tseco' => ['economie', 'maths'],
        'lat' => ['francais', 'arts', 'philosophie'],
        'tll' => ['francais', 'anglais', 'lv2'],
    ];

    /** Famille => mots qui la reconnaissent dans le nom de la matière (sans accents, en minuscules). */
    private const MOTS = [
        'maths' => ['math'],
        'physique_chimie' => ['physique', 'chimie', 'phy-ch', 'ph-ch'],
        'svt' => ['svt', 'sciences naturelle', 'biolog', 's.v.t'],
        'economie' => ['econom', 'comptab'],
        'histoire_geo' => ['histoire', 'geograph', 'hist-geo'],
        'francais' => ['francais', 'litterature', 'grammaire', 'redaction'],
        'anglais' => ['anglais'],
        'philosophie' => ['philo'],
        'sociologie' => ['socio'],
        'arts' => ['arts', 'musique', 'dessin'],
    ];

    public static function libelle(?string $code): ?string
    {
        return self::SERIES[$code][0] ?? null;
    }

    /** Séries proposées pour une classe : 11ème (une seule) ou 12ème. */
    public static function pourNiveau(int $niveau): array
    {
        return array_filter(self::SERIES, fn ($s) => $s[1] === $niveau);
    }

    /** Séries vers lesquelles s'oriente un élève de cette classe (10ème : séries de 11ème ; 11ème : celles de sa série). */
    public static function orientations(Classe $classe): array
    {
        if (!str_starts_with((string) $classe->ordreEnseignement, 'secondairegenerale')) {
            return [];
        }

        return match (ClasseSuivante::niveau($classe->nom_classe)) {
            10 => array_keys(self::pourNiveau(11)),
            11 => $classe->serie ? array_keys(array_filter(self::SERIES, fn ($s) => $s[2] === $classe->serie)) : [],
            default => [],
        };
    }

    /**
     * Matières (id) qui caractérisent chaque série pour l'école : réglage de
     * l'école s'il existe, sinon liste de départ parmi $matieres
     * (collection d'objets id_matiere, nom_matiere, est_lv2).
     */
    public static function matieresParSerie(int $idEcole, Collection $matieres): array
    {
        $reglage = DB::table('series_matieres')->where('id_ecole', $idEcole)->get()->groupBy('serie');
        $resultat = [];
        foreach (array_keys(self::SERIES) as $serie) {
            $resultat[$serie] = $reglage->has($serie)
                ? $reglage[$serie]->pluck('id_matiere')->map(fn ($id) => (int) $id)->all()
                : self::parDefaut($serie, $matieres);
        }

        return $resultat;
    }

    public static function parDefaut(string $serie, Collection $matieres): array
    {
        $familles = self::FAMILLES_PAR_SERIE[$serie] ?? [];

        return $matieres->filter(function ($m) use ($familles) {
            $nom = Str::lower(Str::ascii((string) $m->nom_matiere));
            foreach ($familles as $famille) {
                if ($famille === 'lv2' ? !empty($m->est_lv2) : collect(self::MOTS[$famille])->contains(fn ($mot) => str_contains($nom, $mot))) {
                    return true;
                }
            }

            return false;
        })->pluck('id_matiere')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * Tendance d'un élève : pour chaque série possible, moyenne annuelle
     * (pondérée par les coefficients) des matières qui la caractérisent ;
     * séries triées de la plus forte à la plus faible.
     *
     * @param  array  $matieresAnnuelles  id_matiere => ['nom', 'coef', 'moyenne']
     */
    public static function tendance(Classe $classe, array $matieresAnnuelles): ?array
    {
        $orientations = self::orientations($classe);
        if (!$orientations || !$matieresAnnuelles) {
            return null;
        }

        $matieres = collect($matieresAnnuelles)->map(fn ($m, $id) => (object) ['id_matiere' => $id, 'nom_matiere' => $m['nom'], 'est_lv2' => $m['est_lv2'] ?? false]);
        $parSerie = self::matieresParSerie((int) $classe->idEcole, $matieres->values());

        $series = collect($orientations)->map(function ($serie) use ($parSerie, $matieresAnnuelles) {
            $retenues = collect($parSerie[$serie] ?? [])->filter(fn ($id) => isset($matieresAnnuelles[$id]))->map(fn ($id) => $matieresAnnuelles[$id]);
            $coef = $retenues->sum(fn ($m) => max((float) $m['coef'], 0));
            if ($retenues->isEmpty()) {
                return null;
            }

            return [
                'code' => $serie,
                'libelle' => self::libelle($serie),
                'moyenne' => $coef > 0 ? $retenues->sum(fn ($m) => $m['moyenne'] * max((float) $m['coef'], 0)) / $coef : $retenues->avg('moyenne'),
                'matieres' => $retenues->mapWithKeys(fn ($m) => [$m['nom'] => $m['moyenne']])->all(),
            ];
        })->filter()->sortByDesc('moyenne')->values();

        if ($series->isEmpty()) {
            return null;
        }

        // Deux séries à moins d'un demi-point : on indique les deux.
        $premiere = $series->first();
        $proches = $series->filter(fn ($s) => $premiere['moyenne'] - $s['moyenne'] < 0.5);

        return ['series' => $series->all(), 'retenues' => $proches->all()];
    }
}
