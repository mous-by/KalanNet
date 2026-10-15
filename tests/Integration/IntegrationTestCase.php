<?php

namespace Tests\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Base des tests d'intégration : base MySQL locale, chaque test annulé par une
 * transaction (DatabaseTransactions — jamais RefreshDatabase, qui viderait la base).
 */
abstract class IntegrationTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('KALANNET_TESTS_INTEGRATION') !== '1') {
            $this->markTestSkipped('Tests d\'intégration : définir KALANNET_TESTS_INTEGRATION=1 (base MySQL locale).');
        }

        parent::setUp();

        $hote = (string) config('database.connections.mysql.host');
        if (app()->environment('production') || !in_array($hote, ['127.0.0.1', 'localhost', '::1'], true)) {
            $this->fail('Tests d\'intégration refusés : environnement de production ou base non locale.');
        }

        // Le site local tourne sous un sous-chemin (APP_URL) : les requêtes de test visent la racine.
        config(['app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');

        // Les comptes de test ne doivent pas être bloqués par le changement de mot de passe obligatoire.
        DB::table('utilisateurs')->update(['doit_changer_mdp' => 0]);
    }

    /** Premier compte actif d'un rôle, éventuellement dans une école. */
    protected function compte(string $droit, ?int $idEcole = null): User
    {
        $compte = User::withoutGlobalScopes()->where('droit', $droit)->where('statut', 1)
            ->when($idEcole, fn ($q) => $q->where('idEcole', $idEcole))->first();

        if (!$compte) {
            $this->markTestSkipped("Aucun compte « $droit » dans la base locale.");
        }

        return $compte;
    }

    /** Admin de l'école avec toutes les permissions (dans la transaction du test). */
    protected function adminComplet(int $idEcole): User
    {
        $admin = $this->compte('Admin', $idEcole);
        DB::table('user_permission')->where('user_id', $admin->idUtilisateur)->delete();
        DB::table('user_permission')->insert(DB::table('permissions')->pluck('id')
            ->map(fn ($id) => ['user_id' => $admin->idUtilisateur, 'permission_id' => $id])->all());

        return $admin->fresh();
    }

    /** Une autre école que celle donnée, qui a au moins un élève. */
    protected function autreEcole(int $idEcole): int
    {
        $id = DB::table('eleve')->where('id_ecole', '!=', $idEcole)->value('id_ecole');
        if (!$id) {
            $this->markTestSkipped('Il faut au moins deux écoles avec des élèves.');
        }

        return (int) $id;
    }
}
