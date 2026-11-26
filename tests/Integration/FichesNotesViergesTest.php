<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\DB;

/** Fiches de notes vierges à imprimer pour les enseignants qui notent sur papier. */
class FichesNotesViergesTest extends IntegrationTestCase
{
    private object $classe;
    private int $annee;
    private array $matieres;

    protected function setUp(): void
    {
        parent::setUp();
        $annee = AnneeScolaire::enCours(1);
        $classe = DB::table('classe')->where('idEcole', 1)
            ->whereIn('id_classe', DB::table('ligneclasse')->select('id_classe'))
            ->where(fn ($q) => $q->whereNull('ordreEnseignement')->orWhere('ordreEnseignement', '!=', \App\Support\Ordres::JARDIN))
            ->first();
        if (!$annee || !$classe) {
            $this->markTestSkipped('Il faut une classe avec des matières dans l\'école 1.');
        }
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(3)->pluck('id_eleve');
        DB::table('eleve')->whereIn('id_eleve', $eleves)->update(['id_classe' => $classe->id_classe, 'id_annee' => $annee->id_anneeScolaire, 'etat_dossier' => 0, 'id_matiere_lv2' => null]);
        DB::table('matiere')->whereIn('id_matiere', DB::table('ligneclasse')->where('id_classe', $classe->id_classe)->select('id_matiere'))->update(['est_lv2' => 0]);

        $this->classe = $classe;
        $this->annee = (int) $annee->id_anneeScolaire;
        $this->matieres = DB::table('ligneclasse')->where('id_classe', $classe->id_classe)->distinct()->limit(2)->pluck('id_matiere')->map(fn ($id) => (int) $id)->all();
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
    }

    private function parametres(array $plus = []): array
    {
        return $plus + [
            'id_classe' => $this->classe->id_classe, 'id_annee_scolaire' => $this->annee,
            'matieres' => $this->matieres, 'devoirs' => 2, 'composition' => 1, 'periode' => 'm11',
        ];
    }

    public function test_la_page_liste_les_matieres_de_la_classe(): void
    {
        $nom = DB::table('matiere')->where('id_matiere', $this->matieres[0])->value('nom_matiere');

        $this->get('/evaluations/fiches-vierges?id_classe='.$this->classe->id_classe)
            ->assertOk()
            ->assertSee('Imprimer toutes les fiches')
            ->assertSee($nom);
    }

    public function test_un_pdf_avec_une_fiche_par_matiere(): void
    {
        $reponse = $this->get('/evaluations/fiches-vierges/pdf?'.http_build_query($this->parametres()));

        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_un_fichier_pdf_par_matiere_dans_un_zip(): void
    {
        $reponse = $this->get('/evaluations/fiches-vierges/pdf?'.http_build_query($this->parametres(['format' => 'zip'])));

        $reponse->assertOk();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($reponse->getFile()->getPathname()) === true);
        $this->assertSame(count($this->matieres), $zip->numFiles);
        $this->assertStringStartsWith('%PDF', $zip->getFromIndex(0));
        $zip->close();
    }

    public function test_un_classeur_excel_avec_une_feuille_par_matiere(): void
    {
        $reponse = $this->get('/evaluations/fiches-vierges/pdf?'.http_build_query($this->parametres(['format' => 'xlsx'])));

        $reponse->assertOk();
        $chemin = tempnam(sys_get_temp_dir(), 'fiches').'.xlsx';
        file_put_contents($chemin, $reponse->streamedContent());
        $classeur = \PhpOffice\PhpSpreadsheet\IOFactory::load($chemin);
        unlink($chemin);

        $this->assertSame(count($this->matieres), $classeur->getSheetCount());
        $texte = collect($classeur->getSheet(0)->toArray())->flatten()->filter()->implode(' | ');
        $this->assertStringContainsString('FICHE DE NOTES', $texte);
        $this->assertStringContainsString('Composition', $texte);
        $this->assertStringContainsString('Devoir 2', $texte);
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function cycles(): array
    {
        return [
            'Fondamental I' => ['fondamentale1', true],
            'Fondamental II' => ['fondamentale2', true],
            'Secondaire général' => ['secondairegenerale', false],
            'Secondaire technique et professionnel' => ['secondairetechniqueetprofessionnel', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cycles')]
    public function test_le_cap_n_apparait_pas_au_secondaire(string $ordre, bool $avecCap): void
    {
        $ecole = \App\Models\Ecole::withoutGlobalScopes()->find(1);
        $html = view('pdf.partials.entete_ecole', ['ecole' => $ecole, 'classe' => (object) ['ordreEnseignement' => $ordre]])->render();

        $this->assertStringContainsString('Académie d\'Enseignement de', $html);
        $avecCap ? $this->assertStringContainsString('CAP de', $html) : $this->assertStringNotContainsString('CAP de', $html);
        $this->assertStringNotContainsString('CAP de CAP', $html, 'le nom du CAP enregistré commence parfois déjà par « CAP de »');
    }

    public function test_sans_matiere_cochee_on_revient_avec_un_message(): void
    {
        $this->from('/evaluations/fiches-vierges')
            ->get('/evaluations/fiches-vierges/pdf?'.http_build_query($this->parametres(['matieres' => []])))
            ->assertRedirect('/evaluations/fiches-vierges')
            ->assertSessionHasErrors('matieres');
    }

    public function test_il_faut_au_moins_un_devoir_ou_la_composition(): void
    {
        $this->from('/evaluations/fiches-vierges')
            ->get('/evaluations/fiches-vierges/pdf?'.http_build_query($this->parametres(['devoirs' => 0, 'composition' => 0])))
            ->assertRedirect('/evaluations/fiches-vierges')
            ->assertSessionHasErrors('devoirs');
    }

    public function test_classe_d_une_autre_ecole_refusee(): void
    {
        $autre = DB::table('classe')->where('idEcole', '!=', 1)->value('id_classe');
        if (!$autre) {
            $this->markTestSkipped('Il faut une classe dans une autre école.');
        }

        $statut = $this->get('/evaluations/fiches-vierges/pdf?'.http_build_query($this->parametres(['id_classe' => $autre])))->status();

        $this->assertContains($statut, [403, 404], 'les élèves d\'une autre école ne doivent pas s\'imprimer');
    }
}
