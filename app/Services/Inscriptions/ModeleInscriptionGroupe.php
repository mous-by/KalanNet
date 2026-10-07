<?php

namespace App\Services\Inscriptions;

use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Matiere;
use App\Support\Lv2;
use App\Support\SubventionEtat;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Modèle Excel de l'inscription en groupe : feuille « Élèves » à remplir (titres
 * lisibles, listes déroulantes), « Listes » (valeurs acceptées) et « Mode
 * d'emploi ». Les colonnes gardent leur position (A à J), lue telle quelle par
 * InscriptionController::importGroup() : les anciens fichiers restent valables.
 */
class ModeleInscriptionGroupe
{
    public const MAX_LIGNES = 1000;

    /** Colonne => [titre, obligatoire, largeur]. */
    private const COLONNES = [
        'A' => ['Prénom', true, 20],
        'B' => ['Nom', true, 20],
        'C' => ['Date de naissance', false, 18],
        'D' => ['Lieu de naissance', false, 20],
        'E' => ['Adresse', false, 24],
        'F' => ['Genre', true, 12],
        'G' => ['Cas social', false, 14],
        'H' => ['Matricule', false, 16],
        'I' => ['Langue LV2', false, 14],
        'J' => ['Subventionné par l\'État', false, 22],
    ];

    public function creer(?Ecole $ecole): Spreadsheet
    {
        $classeur = new Spreadsheet;
        $classeur->getProperties()->setTitle('Modèle d\'inscription en groupe')->setCreator('KalanNet');

        $classes = $ecole ? Classe::where('idEcole', $ecole->idEcole)->get() : collect();
        $avecLv2 = $classes->contains(fn ($classe) => Lv2::classeConcernee($classe));
        $avecSubvention = $classes->contains(fn ($classe) => SubventionEtat::classeEligible($classe, $ecole));
        $langues = Matiere::lv2()->orderBy('nom_matiere')->pluck('nom_matiere')
            ->map(fn ($nom) => trim(preg_replace('/\s*LV2$/i', '', $nom)))->all();

        // Feuille « Listes » : valeurs proposées dans les listes déroulantes.
        $listes = $classeur->getActiveSheet()->setTitle('Listes');
        $listes->fromArray(['Genre', 'Cas social', 'Langue LV2', 'Subventionné'], null, 'A1');
        $listes->fromArray([['Masculin'], ['Féminin']], null, 'A2');
        $listes->fromArray([['Normal'], ['Dispensé'], ['Malade']], null, 'B2');
        foreach ($langues as $i => $langue) {
            $listes->setCellValue('C'.($i + 2), $langue);
        }
        $listes->fromArray([['Oui'], ['Non']], null, 'D2');
        $this->styleEntete($listes, 'A1:D1');
        foreach (['A' => 14, 'B' => 14, 'C' => 16, 'D' => 14] as $colonne => $largeur) {
            $listes->getColumnDimension($colonne)->setWidth($largeur);
        }

        // Feuille « Élèves » : celle qu'on remplit (première feuille du classeur).
        $feuille = $classeur->createSheet(0)->setTitle('Élèves');
        foreach (self::COLONNES as $colonne => [$titre, $obligatoire, $largeur]) {
            $feuille->setCellValue($colonne.'1', $titre.($obligatoire ? ' *' : ''));
            $feuille->getColumnDimension($colonne)->setWidth($largeur);
        }
        $this->styleEntete($feuille, 'A1:J1');
        $feuille->freezePane('A2');

        $fin = self::MAX_LIGNES + 1;
        $this->liste($feuille, "F2:F{$fin}", '=Listes!$A$2:$A$3', 'Genre', 'Masculin ou Féminin.');
        $this->liste($feuille, "G2:G{$fin}", '=Listes!$B$2:$B$4', 'Cas social', 'Normal (par défaut si vide), Dispensé ou Malade.');
        $this->liste($feuille, "I2:I{$fin}", '=Listes!$C$2:$C$'.max(2, count($langues) + 1), 'Langue LV2', 'Secondaire uniquement. Laissez vide si l\'élève n\'a pas de LV2.');
        $this->liste($feuille, "J2:J{$fin}", '=Listes!$D$2:$D$3', 'Subventionné par l\'État', 'Oui seulement pour un élève pris en charge par l\'État (secondaire, école privée). Non par défaut.');
        $feuille->getStyle("C2:C{$fin}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $feuille->getStyle("H2:H{$fin}")->getNumberFormat()->setFormatCode('@');

        // Colonnes sans objet pour cette école : masquées (l'import les ignore vides).
        $feuille->getColumnDimension('I')->setVisible($avecLv2);
        $feuille->getColumnDimension('J')->setVisible($avecSubvention);

        // Feuille « Mode d'emploi ».
        $aide = $classeur->createSheet()->setTitle('Mode d\'emploi');
        $consignes = [
            'Remplissez la feuille « Élèves » : une ligne par élève, à partir de la ligne 2. Ne modifiez pas la ligne des titres.',
            'Colonnes obligatoires (*) : Prénom, Nom et Genre. Une ligne sans prénom ou sans nom est ignorée.',
            'Date de naissance : au format JJ/MM/AAAA (ex. 22/04/2009). Laissez vide si elle n\'est pas connue.',
            'Genre et Cas social : choisissez dans la liste déroulante de la cellule.',
            'Matricule : laissez vide pour qu\'il soit créé automatiquement.',
        ];
        if ($avecLv2) {
            $consignes[] = 'Langue LV2 : seulement pour une classe du secondaire. Laissez vide sinon.';
        }
        if ($avecSubvention) {
            $consignes[] = 'Subventionné par l\'État : « Oui » applique d\'office la formule annuelle de la classe, payée par l\'État.';
        }
        $consignes[] = 'La classe, l\'année scolaire et la formule de paiement se choisissent dans KalanNet au moment de l\'import : elles valent pour tout le fichier.';
        $consignes[] = self::MAX_LIGNES.' élèves au plus par fichier. Formats acceptés : .xlsx, .xls.';

        $lignes = [['Inscription en groupe — mode d\'emploi'], ['']];
        foreach ($consignes as $i => $consigne) {
            $lignes[] = [($i + 1).'. '.$consigne];
        }
        $lignes[] = [''];
        $lignes[] = ['Exemple : Issa | Diallo | 22/04/2009 | Ségou | Banankabougou | Masculin | Normal | (vide)'];
        $aide->fromArray($lignes, null, 'A1');
        $aide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $aide->getColumnDimension('A')->setWidth(120);

        $classeur->setActiveSheetIndex(0);

        return $classeur;
    }

    private function styleEntete(Worksheet $feuille, string $plage): void
    {
        $style = $feuille->getStyle($plage);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1D4E89');
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
