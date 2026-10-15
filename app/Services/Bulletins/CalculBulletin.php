<?php

namespace App\Services\Bulletins;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Services\ConductNoteService;
use App\Support\Domaines;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Calcule les bulletins de toute une classe pour une période, en chargeant
 * les notes en une seule fois. Deux méthodes :
 * - « standard » : moyenne = (classe + 2 × compo) / 3, pondérée par les
 *   coefficients, conduite comprise (Fondamental I : note sur 10) ;
 * - « domaines » : moyenne de matière = (classe + compo) / 2, moyenne de
 *   chaque domaine pondérée par les coefficients, moyenne générale = moyenne
 *   des domaines, et une appréciation par partie (maître arabe / français).
 * La note de classe d'une matière est, pour chaque période, la moyenne des
 * devoirs ou un seul devoir, selon le choix fait à la saisie des notes
 * (table note_classe_choix ; par défaut : la moyenne).
 */
class CalculBulletin
{
    private static array $cache = [];

    private Ecole $ecole;
    private ?string $ordre;
    private Collection $resultats;
    /** id_matiere => id_evaluation du seul devoir retenu pour la note de classe. */
    private array $choixNoteClasse = [];

    public function __construct(
        private Classe $classe,
        private int $idAnnee,
        private ?int $idTrimestre,
        private ?int $mois,
        private string $calcul = 'standard',
    ) {
        $this->ecole = Ecole::withoutGlobalScopes()->findOrFail($classe->idEcole);
        $this->ordre = $classe->ordreEnseignement;
    }

    /** Instance partagée pendant la requête (un bulletin par élève relance sinon tout le calcul). */
    public static function pour(Classe $classe, int $idAnnee, ?int $idTrimestre, ?int $mois, string $calcul): self
    {
        $cle = implode('|', [$classe->id_classe, $idAnnee, $idTrimestre, $mois, $calcul]);

        return self::$cache[$cle] ??= new self($classe, $idAnnee, $idTrimestre, $mois, $calcul);
    }

    /** Vide le cache (après une modification de notes ou de choix pendant la même requête). */
    public static function oublier(): void
    {
        self::$cache = [];
    }

    public static function appreciation(?float $moyenne, ?string $ordre): string
    {
        $moyenne = (float) $moyenne;
        if ($ordre === 'fondamentale1') {
            return match (true) {
                $moyenne >= 9 => 'Très bien',
                $moyenne >= 8 => 'Bien',
                $moyenne >= 7 => 'Assez bien',
                $moyenne >= 5 => 'Passable',
                default => 'Insuffisant',
            };
        }

        return match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }

    /** Résultats de tous les élèves qui ont des notes sur la période, avec leur rang. */
    public function resultats(): Collection
    {
        return $this->resultats ??= $this->calculer();
    }

    public function pourEleve(int $idEleve): ?array
    {
        return $this->resultats()->get($idEleve);
    }

    /** Statistiques de la classe (élèves classés : moyenne > 0). */
    public function statistiques(): array
    {
        $moyennes = $this->resultats()->pluck('moyenne_periode')->filter(fn ($m) => $m > 0);

        return [
            'premier' => $moyennes->max() ?? 0,
            'dernier' => $moyennes->min() ?? 0,
            'classe' => $moyennes->count() ? $moyennes->avg() : 0,
            'effectif' => $moyennes->count(),
        ];
    }

    private function calculer(): Collection
    {
        $periodeCol = $this->mois ? 'mois' : 'id_trimestre';
        $periodeVal = $this->mois ?: $this->idTrimestre;
        if (!$periodeVal) {
            return collect();
        }

        $eleves = DB::table('eleve')
            ->where('id_classe', $this->classe->id_classe)
            ->where('id_annee', $this->idAnnee)
            ->where('etat_dossier', 0)
            ->orderBy('nom_eleve')->orderBy('prenom_eleve')
            ->get()
            ->keyBy('id_eleve');

        $notes = DB::table('ligne_evaluation as le')
            ->join('note as n', 'le.id_note', '=', 'n.id_note')
            ->whereIn('le.id_eleve', $eleves->keys())
            ->where('le.id_annee_scolaire', $this->idAnnee)
            ->where('le.'.$periodeCol, $periodeVal)
            ->when($this->filtrerNotesValidees(), fn ($q) => $q->where('le.validation_status', 'valide'))
            ->orderBy('le.id_ligneEvaluation')
            ->get(['le.id_eleve', 'le.id_matiere', 'le.id_evaluation', 'le.note', 'n.typeNote', 'n.codeNote'])
            ->groupBy('id_eleve');

        $this->choixNoteClasse = Schema::hasTable('note_classe_choix')
            ? DB::table('note_classe_choix')
                ->where('id_classe', $this->classe->id_classe)
                ->where('id_annee', $this->idAnnee)
                ->where($periodeCol, $periodeVal)
                ->where('mode', 'unique')
                ->pluck('id_evaluation', 'id_matiere')
                ->all()
            : [];

        $lignes = $this->matieresDeLaClasse();
        $conduites = $this->conduites($eleves->keys());
        $entete = $this->entete();

        $resultats = $eleves
            ->filter(fn ($eleve) => $notes->has($eleve->id_eleve))
            ->map(fn ($eleve) => $this->calculerEleve($eleve, $notes->get($eleve->id_eleve)->groupBy('id_matiere'), $lignes, $conduites[$eleve->id_eleve] ?? null, $entete));

        return $this->classer($resultats);
    }

