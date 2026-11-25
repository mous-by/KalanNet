<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/** Page de connexion en français par défaut, sans effacer la langue habituelle de l'utilisateur. */
class LangueConnexionTest extends IntegrationTestCase
{
    private \App\Models\User $compte;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->compte = $this->compte('Admin', 1);
        DB::table('utilisateurs')->where('idUtilisateur', $this->compte->idUtilisateur)->update([
            'pwd' => Hash::make('Secret-2026'), 'email' => 'langue'.uniqid().'@ecole.ml', 'statut' => 1,
            'doit_changer_mdp' => 0, 'mdp_temporaire_expire_at' => null, 'locale_preference' => 'ar',
        ]);
        $this->compte = $this->compte->fresh();
    }

    public function test_la_page_de_connexion_est_en_francais_meme_si_l_appareil_a_retenu_une_autre_langue(): void
    {
        $this->withCookie('locale', 'ar')->get('/login')
            ->assertOk()
            ->assertSee('<html lang="fr"', false);
    }

    public function test_une_langue_choisie_sur_la_page_de_connexion_est_gardee(): void
    {
        $this->from('/login')->post('/language/en')->assertRedirect('/login');

        $this->get('/login')->assertSee('<html lang="en"', false);
    }

    public function test_se_connecter_sans_choisir_de_langue_garde_la_langue_de_l_utilisateur(): void
    {
        $this->get('/login');
        $this->post('/login', ['identifier' => $this->compte->email, 'pwd' => 'Secret-2026'])->assertRedirect();

        $this->assertAuthenticatedAs($this->compte);
        $this->assertSame('ar', $this->compte->fresh()->locale_preference);
    }

    public function test_choisir_une_langue_avant_de_se_connecter_devient_la_preference(): void
    {
        $this->from('/login')->post('/language/en');
        $this->post('/login', ['identifier' => $this->compte->email, 'pwd' => 'Secret-2026'])->assertRedirect();

        $this->assertSame('en', $this->compte->fresh()->locale_preference);
    }
}
