<?php

namespace App\Services\Rapports;

use App\Support\PropositionPassageDonnees;
use App\Support\RapportSecondaireDefinitions as Def;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Canevas officiels du ministère (secondaire général et quatre maquettes du technique et professionnel), remplis avec le
 * rapport de l'établissement : ce qu'il a saisi à l'écran et ce que KalanNet a pré-rempli. Le vrai fichier est gardé,
 * avec tous ses tableaux, formules et cases de vérification ; une case qui contient une formule n'est jamais écrasée.
 */
class CanevasOfficiel
{
    /** Les cinq modèles officiels du secondaire ; chaque établissement choisit celui que son académie demande. */
    public const MODELES = [
        'esg' => 'Enseignement secondaire général (ESG)',
        'est' => 'Enseignement secondaire technique (EST)',
        'esp' => 'Enseignement secondaire professionnel (ESP)',
        'esp_pro' => 'Enseignement secondaire professionnel « Pro »',
        'agro' => 'Enseignement agricole',
    ];

    public const MAQUETTES_ETP = [
        'est' => 'Enseignement secondaire technique (EST)',
        'esp' => 'Enseignement secondaire professionnel (ESP)',
        'esp_pro' => 'Enseignement secondaire professionnel « Pro »',
        'agro' => 'Enseignement agricole',
    ];

    private Worksheet $f;

    public static function fichier(string $modele, string $type): string
    {
        return resource_path($modele === 'esg' ? "canevas/esg_{$type}.xlsx" : "canevas/etp/{$modele}_{$type}.xlsx");
    }

    /** Écrit une valeur, sauf dans une case qui porte une formule du canevas. */
    private function e(string $cellule, mixed $valeur, ?Worksheet $feuille = null): void
    {
        $feuille ??= $this->f;
        if ($valeur === null || $valeur === '' || str_starts_with((string) $feuille->getCell($cellule)->getValue(), '=')) {
            return;
        }
        if (is_int($valeur) || is_float($valeur)) {
            $feuille->setCellValue($cellule, $valeur);
        } else {
            $feuille->setCellValueExplicit($cellule, (string) $valeur, DataType::TYPE_STRING);
        }
    }

    private function feuille(Spreadsheet $c, string $nom): ?Worksheet
    {
        return $this->f = $c->getSheetByName($nom) ?? $this->f;
    }

    /** Feuille du canevas, ou une feuille hors classeur si elle manque : rien n'est alors écrit au mauvais endroit. */
    private function feuilleOuRien(Spreadsheet $c, string $nom): Worksheet
    {
        return $this->f = $c->getSheetByName($nom) ?? new Worksheet();
    }

    private static function cle(?string $texte): string
    {
        // « Mathématiques Générales » (tableau 2) et « Mathématiques » (autres tableaux) : la même discipline.
        return str_replace('mathematiquesgenerales', 'mathematiques', preg_replace('/[^a-z]/', '', Str::lower(Str::ascii((string) $texte))));
    }

    /** Ligne de chaque discipline dans une colonne de libellés du canevas. @return array<string, int> */
    private function lignesDisciplines(string $colonne, int $de, int $a): array
    {
        $lignes = [];
        foreach (range($de, $a) as $ligne) {
            $lignes[self::cle($this->f->getCell($colonne.$ligne)->getValue())] = $ligne;
        }

        return $lignes;
    }

    /** Remplace une ligne de libellé (« Région : ») par le libellé suivi de la valeur. */
    private function libelle(string $colonne, string $debut, string $texte, int $de = 1, int $a = 60): ?int
    {
        foreach (range($de, $a) as $ligne) {
            $actuel = trim(preg_replace('/\s+/u', ' ', (string) $this->f->getCell($colonne.$ligne)->getValue()));
            if ($actuel !== '' && str_starts_with(self::cle($actuel), self::cle($debut))) {
                $this->f->setCellValueExplicit($colonne.$ligne, $texte, DataType::TYPE_STRING);

                return $ligne;
            }
        }

        return null;
    }

    /** @param Collection<int, object> $enseignants */
    /** @var list<array<string, mixed>> listes nominatives du tableau 24 (fin d'année) */
    private array $listesT24 = [];

    public function remplir(string $modele, string $type, array $d, object $ecole, string $annee, string $academie, Collection $enseignants, array $listesT24 = []): Spreadsheet
    {
        $this->listesT24 = $listesT24;
        $classeur = IOFactory::load(self::fichier($modele, $type));
        $this->f = $classeur->getActiveSheet();
        if ($modele === 'esg') {
            $this->esg($classeur, $type, $d, $annee, $academie, $enseignants);
        } else {
            $this->etp($classeur, $d, $annee, $academie);
        }

        return $classeur;
    }

    /* ——— Secondaire général ——— */

    private const T5 = ['10CG' => 5, '11L' => 7, '11SES' => 8, '11S' => 9, 'TAL' => 11, 'TLL' => 12, 'TSECO' => 13, 'TSS' => 14, 'TSE' => 15, 'TSEXP' => 16];

    private const T2 = ['10CG' => 'B', '11L' => 'C', '11SES' => 'D', '11S' => 'E', 'TLL' => 'F', 'TAL' => 'G', 'TSS' => 'H', 'TSECO' => 'I', 'TSE' => 'J', 'TSEXP' => 'K'];

