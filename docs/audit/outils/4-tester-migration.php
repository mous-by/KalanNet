<?php
// Teste la migration proposée sur la base de test LOCALE db_kalannet_index_audit (jamais la base de travail).
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Garde-fou : uniquement sur un serveur MySQL local, jamais en production.
if (app()->environment('production') || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    exit("Refusé : environnement de production ou base non locale.\n");
}
config(['database.connections.mysql.database' => 'db_kalannet_index_audit']); DB::purge('mysql');
echo 'Base utilisée : ', DB::selectOne('select database() d')->d, PHP_EOL;
$noms = ['idx_utilisateurs_email', 'idx_utilisateurs_telephone', 'idx_utilisateurs_ecole', 'idx_eleve_classe_annee', 'idx_eleve_ecole_annee', 'idx_matiere_ecole', 'idx_matiere_ordre_matiere', 'idx_controle_eleve_periode', 'idx_controle_eleve_eleve', 'idx_conduite_periode', 'idx_ligne_reinscription_eleve_annee', 'idx_ligne_evaluation_eleve_periode'];
$present = fn () => collect(DB::select("select distinct index_name n from information_schema.statistics where table_schema = database()"))->pluck('n')->intersect($noms)->values()->all();
foreach (DB::select("select distinct table_name t, index_name n from information_schema.statistics where table_schema = database()") as $i) if (in_array($i->n, $noms)) DB::statement("ALTER TABLE `{$i->t}` DROP INDEX `{$i->n}`");
echo 'Avant up : ', count($present()), " index de l'audit\n";
$m = require dirname(__DIR__).'/migration-proposee/2026_10_16_100000_ajouter_index_audit_performance.php';
$d = hrtime(true); $m->up(); printf("up() en %.1f s : %d index — %s\n", (hrtime(true) - $d) / 1e9, count($present()), implode(', ', $present()));
$m->up(); echo 'up() relancé : ', count($present()), " index (aucun doublon)\n";
$e = DB::select("EXPLAIN select * from controle_eleve where id_ecole = 1 and id_classe = 5 and id_annee_scolaire = 2 and id_trimestre = 1")[0];
echo "EXPLAIN conduite : clé = {$e->key}\n";
$d = hrtime(true); $m->down(); printf("down() en %.1f s : %d index restants\n", (hrtime(true) - $d) / 1e9, count($present()));
$m->up(); echo 'up() après down : ', count($present()), " index\n";
