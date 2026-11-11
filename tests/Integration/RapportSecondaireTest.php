<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\RapportSecondaire;
use App\Models\User;
use App\Support\RapportSecondaireDonnees as Donnees;
use Illuminate\Support\Facades\DB;

/** Rapports de rentrée et de fin d'année du secondaire : pré-remplissage, reprise, saisie contrôlée, remise à l'académie. */
class RapportSecondaireTest extends IntegrationTestCase
{
    private AnneeScolaire $annee;
    /** @var array<string, Classe> */
    private array $classes = [];
    /** @var list<int> */
    private array $eleves;

    protected function setUp(): void
    {
        parent::setUp();
        // L'école 1 de la base locale peut déjà avoir de vraies fiches : le test part d'une école vierge (transaction annulée à la fin).
        foreach (['rapports_succincts', 'cahiers_bord', 'propositions_passage', 'rapports_secondaire'] as $table) {
            \Illuminate\Support\Facades\DB::table($table)->where('id_ecole', 1)->delete();
        }
        \Illuminate\Support\Facades\DB::table('app_notifications')->whereIn('type', ['rapport_succinct', 'rapport_succinct_rappel', 'cahier_bord', 'proposition_passage', 'proposition_passage_decision', 'rapport_secondaire'])->delete();
        $this->annee = AnneeScolaire::enCours(1);
        $modele = Classe::where('idEcole', 1)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(5)->pluck('id_eleve')->all();
        if (!$modele || count($eleves) < 5) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Secondaire Generale']);
        // Les classes existantes sortent du calcul : seules celles du test comptent.
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => null]);
        foreach (['10' => ['10ème année commune', 'secondairegenerale'], 'ses' => ['11ème SES', 'secondairegenerale'], 'tse' => ['TSE', 'secondairegenerale'], 'gc' => ['1ère année Génie civil', 'secondairetechniqueetprofessionnel']] as $cle => [$nom, $ordre]) {
            $classe = $modele->replicate();
            $classe->nom_classe = $nom;
            $classe->ordreEnseignement = $ordre;
            $classe->serie = null;
            $classe->save();
            $this->classes[$cle] = $classe;
        }
        $this->eleves = $eleves;
        $debut = (int) substr((string) $this->annee->date_debut, 0, 4);
        DB::table('eleve')->where('id_ecole', 1)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        $maj = fn (int $i, string $cle, string $genre, int $age) => DB::table('eleve')->where('id_eleve', $eleves[$i])->update(['id_classe' => $this->classes[$cle]->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => $genre, 'date_naissance' => ($debut - $age).'-02-10']);
        $maj(0, '10', 'Féminin', 15);
        $maj(1, '10', 'Masculin', 16);
        $maj(2, 'ses', 'Masculin', 17);
        $maj(3, 'tse', 'Féminin', 19);
        $maj(4, 'gc', 'Masculin', 18);
        // Redoublante de TSE, admise au Bac de la session.
        $r = DB::table('reinscription')->insertGetId(['statut' => 'redoublant', 'date_reinscription' => now()->toDateString(), 'enrolement' => 1]);
        DB::table('ligne_reinscription')->insert(['id_eleve' => $eleves[3], 'id_classe' => $this->classes['tse']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'id_reinscription' => $r]);
        DB::table('resultats_def_terminal')->insert(['id_eleve' => $eleves[3], 'id_annee' => $this->annee->id_anneeScolaire, 'id_classe' => $this->classes['tse']->id_classe, 'niveau_examen' => 'BAC', 'decision' => 'admis', 'date_resultat' => now()->toDateString()]);
    }

    private function dae(): User
    {
        $academie = (int) DB::table('academie')->value('id_academie');
        DB::table('utilisateurs')->where('droit', 'DAE')->update(['statut' => 0]);
        DB::table('ecole')->where('idEcole', 1)->update(['id_academie' => $academie]);
        $compte = $this->compte('Admin', 1)->replicate();
        $compte->forceFill(['droit' => 'DAE', 'idEcole' => null, 'id_academie' => $academie, 'id_cap' => null, 'statut' => 1, 'email' => 'dae'.uniqid().'@test.ml'])->save();

        return $compte;
    }

    private function url(string $type, string $route = 'rapport-secondaire.store'): string
    {
        return route($route, ['type' => $type, 'annee' => $this->annee->id_anneeScolaire], false);
    }

    private function envoi(string $type, array $tableaux, array $autres = []): array
    {
        return $autres + ['infos' => ['region' => 'Ségou', 'cercle' => 'Markala', 'commune' => 'Sansanding', 'date_creation' => '2012'], 'tableaux_json' => json_encode($tableaux)];
    }

    public function test_rentree_pre_remplie(): void
    {
        // Scolarité depuis le DEF : CG de l'année = 1 an, CG de l'année d'avant = 2 ans.
        $debut = (int) substr((string) $this->annee->date_debut, 0, 4);
        DB::table('eleve')->where('id_eleve', $this->eleves[0])->update(['annee_def' => $debut]);
        DB::table('eleve')->where('id_eleve', $this->eleves[1])->update(['annee_def' => $debut - 1]);
        // Bac de la session écoulée : une admise de TSE (CG trois ans plus tôt) et un ajourné.
        $precedente = DB::table('anneescolaire')->where('date_debut', '<', $this->annee->date_debut)->orderByDesc('date_debut')->first();
        if ($precedente) {
            DB::table('eleve')->where('id_eleve', $this->eleves[2])->update(['annee_def' => (int) substr((string) $precedente->date_debut, 0, 4) - 2]);
            foreach ([[$this->eleves[2], 'admis'], [$this->eleves[4], 'échec']] as [$eleve, $decision]) {
                DB::table('resultats_def_terminal')->insert(['id_eleve' => $eleve, 'id_annee' => $precedente->id_anneeScolaire, 'id_classe' => $this->classes['tse']->id_classe, 'niveau_examen' => 'BAC', 'decision' => $decision]);
            }
        }
        $d = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), $this->annee, 'rentree');
        $t = $d['t'];

        $this->assertSame(['f' => 1, 'g' => 1], $t['T5']['10CG']);
        $this->assertSame(1, $t['T5']['c'.$this->classes['gc']->id_classe]['g'], 'filière technique sur sa ligne');
        $this->assertSame(1, $t['T6']['TSE']['r_f'], 'redoublante');
        $this->assertSame(1, $t['T7']['a15']['10CG_f']);
        $this->assertSame(1, $t['T9']['a4']['TSE_f'], '3 ans attendus + 1 redoublement');
        $this->assertSame(1, $t['T9']['a1']['10CG_f'], 'CG de l\'année : 1 an');
        $this->assertSame(1, $t['T9']['a2']['10CG_g'], 'CG de l\'année d\'avant : 2 ans');
        if ($precedente) {
            $this->assertSame(['inscrits_g' => 2, 'admis_g' => 1], $t['T10']['TSE'], 'inscrits et admis de la session écoulée');
            $this->assertSame(['a3_g' => 1], $t['T11']['TSE'], 'admis par scolarité depuis le DEF');
        }
        $this->assertSame(1, $t['T2']['classes']['TSE']);
        $this->assertSame([2, 3], [$d['infos']['effectif_f'], $d['infos']['effectif_g']]);

        $tableaux = Donnees::tableaux('rentree', 1);
        $this->assertSame(['T1.1', 'T1.2', ...array_map(fn ($n) => 'T'.$n, range(2, 31))], array_values(array_unique(array_column($tableaux, 'code'))), 'les 32 tableaux du canevas officiel de rentrée');
        $numeros = array_map(fn ($t) => $t['numero'] ?? $t['code'], $tableaux);
        foreach (['T7 suite', 'T8 suite', 'T9 suite'] as $suite) {
            $this->assertContains($suite, $numeros, 'terminales séparées des classes de passage, comme le canevas');
        }

        $lignes = Donnees::lignes(1);
        $this->assertSame(['TSE' => 'TSE'], $lignes['terminales']);
    }

    public function test_fin_d_annee_pre_remplie_et_reprise_de_la_rentree(): void
    {
        DB::table('eleve')->where('id_eleve', $this->eleves[1])->update(['etat_dossier' => 2]);
        RapportSecondaire::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => $this->annee->id_anneeScolaire, 'type' => 'rentree', 'statut' => 'remis', 'donnees' => [
            'infos' => ['region' => 'Ségou', 'arrete_creation' => 'Décision 0992'], 't' => ['T12' => ['l0' => ['perm_h' => 3]], 'T24' => ['l0' => ['nom' => 'TRAORE']]],
        ]]);

        $d = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), $this->annee, 'fin');
        $this->assertSame(['effectif_f' => 1, 'effectif_g' => 1, 'abandon_g' => 1], $d['t']['T6']['10CG']);
        $this->assertSame(1, $d['t']['T7'] ?? null ? 0 : 1, 'pas de 11ème L : pas de tableau T7');
        $this->assertSame(1, $d['t']['T9']['11SES']['effectif_g']);
        $this->assertSame(['effectif_f' => 1, 'admis_f' => 1], $d['t']['T11']['TSE']);
        $this->assertSame(1, $d['t']['T13']['TSE']['a4_f']);
        $this->assertSame(3, $d['t']['T1']['l0']['perm_h'], 'enseignants repris du rapport de rentrée');
        $this->assertSame('TRAORE', $d['t']['T22']['l0']['nom']);
        $this->assertSame(['Ségou', 'Décision 0992'], [$d['infos']['region'], $d['infos']['arrete_creation']]);
        $this->assertNotContains('T7', array_column(Donnees::tableaux('fin', 1), 'code'));
        $this->assertContains('T9b', array_column(Donnees::tableaux('fin', 1), 'code'));
    }

    public function test_tableaux_ajoutes_pre_remplis_et_completes_dans_un_rapport_deja_enregistre(): void
    {
        $enseignant = (int) DB::table('enseignants')->where('id_ecole', 1)->value('id_enseignant');
        if (!$enseignant) {
            $this->markTestSkipped('Aucun enseignant dans l\'école 1.');
        }
        DB::table('enseignants')->where('id_enseignant', $enseignant)->update(['nom_prenom_enseignant' => 'Awa KONE', 'genre_enseignant' => 'Féminin', 'is_deleted' => 0, 'matricule' => 'M-77', 'specialite' => 'Mathématiques']);
        DB::table('ligneclasse')->insert(['id_classe' => $this->classes['10']->id_classe, 'id_matiere' => DB::table('matiere')->value('id_matiere'), 'id_enseignants' => $enseignant, 'coefficient' => 1]);
        $rentree = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), $this->annee, 'rentree')['t'];
        $ligne = collect($rentree['T21'])->firstWhere('matricule', 'M-77');
        $this->assertSame(['Awa', 'KONE', 'F', 'Mathématiques'], [$ligne['prenoms'], $ligne['nom'], $ligne['sexe'], $ligne['specialite']]);

        // Rapport enregistré avant ces tableaux : la liste des enseignants est complétée à l'ouverture, la saisie est gardée.
        RapportSecondaire::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => $this->annee->id_anneeScolaire, 'type' => 'rentree', 'statut' => 'brouillon',
            'donnees' => ['infos' => ['region' => 'Ségou'], 't' => ['T5' => ['10CG' => ['f' => 9]]]]]);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get($this->url('rentree', 'rapport-secondaire.index'))->assertOk()->assertSee('T31 : Interventions des partenaires')->assertSee('value="M-77"', false)->assertSee('value="9"', false);

        // Fin d'année : récapitulatifs et admis au Bac.
        $fin = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), $this->annee, 'fin')['t'];
        $this->assertSame(['effectif' => 2], array_intersect_key($fin['T14']['n10'], ['effectif' => 1]));
        $this->assertSame(1, $fin['T14']['n12']['effectif']);
        $this->assertSame(['prenoms', 'nom', 'serie'], array_keys(array_intersect_key($fin['T23']['n1'], array_flip(['prenoms', 'nom', 'serie']))));
        $this->assertSame('TSE', $fin['T23']['n1']['serie']);
        $codes = array_column(Donnees::tableaux('fin', 1, $this->annee), 'code');
        foreach (['T14', 'T16', 'T17', 'T23'] as $code) {
            $this->assertContains($code, $codes);
        }

        // PDF : seules les lignes remplies d'une liste sont imprimées.
        $this->post($this->url('rentree'), $this->envoi('rentree', ['T31' => ['n4' => ['partenaire' => 'UNICEF']]], ['action' => 'enregistrer']))->assertSessionHasNoErrors();
        $this->get(route('rapport-secondaire.pdf', ['type' => 'rentree', 'annee' => $this->annee->id_anneeScolaire], false))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_annee_du_def_saisie_par_classe(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $debut = (int) substr((string) $this->annee->date_debut, 0, 4);
        $this->get(route('rapport-secondaire.def', ['annee' => $this->annee->id_anneeScolaire], false))->assertOk()
            ->assertSee('Année du DEF des élèves')->assertSee('10ème année commune')->assertDontSee('1ère année Génie civil')
            ->assertSee('Remplir toutes les cases vides')->assertSee('data-propose="'.$debut.'"', false)
            ->assertSee('data-propose="'.($debut - 3).'"', false);
        $this->post(route('rapport-secondaire.def.store', [], false), ['annee' => $this->annee->id_anneeScolaire, 'def' => [$this->eleves[0] => $debut, $this->eleves[1] => '']])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame($debut, (int) DB::table('eleve')->where('id_eleve', $this->eleves[0])->value('annee_def'));
        // Envoi de l'écran : un seul champ JSON.
        $this->post(route('rapport-secondaire.def.store', [], false), ['annee' => $this->annee->id_anneeScolaire, 'def_json' => json_encode([$this->eleves[1] => (string) ($debut - 1)])])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 élève(s) ont leur année du DEF'));
        $this->assertSame($debut - 1, (int) DB::table('eleve')->where('id_eleve', $this->eleves[1])->value('annee_def'));
        $this->get(route('rapport-secondaire.def', ['annee' => $this->annee->id_anneeScolaire], false))->assertSee('Enregistré :')->assertSee('id="def_json"', false);
        $this->post(route('rapport-secondaire.def.store', [], false), ['annee' => $this->annee->id_anneeScolaire, 'def' => [$this->eleves[0] => 1800]])->assertSessionHasErrors();
        $this->get($this->url('rentree', 'rapport-secondaire.index'))->assertOk()->assertSee('élève(s) sans année du DEF')->assertSee('T7 suite');
    }

    public function test_saisie_controlee_et_remise_sur_papier(): void
    {
        DB::table('utilisateurs')->where('droit', 'DAE')->update(['statut' => 0]);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get($this->url('rentree', 'rapport-secondaire.index'))->assertOk()->assertSee('T17')->assertSee("Marquer comme remis à l'académie");
        $this->get($this->url('fin', 'rapport-secondaire.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');

        // Pas un nombre : refusé.
        $this->post($this->url('rentree'), $this->envoi('rentree', ['T5' => ['10CG' => ['f' => 'abc']]], ['action' => 'enregistrer']))->assertSessionHasErrors('tableaux');
        // Tableau, ligne ou colonne inconnus : ignorés.
        $this->post($this->url('rentree'), $this->envoi('rentree', ['T5' => ['10CG' => ['f' => '30', 'pirate' => '9'], 'XX' => ['f' => 1]], 'T99' => ['a' => ['b' => 1]], 'T24' => ['l0' => ['nom' => 'DIARRA']]], ['action' => 'remettre']))->assertSessionHas('success');
        $rapport = RapportSecondaire::withoutGlobalScopes()->where('id_ecole', 1)->where('type', 'rentree')->firstOrFail();
        $this->assertSame('remis', $rapport->statut);
        $this->assertSame(['T5' => ['10CG' => ['f' => 30]], 'T24' => ['l0' => ['nom' => 'DIARRA']]], $rapport->donnees_envoyees['t']);

        // Région manquante : pas de remise.
        $this->post($this->url('fin'), $this->envoi('fin', [], ['action' => 'remettre', 'infos' => ['cercle' => 'x', 'commune' => 'y']]))->assertSessionHasErrors('rapport');
    }

    public function test_envoi_a_l_academie_et_cloisonnement(): void
    {
        $dae = $this->dae();
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->post($this->url('fin'), $this->envoi('fin', ['T15' => ['TSE' => ['m12_f' => 1]]], ['action' => 'remettre']))->assertSessionHas('success');
        $rapport = RapportSecondaire::withoutGlobalScopes()->where('id_ecole', 1)->where('type', 'fin')->firstOrFail();
        $this->assertSame('envoye', $rapport->statut);
        $this->assertTrue(AppNotification::where('user_id', $dae->idUtilisateur)->where('type', 'rapport_secondaire')->exists());

        $this->actingAs($dae)->withSession([])->get('/territoire/rapports-secondaire?type=fin')->assertOk()->assertSee('Reçu');
        $this->get('/territoire/rapports-secondaire?type=rentree')->assertOk()->assertSee('Non reçu');
        $this->get("/territoire/rapports-secondaire/{$rapport->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        $dcap = $this->compte('Admin', 1)->replicate();
        $dcap->forceFill(['droit' => 'DCAP', 'idEcole' => null, 'id_cap' => DB::table('cap')->value('id_cap'), 'statut' => 1, 'email' => 'dcap'.uniqid().'@test.ml'])->save();
        $this->actingAs($dcap)->withSession([])->get("/territoire/rapports-secondaire/{$rapport->id}/pdf")->assertForbidden();
        $autre = (int) DB::table('academie')->where('id_academie', '!=', $dae->id_academie)->value('id_academie');
        if ($autre) {
            DB::table('utilisateurs')->where('idUtilisateur', $dae->idUtilisateur)->update(['id_academie' => $autre]);
            $this->actingAs($dae->fresh())->get("/territoire/rapports-secondaire/{$rapport->id}/pdf")->assertNotFound();
        }
    }

    public function test_chaque_ordre_ne_voit_que_ses_rapports(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        // Lycée seul : rapport succinct, rentrée, fin d'année ; pas de rapport du CAP.
        $this->get('/rapport-succinct')->assertOk()->assertSee('Rapport de rentrée')->assertSee("Rapport de fin d'année")->assertDontSee('Rapport succinct (CAP)');

        // Complexe : les quatre onglets.
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Complexe Scolaire']);
        $fondamental = $this->classes['10']->replicate();
        $fondamental->nom_classe = '3ème année';
        $fondamental->ordreEnseignement = 'fondamentale1';
        $fondamental->save();
        $this->get('/rapport-succinct?portee=fondamental')->assertOk()->assertSee('Rapport succinct (CAP)')->assertSee('Rapport succinct (Académie)')->assertSee('Rapport de rentrée');

        // Fondamental seul : son rapport succinct, sans onglet ni rapport du secondaire.
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Fondamentale I']);
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => 'fondamentale1']);
        $this->get('/rapport-succinct')->assertOk()->assertDontSee('Rapport de rentrée');
        $this->get($this->url('rentree', 'rapport-secondaire.index'))->assertForbidden();
        $this->actingAs($this->compte('parent', 1))->withSession(['idEcole' => 1])->get('/rapport-secondaire/rentree')->assertForbidden();
    }
}
