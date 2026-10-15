<?php
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Garde-fou : uniquement sur un serveur MySQL local, jamais en production.
if (app()->environment('production') || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    exit("Refusé : environnement de production ou base non locale.\n");
}
config(['database.connections.mysql.database' => 'db_kalannet_index_audit']); DB::purge('mysql');
if (($argv[1] ?? '') === 'index') {
    DB::statement('ALTER TABLE conduite DROP INDEX IF EXISTS idx_conduite_periode');
    file_put_contents($argv[2], 'go');
    $d = hrtime(true);
    DB::statement('ALTER TABLE conduite ADD INDEX idx_conduite_periode (id_classe, id_annee_scolaire, id_trimestre, id_eleve), ALGORITHM=INPLACE, LOCK=NONE');
    printf("création de l'index : %.0f ms\n", (hrtime(true) - $d) / 1e6);
} else {
    while (!file_exists($argv[2])) usleep(10000);
    usleep(200000);
    $max = 0; $n = 0; $fin = microtime(true) + 3;
    while (microtime(true) < $fin) {
        $d = hrtime(true);
        DB::insert('insert into conduite (id_annee_scolaire, id_classe, id_trimestre, id_eleve, note_conduite) values (9, 9, 9, ?, 16)', [$n]);
        DB::select('select note_conduite from conduite where id_eleve = ? limit 1', [$n]);
        $max = max($max, (hrtime(true) - $d) / 1e6); $n++;
    }
    DB::delete('delete from conduite where id_annee_scolaire = 9 and id_classe = 9 and id_trimestre = 9');
    printf("pendant la création : %d écritures + lectures en 3 s, la plus longue %.0f ms\n", $n, $max);
}
