<?php

namespace Tests\Integration;

use App\Models\Abonnement;
use App\Models\Ecole;
use App\Services\Support\CsbEcoles;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/** Bande des écoles du site de CSB : seulement l'abonnement actif, seulement nom / adresse / logo, retrait par l'Admin. */
class EcolesSurCsbTest extends IntegrationTestCase
{
    private const URL = 'https://csb.test/api/v1/references/ecoles';

    /** Réponse de CSB à l'envoi : logos qu'il demande. @var list<string> */
    private array $logosDemandes = [];

    private bool $csbEnPanne = false;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.csb_support.url' => 'https://csb.test', 'services.csb_support.token' => 'csb_test']);
        Http::fake(['csb.test/*' => fn () => $this->csbEnPanne ? Http::response('', 503) : Http::response(['logos_a_envoyer' => $this->logosDemandes])]);
    }

    private function ecole(array $champs = []): Ecole
    {
        $ecole = Ecole::withoutGlobalScopes()->findOrFail(1);
        $ecole->forceFill($champs + ['afficher_sur_csb' => true])->saveQuietly();

        return $ecole;
    }

    private function abonnement(?string $fin, string $statut = 'actif'): void
    {
        DB::table('abonnements')->where('ecole_id', 1)->delete();
        DB::table('abonnements')->insert(['ecole_id' => 1, 'offre_id' => DB::table('abonnement_offres')->value('id'), 'statut' => $statut, 'fin_at' => $fin, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** @return array<string, mixed>|null */
    private function ligneEnvoyee(): ?array
    {
        $envois = Http::recorded(fn (Request $r) => $r->url() === self::URL);

        return collect($envois->last()[0]['ecoles'])->firstWhere('ref', '1');
    }

    public function test_seuls_le_nom_l_adresse_et_le_logo_sortent_et_seulement_avec_un_abonnement_actif(): void
    {
        $this->ecole(['adresse' => 'Ségou', 'telephone' => '70000000', 'email' => 'secret@ecole.ml']);
        $this->abonnement(now()->addMonth()->toDateTimeString());

        app(CsbEcoles::class)->envoyerEcole(1);
        $ligne = $this->ligneEnvoyee();
        $this->assertTrue($ligne['visible']);
        $this->assertSame('Ségou', $ligne['adresse']);
        $this->assertSame(['adresse', 'logo_hash', 'nom', 'ref', 'visible'], collect($ligne)->keys()->sort()->values()->all());
        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer csb_test') && !str_contains($r->body(), 'secret@ecole.ml') && !str_contains($r->body(), '70000000'));

        // Abonnement expiré : retirée. Licence à vie (pas de date de fin) : affichée.
        $this->abonnement(now()->subDay()->toDateTimeString());
        app(CsbEcoles::class)->envoyerEcole(1);
        $this->assertFalse($this->ligneEnvoyee()['visible']);

        $this->abonnement(null);
        app(CsbEcoles::class)->envoyerEcole(1);
        $this->assertTrue($this->ligneEnvoyee()['visible']);

        // L'école a retiré son affichage.
        $this->ecole(['afficher_sur_csb' => false]);
        app(CsbEcoles::class)->envoyerEcole(1);
        $this->assertFalse($this->ligneEnvoyee()['visible']);
    }

    public function test_liste_complete_et_logo_envoye_a_la_demande(): void
    {
        $logo = 'images_ecoles/zz_test_csb.png';
        File::copy(public_path('assets/images/favicon-32x32.png'), public_path($logo));
        $this->ecole(['logoEcole' => $logo]);
        $this->abonnement(null);
        $this->logosDemandes = ['1'];

        try {
            $this->artisan('csb:ecoles')->assertSuccessful();
            Http::assertSent(fn (Request $r) => $r->url() === self::URL && $r['complet'] === true && collect($r['ecoles'])->firstWhere('ref', '1')['logo_hash'] === md5_file(public_path($logo)));
            Http::assertSent(fn (Request $r) => $r->url() === self::URL.'/1/logo' && $r->isMultipart());
        } finally {
            File::delete(public_path($logo));
        }
    }

    public function test_l_apercu_n_envoie_rien(): void
    {
        $this->artisan('csb:ecoles --apercu')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_l_admin_retire_son_ecole_les_autres_roles_non(): void
    {
        $this->abonnement(null);
        $this->ecole();

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->get('/profil')->assertOk()->assertSee('Afficher mon école sur le site de CSB');
        $this->put('/profil/ecole-sur-csb', [])->assertRedirect()->assertSessionHas('success');
        $this->assertFalse(Ecole::withoutGlobalScopes()->find(1)->afficher_sur_csb);
        // Le choix part aussitôt (après la réponse).
        Http::assertSent(fn (Request $r) => $r->url() === self::URL && $r['ecoles'][0]['visible'] === false);

        $this->put('/profil/ecole-sur-csb', ['afficher_sur_csb' => '1'])->assertRedirect();
        $this->assertTrue(Ecole::withoutGlobalScopes()->find(1)->afficher_sur_csb);

        $this->actingAs($this->compte('enseignant', 1))->withSession(['idEcole' => 1]);
        $this->put('/profil/ecole-sur-csb', [])->assertForbidden();
    }

    public function test_csb_injoignable_ne_bloque_jamais_l_enregistrement(): void
    {
        $this->csbEnPanne = true;
        $this->abonnement(null);
        $ecole = $this->ecole();

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);
        $this->put('/profil/ecole-sur-csb', [])->assertRedirect()->assertSessionHas('success');
        $this->assertFalse($ecole->fresh()->afficher_sur_csb);
    }
}
