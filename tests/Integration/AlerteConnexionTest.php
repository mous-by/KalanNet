<?php

namespace Tests\Integration;

use App\Mail\NouvelleConnexion;
use App\Models\AppNotification;
use App\Support\AlerteConnexion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/** Alerte « Nouvelle connexion détectée » : nouvel appareil seulement, e-mail et notification, jamais bloquante. */
class AlerteConnexionTest extends IntegrationTestCase
{
    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1';
    private const WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';
    private const WINDOWS_MAJ = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    private \App\Models\User $compte;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->compte = $this->compte('Admin', 1);
        DB::table('utilisateurs')->where('idUtilisateur', $this->compte->idUtilisateur)->update([
            'pwd' => Hash::make('Secret-2026'), 'email' => 'directeur'.uniqid().'@ecole.ml', 'statut' => 1, 'doit_changer_mdp' => 0, 'mdp_temporaire_expire_at' => null,
        ]);
        // Un seul compte avec cet identifiant, pour une connexion directe.
        $this->compte = $this->compte->fresh();
        DB::table('connexions_appareils')->where('user_id', $this->compte->idUtilisateur)->delete();
        DB::table('app_notifications')->where('type', 'securite_connexion')->delete();
    }

    private function connexionWeb(string $navigateur, string $ip = '41.73.104.1'): void
    {
        $this->post('/logout');
        $this->withHeaders(['User-Agent' => $navigateur])->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/login', ['identifier' => $this->compte->email, 'pwd' => 'Secret-2026'])->assertRedirect();
        $this->assertAuthenticatedAs($this->compte);
    }

    private function alertes(): int
    {
        return AppNotification::where('user_id', $this->compte->idUtilisateur)->where('type', 'securite_connexion')->count();
    }

    public function test_appareil_reconnu_et_libelle(): void
    {
        $iphone = AlerteConnexion::appareil(self::IPHONE, 'web');
        $this->assertSame('Safari sur iPhone (iOS 18.7)', $iphone['appareil']);
        $this->assertSame('Chrome sur Windows (Windows)', AlerteConnexion::appareil(self::WINDOWS, 'web')['appareil']);
        // Mise à jour du navigateur : même appareil.
        $this->assertSame(AlerteConnexion::appareil(self::WINDOWS, 'web')['empreinte'], AlerteConnexion::appareil(self::WINDOWS_MAJ, 'web')['empreinte']);
        $this->assertSame('Application KalanNet sur Pixel 8', AlerteConnexion::appareil('okhttp/4.12', 'mobile', 'Pixel 8')['appareil']);
        // Deux téléphones du même modèle : distingués par l'identifiant de leur installation (application 1.0.2).
        $this->assertNotSame(AlerteConnexion::appareil('okhttp/4.12', 'mobile', 'Tecno Spark 20', 'a1')['empreinte'], AlerteConnexion::appareil('okhttp/4.12', 'mobile', 'Tecno Spark 20', 'b2')['empreinte']);
        // Ancienne application : nom générique, libellé lisible.
        $this->assertSame('Application KalanNet sur téléphone', AlerteConnexion::appareil('okhttp/4.12', 'mobile', 'mobile-app')['appareil']);
    }

    public function test_alerte_seulement_pour_un_nouvel_appareil(): void
    {
        // Premier appareil connu du compte : enregistré, sans alerte.
        $this->connexionWeb(self::WINDOWS);
        $this->assertSame(0, $this->alertes());
        Mail::assertNothingSent();

        // Même ordinateur, navigateur mis à jour, autre adresse IP : rien.
        $this->connexionWeb(self::WINDOWS_MAJ, '102.164.1.9');
        $this->assertSame(0, $this->alertes());

        // Un iPhone jamais vu : e-mail et notification.
        $this->connexionWeb(self::IPHONE);
        $this->assertSame(1, $this->alertes());
        Mail::assertSent(NouvelleConnexion::class, fn (NouvelleConnexion $m) => $m->hasTo($this->compte->email)
            && $m->ip === '41.73.104.1' && $m->appareil === 'Safari sur iPhone (iOS 18.7)');
        $this->assertStringContainsString('Ce n\'était pas vous', AppNotification::where('user_id', $this->compte->idUtilisateur)->where('type', 'securite_connexion')->value('message'));

        // Le même iPhone ensuite : plus d'alerte.
        $this->connexionWeb(self::IPHONE);
        $this->assertSame(1, $this->alertes());
        Mail::assertSentCount(1);
    }

    public function test_application_mobile_et_compte_sans_email(): void
    {
        DB::table('utilisateurs')->where('idUtilisateur', $this->compte->idUtilisateur)->update(['telephone' => '70112233']);
        $this->connexionWeb(self::WINDOWS);

        $this->postJson('/api/v1/auth/login', ['identifier' => $this->compte->email, 'pwd' => 'Secret-2026', 'device_name' => 'Tecno Spark 20', 'device_id' => 'install-1'])->assertOk();
        Mail::assertSent(NouvelleConnexion::class, fn (NouvelleConnexion $m) => $m->appareil === 'Application KalanNet sur Tecno Spark 20');
        // La même installation ensuite : pas de nouvel e-mail.
        $this->postJson('/api/v1/auth/login', ['identifier' => $this->compte->email, 'pwd' => 'Secret-2026', 'device_name' => 'Tecno Spark 20', 'device_id' => 'install-1'])->assertOk();
        Mail::assertSentCount(1);

        // Sans e-mail (parent connecté par son numéro) : la notification seule.
        DB::table('utilisateurs')->where('idUtilisateur', $this->compte->idUtilisateur)->update(['email' => null]);
        $this->postJson('/api/v1/auth/login', ['identifier' => '70112233', 'pwd' => 'Secret-2026', 'device_name' => 'Itel A70'])->assertOk();
        $this->assertSame(2, $this->alertes());
        Mail::assertSentCount(1);
    }

    public function test_une_panne_d_email_n_empeche_pas_de_se_connecter(): void
    {
        $this->connexionWeb(self::WINDOWS);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Serveur SMTP injoignable'));
        $this->connexionWeb(self::IPHONE);
        $this->assertSame(1, $this->alertes());
    }
}
