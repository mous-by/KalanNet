# API REST KalanNet v1

Documentation de l'API REST utilisée par la future application mobile React Native. Cette API expose, sous forme JSON, les mêmes fonctionnalités que la version web (session-based), avec une authentification par token (Laravel Sanctum) au lieu de cookies de session.

- **Base URL** : `https://<domaine>/api/v1`
- **Format** : JSON uniquement (`Content-Type: application/json`, sauf upload de fichiers en `multipart/form-data`)
- **Authentification** : Bearer token (Sanctum), sauf `POST /auth/login` et `POST /auth/select-school`

## Sommaire

1. [Conventions générales](#conventions-générales)
2. [Authentification](#authentification)
3. [Dashboard](#dashboard)
4. [Notifications](#notifications)
5. [Élèves](#élèves)
6. [Enseignants](#enseignants)
7. [Classes](#classes)
8. [Matières](#matières)
9. [Emploi du temps](#emploi-du-temps)
10. [Évaluations (notes)](#évaluations-notes)
11. [Émargements](#émargements)
12. [Présences](#présences)
13. [Bulletins](#bulletins)
14. [Programmes officiels](#programmes-officiels)
15. [Finances](#finances)
16. [Salaires enseignants](#salaires-enseignants)
17. [Configuration / administration](#configuration--administration)
18. [Annonces](#annonces)
19. [Résultats nationaux](#résultats-nationaux)
20. [Appels d'épreuves (contrôle/discipline)](#appels-dépreuves-contrôledisciplineb)
21. [Abonnements](#abonnements)
22. [Assistant (Kalanbot)](#assistant-kalanbot)
23. [Limitations connues](#limitations-connues)

---

## Conventions générales

### Authentification

Toutes les routes (sauf login/select-school) exigent l'en-tête :

```
Authorization: Bearer <token>
Accept: application/json
```

Le token est obtenu via `POST /auth/login` (ou `/auth/select-school` pour un compte multi-écoles) et n'expire pas automatiquement — il reste valide jusqu'à un appel explicite à `POST /auth/logout` (qui ne révoque que le token courant, pas les autres appareils connectés).

### École active (multi-tenant)

KalanNet est multi-école. Pour un `Admin`, `Gestionnaire`, `enseignant` ou `parent`, l'école est déduite automatiquement du compte (`idEcole` de l'utilisateur, ou de son profil enseignant/parent) — **aucun paramètre requis**.

Pour `SupAdmin`, `DAE` et `DCAP` (qui supervisent plusieurs écoles), certains endpoints acceptent un paramètre `ecole_id` (query string en GET, champ de body en POST) pour cibler une école précise. Sans ce paramètre, ces rôles voient les données toutes écoles confondues quand c'est pertinent (ex: liste des classes), ou reçoivent une liste vide/erreur sur les endpoints qui exigent une école précise.

### Pagination

Les listes volumineuses utilisent la pagination standard Laravel (`->paginate()`), renvoyée telle quelle :

```json
{
  "data": [ ... ],
  "current_page": 1,
  "last_page": 5,
  "per_page": 20,
  "total": 97,
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

Paramètres de requête communs : `page` (numéro de page), `search` (recherche texte selon l'endpoint).

Certains endpoints renvoient une liste complète sans pagination (`{"data": [...]}` simple) — c'est précisé dans chaque section.

### Erreurs

| Code | Cas | Corps |
|---|---|---|
| 401 | Token absent/invalide, identifiants de connexion incorrects | `{"message": "..."}` |
| 403 | Permission insuffisante, accès à une ressource d'une autre école, compte désactivé | `{"message": "..."}` |
| 403 | Abonnement de l'école expiré (voir plus bas) | `{"message": "...", "subscription_blocked": true}` |
| 404 | Ressource introuvable | `{"message": "..."}` |
| 422 | Validation échouée | `{"message": "The given data was invalid.", "errors": {"champ": ["message"]}}` |
| 429 | Trop de tentatives de connexion (5 essais / IP+identifiant) | `{"message": "...", "retry_after": <secondes>}` |

Tous les messages de validation sont en français (`lang/fr/validation.php`).

### Blocage d'abonnement

Si l'abonnement de l'école est expiré, **le login réussit quand même** (le token est délivré, avec `subscription_blocked: true` dans la réponse) mais **tous les autres endpoints métier renvoient 403** avec :

```json
{ "message": "Votre abonnement a expiré. Veuillez renouveler l'abonnement pour continuer.", "subscription_blocked": true }
```

Cela permet à l'app mobile d'afficher un écran de renouvellement au lieu de bloquer la connexion elle-même. Seuls `/auth/*` et `/abonnements/*` restent accessibles dans cet état (à vérifier côté client : l'app doit rediriger vers l'écran d'abonnement dès que `subscription_blocked: true` apparaît, plutôt que de laisser l'utilisateur taper dans le vide).

### Téléchargements de fichiers (PDF/Excel)

Certains endpoints ne renvoient **pas du JSON** mais un flux binaire (`application/pdf`, `application/vnd.ms-excel`) directement, en réutilisant les contrôleurs web tels quels :

- `POST /eleves/cartes-scolaires/pdf`, `POST /eleves/liste/pdf`, `POST /eleves/liste/excel`
- `GET /eleves/transferts/{id}/fiche`
- `POST /bulletins/classes/{idClasse}/pdf`, `GET /bulletins/{id}/telecharger`
- `GET /programmes/pdf/download`
- `GET /timetable/download-pdf`
- `GET /salaires/etat/pdf`, `GET /salaires/bulletin`

Le client mobile doit les traiter comme un téléchargement binaire (sauvegarde locale ou affichage dans un lecteur PDF), pas comme du JSON. L'en-tête `Authorization: Bearer` reste requis.

### Permissions

Les permissions sont attachées à des utilisateurs individuels (table pivot `user_permission`), pas à un rôle. `SupAdmin` a implicitement toutes les permissions. Une permission manquante renvoie `403 {"message": "Permission insuffisante."}`.

---

## Authentification

### `POST /auth/login` (public)

```json
{ "identifier": "email@ecole.com ou +223XXXXXXXX", "pwd": "...", "device_name": "iphone-de-fatou" }
```

- `device_name` est optionnel (nom du token Sanctum, utile pour la gestion multi-appareils côté web `/profile` plus tard).
- Limité à 5 tentatives / 3 minutes par IP+identifiant (429 au-delà).

**Réponses possibles :**

- Compte unique trouvé → `200` avec token :
```json
{ "token": "1|xxxxxxxxxxxx", "user": { "id": 12, "nom_prenom": "...", "droit": "Admin", "ecole": {...}, ... }, "subscription_blocked": false }
```
- Plusieurs comptes partagent cet email/téléphone (multi-écoles) → `200` sans token :
```json
{ "requires_school_selection": true, "accounts": [{ "id_utilisateur": 12, "id_ecole": 3, "nom_ecole": "...", "droit": "Admin" }, ...] }
```
  → l'app doit alors afficher un choix d'école et appeler `select-school`.
- Identifiant/mot de passe incorrect → `401`
- Compte désactivé (`statut = 0`) → `403`

### `POST /auth/select-school` (public)

```json
{ "id_utilisateur": 12, "id_ecole": 3, "device_name": "..." }
```

Renvoie la même forme que le login réussi (`token`, `user`, `subscription_blocked`).

### `POST /auth/logout` (auth)

Révoque uniquement le token utilisé pour cet appel (les autres sessions/appareils restent connectés). Renvoie `{"message": "Déconnecté."}`.

### `GET /auth/me` (auth)

Renvoie le profil courant + statut d'abonnement — à appeler au démarrage de l'app pour restaurer une session existante à partir d'un token stocké localement :

```json
{ "user": { "id": 12, "nom_prenom": "...", "email": "...", "telephone": "...", "fonction": "...", "genre": "...", "droit": "Admin", "statut": 1, "theme_preference": "...", "locale_preference": "...", "ecole": { "id": 3, "nom": "...", "type": "...", "logo": "..." } }, "subscription_blocked": false }
```

---

## Dashboard

### `GET /dashboard` (auth)

Contenu variable selon `droit` de l'utilisateur (`SupAdmin`, `enseignant`, `parent`, ou `Admin`/`Gestionnaire`/`DAE`/`DCAP` par défaut) — chiffres clés, listes récentes, alertes. Structure interne à explorer via un appel réel selon le rôle testé (pas de schéma unique documenté ici, contenu riche et propre à chaque rôle).

`ecole_id` en query pour `SupAdmin`/`DAE`/`DCAP` afin de cibler une école précise.

---

## Notifications

### `GET /notifications` (auth)

Par défaut : notifications non lues de l'utilisateur (max 100, triées par id décroissant).

Avec `?since=2026-09-01T10:00:00Z` : **mode synchronisation** — renvoie tout ce qui a été modifié (lu ou non) depuis cette date, pour permettre à un client mobile de réconcilier son cache local après une période hors-ligne.

Réponse : tableau de `NotificationResource` :
```json
[{ "id": 1, "type": "...", "title": "...", "message": "...", "link": "...", "data": {...}, "read_at": "2026-09-07T10:00:00+00:00", "created_at": "...", "updated_at": "..." }]
```

`link` est un chemin relatif (ex: `/eleves/42`), pas une URL absolue — l'app doit le mapper vers son propre routing interne plutôt que l'ouvrir tel quel.

### `GET /notifications/unread-count` (auth)

`{"count": 5}` — utile pour un badge, à appeler fréquemment sans coût (pas de pagination).

### `POST /notifications/{id}/read` (auth)

Marque une notification comme lue. `{"success": true}` même si l'id n'existe pas/n'appartient pas à l'utilisateur (idempotent, pas d'erreur).

---

## Élèves

### `GET /eleves` (auth)

Query : `id_classe`, `id_annee`, `search` (nom/prénom/matricule). Paginé (30/page). Exclut les élèves transférés/archivés (`etat_dossier = 0`).

### `GET /eleves/{id}` (auth)

Fiche complète : élève, classe, école, parents, plan de paiement, paiements récents (8 derniers), résumé de paiement, résumé des échéances, évaluations récentes, moyennes, historique de transferts. Un compte `parent` ne voit que ses propres enfants (403 sinon).

### `PUT /eleves/{id}` (auth)

```json
{
  "prenom_eleve": "...", "nom_eleve": "...", "matricule": "...", "genre_eleve": "Masculin|Féminin",
  "date_naissance": "2015-01-01", "lieu_naiss": "...", "adresse_eleve": "...", "cas_social": "...",
  "mode_paiement": "...", "statut_paiement": "normal|subventionne|boursier|gratuit",
  "id_classe": 5, "id_annee": 2, "date_inscription": "2026-09-01"
}
```

### `DELETE /eleves/{id}` (auth)

Suppression logique (`etat_dossier = 2`), pas de suppression physique.

### `POST /eleves/{id}/transfert` (auth)

```json
{ "motif": "...", "destination": "...", "travail": "...", "conduite": "..." }
```
→ `201 { "success": true, "id_transfert": 7 }`, marque l'élève transféré (`etat_dossier = 1`).

### `POST /eleves/{id}/reintegrer` (auth)

```json
{ "id_classe": 5, "id_annee": 2, "motif_retour": "..." }
```
Réintègre un élève précédemment transféré.

### `GET /eleves/cartes-scolaires` (auth)

Query `id_classe` + `id_annee` requis pour peupler `eleves` ; sinon renvoie juste les listes `classes`/`annees` pour peupler les filtres côté client.

### Exports (flux binaires, voir la section dédiée plus haut)

`POST /eleves/cartes-scolaires/pdf`, `POST /eleves/liste/pdf`, `POST /eleves/liste/excel`, `GET /eleves/transferts/{id}/fiche`.

---

## Enseignants

### `GET /enseignants` (auth)

Query `search` (nom/email/téléphone/matricule). Paginé (20/page). Un compte `enseignant` ne voit que sa propre fiche.

### `POST /enseignants` (auth)

Validation (`validateEnseignant`, voir `EnseignantController::validateEnseignant`) — champs clés : `nom_prenom`, `genre` (`Masculin`/`Féminin`), `email` (unique par école), `telephone` (normalisé format Mali), plus champs contractuels (`type_contrat`, matières enseignées, etc. — voir le formulaire web équivalent pour la liste exhaustive). Mot de passe initial fixé à `123456`, avatar optionnel (`multipart/form-data`).

### `GET /enseignants/{id}` (auth)

Fiche + classes assignées + statistiques émargements/présences + 8 derniers émargements/présences.

### `PUT /enseignants/{id}` (auth)

Mêmes champs que la création (le mot de passe n'est pas modifié ici).

### `PATCH /enseignants/{id}/archive` / `PATCH /enseignants/{id}/reactivate` (auth)

Archivage logique (`is_deleted`), pas de suppression physique.

---

## Classes

### `GET /classes` (auth)

Liste complète (non paginée) `{"data": [...]}`, avec `classeOfficielle` et compteur d'élèves.

### `GET /classes/form-options` (auth)

`{"matieres": [...], "enseignants": [...], "ordres": [...]}` — options pour construire le formulaire de création/édition.

### `POST /classes` / `PUT /classes/{id}` (auth)

```json
{
  "nom_classe": "6ème A", "ordre_enseignement": "Fondamentale I",
  "id_matiere": [1, 2, 3], "id_enseignants": [5, null, 7], "coefficient": [2, 1, 3]
}
```
Les trois tableaux `id_matiere`/`id_enseignants`/`coefficient` sont alignés par index (une ligne de classe par matière).

### `DELETE /classes/{id}` (auth)

Refuse (`422`) si des élèves sont inscrits dans la classe.

---

## Matières

### `GET /matieres` (auth)

`{"data": [...], "ordres_autorises": [...], "ordres_disponibles": [...]}`, filtrable par `search`.

### `POST /matieres` / `PUT /matieres/{id}` (auth)

```json
{ "nom_matiere": "Mathématiques", "ordre_enseignement": ["Fondamentale I", "Fondamentale II"] }
```

### `DELETE /matieres/{id}` (auth)

Refuse (`422`) si utilisée dans une classe ou une évaluation existante.

---

## Emploi du temps

L'API n'a pas de "sélection courante" côté serveur (contrairement au web qui la garde en session) : `id_classe`/`id_annee` doivent être passés explicitement à chaque appel.

### `GET /timetable?id_classe=5&id_annee=2` (auth)

`{"classes": [...], "annees": [...], "selected_classe": {...}, "selected_annee": {...}, "lignes_classe": [...], "timetable": {"Lundi": [...], "Mardi": [...], ...}}`

### `POST /timetable` / `PUT /timetable/{id}` (auth)

```json
{ "id_classe": 5, "id_matiere": 2, "id_enseignant": 7, "id_annee_scolaire": 2, "jour": "Lundi", "heure_debut": "08:00", "heure_fin": "09:00" }
```

### `POST /timetable/save-grid` (auth)

Sauvegarde en masse de toute la grille en une fois :
```json
{
  "id_classe": 5, "id_annee": 2,
  "slots": { "Lundi": { "08:00": { "id": null, "id_matiere": 2, "id_enseignant": 7, "heure_debut": "08:00", "heure_fin": "09:00" } } },
  "recesses": [...]
}
```
Un créneau existant (`id` fourni) sans `id_matiere` est supprimé ; un nouveau créneau (`id` absent) avec `id_matiere` est créé.

### `DELETE /timetable/{id}` (auth)

### `GET /timetable/download-pdf?id_classe=5&id_annee=2` (auth, flux binaire)

---

## Évaluations (notes)

### `GET /evaluations` (auth)

Query : `id_classe`, `id_matiere`, `id_annee_scolaire`, `id_trimestre`, `mois`. Paginé (20/page), un compte `enseignant` ne voit que ses propres évaluations.

### `GET /evaluations/students` (auth) et `GET /evaluations/classes/{idClasse}/matieres` (auth)

Endpoints support pour construire le formulaire de création (liste d'élèves ciblés, matières disponibles pour une classe).

### `POST /evaluations` (auth, réservé aux enseignants)

Créée via `validateProgramme` (hérité) — champs clés : `id_classe`, `id_matiere`, `id_annee_scolaire`, `id_trimestre`/`mois`, `id_note` (type de note), `libeller`, `date_evaluation`, `heure_debut`, `heure_fin`. Génère une ligne par élève de la classe (`note` initialement `null`).

### `GET /evaluations/{id}` (auth)

Détail + toutes les lignes de notes (`details`) + matière/classe.

### `PUT /evaluations/{id}/notes` (auth)

```json
{ "id_ligneEvaluation": [101, 102, 103], "note": [15.5, null, 12] }
```
Les deux tableaux sont alignés par index. Si la classe exige une validation des notes privées, le statut passe à `en_attente` (notifie les validateurs) au lieu de `valide` directement.

### `POST /evaluations/{id}/validate` (auth, permission de validation)

Force toutes les lignes de l'évaluation à `valide`.

### `DELETE /evaluations/{id}` (auth)

---

## Émargements

Cahier de texte des enseignants (heures de cours effectuées + leçon couverte).

### `GET /emargements` (auth)

Query : `id_enseignant`, `id_classe`, `id_matiere`, `valide`, `date_debut`, `date_fin`. Réponse enrichie avec listes de filtres (`enseignants`, `classes`, `matieres`, `trimestres`, `annees`, `lecons`) + `summary` (total, en attente, heures validées, nb leçons distinctes) + `permissions` (droits contextuels de l'utilisateur courant).

### `POST /emargements` (auth)

```json
{ "id_enseignant": 7, "id_classe": 5, "id_matiere": 2, "chapitre": "...", "id_lecon": 12, "new_lecon_titre": null, "nombre_heure": 2, "id_trimestre": 1, "id_anneeScolaire": 2, "date_emargement": "2026-09-07" }
```
`id_enseignant` est forcé à l'enseignant connecté si `droit === 'enseignant'`. Créé avec `valide = 0`, notifie les validateurs.

### `PUT /emargements/{id}` (auth)

Mêmes champs. Refuse (`422`) si déjà validé.

### `POST /emargements/{id}/validate` (auth, permission `emargement_validation_admin`)

### `DELETE /emargements/{id}` (auth)

Refuse si déjà validé.

---

## Présences

Suivi des heures de présence des enseignants (distinct des émargements — présence physique vs contenu pédagogique couvert).

### `GET /presences` (auth)

Query : `id_enseignant`, `id_classe`, `valide`, `date_debut`, `date_fin`. Réponse enrichie (`enseignants`, `classes`, `trimestres`, `annees`, `permissions`).

### `POST /presences` (auth)

```json
{
  "id_enseignant": 7, "id_classe": 5, "date_presence": "2026-09-07", "nombre_heure": 3,
  "id_trimestre": 1, "id_anneeScolaire": 2,
  "lecons": [{ "titre": "Chapitre 1", "nombre_heure": 2, "progression": 50 }]
}
```

### `PUT /presences/{id}` (auth) — mêmes champs, refuse si déjà validée.

### `POST /presences/{id}/validate` (auth) / `DELETE /presences/{id}` (auth)

---

## Bulletins

### `GET /bulletins/classes` (auth)

Liste des classes disponibles pour la génération de bulletins (avec compteur d'élèves actifs).

### `GET /bulletins/classes/{idClasse}` (auth)

Options de formulaire : années, trimestres, mois disponibles.

### `GET /bulletins/classes/{idClasse}/data?id_annee=2&id_trimestre=1` (auth)

Données brutes du bulletin (hérité du contrôleur web, déjà en JSON).

### `GET /bulletins/classes/{idClasse}/students?id_annee=2&id_trimestre=1` (auth)

Liste des élèves avec une URL de téléchargement individuelle prête à l'emploi :
```json
[{ "id": 42, "nom": "...", "prenom": "...", "matricule": "...", "url": "/api/v1/bulletins/42/telecharger?id_annee=2&id_trimestre=1" }]
```

### `POST /bulletins/classes/{idClasse}/publish` / `DELETE /bulletins/classes/{idClasse}/publish` (auth)

```json
{ "id_annee": 2, "id_trimestre": 1 }
```
ou `{ "id_annee": 2, "mois": 10 }` (un seul des deux, jamais les deux). Publie/dépublie le bulletin pour que les parents puissent le voir/télécharger.

### `POST /bulletins/classes/{idClasse}/pdf` (flux binaire) / `GET /bulletins/{id}/telecharger` (flux binaire)

---

## Programmes officiels

### `GET /programmes?id_classe_officielle=3` (auth)

`{"programmes": [...], "classes_officielles": [...], "can_download_pdf": bool, "can_create": bool, "can_update": bool, "can_delete": bool}`

### `GET /programmes/create` (auth) — options de formulaire (classes officielles + matières).

### `POST /programmes` / `PUT /programmes/{id}` (auth)

```json
{
  "id_classe_officielle": 3,
  "matieres": [{ "id_matiere": 2, "lecons": [{ "titre": "Chapitre 1" }, { "titre": "Chapitre 2" }] }]
}
```

### `GET /programmes/{id}/edit` (auth) / `DELETE /programmes/{id}` (auth) / `GET /programmes/pdf/download` (flux binaire)

---

## Finances

### `GET /finances/paiements` (auth, permission `paiements_apercu`)

Query `id_classe`, `id_annee`. Paginé (20/page). Renvoie aussi listes de filtres, la caisse active, `is_public_school`, `next_reference` (prochaine référence de paiement suggérée).

### `POST /finances/paiements` (auth, permission `paiements_faire`)

```json
{ "echeance_id": 10, "date_paiement": "2026-09-07", "motif": "...", "montant_paye": 25000, "mode_reglement": "espèces", "parent_id": 4, "nom_payeur": "...", "telephone": "+223..." }
```

### `PUT /finances/paiements/{id}` (auth) / `POST /finances/paiements/{id}/cancel` (auth)

Annulation : `{"motif_annulation": "..."}`.

### `GET /finances/eleves/{id}/contexte` (auth)

Contexte de paiement d'un élève précis (échéances, historique) — utile pour préremplir un nouveau paiement.

### `GET /finances/caisse` (auth, permission `caisses_apercu`)

Solde de la caisse de l'école + mouvements (recettes/dépenses fusionnés et triés par date).

### `POST /finances/encaissements` (auth)

```json
{ "id_caisse": 1, "id_annee_scolaire": 2, "type_operation": "...", "date_encaissement": "2026-09-07", "motif_encaissement": "...", "montant_encaissement": 50000 }
```

### `POST /finances/decaissements` (auth, permission `decaissements_creation`)

```json
{ "id_caisse": 1, "id_annee_scolaire": 2, "date_decaissement": "2026-09-07", "motif_decaissement": "...", "montant_decaissement": 20000 }
```
Si l'utilisateur a le droit de valider directement (`canValidateDecaissement`), la dépense est immédiatement déduite de la caisse ; sinon elle reste `valide = 0` en attente, et les validateurs sont notifiés.

### `POST /finances/decaissements/{id}/validate` (auth)

---

## Salaires enseignants

### `GET /salaires` (auth) — vue "paiement" / `GET /salaires/etat` (auth) — vue "état des lieux"

### `POST /salaires/payer` (auth, permission de paiement salarial)

Paiement unique :
```json
{ "id_enseignant": 7, "mois": "09", "annee": 2026, "source": "emargement|presence", "montant_verse": 50000, "date_paiement": "2026-09-07" }
```
Paiement groupé (même endpoint, détection automatique via la présence de `rows`) :
```json
{ "date_paiement": "2026-09-07", "rows": [{ "id_enseignant": 7, "mois": "09", "annee": 2026, "source": "emargement", "montant_verse": 50000 }] }
```

### `GET /salaires/etat/pdf`, `GET /salaires/bulletin` (flux binaires)

---

## Configuration / administration

Réservé aux rôles avec les permissions adéquates (`SupAdmin` a toujours accès). La plupart des listes sont paginées (15/page) et filtrables par `search`.

### Écoles

- `GET /configuration/ecoles` (permission `ecoles_apercu`)
- `POST /configuration/ecoles` / `PUT /configuration/ecoles/{id}` (SupAdmin uniquement) — champs de `validateEcole` (nom, type — dont désormais **Collège**, adresse, académie, CAP, logo en `multipart/form-data`, etc.)
- `DELETE /configuration/ecoles/{id}` (SupAdmin) — refuse si des utilisateurs y sont rattachés.

### Années scolaires

- `GET /configuration/annees` (permission `annees_scolaires_apercu`) — inclut `annee_en_cours`.
- `POST /configuration/annees` : `{ "annee": "2026-2027", "date_debut": "2026-10-01", "date_fin": "2027-07-31" }`

### Utilisateurs

- `GET /configuration/utilisateurs` — liste complète non paginée `{"data": [...]}`, filtrable par `search`.
- `POST /configuration/utilisateurs` : `type_utilisateur` détermine le type de compte à créer —
  - `0` = lier un compte à un enseignant existant (`id_enseignant` requis)
  - `2` = lier un compte à un parent existant (`id_parent` requis)
  - `3` = DAE (`id_academie` requis), `4` = DCAP (`id_cap` requis)
  - autre (défaut `1`) = compte classique (`nomPrenom`, `email`, `telephone`, `genre`, `droit`, `fonction`, `idEcole` pour SupAdmin, `managed_orders` pour un Gestionnaire de complexe)
  
  Mot de passe généré automatiquement si absent (`pwd` optionnel dans le payload).
- `PUT /configuration/utilisateurs/{id}` — mêmes règles selon le type déjà déterminé par le compte cible.
- `PATCH /configuration/utilisateurs/{id}/status` : `{"statut": 0|1}` (impossible sur son propre compte).
- `DELETE /configuration/utilisateurs/{id}` (impossible sur son propre compte, permission `utilisateurs_supprimer` sauf SupAdmin).
- `GET /configuration/utilisateurs/{id}/permissions` — `{"utilisateur": {...}, "grouped_permissions": {...}, "permission_ids": [...], "read_only": bool}`
- `PUT /configuration/utilisateurs/{id}/permissions` : `{"permissions": [1, 5, 12], "managed_orders": ["Fondamentale I"]}`

### Permissions (catalogue)

- `GET /configuration/permissions` (permission `permissions_apercu`) — dédupliqué par nom canonique.
- `POST /configuration/permissions` (SupAdmin) : `{"name": "nouvelle_permission"}`

### Académies / CAP / types de notes / classes officielles / statuts de contrôle

Données de référence, CRUD complet symétrique pour chacune (`GET` liste paginée avec `search`, `POST` création, `PUT .../{id}` modification, `DELETE .../{id}` suppression protégée si des dépendances existent) :

| Ressource | Base URL | Permission | Champs clés |
|---|---|---|---|
| Académies | `/configuration/academies` | `academies_apercu` (lecture), SupAdmin (écriture) | `nom_academie` |
| CAP | `/configuration/caps` | `dcap_apercu` (lecture), SupAdmin (écriture) | `nom_cap`, `id_academie` |
| Types de notes | `/configuration/types-notes` | `types_notes_apercu` | `typeNote` (`devoir`/`composition`/`NT10`), `codeNote`, `valeur` |
| Classes officielles | `/configuration/classes-officielles` | `classes_officielles_apercu` | `nom_classe_officielle`, `ordre_enseignement` |
| Statuts de contrôle | `/configuration/status-controles` | `status_controles_apercu` | `controle`, `alert` (`oui`/`non`), `penalite_conduite` (0-18) |

---

## Annonces

### `GET /annonces` (permission `annonces_apercu`)

Paginé (15/page), avec pièces jointes groupées par annonce.

### `POST /annonces` (permission `annonces_creation`, `multipart/form-data` si fichiers)

```
titre, contenu, public_cible (tous|parents|enseignants|gestionnaires),
statut_annonce (publie|brouillon|archive),
fichiers[] (max 5 Mo chacun, pdf/jpg/jpeg/png/doc/docx/xls/xlsx), titres_fichiers[]
```

### `POST /annonces/marquer-lues` (auth) — marque toutes les annonces visibles comme lues pour l'utilisateur courant.

### `POST /annonces/{id}/publier` / `POST /annonces/{id}/archiver` / `DELETE /annonces/{id}` (permission `annonces_creation`/`annonces_supprimer`)

---

## Résultats nationaux

Suivi des résultats DEF/BAC des élèves.

### `GET /resultats-nationaux` (auth)

Query `id_classe`, `id_annee`, `niveau_examen` (`DEF`/`BAC`).

### `POST /resultats-nationaux` (auth)

```json
{
  "id_classe": 5, "id_annee": 2, "niveau_examen": "DEF", "date_resultat": "2026-07-01",
  "resultats": { "42": { "decision": "admis", "moyenne": 14.5, "observation": "..." } }
}
```
Les clés de `resultats` sont les `id_eleve`.

### `POST /resultats-nationaux/import` (`multipart/form-data`)

```
id_classe, id_annee, niveau_examen, date_resultat,
fichier_resultats (xls/xlsx/csv/txt/pdf, max 10 Mo)
```
Rapprochement par matricule élève. Réponse : `{"success": bool, "saved": n, "ignored": n}`.

---

## Appels d'épreuves (contrôle/discipline)

Suivi des convocations/incidents disciplinaires liés aux épreuves, avec impact automatique sur la note de conduite.

### `GET /appels-epreuves` (permission `controle_apercu`/`controle_creation`)

Nécessite au moins un filtre (`id_classe`, `id_matiere`, `id_annee_scolaire`, `id_trimestre`, `nom_eleve`, `date_debut`, `date_fin`) — sans filtre, la liste est vide par conception.

### `GET /appels-epreuves/create` (permission `controle_creation`)

Options de formulaire + liste d'élèves si `id_classe`/`id_annee_scolaire` fournis en query.

### `POST /appels-epreuves` (permission `controle_creation`)

```json
{
  "id_classe": 5, "id_matiere": 2, "id_annee_scolaire": 2, "id_trimestre": 1,
  "date": "2026-09-07", "libelle": "Composition", "heure_debut": "08:00", "heure_fin": "10:00",
  "notifier_parent": true,
  "statuts": { "42": 1, "43": 2 }
}
```
Les clés de `statuts` sont les `id_eleve`, les valeurs les `id_controle` (statut : présent/absent/etc.). Recalcule automatiquement la note de conduite de la classe et notifie les parents si `notifier_parent` est vrai.

---

## Abonnements

Gestion de l'abonnement de l'école (SaaS) et de son paiement.

### `GET /abonnements` (auth, `canManageAbonnements`)

Offres disponibles, abonnement courant, historique des paiements ; si l'utilisateur a un droit de revue (`canReviewAbonnements`), inclut aussi `admin_paiements` (paiements en attente toutes écoles).

### `POST /abonnements/payer` (paiement en ligne)

```json
{ "offre_id": 3, "fournisseur": "orange_money", "numero_payeur": "+223..." }
```
→ `201 { "reference": "...", "checkout_url": "https://..." }` — l'app mobile doit ouvrir `checkout_url` dans un navigateur intégré (in-app browser), pas dans une WebView de confiance élevée (paiement tiers).

### `POST /abonnements/manuel` (soumission de reçu manuel, `multipart/form-data`)

```
offre_id, mode_paiement, transaction_ref, owner_note,
receipt (image, jpg/jpeg/png/webp, max 5 Mo)
```

### `GET /abonnements/paiements/{reference}` (détail d'un paiement)

### `POST /abonnements/paiements/{paiement}/approuver` / `.../rejeter` (réviseurs uniquement)

`{"review_note": "..."}` (optionnel)

### `POST /abonnements/offres` / `PUT /abonnements/offres/{offre}` / `PATCH /abonnements/offres/{offre}/toggle` (configuration des offres, accès restreint)

---

## Assistant (Kalanbot)

### `POST /assistant/chat` (auth)

Chat IA intégré (Kalanbot). Voir `AssistantController::chat` côté web pour le format exact de la requête/réponse — non détaillé ici, endpoint réutilisé tel quel sans contrôleur API dédié.

---

## Limitations connues

- **Pas de mode hors-ligne côté serveur** : chaque appel API est synchrone et nécessite une connexion. Le mode "offline" pour les écoles sans internet est une responsabilité du client mobile (cache local + file d'attente de synchronisation) — à concevoir côté React Native, l'API ne fait qu'exposer `?since=` sur les notifications comme point d'ancrage pour une future synchronisation plus large.
- **Uploads** : tous les endpoints avec fichiers (`enseignants` avatar, `annonces` pièces jointes, `abonnements/manuel` reçu, `resultats-nationaux/import`) attendent du `multipart/form-data` classique, pas du base64 dans le JSON.
- **Webhooks de paiement** (callback serveur-à-serveur du fournisseur) ne sont pas exposés côté mobile — normal, ce sont des appels du fournisseur vers KalanNet, jamais du client vers l'API.
- **`GET /dashboard`** n'a pas de schéma de réponse figé documenté ligne par ligne ici (contenu riche et différent par rôle) — se référer à un appel réel par rôle testé pendant le développement du client.