    private function esg(Spreadsheet $c, string $type, array $d, string $annee, string $academie, Collection $enseignants): void
    {
        $i = $d['infos'] ?? [];
        $t = $d['t'] ?? [];
        $v = fn (string $code, string $ligne, string $col) => $t[$code][$ligne][$col] ?? null;
        $col = fn (string $lettre, int $decalage) => chr(ord($lettre) + $decalage);

        $this->informations($c, $type, $i, $annee, $academie);

        if ($type === 'rentree') {
            $this->feuille($c, 'T1.1');
            foreach (range(0, 9) as $n) {
                foreach (['n1' => 'B', 'n2' => 'C', 'n3' => 'D'] as $k => $lettre) {
                    $this->e($lettre.(5 + $n), $v('T1.1', 'l'.$n, $k));
                }
            }
            $this->feuille($c, 'T1.2');
            foreach (range(0, 11) as $n) {
                $this->e('B'.(5 + $n), $v('T1.2', 'l'.$n, 'nombre'));
            }

            $this->feuille($c, 'T2');
            $lignes = $this->lignesDisciplines('A', 5, 31);
            foreach (self::T2 as $serie => $lettre) {
                $this->e($lettre.'4', $v('T2', 'classes', $serie));
                foreach (Def::DISCIPLINES as $n => $discipline) {
                    if (isset($lignes[self::cle($discipline)])) {
                        $this->e($lettre.$lignes[self::cle($discipline)], $v('T2', 'l'.$n, $serie));
                    }
                }
            }

            $this->feuille($c, 'T5');
            foreach (self::T5 as $serie => $ligne) {
                $f = $v('T5', $serie, 'f');
                $g = $v('T5', $serie, 'g');
                $this->e('C'.$ligne, $f);
                $this->e('E'.$ligne, $g);
                if ($f !== null || $g !== null) {
                    $this->e('B'.$ligne, (int) $f + (int) $g);
                }
            }
            $this->feuille($c, 'T6');
            foreach (['10CG' => 6, '11L' => 8, '11SES' => 9, '11S' => 10, 'TAL' => 12, 'TLL' => 13, 'TSECO' => 14, 'TSS' => 15, 'TSE' => 16, 'TSEXP' => 17] as $serie => $ligne) {
                foreach (['p_f' => 'B', 'p_g' => 'D', 'r_f' => 'F', 'r_g' => 'H'] as $k => $lettre) {
                    $this->e($lettre.$ligne, $v('T6', $serie, $k));
                }
            }
            if (isset($t['T6']['10CG'])) {
                $this->e('J6', (int) $v('T6', '10CG', 'p_f') + (int) $v('T6', '10CG', 'r_f'));
                $this->e('L6', (int) $v('T6', '10CG', 'p_g') + (int) $v('T6', '10CG', 'r_g'));
            }

            // Âges et régions : les séries sont des colonnes du canevas, les âges et régions des lignes.
            $ages = ['moins13' => 5] + collect(range(13, 25))->mapWithKeys(fn ($a) => ['a'.$a => 6 + $a - 13])->all();
            foreach (['T7' => ['10CG' => 'B', '11L' => 'D', '11SES' => 'F', '11S' => 'H'], 'T_7_S' => ['TAL' => 'B', 'TLL' => 'D', 'TSECO' => 'F', 'TSS' => 'H', 'TSE' => 'J', 'TSEXP' => 'L']] as $feuille => $series) {
                $this->feuille($c, $feuille);
                foreach ($series as $serie => $lettre) {
                    foreach ($ages as $age => $ligne) {
                        $this->e($lettre.$ligne, $v('T7', $age, $serie.'_f'));
                        $this->e($col($lettre, 1).$ligne, $v('T7', $age, $serie.'_g'));
                    }
                }
            }
            foreach (['T8' => ['10CG' => 'B', '11L' => 'D', '11S' => 'F', '11SES' => 'H'], 'T8_S' => ['TLL' => 'B', 'TAL' => 'D', 'TSS' => 'F', 'TSECO' => 'H', 'TSE' => 'J', 'TSEXP' => 'L']] as $feuille => $series) {
                $this->feuille($c, $feuille);
                foreach ($series as $serie => $lettre) {
                    foreach (range(0, 20) as $n) {
                        $this->e($lettre.(5 + $n), $v('T8', 'l'.$n, $serie.'_f'));
                        $this->e($col($lettre, 1).(5 + $n), $v('T8', 'l'.$n, $serie.'_g'));
                    }
                }
            }

            // Scolarité : une table par niveau dans le canevas.
            $this->feuille($c, 'T9');
            foreach ([[['10CG' => 'B'], 7, 1, 3], [['11L' => 'B', '11S' => 'D', '11SES' => 'F'], 16, 2, 5], [['TLL' => 'B', 'TAL' => 'D', 'TSS' => 'F', 'TSECO' => 'H', 'TSE' => 'J', 'TSEXP' => 'L'], 26, 3, 6]] as [$series, $premiere, $de, $a]) {
                foreach ($series as $serie => $lettre) {
                    foreach (range($de, $a) as $ans) {
                        $this->e($lettre.($premiere + $ans - $de), $v('T9', 'a'.$ans, $serie.'_f'));
                        $this->e($col($lettre, 1).($premiere + $ans - $de), $v('T9', 'a'.$ans, $serie.'_g'));
                    }
                }
            }

            $this->feuille($c, 'T10');
            foreach (['TAL' => 6, 'TLL' => 8, 'TSECO' => 10, 'TSS' => 12, 'TSE' => 14, 'TSEXP' => 16] as $serie => $ligne) {
                foreach (['inscrits' => 'B', 'presents' => 'E', 'admis' => 'H'] as $k => $lettre) {
                    $this->e($lettre.$ligne, $v('T10', $serie, $k.'_f'));
                    $this->e($col($lettre, 1).$ligne, $v('T10', $serie, $k.'_g'));
                }
            }
            $this->feuille($c, 'T11');
            foreach (['TAL' => 5, 'TLL' => 7, 'TSECO' => 9, 'TSS' => 11, 'TSE' => 13, 'TSEXP' => 15] as $serie => $ligne) {
                foreach (['a3' => 'B', 'a4' => 'E', 'a5' => 'H', 'a6' => 'K'] as $k => $lettre) {
                    $this->e($lettre.$ligne, $v('T11', $serie, $k.'_f'));
                    $this->e($col($lettre, 1).$ligne, $v('T11', $serie, $k.'_g'));
                }
            }

            $this->feuille($c, 'T12');
            $this->professeursParStatut($v, 'T12', $this->lignesDisciplines('A', 5, 32));
            $this->feuille($c, 'T13-14');
            $this->nationalites($v, 'T13', 6);
            $this->categories($v, 'T14', 17);
            $this->feuille($c, 'T15-16');
            foreach (range(0, 5) as $n) {
                $this->e($col('A', 2 * $n).'6', $v('T15', 'l'.$n, 'f'));
                $this->e($col('B', 2 * $n).'6', $v('T15', 'l'.$n, 'h'));
            }
            foreach (range(0, 6) as $n) {
                $this->e($col('A', 2 * $n).'14', $v('T16', 'l'.$n, 'f'));
                $this->e($col('B', 2 * $n).'14', $v('T16', 'l'.$n, 'h'));
            }
            $this->feuille($c, 'T17');
            $this->maliensEtrangers($v, 'T17', $this->lignesDisciplines('A', 6, 33));
            $this->feuille($c, 'T24');
            $this->administration($v, 'T24', 4, 'A20', $i['email'] ?? null);
            $this->feuille($c, 'T21');
            if (!empty($t['T21'])) {
                $this->liste($t['T21'], 5, 53, ['prenoms' => 'B', 'nom' => 'C', 'sexe' => 'D', 'naissance' => 'E', 'matricule' => 'F', 'integration' => 'G', 'titularisation' => 'H',
                    'arrivee' => 'I', 'specialite' => 'J', 'apc' => 'K', 'fonction' => 'L', 'heures' => 'M', 'heures_sup' => 'N', 'cat' => 'O', 'classe' => 'P', 'echelon' => 'Q',
                    'situation' => 'R', 'enfants' => 'S', 'observations' => 'T']);
            } else {
                $this->listeEnseignants($enseignants);
            }
            $this->rentreeComplements($c, $t);

            return;
        }

        // Fin d'année.
        $this->feuille($c, 'T1');
        $this->professeursParStatut($v, 'T1', $this->lignesDisciplines('A', 5, 31));
        $this->feuille($c, 'T2-5');
        $this->nationalites($v, 'T2', 6);
        $this->categories($v, 'T3', 17);
        $this->maliensEtrangers($v, 'T5', $this->lignesDisciplines('A', 32, 59));

        $sorties = ['red' => 0, 'insuffisance' => 1, 'epuisement' => 2, 'abandon' => 3];
        $this->feuille($c, 'T6');
        $this->resultats($v, 'T6', '10CG', 11, ['p11L' => 'D', 'p11S' => 'G', 'p11SES' => 'J'], 'M', $sorties);
        $this->feuille($c, 'T 7 9');
        $this->resultats($v, 'T7', '11L', 8, ['pTLL' => 'D', 'pTAL' => 'G'], 'J', $sorties);
        $this->resultats($v, 'T8', '11S', 18, ['pTSE' => 'D', 'pTSEXP' => 'G'], 'J', $sorties);
        $this->resultats($v, 'T9', '11SES', 28, ['pTSECO' => 'D', 'pTSS' => 'G'], 'J', $sorties);

        $terminales = ['TLL' => 5, 'TAL' => 7, 'TSS' => 10, 'TSECO' => 12, 'TSE' => 15, 'TSEXP' => 17];
        $this->feuille($c, 'T11');
        foreach ($terminales as $serie => $ligne) {
            foreach (['effectif' => 'B', 'admis' => 'E', 'red' => 'I'] as $k => $lettre) {
                $this->e($lettre.$ligne, $v('T11', $serie, $k.'_f'));
                $this->e($col($lettre, 1).$ligne, $v('T11', $serie, $k.'_g'));
            }
        }
        $this->feuille($c, 'T 12');
        foreach ($terminales as $serie => $ligne) {
            // Les colonnes « réguliers » de l'effectif et des admis sont calculées par le canevas.
            foreach (['inscrits_cl' => 'C', 'presents_reg' => 'I', 'presents_cl' => 'J', 'admis_cl' => 'R'] as $k => $lettre) {
                $this->e($lettre.$ligne, $v('T12', $serie, $k));
            }
        }
        $this->feuille($c, 'T 13 14');
        foreach (['TLL' => 7, 'TAL' => 8, 'TSS' => 9, 'TSECO' => 10, 'TSE' => 11, 'TSEXP' => 12] as $serie => $ligne) {
            foreach (['a3' => 'B', 'a4' => 'E', 'a5' => 'H'] as $k => $lettre) {
                $this->e($lettre.$ligne, $v('T13', $serie, $k.'_f'));
                $this->e($col($lettre, 1).$ligne, $v('T13', $serie, $k.'_g'));
            }
        }
        $this->feuille($c, 'T15');
        foreach (['10CG' => 6, '11L' => 7, '11SES' => 8, '11S' => 9, 'TLL' => 10, 'TAL' => 11, 'TSS' => 12, 'TSECO' => 13, 'TSE' => 14, 'TSEXP' => 15] as $serie => $ligne) {
            foreach (['m7' => 'B', 'm10' => 'D', 'm12' => 'F', 'm14' => 'H', 'm16' => 'J', 'm20' => 'L'] as $k => $lettre) {
                $this->e($lettre.$ligne, $v('T15', $serie, $k.'_f'));
                $this->e($col($lettre, 1).$ligne, $v('T15', $serie, $k.'_g'));
            }
        }
        $this->feuille($c, 'T 18 19');
        foreach (['10CG', '11L', '11S', '11SES', 'TLL', 'TAL', 'TSS', 'TSECO', 'TSE', 'TSEXP'] as $n => $serie) {
            foreach (['capacite' => 'B', 'salles' => 'C', 'disponibles' => 'D', 'a_pourvoir' => 'E'] as $k => $lettre) {
                $this->e($lettre.(5 + $n), $v('T18', $serie, $k));
            }
            foreach (['passants' => 'B', 'redoublants' => 'C', 'effectifs' => 'D', 'classes' => 'E', 'salles' => 'F'] as $k => $lettre) {
                $this->e($lettre.(22 + $n), $v('T19', $serie, $k));
            }
        }
        $this->feuille($c, 'T 20');
        $lignes = $this->lignesDisciplines('A', 4, 30);
        foreach (Def::DISCIPLINES as $n => $discipline) {
            if (isset($lignes[self::cle($discipline)])) {
                foreach (['en_place' => 'B', 'departs' => 'C', 'besoins' => 'D'] as $k => $lettre) {
                    $this->e($lettre.$lignes[self::cle($discipline)], $v('T20', 'l'.$n, $k));
                }
            }
        }
        $this->feuille($c, 'T 21 22');
        foreach (range(0, 4) as $n) {
            foreach (['en_place' => 'B', 'departs' => 'C', 'besoins' => 'D'] as $k => $lettre) {
                $this->e($lettre.(4 + $n), $v('T21', 'l'.$n, $k));
            }
        }
        $this->administration($v, 'T22', 14, 'A28', $i['email'] ?? null);
        $this->finComplements($c, $t);
    }

