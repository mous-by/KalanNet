<?php
// Construit une base de test LOCALE séparée (jamais la production) au volume d'environ 300 écoles.
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Garde-fou : uniquement sur un serveur MySQL local, jamais en production.
if (app()->environment('production') || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    exit("Refusé : environnement de production ou base non locale.\n");
}
$src = config('database.connections.mysql.database');
$dst = 'db_kalannet_index_audit';
$db = DB::connection();
$db->statement("DROP DATABASE IF EXISTS `$dst`");
$db->statement("CREATE DATABASE `$dst` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$tables = ['eleve', 'utilisateurs', 'matiere', 'matiere_ordre', 'controle_eleve', 'controle', 'conduite', 'ligne_reinscription', 'ligne_evaluation', 'note', 'user_permission', 'permissions'];
foreach ($tables as $t) $db->statement("CREATE TABLE `$dst`.`$t` LIKE `$src`.`$t`");
$db->statement("CREATE TABLE `$dst`.n (i INT PRIMARY KEY)");
$db->statement("INSERT INTO `$dst`.n SELECT a.x + b.x*10 + c.x*100 + d.x*1000 + e.x*10000 FROM (SELECT 0 x UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a, (SELECT 0 x UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b, (SELECT 0 x UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c, (SELECT 0 x UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d, (SELECT 0 x UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) e");
$d = "`$dst`";
$t0 = microtime(true);
// 300 écoles, 10 classes chacune, 3 années ; 100 000 élèves.
$db->statement("SET foreign_key_checks = 0");
$db->statement("INSERT INTO $d.eleve (id_eleve, nom_eleve, prenom_eleve, genre_eleve, id_annee, id_classe, id_ecole, etat_dossier, matricule, date_naissance, lieu_naiss, adresse_eleve, date_inscription, image, cas_social, mode_paiement, statut_paiement)
  SELECT i+1, CONCAT('Nom', i % 997), CONCAT('Prenom', i % 89), IF(i%2,'Masculin','Féminin'), 1 + (i DIV 10000) % 3, 1 + (i % 3000), 1 + (i % 3000) DIV 10, IF(i % 50 = 0, 2, 0), CONCAT('M', i), '2012-01-01', 'X', 'X', '2025-10-01', '', 0, '', '' FROM $d.n");
$db->statement("INSERT INTO $d.utilisateurs (idUtilisateur, nomPrenom, email, pwd, fonction, telephone, genre, droit, idEcole, statut)
  SELECT i+1, CONCAT('U', i), CONCAT('u', i, '@ecole.ml'), 'x', 'x', 70000000 + i, 'M', IF(i%5,'enseignant','parent'), 1 + i % 300, 1 FROM $d.n WHERE i < 12000");
$db->statement("INSERT INTO $d.matiere (id_matiere, nom_matiere, id_ecole) SELECT i+1, CONCAT('Matiere ', i), IF(i < 200, NULL, 1 + i % 300) FROM $d.n WHERE i < 9000");
$db->statement("INSERT INTO $d.matiere_ordre (id_matiere, ordre_enseignement) SELECT i+1, ELT(1 + i % 4, 'Fondamentale I','Fondamentale II','Secondaire Generale','Jardin') FROM $d.n WHERE i < 9000");
$db->statement("INSERT INTO $d.matiere_ordre (id_matiere, ordre_enseignement) SELECT i+1, 'Fondamentale II' FROM $d.n WHERE i < 9000 AND i % 3 = 0");
$db->statement("INSERT INTO $d.note SELECT * FROM `$src`.note");
$db->statement("INSERT INTO $d.permissions SELECT * FROM `$src`.permissions");
$db->statement("INSERT INTO $d.user_permission (user_id, permission_id) SELECT u.idUtilisateur, p.id FROM $d.utilisateurs u JOIN $d.permissions p ON p.id % 3 = u.idUtilisateur % 3");
// Notes : 15 lignes par élève de l'année 2 et 3 (≈ 1 million).
foreach ([0, 1, 2, 3, 4] as $k) {
    $db->statement("INSERT INTO $d.ligne_evaluation (id_evaluation, id_enseignant, id_classe, id_matiere, id_annee_scolaire, id_trimestre, id_note, id_eleve, note)
      SELECT 1 + e.id_eleve % 5000, 0, e.id_classe, 1 + (e.id_eleve + $k) % 12, e.id_annee, 1 + ($k + t.i) % 3, 1 + t.i % 2, e.id_eleve, (e.id_eleve * 7 + $k) % 21
      FROM $d.eleve e JOIN $d.n t ON t.i < 3 WHERE e.id_annee >= 2");
}
$db->statement("INSERT INTO $d.controle (id_controle, date_controle, alertControle, type_controle, id_emploi_du_temps, id_eleve, penalite_conduite, id_ecole) SELECT i+1, '2025-11-01', 'x', 'absence', 0, i+1, ELT(1 + i%3, 0, 0.5, 1), 1 + i % 300 FROM $d.n WHERE i < 30000");
foreach ([0, 1, 2] as $k) {
    $db->statement("INSERT INTO $d.controle_eleve (id_eleve, id_classe, id_matiere, id_annee_scolaire, id_trimestre, id_ecole, date, libelle, heure_debut, heure_fin, notifier_parent, id_controle)
      SELECT e.id_eleve, e.id_classe, 0, e.id_annee, 1 + $k, e.id_ecole, '2025-11-01', 'x', '08:00', '09:00', 0, 1 + e.id_eleve % 30000 FROM $d.eleve e WHERE e.id_eleve % 2 = $k % 2");
}
$db->statement("INSERT INTO $d.conduite (id_annee_scolaire, id_classe, id_trimestre, id_eleve, note_conduite) SELECT e.id_annee, e.id_classe, t.i + 1, e.id_eleve, 16 FROM $d.eleve e JOIN $d.n t ON t.i < 3");
$db->statement("INSERT INTO $d.ligne_reinscription (id_eleve, id_annee, id_classe, id_reinscription) SELECT id_eleve, id_annee + 1, id_classe, 1 FROM $d.eleve WHERE id_annee < 3");
$db->statement("SET foreign_key_checks = 1");
foreach ($tables as $t) { $db->statement("ANALYZE TABLE $d.`$t`"); printf("%-20s %9d lignes\n", $t, $db->table("$dst.$t")->count()); }
printf("Construit en %.0f s\n", microtime(true) - $t0);
