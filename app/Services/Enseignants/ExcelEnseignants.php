<?php

namespace App\Services\Enseignants;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as DateExcel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Fichiers Excel des enseignants : modèle d'import (feuille « Enseignants » à
 * remplir, listes déroulantes, mode d'emploi), export de la liste (mêmes
 * colonnes, donc réimportable) et lecture d'un fichier importé.
 */
class ExcelEnseignants
{
    public const MAX_LIGNES = 500;

    /** Colonne => [clé du formulaire, titre, obligatoire, largeur]. */
    public const COLONNES = [
        'A' => ['nom_prenom', 'Nom et prénom', true, 28],
        'B' => ['telephone', 'Téléphone', true, 16],
        'C' => ['genre', 'Genre', false, 12],
        'D' => ['email', 'Email', false, 26],
        'E' => ['date_naissance', 'Date de naissance', false, 16],
        'F' => ['lieu_naissance', 'Lieu de naissance', false, 18],
        'G' => ['diplome', 'Diplôme', false, 18],
        'H' => ['specialite', 'Spécialité', false, 18],
        'I' => ['type_contrat', 'Type de contrat', false, 16],
        'J' => ['salaire', 'Salaire mensuel (CDI / CDD)', false, 18],
        'K' => ['prix_heure', 'Prix de l\'heure (VCT)', false, 16],
        'L' => ['nombre_heure', 'Nombre d\'heures (VCT)', false, 16],
        'M' => ['matricule', 'Matricule', false, 20],
    ];

