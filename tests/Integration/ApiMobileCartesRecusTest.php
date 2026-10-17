<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Application mobile (vc19) : cartes scolaires (modèles, aperçu, PDF en GET) et reçus de paiement. */
class ApiMobileCartesRecusTest extends IntegrationTestCase
{
    public function test_cartes_scolaires_depuis_le_mobile(): void
    {
        $jeton = $this->adminComplet(1)->createToken('mobile')->plainTextToken;
        $eleve = DB::table('eleve')->where('id_ecole', 1)->where('etat_dossier', 0)->first(['id_eleve', 'id_classe', 'id_annee']);
        if (!$eleve) {
            $this->markTestSkipped('Aucun élève dans l\'école 1.');
        }

        $donnees = $this->withToken($jeton)->getJson("/api/v1/eleves/cartes-scolaires?id_classe={$eleve->id_classe}&id_annee={$eleve->id_annee}")
            ->assertOk()->json();
        $this->assertSame(['classique', 'elegant', 'franco_arabe', 'sante', 'primaire', 'recto_verso'], array_column($donnees['modeles'], 'code'));
        $this->assertStringEndsWith('/assets/images/cartes/classique.png', $donnees['modeles'][0]['vignette']);
        $this->assertNotEmpty($donnees['eleves']);
        $this->assertArrayHasKey('entete_gauche', $donnees);

        $requete = "id_classe={$eleve->id_classe}&id_annee={$eleve->id_annee}&template=elegant&selected_eleves={$eleve->id_eleve}&show_qr=1";
        $this->withToken($jeton)->get("/api/v1/eleves/cartes-scolaires/apercu?{$requete}")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->withToken($jeton)->get("/api/v1/eleves/cartes-scolaires/pdf?{$requete}&imprimer=1")->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_recus_de_paiement_depuis_le_mobile(): void
    {
        $jeton = $this->adminComplet(1)->createToken('mobile')->plainTextToken;
        $paiement = DB::table('paiement')->where('idEcole', 1)->value('id_paiement');
        if (!$paiement) {
            $this->markTestSkipped('Aucun paiement dans l\'école 1.');
        }
        foreach (['recu', 'thermique'] as $format) {
            $this->withToken($jeton)->get("/api/v1/finances/paiements/{$paiement}/{$format}")->assertOk()->assertHeader('content-type', 'application/pdf');
        }
        // Paiement d'une autre école : introuvable.
        $autre = DB::table('paiement')->where('idEcole', '!=', 1)->value('id_paiement');
        if ($autre) {
            $this->withToken($jeton)->get("/api/v1/finances/paiements/{$autre}/recu")->assertNotFound();
        }
    }
}
