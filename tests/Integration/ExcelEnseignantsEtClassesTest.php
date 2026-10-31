<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** Liste des enseignants exportée avec ses listes déroulantes ; formulaire de classe : Enregistrer en haut et en bas. */
class ExcelEnseignantsEtClassesTest extends IntegrationTestCase
{
    private function classeur(string $url): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $reponse = $this->get($url)->assertOk();
        $fichier = tempnam(sys_get_temp_dir(), 'kn').'.xlsx';
        file_put_contents($fichier, $reponse->streamedContent());
        $classeur = IOFactory::load($fichier);
        unlink($fichier);

        return $classeur;
    }

    public function test_la_liste_exportee_garde_les_listes_deroulantes_du_modele(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        foreach (['/enseignants/importer/modele', '/enseignants/exporter'] as $url) {
            $feuille = $this->classeur($url)->getSheetByName('Enseignants');
            $genre = $feuille->getCell('C2')->getDataValidation();
            $this->assertSame('list', $genre->getType(), "Genre en liste : {$url}");
            $this->assertSame('Listes!$A$2:$A$3', ltrim($genre->getFormula1(), '='));
            $this->assertSame('list', $feuille->getCell('I2')->getDataValidation()->getType(), "Type de contrat en liste : {$url}");
        }

        // Plus bas que la dernière ligne exportée : la liste reste proposée pour un enseignant ajouté à la main.
        $nombre = \App\Models\Enseignant::withoutGlobalScopes()->where('id_ecole', 1)->count();
        $feuille = $this->classeur('/enseignants/exporter')->getSheetByName('Enseignants');
        $this->assertSame('list', $feuille->getCell('C'.($nombre + 5))->getDataValidation()->getType());
    }

    public function test_formulaire_de_classe_enregistrer_en_haut_et_en_bas(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $page = $this->get('/classes/create')->assertOk()->assertSee('form-actions-sticky', false);
        $this->assertGreaterThanOrEqual(2, substr_count($page->getContent(), 'class="btn btn-primary'), 'Enregistrer en haut et en bas');

        $classe = DB::table('classe')->where('idEcole', 1)->value('id_classe');
        if ($classe) {
            $this->get("/classes/{$classe}/edit")->assertOk()->assertSee('form-actions-sticky', false);
            $this->get("/classes/{$classe}")->assertOk()->assertSee('data-classe-retour', false)->assertSee(route('classes.index'), false);
        }
    }
}
