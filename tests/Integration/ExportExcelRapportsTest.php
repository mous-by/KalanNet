<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\RapportSuccinct;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** Export Excel, à côté du PDF : rapport succinct, cahier de bord, proposition de passage, rapports de rentrée et de fin d'année. */
class ExportExcelRapportsTest extends IntegrationTestCase
{
    private AnneeScolaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['rapports_succincts', 'cahiers_bord', 'propositions_passage', 'rapports_secondaire'] as $table) {
            DB::table($table)->where('id_ecole', 1)->delete();
        }
        $this->annee = AnneeScolaire::enCours(1);
        $modele = Classe::where('idEcole', 1)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(3)->pluck('id_eleve')->all();
        if (!$modele || count($eleves) < 3) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Complexe Scolaire']);
        DB::table('eleve')->where('id_ecole', 1)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        foreach (['3ème année' => 'fondamentale1', 'TSE' => 'secondairegenerale'] as $nom => $ordre) {
            $classe = $modele->replicate();
            $classe->nom_classe = $nom;
            $classe->ordreEnseignement = $ordre;
            $classe->save();
            $ids = $ordre === 'secondairegenerale' ? [$eleves[2]] : [$eleves[0], $eleves[1]];
            DB::table('eleve')->whereIn('id_eleve', $ids)->update(['id_classe' => $classe->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => 'Féminin', 'nom_eleve' => 'KEITA']);
            $this->classes[$nom] = $classe;
        }
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
    }

    /** @var array<string, Classe> */
    private array $classes = [];

    /** Contenu de toutes les cases du fichier téléchargé, en une seule chaîne. */
    private function lire(string $url): string
    {
        $reponse = $this->get($url)->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $reponse->headers->get('content-type'));
        $this->assertStringEndsWith('.xlsx', (string) $reponse->headers->get('content-disposition'));
        $fichier = tempnam(sys_get_temp_dir(), 'kn').'.xlsx';
        file_put_contents($fichier, $reponse->streamedContent());
        $classeur = IOFactory::load($fichier);
        unlink($fichier);
        $texte = collect($classeur->getActiveSheet()->toArray(null, false, false))->flatten()->filter(fn ($v) => $v !== null)->implode(' | ');
        $classeur->disconnectWorksheets();

        return $texte;
    }

    public function test_chaque_fiche_s_exporte_en_excel(): void
    {
        $annee = $this->annee->id_anneeScolaire;

        $succinct = $this->lire("/rapport-succinct/pdf?annee=$annee&portee=fondamental&format=excel");
        $this->assertStringContainsString('Tableau 2 : Groupes pédagogiques', $succinct);
        $this->assertStringContainsString('Total école', $succinct);

        $cahier = $this->lire("/cahier-bord/pdf?annee=$annee&cycle=premier&format=excel");
        $this->assertStringContainsString('I. Situation des effectifs', $cahier);
        $this->assertStringContainsString('V. Personnel enseignant', $cahier);

        $proposition = $this->lire("/proposition-passage/pdf?annee=$annee&classe={$this->classes['3ème année']->id_classe}&format=excel");
        $this->assertStringContainsString('KEITA', $proposition);
        $this->assertStringContainsString('Proposition du conseil des maîtres', $proposition);

        $rentree = $this->lire("/rapport-secondaire/rentree/pdf?annee=$annee&format=excel");
        $this->assertStringContainsString('T5 : Effectif des élèves', $rentree);
        $this->assertStringContainsString('T24 : Administration', $rentree);
        $this->assertStringContainsString("T15 : Répartition des élèves par tranche", $this->lire("/rapport-secondaire/fin/pdf?annee=$annee&format=excel"));

        // Depuis le formulaire : le bouton « Excel » enregistre puis télécharge.
        $this->get("/rapport-succinct?annee=$annee&portee=fondamental")->assertSee('value="excel"', false);
        $this->post("/rapport-secondaire/rentree?annee=$annee", ['action' => 'excel', 'infos' => ['region' => 'Kayes'], 'tableaux_json' => '{}'])
            ->assertRedirect(route('rapport-secondaire.pdf', ['type' => 'rentree', 'annee' => $annee, 'format' => 'excel']));
    }

    public function test_le_cap_exporte_aussi_ce_qu_il_a_recu(): void
    {
        $idCap = (int) DB::table('cap')->value('id_cap');
        DB::table('ecole')->where('idEcole', 1)->update(['id_cap' => $idCap]);
        $rapport = RapportSuccinct::withoutGlobalScopes()->create(['id_ecole' => 1, 'id_annee' => $this->annee->id_anneeScolaire, 'portee' => 'fondamental', 'statut' => 'remis',
            'donnees' => [], 'donnees_envoyees' => ['ecole' => 'École test', 'commune' => 'Kayes', 'groupes' => [['cle' => '3', 'libelle' => '3ème A', 'groupes' => 1, 'g' => 4, 'f' => 5, 'rg' => 0, 'rf' => 1]], 'maitres' => []]]);
        $dcap = $this->compte('Admin', 1)->replicate();
        $dcap->forceFill(['droit' => 'DCAP', 'idEcole' => null, 'id_cap' => $idCap, 'statut' => 1, 'email' => 'dcap'.uniqid().'@test.ml'])->save();

        $this->actingAs($dcap)->withSession([]);
        $texte = $this->lire("/territoire/rapports-succincts/{$rapport->id}/pdf?format=excel");
        $this->assertStringContainsString('École test', $texte);
        $this->assertStringContainsString('3ème A', $texte);
        $this->get('/territoire/rapports-succincts')->assertSee('format=excel', false);
    }
}