    /** Lignes d'une liste du rapport (n1, n2…) écrites à partir de la ligne $premiere du canevas. */
    private function liste(array $lignes, int $premiere, int $max, array $colonnes): void
    {
        $n = 0;
        foreach ($lignes as $valeurs) {
            if (!$valeurs || $n >= $max) {
                continue;
            }
            foreach ($colonnes as $cle => $lettre) {
                $valeur = $valeurs[$cle] ?? null;
                $this->e($lettre.($premiere + $n), is_string($valeur) && ctype_digit($valeur) && strlen($valeur) < 12 ? (int) $valeur : $valeur);
            }
            $n++;
        }
    }

    /** Tableau à lignes et colonnes fixes : [clé de ligne => n° de ligne], [clé de colonne => lettre]. */
    private function grille(array $t, string $code, array $lignes, array $colonnes): void
    {
        foreach ($lignes as $cle => $ligne) {
            foreach ($colonnes as $colonne => $lettre) {
                $valeur = $t[$code][$cle][$colonne] ?? null;
                $this->e($lettre.$ligne, is_numeric($valeur) && ctype_digit((string) $valeur) ? (int) $valeur : $valeur);
            }
        }
    }

    /** Rentrée : tableaux 3, 4, 18 à 20, 22, 23, 25 à 31. */
    private function rentreeComplements(Spreadsheet $c, array $t): void
    {
        $salle = ['dimension', 'etat', 'capacite', 'occupees', 'disponibles', 'heures'];
        $numeros = fn (int $de, int $n) => collect(range(1, $n))->mapWithKeys(fn ($i) => ['n'.$i => $de + $i - 1])->all();
        $libelles = fn (int $de, int $n) => collect(range(0, $n - 1))->mapWithKeys(fn ($i) => ['l'.$i => $de + $i])->all();

        $this->feuilleOuRien($c, 'T3');
        $this->liste($t['T3'] ?? [], 5, 30, array_combine(['numero', ...$salle], ['B', 'C', 'D', 'E', 'F', 'G', 'H']));
        $this->feuilleOuRien($c, 'T4');
        $this->grille($t, 'T4', $libelles(5, 12), array_combine($salle, ['B', 'C', 'D', 'E', 'F', 'G']));
        $this->feuilleOuRien($c, 'T18');
        $this->liste($t['T18'] ?? [], 5, 15, ['nom' => 'B', 'naissance' => 'C', 'specialite' => 'D', 'arrivee_mali' => 'E', 'mutation' => 'F', 'situation' => 'G']);

        $ages = ['moins20', 'a20', 'a30', 'a40', 'a50'];
        $parAge = fn (int $premiere) => collect($ages)->flatMap(fn ($a, $i) => [$a.'_f' => $premiere + 3 * $i, $a.'_h' => $premiere + 3 * $i + 1])->all();
        $statuts = ['fetat', 'fct', 'contetat', 'contct', 'perm', 'vact', 'volontaire'];
        $this->feuilleOuRien($c, 'T19');
        $this->grille($t, 'T19', $parAge(6), array_combine(array_map(fn ($s) => 'a_'.$s, $statuts), ['C', 'D', 'E', 'F', 'G', 'H', 'I'])
            + array_combine(array_map(fn ($s) => 'b_'.$s, $statuts), ['K', 'L', 'M', 'N', 'O', 'P', 'Q'])
            + ['c_fetat' => 'S', 'c_fct' => 'T', 'c_perm' => 'U', 'c_vact' => 'V']);
        $this->feuilleOuRien($c, 'T20');
        $this->grille($t, 'T20', $parAge(7), ['a' => 'C', 'b' => 'D', 'c' => 'E', 'd' => 'F', 'conventionnaire' => 'G']);

        $personnel = ['prenoms' => 'B', 'nom' => 'C', 'sexe' => 'D', 'naissance' => 'E', 'matricule' => 'F', 'integration' => 'G', 'titularisation' => 'H', 'arrivee' => 'I',
            'specialite' => 'J', 'fonction' => 'K', 'cat' => 'L', 'classe' => 'M', 'echelon' => 'N', 'situation' => 'O', 'enfants' => 'P', 'observations' => 'Q'];
        $this->feuilleOuRien($c, 'T22');
        $this->liste($t['T22'] ?? [], 5, 26, $personnel);
        $this->feuilleOuRien($c, 'T23');
        $this->liste($t['T23'] ?? [], 5, 14, $personnel);

        $montants = ['du' => 'B', 'paye_anterieur' => 'C', 'paye_octobre' => 'D', 'reliquat' => 'E'];
        $this->feuilleOuRien($c, 'T25-26-27');
        $this->grille($t, 'T25', $libelles(4, 3), ['montant' => 'B', 'analyse' => 'C']);
        $this->grille($t, 'T26', $libelles(12, 12), $montants);
        $this->grille($t, 'T27', $libelles(32, 5), $montants);
        $this->feuilleOuRien($c, 'T28');
        $this->liste($t['T28'] ?? [], 4, 20, ['marque' => 'A', 'immatriculation' => 'B', 'acquisition' => 'C', 'etat' => 'D', 'observations' => 'E']);
        $this->feuilleOuRien($c, 'T29');
        $this->liste(collect($t['T29'] ?? [])->filter()->values()->map(fn ($l, $i) => ['numero' => (string) ($i + 1)] + $l)->all(), 4, 14,
            ['numero' => 'A', 'libelle' => 'B', 'nature' => 'C', 'montant' => 'D', 'source' => 'E', 'delai' => 'F', 'realisation' => 'G', 'observations' => 'H']);

        $this->feuilleOuRien($c, 'T30');
        $series = ['10CG' => 'C', '11L' => 'E', '11S' => 'G', '11SES' => 'I', 'TAL' => 'K', 'TLL' => 'M', 'TSE' => 'O', 'TSECO' => 'Q', 'TSEXP' => 'S', 'TSS' => 'U'];
        $types = ['vue', 'ouie', 'motricite', 'motricite_fine', 'intellect', 'communication', 'comportement', 'multiples'];
        $lignes = collect($types)->flatMap(fn ($type, $i) => [$type.'_1' => 6 + 3 * $i, $type.'_2' => 7 + 3 * $i, $type.'_3' => 8 + 3 * $i])->all();
        $colonnes = collect($series)->flatMap(fn ($lettre, $serie) => [$serie.'_f' => $lettre, $serie.'_g' => chr(ord($lettre) + 1)])->all();
        $this->grille($t, 'T30', $lignes, $colonnes);
        $this->feuilleOuRien($c, 'T31');
        $this->liste(collect($t['T31'] ?? [])->filter()->values()->map(fn ($l, $i) => ['numero' => (string) ($i + 1)] + $l)->all(), 4, 15,
            ['numero' => 'A', 'partenaire' => 'B', 'zones' => 'C', 'domaines' => 'D', 'realisations' => 'E', 'periode' => 'F', 'observations' => 'G']);
    }

