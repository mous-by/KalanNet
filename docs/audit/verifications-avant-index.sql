-- Vérifications AVANT d'ajouter les index (docs/audit/index-base-de-donnees.md).
-- LECTURE SEULE : uniquement des SELECT. À lancer sur une copie de la production
-- de préférence, sinon sur la production en heures creuses.

-- 1. Volumes et taille des tables concernées.
SELECT table_name, table_rows, ROUND(data_length / 1048576, 1) AS donnees_mo, ROUND(index_length / 1048576, 1) AS index_mo
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('utilisateurs', 'eleve', 'matiere', 'matiere_ordre', 'controle_eleve', 'conduite', 'ligne_reinscription', 'ligne_evaluation', 'user_permission')
ORDER BY table_rows DESC;

-- 2. Index déjà présents (pour ne pas créer de doublon).
SELECT table_name, index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS colonnes, MAX(non_unique) AS non_unique
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN ('utilisateurs', 'eleve', 'matiere', 'matiere_ordre', 'controle_eleve', 'conduite', 'ligne_reinscription', 'ligne_evaluation', 'user_permission')
GROUP BY table_name, index_name;

-- 3. Doublons (n'empêchent pas les index proposés, qui ne sont pas uniques ;
--    à corriger avant toute contrainte UNIQUE future).
SELECT 'conduite' AS table_, COUNT(*) AS groupes_en_double FROM (SELECT 1 FROM conduite GROUP BY id_annee_scolaire, id_classe, id_trimestre, id_eleve HAVING COUNT(*) > 1) x
UNION ALL SELECT 'user_permission', COUNT(*) FROM (SELECT 1 FROM user_permission GROUP BY user_id, permission_id HAVING COUNT(*) > 1) x
UNION ALL SELECT 'matiere_ordre', COUNT(*) FROM (SELECT 1 FROM matiere_ordre GROUP BY id_matiere, ordre_enseignement HAVING COUNT(*) > 1) x
UNION ALL SELECT 'ligne_reinscription', COUNT(*) FROM (SELECT 1 FROM ligne_reinscription GROUP BY id_eleve, id_annee HAVING COUNT(*) > 1) x
UNION ALL SELECT 'utilisateurs (email + école)', COUNT(*) FROM (SELECT 1 FROM utilisateurs WHERE email <> '' GROUP BY email, idEcole HAVING COUNT(*) > 1) x;

-- 4. Données orphelines (références vers une ligne absente).
SELECT 'eleve sans classe' AS controle, COUNT(*) AS lignes FROM eleve e LEFT JOIN classe c ON c.id_classe = e.id_classe WHERE e.id_classe IS NOT NULL AND c.id_classe IS NULL
UNION ALL SELECT 'ligne_evaluation sans eleve', COUNT(*) FROM ligne_evaluation l LEFT JOIN eleve e ON e.id_eleve = l.id_eleve WHERE e.id_eleve IS NULL
UNION ALL SELECT 'ligne_evaluation sans classe', COUNT(*) FROM ligne_evaluation l LEFT JOIN classe c ON c.id_classe = l.id_classe WHERE c.id_classe IS NULL
UNION ALL SELECT 'controle_eleve sans controle', COUNT(*) FROM controle_eleve ce LEFT JOIN controle c ON c.id_controle = ce.id_controle WHERE c.id_controle IS NULL
UNION ALL SELECT 'conduite sans eleve', COUNT(*) FROM conduite co LEFT JOIN eleve e ON e.id_eleve = co.id_eleve WHERE e.id_eleve IS NULL
UNION ALL SELECT 'matiere_ordre sans matiere', COUNT(*) FROM matiere_ordre mo LEFT JOIN matiere m ON m.id_matiere = mo.id_matiere WHERE m.id_matiere IS NULL
UNION ALL SELECT 'user_permission sans utilisateur', COUNT(*) FROM user_permission up LEFT JOIN utilisateurs u ON u.idUtilisateur = up.user_id WHERE u.idUtilisateur IS NULL
UNION ALL SELECT 'ligne_reinscription sans eleve', COUNT(*) FROM ligne_reinscription lr LEFT JOIN eleve e ON e.id_eleve = lr.id_eleve WHERE e.id_eleve IS NULL;

-- 5. Version et moteur (création en ligne : MariaDB >= 10.0, InnoDB).
SELECT VERSION() AS version;
SELECT table_name, engine FROM information_schema.tables WHERE table_schema = DATABASE()
  AND table_name IN ('utilisateurs', 'eleve', 'matiere', 'matiere_ordre', 'controle_eleve', 'conduite', 'ligne_reinscription', 'ligne_evaluation');
