<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\RapportSuccinct;
use App\Models\User;
use App\Support\RapportSuccinctDonnees as Donnees;
use Illuminate\Support\Facades\DB;

/** Rapport succinct du secondaire : la même fiche que celle du CAP, remise à l'académie. */
class RapportSuccinctSecondaireTest extends IntegrationTestCase
{
    private AnneeScolaire $annee;
    /** @var array<string, Classe> */
    private array $classes = [];
    /** @var list<int> */
    private array $eleves;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = AnneeScolaire::enCours(1);
        $modele = Classe::where('idEcole', 1)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(4)->pluck('id_eleve')->all();
        if (!$modele || count($eleves) < 4) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }
        foreach (['10' => ['10ème année commune', 'secondairegenerale'], 'tse' => ['TSE', 'secondairegenerale'], 'gc' => ['1ère année Génie civil', 'secondairetechniqueetprofessionnel']] as $cle => [$nom, $ordre]) {
            $classe = $modele->replicate();
            $classe->nom_classe = $nom;
            $classe->ordreEnseignement = $ordre;
            $classe->serie = null;
            $classe->save();
            $this->classes[$cle] = $classe;
        }
        $this->eleves = $eleves;
        DB::table('eleve')->where('id_ecole', 1)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        $maj = fn (int $id, string $cle, string $genre) => DB::table('eleve')->where('id_eleve', $id)->update(['id_classe' => $this->classes[$cle]->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => $genre]);
        $maj($eleves[0], '10', 'Féminin');
        $maj($eleves[1], '10', 'Masculin');
        $maj($eleves[2], 'tse', 'Féminin');
        $maj($eleves[3], 'gc', 'Masculin');
        $r = DB::table('reinscription')->insertGetId(['statut' => 'redoublant', 'date_reinscription' => now()->toDateString(), 'enrolement' => 1]);
        DB::table('ligne_reinscription')->insert(['id_eleve' => $eleves[2], 'id_classe' => $this->classes['tse']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'id_reinscription' => $r]);
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

    private function formulaire(array $autres = []): array
    {
        $donnees = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, 'secondaire');
        $groupes = collect($donnees['groupes'])->keyBy('cle')->map(fn ($l) => collect($l)->only(['groupes', 'g', 'f', 'rg', 'rf'])->all())->all();
        $maitres = collect($donnees['maitres'])->map(fn ($m) => ['salles' => 6] + $m)->all();

        return $autres + ['quartier' => 'Badalabougou', 'commune' => 'V', 'cercle' => 'Bamako', 'academie' => 'Rive droite', 'cycle' => 3, 'statut' => 3, 'groupes' => $groupes, 'maitres' => $maitres];
    }

    private function url(string $route = 'rapport-succinct.store'): string
    {
        return route($route, ['annee' => $this->annee->id_anneeScolaire, 'portee' => 'secondaire'], false);
    }

    public function test_series_reconnues(): void
    {
        foreach (['10eme année' => '10CG', '11ème année Sciences' => '11S', '11ème année SES' => '11SES', '11ème L' => '11L', '12ème année TSE' => 'TSE', '12ème année TSS' => 'TSS', 'TSexp' => 'TSEXP', 'Terminale TAL' => 'TAL', 'TSECO 2' => 'TSECO', 'TLL' => 'TLL'] as $nom => $serie) {
            $this->assertSame($serie, Donnees::serie($nom), $nom);
        }
        $this->assertSame('11S', Donnees::serie('11ème A', 'sciences'));
        $this->assertNull(Donnees::serie('12ème année'));
    }

    public function test_fiche_pre_remplie_par_serie_et_filiere(): void
    {
        $d = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, 'secondaire');
        $lignes = collect($d['groupes'])->keyBy('libelle');

        $this->assertSame([1, 1, 1], [$lignes['10ème CG']['g'], $lignes['10ème CG']['f'], $lignes['10ème CG']['groupes']]);
        $this->assertSame([0, 1, 1], [$lignes['TSE']['g'], $lignes['TSE']['f'], $lignes['TSE']['rf']]);
        $this->assertSame(1, $lignes['1ère année Génie civil']['g'], 'filière technique sur sa propre ligne');
        $this->assertSame(3, $d['cycle'], 'général et technique');
        $this->assertSame(4, Donnees::totaux($d)['eleves']);
        $this->assertArrayHasKey('general', $d['maitres']);
        $this->assertArrayHasKey('technique', $d['maitres']);

        // Classe de série inconnue : jamais perdue, elle a sa ligne.
        $inconnue = $this->classes['10']->replicate();
        $inconnue->nom_classe = '12ème année';
        $inconnue->save();
        $this->assertTrue(collect(Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, 'secondaire')['groupes'])->contains('libelle', '12ème année'));
    }

    public function test_academie_sans_compte_pdf_et_remise_sur_papier(): void
    {
        $this->dae(null);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $this->get($this->url('rapport-succinct.index'))->assertOk()->assertSee('Marquer comme remis à l\'académie')->assertSee('Tableau 2')->assertDontSee('CAP de')->assertDontSee('name="enseignement"', false);
        $this->get($this->url('rapport-succinct.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->post($this->url(), $this->formulaire(['action' => 'remettre', 'commune' => '']))->assertSessionHasErrors('rapport');
        $this->post($this->url(), $this->formulaire(['action' => 'remettre']))->assertSessionHas('success');
        $rapport = RapportSuccinct::withoutGlobalScopes()->where('id_ecole', 1)->where('portee', 'secondaire')->firstOrFail();
        $this->assertSame('remis', $rapport->statut);
        $this->assertSame('secondaire', $rapport->donnees_envoyees['portee']);
    }

    public function test_academie_sur_kalannet_envoi_et_cloisonnement(): void
    {
        $academies = DB::table('academie')->limit(2)->pluck('id_academie');
        $dae = $this->dae((int) $academies[0]);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $this->get($this->url('rapport-succinct.index'))->assertSee('Envoyer à l\'académie');
        $this->post($this->url(), $this->formulaire(['action' => 'enregistrer']))->assertSessionHas('success');
        $rapport = RapportSuccinct::withoutGlobalScopes()->where('id_ecole', 1)->where('portee', 'secondaire')->firstOrFail();

        // Brouillon : invisible.
        $this->actingAs($dae)->withSession([])->get('/territoire/rapports-succincts')->assertOk()->assertSee('Non reçu');
        $this->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertNotFound();

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->post($this->url(), $this->formulaire(['action' => 'remettre']))->assertSessionHas('success');
        $this->assertSame('envoye', $rapport->fresh()->statut);
        $this->assertSame((int) $academies[0], (int) $rapport->fresh()->id_academie);
        $this->assertTrue(AppNotification::where('user_id', $dae->idUtilisateur)->where('type', 'rapport_succinct')->exists());

        $this->actingAs($dae)->withSession([])->get('/territoire/rapports-succincts')->assertOk()->assertSee('Reçu');
        $this->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        // Le DCAP ne voit pas la fiche du secondaire ; une autre académie non plus.
        $dcap = $this->compte('Admin', 1)->replicate();
        $dcap->forceFill(['droit' => 'DCAP', 'idEcole' => null, 'id_cap' => Ecole::withoutGlobalScopes()->find(1)->id_cap ?: DB::table('cap')->value('id_cap'), 'statut' => 1, 'email' => 'dcap'.uniqid().'@test.ml'])->save();
        $this->actingAs($dcap)->withSession([])->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertNotFound();
        if (isset($academies[1])) {
            DB::table('utilisateurs')->where('idUtilisateur', $dae->idUtilisateur)->update(['id_academie' => $academies[1]]);
            $this->actingAs($dae->fresh())->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertNotFound();
        }
    }

    public function test_complexe_scolaire_deux_onglets_et_deux_fiches(): void
    {
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Complexe Scolaire']);
        DB::table('classe')->where('idEcole', 1)->whereNotIn('id_classe', collect($this->classes)->pluck('id_classe'))->update(['ordreEnseignement' => 'fondamentale1']);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $this->get('/rapport-succinct')->assertOk()->assertSee('Fondamental (CAP)')->assertSee('Secondaire (Académie)')->assertSee('CAP de');
        $this->get($this->url('rapport-succinct.index'))->assertOk()->assertSee('10ème CG')->assertDontSee('CAP de');

        $this->post($this->url(), $this->formulaire(['action' => 'enregistrer']))->assertSessionHas('success');
        $this->assertSame(['secondaire'], RapportSuccinct::withoutGlobalScopes()->where('id_ecole', 1)->pluck('portee')->all(), 'la fiche du fondamental reste séparée');

        // Complexe dont le secondaire n'a pas encore de classe : l'onglet est là, et la page dit quoi faire.
        DB::table('classe')->where('idEcole', 1)->whereIn('ordreEnseignement', Donnees::SECONDAIRE)->update(['ordreEnseignement' => 'fondamentale1']);
        RapportSuccinct::withoutGlobalScopes()->where('id_ecole', 1)->delete();
        $this->get($this->url('rapport-succinct.index'))->assertOk()->assertSee('Aucune classe du secondaire');
        $this->post($this->url(), ['action' => 'remettre', 'cycle' => 1, 'quartier' => 'x', 'commune' => 'x', 'cercle' => 'x', 'academie' => 'x', 'statut' => 1])->assertSessionHasErrors('rapport');
    }

    public function test_lycee_seul_sans_onglet_et_technique_seul(): void
    {
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Secondaire Generale']);
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => 'secondairetechniqueetprofessionnel']);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $page = $this->get('/rapport-succinct')->assertOk()->assertDontSee('Fondamental (CAP)');
        $this->assertSame('secondaire', $page->viewData('portee'));
        $this->assertSame(2, $page->viewData('donnees')['cycle'], 'technique et professionnel');
    }

    public function test_rappel_par_fiche(): void
    {
        DB::table('abonnements')->where('ecole_id', 1)->update(['statut' => 'actif', 'fin_at' => null]);
        $admin = $this->compte('Admin', 1);
        $recus = fn () => AppNotification::where('user_id', $admin->idUtilisateur)->where('type', 'rapport_succinct_rappel')->where('link', 'like', '%portee=secondaire%');

        $this->artisan('rapports-succincts:rappel')->assertSuccessful();
        $this->assertSame(1, $recus()->count());
        $this->assertStringContainsString('académie', $recus()->first()->message);

        $recus()->delete();
        RapportSuccinct::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => $this->annee->id_anneeScolaire, 'portee' => 'secondaire', 'statut' => 'remis', 'donnees' => [], 'donnees_envoyees' => []]);
        $this->artisan('rapports-succincts:rappel')->assertSuccessful();
        $this->assertSame(0, $recus()->count());
    }
}