    /** Fin d'année : tableaux 10, 14, 16, 17 et 23. */
    private function finComplements(Spreadsheet $c, array $t): void
    {
        $sorties = ['effectif' => 'B', 'p1' => 'E', 'p2' => 'I', 'red' => 'M', 'insuffisance' => 'Q', 'epuisement' => 'U', 'abandon' => 'Y'];
        $this->feuilleOuRien($c, 'T10 ');
        $this->grille($t, 'T10', ['11L' => 6, '11S' => 9, '11SES' => 12],
            collect($sorties)->flatMap(fn ($lettre, $cle) => [$cle.'_f' => $lettre, $cle.'_g' => chr(ord($lettre) + 1)])->all());
        $this->feuilleOuRien($c, 'T 13 14');
        $this->grille($t, 'T14', ['n10' => 21, 'n11' => 22, 'n12' => 23], ['effectif' => 'B', 'passage' => 'C', 'redoublement' => 'E', 'exclusion' => 'G']);
        // T16 : cinq tranches de moyenne annuelle, en trois blocs de deux (lignes des séries décalées dans chaque bloc).
        $this->feuilleOuRien($c, 'T16');
        $series = ['TLL' => 0, 'TAL' => 1, 'TSS' => 3, 'TSECO' => 4, 'TSE' => 6, 'TSEXP' => 7];
        foreach ([[6, ['m10' => 'C']], [22, ['m12' => 'C', 'm14' => 'F']], [38, ['m16' => 'C', 'm20' => 'F']]] as [$premiere, $tranches]) {
            foreach ($series as $serie => $decalage) {
                foreach ($tranches as $tranche => $lettre) {
                    $this->e($lettre.($premiere + $decalage), $t['T16'][$serie][$tranche.'_total'] ?? null);
                    $this->e(chr(ord($lettre) + 1).($premiere + $decalage), $t['T16'][$serie][$tranche.'_admis'] ?? null);
                }
            }
        }
        // Premier bloc : « ≥ 10 » = toutes les tranches à partir de 10.
        foreach ($series as $serie => $decalage) {
            $somme = fn (string $suffixe) => collect(['m12', 'm14', 'm16', 'm20'])->sum(fn ($m) => (int) ($t['T16'][$serie][$m.$suffixe] ?? 0));
            $this->e('F'.(6 + $decalage), $somme('_total') ?: null);
            $this->e('G'.(6 + $decalage), $somme('_admis') ?: null);
        }
        $this->feuilleOuRien($c, 'T 17');
        $this->grille($t, 'T17', ['TLL' => 6, 'TAL' => 7, 'TSS' => 8, 'TSECO' => 9, 'TSE' => 10, 'TSEXP' => 11, '11L' => 13, '11S' => 14, '11SES' => 15, '10CG' => 17],
            ['passants_f' => 'B', 'passants_g' => 'C', 'redoublants_f' => 'E', 'redoublants_g' => 'F']);

        $this->listesNominatives($c, $t);
    }

