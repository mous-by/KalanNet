<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Support\RapportSecondaireDonnees;
use Illuminate\Support\Facades\DB;

/**
 * Exclusion pour insuffisance de travail ou pour épuisement de scolarité, et abandon : choisis à la réinscription, repris
 * par le rapport de fin d'année du secondaire même une fois les élèves réinscrits.
 */
class ExclusionMotifsTest extends IntegrationTestCase
{
    private AnneeScolaire $annee;
    private int $suivante;
    /** @var array<string, Classe> */
    private array $classes = [];
    /** @var list<int> */
    private array $eleves;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('rapports_secondaire')->where('id_ecole', 1)->delete();
        $this->annee = AnneeScolaire::enCours(1);
        $modele = Classe::where('idEcole', 1)->first();
        $eleves = DB::table('eleve')->where('id_ecole', 1)->limit(5)->pluck('id_eleve')->all();
        if (!$modele || count($eleves) < 5) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }
        DB::table('ecole')->where('idEcole', 1)->update(['typeEcole' => 'Secondaire Generale']);
        DB::table('classe')->where('idEcole', 1)->update(['ordreEnseignement' => null]);
        foreach (['10' => '10ème année commune', '11s' => '11ème Sciences'] as $cle => $nom) {
            $classe = $modele->replicate();
            $classe->nom_classe = $nom;
            $classe->ordreEnseignement = 'secondairegenerale';
            $classe->serie = null;
            $classe->save();
            $this->classes[$cle] = $classe;
        }
        $this->eleves = $eleves;
        DB::table('eleve')->where('id_ecole', 1)->where('id_annee', $this->annee->id_anneeScolaire)->update(['etat_dossier' => 1]);
        DB::table('eleve')->whereIn('id_eleve', $eleves)->update(['id_classe' => $this->classes['10']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'etat_dossier' => 0, 'genre_eleve' => 'Masculin']);
        // Comme en vrai : chaque élève est arrivé dans l'année par une (ré)inscription.
        foreach ($eleves as $id) {
            $r = DB::table('reinscription')->insertGetId(['statut' => 'passant', 'date_reinscription' => now()->toDateString(), 'enrolement' => 1]);
            DB::table('ligne_reinscription')->insert(['id_eleve' => $id, 'id_classe' => $this->classes['10']->id_classe, 'id_annee' => $this->annee->id_anneeScolaire, 'id_reinscription' => $r]);
        }
        $debut = (int) substr((string) $this->annee->date_debut, 0, 4) + 1;
        $this->suivante = DB::table('anneescolaire')->insertGetId(['annee' => $debut.'-'.($debut + 1), 'date_debut' => $debut.'-10-01', 'date_fin' => ($debut + 1).'-07-31', 'id_ecole' => null]);
    }

    private function reinscrire(array $decisions)
    {
        $eleves = [];
        foreach ($decisions as $i => [$decision, $classe, $motif]) {
            $id = $this->eleves[$i];
            $eleves[$id] = ['selected' => '1', 'id_eleve' => $id, 'decision' => $decision, 'id_classe' => $classe?->id_classe, 'motif_decision' => $motif];
        }

        return $this->post('/pedagogie/inscriptions/reinscription', [
            'source_classe_id' => $this->classes['10']->id_classe, 'source_annee_id' => $this->annee->id_anneeScolaire,
            'target_annee_id' => $this->suivante, 'eleves' => $eleves,
        ]);
    }

    public function test_fin_d_annee_porte_sur_l_annee_qui_se_termine(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        // L'horloge avance de plusieurs mois : abonnement sans échéance pour l'école de test.
        DB::table('abonnements')->where('ecole_id', 1)->update(['statut' => 'actif', 'fin_at' => null]);
        $choisie = fn () => $this->get('/rapport-secondaire/fin')->assertOk()->viewData('annee');
        $fin = (string) $this->annee->date_fin; // fin de l'année en cours (fin août)
        if (!$fin) {
            $this->markTestSkipped("L'année en cours n'a pas de date de fin.");
        }

        // Trois mois après sa fin : c'est elle, l'année qui vient de se terminer.
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse($fin)->addMonths(3));
        $this->assertSame($this->annee->annee, $choisie()->annee);
        // Deux mois avant sa fin (fin juin) : c'est encore elle, l'année qui se termine.
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse($fin)->subMonths(2));
        $this->assertSame($this->annee->annee, $choisie()->annee);
        // Six mois avant sa fin : elle n'est pas finie, c'est l'année précédente qui est proposée.
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse($fin)->subMonths(6));
        $this->assertNotSame($this->annee->annee, $choisie()->annee);
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_motifs_d_exclusion_a_la_reinscription_et_rapport_de_fin_d_annee(): void
    {
        DB::table('ecole')->where('idEcole', 1)->update(['statut' => 'public']); // sans formule de paiement obligatoire
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        // L'écran propose les deux motifs officiels.
        $this->get('/pedagogie/inscriptions?tab=reinscription')->assertOk();
        $page = $this->post('/pedagogie/inscriptions/reinscription/apercu', ['source_classe_id' => $this->classes['10']->id_classe, 'source_annee_id' => $this->annee->id_anneeScolaire, 'target_annee_id' => $this->suivante]);
        $page->assertOk()->assertSee('Exclu : insuffisance de travail')->assertSee('Exclu : épuisement de scolarité');

        // Abandon : motif toujours exigé ; exclusion : le motif officiel suffit.
        $this->reinscrire([0 => ['abandon', null, '']])->assertSessionHasErrors('motif_decision');
        $this->reinscrire([
            0 => ['passant', $this->classes['11s'], null],
            1 => ['redoublant', $this->classes['10'], null],
            2 => ['exclu_insuffisance', null, null],
            3 => ['exclu_epuisement', null, null],
            4 => ['abandon', null, 'Parti au village'],
        ])->assertSessionHasNoErrors();

        $statuts = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
            ->where('lr.id_annee', $this->suivante)->pluck('r.statut', 'lr.id_eleve');
        $this->assertSame('exclu_insuffisance', $statuts[$this->eleves[2]]);
        $this->assertSame('exclu_epuisement', $statuts[$this->eleves[3]]);
        // Exclus et abandons : dossier « retiré », sorti des listes de classe.
        $this->assertSame([2, 2, 2], [Eleve::find($this->eleves[2])->etat_dossier, Eleve::find($this->eleves[3])->etat_dossier, Eleve::find($this->eleves[4])->etat_dossier]);
        $this->assertSame(0, (int) Eleve::find($this->eleves[0])->etat_dossier);

        // Rapport de fin de l'année écoulée, établi après la réinscription : chacun est compté avec sa décision.
        $d = RapportSecondaireDonnees::calculer(Ecole::withoutGlobalScopes()->find(1), $this->annee, 'fin');
        $this->assertSame(['effectif_g' => 5, 'p11S_g' => 1, 'red_g' => 1, 'insuffisance_g' => 1, 'epuisement_g' => 1, 'abandon_g' => 1], $d['t']['T6']['10CG']);
    }
}
