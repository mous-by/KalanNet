<?php

namespace App\Services\Rapports;

use App\Models\Classe;
use App\Services\Bulletins\MoyennesAnnuelles;
use App\Support\DateNaissance;
use App\Support\ExamenNational;
use App\Support\ModelesBulletin;
use App\Support\PropositionPassageDonnees;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Canevas officiels des rapports de l'enseignement secondaire général (Mali) : le vrai fichier Excel, avec ses formules et
 * ses cases de vérification, dans lequel KalanNet écrit ce qu'il connaît. Le lycée complète le reste dans Excel.
 */
class CanevasEsg
{
    public const TYPES = ['rentree' => 'Rapport de rentrée', 'fin' => 'Rapport de fin d\'année'];

    private const FICHIERS = ['rentree' => 'rapport_rentree_esg.xlsx', 'fin' => 'rapport_fin_annee_esg.xlsx'];

    /** Ce que KalanNet remplit, montré à l'école. */
    public const REMPLIS = [
        'rentree' => ['Informations générales (T0)', 'Nombre de classes par série (T2)', 'Effectifs par série et par sexe (T5)', 'Passants et redoublants (T6)', 'Effectifs par âge (T7)', 'Effectifs par scolarité (T9)', 'Admis au Bac de la session précédente (T10)', 'Liste des enseignants (T21)', 'Courriel de l\'établissement (T24)'],
        'fin' => ['Informations générales (T0)', 'Effectifs et abandons de 10e et 11e (T6 à T9)', 'Effectifs et admis au Bac des terminales (T11)', 'Admis au Bac par scolarité (T13)', 'Moyennes annuelles par tranche (T15)'],
    ];

    private const SERIES = ['10CG', '11L', '11SES', '11S', 'TLL', 'TAL', 'TSS', 'TSECO', 'TSE', 'TSEXP'];

    /** @var list<string> */
    private array $avertissements = [];

    /** Série du canevas d'une classe, d'après son nom et sa série ; null si elle n'est pas reconnue. */
    public static function serie(?string $nom, ?string $serie = null): ?string
    {
        $texte = Str::lower(Str::ascii(trim($nom.' '.$serie)));
        foreach (['TSEXP' => 't\s*s?\s*\.?exp', 'TSECO' => 't\s*s?\s*\.?eco', 'TSS' => 'tss', 'TSE' => 'tse', 'TAL' => 'tal', 'TLL' => 'tll'] as $code => $motif) {
            if (preg_match('/\b'.$motif.'/', $texte)) {
                return $code;
            }
        }
        $niveau = ExamenNational::extractClasseLevel($nom);
        if ($niveau === 10) {
            return '10CG';
        }
        if ($niveau === 11) {
            return match (true) {
                (bool) preg_match('/\bses\b|economi/', $texte) => '11SES',
                (bool) preg_match('/lettre|litter|\bl\b/', $texte) => '11L',
                (bool) preg_match('/science|\bs\b/', $texte) => '11S',
                default => null,
            };
        }

        return null;
    }

    public static function ecoleConcernee(?int $idEcole): bool
    {
        return $idEcole && ExamenNational::estMali($idEcole) && DB::table('classe')->where('idEcole', $idEcole)->where('ordreEnseignement', 'secondairegenerale')->exists();
    }

    /** L'académie de l'école a-t-elle un compte DAE actif ? Sinon, le rapport se remet hors de KalanNet. */
    public static function academieSurKalanNet(?int $idAcademie): bool
    {
        return $idAcademie && DB::table('utilisateurs')->where('droit', 'DAE')->where('id_academie', $idAcademie)->where('statut', 1)->exists();
    }

