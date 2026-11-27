<?php

namespace App\Services\Evaluations;

use App\Support\NomAcademie;
use App\Support\Ordres;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Fiches de notes vierges en Excel : même contenu que le PDF (une feuille par matière), à imprimer ou à remplir
 * à l'ordinateur. Le CAP n'apparaît que pour les cycles qui en relèvent ; le secondaire n'a que l'Académie.
 */
class FichesNotesExcel
{
    /**
     * @param  Collection<int, array{matiere: object, enseignant: ?string, eleves: Collection}>  $fiches
     * @param  array<int, array{libelle: string, sur: float}>  $colonnes
     */
    public function creer(Collection $fiches, object $classe, object $annee, array $colonnes, ?string $periode, ?object $ecole): Spreadsheet
    {
        $classeur = new Spreadsheet;
        $classeur->getProperties()->setTitle('Fiches de notes')->setCreator('KalanNet');
        $classeur->removeSheetByIndex(0);
        $noms = [];

        foreach ($fiches as $fiche) {
            $feuille = $classeur->createSheet()->setTitle($this->nomFeuille($fiche['matiere']->nom_matiere, $noms));
            $this->remplir($feuille, $fiche, $classe, $annee, $colonnes, $periode, $ecole);
        }
        $classeur->setActiveSheetIndex(0);

        return $classeur;
    }

    private function remplir(Worksheet $f, array $fiche, object $classe, object $annee, array $colonnes, ?string $periode, ?object $ecole): void
    {
        $nbNotes = count($colonnes);
        $derniere = $this->col(4 + $nbNotes + 1); // N°, Matricule, Nom, Prénom(s), notes…, Observation
        $sur = fn (float $v) => rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ',');

        // En-tête officiel : pays à gauche, académie, CAP seulement sous CAP (jardin, Fondamental I et II).
        $ligne = 1;
        if ($ecole?->pays?->entete_document_gauche) {
            $f->setCellValue("A{$ligne}", $ecole->pays->entete_document_gauche)->getStyle("A{$ligne}")->getFont()->setBold(true);
            $ligne++;
        }
        $f->setCellValue("A{$ligne}", 'Académie d\'Enseignement de '.NomAcademie::sansPrefixe($ecole?->academieRef?->nom_academie ?? $ecole?->academie));
        $ligne++;
        if (blank($classe->ordreEnseignement ?? null) || Ordres::sousCap($classe->ordreEnseignement)) {
            $f->setCellValue("A{$ligne}", 'CAP de '.NomAcademie::capSansPrefixe($ecole?->cap));
            $ligne++;
        }
        if ($ecole?->pays?->entete_document_droite) {
            $f->setCellValue("{$derniere}1", str_replace(["\r\n", "\r"], "\n", $ecole->pays->entete_document_droite));
            $f->getStyle("{$derniere}1")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $f->getStyle("{$derniere}1")->getFont()->setBold(true);
        }

