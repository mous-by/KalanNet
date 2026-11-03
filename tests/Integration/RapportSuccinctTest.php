<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Ecole;
use App\Models\RapportSuccinct;
use App\Support\RapportSuccinctDonnees as Donnees;
use Illuminate\Support\Facades\DB;

/** Rapport succinct : pré-remplissage, téléchargement toujours possible, remise au CAP (compte DCAP ou papier), cloisonnement. */
class RapportSuccinctTest extends IntegrationTestCase
{
    private function formulaire(array $autres = []): array
    {
        $annee = AnneeScolaire::enCours(1);
        $donnees = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $annee->id_anneeScolaire);
        $groupes = collect($donnees['groupes'])->keyBy('cle')->map(fn ($l) => collect($l)->only(['groupes', 'g', 'f', 'rg', 'rf'])->all())->all();
        $maitres = collect($donnees['maitres'])->map(fn ($m) => ['salles' => 8] + $m)->all();

        return $autres + ['quartier' => 'Légal-Ségou', 'commune' => 'Kayes', 'cap' => 'Kayes RG', 'cercle' => 'Kayes', 'academie' => 'Kayes',
            'cycle' => 2, 'statut' => 3, 'enseignement' => 1, 'recrutement' => 1, 'groupes' => $groupes, 'maitres' => $maitres];
    }

    private function dcap(int $idCap, int $statut = 1): void
    {
        DB::table('utilisateurs')->where('droit', 'DCAP')->update(['statut' => 0]);
        if ($statut) {
            DB::table('utilisateurs')->where('droit', 'DCAP')->limit(1)->update(['statut' => 1, 'id_cap' => $idCap]);
        }
    }

    public function test_chiffres_repris_de_kalannet(): void
    {
        $annee = AnneeScolaire::enCours(1);
        $classe = DB::table('classe')->where('idEcole', 1)->where('ordreEnseignement', 'fondamentale2')->first();
        if (!$classe) {
            $this->markTestSkipped('Aucune classe de Fondamental II dans l\'école 1.');
        }
        $niveau = (string) \App\Support\ExamenNational::extractClasseLevel($classe->nom_classe);
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(3)->pluck('id_eleve');
        DB::table('eleve')->where('id_ecole', 1)->where('id_classe', $classe->id_classe)->update(['id_classe' => DB::table('classe')->where('idEcole', 1)->where('id_classe', '!=', $classe->id_classe)->value('id_classe')]);
        DB::table('eleve')->whereIn('id_eleve', $eleves)->update(['id_classe' => $classe->id_classe, 'id_annee' => $annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => 'Masculin']);
        DB::table('eleve')->where('id_eleve', $eleves[0])->update(['genre_eleve' => 'Féminin']);
        $reinscription = DB::table('reinscription')->insertGetId(['statut' => 'redoublant', 'date_reinscription' => now()->toDateString(), 'enrolement' => 1]);
        DB::table('ligne_reinscription')->insert(['id_eleve' => $eleves[0], 'id_classe' => $classe->id_classe, 'id_annee' => $annee->id_anneeScolaire, 'id_reinscription' => $reinscription]);

        $donnees = Donnees::calculer(Ecole::withoutGlobalScopes()->find(1), (int) $annee->id_anneeScolaire);
        $ligne = collect($donnees['groupes'])->firstWhere('cle', $niveau);

        $this->assertGreaterThanOrEqual(2, $ligne['g']);
        $this->assertGreaterThanOrEqual(1, $ligne['f']);
        $this->assertGreaterThanOrEqual(1, $ligne['rf']);
        $this->assertGreaterThanOrEqual(1, $ligne['groupes']);
        $this->assertSame(Donnees::totaux($donnees)['eleves'], collect($donnees['groupes'])->sum(fn ($l) => $l['g'] + $l['f']));
    }

    public function test_cap_sans_kalannet_telechargement_et_remise_sur_papier(): void
    {
        $this->dcap(0, 0);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        // Le PDF rempli se télécharge avant même d'enregistrer.
        $this->get('/rapport-succinct')->assertOk()->assertSee("n'utilise pas encore KalanNet", false)->assertSee('Marquer comme remis au CAP');
        $this->get('/rapport-succinct/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');

        // Il manque des champs : remise refusée, mais le brouillon est gardé.
        $this->from('/rapport-succinct')->post('/rapport-succinct', $this->formulaire(['action' => 'remettre', 'commune' => '']))->assertSessionHasErrors('rapport');
        $rapport = RapportSuccinct::where('id_ecole', 1)->first();
        $this->assertSame('brouillon', $rapport->statut);

        $this->post('/rapport-succinct', $this->formulaire(['action' => 'telecharger']))->assertRedirectContains('/rapport-succinct/pdf');

        $this->post('/rapport-succinct', $this->formulaire(['action' => 'remettre']))->assertRedirect()->assertSessionHas('success');
        $this->assertSame('remis', $rapport->fresh()->statut);
        $this->assertSame('Kayes RG', $rapport->fresh()->donnees_envoyees['cap']);
    }

    public function test_cap_sur_kalannet_envoi_notification_et_cloisonnement(): void
    {
        $idCap = (int) Ecole::withoutGlobalScopes()->find(1)->id_cap;
        if (!$idCap) {
            $this->markTestSkipped('L\'école 1 n\'a pas de CAP.');
        }
        $this->dcap($idCap);
        $dcap = $this->compte('DCAP');

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get('/rapport-succinct')->assertSee('Envoyer au CAP');
        $this->post('/rapport-succinct', $this->formulaire(['action' => 'enregistrer']))->assertSessionHas('success');
        $rapport = RapportSuccinct::where('id_ecole', 1)->first();

        // Brouillon : invisible pour le CAP.
        $this->actingAs($dcap)->withSession([])->get('/territoire/rapports-succincts')->assertOk()->assertSee('Non reçu');
        $this->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertNotFound();

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->post('/rapport-succinct', $this->formulaire(['action' => 'remettre']))->assertSessionHas('success');
        $this->assertSame('envoye', $rapport->fresh()->statut);
        $this->assertTrue(AppNotification::where('user_id', $dcap->idUtilisateur)->where('type', 'rapport_succinct')->exists());

        // Une correction de l'école ne change pas ce que le CAP a reçu, tant qu'elle ne renvoie pas.
        $this->post('/rapport-succinct', $this->formulaire(['action' => 'enregistrer', 'commune' => 'Autre commune']))->assertSessionHas('success');
        $this->assertSame('Kayes', $rapport->fresh()->donnees_envoyees['commune']);
        $this->get('/rapport-succinct')->assertSee('remettez-la de nouveau');

        $this->actingAs($dcap)->withSession([])->get('/territoire/rapports-succincts')->assertOk()->assertSee('Reçu');
        $this->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        // DCAP d'un autre CAP : introuvable.
        $autreCap = DB::table('cap')->where('id_cap', '!=', $idCap)->value('id_cap');
        if ($autreCap) {
            DB::table('utilisateurs')->where('idUtilisateur', $dcap->idUtilisateur)->update(['id_cap' => $autreCap]);
            $this->actingAs($dcap->fresh())->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertNotFound();
        }
    }

    public function test_l_academie_n_a_pas_acces(): void
    {
        $rapport = RapportSuccinct::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => AnneeScolaire::enCours(1)->id_anneeScolaire, 'statut' => 'envoye', 'donnees' => [], 'donnees_envoyees' => []]);
        $dae = $this->compte('DAE');

        // Elle suit le secondaire, jamais les fiches remises au CAP.
        $this->actingAs($dae)->get('/territoire/rapports-succincts')->assertOk();
        $this->get("/territoire/rapports-succincts/{$rapport->id}/pdf")->assertNotFound();
    }

    public function test_rappel_de_fin_octobre_seulement_aux_ecoles_qui_n_ont_pas_remis(): void
    {
        $annee = AnneeScolaire::enCours(1);
        DB::table('abonnements')->where('ecole_id', 1)->update(['statut' => 'actif', 'fin_at' => null]);
        $admin = $this->compte('Admin', 1);
        $recus = fn () => AppNotification::where('user_id', $admin->idUtilisateur)->where('type', 'rapport_succinct_rappel');

        $this->artisan('rapports-succincts:rappel')->assertSuccessful();
        $this->assertSame(1, $recus()->count());
        $this->assertSame('/rapport-succinct?annee='.$annee->id_anneeScolaire, $recus()->first()->link);

        // Relancé (cron de plusieurs jours) : pas de second rappel la même année.
        $this->artisan('rapports-succincts:rappel')->assertSuccessful();
        $this->assertSame(1, $recus()->count());

        // Rapport remis : plus de rappel. Brouillon : rappel maintenu.
        $recus()->delete();
        RapportSuccinct::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => $annee->id_anneeScolaire, 'statut' => 'remis', 'donnees' => [], 'donnees_envoyees' => []]);
        $this->artisan('rapports-succincts:rappel')->assertSuccessful();
        $this->assertSame(0, $recus()->count());

        RapportSuccinct::withoutGlobalScopes()->where('id_ecole', 1)->update(['statut' => 'brouillon']);
        $this->artisan('rapports-succincts:rappel')->assertSuccessful();
        $this->assertSame(1, $recus()->count());
    }

    public function test_droits_et_ecoles_non_concernees(): void
    {
        $this->actingAs($this->compte('parent', 1))->withSession(['idEcole' => 1]);
        $this->get('/rapport-succinct')->assertForbidden();
        $this->post('/rapport-succinct', $this->formulaire())->assertForbidden();
        $this->get('/territoire/rapports-succincts')->assertForbidden();

        // Sans classe concernée ni type de complexe ou de secondaire : pas de rapport succinct.
        $admin = $this->adminComplet(1);
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Fondamentale I']);
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => null]);
        $this->actingAs($admin->fresh())->withSession(['idEcole' => 1])->get('/rapport-succinct')->assertForbidden();
    }
}
