# Sécurité et performances — corrections réalisées

Toutes les mesures ont été faites sur la base de développement locale (peu de
données : 295 élèves, 333 lignes de notes), dans des transactions annulées. Les
**nombres de requêtes** sont transposables à la production ; les **temps**
dépendent du serveur et des volumes et ne le sont pas.

## 1. Sécurité

### 1.1 Connexion sans mot de passe par le choix de l'école — critique, corrigée

- **Constat (confirmé par test).** Un compte rattaché à plusieurs écoles choisit
  son école après la connexion. Les routes de ce choix (`POST /login/select-school`
  et `POST /api/v1/auth/select-school`) connectaient le compte demandé sans
  vérifier qu'un mot de passe venait d'être saisi : connaître deux identifiants
  numériques suffisait. L'API délivrait en plus un jeton sans date d'expiration.
- **Correction** (commit `2ffc1f0`).
  - Web : `login()` garde en session, 10 minutes et pour un seul usage, la liste
    des comptes dont le mot de passe vient d'être vérifié ; `selectSchool()`
    refuse tout autre compte.
  - API : `login` renvoie pour chaque compte un `selection_token` chiffré
    (compte, école, expiration à 10 minutes), exigé par `select-school`.
  - Jetons Sanctum : 90 jours (`SANCTUM_EXPIRATION`, en minutes).
  - Application mobile : envoie le `selection_token` (à partir de la version 17).
- **Effet.** Un compte à une seule école ne voit aucun changement. Un compte à
  plusieurs écoles doit utiliser l'application ≥ v17.
- **Recommandé en production** : supprimer les jetons API émis avant le
  correctif (`personal_access_tokens`), qui ont pu être obtenus par la faille.
- **Test** : `tests/Integration/SelectionEcoleTest.php`.

### 1.2 DAE / DCAP : tableau de bord d'une école hors territoire — élevée, corrigée

- **Constat (confirmé par test).** Sur l'API, un DAE ou un DCAP pouvait passer
  `ecole_id` de n'importe quelle école : `/api/v1/dashboard?ecole_id=…` renvoyait
  les effectifs, les recettes, le solde de caisse et les derniers élèves inscrits
  (date de naissance, adresse) d'une école hors de son académie ou de son CAP.
  Le site web n'était pas concerné (l'école vient de la session).
- **Correction** (commit `8b7c9a9`) : `Authorizer::resolveEcoleId` n'accepte, pour
  un DAE, qu'une école de son académie et, pour un DCAP, qu'une école de son CAP ;
  sinon 403. SupAdmin inchangé.
- **Test** : `tests/Integration/TerritoireEcoleIdTest.php`.

### 1.3 Erreurs 500 sur les évaluations d'une autre école — faible, corrigée

- **Constat.** `/evaluations/{id}/edit` et `/evaluations/{id}/programme` d'une
  évaluation d'une autre école plantaient (la classe, filtrée par école, était
  nulle). L'accès était refusé, mais par une erreur 500.
- **Correction** (commit `8f0340b`) : 404, comme pour les autres ressources d'une
  autre école (évaluations et bulletins).
- **Test** : `tests/Integration/IsolationEcolesTest.php`.

### 1.4 Isolation des écoles — vérifiée

Un Admin de l'école 1 demandant élèves, classes, enseignants, parents,
évaluations et bulletins de l'école 119 reçoit 403 ou 404 sur les 19 routes web
et 13 routes API testées. Aucune fuite détectée. Non couvert à ce jour : routes
POST / PUT / DELETE, paiements et reçus, rôles enseignant et parent.

## 2. Performances

### 2.1 Bulletins (commit `674042e`)

- **Constat.** À chaque génération, la note de conduite de chaque élève était
  réécrite dans `conduite` puis relue : une écriture et deux lectures par élève.
- **Correction.** Conduite calculée en une requête groupée, sans écriture, avec
  la même règle (18 − pénalités, bornée à 6..18, arrondi de la colonne FLOAT ;
  note enregistrée gardée pour un élève sans contrôle). Les appels d'épreuve
  tiennent toujours `conduite` à jour. Bulletins d'une classe : élèves et classe
  chargés une fois, dernier trimestre lu une fois par classe.

| Mesure (classe de 55 élèves) | Avant | Après |
|---|---|---|
| Bulletin d'un élève | 189 requêtes, dont 55 écritures | 23 requêtes, 0 écriture |
| Bulletins de la classe (PDF) | 355 requêtes, dont 55 écritures | 27 requêtes, 0 écriture |
| Données des bulletins de la classe | 186 requêtes — 58 ms | 21 requêtes — 18 ms |

- **Résultats identiques** : 108 calculs (toutes classes × années × trimestres ×
  3 modes) comparés en JSON complet ; texte de 6 PDF (344 bulletins, dont
  trimestre 3 avec moyenne et rang annuels, compositions mensuelles, modèle
  classique) ; 6 cas de conduite. Test : `ConduiteBulletinTest`.
- **Reste** : le PDF d'une classe entière prend ~13 s, dû au rendu dompdf (le SQL
  ne pèse plus que ~9 ms). Piste : génération en tâche de fond.

### 2.2 Vérifications de schéma (commit `397b929`)

- **Constat.** 16 requêtes `information_schema` (`Schema::hasTable / hasColumn`)
  à chaque page web.
- **Correction.** `App\Support\SchemaExiste` : résultat gardé le temps de la
  requête et, s'il est positif, dans le cache applicatif. Cache invalidé après
  chaque `php artisan migrate` (événement `MigrationsEnded`) et par
  `optimize:clear` / `cache:clear`. Une absence n'est jamais mise en cache.
- **Attention** : une table supprimée à la main (sans migration) demande un
  `php artisan optimize:clear`.

| Page | Avant | Après |
|---|---|---|
| `/eleves`, `/classes` | 33 | 17 |
| `/evaluations` | 47 | 30 |
| `/finances/paiements` | 45 | 29 |
| `/annonces` | 45 | 24 |

HTML de 8 pages identique avant / après. Test : `SchemaExisteTest`.

### 2.3 Tableaux de bord (commit `6c7dfa9`)

- Effectifs de toutes les classes (total, filles, garçons) en une requête
  groupée au lieu de trois par classe.
- École et pays (formatage des montants) lus une fois par requête HTTP
  (`Devise::resolvePays`), sans cache persistant.

| Tableau de bord Admin | Avant | Après |
|---|---|---|
| Web | 71 requêtes | 41 requêtes |
| API | 55 requêtes | 31 requêtes |

Sorties identiques (Admin, enseignant, SupAdmin ; web et API ; finances).
Test : `TableauDeBordTest`.

## 3. Restant à traiter

- Index de la base : proposés, non appliqués — voir [index-base-de-donnees.md](index-base-de-donnees.md).
- Génération des PDF de classe en tâche de fond.
- Tableau de bord Admin : environ 40 requêtes, dont un comptage d'élèves fait 3 fois.
- Isolation : tester aussi les routes d'écriture, les paiements et les rôles enseignant / parent.
- Tables sans clé primaire : `user_permission` (utilisée), `roles`, `role_permission`,
  `user_role` (inutilisées), `evaluationprof`, `indiscipline` (vides).
- `tests/Unit/MaliPhoneTest.php` échoue depuis le renommage de `MaliPhone` en `PaysPhone`.
