# Déploiement de l'audit sur LWS — procédure

Trois interventions **séparées**, dans cet ordre. Chacune ne commence qu'après
la vérification de la précédente.

Remplacer dans les commandes :
- `<dossier>` : dossier de KalanNet sur le serveur (celui qui contient `artisan`) ;
- `<domaine>` : adresse du site en production (ex. `https://…`).

## État vérifié avant intervention (copie du 15/10/2026, 19 h 49)

- Dernière migration en production : `2026_10_15_170000_create_classe_maitres`
  (commit `259dad8`). Les commits de 18 h 39 et après ne semblaient pas déployés.
- Depuis `259dad8` : aucune migration, aucune dépendance Composer ; seul
  `config/sanctum.php` change (jetons API : 90 jours).
- Production : MariaDB 10.11, cache Laravel en base de données, 46 jetons API,
  aucun compte DAE / DCAP, aucun compte rattaché à plusieurs écoles.

---

## Intervention 1 — correctifs de sécurité et performances

> **Réalisée et vérifiée le 15/10/2026** sur `~/htdocs/kalannet.com` (https://kalannet.com) :
> correctifs `2ffc1f0`, `8b7c9a9`, `8f0340b`, `e400627` présents ; API et web :
> choix d'école sans mot de passe refusé ; 46 anciens jetons API supprimés.
> Le serveur porte en plus des fichiers d'hébergement propres à LWS (`.htaccess`,
> `default_index.html`, `test.html`), sans code applicatif.
>
> Sur ce serveur : `git --no-pager …` (pas de pager), pas de `mktemp` ni de `head`.

Commits concernés (tous dans `origin/main`) :

| Commit | Contenu |
|---|---|
| `2ffc1f0` | Choix de l'école : mot de passe vérifié exigé (web et API), jetons 90 jours |
| `8b7c9a9` | DAE / DCAP : `ecole_id` limité à leur territoire |
| `8f0340b` | 404 au lieu de 500 pour la classe d'une autre école |
| `674042e`, `397b929`, `6c7dfa9`, `e400627` | Performances (bulletins, cache de schéma, tableau de bord) |
| `e9c3fb5` … `52e0375` | Jardin (déjà testés) |

La migration des index **n'est pas** dans `database/migrations/` : elle ne part pas avec cette intervention.

### 1. Sauvegarde

