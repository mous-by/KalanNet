<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Configuration → Utilisateurs : le SupAdmin voit tous les comptes, même avec une école sélectionnée. */
class UtilisateursSupAdminTest extends IntegrationTestCase
{
    public function test_tous_les_roles_quelle_que_soit_l_ecole_selectionnee(): void
    {
        $superAdmin = $this->compte('SupAdmin');
        $attendu = DB::table('utilisateurs')->where('droit', '!=', 'SupAdmin')->select('droit', DB::raw('count(*) n'))->groupBy('droit')->pluck('n', 'droit');
        $autreEcole = (int) DB::table('ecole')->where('idEcole', '!=', 1)->value('idEcole');

        foreach ([1, $autreEcole] as $ecole) {
            $this->flushSession();
            $html = $this->actingAs($superAdmin)->withSession(['idEcole' => $ecole])->get('/configuration/utilisateurs')->assertOk()->getContent();
            preg_match_all('#data-bs-target="\#(\w+)".*?<span class="badge theme-icon-soft ms-1">(\d+)#s', $html, $m);
            $onglets = array_combine($m[1], array_map('intval', $m[2]));
            $this->assertSame((int) ($attendu['enseignant'] ?? 0), $onglets['enseignants'] ?? null, "enseignants, école $ecole sélectionnée");
            $this->assertSame((int) ($attendu['parent'] ?? 0), $onglets['parents'] ?? null);
            $this->assertSame((int) ($attendu['DAE'] ?? 0), $onglets['dae'] ?? null, 'les DAE n\'ont pas d\'école : toujours visibles');
        }

        // Un compte d'une autre école que celle sélectionnée reste modifiable.
        $enseignant = DB::table('utilisateurs')->where('droit', 'enseignant')->first(['idUtilisateur', 'idEcole']);
        if ($enseignant) {
            $this->actingAs($superAdmin)->withSession(['idEcole' => $autreEcole])
                ->get("/configuration/utilisateurs/{$enseignant->idUtilisateur}/permissions")->assertOk();
        }
    }

    public function test_un_admin_ne_voit_que_son_ecole(): void
    {
        $admin = $this->adminComplet(1);
        $html = $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/configuration/utilisateurs')->assertOk()->getContent();
        $this->assertStringNotContainsString('@charge.kalannet.test', $html, 'aucun compte d\'une autre école');
    }
}
