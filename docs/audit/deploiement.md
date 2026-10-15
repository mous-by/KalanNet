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
git log -1 --oneline               # noter la version actuelle (pour un retour arrière)
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
J=$(mktemp)
T=$(curl -s -c "$J" "<domaine>/login" | grep -o 'name="_token" value="[^"]*"' | cut -d'"' -f4)
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

## Intervention 2 — index (après validation de l'intervention 1)

Détail et preuves : [index-base-de-donnees.md](index-base-de-donnees.md).
Essai sur la copie de production : 10 index créés en 0,4 s, retour arrière en
0,7 s, schéma revenu à l'identique, données inchangées.

1. Sauvegarde (export phpMyAdmin).
2. Un commit séparé ajoute la migration dans `database/migrations/` (préparé à ce
   moment-là, pas avant, pour ne pas partir avec l'intervention 1).
3. Sur le serveur :

```bash
cd <dossier>
git log -1 --oneline                         # noter la version
git pull origin main
php artisan migrate:status | tail -3         # la migration des index doit apparaître « Pending »
php artisan migrate --force
php artisan tinker --execute="foreach (['eleve','utilisateurs','matiere','matiere_ordre','controle_eleve','conduite','ligne_reinscription'] as \$t) echo \$t, ' : ', collect(DB::select(\"SHOW INDEX FROM \$t WHERE Key_name LIKE 'idx\\\\_%'\"))->pluck('Key_name')->unique()->implode(', '), PHP_EOL;"
```

Attendu : `utilisateurs` (email, telephone, ecole), `eleve` (classe_annee,
ecole_annee), `matiere` (ecole), `matiere_ordre` (matiere), `controle_eleve`
(periode), `conduite` (periode), `ligne_reinscription` (eleve_annee).

Retour arrière : `php artisan migrate:rollback --step=1` (ne retire que ces 10 index).

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
