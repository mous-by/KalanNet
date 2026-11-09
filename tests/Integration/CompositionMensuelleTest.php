<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Services\Bulletins\CalculBulletin;
use Illuminate\Support\Facades\DB;

/** Fondamental II : compositions mensuelles à part, sans toucher la moyenne du trimestre. */
class CompositionMensuelleTest extends IntegrationTestCase
{
    private object $classe;
    private int $annee;
    private int $matiere;
    private int $compo;
    private int $trimestre;

    protected function setUp(): void
    {
        parent::setUp();
        $annee = AnneeScolaire::enCours(1);
        $classe = DB::table('classe')->where('idEcole', 1)->where('ordreEnseignement', 'fondamentale2')->first();
        $matiere = $classe ? DB::table('ligneclasse')->where('id_classe', $classe->id_classe)->value('id_matiere') : null;
        $compo = DB::table('note')->whereIn(DB::raw('lower(codeNote)'), ['cp', 'composition'])->value('id_note')
            ?? DB::table('note')->where('typeNote', 'like', '%ompo%')->value('id_note');
        if (!$annee || !$classe || !$matiere || !$compo) {
            $this->markTestSkipped('Il faut une classe de Fondamental II avec une matière dans l\'école 1.');
        }
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(2)->pluck('id_eleve');
        DB::table('eleve')->where('id_classe', $classe->id_classe)->whereNotIn('id_eleve', $eleves)->update(['etat_dossier' => 1]);
        DB::table('eleve')->whereIn('id_eleve', $eleves)->update(['id_classe' => $classe->id_classe, 'id_annee' => $annee->id_anneeScolaire, 'etat_dossier' => 0, 'id_matiere_lv2' => null]);
        DB::table('ligne_evaluation')->where('id_classe', $classe->id_classe)->delete();
        DB::table('ecole')->where('idEcole', 1)->update(['statut' => 'public']);

        $this->classe = $classe;
        $this->annee = (int) $annee->id_anneeScolaire;
        $this->matiere = (int) $matiere;
        $this->compo = (int) $compo;
        $this->trimestre = (int) DB::table('trimestre')->orderBy('id_trimestre')->value('id_trimestre');
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
    }

    private function programmer(array $periode, string $libelle): int
    {
        $this->post('/evaluations/store', $periode + [
            'libeller' => $libelle, 'date_evaluation' => '2026-11-05', 'heure_debut' => '08:00', 'heure_fin' => '10:00',
            'id_classe' => $this->classe->id_classe, 'id_matiere' => $this->matiere, 'id_annee_scolaire' => $this->annee, 'id_note' => $this->compo,
        ])->assertSessionHasNoErrors();

        return (int) DB::table('evaluation')->where('libeller', $libelle)->value('id_evaluation');
    }

    private function noter(int $idEvaluation, float $note): void
    {
        DB::table('ligne_evaluation')->where('id_evaluation', $idEvaluation)->update(['note' => $note]);
    }

    private function moyenne(?int $trimestre, ?int $mois): ?float
    {
        CalculBulletin::oublier();
        $calcul = CalculBulletin::pour(Classe::withoutGlobalScopes()->find($this->classe->id_classe), $this->annee, $trimestre, $mois, 'standard');
        $eleve = DB::table('ligne_evaluation')->where('id_classe', $this->classe->id_classe)->value('id_eleve');
        $m = collect($calcul->pourEleve($eleve)['matieres'] ?? [])->firstWhere('id_matiere', $this->matiere);

        return $m ? (float) $m->M_Gle : null;
    }

    public function test_composition_mensuelle_a_part_du_trimestre(): void
    {
        $trim = $this->programmer(['id_trimestre' => $this->trimestre], 'Compo T1 test');
        $mois = $this->programmer(['mois' => 11, 'id_trimestre' => $this->trimestre], 'Compo novembre test');
        $this->assertNull(DB::table('ligne_evaluation')->where('id_evaluation', $mois)->value('id_trimestre'));
        $this->assertSame(11, (int) DB::table('ligne_evaluation')->where('id_evaluation', $mois)->value('mois'));

        $this->noter($trim, 12);
        $this->noter($mois, 16);
        $this->assertEqualsWithDelta(8.0, $this->moyenne($this->trimestre, null), 0.001, 'trimestre : (0 + 2×12)/3, sans la note du mois');
        $this->assertEqualsWithDelta(16.0, $this->moyenne(null, 11), 0.001, 'bulletin du mois : la note de la composition');

        // Une composition par trimestre et une par mois : pas d'alerte.
        $this->get('/evaluations')->assertOk()->assertDontSee('Plusieurs compositions dans un même trimestre');
    }

    public function test_ancienne_composition_mensuelle_saisie_en_trimestre_se_corrige(): void
    {
        $trim = $this->programmer(['id_trimestre' => $this->trimestre], 'Compo T1 test');
        $erreur = $this->programmer(['id_trimestre' => $this->trimestre], 'Compo octobre test');
        $this->noter($erreur, 15);
        DB::table('ligne_evaluation')->where('id_evaluation', $erreur)->update(['validation_status' => 'valide']);

        $this->get('/evaluations')->assertOk()->assertSee('Plusieurs compositions dans un même trimestre')->assertSee('Compo octobre test');

        $this->put("/evaluations/{$erreur}/programme", [
            'libeller' => 'Compo octobre test', 'date_evaluation' => '2026-10-20', 'heure_debut' => '08:00', 'heure_fin' => '10:00',
            'id_classe' => $this->classe->id_classe, 'id_matiere' => $this->matiere, 'id_annee_scolaire' => $this->annee, 'id_note' => $this->compo, 'mois' => 10,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $lignes = DB::table('ligne_evaluation')->where('id_evaluation', $erreur)->get();
        $this->assertTrue($lignes->every(fn ($l) => (float) $l->note === 15.0 && (int) $l->mois === 10 && $l->id_trimestre === null && $l->validation_status === 'valide'));
        $this->get('/evaluations')->assertOk()->assertDontSee('Plusieurs compositions dans un même trimestre');
    }

    public function test_fondamental_i_et_lycee_inchanges(): void
    {
        $f1 = DB::table('classe')->where('idEcole', 1)->where('ordreEnseignement', 'fondamentale1')->first();
        if ($f1) {
            DB::table('classe')->where('id_classe', $this->classe->id_classe)->update(['ordreEnseignement' => 'fondamentale1']);
            $this->post('/evaluations/store', ['libeller' => 'x', 'date_evaluation' => '2026-11-05', 'heure_debut' => '08:00', 'heure_fin' => '10:00',
                'id_classe' => $this->classe->id_classe, 'id_matiere' => $this->matiere, 'id_annee_scolaire' => $this->annee, 'id_note' => $this->compo, 'id_trimestre' => $this->trimestre])
                ->assertSessionHasErrors('mois');
        }
        DB::table('classe')->where('id_classe', $this->classe->id_classe)->update(['ordreEnseignement' => 'secondairegenerale']);
        $this->post('/evaluations/store', ['libeller' => 'x', 'date_evaluation' => '2026-11-05', 'heure_debut' => '08:00', 'heure_fin' => '10:00',
            'id_classe' => $this->classe->id_classe, 'id_matiere' => $this->matiere, 'id_annee_scolaire' => $this->annee, 'id_note' => $this->compo, 'mois' => 11])
            ->assertSessionHasErrors('id_trimestre');
    }
}
