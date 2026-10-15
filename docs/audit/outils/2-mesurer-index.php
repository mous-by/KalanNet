<?php
// Mesure, sur la base de test LOCALE db_kalannet_index_audit, l'effet de chaque index proposé.
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Garde-fou : uniquement sur un serveur MySQL local, jamais en production.
if (app()->environment('production') || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    exit("Refusé : environnement de production ou base non locale.\n");
}
$db = DB::connection(); $db->statement('USE db_kalannet_index_audit');
$ids = fn ($n, $pas, $depart = 1) => implode(',', range($depart, $depart + ($n - 1) * $pas, $pas));
$classe = 1234; $eleves = $db->table('eleve')->where('id_classe', $classe)->where('id_annee', 2)->pluck('id_eleve')->implode(',');
$requetes = [
  'utilisateurs' => [['connexion (email ou téléphone)', "select * from utilisateurs where (email = 'u9876@ecole.ml' or telephone = 70009876)"],
                     ['utilisateurs d’une école', "select idUtilisateur from utilisateurs where idEcole = 123"]],
  'eleve' => [['élèves d’une classe (liste, bulletins)', "select * from eleve where id_classe = $classe and id_annee = 2 and etat_dossier = 0 order by nom_eleve, prenom_eleve"],
              ['effectif de l’école (tableau de bord)', "select count(*) from eleve where id_ecole = 123 and id_annee = 2 and etat_dossier = 0"]],
  'matiere' => [['matières de l’école', "select * from matiere where (id_ecole = 123 or id_ecole is null) order by nom_matiere"]],
  'matiere_ordre' => [['ordres des matières', "select * from matiere_ordre where id_matiere in (".$ids(40, 37).")"]],
  'controle_eleve' => [['conduite : pénalités de la classe', "select controle_eleve.id_eleve, SUM(ABS(COALESCE(controle.penalite_conduite, 0))) from controle_eleve left join controle on controle.id_controle = controle_eleve.id_controle where controle_eleve.id_classe = $classe and controle_eleve.id_annee_scolaire = 2 and controle_eleve.id_trimestre = 1 and controle_eleve.id_ecole = 124 and controle_eleve.id_eleve in ($eleves) group by controle_eleve.id_eleve"]],
  'conduite' => [['conduite : notes enregistrées', "select note_conduite, id_eleve from conduite where id_classe = $classe and id_annee_scolaire = 2 and id_trimestre = 1 and id_eleve in ($eleves)"]],
  'ligne_reinscription' => [['réinscription : déjà réinscrits', "select id_eleve from ligne_reinscription where id_eleve in ($eleves) and id_annee = 3"]],
  'ligne_evaluation' => [['bulletin : notes de la classe', "select le.id_eleve, le.id_matiere, le.note, n.typeNote from ligne_evaluation le join note n on le.id_note = n.id_note where le.id_eleve in ($eleves) and le.id_annee_scolaire = 2 and le.id_trimestre = 1 order by le.id_ligneEvaluation"],
                         ['moyenne annuelle : périodes', "select distinct id_trimestre from ligne_evaluation where id_eleve in ($eleves) and id_annee_scolaire = 2"]],
];
$index = [
  'utilisateurs' => ['idx_utilisateurs_email' => '(email)', 'idx_utilisateurs_telephone' => '(telephone)', 'idx_utilisateurs_ecole' => '(idEcole)'],
  'eleve' => ['idx_eleve_classe_annee' => '(id_classe, id_annee, etat_dossier)', 'idx_eleve_ecole_annee' => '(id_ecole, id_annee, etat_dossier)'],
  'matiere' => ['idx_matiere_ecole' => '(id_ecole)'],
  'matiere_ordre' => ['idx_matiere_ordre_matiere' => '(id_matiere, ordre_enseignement)'],
  'controle_eleve' => ['idx_controle_eleve_periode' => '(id_ecole, id_classe, id_annee_scolaire, id_trimestre)', 'idx_controle_eleve_eleve' => '(id_eleve, id_controle)'],
  'conduite' => ['idx_conduite_periode' => '(id_classe, id_annee_scolaire, id_trimestre, id_eleve)'],
  'ligne_reinscription' => ['idx_ligne_reinscription_eleve_annee' => '(id_eleve, id_annee)'],
  'ligne_evaluation' => ['idx_ligne_evaluation_eleve_periode' => '(id_eleve, id_annee_scolaire, id_trimestre)'],
];
$mesure = function ($sql) use ($db) {
    $t = [];
    for ($i = 0; $i < 20; $i++) { $d = hrtime(true); $db->select($sql); $t[] = (hrtime(true) - $d) / 1e6; }
    sort($t);
    $p = $db->select('EXPLAIN '.$sql)[0];
    return [$t[10], $p->type, $p->key ?? '—', $p->rows];
};
$taille = fn ($t) => $db->selectOne("select index_length l from information_schema.tables where table_schema = 'db_kalannet_index_audit' and table_name = ?", [$t])->l;
$ecriture = function () use ($db) {
    $t = [];
    foreach ([1, 2, 3] as $_) {
        $db->beginTransaction(); $d = hrtime(true);
        for ($i = 0; $i < 2000; $i++) $db->insert('insert into ligne_evaluation (id_evaluation, id_enseignant, id_classe, id_matiere, id_annee_scolaire, id_trimestre, id_note, id_eleve, note) values (1, 0, 1, 1, 2, 1, 1, ?, 10)', [1 + $i * 37]);
        $t[] = (hrtime(true) - $d) / 1e6; $db->rollBack();
    }
    sort($t); return $t[1];
};
$ecritureAvant = $ecriture();
foreach ($requetes as $table => $liste) {
    $lignes = $db->table($table)->count();
    echo "\n### $table (".number_format($lignes, 0, ',', ' ')." lignes)\n";
    $avant = array_map(fn ($r) => $mesure($r[1]), $liste);
    $tailleAvant = $taille($table);
    foreach ($index[$table] as $nom => $cols) {
        $d = hrtime(true);
        $db->statement("ALTER TABLE `$table` ADD INDEX `$nom` $cols, ALGORITHM=INPLACE, LOCK=NONE");
        printf("  + %-38s %-55s créé en %6.0f ms (ALGORITHM=INPLACE, LOCK=NONE)\n", $nom, $cols, (hrtime(true) - $d) / 1e6);
    }
    $db->statement("ANALYZE TABLE `$table`");
    printf("  taille des index : %.1f Mo -> %.1f Mo\n", $tailleAvant / 1048576, $taille($table) / 1048576);
    foreach ($liste as $i => [$nom, $sql]) {
        [$ta, $typeA, $cleA, $rowsA] = $avant[$i];
        [$tp, $typeP, $cleP, $rowsP] = $mesure($sql);
        printf("  %-40s avant : %-5s %-24s %8s lignes %8.2f ms | après : %-5s %-36s %6s lignes %7.2f ms\n", $nom, $typeA, $cleA, $rowsA, $ta, $typeP, $cleP, $rowsP, $tp);
    }
}
$ecritureApres = $ecriture();
printf("
Écriture : 2 000 notes (ligne_evaluation) en %.0f ms sans les index proposés, %.0f ms avec (médiane de 3)
", $ecritureAvant, $ecritureApres);
