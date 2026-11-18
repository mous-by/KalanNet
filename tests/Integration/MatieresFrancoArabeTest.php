<?php

namespace Tests\Integration;

use App\Models\Ecole;
use App\Models\Matiere;
use Illuminate\Support\Facades\DB;

/** Écoles franco-arabes : les deux catalogues communs ; écoles classiques : jamais les matières franco-arabes. */
class MatieresFrancoArabeTest extends IntegrationTestCase
{
    public function test_une_ecole_franco_arabe_voit_les_deux_catalogues(): void
    {
        $classique = Matiere::create(['nom_matiere' => 'Histoire-Géo test classique', 'id_ecole' => null, 'est_franco_arabe' => false]);
        $franco = Matiere::create(['nom_matiere' => 'Histoire-Géo test franco', 'id_ecole' => null, 'est_franco_arabe' => true]);
        $autreEcole = Matiere::create(['nom_matiere' => 'Matière d\'une autre école', 'id_ecole' => 999999, 'est_franco_arabe' => false]);
        $ids = fn (Ecole $e) => Matiere::query()->visiblesPourEcole($e)->whereIn('id_matiere', [$classique->id_matiere, $franco->id_matiere, $autreEcole->id_matiere])->pluck('id_matiere')->all();

        DB::table('ecole')->where('idEcole', 1)->update(['franco_arabe' => 1]);
        $this->assertEqualsCanonicalizing([$classique->id_matiere, $franco->id_matiere], $ids(Ecole::withoutGlobalScopes()->find(1)), 'franco-arabe : les deux catalogues communs');

        DB::table('ecole')->where('idEcole', 1)->update(['franco_arabe' => 0]);
        $this->assertNotContains($franco->id_matiere, $ids(Ecole::withoutGlobalScopes()->find(1)), 'classique : jamais les matières franco-arabes');
        $this->assertContains($classique->id_matiere, $ids(Ecole::withoutGlobalScopes()->find(1)));
    }

    public function test_message_clair_sans_ordre_d_enseignement(): void
    {
        $this->actingAs($this->compte('SupAdmin'));
        $this->post('/pedagogie/matieres', ['nom_matiere' => 'Histoire-Géographie', 'est_franco_arabe' => 1])
            ->assertSessionHasErrors(['ordre_enseignement' => 'Cochez au moins un ordre d’enseignement (Fondamental I, Fondamental II, Secondaire…) : la case « franco-arabe » indique seulement le type de la matière, pas les classes où elle est enseignée.']);
    }
}
