<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Bienvenue de l'Admin d'une nouvelle école : une seule fois, avec les seuls tarifs de son statut. */
class BienvenueAdminTest extends IntegrationTestCase
{
    public function test_une_seule_fois_et_tarifs_selon_le_statut(): void
    {
        $admin = $this->adminComplet(1);
        foreach (['prive' => ['75 000 F CFA', '100 000 F CFA', 'Offre établissement privé'], 'public' => ['15 000 F CFA', '25 000 F CFA', 'Offre établissement public']] as $statut => $attendus) {
            DB::table('ecole')->where('idEcole', 1)->update(['statut' => $statut]);
            DB::table('utilisateurs')->where('idUtilisateur', $admin->idUtilisateur)->update(['bienvenue_vue_at' => null, 'doit_changer_mdp' => 0]);
            $this->flushSession();

            $html = $this->actingAs($admin->fresh())->withSession(['idEcole' => 1])->get('/dashboard')->assertOk()->getContent();
            $this->assertStringContainsString('id="bienvenueModal"', $html);
            foreach ($attendus as $texte) {
                $this->assertStringContainsString($texte, $html);
            }
            $autre = $statut === 'prive' ? 'Offre établissement public' : 'Offre établissement privé';
            $this->assertStringNotContainsString($autre, $html, 'une école ne voit que les tarifs de son statut');

            // « Commencer » : plus jamais affiché.
            $this->actingAs($admin->fresh())->withSession(['idEcole' => 1])->postJson('/bienvenue/vue')->assertOk();
            $this->assertNotNull(DB::table('utilisateurs')->where('idUtilisateur', $admin->idUtilisateur)->value('bienvenue_vue_at'));
            $this->actingAs($admin->fresh())->withSession(['idEcole' => 1])->get('/dashboard')->assertOk()->assertDontSee('id="bienvenueModal"', false);
        }
    }

    public function test_ni_pour_les_autres_roles_ni_pendant_une_assistance(): void
    {
        $admin = $this->adminComplet(1);
        DB::table('utilisateurs')->where('idUtilisateur', $admin->idUtilisateur)->update(['bienvenue_vue_at' => null, 'doit_changer_mdp' => 0]);

        // Le support connecté au compte (accès d'assistance) ne voit ni ne consomme le message.
        $this->actingAs($admin->fresh())->withSession(['idEcole' => 1, 'assistance' => ['id' => 1, 'expire' => now()->addHour()->timestamp]])
            ->get('/dashboard')->assertDontSee('id="bienvenueModal"', false);
        $this->actingAs($admin->fresh())->withSession(['idEcole' => 1, 'assistance' => ['id' => 1, 'expire' => now()->addHour()->timestamp]])
            ->postJson('/bienvenue/vue')->assertOk();
        $this->assertNull(DB::table('utilisateurs')->where('idUtilisateur', $admin->idUtilisateur)->value('bienvenue_vue_at'));

        $enseignant = $this->compte('enseignant', 1);
        DB::table('utilisateurs')->where('idUtilisateur', $enseignant->idUtilisateur)->update(['bienvenue_vue_at' => null, 'doit_changer_mdp' => 0]);
        $this->flushSession();
        $this->actingAs($enseignant->fresh())->withSession(['idEcole' => 1])->get('/dashboard')->assertDontSee('id="bienvenueModal"', false);
    }

    /** Frais d'adhésion réglés par le SupAdmin (page Abonnements) : repris dans la bienvenue ; un Admin ne peut pas les changer. */
    public function test_frais_d_adhesion_modifiables_par_le_supadmin(): void
    {
        $superAdmin = $this->compte('SupAdmin');
        $page = $this->actingAs($superAdmin)->withSession(['idEcole' => 1])->get('/abonnements')->assertOk()->getContent();
        $this->assertStringContainsString('name="public"', $page);
        $this->assertStringContainsString('name="prive"', $page);

        $this->actingAs($superAdmin)->withSession(['idEcole' => 1])->put('/abonnements/frais-adhesion', ['public' => 20000, 'prive' => 60000])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertEquals(60000, DB::table('frais_adhesion')->where('statut', 'prive')->value('montant'));

        // La bienvenue d'une nouvelle école privée annonce 60 000 + l'abonnement annuel.
        $admin = $this->adminComplet(1);
        DB::table('ecole')->where('idEcole', 1)->update(['statut' => 'prive']);
        DB::table('utilisateurs')->where('idUtilisateur', $admin->idUtilisateur)->update(['bienvenue_vue_at' => null, 'doit_changer_mdp' => 0]);
        $annuel = \App\Support\TarifsBienvenue::pour((new \App\Models\Ecole())->forceFill(['statut' => 'prive']))['annuel'];
        $this->flushSession();
        $html = $this->actingAs($admin->fresh())->withSession(['idEcole' => 1])->get('/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString('60 000 F CFA', $html);
        $this->assertStringContainsString(\App\Support\TarifsBienvenue::montant(60000 + $annuel), $html);

        $this->actingAs($admin->fresh())->withSession(['idEcole' => 1])->put('/abonnements/frais-adhesion', ['public' => 1, 'prive' => 1])->assertForbidden();
    }
}
