<?php

namespace Tests\Integration;

use App\Models\Eleve;
use App\Support\PhotoEleve;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/** Photo d'un élève ajoutée après l'inscription : fiche, « Photos de la classe » (web) et application mobile. */
class PhotosElevesTest extends IntegrationTestCase
{
    /** @var list<string> */
    private array $fichiers = [];

    protected function tearDown(): void
    {
        foreach ($this->fichiers as $fichier) {
            File::delete(public_path($fichier));
        }
        parent::tearDown();
    }

    private function eleve(): object
    {
        $eleve = DB::table('eleve')->where('id_ecole', 1)->where('etat_dossier', 0)->first(['id_eleve', 'id_classe', 'prenom_eleve', 'nom_eleve']);
        if (!$eleve) {
            $this->markTestSkipped('Aucun élève dans l\'école 1.');
        }

        return $eleve;
    }

    public function test_photos_de_la_classe_puis_changement_de_photo(): void
    {
        $eleve = $this->eleve();
        DB::table('eleve')->where('id_eleve', $eleve->id_eleve)->update(['image' => 'assets/images/avatars/avatar-1.png']);
        $this->actingAs($this->adminComplet(1))->withSession(['idEcole' => 1]);

        $this->get('/eleves')->assertOk()->assertSee(route('eleves.photos'), false);
        $page = $this->get('/eleves/photos?classe='.$eleve->id_classe)->assertOk()->assertSee($eleve->nom_eleve)->assertSee(__('eleves.photo_manquante'));
        $this->assertTrue($page->viewData('eleves')->first()->sans_photo, 'les élèves sans photo d\'abord');

        // Photo de téléphone, grande : enregistrée en JPEG réduit à 800 px.
        $reponse = $this->postJson("/eleves/{$eleve->id_eleve}/photo", ['photo' => UploadedFile::fake()->image('photo.jpg', 2400, 1800)])
            ->assertOk()->assertJsonPath('message', __('eleves.photo_enregistree'));
        $premiere = Eleve::find($eleve->id_eleve)->image;
        $this->fichiers[] = $premiere;
        $this->assertStringStartsWith('image_eleves/eleve_'.$eleve->id_eleve.'_', $premiere);
        $this->assertSame(asset($premiere), $reponse->json('image_url'));
        [$largeur, $hauteur] = getimagesize(public_path($premiere));
        $this->assertSame([800, 600], [$largeur, $hauteur]);

        // Changement depuis la fiche : l'ancienne photo est supprimée.
        $this->from("/eleves/{$eleve->id_eleve}")->post("/eleves/{$eleve->id_eleve}/photo", ['photo' => UploadedFile::fake()->image('nouvelle.png', 300, 300)])
            ->assertRedirect("/eleves/{$eleve->id_eleve}")->assertSessionHas('success');
        $seconde = Eleve::find($eleve->id_eleve)->image;
        $this->fichiers[] = $seconde;
        $this->assertNotSame($premiere, $seconde);
        $this->assertFileDoesNotExist(public_path($premiere));
        $this->assertFileExists(public_path($seconde));

        $this->get("/eleves/{$eleve->id_eleve}")->assertOk()->assertSee(asset($seconde), false)->assertSee(__('eleves.photo_changer'));
        $this->get("/eleves/{$eleve->id_eleve}/edit")->assertOk()->assertSee('name="photo"', false);

        // Pas une image : refusé, la photo ne change pas.
        $this->postJson("/eleves/{$eleve->id_eleve}/photo", ['photo' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')])->assertUnprocessable();
        $this->assertSame($seconde, Eleve::find($eleve->id_eleve)->image);
    }

    public function test_photo_depuis_l_application_mobile_et_droits(): void
    {
        $eleve = $this->eleve();
        $jeton = $this->adminComplet(1)->createToken('mobile')->plainTextToken;

        $liste = $this->withToken($jeton)->getJson('/api/v1/eleves/photos?id_classe='.$eleve->id_classe)->assertOk();
        $this->assertNotEmpty($liste->json('classes'));
        $this->assertContains($eleve->id_eleve, array_column($liste->json('eleves'), 'id_eleve'));

        $reponse = $this->withToken($jeton)->post("/api/v1/eleves/{$eleve->id_eleve}/photo", ['photo' => UploadedFile::fake()->image('eleve.jpg', 600, 800)], ['Accept' => 'application/json'])
            ->assertOk();
        $this->fichiers[] = $reponse->json('image');
        $this->withToken($jeton)->getJson("/api/v1/eleves/{$eleve->id_eleve}")->assertOk()->assertJsonPath('image_url', $reponse->json('image_url'));

        // Élève d'une autre école : introuvable.
        $autre = DB::table('eleve')->where('id_ecole', '!=', 1)->value('id_eleve');
        if ($autre) {
            $this->withToken($jeton)->post("/api/v1/eleves/{$autre}/photo", ['photo' => UploadedFile::fake()->image('x.jpg')], ['Accept' => 'application/json'])->assertNotFound();
        }

        // Sans la permission « eleves_modification » : refusé.
        $parent = $this->compte('parent', 1);
        $this->actingAs($parent)->withSession(['idEcole' => 1])->get('/eleves/photos')->assertForbidden();
        $this->postJson("/eleves/{$eleve->id_eleve}/photo", ['photo' => UploadedFile::fake()->image('x.jpg')])->assertForbidden();
    }
}
