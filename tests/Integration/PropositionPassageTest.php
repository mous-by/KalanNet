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

    /** Années passées en 7e, 8e, 9e et avis lus sur la fiche officielle (CAP de Torokorobougou, 2024/2025). */
    public function test_regles_d_exclusion_de_la_fiche_officielle(): void
    {
        $cas = [
            // niveau, années passées par niveau, moyenne, avis du conseil
            [7, [7 => 1], 9.15, 'redouble'], [7, [7 => 2], 8.94, 'exclu'], [7, [7 => 3], 5.90, 'exclu'], [7, [7 => 4], 4.28, 'exclu'],
            [8, [7 => 1, 8 => 1], 6.78, 'redouble'], [8, [7 => 2, 8 => 1], 8.04, 'redouble'], [8, [7 => 1, 8 => 2], 9.95, 'exclu'],
            [8, [7 => 2, 8 => 2], 9.85, 'exclu'], [8, [7 => 1, 8 => 3], 6.70, 'exclu'], [8, [7 => 3, 8 => 1], 2.85, 'exclu'], [8, [7 => 2, 8 => 1], 0.0, 'exclu'],
            [9, [7 => 1, 8 => 1, 9 => 1], 8.93, 'redouble'], [9, [7 => 1, 8 => 1, 9 => 2], 8.81, 'redouble'], [9, [7 => 2, 8 => 1, 9 => 1], 8.18, 'redouble'],
            [9, [7 => 1, 8 => 2, 9 => 1], 5.04, 'redouble'], [9, [7 => 1, 8 => 1, 9 => 3], 13.73, 'exclu'], [9, [7 => 1, 8 => 1, 9 => 4], 5.68, 'exclu'],
            [9, [7 => 2, 8 => 1, 9 => 2], 8.01, 'exclu'], [9, [7 => 1, 8 => 2, 9 => 2], 7.23, 'exclu'], [9, [7 => 2, 8 => 2, 9 => 1], 6.84, 'exclu'], [9, [7 => 1, 8 => 1, 9 => 2], 0.0, 'exclu'],
        ];
        foreach ($cas as [$niveau, $annees, $moyenne, $avis]) {
            $doublements = [];
            foreach ($annees as $n => $nombre) {
                $doublements = [...$doublements, ...($nombre > 1 ? array_fill(0, $nombre - 1, $n) : [])];
            }
            $this->assertSame($avis, Donnees::apresEchec($niveau, $doublements, $moyenne), json_encode([$niveau, $annees, $moyenne]));
        }

        // Redoublements du 1er cycle : ne comptent pas au second.
        $this->assertSame('redouble', Donnees::apresEchec(7, [5, 6], 8.0));
        // 1er cycle : même année doublée deux fois, ou deux redoublements déjà faits.
        $this->assertSame('redouble', Donnees::apresEchec(4, [2], 3.0));
        $this->assertSame('exclu', Donnees::apresEchec(4, [4], 3.0));
        $this->assertSame('exclu', Donnees::apresEchec(4, [2, 3], 3.0));
        // Niveau inconnu : le conseil décide, pas d'exclusion automatique.
        $this->assertSame('redouble', Donnees::apresEchec(null, [7, 8], 3.0));
    }

    public function test_un_redoublement_enregistre_mene_a_l_exclusion_apres_un_nouvel_echec(): void
    {
        $modele = \App\Models\Classe::where('idEcole', 1)->whereIn('ordreEnseignement', Donnees::ORDRES)->first()->replicate();
        $modele->nom_classe = '9ème ZZ';
        $modele->ordreEnseignement = 'fondamentale2';
        $modele->save();
        $examen = \App\Support\ExamenNational::pourClasse($modele, 1);
        if (!$examen) {
            $this->markTestSkipped('Pas d\'examen national en 9e pour l\'école 1.');
        }
        [$a, $b, $c] = $this->eleves;
        DB::table('eleve')->whereIn('id_eleve', $this->eleves)->update(['id_classe' => $modele->id_classe]);
        foreach ($this->eleves as $id) {
            DB::table('resultats_def_terminal')->insert(['id_eleve' => $id, 'id_annee' => $this->annee->id_anneeScolaire, 'id_classe' => $modele->id_classe, 'niveau_examen' => $examen, 'decision' => 'échec', 'date_resultat' => now()->toDateString()]);
        }
        $redouble = fn (int $id) => DB::table('ligne_reinscription')->insert(['id_eleve' => $id, 'id_classe' => $modele->id_classe, 'id_annee' => $this->annee->id_anneeScolaire,
            'id_reinscription' => DB::table('reinscription')->insertGetId(['statut' => 'redoublant', 'date_reinscription' => now()->toDateString(), 'enrolement' => 1])]);
        $redouble($b);
        $redouble($c);
        $redouble($c);

        $lignes = Donnees::calculer($modele, (int) $this->annee->id_anneeScolaire);
        $this->assertSame('redouble', $lignes[$a]['proposition']);
        $this->assertSame('redouble', $lignes[$b]['proposition'], '9e doublée une fois : peut redoubler encore');
        $this->assertSame('exclu', $lignes[$c]['proposition'], '9e déjà doublée deux fois');
        $this->assertSame('9èZZ-9èZZ', $lignes[$c]['doublees']);
        $this->assertSame(11, $lignes[$c]['scolarite']);
    }

    public function test_colonnes_de_la_fiche_detaillee_comme_sur_le_modele(): void
    {
        // Années scolaire 2024/2025 : valeurs lues sur la fiche officielle.
        $c = Donnees::colonnesDetaillees(7, [], 2024);
        $this->assertSame([2018, 'Néant', 6, 2024, 1, null, null], array_values($c));
        $c = Donnees::colonnesDetaillees(7, [5], 2024);
        $this->assertSame([2017, '5ème', 7, 2024, 1, null, null], array_values($c));
        $c = Donnees::colonnesDetaillees(8, [8], 2024);
        $this->assertSame([2016, 'Néant', 6, 2022, 1, 2, null], array_values($c));
        $c = Donnees::colonnesDetaillees(9, [9, 9], 2024);
        $this->assertSame([2014, 'Néant', 6, 2020, 1, 1, 3], array_values($c));
        $c = Donnees::colonnesDetaillees(9, [6, 6], 2024);
        $this->assertSame('6ème;6ème', $c['doublees_1er']);
        $this->assertSame(8, $c['scolarite_1er']);
        $this->assertNull(Donnees::colonnesDetaillees(4, [], 2024)['annee_7e']);

        $this->assertSame(['Moussa Abdoul', 'TRAORE'], Donnees::decouperNom('Moussa Abdoul TRAORE'));
        $this->assertSame(['Moussa', 'TRAORE'], Donnees::decouperNom('TRAORE Moussa'));
        $this->assertSame(['Awa', 'Coulibaly'], Donnees::decouperNom('Awa Coulibaly'));
        $this->assertSame(['Moussa', ''], Donnees::decouperNom('Moussa'));
        $this->assertSame(['', ''], Donnees::decouperNom(null));

        $this->assertSame('Exclue', Donnees::avis('exclu', 'F', false));
        $this->assertSame('Admis', Donnees::avis('passe', 'M', true));
        $this->assertSame('Passe', Donnees::avis('passe', 'F', false));
    }

    public function test_l_ecole_choisit_le_format_et_le_cap_recoit_le_meme(): void
    {
        $modele = \App\Models\Classe::where('idEcole', 1)->whereIn('ordreEnseignement', Donnees::ORDRES)->first()->replicate();
        $modele->nom_classe = '7ème ZZ';
        $modele->ordreEnseignement = 'fondamentale2';
        $modele->save();
        $premier = $modele->replicate();
        $premier->nom_classe = '4ème ZZ';
        $premier->ordreEnseignement = 'fondamentale1';
        $premier->save();
        if (!\App\Support\ExamenNational::estMali(1)) {
            $this->markTestSkipped('L\'école 1 n\'est pas au Mali.');
        }
        [$a, $b, $c] = $this->eleves;
        DB::table('eleve')->whereIn('id_eleve', $this->eleves)->update(['id_classe' => $modele->id_classe]);
        $dcap = $this->dcap((int) DB::table('cap')->value('id_cap'));
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $url = route('proposition-passage.store', ['classe' => $modele->id_classe, 'annee' => $this->annee->id_anneeScolaire], false);

        $this->get(route('proposition-passage.index', ['classe' => $modele->id_classe, 'annee' => $this->annee->id_anneeScolaire], false))->assertOk()->assertSee('Fiche détaillée', false)->assertSee('data-seulement-detaillee', false);

        $lignes = json_decode($this->formulaire('x', [$a => 'passe', $b => 'redouble', $c => 'exclu'])['lignes_json'], true);
        $lignes[$a]['annee_7e'] = 2023;
        $this->post($url, ['action' => 'remettre', 'modele' => 'detaillee', 'lignes_json' => json_encode($lignes), 'renseignements' => ['commune' => 'V Bamako', 'telephone' => '79000000', 'statut' => 'prive', 'recrutement' => 'annuel']])->assertSessionHasNoErrors();
        $fiche = PropositionPassage::withoutGlobalScopes()->where('id_classe', $modele->id_classe)->firstOrFail();
        $this->assertSame('detaillee', $fiche->modele);
        $this->assertSame('V Bamako', $fiche->renseignements['commune']);
        $this->assertSame(2023, (int) $fiche->lignes[$a]['annee_7e']);

        $this->get(route('proposition-passage.pdf', ['classe' => $modele->id_classe, 'annee' => $this->annee->id_anneeScolaire], false))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs(User::withoutGlobalScopes()->find($dcap))->withSession(['idEcole' => null]);
        $this->get("/territoire/propositions-passage/{$fiche->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/territoire/propositions-passage/{$fiche->id}")->assertOk();

        // Premier cycle : pas de modèle officiel détaillé, la demande retombe sur la fiche standard.
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        DB::table('eleve')->whereIn('id_eleve', $this->eleves)->update(['id_classe' => $premier->id_classe]);
        $this->post(route('proposition-passage.store', ['classe' => $premier->id_classe, 'annee' => $this->annee->id_anneeScolaire], false), ['action' => 'enregistrer', 'modele' => 'detaillee', 'lignes_json' => json_encode($lignes)])->assertSessionHasNoErrors();
        $this->assertSame('standard', PropositionPassage::withoutGlobalScopes()->where('id_classe', $premier->id_classe)->value('modele'));
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
