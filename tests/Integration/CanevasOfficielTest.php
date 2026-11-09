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
        ], ['redaction' => ['difficultes' => 'Manque de tables-bancs.']]);
        $c = $this->canevas('fin', 'esg');
        $v = fn (string $feuille, string $cellule) => $c->getSheetByName($feuille)->getCell($cellule)->getValue();

        $this->assertSame(2, $v('T1', 'F5'), 'Mathématiques, permanents hommes');
        $this->assertSame([1, 1, 2], [$v('T6', 'G11'), $v('T6', 'M11'), $v('T6', 'Q11')], 'passage en 11e S, redoublement, exclusion pour insuffisance');
        $this->assertSame(2, $v('T15', 'F6'));
        $this->assertSame(60, $v('T 18 19', 'B5'));
        // Les perspectives (T19) sont calculées par le canevas à partir des tableaux 6 à 9 : sa formule est gardée.
        $this->assertSame("=+'T6'!N11", $v('T 18 19', 'B22'));
        $this->assertSame(1, $v('T 20', 'D4'));
        $this->assertSame(1, $v('T 21 22', 'C6'));
        $this->assertSame('HAIDARA', $v('T 21 22', 'D15'));
        $this->assertSame('Manque de tables-bancs.', RapportSecondaire::withoutGlobalScopes()->where('id_ecole', 1)->where('type', 'fin')->first()->donnees['redaction']['difficultes']);
    }

    public function test_maquette_du_technique_choisie_par_l_ecole(): void
    {
        $this->enregistrer('rentree', []);
        $this->get(route('rapport-secondaire.canevas', ['type' => 'rentree', 'modele' => 'etp'], false))->assertNotFound();
        $this->get('/rapport-secondaire/rentree')->assertOk()->assertSee('Maquette de votre technique et professionnel')->assertSee('Canevas officiel du secondaire général');

        $this->post(route('rapport-secondaire.maquette', [], false), ['maquette' => 'inconnue'])->assertSessionHasErrors('maquette');
        foreach (['est', 'esp', 'esp_pro', 'agro'] as $maquette) {
            $this->post(route('rapport-secondaire.maquette', [], false), ['maquette' => $maquette])->assertSessionHasNoErrors();
            $this->assertSame($maquette, DB::table('ecole')->where('idEcole', 1)->value('maquette_etp'));
            foreach (['rentree', 'fin'] as $type) {
                $feuille = $this->canevas($type, 'etp')->getSheetByName('NOM ETABLIS');
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

        $fin = collect(\App\Support\RapportSecondaireDonnees::annexes('fin', 1, $this->annee))->firstWhere('titre', 'Propositions de passage, de redoublement et d\'exclusion : 10ème année commune');
        $this->assertSame('Réinscription non encore faite', $fin['lignes'][0][5]);

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
