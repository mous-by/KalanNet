# Audit technique KalanNet — octobre 2026

Ce dossier garde la trace de l'audit (sécurité, performances, base de données) :
ce qui a été constaté, ce qui a été corrigé, comment c'est vérifié, et ce qui
reste à décider.

| Fichier | Contenu |
|---|---|
| [rapport-securite-performances.md](rapport-securite-performances.md) | Failles corrigées et optimisations réalisées, avec mesures avant / après |
| [index-base-de-donnees.md](index-base-de-donnees.md) | Index proposés (non appliqués), preuves `EXPLAIN`, coûts, procédure et retour arrière |
| [verifications-avant-index.sql](verifications-avant-index.sql) | Contrôles en lecture seule à lancer avant d'ajouter les index |
| [deploiement.md](deploiement.md) | Procédure LWS : sécurité, puis index, puis mobile (vérifications et retours arrière) |
| [migration-proposee/](migration-proposee/) | Migration des index, **inactive** tant qu'elle n'est pas copiée dans `database/migrations/` |

## Tests de non-régression

Les vérifications de l'audit sont des tests PHPUnit dans `tests/Integration/`.
Ils tournent sur la base MySQL **locale** (le schéma historique n'existe pas
entièrement dans les migrations, donc pas de SQLite) et chaque test est annulé
par une transaction : la base n'est pas modifiée.

```bash
KALANNET_TESTS_INTEGRATION=1 vendor/bin/phpunit -c phpunit.integration.xml
```

Garde-fous : sans `KALANNET_TESTS_INTEGRATION=1` les tests sont ignorés, et ils
refusent de tourner si `APP_ENV=production` ou si la base n'est pas locale.
Ne jamais les lancer sur la production.

| Test | Vérifie |
|---|---|
| `SelectionEcoleTest` | Le choix de l'école (web et API) exige un mot de passe vérifié ; jeton de sélection falsifié, d'un autre compte ou expiré refusé |
| `TerritoireEcoleIdTest` | Un DAE / DCAP ne cible par `ecole_id` qu'une école de son académie / CAP |
| `IsolationEcolesTest` | Les ressources d'une autre école renvoient 403 / 404 (web et API), jamais 500 ni données |
| `ConduiteBulletinTest` | Règles de la note de conduite du bulletin (6 cas) et aucune écriture pendant le calcul |
| `SchemaExisteTest` | Cache des vérifications de schéma et invalidation après une migration |
| `TableauDeBordTest` | Effectifs par classe du tableau de bord = comptages directs |

La suite habituelle (`vendor/bin/phpunit`, SQLite) est inchangée. Elle contient
un échec antérieur à l'audit : `tests/Unit/MaliPhoneTest.php` vise la classe
`App\Rules\MaliPhone`, renommée `PaysPhone` lors de l'internationalisation.
