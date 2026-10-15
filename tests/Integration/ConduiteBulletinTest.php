<?php

namespace Tests\Integration;

use App\Models\Classe;
use App\Services\Bulletins\CalculBulletin;
use Illuminate\Support\Facades\DB;

/**
 * Audit 2026-10 : la conduite du bulletin est calculée sans écrire en base, avec
 * la même règle qu'avant (18 - pénalités, bornée à 6..18, arrondi FLOAT ; note
 * enregistrée gardée pour un élève sans contrôle).
 */
class ConduiteBulletinTest extends IntegrationTestCase
{
    public function test_regles_de_conduite_et_aucune_ecriture(): void
    {
        $classe = DB::table('classe as c')->join('eleve as e', 'e.id_classe', '=', 'c.id_classe')
            ->whereIn('c.ordreEnseignement', ['fondamentale2', 'secondairegenerale'])
            ->groupBy('c.id_classe', 'c.idEcole', 'e.id_annee')->havingRaw('count(*) >= 6')
            ->select('c.id_classe', 'c.idEcole', 'e.id_annee')->first();
        if (!$classe) {
            $this->markTestSkipped('Il faut une classe de 6 élèves au moins (Fondamental II ou secondaire).');
        }
        $eleves = DB::table('eleve')->where('id_classe', $classe->id_classe)->where('id_annee', $classe->id_annee)->orderBy('id_eleve')->limit(6)->pluck('id_eleve')->all();
        [$a, $b, $c, $d, $e, $f] = $eleves;

        DB::table('controle_eleve')->whereIn('id_eleve', $eleves)->delete();
        DB::table('conduite')->whereIn('id_eleve', $eleves)->delete();
        $controle = fn ($penalite, $ecole) => DB::table('controle')->insertGetId(['date_controle' => '2025-11-01 08:00:00', 'alertControle' => 'x', 'type_controle' => 'absence', 'id_emploi_du_temps' => 0, 'id_eleve' => $a, 'penalite_conduite' => $penalite, 'id_ecole' => $ecole]);
        $appel = fn ($eleve, $idControle, $ecole) => DB::table('controle_eleve')->insert(['id_eleve' => $eleve, 'id_classe' => $classe->id_classe, 'id_matiere' => 0, 'id_annee_scolaire' => $classe->id_annee, 'id_trimestre' => 1, 'id_ecole' => $ecole, 'date' => '2025-11-01', 'libelle' => 'x', 'heure_debut' => '08:00', 'heure_fin' => '09:00', 'notifier_parent' => 0, 'id_controle' => $idControle]);
        $noteEnregistree = fn ($eleve, $note) => DB::table('conduite')->insert(['id_annee_scolaire' => $classe->id_annee, 'id_classe' => $classe->id_classe, 'id_trimestre' => 1, 'id_eleve' => $eleve, 'note_conduite' => $note]);

        $p03 = $controle(0.3, $classe->idEcole); $p1 = $controle(1, $classe->idEcole); $p5 = $controle(5, $classe->idEcole);
        foreach ([1, 2, 3] as $_) { $appel($a, $p03, $classe->idEcole); $appel($f, $p5, $classe->idEcole); }
        $noteEnregistree($b, 15);
        $noteEnregistree($c, 12); $appel($c, $p1, $classe->idEcole);
        $appel($d, 999999, $classe->idEcole);
        $appel($e, $p5, 99999);

        CalculBulletin::oublier();
        $calcul = CalculBulletin::pour(Classe::withoutGlobalScopes()->find($classe->id_classe), (int) $classe->id_annee, 1, null, 'standard');
        $conduite = fn ($id) => $calcul->pourEleve($id)['note_conduite'] ?? null;

        $this->assertSame(17.1, $conduite($a), 'A : 3 × 0,3 de pénalité');
        $this->assertSame(15.0, $conduite($b), 'B : note enregistrée, sans contrôle');
        $this->assertSame(17.0, $conduite($c), 'C : les contrôles remplacent la note enregistrée');
        $this->assertSame(18.0, $conduite($d), 'D : contrôle introuvable = 0 de pénalité');
        $this->assertNull($conduite($e), 'E : contrôles d\'une autre école ignorés');
        $this->assertSame(6.0, $conduite($f), 'F : plancher à 6');
        $this->assertSame(0, DB::table('conduite')->whereIn('id_eleve', [$a, $d, $f])->count(), 'aucune écriture pendant le calcul');
    }
}
