<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Cartes scolaires : les 6 modèles s'impriment et chacun peut être visualisé avant impression. */
class CartesScolairesTest extends IntegrationTestCase
{
    private const MODELES = ['classique', 'elegant', 'franco_arabe', 'sante', 'primaire', 'recto_verso'];

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
        $params = ['id_classe' => $eleve->id_classe, 'id_annee' => $eleve->id_annee, 'template' => 'elegant'];
        $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/apercu', $params)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/pdf', $params)->assertOk()->assertHeader('content-type', 'application/pdf');
        // Bouton Imprimer : PDF affiché dans la page (imprimé directement) ; Télécharger : fichier.
        $this->assertStringStartsWith('inline;', $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/pdf', $params + ['imprimer' => 1])->headers->get('content-disposition'));
        $this->assertStringStartsWith('attachment;', $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/pdf', $params + ['imprimer' => 0])->headers->get('content-disposition'));
    }

    public function test_anciens_modeles_acceptes_et_liste_sans_recharger(): void
    {
        $admin = $this->adminComplet(1);
        // Liens et applications existants : les anciens noms de modèles restent acceptés.
        foreach (['institutionnel', 'vertical', 'compact'] as $ancien) {
            $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/eleves/cartes-scolaires/apercu', ['template' => $ancien])
                ->assertOk()->assertHeader('content-type', 'application/pdf');
        }
        $this->assertSame('recto_verso', \App\Support\ModelesCarte::normaliser('vertical'));

        // Classe changée : seule la liste est renvoyée.
        $eleve = DB::table('eleve')->where('id_ecole', 1)->where('etat_dossier', 0)->first(['id_classe', 'id_annee']);
        $liste = $this->actingAs($admin)->withSession(['idEcole' => 1])->withHeader('X-Cartes-Liste', '1')
            ->get("/eleves/cartes-scolaires?id_classe={$eleve->id_classe}&id_annee={$eleve->id_annee}")->assertOk()->getContent();
        $this->assertStringContainsString('id="cards-form"', $liste);
        $this->assertStringNotContainsString('<html', $liste);
    }

    public function test_recto_verso_rectos_puis_versos_en_miroir(): void
    {
        $admin = $this->adminComplet(1);
        $eleve = DB::table('eleve')->where('id_ecole', 1)->where('etat_dossier', 0)->first(['id_classe', 'id_annee']);
        $html = view('pdf.cartes_scolaires', [
            'eleves' => \App\Models\Eleve::where('id_classe', $eleve->id_classe)->where('id_annee', $eleve->id_annee)->where('etat_dossier', 0)->take(4)->get(),
            'classe' => \App\Models\Classe::find($eleve->id_classe), 'annee' => \App\Models\AnneeScolaire::find($eleve->id_annee), 'ecole' => null,
            'config' => ['template' => 'recto_verso', 'primary_color' => '#1e3a8a', 'secondary_color' => '#3b82f6', 'card_title' => 'CARTE', 'signature_label' => 'Le Directeur',
                'signature_image' => null, 'show_mali_flag' => false, 'mali_flag_style' => 'corner', 'show_school_logo' => false, 'show_qr' => false],
            'qrCodes' => [], 'schoolName' => 'École', 'schoolLogoPath' => null, 'adminPhone' => null,
        ])->render();

        // Une feuille de rectos puis une feuille de versos ; 4e élève : 1re colonne au recto, 3e au verso.
        $this->assertSame(2, substr_count($html, 'class="page feuille"'));
        $this->assertStringContainsString('Versos — page 2', $html);
        preg_match_all('#<div class="place" style="left: ([\d.]+)mm; top: ([\d.]+)mm;">#', $html, $m);
        $this->assertSame(['18', '78', '138', '18', '138', '78', '18', '138'], $m[1]);
    }

    public function test_code_barres_du_matricule(): void
    {
        $valeurs = \App\Support\CodeBarres::symboles('EX-0001');
        $this->assertSame(104, $valeurs[0], 'départ jeu B');
        $this->assertSame(106, end($valeurs), 'arrêt');
        $this->assertCount(7 + 3, $valeurs);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', \App\Support\CodeBarres::svg('EX-0001'));
    }

    public function test_modele_inconnu_refuse(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1])
            ->post('/eleves/cartes-scolaires/apercu', ['template' => 'inexistant'])->assertSessionHasErrors('template');
    }
}