    /** @return array{0: Spreadsheet, 1: list<string>} le classeur rempli et ce que l'école doit vérifier */
    public function remplir(object $ecole, object $annee, string $type): array
    {
        $classeur = IOFactory::load(resource_path('canevas/'.self::FICHIERS[$type]));
        $this->avertissements = [];
        $eleves = $this->eleves((int) $ecole->idEcole, (int) $annee->id_anneeScolaire);
        $enseignants = $this->enseignants((int) $ecole->idEcole);

        $this->informations($classeur->getSheetByName('T0'), $ecole, $annee, $eleves, $enseignants);
        if ($type === 'rentree') {
            $this->rentree($classeur, $ecole, $annee, $eleves, $enseignants);
        } else {
            $this->fin($classeur, $ecole, $annee, $eleves);
        }

        return [$classeur, $this->avertissements];
    }

    /** Élèves des classes de secondaire général de l'année, avec leur série et leurs redoublements au lycée. */
    private function eleves(int $idEcole, int $idAnnee): Collection
    {
        $classes = DB::table('classe')->where('idEcole', $idEcole)->where('ordreEnseignement', 'secondairegenerale')->get(['id_classe', 'nom_classe', 'serie']);
        $series = $classes->mapWithKeys(fn ($c) => [$c->id_classe => self::serie($c->nom_classe, $c->serie)]);

        $inconnues = $classes->filter(fn ($c) => $series[$c->id_classe] === null)->pluck('nom_classe');
        if ($inconnues->isNotEmpty()) {
            $this->avertissements[] = 'Classes dont la série n\'est pas reconnue, non comptées : '.$inconnues->implode(', ').'.';
        }

        $eleves = DB::table('eleve')->where('id_ecole', $idEcole)->where('id_annee', $idAnnee)->whereIn('etat_dossier', [0, 2])
            ->whereIn('id_classe', $series->filter()->keys())->get(['id_eleve', 'id_classe', 'genre_eleve', 'date_naissance', 'etat_dossier']);

        $debut = DB::table('anneescolaire')->where('id_anneeScolaire', $idAnnee)->value('date_debut');
        $redoublements = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
            ->join('classe as c', 'c.id_classe', '=', 'lr.id_classe')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'lr.id_annee')
            ->where('r.statut', 'redoublant')->where('c.ordreEnseignement', 'secondairegenerale')->whereIn('lr.id_eleve', $eleves->pluck('id_eleve'))
            ->where('a.date_debut', '<=', $debut)->get(['lr.id_eleve', 'lr.id_annee']);

