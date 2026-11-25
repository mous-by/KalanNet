<?php

namespace Tests\Integration;

use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\DB;

/** Émargement d'un cours de 30 minutes (Fondamental I) : 0,5 h est accepté, gardé tel quel et compté dans les totaux. */
class EmargementTrenteMinutesTest extends IntegrationTestCase
{
    public function test_un_emargement_de_trente_minutes_au_fondamental_un(): void
    {
        $annee = AnneeScolaire::enCours(1);
        $ligne = DB::table('ligneclasse as l')->join('classe as c', 'c.id_classe', '=', 'l.id_classe')
            ->where('c.idEcole', 1)->where('c.ordreEnseignement', 'fondamentale1')->whereNotNull('l.id_enseignants')
            ->first(['l.id_classe', 'l.id_matiere', 'l.id_enseignants']);
        $trimestre = DB::table('trimestre')->value('id_trimestre');
        $officielle = DB::table('classes_officielles')->value('id_classe_officielle');
        if (!$annee || !$ligne || !$trimestre || !$officielle) {
            $this->markTestSkipped('Il faut une classe du Fondamental I avec une matière attribuée dans l\'école 1.');
        }
        // La leçon saisie rejoint le programme officiel : la classe doit y être rattachée (annulé avec le test).
        DB::table('classe')->where('id_classe', $ligne->id_classe)->whereNull('id_classe_officielle')->update(['id_classe_officielle' => $officielle]);

        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1])
            ->post('/enseignants/emargements', [
                'id_enseignant' => $ligne->id_enseignants, 'id_classe' => $ligne->id_classe, 'id_matiere' => $ligne->id_matiere,
                'new_lecon_titre' => 'Lecture : le son « ou » (test 30 min)', 'nombre_heure' => '0.5',
                'id_trimestre' => $trimestre, 'id_anneeScolaire' => $annee->id_anneeScolaire, 'date_emargement' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('enseignants.emargements'));

        $emargement = DB::table('emargement')->where('id_enseignant', $ligne->id_enseignants)->orderByDesc('id_emargement')->first();
        $this->assertSame(0.5, (float) $emargement->nombre_heure, 'gardé à 0,5 h, sans arrondi à 0 ou 1');

        DB::table('emargement')->where('id_emargement', $emargement->id_emargement)->update(['valide' => 1]);
        $this->assertEqualsWithDelta(0.5, (float) DB::table('emargement')->where('id_emargement', $emargement->id_emargement)->sum('nombre_heure'), 0.001);
    }

    public function test_moins_d_un_quart_d_heure_est_refuse(): void
    {
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1])
            ->post('/enseignants/emargements', ['nombre_heure' => '0.1'])
            ->assertSessionHasErrors('nombre_heure');
    }
}
