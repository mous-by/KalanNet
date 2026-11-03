<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\PropositionPassage;
use App\Models\User;
use App\Support\PropositionPassageDonnees as Donnees;
use Illuminate\Support\Facades\DB;

/** Proposition de passage : calcul, téléchargement toujours possible, remise (compte DCAP ou papier), décision du CAP, réinscription. */
class PropositionPassageTest extends IntegrationTestCase
{
    private object $classe;
    private AnneeScolaire $annee;
    /** @var list<int> */
    private array $eleves;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = AnneeScolaire::enCours(1);
        $classe = DB::table('classe')->where('idEcole', 1)->whereIn('ordreEnseignement', Donnees::ORDRES)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(3)->pluck('id_eleve')->all();
        if (!$classe || count($eleves) < 3) {
            $this->markTestSkipped('Pas de classe du fondamental ou pas assez d\'élèves dans l\'école 1.');
        }
        $this->classe = $classe;
        $this->eleves = $eleves;
        DB::table('eleve')->where('id_ecole', 1)->where('id_classe', $classe->id_classe)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        DB::table('eleve')->whereIn('id_eleve', $eleves)->update(['id_classe' => $classe->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => 'Masculin']);
        DB::table('eleve')->where('id_eleve', $eleves[0])->update(['genre_eleve' => 'Féminin']);
    }

    /** Compte DCAP créé pour le test (dans sa transaction), les autres sont désactivés. */
    private function nouveauDcap(int $idCap): User
    {
        $compte = $this->compte('Admin', 1)->replicate();
        $compte->forceFill(['droit' => 'DCAP', 'idEcole' => null, 'id_cap' => $idCap, 'statut' => 1, 'email' => 'dcap'.uniqid().'@test.ml']);
        if (\Illuminate\Support\Facades\Schema::hasColumn('utilisateurs', 'login')) {
            $compte->login = 'dcap'.uniqid();
        }
        $compte->save();

        return $compte;
    }

    private function dcap(?int $idCap): ?int
    {
        DB::table('utilisateurs')->where('droit', 'DCAP')->update(['statut' => 0]);
        if (!$idCap) {
            return null;
        }
        DB::table('ecole')->where('idEcole', 1)->update(['id_cap' => $idCap]);

        return $this->nouveauDcap($idCap)->idUtilisateur;
    }

    private function url(string $route = 'proposition-passage.store'): string
    {
        return route($route, ['classe' => $this->classe->id_classe, 'annee' => $this->annee->id_anneeScolaire], false);
    }

    private function formulaire(string $action, array $propositions): array
    {
        $lignes = [];
        foreach ($propositions as $id => $proposition) {
            $lignes[$id] = ['prenom' => 'Falsifié', 'moyenne' => 20, 'proposition' => $proposition, 'scolarite' => 3, 'doublees' => 'Néant', 'pere' => 'Moussa', 'mere' => 'Awa Coulibaly', 'observation' => null];
        }

        return ['action' => $action, 'directeur' => 'M. Sidibé', 'lignes_json' => json_encode($lignes)];
    }

    private function fiche(): PropositionPassage
    {
        return PropositionPassage::withoutGlobalScopes()->where('id_ecole', 1)->where('id_classe', $this->classe->id_classe)->where('id_annee', $this->annee->id_anneeScolaire)->firstOrFail();
    }

    public function test_recap_et_tri_comme_sur_le_modele(): void
    {
        $l = fn ($sexe, $proposition, $moyenne) => compact('sexe', 'proposition', 'moyenne') + ['prenom' => 'A', 'nom' => 'B'];
        $lignes = Donnees::trier([$l('M', 'abandon', null), $l('F', 'redouble', 9), $l('M', 'passe', 11), $l('F', 'passe', 14), $l('M', 'exclu', 2)]);

        $this->assertSame(['passe', 'passe', 'redouble', 'exclu', 'abandon'], array_column(array_values($lignes), 'proposition'));
        $this->assertSame(14, array_values($lignes)[0]['moyenne']);
        $recap = Donnees::recap($lignes);
        $this->assertSame(['g' => 1, 'f' => 1, 't' => 2], $recap['passe']);
        $this->assertSame(['g' => 3, 'f' => 2, 't' => 5], $recap['total']);
    }

    public function test_cap_sans_compte_telechargement_et_remise_sur_papier(): void
    {
        $this->dcap(null);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $this->get($this->url('proposition-passage.index'))->assertOk()->assertSee('remettez-la à votre CAP', false)->assertSee('Marquer comme remise au CAP');
        $this->get($this->url('proposition-passage.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');

        [$a, $b, $c] = $this->eleves;
        // Une proposition manque : rien n'est remis, mais le travail est gardé.
        $this->post($this->url(), $this->formulaire('remettre', [$a => 'passe', $b => 'redouble', $c => null]))->assertSessionHasErrors('lignes');
        $this->assertSame('brouillon', $this->fiche()->statut);

        $this->post($this->url(), $this->formulaire('remettre', [$a => 'passe', $b => 'redouble', $c => 'abandon']))->assertSessionHasNoErrors();
        $this->assertSame('remis', $this->fiche()->statut);
        $this->assertSame(0, AppNotification::where('type', 'proposition_passage')->where('created_at', '>=', now()->subMinute())->count());
    }

    public function test_cap_sur_kalannet_decision_notification_pdf_et_reinscription(): void
    {
        $idCap = (int) DB::table('cap')->value('id_cap');
        $dcap = $this->dcap($idCap);
        $admin = $this->adminComplet(1);
        [$a, $b, $c] = $this->eleves;
        $this->actingAs($admin)->withSession(['idEcole' => 1]);

        $this->get($this->url('proposition-passage.index'))->assertOk()->assertSee('Envoyer au CAP');
        $this->post($this->url(), $this->formulaire('remettre', [$a => 'passe', $b => 'passe', $c => 'redouble']))->assertSessionHasNoErrors();
        $fiche = $this->fiche();
        $this->assertSame('envoye', $fiche->statut);
        $this->assertSame(1, AppNotification::where('user_id', $dcap)->where('type', 'proposition_passage')->count());

        // L'identité et la moyenne ne se trafiquent pas depuis le formulaire.
        $this->assertNotSame('Falsifié', $fiche->lignes[$a]['prenom']);

        // Le DCAP voit la fiche et décide : il change un élève, confirme les autres.
        $compteDcap = User::withoutGlobalScopes()->find($dcap);
        $this->actingAs($compteDcap)->withSession(['idEcole' => null]);
        $this->get('/territoire/propositions-passage')->assertOk()->assertSee('À décider');
        $this->get("/territoire/propositions-passage/{$fiche->id}")->assertOk()->assertSee('Valider la décision du CAP');
        $this->post("/territoire/propositions-passage/{$fiche->id}/decision", ['decisions' => [$a => ['decision' => 'passe']]])->assertSessionHasErrors('decisions');
        $this->assertSame('envoye', $fiche->fresh()->statut);
        $this->post("/territoire/propositions-passage/{$fiche->id}/decision", ['decisions' => [
            $a => ['decision' => 'passe'], $b => ['decision' => 'redouble', 'observation' => 'Moyenne insuffisante'], $c => ['decision' => 'exclu'],
        ]])->assertSessionHasNoErrors();
        $fiche = $fiche->fresh();
        $this->assertSame('validee', $fiche->statut);
        $this->assertSame('redouble', $fiche->lignes_envoyees[$b]['decision_cap']);
        $this->assertSame(1, AppNotification::where('user_id', $admin->idUtilisateur)->where('type', 'proposition_passage_decision')->count());
        $this->get("/territoire/propositions-passage/{$fiche->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        // La réinscription reprend la décision du CAP.
        $decisions = \App\Http\Controllers\PropositionPassageController::decisionsCap(1, (int) $this->classe->id_classe, (int) $this->annee->id_anneeScolaire);
        $this->assertSame('exclu', $decisions[$c]['decision']);
        $this->assertSame('Moyenne insuffisante', $decisions[$b]['observation']);

        $this->actingAs($admin)->withSession(['idEcole' => 1]);
        $debut = (int) substr((string) $this->annee->annee, 0, 4) + 1;
        $cible = DB::table('anneescolaire')->insertGetId(['annee' => $debut.'-'.($debut + 1), 'date_debut' => $debut.'-10-01', 'date_fin' => ($debut + 1).'-07-31', 'id_ecole' => 1]);
        $page = $this->post('/pedagogie/inscriptions/reinscription/apercu', ['source_classe_id' => $this->classe->id_classe, 'source_annee_id' => $this->annee->id_anneeScolaire, 'target_annee_id' => $cible])->assertOk();
        $lignes = collect($page->viewData('reinscriptionPreview')['rows'])->keyBy(fn ($r) => $r['eleve']->id_eleve);
        $this->assertSame('redoublant', $lignes[$b]['decision_proposee']);
        $this->assertSame('exclu', $lignes[$c]['decision_proposee']);
        $this->assertSame((int) $this->classe->id_classe, (int) $lignes[$c]['classe_cible_id']);
        $page->assertSee('Décision du CAP')->assertSee('value="Moyenne insuffisante"', false);

        // Une fiche renvoyée repart au CAP sans ses anciennes décisions.
        $this->actingAs($admin)->withSession(['idEcole' => 1]);
        $this->post($this->url(), $this->formulaire('remettre', [$a => 'passe', $b => 'passe', $c => 'redouble']))->assertSessionHasNoErrors();
        $this->assertSame('envoye', $this->fiche()->statut);
        $this->assertArrayNotHasKey('decision_cap', $this->fiche()->lignes_envoyees[$b]);
        $this->assertSame([], \App\Http\Controllers\PropositionPassageController::decisionsCap(1, (int) $this->classe->id_classe, (int) $this->annee->id_anneeScolaire));
    }

    public function test_cloisonnement_et_droits(): void
    {
        $idCap = (int) DB::table('cap')->value('id_cap');
        $this->dcap($idCap);
        [$a, $b, $c] = $this->eleves;
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->post($this->url(), $this->formulaire('remettre', [$a => 'passe', $b => 'passe', $c => 'passe']));
        $fiche = $this->fiche();

        // Un DCAP d'un autre CAP ne voit pas la fiche, et un brouillon n'est visible d'aucun DCAP.
        $autreCap = (int) DB::table('cap')->where('id_cap', '!=', $idCap)->value('id_cap');
        if ($autreCap) {
            $this->actingAs($this->nouveauDcap($autreCap))->withSession(['idEcole' => null]);
            $this->get("/territoire/propositions-passage/{$fiche->id}")->assertNotFound();
            $this->post("/territoire/propositions-passage/{$fiche->id}/decision", ['decisions' => [$a => ['decision' => 'exclu']]])->assertNotFound();
        }

        // Pas le droit : parent, et classe d'une autre école.
        $parent = $this->compte('parent', 1);
        $this->actingAs($parent)->withSession(['idEcole' => 1])->get('/proposition-passage')->assertForbidden();
        $this->get('/territoire/propositions-passage')->assertForbidden();

        $autreClasse = DB::table('classe')->where('idEcole', '!=', 1)->whereIn('ordreEnseignement', Donnees::ORDRES)->value('id_classe');
        if ($autreClasse) {
            $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
            $this->post(route('proposition-passage.store', ['classe' => $autreClasse, 'annee' => $this->annee->id_anneeScolaire], false), $this->formulaire('enregistrer', [$a => 'passe']))->assertNotFound();
        }
    }
}