    /** Ordre des listes de la feuille « T 23-24 » : chaque liste a une ligne d'en-tête « N° » puis cinq lignes numérotées. */
    private const BLOCS_23_24 = [
        'T23:TAL', 'T23:TLL', 'T23:TSS', 'T23:TSECO', 'T23:TSEXP', 'T23:TSE',
        'A:10CG>11L', 'A:10CG>11S', 'A:10CG>11SES', 'A:11L>TLL', 'A:11L>TAL', 'A:11SES>TSECO', 'A:11SES>TSS', 'A:11S>TSEXP', 'A:11S>TSE',
        'B:10CG', 'B:11L', 'B:11SES', 'B:11S', 'B:TLL', 'B:TAL', 'B:TSECO', 'B:TSS', 'B:TSEXP', 'B:TSE',
    ];

    /**
     * T23 (admis au Bac par série) et T24 (passage, redoublement, exclusion) : chaque liste est écrite sous son en-tête ; une
     * liste plus longue que les cinq lignes du canevas reçoit des lignes en plus. On remplit du bas vers le haut pour que
     * les lignes ajoutées ne décalent pas les listes qui restent à écrire.
     */
    private function listesNominatives(Spreadsheet $c, array $t): void
    {
        $feuille = $c->getSheetByName('T 23-24');
        if (!$feuille) {
            return;
        }
        $entetes = [];
        foreach ($feuille->getRowIterator(1, $feuille->getHighestDataRow()) as $ligne) {
            if (trim((string) $feuille->getCell('A'.$ligne->getRowIndex())->getValue()) === 'N°') {
                $entetes[] = $ligne->getRowIndex();
            }
        }
        $blocs = self::BLOCS_23_24;
        foreach (['10CG', '11L', '11SES', '11S', 'TLL', 'TAL', 'TSECO', 'TSS', 'TSEXP', 'TSE'] as $classe) {
            foreach (['insuffisance', 'epuisement', 'abandon'] as $motif) {
                $blocs[] = "C:{$classe}:{$motif}";
            }
        }

        // Ce qu'il faut écrire dans chaque liste : [N°, Prénoms, Nom, N° Mle, Moy. ann., Observations / Moy. admission, Série…].
        $contenu = [];
        foreach (collect($t['T23'] ?? [])->filter()->groupBy(fn ($l) => $l['serie'] ?? '') as $serie => $admis) {
            $contenu['T23:'.$serie] = $admis->values()->map(fn ($l, $i) => [$i + 1, $l['prenoms'] ?? '', $l['nom'] ?? '', $l['matricule'] ?? '', $l['moy_ann'] ?? '', $l['moy_admission'] ?? '', $l['mention'] ?? '', $serie])->all();
        }
        foreach ($this->listesT24 as $liste) {
            if (!empty($liste['bloc'])) {
                $contenu[$liste['bloc']] = $liste['lignes'];
            }
        }

        $places = array_slice(array_combine(array_slice($blocs, 0, min(count($blocs), count($entetes))), array_slice($entetes, 0, min(count($blocs), count($entetes)))), 0);
        arsort($places);
        foreach ($places as $bloc => $entete) {
            $lignes = $contenu[$bloc] ?? [];
            if ($lignes === []) {
                continue;
            }
            if (count($lignes) > 5) {
                $feuille->insertNewRowBefore($entete + 6, count($lignes) - 5);
            }
            foreach (array_values($lignes) as $i => $valeurs) {
                foreach (array_values($valeurs) as $j => $valeur) {
                    $cellule = chr(ord('A') + $j).($entete + 1 + $i);
                    if ($valeur === '' || $valeur === null) {
                        continue;
                    }
                    is_int($valeur) ? $feuille->setCellValue($cellule, $valeur) : $feuille->setCellValueExplicit($cellule, (string) $valeur, DataType::TYPE_STRING);
                }
            }
        }
    }

