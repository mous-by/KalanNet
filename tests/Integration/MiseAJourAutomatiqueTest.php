<?php

namespace Tests\Integration;

use App\Support\VersionApp;
use Illuminate\Support\Facades\DB;

/** Les pages ouvertes se mettent à jour seules : version en ligne lisible partout, même en maintenance ou abonnement expiré. */
class MiseAJourAutomatiqueTest extends IntegrationTestCase
{
    public function test_version_en_ligne_et_page_qui_la_porte(): void
    {
        $version = VersionApp::actuelle();
        $this->assertNotSame('', $version);
        if (is_dir(base_path('.git'))) {
            $this->assertSame(substr(trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse HEAD')), 0, 12), $version, 'le commit déployé');
        }

        $reponse = $this->get('/version')->assertOk()->assertExactJson(['version' => $version]);
        $this->assertStringContainsString('no-store', (string) $reponse->headers->get('Cache-Control'));
        $this->assertEmpty($reponse->headers->getCookies(), 'aucune session ouverte pour cette vérification');

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $page = $this->get('/dashboard')->assertOk();
        $page->assertSee('<meta name="kalannet-version" content="'.$version.'"', false)->assertSee('mise-a-jour.js', false);
        $this->assertFileExists(public_path('assets/mon_js/mise-a-jour.js'));
    }

    public function test_lisible_en_maintenance_et_abonnement_expire(): void
    {
        $avant = DB::table('utilisateurs')->where('idUtilisateur', $this->adminComplet(1)->idUtilisateur)->value('last_activity');
        DB::table('maintenance_mode')->updateOrInsert(['id' => DB::table('maintenance_mode')->value('id') ?? 1], ['actif' => 1]);
        $this->assertSame(1, (int) DB::table('maintenance_mode')->value('actif'));
        DB::table('abonnements')->where('ecole_id', 1)->update(['statut' => 'expire']);
        $this->actingAs($this->adminComplet(1));
        $this->get('/version')->assertOk()->assertJsonStructure(['version']);
        $this->assertSame($avant, DB::table('utilisateurs')->where('idUtilisateur', $this->adminComplet(1)->idUtilisateur)->value('last_activity'), 'pas compté comme une activité');
    }
}
