<?php
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Garde-fou : uniquement sur un serveur MySQL local, jamais en production.
if (app()->environment('production') || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    exit("Refusé : environnement de production ou base non locale.\n");
}
config(['database.connections.mysql.database' => 'db_kalannet_index_audit']); DB::purge('mysql');
$ok = 0; $ko = 0; $check = function ($c, $l) use (&$ok, &$ko) { $c ? $ok++ : $ko++; echo ($c ? '  OK  ' : '  ÉCHEC ').$l."\n"; };
$index = fn () => collect(DB::select("select table_name t, index_name i, group_concat(column_name order by seq_in_index) c from information_schema.statistics where table_schema = database() group by table_name, index_name"))->mapWithKeys(fn ($x) => ["{$x->t}.{$x->i}" => $x->c]);
$noms = ['idx_utilisateurs_email', 'idx_utilisateurs_telephone', 'idx_utilisateurs_ecole', 'idx_eleve_classe_annee', 'idx_eleve_ecole_annee', 'idx_matiere_ecole', 'idx_matiere_ordre_matiere', 'idx_controle_eleve_periode', 'idx_controle_eleve_eleve', 'idx_conduite_periode', 'idx_ligne_reinscription_eleve_annee', 'idx_ligne_evaluation_eleve_periode', 'existant_conduite', 'idx_matiere_ecole'];
foreach (DB::select("select distinct table_name t, index_name n from information_schema.statistics where table_schema = database()") as $i) if (in_array($i->n, $noms)) DB::statement("ALTER TABLE `{$i->t}` DROP INDEX `{$i->n}`");
// Pièges préexistants (comme pourrait en contenir la production).
DB::statement('ALTER TABLE conduite ADD INDEX existant_conduite (id_classe, id_annee_scolaire, id_trimestre, id_eleve, note_conduite)');
DB::statement('ALTER TABLE matiere ADD INDEX idx_matiere_ecole (nom_matiere)');
$avant = $index();
$m = require dirname(__DIR__).'/migration-proposee/2026_10_16_100000_ajouter_index_audit_performance.php';
$m->up();
$apres = $index();
$crees = $apres->keys()->diff($avant->keys())->values();
echo 'Créés : ', $crees->implode(', '), "\n";
$check($crees->count() === 8, '8 index créés (10 proposés − 2 déjà couverts)');
$check(!$apres->has('conduite.idx_conduite_periode'), 'conduite : index équivalent présent sous un autre nom -> pas de doublon');
$check($apres['matiere.idx_matiere_ecole'] === 'nom_matiere', 'matiere : nom déjà pris par un autre index -> laissé tel quel');
$check(!$apres->has('controle_eleve.idx_controle_eleve_eleve') && !$apres->has('ligne_evaluation.idx_ligne_evaluation_eleve_periode'), 'index écartés non créés');
$m->up();
$check($index()->count() === $apres->count(), 'relance de up() : rien de plus');
$m->down();
$fin = $index();
$check($fin->keys()->sort()->values()->all() === $avant->keys()->sort()->values()->all(), 'down() : retour exact à l\'état d\'avant');
$check($fin->has('conduite.existant_conduite') && $fin['matiere.idx_matiere_ecole'] === 'nom_matiere', 'down() : index préexistants conservés');
DB::statement('ALTER TABLE conduite DROP INDEX existant_conduite'); DB::statement('ALTER TABLE matiere DROP INDEX idx_matiere_ecole');
$m->up(); $dix = ['utilisateurs.idx_utilisateurs_email', 'utilisateurs.idx_utilisateurs_telephone', 'utilisateurs.idx_utilisateurs_ecole', 'eleve.idx_eleve_classe_annee', 'eleve.idx_eleve_ecole_annee', 'matiere.idx_matiere_ecole', 'matiere_ordre.idx_matiere_ordre_matiere', 'controle_eleve.idx_controle_eleve_periode', 'conduite.idx_conduite_periode', 'ligne_reinscription.idx_ligne_reinscription_eleve_annee']; $i = $index(); $check(collect($dix)->every(fn ($k) => $i->has($k)) && $i['matiere.idx_matiere_ecole'] === 'id_ecole', 'base sans piège : les 10 index créés');
echo "\n$ok OK, $ko échec(s)\n";