Exporter la base depuis phpMyAdmin (comme l'export du 15/10) et la garder hors du serveur.

### 2. Déploiement

```bash
cd <dossier>
git status --short                 # doit être vide (aucune modification locale sur le serveur)
git --no-pager log -1 --oneline    # noter la version actuelle (pour un retour arrière)
git pull origin main
php artisan migrate --force        # attendu : « Nothing to migrate »
php artisan optimize:clear
```

`composer install` n'est pas nécessaire (aucune dépendance modifiée).

### 3. Vérifier la présence du code

```bash
cd <dossier>
for c in 2ffc1f0 8b7c9a9 8f0340b e400627; do git merge-base --is-ancestor $c HEAD && echo "$c présent" || echo "$c ABSENT"; done
grep -cF "login_choix" app/Http/Controllers/AuthController.php                       # attendu : 2
grep -cF "selection_token" app/Http/Controllers/Api/V1/AuthController.php            # attendu : 3
grep -cF "ne fait pas partie de votre territoire" app/Support/Api/Authorizer.php     # attendu : 1
grep -cF 'abort_if($classe === null, 404)' app/Http/Controllers/EvaluationController.php  # attendu : 1
php artisan tinker --execute="echo config('sanctum.expiration');"                 # attendu : 129600
```

### 4. Vérifier le fonctionnement

**Uniquement après le déploiement** (sur l'ancienne version, la première
commande délivrerait un jeton : dans ce cas, le supprimer aussitôt, voir 5).

API — choix d'école sans jeton de sélection, avec le compte de démonstration
Google Play (identifiant 19, école 12) :

```bash
curl -s -X POST "<domaine>/api/v1/auth/select-school" -H "Accept: application/json" \
  -d id_utilisateur=19 -d id_ecole=12
# attendu : {"message":"Sélection expirée ou invalide. Reconnectez-vous ; …"}   (HTTP 422, aucun « token »)
```

Web — même vérification, sans être connecté :

```bash
J=~/kn_verif_cookies.txt
T=$(curl -s -c "$J" "<domaine>/login" | grep -o 'name="_token" value="[^"]*"' | cut -d'"' -f4); T="${T%%$'\n'*}"   # premier jeton seulement
curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" -b "$J" -c "$J" -X POST "<domaine>/login/select-school" \
  -d _token="$T" -d idUtilisateur=19 -d idEcole=12
curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" -b "$J" "<domaine>/dashboard"
rm -f "$J"
# attendu : 302 vers …/login, puis /dashboard -> 302 vers …/login (pas de connexion)
```

Puis, dans le navigateur : connexion normale avec un compte Admin, pages Élèves,
Classes, Bulletins, Tableau de bord. Vérifier `storage/logs/laravel.log` :

```bash
tail -n 50 <dossier>/storage/logs/laravel.log
```

DAE / DCAP : aucun compte de ce type en production ; le correctif est vérifié par
la présence du code (étape 3) et par `tests/Integration/TerritoireEcoleIdTest.php`.

### 5. Suppression des anciens jetons API — à annoncer avant

**Effet** : toutes les sessions de l'application mobile sont fermées ; chaque
utilisateur mobile devra se reconnecter une fois. Les jetons ont pu être obtenus
par la faille corrigée ; ils sont tous antérieurs au correctif.

```bash
cd <dossier>
php artisan tinker --execute="echo DB::table('personal_access_tokens')->count();"   # noter le nombre
php artisan tinker --execute="echo DB::table('personal_access_tokens')->delete();"  # nombre supprimé
```

### Retour arrière de l'intervention 1

```bash
cd <dossier>
git checkout <version notée à l'étape 2>    # ou : git reset --hard <version> si aucune modification locale
php artisan optimize:clear
```

Aucune migration n'ayant été jouée, il n'y a rien à annuler en base. Les jetons
supprimés ne se restaurent pas (les utilisateurs se reconnectent).

---

## Intervention 2 — correctif complémentaire, puis index

Le prochain `git pull` apporte à la fois :
- `d1b8056` **sécurité** : suppression de l'évaluation d'une autre école refusée ;
- `a6bf3d9` tableau de bord (5 comptages en 1), `e1349a3` test unitaire, documentation ;
- `20ed0fb` la migration des **10 index**.

Pour garder les index séparés : **2a** `git pull` sans `migrate` (le correctif est
actif, la migration reste en attente), vérification ; puis **2b** `migrate`.

### 2a — correctif (sans migration)

Avant : export phpMyAdmin de la base. Une commande par ligne :

```bash
cd ~/htdocs/kalannet.com
git --no-pager log -1 --oneline
git pull origin main
php artisan optimize:clear
for c in d1b8056 a6bf3d9 20ed0fb; do git merge-base --is-ancestor $c HEAD && echo "$c présent" || echo "$c ABSENT"; done
grep -cF '// Classe d' app/Http/Controllers/EvaluationController.php
php artisan migrate:status | grep ajouter_index
```

Attendu : 3 × « présent » ; `1` ; la migration `2026_10_16_100000_ajouter_index_audit_performance`
en **Pending** (pas encore appliquée). Puis, dans le navigateur, Tableau de bord et
Évaluations avec un compte Admin.

### 2b — index (heures creuses)

```bash
cd ~/htdocs/kalannet.com
php artisan migrate --force
php artisan tinker --execute="foreach (['eleve','utilisateurs','matiere','matiere_ordre','controle_eleve','conduite','ligne_reinscription'] as \$t) echo \$t, ' : ', collect(DB::select(\"SHOW INDEX FROM \$t WHERE Key_name LIKE 'idx\\\\_%'\"))->pluck('Key_name')->unique()->implode(', '), PHP_EOL;"
php artisan optimize:clear
```

Attendu : `eleve : idx_eleve_classe_annee, idx_eleve_ecole_annee`,
`utilisateurs : idx_utilisateurs_email, idx_utilisateurs_telephone, idx_utilisateurs_ecole`,
`matiere : idx_matiere_ecole`, `matiere_ordre : idx_matiere_ordre_matiere`,
`controle_eleve : idx_controle_eleve_periode`, `conduite : idx_conduite_periode`,
`ligne_reinscription : idx_ligne_reinscription_eleve_annee`. Puis vérifier les
pages Élèves, Classes, Bulletins, Réinscription, Tableau de bord.

Retour arrière des index : `php artisan migrate:rollback --step=1 --force` (ne
retire que ces 10 index, aucune donnée touchée). Retour arrière du code :
`git checkout <version notée>` puis `php artisan optimize:clear`.

Essais réalisés : base de test (≈ 300 écoles) et copie de la production —
création 0,4 à 0,6 s, retour arrière 0,5 à 0,7 s, schéma restauré à l'identique.

### dev.kalannet.com

Même procédure dans `~/htdocs/dev.kalannet.com` si ce site est utilisé ; y
supprimer aussi les anciens jetons API avec la commande protégée de
l'intervention 1 (étape 5).

---

## Intervention 3 — application mobile vc17

- Build : `cea3c6ae-17ac-4a81-be38-d607ab86d4a5`, profil `production`, versionCode
  **17**, commit `2ffc1f0` (contient le `selection_token`) ; aucun changement
  mobile depuis.
- Fichier : https://expo.dev/artifacts/eas/KeiDs5l4Vg34aEEZuKvkzTR-htGy8p4wXshyhFaK2DA.aab
- **Ne pas publier vc16** (sans le correctif de connexion).
- Publication **après l'intervention 1** : vc17 envoie le `selection_token`,
  que seul le serveur corrigé utilise (il reste compatible avec l'ancien serveur).

Étapes Play Console (la clé de service `google-service-account.json` n'est pas
sur le poste de développement, donc pas d'envoi automatique `eas submit`) :

1. Télécharger le `.aab` ci-dessus.
2. Play Console → KalanNet (`com.kalannet.mobile`) → Tests → Test interne →
   Créer une version → importer le `.aab` → notes de version → Enregistrer →
   Examiner → Déployer.
3. Vérifier sur un téléphone du test interne : connexion, puis un compte Admin.
4. Production → Créer une version → « Ajouter depuis la bibliothèque » (versionCode 17)
   → déploiement progressif conseillé (ex. 20 %) → surveiller les plantages → 100 %.
