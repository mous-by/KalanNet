<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;

/** Réinscription : même section au niveau suivant ; élève sans moyenne jamais présélectionné en redoublant. */
class ReinscriptionPropositionTest extends IntegrationTestCase
{
    private function classe(Classe $modele, string $nom): Classe
    {
        $classe = $modele->replicate();
        $classe->nom_classe = $nom;
        $classe->save();

        return $classe;
    }

    public function test_section_proposee_et_eleve_sans_moyenne_a_verifier(): void
    {
        $eleve = Eleve::where('id_ecole', 1)->where('etat_dossier', 0)->first();
        $modele = Classe::where('idEcole', 1)->first();
        $annees = AnneeScolaire::orderBy('id_anneeScolaire')->get();
        $source = $annees->firstWhere('id_anneeScolaire', $eleve?->id_annee);
        $cible = $source ? $annees->first(fn ($a) => (int) substr((string) $a->annee, 0, 4) > (int) substr((string) $source->annee, 0, 4)) : null;
        if (!$eleve || !$modele || !$cible) {
            $this->markTestSkipped('Données locales insuffisantes.');
        }

        // Noms de niveaux absents de la base locale, sans examen national et sans note.
        $b = $this->classe($modele, '4ème ZZB');
        $this->classe($modele, '5ème ZZA');
        $suivante = $this->classe($modele, '5ème ZZB');
        Eleve::whereKey($eleve->id_eleve)->update(['id_classe' => $b->id_classe]);

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $donnees = ['source_classe_id' => $b->id_classe, 'source_annee_id' => $source->id_anneeScolaire, 'target_annee_id' => $cible->id_anneeScolaire];

        $page = $this->post('/pedagogie/inscriptions/reinscription/apercu', $donnees)->assertOk();
        $this->assertSame($suivante->id_classe, $page->viewData('reinscriptionPreview')['targetClasse']->id_classe, '4ème B -> 5ème B, pas 5ème A');
        $page->assertSee(__('inscriptions.decision_a_choisir'))->assertDontSee('value="redoublant" selected', false);
        $this->assertDoesNotMatchRegularExpression('/\[selected\]" value="1" data-reinscription-check\s+checked/', $page->getContent());

        // Coché sans décision : refusé avec un message clair, rien n'est enregistré.
        $this->from('/pedagogie/inscriptions?tab=reinscription')->post('/pedagogie/inscriptions/reinscription', $donnees + ['eleves' => [
            $eleve->id_eleve => ['selected' => '1', 'id_eleve' => $eleve->id_eleve, 'decision' => ''],
        ]])->assertSessionHasErrors('eleves');
        $this->assertSame($b->id_classe, Eleve::find($eleve->id_eleve)->id_classe);
    }
}
