<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\RapportSecondaire;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Canevas officiels du ministère remplis avec le rapport de l'établissement : saisies de l'écran recopiées case par case,
 * formules du canevas intactes ; maquette du technique choisie par l'école ; partie administrative et annexes.
 */
class CanevasOfficielTest extends IntegrationTestCase
{
    private AnneeScolaire $annee;
    /** @var list<int> */
    private array $eleves;
    private ?Spreadsheet $ouvert = null;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('rapports_secondaire')->where('id_ecole', 1)->delete();
        $this->annee = AnneeScolaire::enCours(1);
        $modele = Classe::where('idEcole', 1)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(3)->pluck('id_eleve')->all();
        if (!$modele || count($eleves) < 3) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Complexe Scolaire', 'maquette_etp' => null]);
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => null]);
        $classes = [];
        foreach (['10' => ['10ème année commune', 'secondairegenerale'], 'gc' => ['1ère année Génie civil', 'secondairetechniqueetprofessionnel']] as $cle => [$nom, $ordre]) {
            $classe = $modele->replicate();
            $classe->nom_classe = $nom;
            $classe->ordreEnseignement = $ordre;
            $classe->serie = null;
            $classe->save();
            $classes[$cle] = $classe;
        }
        $this->eleves = $eleves;
        DB::table('eleve')->where('id_ecole', 1)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        DB::table('eleve')->whereIn('id_eleve', [$eleves[0], $eleves[1]])->update(['id_classe' => $classes['10']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => 'Féminin', 'adresse_eleve' => 'Sansanding']);
        DB::table('eleve')->where('id_eleve', $eleves[2])->update(['id_classe' => $classes['gc']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => 'Masculin']);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
    }

    protected function tearDown(): void
    {
        $this->ouvert?->disconnectWorksheets();
        parent::tearDown();
    }

    private function enregistrer(string $type, array $tableaux, array $autres = []): void
    {
        $this->post(route('rapport-secondaire.store', ['type' => $type, 'annee' => $this->annee->id_anneeScolaire], false), $autres + [
            'action' => 'enregistrer', 'infos' => ['nom' => 'Lycée Ahalya', 'region' => 'Ségou', 'cercle' => 'Markala', 'commune' => 'Sansanding', 'arrete_creation' => 'Décision 0992'],
            'tableaux_json' => json_encode($tableaux),
        ])->assertSessionHasNoErrors();
    }

    private function canevas(string $type, string $modele): Spreadsheet
    {
        $this->ouvert?->disconnectWorksheets();
        $reponse = $this->get(route('rapport-secondaire.canevas', ['type' => $type, 'annee' => $this->annee->id_anneeScolaire, 'modele' => $modele], false))->assertOk();
        $fichier = tempnam(sys_get_temp_dir(), 'kn').'.xlsx';
        copy($reponse->baseResponse->getFile()->getPathname(), $fichier);
        $this->ouvert = IOFactory::load($fichier);
        unlink($fichier);

        return $this->ouvert;
    }

    public function test_canevas_de_rentree_du_general_recopie_chaque_saisie(): void
    {
        $this->enregistrer('rentree', [
            'T1.1' => ['l0' => ['n1' => 3]], 'T1.2' => ['l4' => ['nombre' => 2]], 'T2' => ['l0' => ['10CG' => 4]],
            'T7' => ['a15' => ['10CG_f' => 2]], 'T8' => ['l7' => ['10CG_f' => 2]], 'T12' => ['l0' => ['fetat_h' => 2, 'vact_f' => 1]],
            'T13' => ['l0' => ['f' => 1, 'h' => 5]], 'T14' => ['l1' => ['a_f' => 1]], 'T15' => ['l0' => ['h' => 4]], 'T16' => ['l4' => ['f' => 2]],
            'T17' => ['l1' => ['maliens_g' => 3]], 'T24' => ['l0' => ['prenom' => 'Abdrahamane', 'nom' => 'TRAORE']],
        ]);
        $c = $this->canevas('rentree', 'esg');
        $v = fn (string $feuille, string $cellule) => $c->getSheetByName($feuille)->getCell($cellule)->getValue();

        $this->assertStringContainsString('Ségou', $v('T0', 'B15'));
        $this->assertStringContainsString('Décision 0992', $v('T0', 'B18'));
        $this->assertStringContainsString('Lycée Ahalya', $v('T0', 'A9'));
        $this->assertSame(3, $v('T1.1', 'B5'));
        $this->assertSame(2, $v('T1.2', 'B9'));
        $this->assertSame(4, $v('T2', 'B5'), 'Mathématiques, 10e CG');
        $this->assertSame(2, $v('T7', 'B8'), '15 ans, filles de 10e');
        $this->assertSame(2, $v('T8', 'B12'), 'Ségou, filles de 10e');
        $this->assertSame([2, 1], [$v('T12', 'B5'), $v('T12', 'O5')], 'Mathématiques : F/ÉTAT hommes, vacataires femmes');
        $this->assertSame([1, 5], [$v('T13-14', 'A6'), $v('T13-14', 'B6')]);
        $this->assertSame(1, $v('T13-14', 'C18'));
        $this->assertSame(4, $v('T15-16', 'B6'));
        $this->assertSame(2, $v('T15-16', 'I14'), 'licence, femmes');
        $this->assertSame(3, $v('T17', 'C7'), 'physique-chimie, maliens hommes');
        $this->assertSame(['Abdrahamane', 'TRAORE'], [$v('T24', 'C4'), $v('T24', 'D4')]);
        // Formules et vérifications du ministère intactes.
        $this->assertStringStartsWith('=IF(AND(C5+E5=B5', $v('T5', 'H5'));
        $this->assertStringStartsWith('=SUM(', (string) $v('T1.1', 'E5'));
    }

    public function test_les_tableaux_ajoutes_du_canevas_sont_recopies(): void
    {
        $this->enregistrer('rentree', [
            'T3' => ['n1' => ['numero' => 'S1', 'capacite' => '60', 'etat' => 'a'], 'n2' => ['numero' => 'S2']],
            'T4' => ['l6' => ['capacite' => '30']],
            'T19' => ['a30_f' => ['a_fetat' => 2], 'a50_h' => ['c_vact' => 1]],
            'T20' => ['a20_h' => ['b' => 3]],
            'T21' => ['n1' => ['prenoms' => 'Awa', 'nom' => 'KONE', 'fonction' => 'Professeur', 'cat' => 'A']],
            'T22' => ['n1' => ['prenoms' => 'Ali', 'nom' => 'SIDIBE', 'fonction' => 'Gardien']],
            'T26' => ['l0' => ['du' => '1250000']],
            'T29' => ['n3' => ['libelle' => 'Clôture']],
            'T30' => ['vue_2' => ['10CG_f' => 1]],
            'T31' => ['n1' => ['partenaire' => 'UNICEF']],
        ]);
        $c = $this->canevas('rentree', 'esg');
        $v = fn (string $feuille, string $cellule) => $c->getSheetByName($feuille)->getCell($cellule)->getValue();

        $this->assertSame(['S1', 'a', 60, 'S2'], [$v('T3', 'B5'), $v('T3', 'D5'), $v('T3', 'E5'), $v('T3', 'B6')]);
        $this->assertSame(30, $v('T4', 'D11'), 'salle informatique 1');
        $this->assertSame([2, 1], [$v('T19', 'C12'), $v('T19', 'V19')], '30-39 ans F, cat. A F/ÉTAT ; 50 ans et plus H, cat. C vacataires');
        $this->assertSame(3, $v('T20', 'D11'), '20-29 ans H, cat. B');
        $this->assertSame(['Awa', 'KONE', 'Professeur', 'A'], [$v('T21', 'B5'), $v('T21', 'C5'), $v('T21', 'L5'), $v('T21', 'O5')], 'saisie de l\'établissement');
        $this->assertSame(['Ali', 'Gardien'], [$v('T22', 'B5'), $v('T22', 'K5')]);
        $this->assertSame(1250000, $v('T25-26-27', 'B12'), 'salaires d\'octobre');
        $this->assertSame(['1', 'Clôture'], [(string) $v('T29', 'A4'), $v('T29', 'B4')], 'liste renumérotée sans trou');
        $this->assertSame(1, $v('T30', 'C7'), 'vue, beaucoup de difficultés, filles de 10e');
        $this->assertSame('UNICEF', $v('T31', 'B4'));
    }

    public function test_sans_rien_enregistrer_le_canevas_porte_les_chiffres_de_kalannet(): void
    {
        $c = $this->canevas('rentree', 'esg');
        $v = fn (string $feuille, string $cellule) => $c->getSheetByName($feuille)->getCell($cellule)->getValue();
        $this->assertSame([2, 2, null], [$v('T5', 'B5'), $v('T5', 'C5'), $v('T5', 'E5')], 'effectif de 10e (aucun garçon : case laissée vide, comptée zéro par le canevas)');
        $this->assertSame(1, $v('T2', 'B4'), 'nombre de classes de 10e');
        $this->assertStringContainsString('Filles : 2', preg_replace('/\s+/', ' ', $v('T0', 'B26')));

        $f = $this->canevas('fin', 'esg');
        $this->assertSame([2, null], [$f->getSheetByName('T6')->getCell('A11')->getValue(), $f->getSheetByName('T6')->getCell('B11')->getValue()], 'fin d\'année : effectif de 10e');
    }

    public function test_canevas_de_fin_d_annee_du_general(): void
    {
        $this->enregistrer('fin', [
            'T1' => ['l0' => ['perm_h' => 2]], 'T6' => ['10CG' => ['p11S_f' => 1, 'red_f' => 1, 'insuffisance_g' => 2]],
            'T15' => ['10CG' => ['m12_f' => 2]], 'T18' => ['10CG' => ['capacite' => 60]], 'T19' => ['10CG' => ['passants' => 40]],
            'T20' => ['l0' => ['besoins' => 1]], 'T21' => ['l2' => ['departs' => 1]], 'T22' => ['l1' => ['nom' => 'HAIDARA']],
            'T16' => ['TSE' => ['m12_total' => 3, 'm12_admis' => 2, 'm20_total' => 1, 'm20_admis' => 1]],
        ], ['redaction' => ['difficultes' => 'Manque de tables-bancs.']]);
        $c = $this->canevas('fin', 'esg');
        $v = fn (string $feuille, string $cellule) => $c->getSheetByName($feuille)->getCell($cellule)->getValue();

        $this->assertSame(2, $v('T1', 'F5'), 'Mathématiques, permanents hommes');
        $this->assertSame([1, 1, 2], [$v('T6', 'G11'), $v('T6', 'M11'), $v('T6', 'Q11')], 'passage en 11e S, redoublement, exclusion pour insuffisance');
        $this->assertSame(2, $v('T15', 'F6'));
        // T16 : le canevas calcule les présentés depuis T15 (formule gardée) ; KalanNet écrit les admis.
        $this->assertStringStartsWith('=', (string) $v('T16', 'C28'));
        $this->assertSame(2, $v('T16', 'D28'), 'TSE, 10 ≤ M.A < 12 : admis');
        $this->assertSame(1, $v('T16', 'G44'), 'TSE, M.A ≥ 16 : admis');
        $this->assertSame(60, $v('T 18 19', 'B5'));
        // Les perspectives (T19) sont calculées par le canevas à partir des tableaux 6 à 9 : sa formule est gardée.
        $this->assertSame("=+'T6'!N11", $v('T 18 19', 'B22'));
        $this->assertSame(1, $v('T 20', 'D4'));
        $this->assertSame(1, $v('T 21 22', 'C6'));
        $this->assertSame('HAIDARA', $v('T 21 22', 'D15'));
        $this->assertSame('Manque de tables-bancs.', RapportSecondaire::withoutGlobalScopes()->where('id_ecole', 1)->where('type', 'fin')->first()->donnees['redaction']['difficultes']);
    }

    public function test_les_cinq_modeles_au_choix_de_l_etablissement(): void
    {
        $this->enregistrer('rentree', []);
        // Pas encore de choix, mais des classes du général : l'ESG est proposé d'office.
        $page = $this->get('/rapport-secondaire/rentree')->assertOk()->assertSee('Modèle de rapport demandé par votre académie');
        foreach (['Enseignement secondaire général (ESG)', 'Enseignement secondaire technique (EST)', 'Enseignement secondaire professionnel (ESP)', 'Enseignement agricole'] as $libelle) {
            $page->assertSee($libelle);
        }
        $this->assertSame('esg', $page->viewData('maquette'));
        $this->assertNotNull($this->canevas('rentree', 'choisi')->getSheetByName('T5'), 'canevas ESG');

        $this->post(route('rapport-secondaire.maquette', [], false), ['maquette' => 'inconnue'])->assertSessionHasErrors('maquette');
        $this->post(route('rapport-secondaire.maquette', [], false), ['maquette' => 'esg'])->assertSessionHasNoErrors();
        $this->assertSame('esg', DB::table('ecole')->where('idEcole', 1)->value('maquette_etp'));

        // Établissement seulement technique, sans choix : rien n'est proposé d'office.
        DB::table('classe')->where('idEcole', 1)->where('ordreEnseignement', 'secondairegenerale')->update(['ordreEnseignement' => 'secondairetechniqueetprofessionnel']);
        DB::table('ecole')->where('idEcole', 1)->update(['maquette_etp' => null]);
        $this->get(route('rapport-secondaire.canevas', ['type' => 'rentree', 'modele' => 'choisi'], false))->assertNotFound();

        foreach (['est', 'esp', 'esp_pro', 'agro'] as $maquette) {
            $this->post(route('rapport-secondaire.maquette', [], false), ['maquette' => $maquette])->assertSessionHasNoErrors();
            $this->assertSame($maquette, DB::table('ecole')->where('idEcole', 1)->value('maquette_etp'));
            foreach (['rentree', 'fin'] as $type) {
                $feuille = $this->canevas($type, 'choisi')->getSheetByName('NOM ETABLIS');
                $texte = collect(range(10, 40))->map(fn ($l) => (string) $feuille->getCell('A'.$l)->getValue())->implode(' | ');
                // Fin d'année pas encore enregistrée : les renseignements stables viennent du rapport de rentrée.
                $this->assertStringContainsString($type === 'rentree' ? 'Lycée Ahalya' : 'Markala', $texte, "$maquette $type");
            }
        }
    }

    public function test_annexes_nominatives(): void
    {
        $rentree = \App\Support\RapportSecondaireDonnees::annexes('rentree', 1, $this->annee);
        $dixieme = collect($rentree)->firstWhere('titre', 'Liste nominative des élèves : 10ème année commune');
        $this->assertCount(2, $dixieme['lignes']);
        $this->assertSame('Sansanding', $dixieme['lignes'][0][1]);

        // T24 : listes du canevas tirées des décisions (passage par classe d'arrivée, redoublement, exclusion par motif).
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(10)->get(['id_eleve', 'nom_eleve']);
        $lignes = ['classes' => [900001 => '10CG', 900002 => '11L', 900003 => 'TSE'], 'series' => ['10CG' => '10ème CG', '11L' => '11ème L', 'TSE' => 'TSE']];
        $membre = fn ($e, $cle, $decision, $cible = null, $actif = true) => (object) ['id_eleve' => $e->id_eleve, 'id_classe' => array_search($cle, $lignes['classes']), 'cle' => $cle,
            'decision' => $decision, 'actif' => $actif, 'id_classe_cible' => $cible ? array_search($cible, $lignes['classes']) : null, 'sexe' => 'g'];
        $membres = collect([
            ...$eleves->take(7)->map(fn ($e) => $membre($e, '10CG', 'passant', '11L')),
            $membre($eleves[7], '11L', 'redoublant'), $membre($eleves[8], 'TSE', 'exclu_epuisement'), $membre($eleves[9], '10CG', null, null, false),
        ]);
        $listes = collect(\App\Support\RapportSecondaireDonnees::listesT24($lignes, $membres, (int) $this->annee->id_anneeScolaire, (string) $this->annee->annee))->keyBy('bloc');
        $this->assertSame(['A:10CG>11L', 'B:11L', 'C:10CG:abandon', 'C:TSE:epuisement'], $listes->keys()->sort()->values()->all());
        $this->assertCount(7, $listes['A:10CG>11L']['lignes']);
        $this->assertStringContainsString('proposés au passage en 11ème L', $listes['A:10CG>11L']['titre']);
        $this->assertSame(['N°', 'Prénoms', 'Nom', 'N° Mle', 'Moy. ann.', 'Observations', 'Série'], $listes['B:11L']['entetes']);

        // Canevas : la liste de 7 passants reçoit deux lignes de plus ; la liste suivante est décalée d'autant.
        $classeur = (new \App\Services\Rapports\CanevasOfficiel)->remplir('esg', 'fin', ['infos' => [], 't' => []], \App\Models\Ecole::withoutGlobalScopes()->find(1), (string) $this->annee->annee, 'Kayes', collect(), $listes->values()->all());
        $f = $classeur->getSheetByName('T 23-24');
        $this->assertSame($eleves[6]->nom_eleve, $f->getCell('C73')->getValue(), '7e passant, sur une ligne ajoutée');
        $this->assertSame('N°', $f->getCell('A78')->getValue(), 'liste suivante décalée de deux lignes');
        $classeur->disconnectWorksheets();

        $this->get(route('rapport-secondaire.pdf', ['type' => 'fin', 'annee' => $this->annee->id_anneeScolaire], false))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_l_academie_telecharge_le_canevas_du_rapport_recu(): void
    {
        $academie = (int) DB::table('academie')->value('id_academie');
        DB::table('ecole')->where('idEcole', 1)->update(['id_academie' => $academie]);
        $rapport = RapportSecondaire::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => $this->annee->id_anneeScolaire, 'type' => 'rentree', 'statut' => 'remis',
            'donnees' => [], 'donnees_envoyees' => ['infos' => ['nom' => 'Lycée reçu', 'region' => 'Kayes'], 't' => ['T1.1' => ['l0' => ['n2' => 7]]]]]);
        $dae = $this->compte('Admin', 1)->replicate();
        $dae->forceFill(['droit' => 'DAE', 'idEcole' => null, 'id_academie' => $academie, 'id_cap' => null, 'statut' => 1, 'email' => 'dae'.uniqid().'@test.ml'])->save();

        $this->actingAs($dae)->withSession([]);
        $reponse = $this->get("/territoire/rapports-secondaire/{$rapport->id}/canevas?modele=esg")->assertOk();
        $fichier = tempnam(sys_get_temp_dir(), 'kn').'.xlsx';
        copy($reponse->baseResponse->getFile()->getPathname(), $fichier);
        $this->ouvert = IOFactory::load($fichier);
        unlink($fichier);
        $this->assertSame(7, $this->ouvert->getSheetByName('T1.1')->getCell('C5')->getValue());
        $this->assertStringContainsString('Lycée reçu', $this->ouvert->getSheetByName('T0')->getCell('A9')->getValue());
    }
}
