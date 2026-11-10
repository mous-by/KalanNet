<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\Planification;
use Illuminate\Support\Facades\DB;

/** Modification en groupe des formules de paiement : comme la création, plusieurs classes d'un coup. */
class PlanificationGroupeTest extends IntegrationTestCase
{
    private array $classes;
    private int $annee;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('ecole')->where('idEcole', 1)->update(['statut' => 'prive']);
        $this->classes = DB::table('classe')->where('idEcole', 1)->orderBy('id_classe')->limit(3)->pluck('id_classe')->map(fn ($id) => (int) $id)->all();
        if (count($this->classes) < 3) {
            $this->markTestSkipped('Il faut trois classes dans l\'école 1.');
        }
        $this->annee = (int) AnneeScolaire::enCours(1)->id_anneeScolaire;
        $ids = Planification::whereIn('id_classe', $this->classes)->where('id_annee', $this->annee)->pluck('id_planification');
        DB::table('paiement')->whereIn('id_planification', $ids)->delete();
        DB::table('ligne_inscription')->whereIn('id_planification', $ids)->update(['id_planification' => null]);
        DB::table('planification_tranches')->whereIn('id_planification', $ids)->delete();
        Planification::whereIn('id_planification', $ids)->delete();
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
    }

    private function formules(array $classes, array $lignes): array
    {
        $data = ['id_classes' => $classes, 'id_annee' => $this->annee];
        foreach ($lignes as $i => $l) {
            $data['motif'][] = $l[0];
            $data['date_debut'][] = $l[1];
            $data['date_fin'][] = $l[2];
            $data['montant'][] = $l[3];
            $data['row_key'][] = (string) ($i + 1);
            if (isset($l[4])) {
                $data['tranches'][(string) ($i + 1)] = $l[4];
            }
        }

        return $data;
    }

    private function formule(int $classe, string $motif): ?Planification
    {
        return Planification::with('tranches')->where('id_classe', $classe)->where('id_annee', $this->annee)->where('motif', $motif)->first();
    }

    public function test_modifier_plusieurs_classes_d_un_coup(): void
    {
        [$a, $b, $c] = $this->classes;
        $tranches = ['montant' => [40000, 30000, 30000], 'date_limite' => ['2026-10-31', '2027-01-31', '2027-05-31']];
        $this->post('/finances/planifications', $this->formules([$a, $b], [
            ['mensuelle', '2026-10-01', '2026-10-31', 10000],
            ['par_tranche', '2026-10-01', '2027-05-31', 100000, $tranches],
        ]))->assertSessionHas('success');
        $this->assertNotNull($this->formule($b, '3 tranches'));

        $this->get('/finances/planifications/modifier')->assertOk()->assertSee('modification en groupe')->assertSee('formulesExistantes', false);

        // Un élève de B a déjà versé 14 000 sur la mensuelle : on ne descend pas en dessous.
        $mensuelleB = $this->formule($b, 'mensuelle');
        DB::table('paiement')->insert(['id_classe' => $b, 'id_annee' => $this->annee, 'id_trimestre' => 1, 'reference' => 'TEST-GRP', 'idEcole' => 1,
            'nom_payeur' => 'x', 'telephone' => '0', 'id_planification' => $mensuelleB->id_planification, 'id_eleve' => 1, 'montant_paye' => 14000]);

        $this->put('/finances/planifications/modifier', $this->formules([$a, $b, $c], [
            ['mensuelle', '2026-10-05', '2026-11-04', 12500],
            ['par_tranche', '2026-10-01', '2027-05-15', 105000, ['montant' => [50000, 30000, 25000], 'date_limite' => ['2026-11-15', '2027-02-15', '2027-05-15']]],
        ]))->assertRedirect('/finances/planifications/modifier')->assertSessionHas('success', '3 formule(s) de paiement modifiée(s).');

        $erreurs = implode(' | ', session('planification_errors'));
        $this->assertStringContainsString('pas de formule « mensuelle »', $erreurs, 'classe C sans formule : signalée');
        $this->assertStringContainsString('14 000', $erreurs, 'classe B : versement déjà fait plus élevé');

        $this->assertSame(12500.0, (float) $this->formule($a, 'mensuelle')->montant_planification);
        $this->assertSame('2026-10-05', substr((string) $this->formule($a, 'mensuelle')->date_debut, 0, 10));
        $this->assertSame(10000.0, (float) $this->formule($b, 'mensuelle')->montant_planification, 'B laissée telle quelle');
        foreach ([$a, $b] as $classe) {
            $f = $this->formule($classe, '3 tranches');
            $this->assertSame(105000.0, (float) $f->montant_planification);
            $this->assertSame([50000.0, 30000.0, 25000.0], $f->tranches->sortBy('numero')->pluck('montant')->map(fn ($m) => (float) $m)->values()->all());
            $this->assertSame('2027-05-15', substr((string) $f->date_fin, 0, 10));
        }
        $this->assertSame(0, Planification::where('id_classe', $c)->where('id_annee', $this->annee)->count(), 'rien de créé');
    }

    public function test_changer_la_formule_des_eleves_deja_inscrits(): void
    {
        [$a] = $this->classes;
        $this->post('/finances/planifications', $this->formules([$a], [
            ['mensuelle', '2026-10-01', '2026-10-31', 10000],
            ['par_tranche', '2026-10-01', '2027-05-31', 100000, ['montant' => [40000, 30000, 30000], 'date_limite' => ['2026-10-31', '2027-01-31', '2027-05-31']]],
        ]))->assertSessionHas('success');
        $mensuelle = $this->formule($a, 'mensuelle');
        $tranches = $this->formule($a, '3 tranches');

        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(3)->pluck('id_eleve')->map(fn ($id) => (int) $id)->all();
        DB::table('eleve')->where('id_classe', $a)->whereNotIn('id_eleve', $eleves)->update(['etat_dossier' => 1]);
        DB::table('eleve')->whereIn('id_eleve', $eleves)->update(['id_classe' => $a, 'id_annee' => $this->annee, 'etat_dossier' => 0]);
        DB::table('ligne_inscription')->whereIn('id_eleve', $eleves)->where('id_annee', $this->annee)->delete();
        DB::table('subvention_etat_eleve')->whereIn('id_eleve', $eleves)->delete();
        foreach (array_slice($eleves, 0, 2) as $e) {
            DB::table('ligne_inscription')->insert(['id_eleve' => $e, 'id_classe' => $a, 'id_annee' => $this->annee, 'id_planification' => $mensuelle->id_planification, 'date_inscription' => '2026-10-01']);
        }
        // Le 2e a déjà payé sur la mensuelle : il garde sa formule.
        DB::table('paiement')->insert(['id_classe' => $a, 'id_annee' => $this->annee, 'id_trimestre' => 1, 'reference' => 'TEST-CHG', 'idEcole' => 1,
            'nom_payeur' => 'x', 'telephone' => '0', 'id_planification' => $mensuelle->id_planification, 'id_eleve' => $eleves[1], 'montant_paye' => 5000]);

        $this->get("/finances/planifications/eleves?id_classe={$a}&id_annee={$this->annee}")->assertOk()
            ->assertSee('Changer la formule des élèves')->assertSee('3 tranches')->assertSee('Aucune formule')->assertSee('5 000 F');

        $this->put('/finances/planifications/eleves', ['id_classe' => $a, 'id_annee' => $this->annee, 'id_planification' => $tranches->id_planification, 'eleves' => $eleves])
            ->assertRedirect()->assertSessionHas('success', '2 élève(s) passé(s) à la formule « 3 tranches ».');
        $this->assertStringContainsString('a déjà payé', implode(' ', session('planification_errors')));

        $formuleDe = fn ($e) => (int) DB::table('ligne_inscription')->where('id_eleve', $e)->where('id_annee', $this->annee)->value('id_planification');
        $this->assertSame((int) $tranches->id_planification, $formuleDe($eleves[0]));
        $this->assertSame((int) $mensuelle->id_planification, $formuleDe($eleves[1]));
        $this->assertSame((int) $tranches->id_planification, $formuleDe($eleves[2]), 'élève sans formule : inscription créée');

        // Une formule d'une autre classe est refusée.
        $this->put('/finances/planifications/eleves', ['id_classe' => $this->classes[1], 'id_annee' => $this->annee, 'id_planification' => $tranches->id_planification, 'eleves' => $eleves])->assertNotFound();
    }

    public function test_droits(): void
    {
        $this->actingAs($this->compte('parent', 1))->withSession(['idEcole' => 1]);
        $this->get('/finances/planifications/modifier')->assertForbidden();
        $this->put('/finances/planifications/modifier', $this->formules([$this->classes[0]], [['mensuelle', '2026-10-01', '2026-10-31', 1]]))->assertForbidden();
        $this->get('/finances/planifications/eleves')->assertForbidden();
    }
}
