<?php

namespace App\Services\Rapports;

use App\Support\CahierBordDonnees;
use App\Support\PropositionPassageDonnees;
use App\Support\RapportSecondaireDefinitions;
use App\Support\RapportSuccinctDonnees;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export Excel des fiches remises au CAP ou à l'académie (rapport succinct, cahier de bord, proposition de passage,
 * rapports de rentrée et de fin d'année) : les mêmes données que le PDF, en tableaux que l'on peut retravailler.
 */
class ExportExcel
{
    private Spreadsheet $classeur;

    private Worksheet $feuille;

    private int $ligne = 1;

    private int $colonnes = 2;

    /** @var list<int> lignes de titre, qui débordent sur les colonnes voisines */
    private array $titres = [];

    public function __construct(string $titre)
    {
        $this->classeur = new Spreadsheet;
        $this->classeur->getProperties()->setTitle($titre)->setCreator('KalanNet');
        $this->feuille = $this->classeur->getActiveSheet()->setTitle(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $titre), 0, 31));
        $this->titre($titre, 14);
    }

    public function titre(string $texte, int $taille = 11): self
    {
        $this->feuille->setCellValue('A'.$this->ligne, $texte);
        $this->feuille->getStyle('A'.$this->ligne)->getFont()->setBold(true)->setSize($taille);
        $this->titres[] = $this->ligne;
        $this->ligne += $taille > 11 ? 2 : 1;

        return $this;
    }

    /** Libellé et valeur côte à côte, deux paires par ligne. @param array<string, mixed> $paires */
    public function paires(array $paires): self
    {
        foreach (array_chunk($paires, 2, true) as $deux) {
            $col = 1;
            foreach ($deux as $libelle => $valeur) {
                $this->feuille->setCellValue([$col, $this->ligne], $libelle);
                $this->ecrire($col + 1, $this->ligne, $valeur);
                $this->feuille->getStyle([$col + 1, $this->ligne])->getFont()->setBold(true);
                $col += 2;
            }
            $this->ligne++;
        }
        $this->colonnes = max($this->colonnes, 4);
        $this->ligne++;

        return $this;
    }

    /**
     * Un tableau encadré : lignes d'en-tête (cellules [texte, largeur]), lignes de données, dernière ligne en gras si total.
     *
     * @param  list<list<array{0: string, 1: int}>>  $entetes
     * @param  list<list<mixed>>  $lignes
     * @param  list<int>  $grasLignes  index des lignes à mettre en gras
     */
    public function tableau(?string $titre, array $entetes, array $lignes, bool $total = false, array $grasLignes = []): self
    {
        if ($titre) {
            $this->titre($titre);
        }
        $debut = $this->ligne;
        $largeur = 1;
        foreach ($entetes as $entete) {
            $col = 1;
            foreach ($entete as [$texte, $span]) {
                $this->feuille->setCellValue([$col, $this->ligne], $texte);
                if ($span > 1) {
                    $this->feuille->mergeCells([$col, $this->ligne, $col + $span - 1, $this->ligne]);
                }
                $col += $span;
            }
            $largeur = max($largeur, $col - 1);
            $this->ligne++;
        }
        if ($entetes) {
            $style = $this->feuille->getStyle([1, $debut, $largeur, $this->ligne - 1]);
            $style->getFont()->setBold(true);
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E5E5');
            $style->getAlignment()->setWrapText(true)->setHorizontal('center')->setVertical('center');
        }
        foreach ($lignes as $i => $valeurs) {
            foreach (array_values($valeurs) as $col => $valeur) {
                $this->ecrire($col + 1, $this->ligne, $valeur);
            }
            $largeur = max($largeur, count($valeurs));
            if (in_array($i, $grasLignes, true) || ($total && $i === count($lignes) - 1)) {
                $this->feuille->getStyle([1, $this->ligne, $largeur, $this->ligne])->getFont()->setBold(true);
            }
            $this->ligne++;
        }
        if ($this->ligne > $debut) {
            $this->feuille->getStyle([1, $debut, $largeur, $this->ligne - 1])->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        $this->colonnes = max($this->colonnes, $largeur);
        $this->ligne++;

        return $this;
    }

    private function ecrire(int $col, int $ligne, mixed $valeur): void
    {
        if ($valeur === null || $valeur === '') {
            return;
        }
        // Matricules, téléphones, dates : gardés tels quels, jamais convertis en nombre ou en date.
        if (is_int($valeur) || is_float($valeur)) {
            $this->feuille->setCellValue([$col, $ligne], $valeur);
        } else {
            $this->feuille->setCellValueExplicit([$col, $ligne], (string) $valeur, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }

    public function telecharger(string $nom)
    {
        // Colonne des libellés à largeur fixe (les titres de tableaux ne l'élargissent pas), chiffres ajustés à leur contenu.
        $this->feuille->getColumnDimension('A')->setWidth(34);
        foreach (range(2, max(2, $this->colonnes)) as $col) {
            $this->feuille->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }
        $this->feuille->getStyle('A1:A'.$this->ligne)->getAlignment()->setWrapText(true);
        foreach ($this->titres as $ligne) {
            $this->feuille->getStyle('A'.$ligne)->getAlignment()->setWrapText(false);
        }
        $this->feuille->getPageSetup()->setOrientation('landscape')->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $classeur = $this->classeur;

        return response()->streamDownload(function () use ($classeur) {
            (new Xlsx($classeur))->save('php://output');
            $classeur->disconnectWorksheets();
        }, $nom.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /* ——— Fiches ——— */

    private static function simple(array $libelles): array
    {
        return [array_map(fn ($l) => [$l, 1], $libelles)];
    }

    public static function rapportSuccinct(array $d, string $annee): self
    {
        $C = RapportSuccinctDonnees::class;
        $secondaire = ($d['portee'] ?? 'fondamental') === 'secondaire';
        $x = new self('Rapport succinct de rentrée '.$annee);
        $infos = [($secondaire ? 'Établissement' : 'École') => $d['ecole'] ?? null, 'Village / quartier' => $d['quartier'] ?? null, 'Commune' => $d['commune'] ?? null];
        if (!$secondaire) {
            $infos['CAP'] = $d['cap'] ?? null;
        }
        $infos += ['Cercle' => $d['cercle'] ?? null, 'Académie' => $d['academie'] ?? null,
            'Enseignement' => ($secondaire ? $C::CYCLES_SECONDAIRE : $C::CYCLES)[$d['cycle'] ?? 0] ?? null, 'Statut' => $C::STATUTS[$d['statut'] ?? 0] ?? null];
        if (!$secondaire) {
            $infos += ['Type d\'enseignement' => $C::ENSEIGNEMENTS[$d['enseignement'] ?? 0] ?? null, 'Mode de recrutement' => $C::RECRUTEMENTS[$d['recrutement'] ?? 0] ?? null, 'Langue (pédagogie convergente)' => $d['langue'] ?? null];
        }
        $x->titre('Tableau 1 : Renseignements généraux')->paires($infos);

        $t = $C::totaux($d);
        $lignes = collect($d['groupes'] ?? [])->map(fn ($l) => [$l['libelle'], (int) $l['groupes'], (int) $l['g'], (int) $l['f'], (int) $l['g'] + (int) $l['f'], (int) $l['rg'], (int) $l['rf'], (int) $l['rg'] + (int) $l['rf']])->all();
        $lignes[] = [$secondaire ? 'Total établissement' : 'Total école', $t['groupes'], $t['g'], $t['f'], $t['eleves'], $t['rg'], $t['rf'], $t['redoublants']];
        $x->tableau('Tableau 2 : Groupes pédagogiques et effectifs scolaires', [
            [[$secondaire ? 'Classe / série' : "Année d'études", 1], ['Groupes pédagogiques', 1], ['Élèves', 2], ['Total élèves', 1], ['Redoublants', 2], ['Total redoublants', 1]],
            [['', 1], ['', 1], ['G', 1], ['F', 1], ['', 1], ['G', 1], ['F', 1], ['', 1]],
        ], $lignes, true);

        $cycles = ['prescolaire' => 'Préscolaire', 'premier' => 'Premier cycle', 'second' => 'Second cycle', 'general' => 'Secondaire général', 'technique' => 'Technique et professionnel'];
        $x->tableau('Tableau 3 : '.($secondaire ? 'Enseignants' : 'Maîtres en classe').' et salles de classe', self::simple(['Cycle', 'Permanents', 'Contractuels', 'Total', 'Salles de classe']),
            collect($d['maitres'] ?? [])->map(fn ($m, $c) => [$cycles[$c] ?? $c, (int) $m['permanents'], (int) $m['contractuels'], (int) $m['permanents'] + (int) $m['contractuels'], $m['salles'] ?? null])->values()->all());

        return $x;
    }

    public static function cahierBord(array $d, string $annee): self
    {
        $C = CahierBordDonnees::class;
        $cycle = $d['cycle'] ?? 'premier';
        $x = new self('Cahier de bord '.$annee.' ('.($C::CYCLES[$cycle] ?? $cycle).')');
        $x->paires(['Établissement' => $d['ecole'] ?? null, 'Dirigé par' => $d['directeur'] ?? null, 'Contact' => $d['contact'] ?? null, 'Depuis' => $d['depuis'] ?? null,
            'Régime de recrutement' => RapportSuccinctDonnees::RECRUTEMENTS[$d['regime'] ?? 0] ?? null, 'Commune' => $d['commune'] ?? null,
            'CAP' => $cycle === 'secondaire' ? null : ($d['cap'] ?? null), 'Académie' => $d['academie'] ?? null]);

        $effectifs = collect($d['effectifs'] ?? []);
        $lignes = $effectifs->map(fn ($l) => [$l['libelle'], (int) $l['groupes'], (int) $l['g'] - (int) $l['rg'], (int) $l['f'] - (int) $l['rf'], (int) $l['rg'], (int) $l['rf'], (int) $l['g'] + (int) $l['f']])->all();
        $somme = fn ($c) => $effectifs->sum(fn ($l) => (int) $l[$c]);
        $lignes[] = ['Total', $somme('groupes'), $somme('g') - $somme('rg'), $somme('f') - $somme('rf'), $somme('rg'), $somme('rf'), $somme('g') + $somme('f')];
        $nouveaux = $cycle === 'jardin' ? 'Nouveaux' : 'Recrutés / passants';
        $x->tableau('I. Situation des effectifs', [
            [['', 1], ['Nbre de cours', 1], [$nouveaux, 2], [$cycle === 'jardin' ? 'Anciens' : 'Redoublants', 2], ['Total', 1]],
            [['', 1], ['', 1], ['G', 1], ['F', 1], ['G', 1], ['F', 1], ['', 1]],
        ], $lignes, true);

        $dates = array_values($C::DATES);
        $x->tableau('II. Personnel administratif', self::simple(['N°', 'Prénom et nom', 'Date nais.', 'N° Mle', 'Classe / échelon', 'S.F', 'Fonction', 'Diplôme', ...$dates, 'Contact']),
            collect($d['administratifs'] ?? [])->values()->map(fn ($p, $i) => [$i + 1, ...array_map(fn ($c) => $p[$c] ?? null, $C::CHAMPS_ADMINISTRATIF)])->all());

        $salles = collect($C::SALLES)->mapWithKeys(fn ($l, $c) => ['Salles en '.mb_strtolower($l) => $d['salles'][$c] ?? null])->all();
        $x->titre('III. Salles de classe')->paires($salles + ['Date de création' => $d['date_creation'] ?? null, "Date d'ouverture" => $d['date_ouverture'] ?? null]);
        $x->tableau('IV. Tables-bancs', self::simple(['', ...$effectifs->pluck('libelle')->all(), 'Total']),
            collect($C::TABLES)->map(fn ($libelle, $type) => [$libelle, ...$effectifs->map(fn ($l) => $d['tables'][$l['cle']][$type] ?? null)->all(), $effectifs->sum(fn ($l) => (int) ($d['tables'][$l['cle']][$type] ?? 0))])->values()->all());
        $x->titre('Équipements')->paires(collect($C::EQUIPEMENTS)->mapWithKeys(fn ($l, $c) => [$l => $d['equipements'][$c] ?? null])->all());

        $enseignants = collect($d['enseignants'] ?? [])->values();
        $x->tableau('V. Personnel enseignant (noms des femmes en gras)', self::simple(['N°', 'Prénom et nom', 'Date nais.', 'N° Mle', 'Classe / échelon', 'S.F', 'Fonction', 'Spécialité', 'Classes tenues', 'Diplôme', ...$dates, 'Contact']),
            $enseignants->map(fn ($e, $i) => [$i + 1, $e['nom'], $e['naissance'], $e['matricule'], ...array_map(fn ($c) => $e[$c] ?? null, $C::CHAMPS_ENSEIGNANT)])->all(),
            false, $enseignants->keys()->filter(fn ($i) => !empty($enseignants[$i]['femme']))->values()->all());

        return $x;
    }

    /** @param array<string, mixed> $e en-tête (voir PropositionPassageDonnees::entete) */
    public static function propositionPassage(array $e, array $lignes, bool $detaillee, ?array $recapCap): self
    {
        $D = PropositionPassageDonnees::class;
        $x = new self('Proposition de passage '.$e['classe'].' '.$e['annee']);
        $x->paires(['École' => $e['ecole'], 'Classe' => $e['classe'], 'Année scolaire' => $e['annee'], 'Cycle' => $e['cycle'], 'Académie' => $e['academie'], 'CAP' => $e['cap']]);

        $colonnes = ['N°', 'Prénom', 'Nom', 'Sexe', 'Date de naissance', 'Lieu de naissance', 'Père', 'Mère', 'N° Mle'];
        $champs = $detaillee
            ? ['annee_recrutement' => 'Année de recrutement', 'doublees_1er' => 'Classes doublées', 'scolarite_1er' => 'Scolarité', 'annee_7e' => 'Entrée en 7e', 'annees_7' => 'Années en 7e', 'annees_8' => 'Années en 8e', 'annees_9' => 'Années en 9e']
            : ['scolarite' => 'Scolarité', 'doublees' => 'Classes doublées'];
        $rangs = [];
        $n = 0;
        foreach ($lignes as $l) {
            $rangs[] = [++$n, $l['prenom'] ?? null, $l['nom'] ?? null, $l['sexe'] ?? null, $l['date_naissance'] ?? null, $l['lieu_naissance'] ?? null, $l['pere'] ?? null, $l['mere'] ?? null, $l['matricule'] ?? null,
                ...array_map(fn ($c) => $l[$c] ?? null, array_keys($champs)),
                isset($l['moyenne']) ? round((float) $l['moyenne'], 2) : null, $D::DECISIONS[$l['proposition'] ?? ''] ?? null, $D::DECISIONS[$l['decision_cap'] ?? ''] ?? null, $l['observation_cap'] ?? $l['observation'] ?? null];
        }
        $x->tableau('Liste des élèves', self::simple([...$colonnes, ...array_values($champs), 'Moyenne annuelle', 'Proposition du conseil des maîtres', 'Décision du CAP', 'Observation']), $rangs);

        $recap = fn (?array $r) => collect(['passe' => 'Passent', 'redouble' => 'Redoublent', 'exclu' => 'Exclu(e)s', 'abandon' => 'Abandons', 'total' => 'Total'])
            ->map(fn ($libelle, $k) => [$libelle, $r[$k]['g'] ?? null, $r[$k]['f'] ?? null, $r[$k]['t'] ?? null])->values()->all();
        $x->tableau('Proposition du conseil des maîtres', self::simple(['', 'Garçons', 'Filles', 'Total']), $recap($D::recap($lignes)), true);
        $x->tableau('Proposition du CAP', self::simple(['', 'Garçons', 'Filles', 'Total']), $recap($recapCap), true);

        return $x;
    }

    /** @param list<array<string, mixed>> $tableaux */
    public static function rapportSecondaire(array $d, string $type, string $annee, array $tableaux, array $annexes = []): self
    {
        $x = new self(RapportSecondaireDefinitions::TYPES[$type].' '.$annee);
        $x->titre('Partie administrative');
        foreach (RapportSecondaireDefinitions::redaction($type) as $cle => [$titre]) {
            $x->tableau($titre, [], [[(string) ($d['redaction'][$cle] ?? '')]]);
        }
        $x->titre('T0 : Informations générales')->paires(collect(RapportSecondaireDefinitions::infos($type))->mapWithKeys(fn ($l, $c) => [$l => $d['infos'][$c] ?? null])->all());

        foreach ($tableaux as $tableau) {
            $groupee = collect($tableau['colonnes'])->contains(fn ($g) => $g['libelle'] !== null);
            $colonnes = collect($tableau['colonnes'])->flatMap(fn ($g) => $g['cols'])->all();
            $totalLigne = in_array($tableau['totaux'] ?? null, ['colonne', 'les deux'], true);
            $totalColonne = in_array($tableau['totaux'] ?? null, ['ligne', 'les deux'], true);
            $valeurs = $d['t'][$tableau['code']] ?? [];

            $entetes = $groupee ? [
                [['', 1], ...array_map(fn ($g) => [(string) $g['libelle'], count($g['cols'])], $tableau['colonnes']), ...($totalLigne ? [['Total', 1]] : [])],
                [['', 1], ...array_map(fn ($l) => [$l, 1], array_values($colonnes)), ...($totalLigne ? [['', 1]] : [])],
            ] : self::simple(['', ...array_values($colonnes), ...($totalLigne ? ['Total'] : [])]);
            $lignes = [];
            $numero = 0;
            foreach ($tableau['lignes'] as $cle => $libelle) {
                if (!empty($tableau['liste'])) {
                    if (empty($valeurs[$cle])) {
                        continue; // liste : seulement les lignes remplies, renumérotées
                    }
                    $libelle = (string) ++$numero;
                }
                $cases = array_map(fn ($c) => $valeurs[$cle][$c] ?? null, array_keys($colonnes));
                $lignes[] = [$libelle, ...$cases, ...($totalLigne ? [array_sum(array_map('intval', array_filter($cases, 'is_numeric')))] : [])];
            }
            if ($totalColonne) {
                $sommes = array_map(fn ($c) => collect($valeurs)->sum(fn ($v) => (int) ($v[$c] ?? 0)), array_keys($colonnes));
                $lignes[] = ['Total', ...$sommes, ...($totalLigne ? [array_sum($sommes)] : [])];
            }
            $x->tableau($tableau['code'].' : '.$tableau['titre'], $entetes, $lignes, $totalColonne);
        }
        foreach ($annexes as $annexe) {
            $x->tableau('Annexe : '.$annexe['titre'], self::simple($annexe['entetes']), $annexe['lignes']);
        }

        return $x;
    }
}
