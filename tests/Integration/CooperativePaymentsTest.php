<?php

namespace Tests\Integration;

use App\Models\Planification;
use Illuminate\Support\Facades\DB;

class CooperativePaymentsTest extends IntegrationTestCase
{
    public function test_public_students_without_linked_plan_use_the_class_cooperative(): void
    {
        $student = DB::table('eleve as e')
            ->join('ecole as school', 'school.idEcole', '=', 'e.id_ecole')
            ->join('ligne_inscription as enrollment', function ($join) {
                $join->on('enrollment.id_eleve', '=', 'e.id_eleve')
                    ->on('enrollment.id_classe', '=', 'e.id_classe')
                    ->on('enrollment.id_annee', '=', 'e.id_annee');
            })
            ->where('school.statut', 'public')
            ->where('e.etat_dossier', 0)
            ->whereNull('enrollment.id_planification')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('caisse')
                    ->whereColumn('caisse.id_ecole', 'e.id_ecole')
                    ->where('caisse.status', 1);
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('ligneparents_eleves as relation')
                    ->whereColumn('relation.id_eleve', 'e.id_eleve');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('planification as p')
                    ->whereColumn('p.id_classe', 'e.id_classe')
                    ->whereColumn('p.id_annee', 'e.id_annee');
            })
            ->select('e.id_ecole', 'e.id_eleve', 'e.id_classe', 'e.id_annee', 'e.prenom_eleve', 'e.nom_eleve', 'enrollment.id_inscription')
            ->first();

        if (!$student) {
            $this->markTestSkipped('Aucun élève public actif sans planification à utiliser dans la base de test.');
        }

        $admin = $this->adminComplet((int) $student->id_ecole);
        $filters = [
            'id_classe' => $student->id_classe,
            'id_annee' => $student->id_annee,
            'id_trimestre' => DB::table('trimestre')->value('id_trimestre'),
            'date_paiement' => now()->toDateString(),
            'type_planification' => '',
        ];
        $session = ['idEcole' => $student->id_ecole, 'finances_paiements_filters' => $filters];

        $this->actingAs($admin)->withSession($session)->get('/finances/paiements')
            ->assertOk()
            ->assertSee(__('finances.cooperative_not_configured'));

        $cooperative = Planification::create([
            'motif' => 'cooperative',
            'id_classe' => $student->id_classe,
            'id_annee' => $student->id_annee,
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-08-31',
            'montant_planification' => 100000,
        ]);

        $this->actingAs($admin)->withSession($session)->get('/finances/paiements')
            ->assertOk()
            ->assertSee($student->prenom_eleve.' '.$student->nom_eleve)
            ->assertDontSee(__('finances.cooperative_not_configured'));

        $parentId = DB::table('ligneparents_eleves')->where('id_eleve', $student->id_eleve)->value('id_parent');
        $this->actingAs($admin)->withSession(['idEcole' => $student->id_ecole])
            ->post('/finances/paiements/groupes', [
                'id_classe' => $student->id_classe,
                'id_annee' => $student->id_annee,
                'id_trimestre' => $filters['id_trimestre'],
                'date_paiement' => $filters['date_paiement'],
                'type_planification' => 'cooperative',
                'alert' => [$student->id_eleve],
                'id_eleve' => [$student->id_eleve],
                'id_planification' => [$cooperative->id_planification],
                'motif' => ['Coopérative'],
                'montant_recu' => [1],
                'parent_id' => [$parentId],
            ])
            ->assertRedirect('/finances/paiements')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('paiement', [
            'idEcole' => $student->id_ecole,
            'id_eleve' => $student->id_eleve,
            'id_planification' => $cooperative->id_planification,
            'montant_paye' => 1,
        ]);
    }
}