    /** Disciplines × statut, hommes (B à H) puis femmes (J à P). */
    private function professeursParStatut(\Closure $v, string $code, array $lignes): void
    {
        foreach (Def::DISCIPLINES as $n => $discipline) {
            $ligne = $lignes[self::cle($discipline)] ?? null;
            if (!$ligne) {
                continue;
            }
            foreach (array_keys(Def::STATUTS) as $k => $statut) {
                $this->e(chr(ord('B') + $k).$ligne, $v($code, 'l'.$n, $statut.'_h'));
                $this->e(chr(ord('J') + $k).$ligne, $v($code, 'l'.$n, $statut.'_f'));
            }
        }
    }

    /** Nationalités en colonnes (F puis H), sur une ligne. */
    private function nationalites(\Closure $v, string $code, int $ligne): void
    {
        foreach (range(0, 5) as $n) {
            $this->e(chr(ord('A') + 2 * $n).$ligne, $v($code, 'l'.$n, 'f'));
            $this->e(chr(ord('B') + 2 * $n).$ligne, $v($code, 'l'.$n, 'h'));
        }
    }

    /** Statut × catégorie A (C, D) et B (F, G). */
    private function categories(\Closure $v, string $code, int $premiere): void
    {
        foreach (range(0, 6) as $n) {
            foreach (['a_f' => 'C', 'a_g' => 'D', 'b_f' => 'F', 'b_g' => 'G'] as $k => $lettre) {
                $this->e($lettre.($premiere + $n), $v($code, 'l'.$n, $k));
            }
        }
    }

    private function maliensEtrangers(\Closure $v, string $code, array $lignes): void
    {
        foreach (Def::DISCIPLINES as $n => $discipline) {
            $ligne = $lignes[self::cle($discipline)] ?? null;
            if ($ligne) {
                foreach (['maliens_f' => 'B', 'maliens_g' => 'C', 'etrangers_f' => 'E', 'etrangers_g' => 'F'] as $k => $lettre) {
                    $this->e($lettre.$ligne, $v($code, 'l'.$n, $k));
                }
            }
        }
    }

