<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\Planification;
use Illuminate\Support\Facades\DB;

class SchoolYearManagementTest extends IntegrationTestCase
{
    public function test_planification_form_includes_previous_school_years(): void
    {
        $schoolId = (int) $this->compte('Admin')->idEcole;
        $admin = $this->adminComplet($schoolId);
        $previousYear = $this->createSchoolYear($schoolId, '2010-2011', '2010-09-01', '2011-08-31');

        $this->actingAs($admin)->withSession(['idEcole' => $schoolId])
            ->get('/finances/planifications/ajouter')
            ->assertOk()
            ->assertSee('2010-2011')
            ->assertSee('value="'.$previousYear->id_anneeScolaire.'"', false);
    }

    public function test_school_years_are_listed_after_the_shared_automatic_year(): void
    {
        $schoolId = (int) $this->compte('Admin')->idEcole;
        $admin = $this->adminComplet($schoolId);
        $this->createSchoolYear($schoolId, '2012-2013', '2012-09-01', '2013-08-31');

        $response = $this->actingAs($admin)->withSession(['idEcole' => $schoolId])->get('/configuration/annees');
        $response->assertOk()
            ->assertSee('2012-2013')
            ->assertSee(__('configuration.annees_origine_auto'));

        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/s', $response->getContent(), $rows);
        $automaticRow = null;
        $schoolYearRow = null;
        foreach ($rows[1] as $index => $row) {
            if (str_contains($row, __('configuration.annees_origine_auto'))) {
                $automaticRow ??= $index;
            }
            if (str_contains($row, '2012-2013')) {
                $schoolYearRow = $index;
            }
        }

        $this->assertNotNull($automaticRow);
        $this->assertNotNull($schoolYearRow);
        $this->assertLessThan($schoolYearRow, $automaticRow);
    }

    public function test_only_unused_school_years_can_be_deleted(): void
    {
        $schoolId = (int) $this->compte('Admin')->idEcole;
        $superAdmin = $this->compte('SupAdmin');
        $unusedYear = $this->createSchoolYear($schoolId, '2014-2015', '2014-09-01', '2015-08-31');

        $this->actingAs($superAdmin)->withSession(['idEcole' => $schoolId])
            ->delete(route('configuration.annees.destroy', $unusedYear->id_anneeScolaire))
            ->assertRedirect(route('configuration.annees'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('anneescolaire', ['id_anneeScolaire' => $unusedYear->id_anneeScolaire]);

        $automaticYear = AnneeScolaire::withoutGlobalScopes()->whereNull('id_ecole')->firstOrFail();
        $this->actingAs($superAdmin)->withSession(['idEcole' => $schoolId])
            ->delete(route('configuration.annees.destroy', $automaticYear->id_anneeScolaire))
            ->assertSessionHas('error', __('configuration.annees_delete_auto_error'));
        $this->assertDatabaseHas('anneescolaire', ['id_anneeScolaire' => $automaticYear->id_anneeScolaire]);

        $classId = DB::table('classe')->where('idEcole', $schoolId)->value('id_classe');
        if (!$classId) {
            $this->markTestSkipped('Aucune classe disponible pour tester une année scolaire utilisée.');
        }

        $usedYear = $this->createSchoolYear($schoolId, '2016-2017', '2016-09-01', '2017-08-31');
        Planification::create([
            'motif' => 'cooperative',
            'id_classe' => $classId,
            'id_annee' => $usedYear->id_anneeScolaire,
            'date_debut' => '2016-09-01',
            'date_fin' => '2017-08-31',
            'montant_planification' => 1000,
        ]);

        $this->actingAs($superAdmin)->withSession(['idEcole' => $schoolId])
            ->delete(route('configuration.annees.destroy', $usedYear->id_anneeScolaire))
            ->assertSessionHas('error', __('configuration.annees_delete_used_error', [
                'usages' => 'planification.id_annee (1)',
            ]));
        $this->assertDatabaseHas('anneescolaire', ['id_anneeScolaire' => $usedYear->id_anneeScolaire]);
    }

    public function test_school_admin_needs_explicit_permission_to_create_school_years(): void
    {
        $schoolId = (int) $this->compte('Admin')->idEcole;
        $admin = $this->adminComplet($schoolId);
        $permissionId = DB::table('permissions')->where('name', 'annees_scolaires_creation')->value('id');
        $this->assertNotNull($permissionId, 'La permission doit être enregistrée dans le catalogue.');

        DB::table('user_permission')
            ->where('user_id', $admin->idUtilisateur)
            ->where('permission_id', $permissionId)
            ->delete();
        $admin = $admin->fresh();
        $session = ['idEcole' => $schoolId];

        $this->actingAs($admin)->withSession($session)->get('/configuration/annees')
            ->assertOk()
            ->assertDontSee('id="addNewAnneeModal"', false)
            ->assertDontSee('data-bs-target="#addNewAnneeModal"', false);

        $anneeData = [
            'annee' => '2030-2031',
            'date_debut' => '2030-09-01',
            'date_fin' => '2031-08-31',
        ];
        $this->actingAs($admin)->withSession($session)
            ->post(route('configuration.annees.store'), $anneeData)
            ->assertForbidden();

        DB::table('user_permission')->insert([
            'user_id' => $admin->idUtilisateur,
            'permission_id' => $permissionId,
        ]);
        $admin = $admin->fresh();

        $this->actingAs($admin)->withSession($session)->get('/configuration/annees')
            ->assertOk()
            ->assertSee('id="addNewAnneeModal"', false)
            ->assertSee('data-bs-target="#addNewAnneeModal"', false);

        $this->actingAs($admin)->withSession($session)
            ->post(route('configuration.annees.store'), $anneeData)
            ->assertRedirect(route('configuration.annees'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('anneescolaire', [
            'annee' => '2030-2031',
            'id_ecole' => $schoolId,
        ]);
    }

    private function createSchoolYear(int $schoolId, string $label, string $start, string $end): AnneeScolaire
    {
        return AnneeScolaire::withoutGlobalScopes()->create([
            'annee' => $label,
            'date_debut' => $start,
            'date_fin' => $end,
            'id_ecole' => $schoolId,
        ]);
    }
}