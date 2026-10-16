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

    public function test_edition_colonne_horaire_et_boutons_au_theme(): void
    {
        $admin = $this->adminComplet(1);
        GrilleEmploiDuTemps::enregistrer($this->classe, $this->annee, ['08:00' => ['debut' => '07:30', 'fin' => '08:30']], []);
        $html = $this->actingAs($admin)->withSession(['idEcole' => 1, 'timetable_id_classe' => $this->classe, 'timetable_id_annee' => $this->annee])
            ->get('/pedagogie/timetable')->assertOk()->getContent();

        // Colonne Horaire : champs début / fin modifiables en mode édition, mis à jour en direct.
        $this->assertMatchesRegularExpression('#class="[^"]*ligne-debut[^"]*"[^>]*value="07:30"[^>]*oninput="changerHeureLigne\(this\)"#', $html);
        $this->assertMatchesRegularExpression('#class="[^"]*ligne-fin[^"]*"[^>]*value="08:30"#', $html);
        // Boutons Lecture / Édition : couleur du thème.
        $this->assertStringContainsString('mode-grille', $html);
        $this->assertStringContainsString('.mode-grille .btn-check:checked + .btn', $html);
        $this->assertStringNotContainsString('btn-outline-primary px-3 py-2 fw-semibold" for="mode_', $html);
    }

    /** Cas réel : emploi du temps saisi sur le mobile (heures libres), consulté sur le web. */
    public function test_cours_saisis_sur_le_mobile_tous_visibles_sur_le_web(): void
    {
        $admin = $this->adminComplet(1);
        [$m1, $m2, $m3] = $this->matieres;
        GrilleEmploiDuTemps::enregistrer($this->classe, $this->annee, ['08:00' => ['debut' => '08:00', 'fin' => '09:00'], '09:00' => ['debut' => '09:00', 'fin' => '10:00']], ['09:00' => 15]);
        $jeton = $admin->createToken('mobile')->plainTextToken;
        foreach ([['Lundi', $m1, '7h30', '8h30'], ['Mardi', $m1, '08:00', '08:30'], ['Mardi', $m2, '08:30', '09:00'], ['Mercredi', $m3, '13:30', '15:00'], ['Jeudi', $m2, '8:00', '9:00']] as [$jour, $m, $debut, $fin]) {
            $this->withToken($jeton)->postJson('/api/v1/timetable', ['id_classe' => $this->classe, 'id_matiere' => $m, 'id_annee_scolaire' => $this->annee, 'jour' => $jour, 'heure_debut' => $debut, 'heure_fin' => $fin])->assertSuccessful();
        }
        $this->withToken($jeton)->postJson('/api/v1/timetable', ['id_classe' => $this->classe, 'id_matiere' => $m1, 'id_annee_scolaire' => $this->annee, 'jour' => 'Vendredi', 'heure_debut' => 'abc', 'heure_fin' => '09:00'])->assertStatus(422);

        $cours = DB::table('emploi_du_temps')->where('id_classe', $this->classe)->where('id_annee_scolaire', $this->annee)->get();
        $this->assertSame(['07:30', '08:00', '08:30', '13:30', '08:00'], $cours->sortBy('id')->pluck('heure_debut')->map(fn ($h) => substr($h, 0, 5))->values()->all(), 'heures normalisées');

        $html = $this->actingAs($admin)->withSession(['idEcole' => 1, 'timetable_id_classe' => $this->classe, 'timetable_id_annee' => $this->annee])->get('/pedagogie/timetable')->assertOk()->getContent();
        preg_match_all('#name="slots\[(\w+)\]\[[\d:]+\]\[id\]" value="(\d+)"#', $html, $m, PREG_SET_ORDER);
        $this->assertEqualsCanonicalizing($cours->pluck('id')->map(fn ($id) => (string) $id)->all(), array_column($m, 2), 'chaque cours a sa case sur le web');
        $this->assertSame(2, count(array_filter($m, fn ($x) => $x[1] === 'Mardi')), 'les deux cours de mardi (même heure) visibles');
        $this->assertStringContainsString('13:30 - 15:00', $html);

        // Application mobile : les récréations sont transmises avec l'emploi du temps.
        $this->withToken($jeton)->getJson("/api/v1/timetable?id_classe={$this->classe}&id_annee={$this->annee}")->assertOk()
            ->assertJsonPath('grille.recreations', fn ($r) => array_values($r) === [15]);

        $disposition = GrilleEmploiDuTemps::disposer($this->classe, $this->annee, \App\Models\EmploiDuTemps::where('id_classe', $this->classe)->where('id_annee_scolaire', $this->annee)->get());
        $this->assertSame(0, $disposition['non_places']);
        $this->assertSame(15, collect($disposition['recreations'])->first(), 'la récréation suit sa ligne');
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

    public function test_seules_les_matieres_de_la_classe_sont_proposees_et_acceptees(): void
    {
        $admin = $this->adminComplet(1);
        $session = ['idEcole' => 1, 'timetable_id_classe' => $this->classe, 'timetable_id_annee' => $this->annee];
        $deLaClasse = DB::table('ligneclasse')->where('id_classe', $this->classe)->pluck('id_matiere')->map(fn ($id) => (string) $id)->unique()->sort()->values()->all();
        $autre = (int) DB::table('matiere')->whereNotIn('id_matiere', $deLaClasse)->value('id_matiere');

        // Grille : uniquement les matières de la classe.
        $html = $this->actingAs($admin)->withSession($session)->get('/pedagogie/timetable')->assertOk()->getContent();
        preg_match('#<select name="slots\[Lundi\]\[08:00\]\[id_matiere\]".*?</select>#s', $html, $select);
        preg_match_all('#<option value="(\d+)"#', $select[0], $options);
        $this->assertEqualsCanonicalizing($deLaClasse, array_values(array_unique($options[1])));

        // Enregistrement : une autre matière est refusée (web et mobile), rien n'est créé.
        $this->actingAs($admin)->withSession($session)->post('/pedagogie/timetable/save-grid', [
            'slots' => ['Lundi' => ['08:00' => ['id_matiere' => $autre, 'heure_debut' => '08:00', 'heure_fin' => '09:00']]],
        ])->assertSessionHasErrors('id_matiere');
        $this->withToken($admin->createToken('test')->plainTextToken)->postJson('/api/v1/timetable/save-grid', [
            'id_classe' => $this->classe, 'id_annee' => $this->annee,
            'slots' => ['Lundi' => ['08:00' => ['id_matiere' => $autre, 'heure_debut' => '08:00', 'heure_fin' => '09:00']]],
        ])->assertStatus(422);
        $this->assertSame(0, DB::table('emploi_du_temps')->where('id_classe', $this->classe)->where('id_annee_scolaire', $this->annee)->count());

        // Classe sans matière : aucune matière proposée, et un message.
        DB::table('ligneclasse')->where('id_classe', $this->classe)->delete();
        $this->flushSession();
        $html = $this->actingAs($admin)->withSession($session)->get('/pedagogie/timetable')->assertOk()->getContent();
        preg_match('#<select name="slots\[Lundi\]\[08:00\]\[id_matiere\]".*?</select>#s', $html, $select);
        $this->assertDoesNotMatchRegularExpression('#<option value="\d+"#', $select[0]);
        $this->assertStringContainsString('Aucune matière n\'est attribuée à la classe', $html);
    }
}