    /** Résultats d'une classe de 10e ou 11e : effectif, passages par série, redoublement, exclusions, abandon. */
    private function resultats(\Closure $v, string $code, string $serie, int $ligne, array $passages, string $premiereSortie, array $sorties): void
    {
        $this->e('A'.$ligne, $v($code, $serie, 'effectif_f'));
        $this->e('B'.$ligne, $v($code, $serie, 'effectif_g'));
        foreach ($passages as $k => $lettre) {
            $this->e($lettre.$ligne, $v($code, $serie, $k.'_f'));
            $this->e(chr(ord($lettre) + 1).$ligne, $v($code, $serie, $k.'_g'));
        }
        foreach ($sorties as $k => $rang) {
            $lettre = chr(ord($premiereSortie) + 3 * $rang);
            $this->e($lettre.$ligne, $v($code, $serie, $k.'_f'));
            $this->e(chr(ord($lettre) + 1).$ligne, $v($code, $serie, $k.'_g'));
        }
    }

    private function administration(\Closure $v, string $code, int $premiere, string $celluleEmail, ?string $email): void
    {
        foreach (range(0, 11) as $n) {
            foreach (['prenom' => 'C', 'nom' => 'D', 'telephone' => 'E', 'email' => 'F'] as $k => $lettre) {
                $this->e($lettre.($premiere + $n), $v($code, 'l'.$n, $k));
            }
        }
        if ($email) {
            $this->f->setCellValueExplicit($celluleEmail, 'ADRESSE MAIL ETABLISSEMENT : '.$email, DataType::TYPE_STRING);
        }
    }

    /** Tableau 21 : liste nominative des enseignants (53 lignes dans le canevas). */
    private function listeEnseignants(Collection $enseignants): void
    {
        foreach ($enseignants->take(53)->values() as $n => $e) {
            $ligne = 5 + $n;
            [$prenom, $nom] = PropositionPassageDonnees::decouperNom($e->nom_prenom_enseignant);
            $this->e('B'.$ligne, $prenom);
            $this->e('C'.$ligne, $nom);
            $this->e('D'.$ligne, PropositionPassageDonnees::fille($e->genre_enseignant) ? 'F' : 'H');
            $this->e('E'.$ligne, $e->date_naissance_enseignant ? substr((string) $e->date_naissance_enseignant, 0, 4) : null);
            $this->e('F'.$ligne, $e->matricule);
            $this->e('J'.$ligne, $e->specialite);
            $this->e('R'.$ligne, $e->statut_matrimonial);
            $this->e('S'.$ligne, $e->nombre_enfants);
        }
    }

    private function informations(Spreadsheet $c, string $type, array $i, string $annee, string $academie): void
    {
        $this->feuille($c, 'T0');
        $this->f->setCellValueExplicit('A7', "ACADEMIE D'ENSEIGNEMENT DE ".Str::upper($academie)."          ANNEE SCOLAIRE {$annee}", DataType::TYPE_STRING);
        $this->f->setCellValueExplicit('A9', 'Lycée '.($i['nom'] ?? ''), DataType::TYPE_STRING);
        $this->f->setCellValueExplicit('A10', ' BP '.($i['bp'] ?? '……').' ; N° Tél '.($i['telephone'] ?? '').'   Adresse email : '.($i['email'] ?? ''), DataType::TYPE_STRING);
        if (filled($i['gps'] ?? null)) {
            $this->f->setCellValueExplicit('A11', 'Coordonnées GPS : '.$i['gps'], DataType::TYPE_STRING);
        }
        $x = fn (string $cle) => $i[$cle] ?? '';
        $effectif = (int) ($i['effectif_f'] ?? 0) + (int) ($i['effectif_g'] ?? 0);
        $enseignants = (int) ($i['enseignants_f'] ?? 0) + (int) ($i['enseignants_h'] ?? 0);
        foreach ([
            ["Ordre d'enseignement", "Ordre d'enseignement : ".$x('ordre')],
            ["Nom de l'établissement", "Nom de l'établissement : ".$x('nom')],
            ['Région', 'Région : '.$x('region')],
            ['Cercle', 'Cercle : '.$x('cercle').'   Arrondissement : '.$x('arrondissement').'   Commune : '.$x('commune')],
            ['Date de création', 'Date de création : '.$x('date_creation')],
            ['Arrêté/Décision de création', 'Arrêté/Décision de création : '.$x('arrete_creation')],
            ["Arrêté/Décision d'ouverture", "Arrêté/Décision d'ouverture : ".$x('arrete_ouverture')],
            ["Date d'ouverture", "Date d'ouverture : ".$x('date_ouverture')],
            ['Mission', 'Mission : '.$x('mission')],
            ['Statut', 'Statut : '.$x('statut')],
            ['Diplôme de fin de cycle', 'Diplôme de fin de cycle : '.$x('diplome')],
            ['Capacité', "Capacité d'accueil : ".$x('capacite')],
            ['Nombre de salles ordinaires', 'Nombre de salles ordinaires : '.$x('salles_ordinaires').'   Nombre de salles utilisées : '.$x('salles_ordinaires_utilisees')],
            ['Nombre de salles spécialisées', 'Nombre de salles spécialisées : '.$x('salles_specialisees').'   Nombre de salles utilisées : '.$x('salles_specialisees_utilisees')],
            ['Nombre de laboratoires', 'Nombre de laboratoires : '.$x('laboratoires')],
            ['Effectif : Filles', 'Effectif : Filles : '.$x('effectif_f').'   Garçons : '.$x('effectif_g').'   Total : '.$effectif.'   Nombre de Groupes Pédagogiques : '.$x('groupes')],
            ['Effectif global du personnel', 'Effectif global du personnel : '.$x('personnel_global')],
            ['Effectif du personnel enseignant', 'Effectif du personnel enseignant : Femmes : '.$x('enseignants_f').'   Hommes : '.$x('enseignants_h').'   Total : '.$enseignants],
            ["Nombre d'élèves inscrits", "Nombre d'élèves inscrits depuis l'ouverture de l'établissement : ".$x('inscrits_depuis')],
            ['Nombres de diplômés', "Nombre de diplômés depuis l'ouverture de l'établissement : ".$x('diplomes_depuis')],
            ['Superficie', 'Superficie du domaine scolaire : '.$x('superficie')],
            ['Références du titre foncier', 'Références du titre foncier : '.$x('titre_foncier')],
            ['Electricité', 'Électricité : '.$x('electricite')],
            ['Autres informations', 'Autres informations : '.$x('autres')],
        ] as [$debut, $texte]) {
            $this->libelle('B', $debut, $texte, 12, 50);
        }
        // Fonctionnaires, contractuels, permanents et vacataires : libellés sur une ligne, femmes et hommes sur les deux suivantes.
        $ligne = $this->libelle('B', 'Fonctionnaires Etat', 'Fonctionnaires État   |   Fonctionnaires Collectivités   |   Contractuels', 12, 50);
        if ($ligne) {
            $this->f->setCellValueExplicit('B'.($ligne + 1), 'Femmes : '.$x('fetat_f').'   |   Femmes : '.$x('fct_f').'   |   Femmes : '.$x('contractuels_f'), DataType::TYPE_STRING);
            $this->f->setCellValueExplicit('B'.($ligne + 2), 'Hommes : '.$x('fetat_h').'   |   Hommes : '.$x('fct_h').'   |   Hommes : '.$x('contractuels_h'), DataType::TYPE_STRING);
        }
        $ligne = $this->libelle('B', 'Permanents', 'Permanents   |   Vacataires', 12, 50);
        if ($ligne) {
            $this->f->setCellValueExplicit('B'.($ligne + 1), 'Femmes : '.$x('permanents_f').'   |   Femmes : '.$x('vacataires_f'), DataType::TYPE_STRING);
            $this->f->setCellValueExplicit('B'.($ligne + 2), 'Hommes : '.$x('permanents_h').'   |   Hommes : '.$x('vacataires_h'), DataType::TYPE_STRING);
        }
    }

