<?php

namespace Tests\Integration;

use App\Models\Classe;
use Illuminate\Support\Facades\DB;

/** Espace du SupAdmin : séries des classes du général de tous les établissements, réglées en une fois. */
class SeriesLyceesTest extends IntegrationTestCase
{
    public function test_le_supadmin_regle_les_series_de_tous_les_lycees(): void
    {
        $modele = Classe::withoutGlobalScopes()->where('idEcole', 1)->first();
        if (!$modele) {
            $this->markTestSkipped('Aucune classe dans l\'école 1.');
        }
        DB::table('classe')->where('ordreEnseignement', 'secondairegenerale')->update(['ordreEnseignement' => null]);
        $creer = function (string $nom, ?string $serie = null) use ($modele) {
            $c = $modele->replicate();
            $c->forceFill(['nom_classe' => $nom, 'ordreEnseignement' => 'secondairegenerale', 'serie' => $serie])->save();

            return $c->id_classe;
        };
        $dixieme = $creer('10ème année commune');
        $onzeL = $creer('11ème Lettres', 'tll');      // série de terminale donnée par erreur à une 11e
        $inconnue = $creer('Classe B');
        $tss = $creer('Terminale Sciences Sociales');
        $dejaChoisie = $creer('TSE', 'tse');

        $supadmin = $this->compte('SupAdmin');
        $this->actingAs($supadmin);
        $this->get('/series-lycees')->assertOk()->assertSee('Séries des lycées')->assertSee('10ème année commune')->assertSee('Classe B')
            ->assertDontSee('>TSE<', false)->assertSee('Non reconnue : à choisir');

        $this->post('/series-lycees/propositions')->assertRedirect()->assertSessionHas('success');
        $serie = fn ($id) => DB::table('classe')->where('id_classe', $id)->value('serie');
        $this->assertSame(['10cg', 'll', null, 'tss', 'tse'], [$serie($dixieme), $serie($onzeL), $serie($inconnue), $serie($tss), $serie($dejaChoisie)]);

        // La classe non reconnue se choisit à la main (envoi en un seul champ).
        $this->post('/series-lycees', ['series_json' => json_encode([$inconnue => 'sciences'])])->assertRedirect()->assertSessionHas('success', '1 classe(s) mise(s) à jour.');
        $this->assertSame('sciences', $serie($inconnue));
        $this->get('/series-lycees')->assertOk()->assertSee('Toutes les classes du secondaire général ont leur série.');

        // Réservé au SupAdmin.
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1])->get('/series-lycees')->assertForbidden();
        $this->post('/series-lycees/propositions')->assertForbidden();
    }
}
