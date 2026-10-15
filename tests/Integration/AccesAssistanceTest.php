<?php

namespace Tests\Integration;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Accès d'assistance d'un SupAdmin au compte d'un client, avec l'accord d'un Admin de l'école. */
class AccesAssistanceTest extends IntegrationTestCase
{
    private function connecte(User $attendu, string $etape): void
    {
        $this->assertSame($attendu->idUtilisateur, auth()->id(), "$etape : compte connecté ".var_export(auth()->id(), true));
    }

    private function demander(User $superviseur, User $cible, string $motif = 'Le client ne voit pas ses bulletins')
    {
        // Comme à une vraie connexion : l'école du compte est en session (sauf SupAdmin).
        $session = $superviseur->droit === 'SupAdmin' ? [] : ['idEcole' => $superviseur->idEcole];

        return $this->actingAs($superviseur)->withSession($session)->post("/configuration/utilisateurs/{$cible->idUtilisateur}/assistance", ['motif' => $motif]);
    }

    private function derniereDemande(): object
    {
        return DB::table('acces_assistance')->latest('id')->first();
    }

    private function repondre(User $admin, int $id, string $decision)
    {
        return $this->actingAs($admin)->withSession(['idEcole' => $admin->idEcole])->postJson("/assistance/$id/repondre", ['decision' => $decision]);
    }

    /** Demande, accord de l'Admin de l'école, puis entrée dans le compte. */
    private function entrer(User $sup, User $cible): int
    {
        $this->demander($sup, $cible);
        $id = (int) $this->derniereDemande()->id;
        $this->repondre($this->compte('Admin', (int) $cible->idEcole), $id, 'accepter')->assertOk();
        $this->actingAs($sup)->post("/assistance/$id/ouvrir")->assertRedirect(route('dashboard'));

        return $id;
    }

    public function test_demande_accord_et_entree_dans_le_compte(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $derniereConnexion = $cible->last_login_at;
        $notifications = DB::table('app_notifications')->where('type', 'acces_assistance_demande')->count();

        $reponse = $this->demander($sup, $cible);
        $demande = $this->derniereDemande();
        $reponse->assertRedirect(route('assistance.attente', $demande->id));
        $this->connecte($sup, 'après la demande, toujours SupAdmin');
        $this->assertSame('en_attente', $demande->statut);
        $this->assertGreaterThan($notifications, DB::table('app_notifications')->where('type', 'acces_assistance_demande')->count(), 'Admins prévenus');
        $this->actingAs($sup)->getJson("/assistance/{$demande->id}/statut")->assertJsonPath('statut', 'en_attente');

        // Sans accord, impossible d'entrer.
        $this->actingAs($sup)->post("/assistance/{$demande->id}/ouvrir")->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'sans accord');

        // L'Admin de l'école voit la demande et l'accepte.
        $admin = $this->compte('Admin', 1);
        $this->actingAs($admin)->withSession(['idEcole' => 1])->getJson('/assistance/demande')->assertJsonPath('demande.id', $demande->id);
        $this->repondre($admin, $demande->id, 'accepter')->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(['accepte', $admin->idUtilisateur], [$this->derniereDemande()->statut, (int) $this->derniereDemande()->decide_par]);

