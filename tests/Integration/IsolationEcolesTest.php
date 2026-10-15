<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Audit 2026-10 : les ressources d'une autre école renvoient 403 ou 404, jamais une erreur 500 ni des données. */
class IsolationEcolesTest extends IntegrationTestCase
{
    public function test_ressources_d_une_autre_ecole(): void
    {
        $admin = $this->adminComplet(1);
        $autre = $this->autreEcole(1);
        $eleve = DB::table('eleve')->where('id_ecole', $autre)->first();
        $classe = DB::table('classe')->where('idEcole', $autre)->value('id_classe');
        $enseignant = DB::table('enseignants')->where('id_ecole', $autre)->value('id_enseignant');
        $evaluation = DB::table('ligne_evaluation as l')->join('classe as c', 'c.id_classe', '=', 'l.id_classe')
            ->where('c.idEcole', $autre)->where('l.id_evaluation', '>', 0)->value('l.id_evaluation');

        $urls = array_filter([
            "/eleves/{$eleve->id_eleve}", "/eleves/{$eleve->id_eleve}/edit",
            $classe ? "/classes/$classe" : null, $classe ? "/pedagogie/classes/$classe/bulletins" : null,
            $enseignant ? "/enseignants/$enseignant" : null,
            $evaluation ? "/evaluations/$evaluation/edit" : null, $evaluation ? "/evaluations/$evaluation/programme" : null,
        ]);
        foreach ($urls as $url) {
            $reponse = $this->actingAs($admin)->withSession(['idEcole' => 1])->get($url);
            $this->assertContains($reponse->status(), [403, 404], "$url : {$reponse->status()}");
        }

        // Écriture : supprimer l'évaluation d'une autre école est refusé (faille corrigée en octobre 2026).
        $evaluationEtrangere = DB::table('ligne_evaluation as l')->join('classe as c', 'c.id_classe', '=', 'l.id_classe')
            ->where('c.idEcole', '!=', 1)->where('l.id_evaluation', '>', 0)->value('l.id_evaluation');
        $this->assertNotNull($evaluationEtrangere, 'il faut une évaluation d\'une autre école dans la base locale');
        $evaluation = $evaluationEtrangere;
        if ($evaluation) {
            $notes = DB::table('ligne_evaluation')->where('id_evaluation', $evaluation)->count();
            $this->actingAs($admin)->withSession(['idEcole' => 1])->delete("/evaluations/$evaluation")->assertNotFound();
            $this->assertSame($notes, DB::table('ligne_evaluation')->where('id_evaluation', $evaluation)->count(), 'web : notes intactes');
            $this->withToken($admin->createToken('test')->plainTextToken)->deleteJson("/api/v1/evaluations/$evaluation")->assertNotFound();
            $this->assertSame($notes, DB::table('ligne_evaluation')->where('id_evaluation', $evaluation)->count(), 'API : notes intactes');
            $this->assertTrue(DB::table('evaluation')->where('id_evaluation', $evaluation)->exists());
        }

        $jeton = $admin->createToken('test')->plainTextToken;
        foreach (array_filter(["/api/v1/eleves/{$eleve->id_eleve}", $classe ? "/api/v1/classes/$classe" : null, $evaluation ? "/api/v1/evaluations/$evaluation" : null]) as $url) {
            $reponse = $this->withToken($jeton)->getJson($url);
            $this->assertContains($reponse->status(), [403, 404], "$url : {$reponse->status()}");
            $this->assertStringNotContainsString((string) $eleve->nom_eleve.'"', $reponse->getContent());
        }
    }
}
