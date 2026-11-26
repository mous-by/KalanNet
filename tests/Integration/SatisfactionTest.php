<?php

namespace Tests\Integration;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Avis de satisfaction : proposé après une action réussie seulement, rapport et publication sur le site de CSB. */
class SatisfactionTest extends IntegrationTestCase
{
    private \App\Models\User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        if (!\App\Support\SchemaExiste::table('avis_satisfaction')) {
            $this->markTestSkipped('Migration des avis de satisfaction non jouée.');
        }
        $this->admin = $this->adminComplet(1);
        DB::table('avis_satisfaction')->delete();
        DB::table('satisfaction_publications')->delete();
        config(['services.csb_support.url' => 'https://csb.test', 'services.csb_support.token' => 'csb_test']);
        Http::fake(['csb.test/*' => Http::response(['ok' => true, 'url' => 'https://csb.test/temoignages'])]);
    }

    private function pageApresAction(): string
    {
        return $this->actingAs($this->admin)->withSession(['idEcole' => 1, 'success' => 'Enregistré.'])->get('/dashboard')->getContent();
    }

    public function test_pas_d_avis_sans_action_reussie_ni_avant_trois_actions(): void
    {
        $this->assertStringNotContainsString('satisfactionModal', $this->actingAs($this->admin)->withSession(['idEcole' => 1])->get('/dashboard')->getContent());
        $this->assertStringNotContainsString('satisfactionModal', $this->pageApresAction());
        $this->assertStringNotContainsString('satisfactionModal', $this->pageApresAction());
        $this->assertStringContainsString('satisfactionModal', $this->pageApresAction(), 'à la troisième action réussie');
        $this->assertStringNotContainsString('satisfactionModal', $this->pageApresAction(), 'une seule fois par session');
    }

    public function test_le_modal_suit_la_langue_choisie(): void
    {
        foreach (['fr' => 'C\'est fait ! Un petit avis ?', 'en' => 'Done! Quick feedback?', 'ar' => 'تمّ! هل تشاركنا رأيك؟'] as $langue => $titre) {
            $this->admin->forceFill(['locale_preference' => $langue])->save();
            $page = $this->withSession(['satisfaction_actions' => 5, 'satisfaction_proposee' => false])->pageApresAction();
            $this->assertStringContainsString(e($titre), $page, "modal en « $langue »");
        }
    }

    public function test_pas_redemande_avant_le_delai(): void
    {
        DB::table('avis_satisfaction')->insert(['user_id' => $this->admin->idUtilisateur, 'statut' => 'plus_tard', 'created_at' => now()->subDays(3), 'updated_at' => now()]);
        $this->withSession(['satisfaction_actions' => 5]);

        $this->assertStringNotContainsString('satisfactionModal', $this->pageApresAction());
    }

    public function test_le_supadmin_n_est_jamais_sollicite(): void
    {
        $this->admin = $this->compte('SupAdmin');
        $this->withSession(['satisfaction_actions' => 5]);

        $this->assertStringNotContainsString('satisfactionModal', $this->pageApresAction());
    }

    public function test_enregistrer_un_avis(): void
    {
        $this->actingAs($this->admin)->withSession(['idEcole' => 1])->postJson('/satisfaction', [
            'statut' => 'repondu', 'note' => 5, 'points' => ['facile', 'temps'], 'commentaire' => 'Très pratique pour les bulletins.',
            'accord_publication' => 1, 'contexte' => 'evaluations',
        ])->assertOk();

        $avis = DB::table('avis_satisfaction')->where('user_id', $this->admin->idUtilisateur)->first();
        $this->assertSame(5, (int) $avis->note);
        $this->assertSame(['facile', 'temps'], json_decode($avis->points, true));
        $this->assertTrue((bool) $avis->accord_publication);
    }

    public function test_un_avis_negatif_n_est_jamais_publiable(): void
    {
        $this->actingAs($this->admin)->withSession(['idEcole' => 1])->postJson('/satisfaction', [
            'statut' => 'repondu', 'note' => 2, 'points' => ['lent'], 'commentaire' => 'Trop lent', 'accord_publication' => 1,
        ])->assertOk();

        $this->assertFalse((bool) DB::table('avis_satisfaction')->value('accord_publication'));
    }

    public function test_rapport_et_publications_reservees_au_supadmin(): void
    {
        $this->actingAs($this->admin)->withSession(['idEcole' => 1])->get('/satisfaction')->assertForbidden();
        $this->actingAs($this->admin)->withSession(['idEcole' => 1])->post('/satisfaction/rapport/publier')->assertForbidden();
    }

    public function test_le_supadmin_publie_un_avis_et_le_rapport_sur_le_site_de_csb(): void
    {
        $id = DB::table('avis_satisfaction')->insertGetId([
            'user_id' => $this->admin->idUtilisateur, 'id_ecole' => 1, 'statut' => 'repondu', 'note' => 5, 'droit' => 'Admin',
            'commentaire' => 'KalanNet a changé notre école.', 'accord_publication' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sup = $this->compte('SupAdmin');

        $this->actingAs($sup)->get('/satisfaction')->assertOk()->assertSee('KalanNet a changé notre école.');
        $this->actingAs($sup)->from('/satisfaction')->post("/satisfaction/{$id}/publier")->assertSessionHas('success');
        $this->assertNotNull(DB::table('avis_satisfaction')->where('id', $id)->value('publie_at'));

        $this->actingAs($sup)->from('/satisfaction')->post('/satisfaction/rapport/publier')->assertSessionHas('success');
        $this->assertSame(1, DB::table('satisfaction_publications')->count());

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/v1/references/temoignages')
            && $r['ref'] === 'avis-'.$id && $r['publie'] === true && $r['note'] === 5 && $r['auteur'] === $this->admin->nomPrenom);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/v1/references/satisfaction')
            && $r['total'] === 1 && (float) $r['moyenne'] === 5.0 && $r['pct_satisfaits'] === 100);

        $this->actingAs($sup)->get('/satisfaction/rapport.pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_sans_accord_l_avis_ne_part_pas_sur_le_site(): void
    {
        $id = DB::table('avis_satisfaction')->insertGetId([
            'user_id' => $this->admin->idUtilisateur, 'statut' => 'repondu', 'note' => 5, 'commentaire' => 'Bien',
            'accord_publication' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->compte('SupAdmin'))->from('/satisfaction')->post("/satisfaction/{$id}/publier")->assertSessionHas('error');
        Http::assertNothingSent();
    }
}