    private function calculerEleve(object $eleve, Collection $notesParMatiere, Collection $lignes, ?float $conduite, array $entete): array
    {
        $matieres = $lignes
            // Une langue LV2 que l'élève n'a pas choisie n'apparaît pas : ses
            // « 0 » fausseraient le total des coefficients.
            ->reject(fn ($l) => $l->est_lv2 && (int) $l->id_matiere !== (int) $eleve->id_matiere_lv2)
            ->map(fn ($l) => $this->calculerMatiere(clone $l, $notesParMatiere->get($l->id_matiere, collect())))
            ->values();

        $apercu = (object) array_merge((array) $eleve, $entete);

        $donnees = [
            'apercu' => $apercu,
            'ecole' => $this->ecole,
            'matieres' => $matieres,
            'note_conduite' => $conduite,
            'appreciation_conduite' => $conduite ? self::appreciation($conduite, $this->ordre) : null,
            'ordre' => $this->ordre,
            'domaines' => collect(),
            'groupes' => [],
            'parties' => [],
        ];

        if ($this->calcul === 'domaines') {
            return $this->moyennesParDomaines($donnees);
        }
        if ($this->calcul === 'principales') {
            return $this->moyennesParGroupe($donnees);
        }

        $fondamental1 = $this->ordre === 'fondamentale1';
        $totalCoef = $fondamental1 ? $matieres->count() : $matieres->sum('coef');
        $totalNotes = $fondamental1 ? $matieres->sum('M_Gle') : $matieres->sum('M_Coef');
        if (!$fondamental1 && $conduite !== null) {
            $totalCoef += 1;
            $totalNotes += $conduite;
        }
        $donnees['moyenne_periode'] = $totalCoef ? $totalNotes / $totalCoef : 0;

        // Secondaire : matières affichées en « principales » puis « secondaires » sur tous les modèles.
        if (str_starts_with((string) $this->ordre, 'secondaire')) {
            $donnees['groupes'] = $this->grouperMatieres($donnees);
        }

        return $donnees;
    }

    private function calculerMatiere(object $matiere, Collection $notes): object
    {
        $lignesDevoirs = $notes->filter(fn ($n) => $this->estType($n, 'devoir', ['dv', 'devoir']));
        // Un seul devoir retenu pour cette matière et cette période : on ne garde que lui.
        if ($devoirRetenu = $this->choixNoteClasse[$matiere->id_matiere] ?? null) {
            $lignesDevoirs = $lignesDevoirs->where('id_evaluation', $devoirRetenu);
        }
        $devoirs = $lignesDevoirs->pluck('note')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
        $compo = $notes->first(fn ($n) => $this->estType($n, 'composition', ['cp', 'composition']));
        $nt10 = $notes->filter(fn ($n) => $this->estType($n, 'nt10', ['nt10']))->pluck('note')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);

        $noteClasse = $devoirs->isEmpty() ? null : $devoirs->avg();
        $noteCompo = $compo ? (float) $compo->note : null;

        $matiere->coef = (float) ($matiere->coef ?? 0);
        $matiere->note_classe = $noteClasse;
        $matiere->note_compo = $noteCompo;
        $matiere->nb_devoirs = $devoirs->count();
        $matiere->M_Class = $noteClasse ?? 0;
        $matiere->M_Compo = ($noteCompo ?? 0) * 2;
        $matiere->NT10 = $nt10->avg() ?? 0;

