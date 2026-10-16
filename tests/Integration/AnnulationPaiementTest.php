<?php

namespace Tests\Integration;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Annuler un paiement de frais scolaires : caché à tous ; le SupAdmin l'accorde
 * à un Admin, qui peut à son tour l'accorder à son gestionnaire.
 */
class AnnulationPaiementTest extends IntegrationTestCase
{
    private int $permission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permission = (int) DB::table('permissions')->where('name', 'paiements_annuler')->value('id');
    }

    public function test_cachee_puis_accordee_par_le_supadmin_puis_transmise_au_gestionnaire(): void
    {
        $admin = $this->adminSansAnnulation(1);
        $gestionnaire = $this->gestionnaire(1);
        $enseignant = $this->compte('enseignant', 1);
        $superAdmin = $this->compte('SupAdmin');
        $paiement = $this->paiementValide(1);
        $session = ['idEcole' => 1];

        // 1. Personne ne la voit : ni bouton, ni case à cocher, et le serveur refuse.
        $this->actingAs($admin)->withSession($session)->get('/finances/paiements/historique')->assertOk()
            ->assertDontSee('id="annulerPaiementModal"', false)->assertDontSee('history-action-cancel"', false);
        $this->actingAs($admin)->withSession($session)->get("/configuration/utilisateurs/{$gestionnaire->idUtilisateur}/permissions")->assertOk()
            ->assertDontSee('value="'.$this->permission.'"', false);
        $this->actingAs($admin)->withSession($session)->delete("/finances/paiements/{$paiement->id_paiement}", ['motif_annulation' => 'Erreur de saisie'])->assertForbidden();
        $this->forgetGuards();
        $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson("/api/v1/finances/paiements/{$paiement->id_paiement}/cancel", ['motif_annulation' => 'Erreur de saisie'])->assertForbidden();
        $this->forgetGuards();

        // 2. Le SupAdmin l'accorde à l'Admin.
        $this->actingAs($superAdmin)->withSession($session)->put("/configuration/utilisateurs/{$admin->idUtilisateur}/permissions", [
            'permissions' => array_merge($this->permissionsDe($admin), [$this->permission]),
        ])->assertRedirect();
        $this->assertTrue($this->aLaPermission($admin));
        $this->flushSession();

        // 3. L'Admin la voit, et peut la donner à son gestionnaire (pas à un enseignant).
        $admin = $admin->fresh();
        $this->actingAs($admin)->withSession($session)->get('/finances/paiements/historique')->assertOk()
            ->assertSee('id="annulerPaiementModal"', false)->assertSee('history-action-cancel"', false);
        $this->actingAs($admin)->withSession($session)->put("/configuration/utilisateurs/{$gestionnaire->idUtilisateur}/permissions", [
            'permissions' => array_merge($this->permissionsDe($gestionnaire), [$this->permission]),
        ])->assertRedirect();
        $this->assertTrue($this->aLaPermission($gestionnaire), 'transmise au gestionnaire');
        $this->actingAs($admin)->withSession($session)->put("/configuration/utilisateurs/{$enseignant->idUtilisateur}/permissions", [
            'permissions' => array_merge($this->permissionsDe($enseignant), [$this->permission]),
        ]);
        $this->assertFalse($enseignant->fresh()->userHasPermission('paiements_annuler'), 'jamais pour un enseignant');

        // 4. Le gestionnaire annule : paiement « annulé », montant retiré de la caisse.
        $caisse = (float) DB::table('caisse')->where('id_caisse', $paiement->id_caisse)->value('montant_net');
        DB::table('caisse')->where('id_caisse', $paiement->id_caisse)->update(['montant_net' => max($caisse, 1e9)]);
        $avant = (float) DB::table('caisse')->where('id_caisse', $paiement->id_caisse)->value('montant_net');
        $this->flushSession();
        $this->actingAs($gestionnaire->fresh())->withSession($session)->from('/finances/paiements/historique')
            ->delete("/finances/paiements/{$paiement->id_paiement}", ['motif_annulation' => 'Paiement saisi deux fois'])
            ->assertRedirect('/finances/paiements/historique')->assertSessionHas('success');

        $annule = DB::table('paiement')->where('id_paiement', $paiement->id_paiement)->first();
        $this->assertSame('annule', $annule->statut);
        $this->assertSame('Paiement saisi deux fois', $annule->motif_annulation);
        $this->assertEqualsWithDelta($avant - (float) ($paiement->montant_paye ?? $paiement->montant), (float) DB::table('caisse')->where('id_caisse', $paiement->id_caisse)->value('montant_net'), 0.01);
    }

    public function test_nouveaux_comptes_sans_la_permission(): void
    {
        $admin = $this->compte('Admin', 1);
        DB::table('user_permission')->where('user_id', $admin->idUtilisateur)->delete();
        app(\App\Http\Controllers\ConfigurationController::class)->syncDefaultPermissions($admin, 1);

        $this->assertFalse($this->aLaPermission($admin));
        $this->assertTrue($admin->fresh()->userHasPermission('paiements_faire'), 'les autres permissions restent données');
    }

    /** Admin avec toutes les permissions, sauf l'annulation des paiements. */
    private function adminSansAnnulation(int $idEcole): User
    {
        $admin = $this->adminComplet($idEcole);
        DB::table('user_permission')->where('user_id', $admin->idUtilisateur)->where('permission_id', $this->permission)->delete();

        return $admin->fresh();
    }

    /** Gestionnaire de l'école (créé dans la transaction du test s'il n'y en a pas). */
    private function gestionnaire(int $idEcole): User
    {
        return User::withoutGlobalScopes()->where('droit', 'Gestionnaire')->where('statut', 1)->where('idEcole', $idEcole)->first()
            ?? User::withoutGlobalScopes()->create([
                'nomPrenom' => 'Gestionnaire Test', 'email' => 'gestionnaire.test@charge.kalannet.test', 'pwd' => bcrypt('x'),
                'droit' => 'Gestionnaire', 'idEcole' => $idEcole, 'statut' => 1,
            ]);
    }

    private function paiementValide(int $idEcole): object
    {
        $paiement = DB::table('paiement')->where('idEcole', $idEcole)->whereNotNull('id_caisse')
            ->where(fn ($q) => $q->whereNull('statut')->orWhere('statut', '!=', 'annule'))->first();
        if (!$paiement) {
            $this->markTestSkipped('Aucun paiement valide dans la base locale.');
        }

        return $paiement;
    }

    private function permissionsDe(User $user): array
    {
        return DB::table('user_permission')->where('user_id', $user->idUtilisateur)->pluck('permission_id')->map(fn ($id) => (int) $id)->all();
    }

    private function aLaPermission(User $user): bool
    {
        return DB::table('user_permission')->where('user_id', $user->idUtilisateur)->where('permission_id', $this->permission)->exists();
    }

    private function forgetGuards(): void
    {
        app('auth')->forgetGuards();
    }
}
