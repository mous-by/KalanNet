<?php

namespace App\Services\Bulletins;

use App\Models\Classe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Support\Ordres;

/**
 * Moyenne annuelle d'une classe : moyenne des moyennes des périodes composées
 * (compositions mensuelles : somme des mois ÷ nombre de mois ; trimestres :
 * somme des trimestres ÷ nombre de trimestres). Seules les périodes où
 * l'élève a une moyenne comptent. Rang annuel : ex aequo au même rang.
 */
class MoyennesAnnuelles
{
    private const MOIS = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];

    private static array $cache = [];

    private ?Collection $periodes = null;
    private ?Collection $resultats = null;

    public function __construct(private Classe $classe, private int $idAnnee, private string $calcul = 'standard')
    {
    }

    private ?int $dernierTrimestre = null;

    public static function pour(Classe $classe, int $idAnnee, string $calcul): self
    {
        return self::$cache[$classe->id_classe.'|'.$idAnnee.'|'.$calcul] ??= new self($classe, $idAnnee, $calcul);
    }

    /** true : la classe compose par mois (Fondamental I) ; false : par trimestre. */
    public function mensuel(): bool
    {
        return (bool) ($this->periodes()->first()['mois'] ?? null);
    }

    /**
     * Périodes composées par la classe, dans l'ordre de l'année scolaire :
     * [['id_trimestre' => .., 'mois' => .., 'libelle' => ..], ...].
     */
    public function periodes(): Collection
    {
        if ($this->periodes !== null) {
            return $this->periodes;
        }

        $eleves = DB::table('eleve')->where('id_classe', $this->classe->id_classe)->where('id_annee', $this->idAnnee)->pluck('id_eleve');
        $notes = DB::table('ligne_evaluation')->whereIn('id_eleve', $eleves)->where('id_annee_scolaire', $this->idAnnee);
        $mois = (clone $notes)->whereNotNull('mois')->distinct()->pluck('mois')->map(fn ($m) => (int) $m)->filter()->values();
        $trimestres = (clone $notes)->whereNull('mois')->whereNotNull('id_trimestre')->distinct()->pluck('id_trimestre')->map(fn ($t) => (int) $t)->values();

        // Fondamental I : compositions mensuelles si la classe en a ; sinon trimestres.
        $parMois = Ordres::surDix($this->classe->ordreEnseignement) ? $mois->isNotEmpty() : $trimestres->isEmpty() && $mois->isNotEmpty();

        if ($parMois) {
            // Année scolaire : de septembre à août.
            return $this->periodes = $mois->sortBy(fn ($m) => ($m + 3) % 12)->values()
                ->map(fn ($m) => ['id_trimestre' => null, 'mois' => $m, 'libelle' => self::MOIS[$m] ?? (string) $m]);
        }

        $noms = DB::table('trimestre')->pluck('nom_trimestre', 'id_trimestre');

        return $this->periodes = $trimestres->sort()->values()
            ->map(fn ($t) => ['id_trimestre' => $t, 'mois' => null, 'libelle' => $noms[$t] ?? (string) $t]);
    }

    /** id_eleve => ['moyenne', 'rang', 'total', 'nb_periodes', 'periodes' => [libellé => [moyenne, rang]]]. */
    public function resultats(): Collection
    {
        if ($this->resultats !== null) {
            return $this->resultats;
        }

        $parEleve = [];
        $parMatiere = [];
        foreach ($this->periodes() as $periode) {
            $calcul = CalculBulletin::pour($this->classe, $this->idAnnee, $periode['id_trimestre'], $periode['mois'], $this->calcul);
            foreach ($calcul->resultats() as $idEleve => $r) {
                if (($r['moyenne_periode'] ?? 0) > 0) {
                    $parEleve[$idEleve][$periode['libelle']] = ['moyenne' => (float) $r['moyenne_periode'], 'rang' => $r['rang'] ?? null];
                    // Moyenne de chaque matière sur la période (pour la tendance d'orientation).
                    foreach ($r['matieres'] as $m) {
                        if (!empty($m->id_matiere)) {
                            $parMatiere[$idEleve][$m->id_matiere]['nom'] = $m->nom_matiere;
                            $parMatiere[$idEleve][$m->id_matiere]['coef'] = $m->coef;
                            $parMatiere[$idEleve][$m->id_matiere]['est_lv2'] = !empty($m->est_lv2);
                            $parMatiere[$idEleve][$m->id_matiere]['notes'][] = (float) $m->M_Gle;
                        }
                    }
                }
            }
        }

        $annuels = collect($parEleve)->map(fn ($periodes, $idEleve) => [
            'moyenne' => collect($periodes)->avg('moyenne'),
            'nb_periodes' => count($periodes),
            'periodes' => $periodes,
            'matieres' => collect($parMatiere[$idEleve] ?? [])->map(fn ($m) => [
                'nom' => $m['nom'],
                'coef' => $m['coef'],
                'est_lv2' => $m['est_lv2'],
                'moyenne' => array_sum($m['notes']) / count($m['notes']),
            ])->all(),
        ]);

        $classes = $annuels->sortByDesc(fn ($a) => round($a['moyenne'], 2));
        $rangs = [];
        $precedente = null;
        $rang = 0;
        $position = 0;
        foreach ($classes as $idEleve => $a) {
            $position++;
            $moyenne = round($a['moyenne'], 2);
            $rang = ($precedente !== null && $moyenne === $precedente) ? $rang : $position;
            $rangs[$idEleve] = $rang;
            $precedente = $moyenne;
        }

        return $this->resultats = $annuels->map(fn ($a, $idEleve) => $a + ['rang' => $rangs[$idEleve], 'total' => $annuels->count()]);
    }

    public function pourEleve(int $idEleve): ?array
    {
        return $this->resultats()->get($idEleve);
    }

    /**
     * Bulletin de fin d'année : dernier trimestre de l'année (trimestres) ou
     * dernier mois composé par la classe (compositions mensuelles).
     */
    public function estDernierePeriode(?int $idTrimestre, ?int $mois): bool
    {
        $derniere = $this->periodes()->last();
        if (!$derniere) {
            return false;
        }

        if ($mois) {
            return $derniere['mois'] === (int) $mois;
        }

        // Dernier trimestre lu une fois par instance (une instance par classe et par année).
        $this->dernierTrimestre ??= (int) DB::table('trimestre')->max('id_trimestre');

        return $idTrimestre && (int) $idTrimestre === $this->dernierTrimestre
            && $derniere['id_trimestre'] === (int) $idTrimestre;
    }
}
