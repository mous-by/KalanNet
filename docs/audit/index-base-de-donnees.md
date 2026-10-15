# Index de la base de données — proposition (non appliquée)

**État : en attente de validation.** Aucun index n'a été ajouté à la base de
travail ni à la production. La migration est rangée dans
[migration-proposee/](migration-proposee/) pour que `php artisan migrate` ne
l'exécute pas.

## Méthode

1. **Index existants** relevés dans `information_schema` et dans les 78
   migrations, pour éviter les doublons.
2. **Requêtes réelles** capturées pendant l'utilisation des écrans concernés
   (connexion, liste des élèves, création de classe, bulletins, réinscription,
   tableau de bord), puis passées à `EXPLAIN`.
3. **Base de test locale séparée** (`db_kalannet_index_audit`, jamais la
   production), mêmes structures, volumes d'environ 300 écoles : 100 000 élèves,
   900 000 notes, 300 000 notes de conduite, 150 000 contrôles, 70 000
   réinscriptions, 12 000 utilisateurs, 9 000 matières. Chaque requête mesurée
   sans puis avec l'index (médiane de 20 exécutions).
4. **Coût** : temps de création, taille, insertions avant / après, et écritures
   pendant la création (verrouillage).

Aucune copie de la production n'était disponible : les volumes réels et les
données orphelines de la production restent à vérifier avec
[verifications-avant-index.sql](verifications-avant-index.sql) (lecture seule).

## Constat préalable

Les migrations `2026_05_25_000003` (tables `controle_eleve` et `conduite`)
prévoyaient des index, mais commencent par `if (!Schema::hasTable(...))` : les
tables existant déjà, les index n'ont jamais été créés. La production est très
probablement dans le même cas (à confirmer avec la requête 2 du script).

## Index proposés (10)

Tous **non uniques** : ils ne refusent et ne modifient aucune donnée.

| Table | Index | Colonnes | Requête améliorée | Avant | Après |
|---|---|---|---|---|---|
| `utilisateurs` | `idx_utilisateurs_email` + `idx_utilisateurs_telephone` | `email` ; `telephone` | Connexion (`email = ? OR telephone = ?`) | lecture complète, 12 026 lignes, 17,1 ms | 2 lignes, 1,8 ms |
| `utilisateurs` | `idx_utilisateurs_ecole` | `idEcole` | Utilisateurs d'une école | lecture complète, 5,2 ms | 40 lignes, 1,5 ms |
| `eleve` | `idx_eleve_classe_annee` | `id_classe, id_annee, etat_dossier` | Élèves d'une classe (listes, bulletins, réinscription) | 49 819 lignes examinées, 43,4 ms | 12 lignes, 0,5 ms |
| `eleve` | `idx_eleve_ecole_annee` | `id_ecole, id_annee, etat_dossier` | Effectifs de l'école (tableau de bord) | 11,6 ms | 0,35 ms |
| `matiere` | `idx_matiere_ecole` | `id_ecole` | Matières de l'école (formulaire de classe, LV2) | lecture complète, 5,6 ms | 1,4 ms |
| `matiere_ordre` | `idx_matiere_ordre_matiere` | `id_matiere, ordre_enseignement` | Ordres des matières | lecture complète, 6,1 ms | 2,1 ms |
| `controle_eleve` | `idx_controle_eleve_periode` | `id_ecole, id_classe, id_annee_scolaire, id_trimestre` | Conduite du bulletin (pénalités de la classe) | lecture complète, 149 632 lignes, 76,2 ms | 12 lignes, 1,3 ms |
| `conduite` | `idx_conduite_periode` | `id_classe, id_annee_scolaire, id_trimestre, id_eleve` | Notes de conduite enregistrées | lecture complète, 299 442 lignes, 101,3 ms | 12 lignes, 1,7 ms |
| `ligne_reinscription` | `idx_ligne_reinscription_eleve_annee` | `id_eleve, id_annee` | Réinscription : élèves déjà réinscrits | lecture complète, 70 272 lignes, 23,4 ms | 12 lignes, 0,6 ms |

Pour la génération d'un bulletin de classe, les deux requêtes de conduite
passent ainsi d'environ 177 ms à environ 3 ms sur ce volume ; ces lectures
complètes grandissent avec la base, les lectures par index non.

### Index étudié puis écarté

| Table | Colonnes | Raison |
|---|---|---|
| `ligne_evaluation` | `id_eleve, id_annee_scolaire, id_trimestre` | Pas de gain : l'index existant sur `id_eleve` suffit déjà (bulletin 1,36 ms sans, 2,87 ms avec). 23 Mo de plus et écriture des notes nettement plus lente. |
| `controle_eleve` | `id_eleve, id_controle` | Prévu par la migration d'origine, mais aucune requête du code ne part de `id_eleve` sur cette table : les appels filtrent par école, classe, année et trimestre (couverts par `idx_controle_eleve_periode`). |

