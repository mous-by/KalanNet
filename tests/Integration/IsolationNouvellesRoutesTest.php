<?php

namespace Tests\Integration;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Routes ajoutées ou modifiées après l'audit d'octobre 2026 : un Admin qui
 * vise la classe d'une autre école est refusé et rien n'est modifié.
 */
class IsolationNouvellesRoutesTest extends IntegrationTestCase
{
    private function classeEtrangere(int $idEcole): object
    {
        $classe = DB::table('classe as c')
            ->where('c.idEcole', '!=', $idEcole)
            ->whereExists(fn ($q) => $q->selectRaw(1)->from('eleve')->whereColumn('eleve.id_classe', 'c.id_classe')->where('eleve.etat_dossier', 0))
            ->first(['c.id_classe', 'c.nom_classe', 'c.idEcole']);
        if (!$classe) {
            $this->markTestSkipped('Il faut une classe avec des élèves dans une autre école.');
        }

        return $classe;
    }

    private function compter(object $classe): array
    {
        return [
            'eleves' => DB::table('eleve')->where('id_classe', $classe->id_classe)->count(),
            'inscriptions' => DB::table('ligne_inscription')->where('id_classe', $classe->id_classe)->count(),
            'paiements' => DB::table('paiement')->where('id_classe', $classe->id_classe)->count(),
        ];
    }

    public function test_reinitialiser_la_classe_d_une_autre_ecole_est_refuse(): void
    {
        $admin = $this->adminComplet(1);
        $classe = $this->classeEtrangere(1);
        $avant = $this->compter($classe);

        $reponse = $this->actingAs($admin)->withSession(['idEcole' => 1])
            ->delete("/classes/{$classe->id_classe}/eleves/reinitialiser", ['confirmation_classe' => $classe->nom_classe]);

        $this->assertContains($reponse->status(), [403, 404], 'réinitialisation : ' . $reponse->status());
        $this->assertSame($avant, $this->compter($classe), 'aucun élève supprimé');
    }

    public function test_inscrire_dans_la_classe_d_une_autre_ecole_est_refuse(): void
    {
        $admin = $this->adminComplet(1);
        $classe = $this->classeEtrangere(1);
        $annee = DB::table('eleve')->where('id_classe', $classe->id_classe)->value('id_annee');
        $avant = $this->compter($classe);
        $eleve = ['prenom_eleve' => 'Intrus', 'nom_eleve' => 'Test', 'genre_eleve' => 'Masculin', 'id_classe' => $classe->id_classe, 'id_annee' => $annee, 'date_naissance' => 'vers 2012'];

        // Inscription individuelle (web).
        $web = $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/pedagogie/inscriptions', $eleve);
        $this->assertNotSame(500, $web->status());

        // Import Excel en groupe (web), avec la case « tous subventionnés ».
        $classeur = new Spreadsheet();
        $classeur->getActiveSheet()->fromArray([['Prénom', 'Nom', 'Naissance'], ['Intrus', 'Excel', '22/04/2012']]);
        $fichier = tempnam(sys_get_temp_dir(), 'kn') . '.xlsx';
        (new Xlsx($classeur))->save($fichier);
        $import = $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/pedagogie/inscriptions/groupe/import', [
            'fichier_excel' => new UploadedFile($fichier, 'eleves.xlsx', null, null, true),
            'id_classe' => $classe->id_classe, 'id_annee' => $annee, 'subventionnes_tous' => 1,
        ]);
        $this->assertNotSame(500, $import->status());

        // Inscription par l'API mobile.
        $api = $this->withToken($admin->createToken('test')->plainTextToken)->postJson('/api/v1/eleves', $eleve);
        $this->assertContains($api->status(), [403, 404, 422], 'API : ' . $api->status());

        $this->assertSame($avant, $this->compter($classe), 'aucun élève inscrit dans la classe étrangère');
        @unlink($fichier);
    }

    public function test_finances_sur_la_classe_d_une_autre_ecole_sont_refusees(): void
    {
        $admin = $this->adminComplet(1);
        $classe = $this->classeEtrangere(1);
        $eleve = DB::table('eleve')->where('id_classe', $classe->id_classe)->where('etat_dossier', 0)->first(['id_eleve', 'id_annee']);
        $avant = $this->compter($classe);
        $paiementsEcole = DB::table('paiement')->where('idEcole', 1)->count();

        // Subvention État ciblée sur une classe étrangère.
        $subvention = $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/finances/subventions-etat', [
            'annee_scolaire_id' => $eleve->id_annee, 'classe_id' => $classe->id_classe,
            'date_paiement' => now()->toDateString(), 'montant_recu' => 50000,
        ]);
        $this->assertContains($subvention->status(), [403, 404], 'subvention : ' . $subvention->status());

        // Paiement groupé sur un élève d'une classe étrangère.
        $paiement = $this->actingAs($admin)->withSession(['idEcole' => 1])->post('/finances/paiements/groupes', [
            'id_classe' => $classe->id_classe, 'id_annee' => $eleve->id_annee, 'id_trimestre' => 1,
            'date_paiement' => now()->toDateString(), 'alert' => [$eleve->id_eleve], 'id_eleve' => [$eleve->id_eleve],
            'id_planification' => [1], 'motif' => ['x'], 'montant_recu' => [1000],
        ]);
        $this->assertContains($paiement->status(), [403, 404], 'paiement groupé : ' . $paiement->status());

        $this->assertSame($avant, $this->compter($classe));
        $this->assertSame($paiementsEcole, DB::table('paiement')->where('idEcole', 1)->count(), 'aucun paiement créé');
    }

    public function test_bulletins_api_d_une_autre_ecole_et_cachet_reserve_a_l_admin(): void
    {
        $admin = $this->adminComplet(1);
        $classe = $this->classeEtrangere(1);
        $jeton = $admin->createToken('test')->plainTextToken;

        foreach (["/api/v1/bulletins/classes/{$classe->id_classe}", "/api/v1/finances/paiements?id_classe={$classe->id_classe}&id_annee=1"] as $url) {
            $reponse = $this->withToken($jeton)->getJson($url);
            $this->assertContains($reponse->status(), [403, 404], "$url : {$reponse->status()}");
        }

        // Cachet : un Gestionnaire ne peut ni l'ajouter ni le supprimer.
        $gestionnaire = $this->compte('Gestionnaire');
        $avant = DB::table('ecole')->where('idEcole', $gestionnaire->idEcole)->value('cachet');
        $this->actingAs($gestionnaire)->withSession(['idEcole' => $gestionnaire->idEcole])
            ->delete('/eleves/cartes-scolaires/cachet')->assertForbidden();
        $this->actingAs($gestionnaire)->withSession(['idEcole' => $gestionnaire->idEcole])
            ->post('/eleves/cartes-scolaires/cachet', ['cachet' => UploadedFile::fake()->image('cachet.png')])->assertForbidden();
        $this->assertSame($avant, DB::table('ecole')->where('idEcole', $gestionnaire->idEcole)->value('cachet'));
    }
}
