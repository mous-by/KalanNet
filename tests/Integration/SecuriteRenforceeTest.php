<?php

namespace Tests\Integration;

use App\Models\AbonnementPaiement;
use App\Support\FichierTeleverse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Audit de sécurité du 31/10/2026 : webhook de paiement des abonnements, noms des fichiers envoyés, en-têtes de
 * sécurité, coefficient jusqu'à 10.
 */
class SecuriteRenforceeTest extends IntegrationTestCase
{
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    private function paiementEnAttente(string $fournisseur): AbonnementPaiement
    {
        $offre = DB::table('abonnement_offres')->where('duree_jours', '>', 0)->first();
        if (!$offre) {
            $this->markTestSkipped('Aucune offre d\'abonnement dans la base locale.');
        }

        return AbonnementPaiement::create([
            'ecole_id' => 1, 'offre_id' => $offre->id, 'fournisseur' => $fournisseur, 'reference' => 'ABN-TEST-'.uniqid(),
            'montant' => 1000, 'devise' => 'XOF', 'statut' => 'en_attente',
        ]);
    }

    public function test_le_webhook_ne_marque_rien_paye_sans_signature_valide(): void
    {
        $paiement = $this->paiementEnAttente('orange_money');
        $corps = json_encode(['reference' => $paiement->reference, 'status' => 'paid']);
        $envoyer = fn (array $entetes = []) => $this->call('POST', '/abonnements/webhook/orange_money', [], [], [], $entetes + ['CONTENT_TYPE' => 'application/json'], $corps);

        // Faille corrigée : n'importe qui connaissant la référence marquait le paiement payé.
        config(['services.abonnements.orange_money.webhook_secret' => null]);
        $envoyer()->assertStatus(401);
        $this->assertSame('en_attente', $paiement->fresh()->statut);

        config(['services.abonnements.orange_money.webhook_secret' => 'secret-de-test']);
        $envoyer(['HTTP_X_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $corps, 'autre-secret')])->assertStatus(401);
        $this->assertSame('en_attente', $paiement->fresh()->statut);

        $envoyer(['HTTP_X_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $corps, 'secret-de-test')])->assertOk();
        $this->assertSame('paye', $paiement->fresh()->statut);

        // Wave sans secret configuré : refusé aussi (avant : accepté sans vérification).
        $wave = $this->paiementEnAttente('wave');
        config(['services.abonnements.wave.webhook_secret' => null]);
        $this->postJson('/abonnements/webhook/wave', ['reference' => $wave->reference, 'status' => 'paid'])->assertStatus(401);
        $this->assertSame('en_attente', $wave->fresh()->statut);
    }

    /** Vrai fichier envoyé (type détecté sur le contenu ; les faux fichiers de test le déduisent du nom). */
    private function envoi(string $nom, string $contenu): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'kn');
        file_put_contents($chemin, $contenu);

        return new UploadedFile($chemin, $nom, null, null, true);
    }

    public function test_l_extension_des_fichiers_vient_du_contenu_pas_du_nom(): void
    {
        $gif = base64_decode(self::GIF).'<script>alert(1)</script>';
        foreach (['photo.html', 'photo.svg', 'photo.php', 'photo.gif'] as $nom) {
            $nomSur = FichierTeleverse::nom($this->envoi($nom, $gif), 'test_');
            $this->assertStringEndsWith('.gif', $nomSur, "{$nom} est enregistré en .gif");
        }

        $this->expectException(ValidationException::class);
        FichierTeleverse::nom($this->envoi('page.jpg', '<html><script>alert(1)</script></html>'), 'test_');
    }

    public function test_photo_de_profil_toujours_enregistree_sous_une_extension_d_image(): void
    {
        $admin = $this->adminComplet(1);
        $this->actingAs($admin)->withSession(['idEcole' => 1]);
        $champs = ['nomPrenom' => $admin->nomPrenom, 'email' => $admin->email ?: 'admin'.$admin->idUtilisateur.'@test.ml'];

        // Une page HTML qui n'est pas une image : refusée.
        $this->from('/profil')->put('/profil', $champs + ['image' => $this->envoi('page.html', '<html><script>alert(1)</script></html>')])
            ->assertSessionHasErrors('image');

        // Un vrai GIF nommé photo.html (avant : enregistré en .html, servi comme page web) : enregistré en .gif.
        $this->from('/profil')->put('/profil', $champs + ['image' => $this->envoi('photo.html', base64_decode(self::GIF))])->assertSessionHasNoErrors();
        $image = $admin->fresh()->image;
        File::delete(public_path($image));
        $this->assertMatchesRegularExpression('#^images_utilisateurs/user_[0-9a-f.]+\.gif$#', $image);
    }

    public function test_en_tetes_de_securite_et_coefficient_jusqu_a_10(): void
    {
        $this->get('/login')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->postJson('/api/v1/auth/login', [])->assertHeader('X-Content-Type-Options', 'nosniff');

        $classe = DB::table('classe')->where('idEcole', 1)->value('id_classe');
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->from("/classes/{$classe}/edit")->put("/classes/{$classe}", ['coefficient' => [10]])->assertSessionDoesntHaveErrors('coefficient.0');
        $this->from("/classes/{$classe}/edit")->put("/classes/{$classe}", ['coefficient' => [11]])->assertSessionHasErrors('coefficient.0');
    }
}
