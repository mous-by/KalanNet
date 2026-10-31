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

---

## 3. Audit complémentaire du 31/10/2026

Domaines vérifiés en plus de l'isolation des écoles (section 1) : envois de fichiers, injection SQL, XSS, redirections,
téléchargements, routes publiques, webhook de paiement, en-têtes, fichiers exposés en production. Test :
`tests/Integration/SecuriteRenforceeTest.php`.

### 3.1 Webhook de paiement des abonnements — critique, corrigée

- **Constat (confirmé par le code et par test).** `POST /abonnements/webhook/{fournisseur}` est public. Pour Orange
  Money, Mobile Money et Mobicash, aucune vérification : envoyer `{"reference": "ABN-…", "status": "paid"}` marquait
  le paiement payé et **activait l'abonnement sans paiement**. La référence est affichée à l'école qui lance le
  paiement, et le lancement est permis même quand l'opérateur n'est pas branché. Wave n'était vérifié que si
  `ABONNEMENT_WAVE_WEBHOOK_SECRET` était renseigné.
- **Correction.** `AbonnementPaymentService::verifyWebhook` : tout webhook sans signature valide est refusé (401).
  Wave : en-tête `Wave-Signature`, secret obligatoire. Autres opérateurs : en-tête `X-Webhook-Signature` =
  HMAC-SHA256 du corps brut avec `ABONNEMENT_{OPERATEUR}_WEBHOOK_SECRET` (vide = refusé).
- **En production** : si Wave est utilisé, renseigner `ABONNEMENT_WAVE_WEBHOOK_SECRET` (tableau de bord Wave), sinon
  les paiements Wave ne sont plus validés automatiquement (la validation manuelle reste possible).

### 3.2 Fichiers envoyés servis comme pages web — élevée, corrigée

- **Constat (confirmé par test).** La règle `image` contrôle le contenu, pas le nom : un vrai GIF nommé `photo.html`
  (ou `.svg`, `.xhtml`) était accepté et rangé dans `public/` avec cette extension, donc servi comme page du site
  (script exécuté chez l'administrateur qui ouvre le lien). Concernés : photo de profil (web, API), photo d'élève à
  l'inscription (web, API), photo d'enseignant. Les extensions PHP étaient déjà bloquées par Laravel.
- **Correction.** `App\Support\FichierTeleverse::nom` : extension déduite du contenu (type détecté par le serveur),
  liste fermée (images ; documents pour les annonces), appliqué aux 11 envois rangés dans `public/`. Règles `mimes`
  ajoutées aux champs qui n'avaient que `image`. Défense en profondeur : `.htaccess` dans `image_eleves`,
  `images_*` et `uploads` qui refuse de servir scripts, pages HTML, SVG et JS (vérifié avec Apache : `.html` 403,
  `.png` 200).

### 3.3 XSS : nom d'un lecteur d'annonce — moyenne, corrigée

- **Constat.** La liste « qui a lu l'annonce » insérait le nom de chaque lecteur en HTML. Un utilisateur (un parent,
  par exemple) peut modifier son nom dans son profil : un nom contenant du HTML s'exécutait chez l'administrateur.
- **Correction.** Noms insérés comme texte (`textContent`).
- **Code mort** (non chargé par aucune page, à supprimer un jour) : `public/assets/mon_js/dashboard-dcap-dae.js`,
  `filtre_enregistrement_emargement.js`, `filtre_emargement.js`, `filtre__presence.js`,
  `recuperation_modal_emargement.js` ; ils insèrent aussi des données sans échappement.

### 3.4 En-têtes de sécurité — ajoutés

`App\Http\Middleware\EnTetesSecurite` (toutes les réponses) : `X-Frame-Options: SAMEORIGIN`,
`X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (caméra
et micro pour KalanNet seulement, pas de géolocalisation), `Strict-Transport-Security` en HTTPS. Pas de
Content-Security-Policy : les pages contiennent beaucoup de scripts en ligne (chantier séparé).

### 3.5 Service worker — corrigé

L'ancien service worker gardait en cache **toutes les pages** consultées (données d'élèves, paiements), relisibles
hors connexion sur un ordinateur partagé. Il ne garde plus que les fichiers techniques ; les pages ont une page
« Hors connexion » (`public/offline.html`). Le cache v1 est supprimé à la première visite.

### 3.6 Vérifié sans faille

- **SQL** : aucune requête construite avec une donnée de l'utilisateur (les `*Raw` n'utilisent que des valeurs du
  code ou des paramètres liés).
- **Affichage** : les 16 `{!! !!}` des vues passent tous par `e()` ou des textes de traduction ; l'assistant affiche
  ses réponses en texte.
- **Redirections, téléchargements** : aucune redirection vers une adresse fournie par l'utilisateur, aucun chemin
  de fichier venant de la requête.
- **Routes publiques** : 11 (connexion, choix de l'école, langue, thème, politique de confidentialité, webhook) ;
  connexion limitée à 5 tentatives (web et API).
- **Kalanbot** : permission vérifiée avant chaque action, puis passage par les mêmes contrôleurs que le site.
- **Production (kalannet.com, lecture seule)** : `.env`, `.git`, journaux, `composer.lock`, listes de dossiers
  refusés ; pages d'erreur sans détail technique ; cookies `secure`, `httponly`, `samesite=lax`.

