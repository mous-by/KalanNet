<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Classe;
use App\Models\RapportEsg;
use App\Models\User;
use App\Services\Rapports\CanevasEsg;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** Rapports de rentrée et de fin d'année des lycées : canevas officiel rempli, envoi ou remise à l'académie, cloisonnement. */
class RapportsAcademieTest extends IntegrationTestCase
{
    private int $idEcole;
    private AnneeScolaire $annee;
    /** @var array<string, Classe> */
    private array $classes = [];
    /** @var list<int> */
    private array $eleves;
    private ?\PhpOffice\PhpSpreadsheet\Spreadsheet $ouvert = null;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->idEcole = 1;
        $this->annee = AnneeScolaire::enCours(1);
        $modele = Classe::where('idEcole', 1)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(4)->pluck('id_eleve')->all();
        if (!$modele || count($eleves) < 4 || !\App\Support\ExamenNational::estMali(1)) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }
        foreach (['10' => '10ème année commune', '11' => '11ème SES', 'T' => 'TSE'] as $cle => $nom) {
            $classe = $modele->replicate();
            $classe->nom_classe = $nom;
            $classe->ordreEnseignement = 'secondairegenerale';
            $classe->serie = null;
            $classe->save();
            $this->classes[$cle] = $classe;
        }
        $this->eleves = $eleves;
        DB::table('eleve')->where('id_ecole', 1)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        $maj = fn (int $id, string $cle, string $genre, string $naissance) => DB::table('eleve')->where('id_eleve', $id)->update(['id_classe' => $this->classes[$cle]->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => $genre, 'date_naissance' => $naissance]);
        $anneeDebut = (int) substr((string) $this->annee->date_debut, 0, 4);
        $maj($eleves[0], '10', 'Féminin', ($anneeDebut - 15).'-03-12');
        $maj($eleves[1], '10', 'Masculin', ($anneeDebut - 16).'-11-01');
        $maj($eleves[2], '11', 'Masculin', ($anneeDebut - 17).'-06-20');
        $maj($eleves[3], 'T', 'Féminin', ($anneeDebut - 18).'-01-05');
        // Une redoublante de terminale.
        $r = DB::table('reinscription')->insertGetId(['statut' => 'redoublant', 'date_reinscription' => now()->toDateString(), 'enrolement' => 1]);
        DB::table('ligne_reinscription')->insert(['id_eleve' => $eleves[3], 'id_classe' => $this->classes['T']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'id_reinscription' => $r]);
    }

    private function dae(?int $idAcademie): ?User
    {
        DB::table('utilisateurs')->where('droit', 'DAE')->update(['statut' => 0]);
        if (!$idAcademie) {
            return null;
        }
        DB::table('ecole')->where('idEcole', 1)->update(['id_academie' => $idAcademie]);
        $compte = $this->compte('Admin', 1)->replicate();
        $compte->forceFill(['droit' => 'DAE', 'idEcole' => null, 'id_academie' => $idAcademie, 'id_cap' => null, 'statut' => 1, 'email' => 'dae'.uniqid().'@test.ml'])->save();

        return $compte;
    }

    protected function tearDown(): void
    {
        $this->ouvert?->disconnectWorksheets();
        parent::tearDown();
    }

    /** Un seul classeur ouvert à la fois : ils occupent beaucoup de mémoire. */
    private function classeur(string $type): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $this->ouvert?->disconnectWorksheets();
        $reponse = $this->get(route('rapport-academie.canevas', ['type' => $type, 'annee' => $this->annee->id_anneeScolaire], false))->assertOk();
        $fichier = tempnam(sys_get_temp_dir(), 'kn').'.xlsx';
        copy($reponse->baseResponse->getFile()->getPathname(), $fichier);
        $classeur = IOFactory::load($fichier);
        unlink($fichier);
        $this->ouvert = $classeur;

        return $classeur;
    }

    public function test_un_seul_menu_rapport_succinct(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        // École avec fondamental et lycée : la fiche du CAP, et un lien vers les canevas.
        DB::table('classe')->where('idEcole', 1)->where('ordreEnseignement', 'secondairegenerale')->whereNotIn('id_classe', collect($this->classes)->pluck('id_classe'))->update(['ordreEnseignement' => 'fondamentale1']);
        DB::table('classe')->where('idEcole', 1)->whereNotIn('id_classe', collect($this->classes)->pluck('id_classe'))->limit(1)->update(['ordreEnseignement' => 'fondamentale1']);
        $this->get('/rapport-succinct')->assertOk()->assertSee(route('rapport-academie.index'), false);
        $this->get('/rapports-academie')->assertOk()->assertSee(route('rapport-succinct.index'), false)->assertSee('Rapport succinct : secondaire');

        // Lycée seul : le même menu mène aux canevas.
        DB::table('classe')->where('idEcole', 1)->whereIn('ordreEnseignement', \App\Support\Ordres::SOUS_CAP)->update(['ordreEnseignement' => 'secondairegenerale']);
        $this->get('/rapport-succinct')->assertRedirect(route('rapport-academie.index'));
        $this->get('/rapports-academie')->assertOk()->assertDontSee('Voir le rapport succinct du fondamental');
    }

    public function test_secondaire_technique_concerne(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => 'secondairetechniqueetprofessionnel']);
        DB::table('eleve')->whereIn('id_eleve', $this->eleves)->update(['genre_eleve' => 'Féminin']);

        $this->assertTrue(CanevasEsg::ecoleConcernee(1));
        $this->get('/rapport-succinct')->assertRedirect(route('rapport-academie.index'));
        $this->get('/rapports-academie')->assertOk()->assertSee('secondaire général : leurs tableaux')->assertDontSee('Effectifs par âge');

        // Renseignements généraux seulement : pas de zéros dans les tableaux par série, formules intactes.
        foreach (['rentree', 'fin'] as $type) {
            $c = $this->classeur($type);
            $t0 = $c->getSheetByName('T0');
            $this->assertStringContainsString('TECHNIQUE ET PROFESSIONNEL', $t0->getCell('A5')->getValue());
            $this->assertStringStartsWith('Établissement ', $t0->getCell('A9')->getValue());
            $this->assertMatchesRegularExpression('/Filles : 4 /', preg_replace('/\s+/', ' ', collect(range(12, 50))->map(fn ($l) => $t0->getCell('B'.$l)->getValue())->implode('|')));
        }
        $rentree = $this->classeur('rentree');
        $this->assertNull($rentree->getSheetByName('T5')->getCell('B5')->getValue());
        $this->assertNull($rentree->getSheetByName('T2')->getCell('B4')->getValue());
        $this->assertNull($this->classeur('fin')->getSheetByName('T 7 9')->getCell('A28')->getValue());

        // L'académie le voit dans sa liste.
        $dae = $this->dae((int) DB::table('academie')->value('id_academie'));
        $this->actingAs($dae)->withSession(['idEcole' => null]);
        $this->get('/territoire/rapports-academie')->assertOk()->assertSee(DB::table('ecole')->where('idEcole', 1)->value('nomEcole'));
    }

    public function test_classes_a_verifier_signalees_sur_la_page(): void
    {
        $modele = $this->classes['10']->replicate();
        $modele->nom_classe = '12ème année';
        $modele->save();
        $technique = $this->classes['10']->replicate();
        $technique->nom_classe = '1ère année Génie civil';
        $technique->ordreEnseignement = 'secondairetechniqueetprofessionnel';
        $technique->save();

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get('/rapports-academie')->assertOk()->assertSee('12ème année')->assertSee('Série non reconnue')->assertSee('1ère année Génie civil')->assertSee('Classes techniques absentes');
    }

    public function test_series_reconnues(): void
    {
        foreach (['10eme année' => '10CG', '11ème année Sciences' => '11S', '11ème année SES' => '11SES', '11ème L' => '11L', '12ème année TSE' => 'TSE', '12ème année TSS' => 'TSS', 'TSexp' => 'TSEXP', 'Terminale TAL' => 'TAL', 'TSECO 2' => 'TSECO', 'TLL' => 'TLL'] as $nom => $serie) {
            $this->assertSame($serie, CanevasEsg::serie($nom), $nom);
        }
        $this->assertSame('11S', CanevasEsg::serie('11ème A', 'sciences'));
        $this->assertNull(CanevasEsg::serie('12ème année'));
        $this->assertNull(CanevasEsg::serie('7ème année'));
    }

    public function test_canevas_de_rentree_rempli(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $c = $this->classeur('rentree');

        $this->assertStringContainsString($this->annee->annee, $c->getSheetByName('T0')->getCell('A7')->getValue());
        $this->assertStringContainsString('Filles : 2', preg_replace('/\s+/', ' ', $c->getSheetByName('T0')->getCell('B26')->getValue()));

        $t5 = $c->getSheetByName('T5');
        $this->assertSame([2, 1, 1], [$t5->getCell('B5')->getValue(), $t5->getCell('C5')->getValue(), $t5->getCell('E5')->getValue()], '10e');
        $this->assertSame([1, 0, 1], [$t5->getCell('B8')->getValue(), $t5->getCell('C8')->getValue(), $t5->getCell('E8')->getValue()], '11 SES');
        $this->assertSame([1, 1, 0], [$t5->getCell('B15')->getValue(), $t5->getCell('C15')->getValue(), $t5->getCell('E15')->getValue()], 'TSE');

        // Redoublante de TSE : pas passante.
        $t6 = $c->getSheetByName('T6');
        $this->assertSame(0, $t6->getCell('B16')->getValue());
        $this->assertSame(1, $t6->getCell('F16')->getValue());
        $this->assertSame([1, 1], [$t6->getCell('J6')->getValue(), $t6->getCell('L6')->getValue()]);

        // Âges au 31 décembre de la rentrée : 15 ans en 10e, 16 ans.
        $t7 = $c->getSheetByName('T7');
        $this->assertSame(1, $t7->getCell('B8')->getValue());
        $this->assertSame(1, $t7->getCell('C9')->getValue());

        // Scolarité : la redoublante de TSE a 4 ans de lycée.
        $t9 = $c->getSheetByName('T9');
        $this->assertSame(1, $t9->getCell('J27')->getValue());
        $this->assertSame(0, $t9->getCell('J26')->getValue());
        $this->assertSame(1, $c->getSheetByName('T2')->getCell('J4')->getValue());

        // Les formules et vérifications du canevas restent en place.
        $this->assertStringStartsWith('=IF(AND(C5+E5=B5', $t5->getCell('H5')->getValue());
    }

    public function test_canevas_de_fin_d_annee_rempli(): void
    {
        DB::table('resultats_def_terminal')->insert(['id_eleve' => $this->eleves[3], 'id_annee' => $this->annee->id_anneeScolaire, 'id_classe' => $this->classes['T']->id_classe, 'niveau_examen' => 'BAC', 'decision' => 'admis', 'date_resultat' => now()->toDateString()]);
        DB::table('eleve')->where('id_eleve', $this->eleves[1])->update(['etat_dossier' => 2]);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $c = $this->classeur('fin');

        $t6 = $c->getSheetByName('T6');
        $this->assertSame([1, 1], [$t6->getCell('A11')->getValue(), $t6->getCell('B11')->getValue()], 'effectif 10e, abandon compris');
        $this->assertSame([0, 1], [$t6->getCell('V11')->getValue(), $t6->getCell('W11')->getValue()], 'un abandon');
        $this->assertSame(1, $c->getSheetByName('T 7 9')->getCell('B28')->getValue(), '11 SES');

        $t11 = $c->getSheetByName('T11');
        $this->assertSame([1, 0, 1, 0], [$t11->getCell('B15')->getValue(), $t11->getCell('C15')->getValue(), $t11->getCell('E15')->getValue(), $t11->getCell('F15')->getValue()], 'TSE');
        // Admise après un redoublement : 4 ans.
        $this->assertSame(1, $c->getSheetByName('T 13 14')->getCell('E11')->getValue());
        $this->assertSame(0, $c->getSheetByName('T 13 14')->getCell('B11')->getValue());
    }

    public function test_academie_sans_compte_remise_directe(): void
    {
        $this->dae(null);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get('/rapports-academie')->assertOk()->assertSee('Marquer comme remis')->assertSee('Rapport de fin');

        $this->post('/rapports-academie/rentree', ['annee' => $this->annee->id_anneeScolaire])->assertSessionHasNoErrors();
        $this->assertSame('remis', RapportEsg::withoutGlobalScopes()->where('id_ecole', 1)->where('type', 'rentree')->value('statut'));
    }

    public function test_academie_sur_kalannet_envoi_et_cloisonnement(): void
    {
        $academies = DB::table('academie')->limit(2)->pluck('id_academie');
        $dae = $this->dae((int) $academies[0]);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get('/rapports-academie')->assertOk()->assertSee('Envoyer à l\'académie');

        $url = route('rapport-academie.remettre', ['type' => 'rentree', 'annee' => $this->annee->id_anneeScolaire], false);
        $this->post($url, [])->assertSessionHasErrors('fichier');
        // Pas un classeur : refusé, même renommé.
        $faux = tempnam(sys_get_temp_dir(), 'kn');
        file_put_contents($faux, '<?php echo 1;');
        $this->post($url, ['fichier' => new UploadedFile($faux, 'rapport.php', null, null, true)])->assertSessionHasErrors('fichier');
        $this->assertSame(0, RapportEsg::withoutGlobalScopes()->where('id_ecole', 1)->count());

        $vrai = tempnam(sys_get_temp_dir(), 'kn').'.xlsx';
        copy(resource_path('canevas/rapport_rentree_esg.xlsx'), $vrai);
        $this->post($url, ['fichier' => new UploadedFile($vrai, 'rapport rentrée.xlsx', null, null, true)])->assertSessionHasNoErrors();
        $rapport = RapportEsg::withoutGlobalScopes()->where('id_ecole', 1)->firstOrFail();
        $this->assertSame('envoye', $rapport->statut);
        Storage::disk('local')->assertExists($rapport->fichier);
        $this->assertStringEndsWith('.xlsx', $rapport->fichier);
        $this->assertSame(1, AppNotification::where('user_id', $dae->idUtilisateur)->where('type', 'rapport_academie')->count());
        $this->get(route('rapport-academie.fichier', $rapport->id, false))->assertOk();

        // L'académie voit et télécharge.
        $this->actingAs($dae)->withSession(['idEcole' => null]);
        $this->get('/territoire/rapports-academie?annee='.$this->annee->id_anneeScolaire)->assertOk()->assertSee('Reçu');
        $this->get("/territoire/rapports-academie/{$rapport->id}/fichier")->assertOk();

        // Une autre académie, un parent : non.
        if (isset($academies[1])) {
            $autre = $this->compte('Admin', 1)->replicate();
            $autre->forceFill(['droit' => 'DAE', 'idEcole' => null, 'id_academie' => $academies[1], 'id_cap' => null, 'statut' => 1, 'email' => 'dae2'.uniqid().'@test.ml'])->save();
            $this->actingAs($autre)->withSession(['idEcole' => null]);
            $this->get("/territoire/rapports-academie/{$rapport->id}/fichier")->assertNotFound();
        }
        $parent = $this->compte('parent', 1);
        $this->actingAs($parent)->withSession(['idEcole' => 1]);
        $this->get('/rapports-academie')->assertForbidden();
        $this->get(route('rapport-academie.fichier', $rapport->id, false))->assertForbidden();
        $this->get('/territoire/rapports-academie')->assertForbidden();
    }
}
