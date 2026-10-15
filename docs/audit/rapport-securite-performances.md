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

### 1.4 Suppression de l'évaluation d'une autre école — élevée, corrigée

- **Constat (confirmé par test).** `DELETE /evaluations/{id}` (web et API)
  supprimait l'évaluation d'une autre école et toutes ses notes : la vérification
  de l'école n'était faite que si la classe de l'évaluation était chargée, or
  le filtre par école la rend nulle pour une autre école.
- **Correction** (commit `d1b8056`) : 404. Test : `IsolationEcolesTest` (il échoue
  sans le correctif).

### 1.5 Isolation des écoles et des rôles — vérifiée

- **Lecture** : 19 routes web et 13 routes API (élèves, classes, enseignants,
  parents, évaluations, bulletins) — 403 / 404, aucune fuite.
- **Écriture** : 42 routes web et 33 routes API en POST / PUT / PATCH / DELETE
  (modification, suppression, transfert, validation, paiements, utilisateurs,
  bulletins) — la seule faille trouvée est 1.4 ; toutes les autres refusent sans
  rien modifier.
- **Parent** : aucun accès aux données d'un élève qui n'est pas son enfant
  (7 routes) ; aucun accès aux listes d'élèves, paiements, enseignants, classes.
- **Enseignant** : aucun accès en lecture, modification ou suppression aux
  évaluations d'un collègue (web et API) ; pas d'accès aux listes d'élèves,
  paiements ou utilisateurs.
- Non couvert : DAE / DCAP en écriture (aucun compte en production ; leurs routes
  sont restreintes par `RestreindreSuperviseurs`).

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
- **Correction.** `App\Support\SchemaExiste` : toutes les réponses positives
  dans **une seule entrée de cache** (`schema_existe`), lue une fois par requête
  et réécrite seulement quand une nouvelle table ou colonne est vue. Entrée
  oubliée après chaque `php artisan migrate` (événement `MigrationsEnded`) et par
  `optimize:clear` / `cache:clear`. Une absence n'est jamais mise en cache.
- **Cache en base de données (cas de la production)** : chaque lecture du cache
  est une requête SQL. Première version : 12 lectures par page ; version
  regroupée : 1 lecture (`/eleves` 29 -> 18 requêtes, `/dashboard` 49 -> 38),
  HTML identique.
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

- Actifs, abandons, diplômés, filles et garçons de l'école en une requête au
  lieu de cinq (commit `a6bf3d9`).

| Tableau de bord Admin | Avant | Après |
|---|---|---|
| Web | 71 requêtes | 37 requêtes |
| API | 55 requêtes | 27 requêtes |

Sorties identiques (Admin, enseignant, SupAdmin ; web et API ; finances).
Test : `TableauDeBordTest`.

## 3. Restant à traiter

- Index de la base : proposés, non appliqués — voir [index-base-de-donnees.md](index-base-de-donnees.md).
- Génération des PDF de classe en tâche de fond.
- Tables sans clé primaire : `user_permission` (utilisée), `roles`, `role_permission`,
  `user_role` (inutilisées), `evaluationprof`, `indiscipline` (vides).
- `user_permission` sans clé primaire ni contrainte d'unicité (0 doublon aujourd'hui).