        $this->actingAs($sup)->getJson("/assistance/{$demande->id}/statut")->assertJsonPath('statut', 'accepte');
        $this->actingAs($sup)->post("/assistance/{$demande->id}/ouvrir")->assertRedirect(route('dashboard'));
        $this->connecte($cible, 'après accord');
        $this->assertSame('en_cours', $this->derniereDemande()->statut);
        $this->get('/eleves')->assertOk()->assertSee(__('assistance.revenir'));
        $this->assertEquals($derniereConnexion, $cible->fresh()->last_login_at, 'dernière connexion du client inchangée');
    }

    public function test_refus_expiration_et_mauvaise_ecole(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $admin = $this->compte('Admin', 1);

        // Refus.
        $this->demander($sup, $cible);
        $id = (int) $this->derniereDemande()->id;
        $this->repondre($admin, $id, 'refuser')->assertOk();
        $this->actingAs($sup)->post("/assistance/$id/ouvrir")->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'après refus');
        $this->assertSame('refuse', $this->derniereDemande()->statut);

        // L'Admin d'une autre école ne peut pas accepter.
        $this->demander($sup, $cible);
        $id = (int) $this->derniereDemande()->id;
        $autreAdmin = User::withoutGlobalScopes()->where('droit', 'Admin')->where('statut', 1)->where('idEcole', '!=', 1)->first();
        if ($autreAdmin) {
            $this->repondre($autreAdmin, $id, 'accepter')->assertStatus(409);
            $this->assertSame('en_attente', $this->derniereDemande()->statut);
        }

        // Sans réponse en 2 minutes : la demande expire, une réponse tardive est refusée.
        $this->travel(121)->seconds();
        $this->actingAs($sup)->getJson("/assistance/$id/statut")->assertJsonPath('statut', 'expire');
        $this->repondre($admin, $id, 'accepter')->assertStatus(409);
        $this->actingAs($sup)->post("/assistance/$id/ouvrir")->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'après expiration');
        $this->travelBack();
    }

    public function test_une_seule_entree_et_delai_apres_accord(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);

        $id = $this->entrer($sup, $cible);
        $this->post('/assistance/terminer');
        $this->connecte($sup, 'retour');
        $this->actingAs($sup)->post("/assistance/$id/ouvrir")->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'deuxième entrée refusée');

        // Accord donné, mais entrée plus de 5 minutes après.
        $this->demander($sup, $cible);
        $id = (int) $this->derniereDemande()->id;
        $this->repondre($this->compte('Admin', 1), $id, 'accepter')->assertOk();
        $this->travel(6)->minutes();
        $this->actingAs($sup)->post("/assistance/$id/ouvrir")->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'accord trop ancien');
        $this->travelBack();
    }

    /** Le bouton de la liste des utilisateurs envoie bien une demande (pas l'entrée directe). */
    public function test_bouton_de_la_liste_envoie_la_demande(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $html = $this->actingAs($sup)->get('/configuration/utilisateurs?idEcole=1')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#data-url="[^"]*/configuration/utilisateurs/'.$cible->idUtilisateur.'/assistance"#', $html, 'adresse du bouton');
        preg_match('#data-url="([^"]*/configuration/utilisateurs/'.$cible->idUtilisateur.'/assistance)"#', $html, $m);

        $reponse = $this->actingAs($sup)->post(parse_url($m[1], PHP_URL_PATH), ['motif' => 'Demande depuis le bouton']);
        $demande = $this->derniereDemande();
        $reponse->assertRedirect(route('assistance.attente', $demande->id))->assertSessionHas('success');
        $this->assertSame(['en_attente', $cible->idUtilisateur], [$demande->statut, (int) $demande->id_utilisateur]);
        $this->actingAs($sup)->get(route('assistance.attente', $demande->id, false))->assertOk()->assertSee(__('assistance.demande_envoyee', ['nom' => $cible->nomPrenom]));
    }

    /** Cas réel : l'Admin accepte dans le même navigateur, ce qui remplace la session SupAdmin. */
    public function test_meme_navigateur_message_clair_sans_404(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $this->demander($sup, $cible);
        $id = (int) $this->derniereDemande()->id;

        $admin = $this->compte('Admin', 1);
        $this->repondre($admin, $id, 'accepter')->assertOk();
        // La page d'attente (même navigateur) interroge le statut puis tente d'entrer : l'Admin est connecté.
        $this->getJson("/assistance/$id/statut")->assertStatus(403)->assertJsonPath('statut', 'autre_compte');
        $this->post("/assistance/$id/ouvrir")->assertRedirect(route('dashboard'))->assertSessionHas('error', __('assistance.autre_compte'));
        $this->connecte($admin, 'l\'Admin reste dans son propre compte');
        $this->assertSame('accepte', $this->derniereDemande()->statut, 'la demande reste utilisable par le SupAdmin');

        // Depuis sa propre session, le SupAdmin peut encore entrer.
        $this->actingAs($sup)->post("/assistance/$id/ouvrir")->assertRedirect(route('dashboard'));
        $this->connecte($cible, 'SupAdmin entré dans le compte');
    }

    public function test_motif_obligatoire_et_refus(): void
    {
        $sup = $this->compte('SupAdmin');
        $admin = $this->compte('Admin', 1);
        $avant = DB::table('acces_assistance')->count();

        $this->demander($sup, $admin, 'court');
        $this->connecte($sup, 'motif trop court');

        $enseignant = User::withoutGlobalScopes()->where('idEcole', 1)->where('droit', 'enseignant')->where('statut', 1)->first();
        $this->demander($admin, $enseignant)->assertSessionHas('error');
        $this->connecte($admin, 'un Admin ne peut pas demander');

        foreach (['DAE', 'DCAP'] as $role) {
            if ($exclu = User::withoutGlobalScopes()->where('droit', $role)->first()) {
                $this->demander($sup, $exclu)->assertSessionHas('error');
            }
        }
        $enseignant->update(['statut' => 0]);
        $this->demander($sup, $enseignant->fresh())->assertSessionHas('error');

        // École sans Admin actif : personne ne peut accepter.
        User::withoutGlobalScopes()->where('idEcole', 1)->where('droit', 'Admin')->update(['statut' => 0]);
        $gestionnaire = User::withoutGlobalScopes()->where('idEcole', 1)->where('droit', '!=', 'Admin')->where('statut', 1)->first();
        $this->demander($sup, $gestionnaire)->assertSessionHas('error', __('assistance.refus_sans_admin'));

        $this->assertSame($avant, DB::table('acces_assistance')->count(), 'aucune demande enregistrée');
    }

    public function test_actions_interdites_pendant_l_acces(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);
        $autre = User::withoutGlobalScopes()->where('idEcole', 1)->where('droit', '!=', 'Admin')->where('statut', 1)->first();
        $mdp = $cible->pwd;
        $this->entrer($sup, $cible);

        $this->put('/profil/mot-de-passe', ['current_password' => 'x', 'password' => 'Nouveau2026', 'password_confirmation' => 'Nouveau2026'])->assertRedirect(route('dashboard'));
        $this->post("/configuration/utilisateurs/{$autre->idUtilisateur}/mot-de-passe")->assertRedirect(route('dashboard'));
        $this->get("/configuration/utilisateurs/{$autre->idUtilisateur}/permissions")->assertRedirect(route('dashboard'));
        $this->delete("/configuration/utilisateurs/{$autre->idUtilisateur}")->assertRedirect(route('dashboard'));
        $this->post("/configuration/utilisateurs/{$autre->idUtilisateur}/assistance", ['motif' => 'Une autre demande ici'])->assertRedirect(route('dashboard'));
        // Le compte ouvert (un Admin) ne voit pas les demandes d'assistance.
        $this->getJson('/assistance/demande')->assertJsonPath('demande', null);
        $this->assertSame($mdp, $cible->fresh()->pwd);
        $this->assertTrue(User::withoutGlobalScopes()->whereKey($autre->idUtilisateur)->exists());
        $this->connecte($cible, 'toujours dans le compte du client');
    }

    public function test_retour_au_compte_fin_expiration_et_deconnexion(): void
    {
        $sup = $this->compte('SupAdmin');
        $cible = $this->compte('Admin', 1);

        $this->entrer($sup, $cible);
        $this->post('/assistance/terminer')->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'terminer');
        $this->assertSame(['termine', 'termine'], [$this->derniereDemande()->statut, $this->derniereDemande()->fin_raison]);

        $this->entrer($sup, $cible);
        $this->travel(61)->minutes();
        $this->get('/eleves')->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'expiration');
        $this->assertSame('expire', $this->derniereDemande()->fin_raison);
        $this->travelBack();

        $this->entrer($sup, $cible);
        $this->post('/logout')->assertRedirect(route('configuration.utilisateurs'));
        $this->connecte($sup, 'déconnexion');
        $this->assertNotNull($this->derniereDemande()->fin_at);
    }

    public function test_compte_qui_doit_changer_son_mot_de_passe(): void
    {
        $sup = $this->compte('SupAdmin');
        // Un compte de l'école (pas l'Admin qui accepte la demande).
        $cible = User::withoutGlobalScopes()->where('idEcole', 1)->whereIn('droit', ['enseignant', 'Gestionnaire'])->where('statut', 1)->first();
        $cible->update(['doit_changer_mdp' => 1]);

        $this->entrer($sup, $cible->fresh());
        $this->get('/dashboard')->assertOk(); // pas renvoyé vers le changement de mot de passe
        $this->assertSame(1, (int) $cible->fresh()->doit_changer_mdp, 'le client choisira lui-même son mot de passe');
    }

    public function test_journal_reserve_au_supadmin(): void
    {
        $sup = $this->compte('SupAdmin');
        DB::table('acces_assistance')->insert(['id_superviseur' => $sup->idUtilisateur, 'id_utilisateur' => $sup->idUtilisateur, 'id_ecole' => 1, 'motif' => 'Motif journal test', 'statut' => 'termine', 'ip' => '127.0.0.1', 'debut_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $admin = $this->compte('Admin', 1);
        $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/assistance/journal')->assertForbidden();
        $this->actingAs($sup)->get('/assistance/journal')->assertOk()->assertSee('Motif journal test');
    }
}