    /* ——— Technique et professionnel : page d'identification, commune aux quatre maquettes ——— */

    private function etp(Spreadsheet $c, array $d, string $annee, string $academie): void
    {
        $this->feuille($c, 'NOM ETABLIS');
        $i = $d['infos'] ?? [];
        $x = fn (string $cle) => $i[$cle] ?? '';
        $this->f->setCellValueExplicit('A6', "ACADEMIE D'ENSEIGNEMENT DE ".Str::upper($academie), DataType::TYPE_STRING);
        $this->f->setCellValueExplicit('G8', 'ANNEE SCOLAIRE '.$annee, DataType::TYPE_STRING);
        $this->libelle('A', 'N° Tél', 'N° Tél : '.$x('telephone').'   Email : '.$x('email'), 8, 12);
        foreach ([
            ["Nom de l'Etablissement", "Nom de l'Établissement : ".$x('nom')],
            ["Ordre d'Enseignement", "Ordre d'Enseignement : ".($x('ordre') ?: 'Technique et professionnel')],
            ["Région d'Implantation", "Région d'Implantation : ".$x('region')],
            ["Cercle d'Implantation", "Cercle d'Implantation : ".$x('cercle')],
            ['Arrondissement', 'Arrondissement : '.$x('arrondissement')],
            ['Commune', 'Commune : '.$x('commune')],
            ['Texte de Création', 'Texte de Création : '.$x('arrete_creation')],
            ["Arrêté d'Ouverture", "Arrêté d'Ouverture : ".$x('arrete_ouverture')],
            ["Date d'Ouverture", "Date d'Ouverture : ".$x('date_ouverture')],
            ['Mission', 'Mission : '.$x('mission')],
            ['Statut', 'Statut : '.$x('statut')],
            ['Diplômes de fin de Cycle', 'Diplômes de fin de Cycle : '.$x('diplome')],
            ["Capacité d'Accueil", "Capacité d'Accueil : ".$x('capacite')],
            ['Nombre de Salles Ordinaires', 'Nombre de Salles Ordinaires : '.$x('salles_ordinaires')],
            ['Nombre de Salles Spécialisées', 'Nombre de Salles Spécialisées : '.$x('salles_specialisees')],
            ['Effectif Personnel Enseignant', 'Effectif Personnel Enseignant : '.((int) ($i['enseignants_f'] ?? 0) + (int) ($i['enseignants_h'] ?? 0))],
            ['Superficie du domaine scolaire', 'Superficie du domaine scolaire : '.$x('superficie')],
            ['Titre de Propriété', 'Titre de Propriété : '.$x('titre_foncier')],
            ['Autres informations', 'Autres informations : '.$x('autres')],
        ] as [$debut, $texte]) {
            $this->libelle('A', $debut, $texte, 12, 60);
        }
        $this->libelle('A', 'Effectif élèves', 'Effectif élèves : '.((int) ($i['effectif_f'] ?? 0) + (int) ($i['effectif_g'] ?? 0)), 30, 45);
    }

    /** Enseignants de l'établissement, pour la liste nominative du canevas. */
    public static function enseignants(int $idEcole, array $classes): Collection
    {
        $requete = DB::table('enseignants')->where('id_ecole', $idEcole)->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))->orderBy('nom_prenom_enseignant');
        if (DB::table('classe')->where('idEcole', $idEcole)->count() !== count($classes)) {
            $requete->whereIn('id_enseignant', DB::table('ligneclasse')->whereIn('id_classe', array_keys($classes))->whereNotNull('id_enseignants')->pluck('id_enseignants')
                ->merge(DB::table('classe_maitres')->whereIn('id_classe', array_keys($classes))->pluck('id_enseignant'))->unique());
        }

        return $requete->get();
    }
}