        return $eleves->map(function ($e) use ($series, $redoublements, $idAnnee) {
            $e->serie = $series[$e->id_classe];
            $e->fille = PropositionPassageDonnees::fille($e->genre_eleve);
            $e->actif = (int) $e->etat_dossier === 0;
            $e->redoublements = $redoublements->where('id_eleve', $e->id_eleve)->count();
            $e->redouble = $redoublements->where('id_eleve', $e->id_eleve)->where('id_annee', $idAnnee)->isNotEmpty();

            return $e;
        });
    }

    /** Enseignants du lycée : tous s'il n'a pas d'autre ordre, sinon ceux qui enseignent dans ses classes. */
    private function enseignants(int $idEcole): Collection
    {
        $lycee = DB::table('classe')->where('idEcole', $idEcole)->where('ordreEnseignement', 'secondairegenerale')->pluck('id_classe');
        $requete = DB::table('enseignants')->where('id_ecole', $idEcole)->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))->orderBy('nom_prenom_enseignant');

        if (DB::table('classe')->where('idEcole', $idEcole)->count() !== $lycee->count()) {
            $ids = DB::table('ligneclasse')->whereIn('id_classe', $lycee)->whereNotNull('id_enseignants')->pluck('id_enseignants')
                ->merge(DB::table('classe_maitres')->whereIn('id_classe', $lycee)->pluck('id_enseignant'))->unique();
            $requete->whereIn('id_enseignant', $ids);
        }

        return $requete->get();
    }

    private function libelle(Worksheet $feuille, string $debut, string $texte): void
    {
        foreach (range(12, 50) as $ligne) {
            if (str_starts_with(trim(preg_replace('/\s+/u', ' ', (string) $feuille->getCell('B'.$ligne)->getValue())), $debut)) {
                $feuille->setCellValue('B'.$ligne, $texte);

                return;
            }
        }
    }

    private function informations(Worksheet $f, object $ecole, object $annee, Collection $eleves, Collection $enseignants): void
    {
        $academie = DB::table('academie')->where('id_academie', $ecole->id_academie)->value('nom_academie') ?: $ecole->academie;

        $f->setCellValue('A7', "ACADEMIE D'ENSEIGNEMENT DE {$academie}          ANNEE SCOLAIRE {$annee->annee}");
        $f->setCellValue('A9', 'Lycée '.$ecole->nomEcole);
        $f->setCellValue('A10', ' BP……;N° Tél '.$ecole->telephone.'   Adresse email: '.$ecole->email);

        $filles = $eleves->where('actif', true)->where('fille', true)->count();
        $garcons = $eleves->where('actif', true)->where('fille', false)->count();
        $groupes = $eleves->where('actif', true)->pluck('id_classe')->unique()->count();
        $femmes = $enseignants->filter(fn ($e) => PropositionPassageDonnees::fille($e->genre_enseignant))->count();

        $this->libelle($f, "Nom de l'établissement", "Nom de l'établissement: ".$ecole->nomEcole);
        $this->libelle($f, 'Statut', 'Statut : '.Str::ucfirst((string) $ecole->statut));
        $this->libelle($f, 'Effectif : Filles', "Effectif : Filles : {$filles}   Garçons : {$garcons}   Total : ".($filles + $garcons)."   Nombre de Groupes Pédagogiques : {$groupes}");
        $this->libelle($f, 'Effectif du personnel enseignant', "Effectif du personnel enseignant : Femmes : {$femmes}   Hommes : ".($enseignants->count() - $femmes).'   Total : '.$enseignants->count());
    }

    /** @return array{0: int, 1: int} filles, garçons */
    private function parSexe(Collection $eleves): array
    {
        return [$eleves->where('fille', true)->count(), $eleves->where('fille', false)->count()];
    }

    private function rentree(Spreadsheet $c, object $ecole, object $annee, Collection $tous, Collection $enseignants): void
    {
        $eleves = $tous->where('actif', true)->values();

        // T2 : nombre de classes par série.
        foreach (['10CG' => 'B', '11L' => 'C', '11SES' => 'D', '11S' => 'E', 'TLL' => 'F', 'TAL' => 'G', 'TSS' => 'H', 'TSECO' => 'I', 'TSE' => 'J', 'TSEXP' => 'K'] as $serie => $col) {
            $c->getSheetByName('T2')->setCellValue($col.'4', $eleves->where('serie', $serie)->pluck('id_classe')->unique()->count());
        }

        // T5 et T6 : effectifs, passants et redoublants.
        $t5 = ['10CG' => 5, '11L' => 7, '11SES' => 8, '11S' => 9, 'TAL' => 11, 'TLL' => 12, 'TSECO' => 13, 'TSS' => 14, 'TSE' => 15, 'TSEXP' => 16];
        $t6 = ['10CG' => 6, '11L' => 8, '11SES' => 9, '11S' => 10, 'TAL' => 12, 'TLL' => 13, 'TSECO' => 14, 'TSS' => 15, 'TSE' => 16, 'TSEXP' => 17];
        foreach (self::SERIES as $serie) {
            $groupe = $eleves->where('serie', $serie);
            [$f, $g] = $this->parSexe($groupe);
            $s5 = $c->getSheetByName('T5');
            $s5->setCellValue('B'.$t5[$serie], $f + $g);
            $s5->setCellValue('C'.$t5[$serie], $f);
            $s5->setCellValue('E'.$t5[$serie], $g);

            [$rf, $rg] = $this->parSexe($groupe->where('redouble', true));
            $s6 = $c->getSheetByName('T6');
            $s6->setCellValue('B'.$t6[$serie], $f - $rf);
            $s6->setCellValue('D'.$t6[$serie], $g - $rg);
            $s6->setCellValue('F'.$t6[$serie], $rf);
            $s6->setCellValue('H'.$t6[$serie], $rg);
        }
        // La ligne de 10e n'a pas de formule de total dans le canevas.
        [$f10, $g10] = $this->parSexe($eleves->where('serie', '10CG'));
        $c->getSheetByName('T6')->setCellValue('J6', $f10)->setCellValue('L6', $g10);

        $this->ages($c, $eleves, $annee);
        $this->scolarite($c, $eleves);
        $this->bacPrecedent($c, $ecole, $annee);
        $this->listeEnseignants($c->getSheetByName('T21'), $enseignants);
        if ($ecole->email) {
            $c->getSheetByName('T24')->setCellValue('A20', 'ADRESSE MAIL ETABLISSEMENT : '.$ecole->email);
        }
    }

    /** T7 et sa suite : âge atteint au 31 décembre de l'année de la rentrée. */
    private function ages(Spreadsheet $c, Collection $eleves, object $annee): void
    {
        $colonnes = [
            'T7' => ['10CG' => 'B', '11L' => 'D', '11SES' => 'F', '11S' => 'H'],
            'T_7_S' => ['TAL' => 'B', 'TLL' => 'D', 'TSECO' => 'F', 'TSS' => 'H', 'TSE' => 'J', 'TSEXP' => 'L'],
        ];
        $reference = Carbon::parse($annee->date_debut)->endOfYear();
        $sansDate = 0;
        $comptes = [];
        foreach ($eleves as $eleve) {
            $date = DateNaissance::pourSaisie($eleve->date_naissance);
            try {
                $age = $date ? Carbon::createFromFormat('d/m/Y', $date)->diffInYears($reference) : null;
            } catch (\Throwable) {
                $age = null;
            }
            if ($age === null) {
                $sansDate++;
                continue;
            }
            $ligne = $age < 13 ? 5 : min(18, 6 + ((int) $age - 13));
            $comptes[$eleve->serie][$ligne][$eleve->fille ? 'f' : 'g'] = ($comptes[$eleve->serie][$ligne][$eleve->fille ? 'f' : 'g'] ?? 0) + 1;
        }
        foreach ($colonnes as $feuille => $series) {
            foreach ($series as $serie => $col) {
                foreach ($comptes[$serie] ?? [] as $ligne => $sexes) {
                    $c->getSheetByName($feuille)->setCellValue($col.$ligne, $sexes['f'] ?? 0);
                    $c->getSheetByName($feuille)->setCellValue(chr(ord($col) + 1).$ligne, $sexes['g'] ?? 0);
                }
            }
        }
        if ($sansDate > 0) {
            $this->avertissements[] = "{$sansDate} élève(s) sans date de naissance valable : absents du tableau par âge (T7), sa vérification signalera un écart.";
        }
    }

    /** T9 : années passées au lycée (niveau d'entrée + redoublements), plafonnées à la dernière ligne du tableau. */
    private function scolarite(Spreadsheet $c, Collection $eleves): void
    {
        $f = $c->getSheetByName('T9');
        $tables = [
            [['10CG' => 'B'], 7, 3],
            [['11L' => 'B', '11S' => 'D', '11SES' => 'F'], 16, 4],
            [['TLL' => 'B', 'TAL' => 'D', 'TSS' => 'F', 'TSECO' => 'H', 'TSE' => 'J', 'TSEXP' => 'L'], 26, 4],
        ];
        foreach ($tables as [$series, $premiere, $lignes]) {
            foreach ($series as $serie => $col) {
                foreach (range(0, $lignes - 1) as $i) {
                    $groupe = $eleves->where('serie', $serie)->filter(fn ($e) => min($e->redoublements, $lignes - 1) === $i);
                    [$filles, $garcons] = $this->parSexe($groupe);
                    $f->setCellValue($col.($premiere + $i), $filles);
                    $suivante = chr(ord($col) + 1);
                    $f->setCellValue($suivante.($premiere + $i), $garcons);
                }
            }
        }
    }

    /** @return Collection<int, object> résultats du Bac d'une année, avec série et sexe */
    private function resultatsBac(int $idEcole, array $idsAnnee): Collection
    {
        return DB::table('resultats_def_terminal as r')->join('eleve as e', 'e.id_eleve', '=', 'r.id_eleve')->leftJoin('classe as c', 'c.id_classe', '=', 'r.id_classe')
            ->where('e.id_ecole', $idEcole)->whereIn('r.id_annee', $idsAnnee)->where('r.niveau_examen', 'BAC')
            ->get(['r.id_eleve', 'r.decision', 'c.nom_classe', 'c.serie', 'e.genre_eleve'])
            ->map(function ($r) {
                $r->serie_canevas = self::serie($r->nom_classe, $r->serie);
                $r->fille = PropositionPassageDonnees::fille($r->genre_eleve);
                $r->admis = $r->decision === 'admis';

                return $r;
            });
    }

    /** T10 : admis au Bac de l'année précédente (inscrits et présents restent à saisir). */
    private function bacPrecedent(Spreadsheet $c, object $ecole, object $annee): void
    {
        $precedente = DB::table('anneescolaire')->where('date_debut', '<', $annee->date_debut)->orderByDesc('date_debut')->value('annee');
        if (!$precedente) {
            return;
        }
        $resultats = $this->resultatsBac((int) $ecole->idEcole, DB::table('anneescolaire')->where('annee', $precedente)->pluck('id_anneeScolaire')->all());
        foreach (['TAL' => 6, 'TLL' => 8, 'TSECO' => 10, 'TSS' => 12, 'TSE' => 14, 'TSEXP' => 16] as $serie => $ligne) {
            $admis = $resultats->where('serie_canevas', $serie)->where('admis', true);
            $c->getSheetByName('T10')->setCellValue('H'.$ligne, $admis->where('fille', true)->count());
            $c->getSheetByName('T10')->setCellValue('I'.$ligne, $admis->where('fille', false)->count());
        }
    }

    private function listeEnseignants(Worksheet $f, Collection $enseignants): void
    {
        $ligne = 5;
        foreach ($enseignants as $e) {
            if ($ligne > 57) {
                $this->avertissements[] = 'Le tableau T21 ne contient que 53 lignes : '.($enseignants->count() - 53).' enseignant(s) à ajouter à la main.';
                break;
            }
            [$prenom, $nom] = PropositionPassageDonnees::decouperNom($e->nom_prenom_enseignant);
            $f->setCellValue('B'.$ligne, $prenom);
            $f->setCellValue('C'.$ligne, $nom);
            $f->setCellValue('D'.$ligne, PropositionPassageDonnees::fille($e->genre_enseignant) ? 'F' : 'H');
            $f->setCellValue('E'.$ligne, $e->date_naissance_enseignant ? substr((string) $e->date_naissance_enseignant, 0, 4) : null);
            $f->setCellValueExplicit('F'.$ligne, (string) $e->matricule, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $f->setCellValue('J'.$ligne, $e->specialite);
            $f->setCellValue('S'.$ligne, $e->nombre_enfants);
            $ligne++;
        }
    }

    private function fin(Spreadsheet $c, object $ecole, object $annee, Collection $tous): void
    {
        // T6 à T9 : effectif et abandons ; passages, redoublements et exclusions relèvent du conseil de classe.
        foreach ([['T6', 11, '10CG', 'V'], ['T 7 9', 8, '11L', 'S'], ['T 7 9', 18, '11S', 'S'], ['T 7 9', 28, '11SES', 'S']] as [$feuille, $ligne, $serie, $abandon]) {
            [$f, $g] = $this->parSexe($tous->where('serie', $serie));
            [$af, $ag] = $this->parSexe($tous->where('serie', $serie)->where('actif', false));
            $s = $c->getSheetByName($feuille);
            $s->setCellValue('A'.$ligne, $f);
            $s->setCellValue('B'.$ligne, $g);
            $s->setCellValue($abandon.$ligne, $af);
            $s->setCellValue(chr(ord($abandon) + 1).$ligne, $ag);
        }

        // T11 et T13 : terminales et Bac de la session.
        $resultats = $this->resultatsBac((int) $ecole->idEcole, DB::table('anneescolaire')->where('annee', $annee->annee)->pluck('id_anneeScolaire')->all());
        $parEleve = $tous->keyBy('id_eleve');
        foreach (['TLL' => [5, 7], 'TAL' => [7, 8], 'TSS' => [10, 9], 'TSECO' => [12, 10], 'TSE' => [15, 11], 'TSEXP' => [17, 12]] as $serie => [$t11, $t13]) {
            [$f, $g] = $this->parSexe($tous->where('serie', $serie)->where('actif', true));
            $admis = $resultats->where('serie_canevas', $serie)->where('admis', true);
            $s = $c->getSheetByName('T11');
            $s->setCellValue('B'.$t11, $f);
            $s->setCellValue('C'.$t11, $g);
            $s->setCellValue('E'.$t11, $admis->where('fille', true)->count());
            $s->setCellValue('F'.$t11, $admis->where('fille', false)->count());

            foreach (['B', 'E', 'H'] as $i => $col) {
                $groupe = $admis->filter(fn ($r) => min($parEleve[$r->id_eleve]->redoublements ?? 0, 2) === $i);
                $s13 = $c->getSheetByName('T 13 14');
                $s13->setCellValue($col.$t13, $groupe->where('fille', true)->count());
                $s13->setCellValue(chr(ord($col) + 1).$t13, $groupe->where('fille', false)->count());
            }
        }

        $this->tranches($c->getSheetByName('T15'), $tous->where('actif', true), (int) $annee->id_anneeScolaire);
    }

    /** T15 : moyennes annuelles par tranche, par série. */
    private function tranches(Worksheet $f, Collection $eleves, int $idAnnee): void
    {
        $lignes = ['10CG' => 6, '11L' => 7, '11SES' => 8, '11S' => 9, 'TLL' => 10, 'TAL' => 11, 'TSS' => 12, 'TSECO' => 13, 'TSE' => 14, 'TSEXP' => 15];
        $colonnes = ['B', 'D', 'F', 'H', 'J', 'L'];
        $comptes = [];
        $sansMoyenne = 0;
        foreach ($eleves->groupBy('id_classe') as $idClasse => $groupe) {
            $classe = Classe::withoutGlobalScopes()->find($idClasse);
            $moyennes = MoyennesAnnuelles::pour($classe, $idAnnee, ModelesBulletin::calcul(ModelesBulletin::pourClasse($classe)));
            foreach ($groupe as $eleve) {
                $moyenne = $moyennes->pourEleve($eleve->id_eleve)['moyenne'] ?? null;
                if ($moyenne === null) {
                    $sansMoyenne++;
                    continue;
                }
                $moyenne = (float) $moyenne;
                $col = match (true) {
                    $moyenne < 7 => 'B', $moyenne < 10 => 'D', $moyenne < 12 => 'F', $moyenne < 14 => 'H', $moyenne < 16 => 'J', default => 'L',
                };
                $comptes[$eleve->serie][$col][$eleve->fille ? 0 : 1] = ($comptes[$eleve->serie][$col][$eleve->fille ? 0 : 1] ?? 0) + 1;
            }
        }
        foreach ($lignes as $serie => $ligne) {
            foreach ($colonnes as $col) {
                $f->setCellValue($col.$ligne, $comptes[$serie][$col][0] ?? 0);
                $f->setCellValue(chr(ord($col) + 1).$ligne, $comptes[$serie][$col][1] ?? 0);
            }
        }
        if ($sansMoyenne > 0) {
            $this->avertissements[] = "{$sansMoyenne} élève(s) sans moyenne annuelle : absents du tableau par tranche (T15).";
        }
    }
}
