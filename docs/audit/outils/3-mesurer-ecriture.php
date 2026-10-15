<?php
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Garde-fou : uniquement sur un serveur MySQL local, jamais en production.
if (app()->environment('production') || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    exit("Refusé : environnement de production ou base non locale.\n");
}
$db = DB::connection(); $db->statement('USE db_kalannet_index_audit');
$inserts = [
  'ligne_evaluation' => ["insert into ligne_evaluation (id_evaluation, id_enseignant, id_classe, id_matiere, id_annee_scolaire, id_trimestre, id_note, id_eleve, note) values (1, 0, 1, 1, 2, 1, 1, ?, 10)", ['idx_ligne_evaluation_eleve_periode' => '(id_eleve, id_annee_scolaire, id_trimestre)']],
  'eleve' => ["insert into eleve (nom_eleve, prenom_eleve, genre_eleve, id_annee, id_classe, id_ecole, etat_dossier, matricule, date_naissance, lieu_naiss, adresse_eleve, date_inscription, image, cas_social, mode_paiement, statut_paiement) values ('N', 'P', 'Masculin', 2, ?, 12, 0, 'X', '2012-01-01', 'X', 'X', '2025-10-01', '', 0, '', '')", ['idx_eleve_classe_annee' => '(id_classe, id_annee, etat_dossier)', 'idx_eleve_ecole_annee' => '(id_ecole, id_annee, etat_dossier)']],
  'controle_eleve' => ["insert into controle_eleve (id_eleve, id_classe, id_matiere, id_annee_scolaire, id_trimestre, id_ecole, date, libelle, heure_debut, heure_fin, notifier_parent, id_controle) values (?, 5, 0, 2, 1, 1, '2025-11-01', 'x', '08:00', '09:00', 0, 7)", ['idx_controle_eleve_periode' => '(id_ecole, id_classe, id_annee_scolaire, id_trimestre)', 'idx_controle_eleve_eleve' => '(id_eleve, id_controle)']],
  'conduite' => ["insert into conduite (id_annee_scolaire, id_classe, id_trimestre, id_eleve, note_conduite) values (2, 5, 1, ?, 16)", ['idx_conduite_periode' => '(id_classe, id_annee_scolaire, id_trimestre, id_eleve)']],
];
$mesure = function ($sql) use ($db) {
    $t = [];
    foreach (range(1, 5) as $_) {
        $db->beginTransaction(); $d = hrtime(true);
        for ($i = 0; $i < 2000; $i++) $db->insert($sql, [1 + ($i * 37) % 99000]);
        $t[] = (hrtime(true) - $d) / 1e6; $db->rollBack();
    }
    sort($t); return $t[2];
};
foreach ($inserts as $table => [$sql, $index]) {
    foreach (array_keys($index) as $nom) $db->statement("ALTER TABLE `$table` DROP INDEX IF EXISTS `$nom`");
    $sans = $mesure($sql);
    foreach ($index as $nom => $cols) $db->statement("ALTER TABLE `$table` ADD INDEX `$nom` $cols");
    $avec = $mesure($sql);
    printf("%-18s 2 000 insertions : %6.0f ms sans, %6.0f ms avec %d index (%+.0f %%, %.3f -> %.3f ms par ligne)\n", $table, $sans, $avec, count($index), ($avec / $sans - 1) * 100, $sans / 2000, $avec / 2000);
}