        if ($this->calcul === 'domaines') {
            $matiere->M_Gle = ($noteClasse === null && $noteCompo === null && $nt10->isNotEmpty())
                ? (float) $nt10->avg()
                : (($noteClasse ?? 0) + ($noteCompo ?? 0)) / 2;
            $matiere->M_Coef = $matiere->M_Gle * $matiere->coef;
        } elseif ($this->ordre === 'fondamentale1' && $this->calcul !== 'principales') {
            $matiere->M_Gle = (float) $matiere->NT10;
            $matiere->M_Coef = $matiere->M_Gle;
        } else {
            $matiere->M_Gle = ($matiere->M_Class + $matiere->M_Compo) / 3;
            $matiere->M_Coef = $matiere->M_Gle * $matiere->coef;
        }
        $matiere->appreciation = self::appreciation($matiere->M_Gle, $this->ordre);
        [$matiere->nom_fr, $matiere->nom_ar] = Domaines::separerNom($matiere->nom_matiere);

        return $matiere;
    }

    private function moyennesParDomaines(array $donnees): array
    {
        $domaines = Domaines::pourEcole((int) $this->classe->idEcole)->keyBy('id_domaine');
        $groupes = $donnees['matieres']->groupBy(fn ($m) => $m->id_domaine && $domaines->has($m->id_domaine) ? $m->id_domaine : 0);

        $resultat = $domaines->filter(fn ($d) => $groupes->has($d->id_domaine))
            ->map(fn ($d) => (object) ['id' => $d->id_domaine, 'nom' => $d->nom, 'nom_ar' => $d->nom_ar, 'partie' => $d->partie, 'matieres' => $groupes[$d->id_domaine]])
            ->values();
        if ($groupes->has(0)) {
            $resultat->push((object) ['id' => 0, 'nom' => 'Autres matières', 'nom_ar' => null, 'partie' => null, 'matieres' => $groupes[0]]);
        }

        foreach ($resultat as $domaine) {
            $coef = $domaine->matieres->sum('coef');
            $domaine->total_coef = $coef;
            $domaine->total = $domaine->matieres->sum('M_Coef');
            $domaine->moyenne = $coef > 0 ? $domaine->total / $coef : (float) $domaine->matieres->avg('M_Gle');
            $domaine->appreciation = self::appreciation($domaine->moyenne, $this->ordre);
        }

        $parties = [];
        foreach (array_keys(Domaines::PARTIES) as $partie) {
            $ceux = $resultat->where('partie', $partie);
            if ($ceux->isNotEmpty()) {
                $moyenne = $ceux->avg('moyenne');
                $parties[$partie] = ['moyenne' => $moyenne, 'appreciation' => self::appreciation($moyenne, $this->ordre)];
            }
        }

        $donnees['domaines'] = $resultat;
        $donnees['parties'] = $parties;
        $donnees['moyenne_periode'] = $resultat->isNotEmpty() ? $resultat->avg('moyenne') : 0;

        return $donnees;
    }

    /**
     * Matières principales puis secondaires (conduite comprise, coefficient 1) :
     * total et moyenne partielle de chaque groupe ; moyenne générale = moyenne
     * des deux moyennes partielles. Une matière sans groupe choisi est
     * principale si son coefficient est d'au moins 2.
     */
    private function moyennesParGroupe(array $donnees): array
    {
        $resultat = $this->grouperMatieres($donnees);

        $donnees['groupes'] = $resultat;
        $donnees['total_coef'] = collect($resultat)->sum('total_coef');
        $donnees['moyenne_periode'] = $resultat ? collect($resultat)->avg('moyenne') : 0;

        return $donnees;
    }

    /**
     * Matières principales puis secondaires (conduite comprise, coefficient 1),
     * avec total et moyenne partielle de chaque groupe. Sans groupe choisi
     * dans la classe, une matière est principale si son coefficient est
     * d'au moins 2.
     */
    private function grouperMatieres(array $donnees): array
    {
        $groupes = ['principales' => collect(), 'secondaires' => collect()];
        foreach ($donnees['matieres'] as $m) {
            $groupe = $m->groupe ?: ($m->coef >= 2 ? 'principale' : 'secondaire');
            $groupes[$groupe === 'principale' ? 'principales' : 'secondaires']->push($m);
        }
        if ($donnees['note_conduite'] !== null) {
            $groupes['secondaires']->push((object) [
                'nom_matiere' => 'Conduite', 'est_conduite' => true, 'coef' => 1.0,
                'note_classe' => null, 'note_compo' => null, 'M_Class' => null, 'M_Compo' => null,
                'M_Gle' => $donnees['note_conduite'], 'M_Coef' => $donnees['note_conduite'],
                'appreciation' => $donnees['appreciation_conduite'],
            ]);
        }

        $resultat = [];
        foreach ($groupes as $cle => $matieres) {
            if ($matieres->isEmpty()) {
                continue;
            }
            $coef = $matieres->sum('coef');
            $total = $matieres->sum('M_Coef');
            $resultat[$cle] = (object) [
                'matieres' => $matieres->values(),
                'total_coef' => $coef,
                'total' => $total,
                'moyenne' => $coef > 0 ? $total / $coef : 0,
            ];
        }

        return $resultat;
    }

    /** Rang (ex aequo : même rang), moyenne du premier et effectif classé. */
    private function classer(Collection $resultats): Collection
    {
        $classes = $resultats->filter(fn ($r) => $r['moyenne_periode'] > 0)
            ->sortByDesc(fn ($r) => round((float) $r['moyenne_periode'], 2))
            ->values();

        $rangs = [];
        $precedente = null;
        $rangPrecedent = 0;
        foreach ($classes as $index => $r) {
            $moyenne = round((float) $r['moyenne_periode'], 2);
            $rangPrecedent = ($precedente !== null && $moyenne === $precedente) ? $rangPrecedent : $index + 1;
            $rangs[$r['apercu']->id_eleve] = $rangPrecedent;
            $precedente = $moyenne;
        }
        $premier = $classes->first()['moyenne_periode'] ?? 0;
        $parRang = array_count_values($rangs);

        return $resultats->map(function ($r) use ($rangs, $premier, $classes, $parRang) {
            $r['rang'] = $rangs[$r['apercu']->id_eleve] ?? null;
            $r['exaequo'] = $r['rang'] !== null && $parRang[$r['rang']] > 1;
            $r['total_eleves'] = $classes->count();
            $r['moyenne_premier'] = $premier;

            return $r;
        });
    }

    private function matieresDeLaClasse(): Collection
    {
        // Les domaines ne servent qu'au calcul par domaines : on ne crée pas la
        // liste de l'école pour un bulletin classique.
        $domaines = $this->calcul === 'domaines' ? Domaines::pourEcole((int) $this->classe->idEcole) : collect();

        return DB::table('ligneclasse as lc')
            ->join('matiere as m', 'lc.id_matiere', '=', 'm.id_matiere')
            ->leftJoin('enseignants as en', 'en.id_enseignant', '=', 'lc.id_enseignants')
            ->where('lc.id_classe', $this->classe->id_classe)
            ->orderBy('lc.id_ligneclasse')
            ->get(['m.nom_matiere', 'lc.coefficient as coef', 'lc.id_matiere', 'm.est_lv2', 'lc.id_domaine', 'lc.groupe', 'm.domaine_defaut', 'en.nom_prenom_enseignant as enseignant'])
            ->map(function ($l) use ($domaines) {
                $l->id_domaine = $l->id_domaine ?: Domaines::defautPourMatiere($domaines, $l->domaine_defaut);

                return $l;
            });
    }

    /** Notes de conduite de la classe (trimestre seulement, hors Fondamental I). */
    private function conduites(Collection $idsEleves): array
    {
        if ($this->ordre === 'fondamentale1' || $this->mois || !$this->idTrimestre) {
            return [];
        }

        $idEcole = (int) $this->classe->idEcole;
        app(ConductNoteService::class)->syncClass((int) $this->classe->id_classe, $this->idAnnee, $this->idTrimestre, $idEcole);

        return DB::table('conduite')
            ->where('id_classe', $this->classe->id_classe)
            ->where('id_annee_scolaire', $this->idAnnee)
            ->where('id_trimestre', $this->idTrimestre)
            ->whereIn('id_eleve', $idsEleves)
            ->pluck('note_conduite', 'id_eleve')
            ->map(fn ($n) => $n === null ? null : (float) $n)
            ->all();
    }

    /** Informations communes à tous les bulletins : classe, année, période. */
    private function entete(): array
    {
        $annee = AnneeScolaire::withoutGlobalScopes()->find($this->idAnnee);
        $mois = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];

        return array_filter([
            'nom_classe' => $this->classe->nom_classe,
            'ordreEnseignement' => $this->ordre,
            'annee' => $annee?->annee,
            'annee_debut' => $annee?->date_debut,
            'annee_fin' => $annee?->date_fin,
            'nom_trimestre' => $this->idTrimestre ? DB::table('trimestre')->where('id_trimestre', $this->idTrimestre)->value('nom_trimestre') : null,
            'mois_nom' => $this->mois ? ($mois[$this->mois] ?? null) : null,
        ], fn ($v) => $v !== null);
    }

    private function estType(object $note, string $type, array $codes): bool
    {
        return strtolower((string) $note->typeNote) === $type || in_array(strtolower((string) $note->codeNote), $codes, true);
    }

    private function filtrerNotesValidees(): bool
    {
        return Schema::hasColumn('ligne_evaluation', 'validation_status')
            && strtolower(trim((string) $this->ecole->statut)) === 'prive';
    }
}
