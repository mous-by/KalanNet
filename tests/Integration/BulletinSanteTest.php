<?php

namespace Tests\Integration;

use App\Models\Classe;
use App\Support\ModelesBulletin;
use App\Support\PresentationBulletin;
use Illuminate\Support\Facades\DB;

/** Modèle de bulletin « École de santé » et signataire selon l'ordre (proviseur au secondaire). */
class BulletinSanteTest extends IntegrationTestCase
{
    public function test_modele_sante_par_defaut_et_reserve_aux_ecoles_de_sante(): void
    {
        $idSante = (int) DB::table('ecole')->where('typeEcole', 'École de Santé')->value('idEcole');
        $idAutre = (int) DB::table('ecole')->where('typeEcole', 'Secondaire Generale')->value('idEcole');
        if (!$idSante || !$idAutre) {
            $this->markTestSkipped('Il faut une école de santé et un lycée.');
        }

        $this->assertArrayHasKey('sante', ModelesBulletin::disponibles($idSante));
        $this->assertArrayNotHasKey('sante', ModelesBulletin::disponibles($idAutre));
        $classe = new Classe(['idEcole' => $idSante, 'ordreEnseignement' => null]);
        $this->assertSame('sante', ModelesBulletin::parDefaut($classe));
    }

    public function test_pdf_du_modele_sante(): void
    {
        $eleve = DB::table('eleve as el')->join('classe as c', 'c.id_classe', '=', 'el.id_classe')->join('ecole as e', 'e.idEcole', '=', 'c.idEcole')
            ->where('e.typeEcole', 'École de Santé')
            ->whereExists(fn ($q) => $q->from('ligne_evaluation as le')->whereColumn('le.id_eleve', 'el.id_eleve')->whereNotNull('le.id_trimestre'))
            ->select('el.id_eleve', 'el.id_annee', 'c.idEcole')->first();
        if (!$eleve) {
            $this->markTestSkipped('Aucun élève noté en école de santé.');
        }
        $trimestre = DB::table('ligne_evaluation')->where('id_eleve', $eleve->id_eleve)->whereNotNull('id_trimestre')->value('id_trimestre');

        $this->actingAs($this->adminComplet((int) $eleve->idEcole))->withSession(['idEcole' => $eleve->idEcole])
            ->get("/pedagogie/bulletins/{$eleve->id_eleve}/download?id_annee={$eleve->id_annee}&id_trimestre={$trimestre}&modele=sante")
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $html = view('pdf.bulletins.sante', PresentationBulletin::variables(
            \App\Services\Bulletins\CalculBulletin::pour(Classe::find(DB::table('eleve')->where('id_eleve', $eleve->id_eleve)->value('id_classe')), (int) $eleve->id_annee, (int) $trimestre, null, 'standard')
                ->pourEleve((int) $eleve->id_eleve) + ['statistiques' => ['premier' => 0], 'annuel' => null]
        ))->render();
        foreach (['Bulletin de notes', 'Moy. de classe', '(a+2b)/3', 'Notes coeffic.', 'Moyenne obtenue', 'Appréciation générale', 'Le Directeur des Études', 'Parent / Tuteur'] as $texte) {
            $this->assertStringContainsString($texte, $html);
        }
    }

    public function test_signataire_selon_l_ordre(): void
    {
        $ecole = (object) ['typeEcole' => 'Complexe Scolaire'];
        $this->assertSame('Le Proviseur', PresentationBulletin::signataire($ecole, 'secondairegenerale'));
        $this->assertSame('Le Proviseur', PresentationBulletin::signataire($ecole, 'secondairetechniqueetprofessionnel'));
        $this->assertSame('Le Directeur', PresentationBulletin::signataire($ecole, 'fondamentale2'));
        $this->assertSame('Le Directeur des Études', PresentationBulletin::signataire((object) ['typeEcole' => 'École de Santé'], null));
    }
}
