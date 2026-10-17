<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Cartes scolaires : les 6 modèles s'impriment et chacun peut être visualisé avant impression. */
class CartesScolairesTest extends IntegrationTestCase
{
    private const MODELES = ['institutionnel', 'moderne', 'vertical', 'epure', 'horizon', 'compact'];

    public function test_apercu_de_chaque_modele_sans_classe_puis_avec_classe(): void
    {
        $admin = $this->adminComplet(1);
        $page = $this->actingAs($admin)->withSession(['idEcole' => 1])->get('/eleves/cartes-scolaires')->assertOk()->getContent();
        foreach (self::MODELES as $modele) {
            $this->assertStringContainsString('class="btn btn-sm btn-outline-secondary ms-auto carte-apercu" data-template="'.$modele.'"', $page);

            // Sans classe : élèves d'exemple.
            $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/apercu', ['template' => $modele, 'card_title' => 'CARTE TEST'])
                ->assertOk()->assertHeader('content-type', 'application/pdf')
                ->assertHeader('content-disposition', 'inline; filename=Apercu_carte_'.$modele.'.pdf');
        }

        // Avec une classe : ses élèves, et l'impression complète.
        $eleve = DB::table('eleve')->where('id_ecole', 1)->where('etat_dossier', 0)->first(['id_classe', 'id_annee']);
        if (!$eleve) {
            $this->markTestSkipped('Aucun élève dans l\'école 1.');
        }
        $params = ['id_classe' => $eleve->id_classe, 'id_annee' => $eleve->id_annee, 'template' => 'horizon'];
        $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/apercu', $params)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/pdf', $params)->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_modele_inconnu_refuse(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1])
            ->post('/eleves/cartes-scolaires/apercu', ['template' => 'inexistant'])->assertSessionHasErrors('template');
    }
}