        $ligne++;
        $f->mergeCells("A{$ligne}:{$derniere}{$ligne}")->setCellValue("A{$ligne}", mb_strtoupper((string) ($ecole->nomEcole ?? '')));
        $f->getStyle("A{$ligne}")->getFont()->setBold(true)->setSize(13);
        $f->getStyle("A{$ligne}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // Logo de l'école à gauche du nom (PNG, JPEG ou GIF seulement).
        if (($logo = \App\Support\LogoEcole::chemin($ecole)) && \App\Support\LogoEcole::dataUri($ecole)) {
            $f->getRowDimension($ligne)->setRowHeight(42);
            $f->getStyle("A{$ligne}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            (new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing)->setName('Logo')->setPath($logo)
                ->setHeight(52)->setCoordinates("A{$ligne}")->setOffsetX(2)->setOffsetY(2)->setWorksheet($f);
        }
        $ligne++;
        $f->mergeCells("A{$ligne}:{$derniere}{$ligne}")->setCellValue("A{$ligne}", 'FICHE DE NOTES');
        $f->getStyle("A{$ligne}")->getFont()->setBold(true)->setSize(15);
        $f->getStyle("A{$ligne}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ligne += 2;
        $infos = [
            ['Classe : '.$classe->nom_classe, 'Matière : '.$fiche['matiere']->nom_matiere, 'Année scolaire : '.$annee->annee],
            ['Enseignant(e) : '.($fiche['enseignant'] ?: '……………………'), 'Période : '.($periode ?: '……………………'), 'Effectif : '.$fiche['eleves']->count().' élève(s)'],
        ];
        foreach ($infos as [$a, $b, $c]) {
            $f->mergeCells("A{$ligne}:C{$ligne}")->setCellValue("A{$ligne}", $a);
            $f->setCellValue("D{$ligne}", $b);
            $f->mergeCells("E{$ligne}:{$derniere}{$ligne}")->setCellValue("E{$ligne}", $c);
            $f->getStyle("A{$ligne}:{$derniere}{$ligne}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $ligne++;
        }
        $f->mergeCells("A{$ligne}:{$derniere}{$ligne}")->setCellValue("A{$ligne}", 'Notes écrites lisiblement, sur le barème indiqué en haut de chaque colonne. Élève absent : écrire « Abs ». Fiche à remettre à l\'administration pour la saisie dans KalanNet.');
        $f->getStyle("A{$ligne}")->getFont()->setItalic(true)->setSize(9);
        $f->getStyle("A{$ligne}")->getAlignment()->setWrapText(true);
        $f->getRowDimension($ligne)->setRowHeight(26);

        // Tableau : titres, ligne des dates, élèves.
        $ligne += 2;
        $titre = $ligne;
        $entetes = array_merge(['N°', 'Matricule', 'Nom', 'Prénom(s)'], array_map(fn ($c) => $c['libelle']."\n/ ".$sur($c['sur']), $colonnes), ['Observation']);
        $f->fromArray($entetes, null, "A{$titre}");
        $f->getRowDimension($titre)->setRowHeight(30);
        $dates = $titre + 1;
        $f->mergeCells("A{$dates}:D{$dates}")->setCellValue("A{$dates}", 'Date');
        $f->getStyle("A{$dates}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $f->getStyle("A{$dates}")->getFont()->setItalic(true);

        $premiere = $dates + 1;
        foreach ($fiche['eleves']->values() as $i => $eleve) {
            $r = $premiere + $i;
            $f->setCellValue("A{$r}", $i + 1);
            $f->setCellValueExplicit("B{$r}", (string) $eleve->matricule, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $f->setCellValue("C{$r}", $eleve->nom_eleve);
            $f->setCellValue("D{$r}", $eleve->prenom_eleve);
            $f->getRowDimension($r)->setRowHeight(18);
        }
        $fin = max($premiere, $premiere + $fiche['eleves']->count() - 1);

        $tableau = $f->getStyle("A{$titre}:{$derniere}{$fin}");
        $tableau->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $tableau->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $entete = $f->getStyle("A{$titre}:{$derniere}{$titre}");
        $entete->getFont()->setBold(true);
        $entete->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
        $entete->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $f->getStyle("A{$dates}:{$derniere}{$dates}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $f->getStyle("A{$premiere}:A{$fin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Cases de notes : nombre entre 0 et le barème ; « Abs » reste possible (simple avertissement).
        foreach ($colonnes as $k => $colonne) {
            $lettre = $this->col(5 + $k);
            $f->getStyle("{$lettre}{$premiere}:{$lettre}{$fin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $validation = (new DataValidation)->setType(DataValidation::TYPE_DECIMAL)->setOperator(DataValidation::OPERATOR_BETWEEN)
                ->setFormula1('0')->setFormula2((string) $colonne['sur'])->setAllowBlank(true)
                ->setErrorStyle(DataValidation::STYLE_WARNING)->setShowErrorMessage(true)
                ->setErrorTitle($colonne['libelle'])->setError('Note entre 0 et '.$sur($colonne['sur']).' (ou « Abs » pour un absent).');
            $f->setDataValidation("{$lettre}{$premiere}:{$lettre}{$fin}", $validation);
        }

        // Signatures.
        $s = $fin + 2;
        $f->setCellValue("A{$s}", 'L\'enseignant(e) — date et signature :');
        $f->setCellValue("E{$s}", 'Reçue par l\'administration le : ……………………');
        $f->setCellValue('E'.($s + 1), 'Saisie dans KalanNet le : …………………… par : ……………………');

        // Largeurs et mise en page A4 : tableau ajusté à la largeur, titres répétés sur chaque page.
        foreach (['A' => 5, 'B' => 17, 'C' => 18, 'D' => 22] as $c => $w) {
            $f->getColumnDimension($c)->setWidth($w);
        }
        foreach ($colonnes as $k => $colonne) {
            $f->getColumnDimension($this->col(5 + $k))->setWidth(12);
        }
        $f->getColumnDimension($derniere)->setWidth(20);
        $f->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd($titre, $dates);
        $f->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.35)->setRight(0.35);
        $f->freezePane('A'.$premiere);
    }

    private function col(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }

    /** Nom de feuille Excel : 31 caractères au plus, sans : \ / ? * [ ], unique dans le classeur. */
    private function nomFeuille(string $matiere, array &$deja): string
    {
        $base = mb_substr(trim(preg_replace('/[:\\\\\/?*\[\]]+/u', ' ', $matiere)) ?: 'Matière', 0, 28);
        $nom = $base;
        for ($i = 2; in_array(mb_strtolower($nom), $deja, true); $i++) {
            $nom = $base.' '.$i;
        }
        $deja[] = mb_strtolower($nom);

        return $nom;
    }
}
