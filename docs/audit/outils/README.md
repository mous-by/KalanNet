# Outils de mesure des index

Ils travaillent sur une base **locale dédiée**, `db_kalannet_index_audit`, créée
à partir de la structure de la base du `.env` puis remplie de données fictives
(≈ 300 écoles). La base de travail n'est jamais modifiée, et chaque script refuse
de tourner en production ou sur une base non locale.

```bash
php docs/audit/outils/1-construire-base-test.php      # (re)crée la base de test, ~10 min
php docs/audit/outils/2-mesurer-index.php             # EXPLAIN et temps, sans puis avec chaque index
php docs/audit/outils/3-mesurer-ecriture.php          # coût des insertions avec / sans index
php docs/audit/outils/4-tester-migration.php          # migration proposée : up, relance, down, up
php docs/audit/outils/5-tester-verrouillage.php index /tmp/go & php docs/audit/outils/5-tester-verrouillage.php dml /tmp/go
php docs/audit/outils/6-tester-migration-pieges.php    # doublons évités, retour arrière limité aux index créés
```

Supprimer la base de test : `DROP DATABASE db_kalannet_index_audit;`
