# Audit technique KalanNet — rapport final (octobre 2026)

Pour chaque point, le statut est indiqué : **confirmé** (par un test ou une
mesure), **suspecté** (non confirmé), ou **non vérifiable** avec les accès
disponibles. Les mesures viennent de la base locale, d'une base de test au
volume d'environ 300 écoles et d'une copie de la production du 15/10/2026
(comptages uniquement, aucune donnée personnelle reproduite).

## A. Architecture réelle

| Élément | Constat |
|---|---|
| Backend | Laravel 13.35, PHP ≥ 8.3 (8.5 en local). Production : version de PHP du site à confirmer (`php -v` sur LWS ; l'export phpMyAdmin indique 8.2 pour phpMyAdmin lui-même) |
| Base | MariaDB — 10.4.28 en local, **10.11** en production ; 93 tables InnoDB, 53 clés étrangères |
| Organisation | 58 contrôleurs web, 24 contrôleurs API v1 (héritant en grande partie des contrôleurs web), 55 modèles, 127 services, 463 routes (274 web, 189 API) |
| Multi-établissement | Global scope `BelongsToSchool` (17 modèles) filtrant par `session('idEcole')` ; API : `SeedSchoolSession` remplit cette valeur à chaque requête |
| Rôles | SupAdmin, Admin, Gestionnaire (avec ordres d'enseignement gérés), enseignant, parent, DAE, DCAP, revendeur ; permissions fines dans `user_permission` |
| Authentification | Sessions web ; jetons Sanctum pour le mobile (90 jours depuis l'audit) ; limitation à 5 tentatives |
| Cache / sessions / file | Fichiers en local, **base de données en production** ; file `database` mais aucun Job : tout est synchrone ; aucune tâche planifiée |
| PDF | dompdf (bulletins, reçus, cartes), ar-php pour l'arabe |
| Mobile | Expo SDK 57, API `https://kalannet.com/api/v1` |
| Tests | Avant l'audit : 1 test unitaire (cassé). Après : `tests/Unit` (5) et `tests/Integration` (15, base MySQL locale) |

## B. Les 93 tables

Inventaire complet : [inventaire-tables.md](inventaire-tables.md). En résumé :
schéma identique en local et en production ; production encore petite
(10 écoles, 68 élèves, 14 utilisateurs, aucune note) ; 6 tables sans clé
primaire ; 2 tables jamais utilisées (`roles`, `role_permission`) ; 0 doublon et
0 orphelin en production sur les contrôles effectués.

## C. Problèmes par priorité

| Priorité | Problème | Statut | Suite |
|---|---|---|---|
| **Critique** | Connexion à n'importe quel compte sans mot de passe par le choix de l'école (web et API) | Confirmé | Corrigé (`2ffc1f0`), **déployé et vérifié en production** le 15/10 ; 46 anciens jetons supprimés |
| **Élevée** | DAE / DCAP : tableau de bord d'une école hors territoire par `ecole_id` | Confirmé (aucun compte DAE/DCAP en production) | Corrigé (`8b7c9a9`), déployé |
| **Élevée** | Suppression de l'évaluation (et des notes) d'une autre école | Confirmé | Corrigé (`d1b8056`), déployé et vérifié le 16/10 |
| **Élevée** | Jetons API sans expiration | Confirmé | 90 jours (`2ffc1f0`), déployé |
| **Élevée** | Bulletins : écriture en base à chaque consultation, 189 requêtes par bulletin | Confirmé | Corrigé (`674042e`), déployé |
| Moyenne | 16 requêtes `information_schema` par page (12 lectures de cache avec le cache en base) | Confirmé | Corrigé (`397b929`, `e400627`), déployé |
| Moyenne | Index manquants (élèves d'une classe, connexion, conduite, réinscription…) | Confirmé (`EXPLAIN`, base de test) | 10 index (`20ed0fb`), déployés et vérifiés le 16/10 |
| Moyenne | Tableau de bord : N+1 par classe et comptages répétés | Confirmé | Corrigé (`6c7dfa9`, `a6bf3d9`), déployé |
| Moyenne | PDF des bulletins d'une classe : ~13 s pour 55 élèves (rendu dompdf) | Confirmé | Non traité : génération en tâche de fond recommandée |
| Faible | Erreur 500 au lieu de 404 sur les évaluations d'une autre école | Confirmé | Corrigé (`8f0340b`), déployé |
| Faible | Tables sans clé primaire, `user_permission` sans unicité | Confirmé | À décider |
| Faible | Clés étrangères absentes sur les notes et quelques liaisons | Confirmé | À décider quand les données grandiront |
| Faible | Test unitaire cassé (`MaliPhoneTest`) | Confirmé | Remplacé par `PaysPhoneTest` (`e1349a3`) |

## D. Performance SQL confirmée

Détail : [rapport-securite-performances.md](rapport-securite-performances.md) et
[index-base-de-donnees.md](index-base-de-donnees.md).

| Mesure | Avant | Après |
|---|---|---|
| Bulletin d'un élève | 189 requêtes (55 écritures) | 23 requêtes, 0 écriture |
| Bulletins d'une classe de 55 élèves | 355 requêtes | 27 requêtes |
| Page ordinaire (`/eleves`, cache en base) | 29 requêtes | 18 requêtes |
| Tableau de bord Admin web / API | 71 / 55 requêtes | 37 / 27 requêtes |
| Conduite du bulletin (300 000 lignes, base de test) | 177 ms | ~3 ms avec les index |
| Élèves d'une classe (100 000 élèves, base de test) | 43 ms | 0,5 ms avec les index |

## E. Sécurité confirmée

Voir C. Contrôles réalisés sans faille restante : lecture (32 routes) et écriture
(75 routes) entre écoles, parent limité à ses enfants, enseignant limité à ses
évaluations, mots de passe hachés (bcrypt), limitation des tentatives de
connexion, permissions vérifiées côté serveur. Non vérifiable d'ici : HTTPS et
en-têtes du serveur, `APP_DEBUG` en production, journalisation côté LWS.

## F. Optimisations réalisées

Bulletins sans N+1 ni écriture ; cache de schéma regroupé ; tableau de bord
groupé ; école et pays lus une fois par requête ; 10 index prêts. Toutes à
**résultat identique** : 108 calculs de bulletins, 6 PDF (344 bulletins), 16 pages
et 8 réponses API comparés avant / après.

## G. Fichiers modifiés (principaux)

| Domaine | Fichiers |
|---|---|
| Sécurité | `AuthController` (web, API), `Support/Api/Authorizer`, `EvaluationController` (web, API), `BulletinController`, `config/sanctum.php`, mobile `AuthContext`, `login.tsx`, `types/api.ts` |
| Performances | `Services/Bulletins/CalculBulletin`, `MoyennesAnnuelles`, `Support/SchemaExiste` (nouveau), `AppServiceProvider`, `DashboardController`, `Support/Devise`, 3 middlewares et 17 contrôleurs (appels de schéma) |
| Base | `database/migrations/2026_10_16_100000_ajouter_index_audit_performance.php` |
| Tests et documentation | `tests/Integration/*`, `tests/Unit/PaysPhoneTest.php`, `phpunit.integration.xml`, `docs/audit/*` |

## H. Migrations

Une seule : `2026_10_16_100000_ajouter_index_audit_performance` — 10 index non
uniques, création en ligne, aucune donnée modifiée. Testée sur la base de test
et sur la copie de production (création 0,4 à 0,6 s, retour arrière 0,5 à
0,7 s, schéma restauré à l'identique ; doublons évités ; retour arrière limité
aux index créés). Risque : verrou de métadonnées bref (≤ 1 s mesuré) ;
appliquer en heures creuses.

## I. Tests

- `tests/Integration` : 15 tests, tous réussis (connexion, territoire, isolation,
  conduite, cache de schéma, tableau de bord). Lancement :
  `KALANNET_TESTS_INTEGRATION=1 vendor/bin/phpunit -c phpunit.integration.xml`.
- `tests/Unit` : 5 tests, tous réussis.
- Le test d'isolation a été vérifié **en échec sans le correctif** de suppression.
- Non exécutables ici : tests de charge (plusieurs utilisateurs simultanés) sur
  l'infrastructure LWS.

## J. Avant / après

Voir D. Les nombres de requêtes sont transposables à la production ; les temps
dépendent du serveur. À ce jour, la production est trop petite pour que les
index produisent un gain visible : ils préparent la croissance.

## K. Restant à traiter

1. Publier l'application mobile vc17 (procédure : [deploiement.md](deploiement.md)).
2. Génération des PDF de classe en tâche de fond (file `database` déjà configurée).
3. `user_permission` : clé primaire / unicité ; décider du sort de `roles`, `role_permission`.
4. Clés étrangères sur les notes et les liaisons, après contrôle des orphelins.
5. Vérifier sur LWS : `php -v`, `APP_DEBUG=false`, HTTPS forcé.
6. Supprimer `test.html` et `default_index.html` du serveur s'ils sont accessibles.

## L. Montée en charge

- Les 10 index sont en place depuis le 16/10/2026.
- Cache et sessions en base fonctionnent sur un seul serveur ; Redis si plusieurs
  serveurs ou forte charge.
- Bulletins de classe et exports lourds en Jobs (`php artisan queue:work`,
  à lancer par une tâche cron LWS).
- `innodb_buffer_pool_size` adapté à la mémoire du serveur MariaDB.
- Pagination présente sur les principales listes de l'API (élèves, enseignants,
  finances : 11 contrôleurs API sur 24) ; à surveiller sur les exports et les
  PDF de classe.

## M. Maintenance préventive

| Fréquence | Action |
|---|---|
| À chaque déploiement | `git pull`, `php artisan migrate --force`, `php artisan optimize:clear` ; tests d'intégration en local avant |
| Chaque semaine | Lire `storage/logs/laravel.log` ; vérifier les sauvegardes |
| Chaque mois | `verifications-avant-index.sql` (volumes, doublons, orphelins) ; purge des jetons API expirés (`php artisan sanctum:prune-expired`) |
| Chaque trimestre | Restauration d'essai d'une sauvegarde ; revue des comptes SupAdmin / DAE / DCAP ; mise à jour des dépendances (`composer outdated`) |
| Quand la base grandit | Rejouer `docs/audit/outils/2-mesurer-index.php` sur une copie ; réexaminer les requêtes lentes (`slow_query_log`) |
