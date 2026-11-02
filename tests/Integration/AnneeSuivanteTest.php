<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Année suivante créée dès juin pour la réinscription ; l'année en cours reste celle des dates jusqu'au 1er septembre. */
class AnneeSuivanteTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function annee(string $nom): ?object
    {
        return DB::table('anneescolaire')->where('annee', $nom)->whereNull('id_ecole')->first();
    }

    public function test_cree_des_juin_jamais_avant_et_sans_toucher_l_annee_en_cours(): void
    {
        DB::table('anneescolaire')->whereIn('annee', ['2029-2030', '2030-2031'])->delete();
        // Dates de test lointaines : licence à vie, pour que l'abonnement ne bloque pas les pages.
        DB::table('abonnements')->where('ecole_id', 1)->update(['statut' => 'actif', 'fin_at' => null]);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        Carbon::setTestNow('2030-05-31 12:00:00');
        $this->followingRedirects()->get('/pedagogie/inscriptions/reinscription')->assertOk();
        $this->assertNotNull($this->annee('2029-2030'));
        $this->assertNull($this->annee('2030-2031'), 'pas encore en mai');

        Carbon::setTestNow('2030-06-01 08:00:00');
        $this->followingRedirects()->get('/pedagogie/inscriptions/reinscription')->assertOk();
        $suivante = $this->annee('2030-2031');
        $this->assertNotNull($suivante, 'créée le 1er juin à l\'ouverture de la réinscription');
        $this->assertSame('2030-09-01', $suivante->date_debut);
        $this->assertSame(1, DB::table('anneescolaire')->where('annee', '2030-2031')->whereNull('id_ecole')->count());

        // La barre du haut et les formulaires financiers restent sur l'année en cours.
        $this->assertSame('2029-2030', AnneeScolaire::enCours(1)->annee);
        $this->get('/dashboard')->assertOk()->assertSee('2029-2030');

        // Au 1er septembre, l'année suivante devient l'année en cours, sans doublon.
        Carbon::setTestNow('2030-09-01 08:00:00');
        $this->assertSame('2030-2031', AnneeScolaire::enCours(1)->annee);
        $this->get('/configuration/annees')->assertOk();
        $this->assertSame(1, DB::table('anneescolaire')->where('annee', '2030-2031')->whereNull('id_ecole')->count());
    }

    public function test_pas_de_doublon_quand_l_ecole_a_cree_la_sienne(): void
    {
        DB::table('anneescolaire')->whereIn('annee', ['2032-2033'])->delete();
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $propre = DB::table('anneescolaire')->insertGetId(['annee' => '2032-2033', 'date_debut' => '2032-09-01', 'date_fin' => '2033-08-31', 'id_ecole' => 1]);
        $commune = DB::table('anneescolaire')->insertGetId(['annee' => '2032-2033', 'date_debut' => '2032-09-01', 'date_fin' => '2033-08-31', 'id_ecole' => null]);

        $ids = AnneeScolaire::where('annee', '2032-2033')->pluck('id_anneeScolaire')->all();
        $this->assertSame([$propre], $ids, 'l\'école ne voit que la sienne');

        // Élèves déjà sur l'année commune : rien n'est masqué.
        DB::table('eleve')->where('id_ecole', 1)->limit(1)->update(['id_annee' => $commune]);
        $this->assertCount(2, AnneeScolaire::where('annee', '2032-2033')->get());

        // Une autre école voit l'année commune.
        session(['idEcole' => $this->autreEcole(1)]);
        $this->assertContains($commune, AnneeScolaire::where('annee', '2032-2033')->pluck('id_anneeScolaire')->all());
    }

    public function test_creation_d_une_annee_qui_existe_deja_refusee(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $nom = DB::table('anneescolaire')->whereNull('id_ecole')->value('annee');
        $avant = DB::table('anneescolaire')->count();

        $this->from('/configuration/annees')->post('/configuration/annees', ['annee' => $nom, 'date_debut' => '2040-09-01', 'date_fin' => '2041-08-31'])
            ->assertSessionHasErrors('annee');
        $this->assertSame($avant, DB::table('anneescolaire')->count());
    }

    public function test_les_formulaires_financiers_proposent_l_annee_en_cours(): void
    {
        DB::table('anneescolaire')->insert(['annee' => '2099-2100', 'date_debut' => '2099-09-01', 'date_fin' => '2100-08-31', 'id_ecole' => null]);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $page = $this->get('/finances/depenses')->assertOk()->getContent();
        $enCours = AnneeScolaire::enCours(1);
        if (str_contains($page, 'name="id_annee_scolaire"')) {
            $this->assertStringContainsString('value="'.$enCours->id_anneeScolaire.'" selected', $page);
        }
        $this->assertNotSame('2099-2100', $enCours->annee);
    }
}