    /** @param array<string, string> $contrats types de contrat permis à l'école */
    public function modele(array $contrats): Spreadsheet
    {
        $classeur = new Spreadsheet;
        $classeur->getProperties()->setTitle('Modèle d\'import des enseignants')->setCreator('KalanNet');
        $this->feuilleListes($classeur, $contrats);

        $feuille = $classeur->createSheet(0)->setTitle('Enseignants');
        $this->entetes($feuille, true);

        $fin = self::MAX_LIGNES + 1;
        $this->liste($feuille, "C2:C{$fin}", '=Listes!$A$2:$A$3', 'Genre', 'Masculin ou Féminin.');
        $this->liste($feuille, "I2:I{$fin}", '=Listes!$B$2:$B$'.(count($contrats) + 1), 'Type de contrat', 'Choisissez dans la liste. Laissez vide si inconnu.');
        $feuille->getStyle("E2:E{$fin}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $feuille->getStyle("B2:B{$fin}")->getNumberFormat()->setFormatCode('@');
        $feuille->getStyle("M2:M{$fin}")->getNumberFormat()->setFormatCode('@');

        $aide = $classeur->createSheet()->setTitle('Mode d\'emploi');
        $consignes = [
            'Remplissez la feuille « Enseignants » : une ligne par enseignant, à partir de la ligne 2. Ne modifiez pas la ligne des titres.',
            'Colonnes obligatoires (*) : Nom et prénom, Téléphone. Toutes les autres sont facultatives.',
            'Téléphone : un numéro par enseignant, différent de ceux déjà enregistrés dans l\'école.',
            'Date de naissance : au format JJ/MM/AAAA (ex. 14/02/1985).',
            'Genre et Type de contrat : choisissez dans la liste déroulante de la cellule.',
            'Salaire mensuel : seulement pour un contrat CDI ou CDD. Prix de l\'heure et nombre d\'heures : seulement pour un VCT.',
            'Matricule : laissez vide pour qu\'il soit créé automatiquement.',
            'Le fichier est vérifié en entier avant l\'import : s\'il contient une erreur, aucun enseignant n\'est ajouté et chaque ligne à corriger vous est indiquée.',
            self::MAX_LIGNES.' enseignants au plus par fichier. Formats acceptés : .xlsx, .xls.',
        ];
        $lignes = [['Import des enseignants — mode d\'emploi'], ['']];
        foreach ($consignes as $i => $consigne) {
            $lignes[] = [($i + 1).'. '.$consigne];
        }
        $aide->fromArray($lignes, null, 'A1');
        $aide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $aide->getColumnDimension('A')->setWidth(120);

        $classeur->setActiveSheetIndex(0);

        return $classeur;
    }

    /**
     * Liste des enseignants, avec les colonnes du modèle d'import et leur statut. Genre et Type de contrat gardent
     * leurs listes déroulantes (comme le modèle), pour compléter ou corriger le fichier sans faute de frappe.
     *
     * @param  list<string>  $contrats
     */
    public function export(Collection $enseignants, ?string $nomEcole, array $contrats = []): Spreadsheet
    {
        $classeur = new Spreadsheet;
        $classeur->getProperties()->setTitle('Liste des enseignants')->setCreator('KalanNet');
        $this->feuilleListes($classeur, $contrats);
        $feuille = $classeur->createSheet(0)->setTitle('Enseignants');
        $this->entetes($feuille, false);
        $feuille->setCellValue('N1', 'Statut');
        $feuille->getColumnDimension('N')->setWidth(12);
        $this->styleEntete($feuille, 'A1:N1');

        $ligne = 2;
        foreach ($enseignants as $e) {
            $feuille->fromArray([
                $e->nom_prenom_enseignant,
                null,
                $e->genre_enseignant === 'Feminin' ? 'Féminin' : $e->genre_enseignant,
                $e->email_enseignant,
                $e->date_naissance_enseignant ? Carbon::parse($e->date_naissance_enseignant)->format('d/m/Y') : null,
                $e->lieu_naissance_enseignant,
                $e->diplome_enseignant,
                $e->specialite,
                $e->type_contrat_enseignant,
                $e->salaire_enseignant,
                $e->prix_heure,
                $e->nombre_heure,
                null,
                $e->is_deleted ? 'Archivé' : 'Actif',
            ], null, 'A'.$ligne);
            // Téléphone et matricule en texte : pas de zéro de tête perdu.
            $feuille->setCellValueExplicit('B'.$ligne, (string) $e->telephone_enseignant, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $feuille->setCellValueExplicit('M'.$ligne, (string) $e->matricule, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $ligne++;
        }

        if ($ligne > 2) {
            $feuille->getStyle('A1:N'.($ligne - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        $feuille->getStyle('J2:K'.max(2, $ligne - 1))->getNumberFormat()->setFormatCode('#,##0');
        $feuille->setAutoFilter('A1:N'.max(1, $ligne - 1));
        if ($nomEcole) {
            $classeur->getProperties()->setSubject($nomEcole);
        }

        $fin = max($ligne - 1, 1) + self::MAX_LIGNES;
        $this->liste($feuille, "C2:C{$fin}", '=Listes!$A$2:$A$3', 'Genre', 'Masculin ou Féminin.');
        if ($contrats !== []) {
            $this->liste($feuille, "I2:I{$fin}", '=Listes!$B$2:$B$'.(count($contrats) + 1), 'Type de contrat', 'Choisissez dans la liste. Laissez vide si inconnu.');
        }
        $classeur->setActiveSheetIndex(0);

        return $classeur;
    }

    /**
     * Lignes remplies du fichier : [numéro de ligne => données du formulaire].
     * Les dates Excel sont converties en AAAA-MM-JJ ; une date illisible est
     * laissée telle quelle pour que la validation la signale.
     */
    public function lire(string $chemin): array
    {
        $feuille = IOFactory::load($chemin)->getSheet(0);
        $lignes = [];

        for ($ligne = 2, $fin = $feuille->getHighestDataRow(); $ligne <= $fin; $ligne++) {
            $donnees = [];
            foreach (self::COLONNES as $colonne => [$cle]) {
                $valeur = $feuille->getCell($colonne.$ligne)->getValue();
                $donnees[$cle] = is_string($valeur) ? trim($valeur) : $valeur;
            }
            if (collect($donnees)->every(fn ($v) => $v === null || $v === '')) {
                continue;
            }

            $donnees['date_naissance'] = $this->date($donnees['date_naissance']);
            $donnees['genre'] = $this->genre($donnees['genre']);
            foreach (['telephone', 'matricule', 'nom_prenom', 'type_contrat'] as $texte) {
                $donnees[$texte] = $donnees[$texte] === null ? null : trim((string) $donnees[$texte]);
            }
            $donnees['type_contrat'] = $donnees['type_contrat'] ? strtoupper($donnees['type_contrat']) : null;
            $lignes[$ligne] = array_map(fn ($v) => $v === '' ? null : $v, $donnees);
        }

        return $lignes;
    }

    private function date(mixed $valeur): mixed
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        if (is_numeric($valeur)) {
            return Carbon::instance(DateExcel::excelToDateTimeObject((float) $valeur))->format('Y-m-d');
        }
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, (string) $valeur);
                if ($date && $date->format($format) === (string) $valeur) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        return (string) $valeur;
    }

    private function genre(mixed $valeur): ?string
    {
        $v = strtolower(\Illuminate\Support\Str::ascii(trim((string) $valeur)));

        return match (true) {
            $v === '' => null,
            in_array($v, ['f', 'feminin', 'femme'], true) => 'Féminin',
            in_array($v, ['m', 'masculin', 'homme'], true) => 'Masculin',
            default => (string) $valeur,
        };
    }

    private function entetes(Worksheet $feuille, bool $marquerObligatoires): void
    {
        foreach (self::COLONNES as $colonne => [, $titre, $obligatoire, $largeur]) {
            $feuille->setCellValue($colonne.'1', $titre.($marquerObligatoires && $obligatoire ? ' *' : ''));
            $feuille->getColumnDimension($colonne)->setWidth($largeur);
        }
        $this->styleEntete($feuille, 'A1:M1');
        $feuille->freezePane('A2');
    }

    private function styleEntete(Worksheet $feuille, string $plage): void
    {
        $style = $feuille->getStyle($plage);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1D4E89');
    }

    /** Feuille « Listes » (valeurs des listes déroulantes) : Genre en A, types de contrat en B. */
    private function feuilleListes(Spreadsheet $classeur, array $contrats): Worksheet
    {
        $listes = $classeur->getActiveSheet()->setTitle('Listes');
        $listes->fromArray(['Genre', 'Type de contrat'], null, 'A1');
        $listes->fromArray([['Masculin'], ['Féminin']], null, 'A2');
        foreach (array_values($contrats) as $i => $contrat) {
            $listes->setCellValue('B'.($i + 2), $contrat);
        }
        $this->styleEntete($listes, 'A1:B1');
        $listes->getColumnDimension('A')->setWidth(14);
        $listes->getColumnDimension('B')->setWidth(18);

        return $listes;
    }

    private function liste(Worksheet $feuille, string $plage, string $formule, string $titre, string $aide): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST)->setErrorStyle(DataValidation::STYLE_STOP)->setAllowBlank(true)
            ->setShowDropDown(true)->setShowInputMessage(true)->setShowErrorMessage(true)
            ->setPromptTitle($titre)->setPrompt($aide)->setErrorTitle($titre)->setError($aide)
            ->setFormula1($formule);
        $feuille->setDataValidation($plage, $validation);
    }
}
