<?php

namespace Tests\Integration;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/** Audit 2026-10 : le choix de l'école ne doit jamais connecter sans mot de passe vérifié. */
class SelectionEcoleTest extends IntegrationTestCase
{
    private function compteDeuxEcoles(): array
    {
        $a = $this->compte('Admin');
        $a->update(['email' => 'multi.test@exemple.ml', 'pwd' => Hash::make('Secret2026'), 'statut' => 1]);
        $b = $a->replicate();
        $b->idEcole = $this->autreEcole((int) $a->idEcole);
        $b->save();

        return [$a, $b];
    }

    public function test_web_select_school_sans_login_ne_connecte_pas(): void
    {
        $admin = $this->compte('Admin');
        $this->post('/login/select-school', ['idUtilisateur' => $admin->idUtilisateur, 'idEcole' => $admin->idEcole])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_api_select_school_sans_jeton_est_refuse(): void
    {
        $admin = $this->compte('Admin');
        $this->postJson('/api/v1/auth/select-school', ['id_utilisateur' => $admin->idUtilisateur, 'id_ecole' => $admin->idEcole])
            ->assertStatus(422)->assertJsonMissing(['token']);
    }

    public function test_web_parcours_normal_et_compte_non_verifie(): void
    {
        [, $b] = $this->compteDeuxEcoles();
        $autre = User::withoutGlobalScopes()->where('statut', 1)->where('email', '!=', 'multi.test@exemple.ml')->first();

        $this->post('/login', ['identifier' => 'multi.test@exemple.ml', 'pwd' => 'Secret2026'])->assertOk();
        $this->post('/login/select-school', ['idUtilisateur' => $autre->idUtilisateur, 'idEcole' => $autre->idEcole]);
        $this->assertGuest();

        $this->post('/login', ['identifier' => 'multi.test@exemple.ml', 'pwd' => 'Secret2026'])->assertOk();
        $this->post('/login/select-school', ['idUtilisateur' => $b->idUtilisateur, 'idEcole' => $b->idEcole]);
        $this->assertAuthenticatedAs($b);
    }

    public function test_api_jeton_de_selection(): void
    {
        [$a, $b] = $this->compteDeuxEcoles();
        $comptes = collect($this->postJson('/api/v1/auth/login', ['identifier' => 'multi.test@exemple.ml', 'pwd' => 'Secret2026'])
            ->assertOk()->assertJsonPath('requires_school_selection', true)->json('accounts'))->keyBy('id_utilisateur');

        $this->postJson('/api/v1/auth/select-school', ['id_utilisateur' => $b->idUtilisateur, 'id_ecole' => $b->idEcole, 'selection_token' => $comptes[$a->idUtilisateur]['selection_token']])
            ->assertStatus(422);
        $this->postJson('/api/v1/auth/select-school', ['id_utilisateur' => $b->idUtilisateur, 'id_ecole' => $b->idEcole, 'selection_token' => 'faux'])
            ->assertStatus(422);
        $this->postJson('/api/v1/auth/select-school', ['id_utilisateur' => $b->idUtilisateur, 'id_ecole' => $b->idEcole, 'selection_token' => $comptes[$b->idUtilisateur]['selection_token']])
            ->assertOk()->assertJsonStructure(['token']);

        $this->travel(11)->minutes();
        $this->postJson('/api/v1/auth/select-school', ['id_utilisateur' => $b->idUtilisateur, 'id_ecole' => $b->idEcole, 'selection_token' => $comptes[$b->idUtilisateur]['selection_token']])
            ->assertStatus(422);
    }
}