### Écartés pour l'instant (à décider séparément)

- **Contraintes UNIQUE** (`conduite`, `user_permission`, `matiere_ordre`,
  `ligne_reinscription`) : utiles pour l'intégrité, mais elles refuseraient des
  données existantes en double. À envisager seulement après la requête 3 du
  script de vérification, et après correction des doublons éventuels.
- **Suppression des index devenus redondants** (par exemple `eleve.id_ecole`,
  préfixe de `idx_eleve_ecole_annee`) : gain faible, aucune suppression proposée.
- **Clés étrangères manquantes** : la base locale contient déjà 4 lignes
  orphelines dans `matiere_ordre` (ordres de matières supprimées) ; à traiter
  après le contrôle des orphelins en production.

## Coûts mesurés

| Table | Création de l'index (base de test) | Taille ajoutée |
|---|---|---|
| `utilisateurs` (12 000 lignes) | 0,1 à 2,5 s par index | 0,7 Mo |
| `eleve` (100 000) | 0,7 à 1,0 s par index | 5,1 Mo |
| `controle_eleve` (150 000) | 1,0 s | ≈ 4 Mo |
| `conduite` (300 000) | 2,9 s | 9,5 Mo |
| `ligne_reinscription` (70 000), `matiere`, `matiere_ordre` | < 0,7 s | < 2 Mo |
| **Total, 10 index** (migration complète) | **≈ 11 s** | environ 22 Mo |

**Écriture.** Sur la machine de test, 2 000 insertions : `eleve` 0,45 → 1,1 ms
par ligne, `controle_eleve` 0,44 → 1,5 ms, `conduite` inchangé. Ces chiffres sont
**pessimistes** : la base locale n'a que 16 Mo de cache InnoDB
(`innodb_buffer_pool_size`, valeur XAMPP), si bien que chaque insertion lit le
disque. Les tables concernées s'écrivent peu souvent (inscriptions, appels) ; le
surcoût reste de l'ordre de la milliseconde par ligne.

**Verrouillage (MariaDB 10.4, InnoDB).** Création en ligne
(`ALGORITHM=INPLACE, LOCK=NONE`) : lectures et écritures continuent. Mesuré
pendant la création de l'index de `conduite` (3,8 s) : 45 écritures + lectures
réussies en 3 s, attente maximale 935 ms (verrou de métadonnées bref au début et
à la fin). Une longue transaction ouverte sur la table peut retarder ce verrou :
appliquer en heures creuses.

## Procédure d'application (après validation)

1. **Sauvegarde** complète de la base de production, et vérification qu'elle se
   restaure (sur une base de test).
2. Lancer [verifications-avant-index.sql](verifications-avant-index.sql) (lecture
   seule) ; noter volumes, index existants, doublons et orphelins.
3. Vérifier `innodb_buffer_pool_size` sur le serveur (idéalement une part
   importante de la mémoire disponible pour MariaDB).
4. En heures creuses : copier
   `docs/audit/migration-proposee/2026_10_16_100000_ajouter_index_audit_performance.php`
   dans `database/migrations/`, puis `php artisan migrate --force`.
   Un index déjà présent, ou dont la table ou une colonne manque, est ignoré ;
   la migration peut être relancée sans risque.
5. Contrôler : `EXPLAIN` d'une requête de conduite (clé `idx_controle_eleve_periode`)
   et des pages élèves / bulletins / réinscription.

## Retour arrière

- `php artisan migrate:rollback --step=1` supprime les index créés (0,3 à 0,5 s sur
  la base de test). Aucune donnée n'est touchée : supprimer un index ne fait que
  revenir au comportement actuel.
- **Pas de double emploi** : `up()` ignore un index si son nom existe déjà ou si un
  index existant commence déjà par les mêmes colonnes.
- **Retour arrière limité** : `down()` ne supprime qu'un index portant l'un des
  10 noms **et** exactement les colonnes prévues.
- Testé sur la base de test ([outils/6-tester-migration-pieges.php](outils/6-tester-migration-pieges.php), 8/8) :
  index équivalent sous un autre nom (non dupliqué), nom déjà pris par un autre
  index (laissé tel quel), relance sans effet, retour exact à l'état d'avant,
  index préexistants conservés.
- **Compatibilité MariaDB 10.4** : `ADD INDEX` / `DROP INDEX` avec
  `ALGORITHM=INPLACE, LOCK=NONE` sur InnoDB (création en ligne disponible depuis
  MariaDB 10.0) ; testé sur MariaDB 10.4.28.

## Reproduire les mesures

Les scripts de construction de la base de test et de mesure sont dans
[outils/](outils/) (base locale dédiée, refus en production). Les tests de
non-régression applicatifs sont dans `tests/Integration/` (voir [README.md](README.md)).
