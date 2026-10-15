<?php

namespace Tests\Integration;

use App\Support\GrilleEmploiDuTemps;
use Illuminate\Support\Facades\DB;

/**
 * Horaires des lignes et récréations de l'emploi du temps : enregistrés en base,
 * donc conservés après déconnexion, sur un autre appareil et sur le mobile.
 */
class EmploiDuTempsGrilleTest extends IntegrationTestCase
{
    private int $classe;

    private int $annee = 2;

    private array $matieres;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classe = (int) DB::table('classe')->where('idEcole', 1)->whereExists(fn ($q) => $q->from('ligneclasse')->whereColumn('ligneclasse.id_classe', 'classe.id_classe'))->value('id_classe');
        $this->matieres = DB::table('ligneclasse')->where('id_classe', $this->classe)->limit(3)->pluck('id_matiere')->all();
        DB::table('emploi_du_temps')->where('id_classe', $this->classe)->where('id_annee_scolaire', $this->annee)->delete();
        DB::table('emploi_du_temps_grilles')->where('id_classe', $this->classe)->delete();
    }

    public function test_horaires_modifies_conserves_apres_deconnexion(): void
    {
        $admin = $this->adminComplet(1);
        [$m1, $m2, $m3] = $this->matieres;
        $session = ['idEcole' => 1, 'timetable_id_classe' => $this->classe, 'timetable_id_annee' => $this->annee];

        $this->actingAs($admin)->withSession($session)->post('/pedagogie/timetable/save-grid', [
            'slots' => [
                'Lundi' => [
                    '08:00' => ['id_matiere' => $m1, 'heure_debut' => '07:30', 'heure_fin' => '08:30'],
                    '09:00' => ['id_matiere' => $m2, 'heure_debut' => '08:30', 'heure_fin' => '09:30'],
                    '10:00' => ['id_matiere' => $m3, 'heure_debut' => '09:45', 'heure_fin' => '10:45'],
                ],
            ],
            'recesses' => ['09:00' => 15],
        ])->assertSessionHas('success');

        $grille = DB::table('emploi_du_temps_grilles')->where('id_classe', $this->classe)->where('id_annee_scolaire', $this->annee)->first();
        $this->assertNotNull($grille, 'grille enregistrée en base');
        $this->assertSame(['debut' => '07:30', 'fin' => '08:30'], json_decode($grille->horaires, true)['08:00']);
        $this->assertSame(15, json_decode($grille->recreations, true)['09:00']);

        // Nouvelle session (déconnexion, autre appareil) : mêmes horaires, cours de 07:30 visible.
        $this->flushSession();
        $html = $this->actingAs($admin)->withSession(['idEcole' => 1, 'timetable_id_classe' => $this->classe, 'timetable_id_annee' => $this->annee])
            ->get('/pedagogie/timetable')->assertOk()->getContent();
        $this->assertStringContainsString('07:30 - 08:30', $html);
        $this->assertStringContainsString('09:45 - 10:45', $html);
        $nomM1 = DB::table('matiere')->where('id_matiere', $m1)->value('nom_matiere');
        $this->assertStringContainsString(e($nomM1), $html, 'le cours de 07:30 apparaît dans la grille');

        // Application mobile : la grille est fournie par l'API.
        $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson("/api/v1/timetable?id_classe={$this->classe}&id_annee={$this->annee}")
            ->assertOk()
            ->assertJsonPath('grille.horaires.08:00.debut', '07:30')
            ->assertJsonPath('grille.recreations.09:00', 15);
    }

    public function test_emplois_du_temps_existants_reconstitues_depuis_les_cours(): void
    {
        [$m1, $m2, $m3] = $this->matieres;
        foreach ([[$m1, '07:30:00', '08:30:00'], [$m2, '08:30:00', '09:30:00'], [$m3, '09:45:00', '10:45:00']] as [$m, $debut, $fin]) {
            DB::table('emploi_du_temps')->insert(['id_classe' => $this->classe, 'id_matiere' => $m, 'id_annee_scolaire' => $this->annee, 'jour' => 'Lundi', 'heure_debut' => $debut, 'heure_fin' => $fin]);
        }

        $grille = GrilleEmploiDuTemps::lire($this->classe, $this->annee, DB::table('emploi_du_temps')->where('id_classe', $this->classe)->where('id_annee_scolaire', $this->annee)->get());
        $this->assertSame(['debut' => '07:30', 'fin' => '08:30'], $grille['horaires']['08:00']);
        $this->assertSame(['debut' => '09:45', 'fin' => '10:45'], $grille['horaires']['10:00']);
        $this->assertSame(['09:00' => 15], $grille['recreations'], 'écart de 15 minutes = récréation');
    }

    public function test_valeurs_invalides_ignorees(): void
    {
        GrilleEmploiDuTemps::enregistrer($this->classe, $this->annee, ['08:00' => ['debut' => '07:30', 'fin' => '08:30'], '99:99' => ['debut' => 'x', 'fin' => 'y'], '09:00' => ['debut' => 'abc', 'fin' => '09:00']], ['09:00' => 15, '10:00' => -5, '11:00' => 999]);
        $grille = GrilleEmploiDuTemps::lire($this->classe, $this->annee);
        $this->assertSame(['08:00'], array_keys($grille['horaires']));
        $this->assertSame(['09:00' => 15], $grille['recreations']);
    }
}
