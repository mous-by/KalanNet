<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Audit 2026-10 : un DAE / DCAP ne cible par ecole_id qu'une école de son territoire. */
class TerritoireEcoleIdTest extends IntegrationTestCase
{
    public function test_dae_limite_a_son_academie(): void
    {
        $dae = $this->compte('DAE');
        $dans = DB::table('ecole')->where('id_academie', $dae->id_academie)->value('idEcole');
        $hors = DB::table('ecole')->where('id_academie', '!=', $dae->id_academie)->value('idEcole');
        $jeton = $dae->createToken('test')->plainTextToken;

        $this->withToken($jeton)->getJson("/api/v1/dashboard?ecole_id=$hors")->assertForbidden();
        $this->withToken($jeton)->getJson('/api/v1/dashboard?ecole_id=99999999')->assertForbidden();
        $this->withToken($jeton)->getJson("/api/v1/dashboard?ecole_id=$dans")->assertOk();
        $this->withToken($jeton)->getJson('/api/v1/dashboard')->assertOk();
    }

    public function test_dcap_limite_a_son_cap(): void
    {
        $dcap = $this->compte('DCAP');
        $dans = DB::table('ecole')->where('id_cap', $dcap->id_cap)->value('idEcole');
        $hors = DB::table('ecole')->where('id_cap', '!=', $dcap->id_cap)->value('idEcole');
        $jeton = $dcap->createToken('test')->plainTextToken;

        $this->withToken($jeton)->getJson("/api/v1/dashboard?ecole_id=$hors")->assertForbidden();
        $this->withToken($jeton)->getJson("/api/v1/dashboard?ecole_id=$dans")->assertOk();
    }

    public function test_supadmin_peut_cibler_toute_ecole(): void
    {
        $sup = $this->compte('SupAdmin');
        $ecole = DB::table('ecole')->value('idEcole');
        $this->withToken($sup->createToken('test')->plainTextToken)->getJson("/api/v1/dashboard?ecole_id=$ecole")->assertOk();
    }
}
