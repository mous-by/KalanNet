<?php

namespace Tests\Integration;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Accès d'assistance d'un SupAdmin au compte d'un client. */
class AccesAssistanceTest extends IntegrationTestCase
{
    private function connecte(User $attendu, string $etape): void
    {
        $this->assertSame($attendu->idUtilisateur, auth()->id(), "$etape : compte connecté ".var_export(auth()->id(), true));
        $this->assertFalse(session()->has('assistance'), "$etape : aucun accès d'assistance ouvert");
    }

    private function ouvrir(User $superviseur, User $cible, string $motif = 'Le client ne voit pas ses bulletins')
    {
        // Comme à une vraie connexion : l'école du compte est en session (sauf SupAdmin).
        $session = $superviseur->droit === 'SupAdmin' ? [] : ['idEcole' => $superviseur->idEcole];

        return $this->actingAs($superviseur)->withSession($session)->post("/configuration/utilisateurs/{$cible->idUtilisateur}/assistance", ['motif' => $motif]);
    }

    public function test_ouverture_discrete_et_journal(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $derniereConnexion = $cible->last_login_at;
        $derniereActivite = $cible->last_activity;
        $notifications = DB::table('app_notifications')->where('type', 'acces_assistance')->count();

        $this->ouvrir($sup, $cible)->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($cible);
        $this->assertSame($sup->idUtilisateur, session('assistance.id_superviseur'));
        $this->assertSame(1, (int) session('idEcole'));
        $acces = DB::table('acces_assistance')->latest('id')->first();
        $this->assertSame([$sup->idUtilisateur, $cible->idUtilisateur, 1, 'Le client ne voit pas ses bulletins'], [(int) $acces->id_superviseur, (int) $acces->id_utilisateur, (int) $acces->id_ecole, $acces->motif]);
        $this->assertNull($acces->fin_at);
        $this->assertSame($notifications, DB::table('app_notifications')->where('type', 'acces_assistance')->count(), 'l\'école n\'est pas prévenue');

        $this->get('/eleves')->assertOk()->assertSee(__('assistance.revenir'));
        $this->assertEquals($derniereConnexion, $cible->fresh()->last_login_at, 'dernière connexion du client inchangée');
        $this->assertEquals($derniereActivite, $cible->fresh()->last_activity, 'dernière activité du client inchangée');
    }

    public function test_motif_obligatoire_et_refus(): void
    {
        $sup = $this->compte('SupAdmin');
        $admin = $this->compte('Admin', 1);

        $this->ouvrir($sup, $admin, 'court');
        $this->connecte($sup, 'étape 1');
        $this->assertSame(0, DB::table('acces_assistance')->where('id_utilisateur', $admin->idUtilisateur)->whereNull('fin_at')->count());

        // Un Admin ne peut pas ouvrir de compte.
        $enseignant = User::withoutGlobalScopes()->where('idEcole', 1)->where('droit', 'enseignant')->where('statut', 1)->first();
        $this->ouvrir($admin, $enseignant)->assertSessionHas('error');
        $this->connecte($admin, 'étape 2');

        // Ni un SupAdmin, ni un DAE / DCAP, ni un compte désactivé.
        foreach (['DAE', 'DCAP'] as $role) {
            $exclu = User::withoutGlobalScopes()->where('droit', $role)->first();
            if ($exclu) {
                $this->ouvrir($sup, $exclu)->assertSessionHas('error');
                $this->connecte($sup, 'étape 3');
            }
        }
        $enseignant->update(['statut' => 0]);
        $this->ouvrir($sup, $enseignant->fresh())->assertSessionHas('error');
        $this->connecte($sup, 'étape 4');
    }

    public function test_actions_interdites_pendant_l_acces(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $autre = User::withoutGlobalScopes()->where('idEcole', 1)->where('droit', '!=', 'Admin')->where('statut', 1)->first();
        $mdp = $cible->pwd;
        $this->ouvrir($sup, $cible);

        $this->put('/profil/mot-de-passe', ['current_password' => 'x', 'password' => 'Nouveau2026', 'password_confirmation' => 'Nouveau2026'])->assertRedirect(route('dashboard'));
        $this->post("/configuration/utilisateurs/{$autre->idUtilisateur}/mot-de-passe")->assertRedirect(route('dashboard'));
        $this->get("/configuration/utilisateurs/{$autre->idUtilisateur}/permissions")->assertRedirect(route('dashboard'));
        $this->delete("/configuration/utilisateurs/{$autre->idUtilisateur}")->assertRedirect(route('dashboard'));
        $this->assertSame($mdp, $cible->fresh()->pwd);
        $this->assertTrue(User::withoutGlobalScopes()->whereKey($autre->idUtilisateur)->exists());
        $this->assertAuthenticatedAs($cible);
    }

    public function test_retour_au_compte_fin_expiration_et_deconnexion(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);

        $this->ouvrir($sup, $cible);
        $this->post('/assistance/terminer')->assertRedirect(route('configuration.utilisateurs'));
        $this->assertAuthenticatedAs($sup);
        $this->assertFalse(session()->has('assistance'));
        $this->assertSame('termine', DB::table('acces_assistance')->latest('id')->value('fin_raison'));

        $this->ouvrir($sup, $cible);
        $this->travel(61)->minutes();
        $this->get('/eleves')->assertRedirect(route('configuration.utilisateurs'));
        $this->assertAuthenticatedAs($sup);
        $this->assertSame('expire', DB::table('acces_assistance')->latest('id')->value('fin_raison'));
        $this->travelBack();

        $this->ouvrir($sup, $cible);
        $this->post('/logout')->assertRedirect(route('configuration.utilisateurs'));
        $this->assertAuthenticatedAs($sup);
        $this->assertNotNull(DB::table('acces_assistance')->latest('id')->value('fin_at'));
    }

    public function test_compte_qui_doit_changer_son_mot_de_passe(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $cible->update(['doit_changer_mdp' => 1]);

        $this->ouvrir($sup, $cible->fresh());
        $this->get('/eleves')->assertOk();
        $this->assertSame(1, (int) $cible->fresh()->doit_changer_mdp, 'le client choisira lui-même son mot de passe');
    }

    public function test_journal_reserve_au_supadmin(): void
    {
        $sup = $this->compte('SupAdmin');
        $autreEcole = $this->autreEcole(1);
        DB::table('acces_assistance')->insert([
            ['id_superviseur' => $sup->idUtilisateur, 'id_utilisateur' => $sup->idUtilisateur, 'id_ecole' => 1, 'motif' => 'Motif école 1 test', 'ip' => '127.0.0.1', 'debut_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['id_superviseur' => $sup->idUtilisateur, 'id_utilisateur' => $sup->idUtilisateur, 'id_ecole' => $autreEcole, 'motif' => 'Motif autre école test', 'ip' => '127.0.0.1', 'debut_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $admin = $this->compte('Admin', 1);
        $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/assistance/journal')->assertForbidden();
        $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/configuration/utilisateurs')->assertDontSee(__('assistance.journal'));
        $this->actingAs($sup)->get('/assistance/journal')->assertOk()->assertSee('Motif école 1 test')->assertSee('Motif autre école test');
        // Pendant un accès d'assistance (compte de l'école), le journal n'est pas consultable.
        $this->ouvrir($sup, $admin);
        $this->get('/assistance/journal')->assertForbidden();

        $enseignant = User::withoutGlobalScopes()->where('idEcole', 1)->where('droit', 'enseignant')->where('statut', 1)->first();
        $this->actingAs($enseignant)->withSession(['idEcole' => 1])->get('/assistance/journal')->assertForbidden();
    }
}
