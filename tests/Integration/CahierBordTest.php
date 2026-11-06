<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\CahierBord;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\User;
use App\Support\CahierBordDonnees as Donnees;
use Illuminate\Support\Facades\DB;

/** Cahier de bord : une fiche par cycle, pré-remplie, reprise d'une année sur l'autre, remise au CAP, cloisonnement. */
class CahierBordTest extends IntegrationTestCase
{
    private AnneeScolaire $annee;
    /** @var array<string, Classe> */
    private array $classes = [];
    /** @var list<int> */
    private array $eleves;
    private int $enseignant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = AnneeScolaire::enCours(1);
        $modele = Classe::where('idEcole', 1)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(4)->pluck('id_eleve')->all();
        $enseignant = DB::table('enseignants')->where('id_ecole', 1)->value('id_enseignant');
        if (!$modele || count($eleves) < 4 || !$enseignant) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => 'fondamentale2']);
        foreach (['1' => ['1ère année A', 'fondamentale1'], '3' => ['3ème année', 'fondamentale1'], 'ps' => ['Petite section', 'jardin']] as $cle => [$nom, $ordre]) {
            $classe = $modele->replicate();
            $classe->nom_classe = $nom;
            $classe->ordreEnseignement = $ordre;
            $classe->save();
            $this->classes[$cle] = $classe;
        }
        $this->eleves = $eleves;
        $this->enseignant = (int) $enseignant;
        DB::table('eleve')->where('id_ecole', 1)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        $maj = fn (int $id, string $cle, string $genre) => DB::table('eleve')->where('id_eleve', $id)->update(['id_classe' => $this->classes[$cle]->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => $genre]);
        $maj($eleves[0], '1', 'Féminin');
        $maj($eleves[1], '1', 'Masculin');
        $maj($eleves[2], '3', 'Féminin');
        $maj($eleves[3], 'ps', 'Masculin');
        $r = DB::table('reinscription')->insertGetId(['statut' => 'redoublant', 'date_reinscription' => now()->toDateString(), 'enrolement' => 1]);
        DB::table('ligne_reinscription')->insert(['id_eleve' => $eleves[2], 'id_classe' => $this->classes['3']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'id_reinscription' => $r]);
        DB::table('enseignants')->where('id_enseignant', $enseignant)->update(['is_deleted' => 0, 'genre_enseignant' => 'Feminin', 'nom_prenom_enseignant' => 'Awa DIARRA']);
        DB::table('classe_maitres')->insert(['id_classe' => $this->classes['3']->id_classe, 'id_enseignant' => $enseignant]);
    }

    private function dcap(?int $idCap): ?User
    {
        DB::table('utilisateurs')->where('droit', 'DCAP')->update(['statut' => 0]);
        if (!$idCap) {
            return null;
        }
        DB::table('ecole')->where('idEcole', 1)->update(['id_cap' => $idCap]);
        $compte = $this->compte('Admin', 1)->replicate();
        $compte->forceFill(['droit' => 'DCAP', 'idEcole' => null, 'id_cap' => $idCap, 'statut' => 1, 'email' => 'dcap'.uniqid().'@test.ml'])->save();

        return $compte;
    }

    private function formulaire(string $cycle, array $autres = []): array
    {
        $d = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, $cycle, 'M. Traoré');
        $effectifs = collect($d['effectifs'])->keyBy('cle')->map(fn ($l) => collect($l)->only(['groupes', 'g', 'f', 'rg', 'rf'])->all())->all();
        $enseignants = collect($d['enseignants'])->mapWithKeys(fn ($e) => [$e['id'] => ['echelon' => '2-4', 'recrutement' => '2015', 'arrivee_ecole' => '2020']])->all();

        return $autres + ['directeur' => 'M. Traoré', 'commune' => 'Kayes', 'regime' => 1, 'cap' => 'Kayes RG', 'academie' => 'Kayes', 'effectifs' => $effectifs,
            'enseignants_json' => json_encode($enseignants), 'salles' => ['dur' => 6, 'banco' => 1], 'date_creation' => '1998',
            'tables' => ['3' => ['p2_bon' => 20]], 'equipements' => ['bureaux' => 6]];
    }

    private function url(string $cycle, string $route = 'cahier-bord.store'): string
    {
        return route($route, ['annee' => $this->annee->id_anneeScolaire, 'cycle' => $cycle], false);
    }

    public function test_une_fiche_par_cycle_et_chiffres_repris(): void
    {
        $this->assertSame(['jardin', 'premier', 'second'], Donnees::cycles(1));
        $d = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, 'premier', 'M. Traoré');
        $lignes = collect($d['effectifs'])->keyBy('cle');

        $this->assertSame(['1ère A', '2è A', '3è A', '4è A', '5è A', '6è A'], $lignes->pluck('libelle')->all());
        $this->assertSame([1, 1, 1], [$lignes['1']['g'], $lignes['1']['f'], $lignes['1']['groupes']]);
        $this->assertSame([0, 1, 1], [$lignes['3']['g'], $lignes['3']['f'], $lignes['3']['rf']]);
        $enseignant = collect($d['enseignants'])->firstWhere('id', $this->enseignant);
        $this->assertTrue($enseignant['femme']);
        $this->assertSame('3ème année', $enseignant['classes']);
        $this->assertSame('M. Traoré', $d['administratifs'][0]['nom']);

        $jardin = collect(Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, 'jardin', 'x')['effectifs'])->keyBy('cle');
        $this->assertSame(1, $jardin['ps']['g']);
        $this->assertSame('ms', Donnees::niveauDe('jardin', 'Moyenne section B'));
        $this->assertNull(Donnees::niveauDe('jardin', 'Garderie'));
    }

    public function test_cap_sans_compte_pdf_et_remise_sur_papier(): void
    {
        $this->dcap(null);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $this->get($this->url('premier', 'cahier-bord.index'))->assertOk()->assertSee('Marquer comme remis au CAP')->assertSee("Jardin d'enfants")->assertSee('2e cycle')->assertSee('Awa DIARRA');
        $this->get($this->url('premier', 'cahier-bord.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->post($this->url('premier'), $this->formulaire('premier', ['action' => 'remettre', 'commune' => '']))->assertSessionHasErrors('cahier');
        $this->post($this->url('premier'), $this->formulaire('premier', ['action' => 'remettre']))->assertSessionHas('success');
        $fiche = CahierBord::withoutGlobalScopes()->where('id_ecole', 1)->where('cycle', 'premier')->firstOrFail();
        $this->assertSame('remis', $fiche->statut);
        $this->assertSame(6, $fiche->donnees_envoyees['salles']['dur']);
        $this->assertSame('2-4', collect($fiche->donnees_envoyees['enseignants'])->firstWhere('id', $this->enseignant)['echelon']);
        $this->assertSame(1, CahierBord::withoutGlobalScopes()->where('id_ecole', 1)->count(), 'les autres cycles restent séparés');

        // L'identité de l'enseignant ne se trafique pas depuis le formulaire.
        $faux = $this->formulaire('premier', ['action' => 'enregistrer', 'enseignants_json' => json_encode([$this->enseignant => ['nom' => 'Falsifié', 'echelon' => '3']])]);
        $this->post($this->url('premier'), $faux)->assertSessionHas('success');
        $this->assertSame('Awa DIARRA', collect($fiche->fresh()->donnees['enseignants'])->firstWhere('id', $this->enseignant)['nom']);
    }

    public function test_repris_de_l_annee_precedente(): void
    {
        $debut = (int) substr((string) $this->annee->date_debut, 0, 4) - 1;
        $avant = DB::table('anneescolaire')->insertGetId(['annee' => $debut.'-'.($debut + 1), 'date_debut' => $debut.'-10-01', 'date_fin' => ($debut + 1).'-07-31', 'id_ecole' => 1]);
        CahierBord::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => $avant, 'cycle' => 'premier', 'statut' => 'remis', 'donnees' => [
            'commune' => 'Kayes N\'Di', 'regime' => 2, 'salles' => ['dur' => 9], 'date_creation' => '1987', 'tables' => ['1' => ['p2_bon' => 12]],
            'enseignants' => [['id' => $this->enseignant, 'echelon' => 'MSC 3', 'recrutement' => '2010', 'classes' => 'ancienne']],
        ]]);

        $d = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, 'premier', 'x');
        $this->assertSame(['Kayes N\'Di', 2, 9, '1987', 12], [$d['commune'], $d['regime'], $d['salles']['dur'], $d['date_creation'], $d['tables']['1']['p2_bon']]);
        $enseignant = collect($d['enseignants'])->firstWhere('id', $this->enseignant);
        $this->assertSame(['MSC 3', '2010'], [$enseignant['echelon'], $enseignant['recrutement']]);
        $this->assertSame('3ème année', $enseignant['classes'], 'les classes tenues sont celles de cette année');
        $this->assertSame(1, collect($d['effectifs'])->firstWhere('cle', '1')['g'], 'les effectifs sont toujours ceux de KalanNet');
    }

    public function test_cap_sur_kalannet_envoi_et_cloisonnement(): void
    {
        $idCap = (int) DB::table('cap')->value('id_cap');
        $dcap = $this->dcap($idCap);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $this->post($this->url('jardin'), $this->formulaire('jardin', ['action' => 'enregistrer']))->assertSessionHas('success');
        $fiche = CahierBord::withoutGlobalScopes()->where('id_ecole', 1)->where('cycle', 'jardin')->firstOrFail();
        $this->actingAs($dcap)->withSession([])->get('/territoire/cahiers-bord')->assertOk()->assertSee('Non reçu');
        $this->get("/territoire/cahiers-bord/{$fiche->id}/pdf")->assertNotFound();

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get($this->url('jardin', 'cahier-bord.index'))->assertSee('Envoyer au CAP')->assertSee('Section des Petits');
        $this->post($this->url('jardin'), $this->formulaire('jardin', ['action' => 'remettre']))->assertSessionHas('success');
        $this->assertSame('envoye', $fiche->fresh()->statut);
        $this->assertTrue(AppNotification::where('user_id', $dcap->idUtilisateur)->where('type', 'cahier_bord')->exists());

        $this->actingAs($dcap)->withSession([])->get('/territoire/cahiers-bord')->assertOk()->assertSee('Reçu')->assertSee("Jardin d'enfants");
        $this->get("/territoire/cahiers-bord/{$fiche->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        // Autre CAP, académie, parent : non.
        $autreCap = (int) DB::table('cap')->where('id_cap', '!=', $idCap)->value('id_cap');
        if ($autreCap) {
            DB::table('utilisateurs')->where('idUtilisateur', $dcap->idUtilisateur)->update(['id_cap' => $autreCap]);
            $this->actingAs($dcap->fresh())->get("/territoire/cahiers-bord/{$fiche->id}/pdf")->assertNotFound();
        }
        // L'académie suit le secondaire, jamais les fiches remises au CAP.
        $dae = $this->compte('Admin', 1)->replicate();
        $dae->forceFill(['droit' => 'DAE', 'idEcole' => null, 'id_academie' => DB::table('academie')->value('id_academie'), 'statut' => 1, 'email' => 'dae'.uniqid().'@test.ml'])->save();
        $this->actingAs($dae)->withSession([])->get('/territoire/cahiers-bord')->assertOk()->assertDontSee("Jardin d'enfants");
        $this->get("/territoire/cahiers-bord/{$fiche->id}/pdf")->assertNotFound();
        $this->actingAs($this->compte('parent', 1))->withSession(['idEcole' => 1])->get('/cahier-bord')->assertForbidden();
    }

    public function test_classes_non_reconnues_signalees(): void
    {
        $garderie = $this->classes['ps']->replicate();
        $garderie->nom_classe = 'Garderie';
        $garderie->save();
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get($this->url('jardin', 'cahier-bord.index'))->assertOk()->assertSee('Garderie');
    }

    public function test_secondaire_remis_a_l_academie(): void
    {
        $lycee = $this->classes['1']->replicate();
        $lycee->nom_classe = 'TSE';
        $lycee->ordreEnseignement = 'secondairegenerale';
        $lycee->save();
        $technique = $this->classes['1']->replicate();
        $technique->nom_classe = '1ère année Génie civil';
        $technique->ordreEnseignement = 'secondairetechniqueetprofessionnel';
        $technique->save();
        DB::table('eleve')->where('id_eleve', $this->eleves[0])->update(['id_classe' => $lycee->id_classe]);
        DB::table('classe_maitres')->insert(['id_classe' => $lycee->id_classe, 'id_enseignant' => $this->enseignant]);

        $this->assertContains('secondaire', Donnees::cycles(1));
        $d = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $this->annee->id_anneeScolaire, 'secondaire', 'M. Coulibaly');
        $lignes = collect($d['effectifs'])->keyBy('libelle');
        $this->assertSame(1, $lignes['TSE']['f']);
        $this->assertArrayHasKey('1ère année Génie civil', $lignes->all());
        $this->assertSame('Proviseur', $d['administratifs'][0]['fonction']);
        $this->assertCount(8, $d['administratifs']);
        $this->assertSame('Professeur', collect($d['enseignants'])->firstWhere('id', $this->enseignant)['fonction']);

        $academie = (int) DB::table('academie')->value('id_academie');
        DB::table('utilisateurs')->where('droit', 'DAE')->update(['statut' => 0]);
        DB::table('ecole')->where('idEcole', 1)->update(['id_academie' => $academie]);
        $dae = $this->compte('Admin', 1)->replicate();
        $dae->forceFill(['droit' => 'DAE', 'idEcole' => null, 'id_academie' => $academie, 'id_cap' => null, 'statut' => 1, 'email' => 'dae'.uniqid().'@test.ml'])->save();

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get($this->url('secondaire', 'cahier-bord.index'))->assertOk()->assertSee('Envoyer à l\'académie')->assertDontSee('CAP de')->assertSee('Classe / série');
        $this->post($this->url('secondaire'), $this->formulaire('secondaire', ['action' => 'remettre']))->assertSessionHas('success');
        $fiche = CahierBord::withoutGlobalScopes()->where('id_ecole', 1)->where('cycle', 'secondaire')->firstOrFail();
        $this->assertSame('envoye', $fiche->statut);
        $this->assertSame($academie, (int) $fiche->id_academie);
        $this->assertTrue(AppNotification::where('user_id', $dae->idUtilisateur)->where('type', 'cahier_bord')->exists());
        $this->get($this->url('secondaire', 'cahier-bord.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($dae)->withSession([])->get('/territoire/cahiers-bord')->assertOk()->assertSee('Secondaire')->assertSee('Reçu');
        $this->get("/territoire/cahiers-bord/{$fiche->id}/pdf")->assertOk();

        // Le DCAP ne voit pas le cahier du secondaire.
        $dcap = $this->dcap((int) DB::table('cap')->value('id_cap'));
        $this->actingAs($dcap)->withSession([])->get("/territoire/cahiers-bord/{$fiche->id}/pdf")->assertNotFound();
    }

    public function test_tous_sauf_les_ecoles_de_sante_franco_arabes_et_privees_comprises(): void
    {
        // Franco-arabe (médersa), privée : concernée comme les autres.
        DB::table('ecole')->where('idEcole', 1)->update(['franco_arabe' => 1, 'statut' => 'prive', 'typeEcole' => 'Fondamentale I']);
        $this->assertSame(['jardin', 'premier', 'second'], Donnees::cycles(1));
        $this->assertNotSame([], \App\Support\RapportSuccinctDonnees::portees(1));
        $this->assertTrue(\App\Support\PropositionPassageDonnees::ecoleConcernee(1));
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get('/cahier-bord')->assertOk();
        $this->get('/rapport-succinct')->assertOk();
        $this->get('/proposition-passage')->assertOk();

        // École de santé, publique ou privée : aucun de ces modules.
        foreach (['public', 'prive'] as $statut) {
            DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'École de Santé', 'statut' => $statut, 'franco_arabe' => 0]);
            $this->assertSame([], Donnees::cycles(1));
            $this->assertSame([], \App\Support\RapportSuccinctDonnees::portees(1));
            $this->assertFalse(\App\Support\PropositionPassageDonnees::ecoleConcernee(1));
            $this->get('/cahier-bord')->assertForbidden();
            $this->get('/rapport-succinct')->assertForbidden();
            $this->get('/proposition-passage')->assertForbidden();
        }
    }
}
