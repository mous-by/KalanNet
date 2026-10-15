# Inventaire des 93 tables

Généré le 16/10/2026 à partir du schéma (identique en local et en production,
vérifié sur la copie de production du 15/10/2026). Les volumes sont des
**comptages** de la production ; aucune donnée n'est reproduite.

- **Fichiers qui l'utilisent** : nombre de fichiers de `app/` et `resources/views/`
  qui citent la table (plus le modèle Eloquent s'il existe). Indicatif : une
  table citée par une seule chaîne dynamique peut échapper à ce décompte.
- Toutes les tables sont en InnoDB ; 53 clés étrangères au total.

## Points relevés

| Constat | Tables | Statut |
|---|---|---|
| Pas de clé primaire | `user_permission` (utilisée, 1 241 lignes), `roles`, `role_permission`, `user_role`, `evaluationprof`, `indiscipline` | Constaté. Une clé primaire / contrainte UNIQUE sur `user_permission` est souhaitable (0 doublon en production) ; à décider. |
| Non utilisées par le code | `roles` (1 ligne), `role_permission` (vide) | Constaté. Conservées : aucune suppression sans décision. |
| Index manquants sur des colonnes très filtrées | `eleve`, `utilisateurs`, `matiere`, `matiere_ordre`, `controle_eleve`, `conduite`, `ligne_reinscription` | 10 index prêts (migration `2026_10_16_100000`), voir [index-base-de-donnees.md](index-base-de-donnees.md). |
| Index prévus par une migration mais jamais créés | `controle_eleve`, `conduite` | La migration `2026_05_25_000003` est sautée quand la table existe déjà ; couvert par les 10 index. |
| Pas de clé étrangère | `ligne_evaluation` (notes), `matiere_ordre`, `ligneclasse.id_enseignants`, `conduite`, `controle_eleve` | Constaté ; 0 orphelin en production, 4 dans `matiere_ordre` en local. À décider après la croissance des données. |

## Tables

| Table | Modèle | Colonnes | Clé primaire | Clés étrangères | Index | Lignes (prod 15/10) | Fichiers qui l'utilisent | Remarque |
|---|---|---|---|---|---|---|---|---|
| `abonnements` | Abonnement | 9 | `id` | offre_id→abonnement_offres | 2 | 13 | 24 |  |
| `abonnement_offres` | AbonnementOffre | 11 | `id` | — | 1 | 6 | 4 |  |
| `abonnement_paiements` | AbonnementPaiement | 27 | `id` | abonnement_id→abonnements, offre_id→abonnement_offres | 6 | 0 | 3 |  |
| `academie` | Academie | 7 | `id_academie` | id_pays→pays | 4 | 26 | 19 |  |
| `anneescolaire` | AnneeScolaire | 5 | `id_anneeScolaire` | — | 1 | 4 | 5 |  |
| `annonces_academie` | — | 12 | `id_annonce` | id_academie→academie, id_utilisateur→utilisateurs | 3 | 0 | 3 |  |
| `annonces_admin_gestionnaire` | — | 11 | `id_annonce` | id_utilisateur→utilisateurs, id_ecole→ecole | 2 | 0 | 8 |  |
| `annonces_cap` | — | 12 | `id_annonce` | id_cap→cap, id_utilisateur→utilisateurs | 3 | 0 | 2 |  |
| `annonces_fichiers` | — | 9 | `id_fichier` | — | 0 | 0 | 4 |  |
| `annonces_lues` | — | 6 | `id` | id_utilisateur→utilisateurs | 1 | 0 | 6 |  |
| `app_notifications` | AppNotification | 10 | `id` | — | 1 | 4 | 12 |  |
| `banques` | Banque | 7 | `id_banques` | — | 1 | 0 | 6 |  |
| `bulletin` | — | 5 | `id_bulletin` | — | 2 | 0 | 2 |  |
| `bulletin_publications` | — | 10 | `id` | — | 2 | 1 | 4 |  |
| `cache` | — | 3 | `key` | — | 1 | 932 | 0 | table technique Laravel |
| `cache_locks` | — | 3 | `key` | — | 1 | 0 | 0 | table technique Laravel |
| `caisse` | Caisse | 9 | `id_caisse` | — | 1 | 0 | 12 |  |
| `cap` | Cap | 8 | `id_cap` | id_academie→academie, id_pays→pays | 5 | 125 | 18 |  |
| `classe` | Classe | 12 | `id_classe` | — | 1 | 28 | 69 |  |
| `classes_officielles` | ClasseOfficielle | 4 | `id_classe_officielle` | id_pays→pays | 1 | 30 | 10 |  |
| `classe_maitres` | — | 3 | `id` | id_classe→classe, id_enseignant→enseignants | 2 | 0 | 3 |  |
| `conduite` | — | 6 | `id_conduite` | — | 1 | 0 | 7 |  |
| `controle` | Controle | 8 | `id_controle` | id_eleve→eleve | 3 | 9 | 10 |  |
| `controle_eleve` | AppelEpreuve | 14 | `id_controle_eleve` | — | 1 | 0 | 8 |  |
| `decaissement` | Decaissement | 10 | `id_decaissement` | id_annee_scolaire→anneescolaire, id_caisse→caisse | 2 | 0 | 5 |  |
| `domaines` | — | 9 | `id_domaine` | — | 1 | 8 | 9 |  |
| `echeances_paiement` | EcheancePaiement | 8 | `id` | plan_paiement_id→plans_paiement | 1 | 0 | 3 |  |
| `ecole` | Ecole | 23 | `idEcole` | id_pays→pays | 1 | 10 | 56 |  |
| `eleve` | Eleve | 18 | `id_eleve` | id_annee→anneescolaire, id_matiere_lv2→matiere | 5 | 68 | 35 |  |
| `emargement` | Emargement | 12 | `id_emargement` | id_anneeScolaire→anneescolaire, id_trimestre→trimestre, id_matiere→matiere | 4 | 0 | 10 |  |
| `emploi_du_temps` | EmploiDuTemps | 8 | `id` | id_annee_scolaire→anneescolaire, id_matiere→matiere, id_enseignant→enseignants | 3 | 75 | 9 |  |
| `encaissement` | Encaissement | 10 | `id_encaissement` | id_caisse→caisse, idUtilisateur→utilisateurs, id_annee_scolaire→anneescolaire | 4 | 0 | 7 |  |
| `enseignants` | Enseignant | 28 | `id_enseignant` | — | 1 | 55 | 44 |  |
| `evaluation` | Evaluation | 6 | `id_evaluation` | — | 0 | 0 | 9 |  |
| `evaluationprof` | — | 2 | — | — | 0 | 0 | 2 | pas de clé primaire |
| `evaluations_communes` | — | 22 | `id` | — | 2 | 0 | 2 |  |
| `evaluation_commune_accuses` | — | 8 | `id` | — | 1 | 0 | 3 |  |
| `evaluation_commune_caps` | — | 5 | `id_evaluation,id_cap` | — | 0 | 0 | 2 |  |
| `failed_jobs` | — | 7 | `id` | — | 2 | 0 | 0 | table technique Laravel |
| `filieres` | Filiere | 6 | `id_filiere` | — | 0 | 0 | 7 |  |
| `frais_scolaires` | FraisScolaire | 10 | `id` | — | 2 | 0 | 3 |  |
| `indiscipline` | — | 6 | — | — | 0 | 0 | 1 | pas de clé primaire |
| `inscription` | — | 4 | `id_inscription` | id_eleve→eleve | 1 | 0 | 7 |  |
| `jobs` | — | 7 | `id` | — | 1 | 0 | 0 | table technique Laravel |
| `job_batches` | — | 10 | `id` | — | 0 | 0 | 0 | table technique Laravel |
| `kalanbot_actions_log` | KalanbotActionLog | 10 | `id` | user_id→utilisateurs | 2 | 0 | 3 |  |
| `lecons_presence` | LeconPresence | 5 | `id_lecon_presence` | — | 1 | 0 | 3 |  |
| `ligneclasse` | LigneClasse | 7 | `id_ligneclasse` | id_classe→classe, id_matiere→matiere | 2 | 309 | 8 |  |
| `ligneparents_eleves` | — | 5 | `id_ligneParent_eleve` | id_eleve→eleve, id_parent→parents | 2 | 0 | 5 |  |
| `ligne_evaluation` | LigneEvaluation | 14 | `id_ligneEvaluation` | — | 8 | 0 | 9 |  |
| `ligne_inscription` | — | 8 | `id_inscription` | id_annee→anneescolaire, id_classe→classe, id_planification→planification | 3 | 68 | 9 |  |
| `ligne_paiement_eleve` | LignePaiementEleve | 7 | `idligne_paiement_eleve` | — | 5 | 0 | 3 |  |
| `ligne_reinscription` | — | 5 | `id_ligne_reinscription` | — | 1 | 0 | 2 |  |
| `ligne_salaire` | LigneSalaire | 4 | `id_ligne_paiement` | — | 1 | 0 | 3 |  |
| `maintenance_mode` | MaintenanceMode | 7 | `id` | — | 0 | 1 | 4 |  |
| `matiere` | Matiere | 6 | `id_matiere` | — | 1 | 181 | 41 |  |
| `matiere_ordre` | MatiereOrdre | 3 | `id` | — | 1 | 231 | 5 |  |
| `migrations` | — | 3 | `id` | — | 0 | 78 | 0 | table technique Laravel |
| `moyenne_eleve` | — | 9 | `id_moyenne` | — | 4 | 0 | 1 |  |
| `note` | Note | 5 | `id_note` | — | 0 | 11 | 19 |  |
| `note_classe_choix` | — | 11 | `id` | — | 1 | 0 | 2 |  |
| `notifications` | — | 6 | `id` | — | 0 | 0 | 3 | table technique Laravel |
| `paiement` | Paiement | 25 | `id_paiement` | — | 2 | 0 | 8 |  |
| `paiement_sequences` | PaiementSequence | 6 | `id` | — | 1 | 0 | 3 |  |
| `parents` | ParentModel | 7 | `id_parent` | — | 1 | 0 | 32 |  |
| `pays` | Pays | 20 | `id` | — | 1 | 197 | 8 |  |
| `permissions` | Permission | 2 | `id` | — | 0 | 137 | 12 |  |
| `personal_access_tokens` | — | 10 | `id` | — | 3 | 46 | 2 | table technique Laravel |
| `planification` | Planification | 7 | `id_planification` | — | 0 | 21 | 8 |  |
| `planification_tranches` | PlanificationTranche | 6 | `id` | — | 2 | 0 | 3 |  |
| `plans_paiement` | PlanPaiement | 15 | `id` | — | 2 | 0 | 3 |  |
| `presences` | Presence | 9 | `id_presence` | id_enseignant→enseignants, id_classe→classe, id_trimestre→trimestre, id_anneeScolaire→anneescolaire | 4 | 0 | 14 |  |
| `programmes_officiels` | ProgrammeOfficiel | 4 | `id_programme` | — | 1 | 5 | 2 |  |
| `programme_classes` | ProgrammeClasse | 5 | `id_programme_classe` | — | 3 | 50 | 5 |  |
| `programme_lecons` | ProgrammeLecon | 4 | `id_lecon` | — | 1 | 679 | 5 |  |
| `reduction_paiement_configs` | ReductionPaiementConfig | 10 | `id` | — | 1 | 0 | 3 |  |
| `reinscription` | — | 7 | `id_reinscription` | — | 0 | 0 | 2 |  |
| `resultats_def_terminal` | — | 9 | `id` | — | 2 | 0 | 4 |  |
| `retrait` | Retrait | 9 | `id_retrait` | — | 3 | 0 | 4 |  |
| `revendeurs` | Revendeur | 7 | `id` | — | 0 | 1 | 6 |  |
| `revendeur_offres` | RevendeurOffre | 7 | `id` | id_offre→abonnement_offres, id_revendeur→revendeurs | 2 | 1 | 2 |  |
| `roles` | — | 2 | — | — | 0 | 1 | 0 | pas de clé primaire ; non utilisée par le code |
| `role_permission` | — | 2 | — | — | 0 | 0 | 0 | pas de clé primaire ; non utilisée par le code |
| `salaire` | Salaire | 9 | `id_salaire` | — | 0 | 0 | 8 |  |
| `series_matieres` | — | 6 | `id` | — | 1 | 0 | 2 |  |
| `sessions` | — | 6 | `id` | — | 2 | 3 | 3 | table technique Laravel |
| `subvention_etat_eleve` | — | 7 | `id` | — | 2 | 0 | 3 |  |
| `transfert` | — | 11 | `id_transfert` | id_ecole→ecole, id_eleve→eleve | 2 | 0 | 3 |  |
| `trimestre` | Trimestre | 3 | `id_trimestre` | — | 1 | 3 | 22 |  |
| `user_permission` | — | 2 | — | permission_id→permissions, user_id→utilisateurs | 2 | 1241 | 4 | pas de clé primaire |
| `user_role` | — | 2 | — | — | 0 | 0 | 1 | pas de clé primaire |
| `utilisateurs` | User | 28 | `idUtilisateur` | id_academie→academie, id_cap→cap | 5 | 14 | 19 |  |
| `versement` | Versement | 7 | `id_versement` | — | 3 | 0 | 3 |  |
