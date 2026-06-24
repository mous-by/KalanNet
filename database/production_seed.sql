
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `abonnement_offres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abonnement_offres` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `nom` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `montant` decimal(12,2) NOT NULL,
  `devise` varchar(8) NOT NULL DEFAULT 'XOF',
  `duree_jours` int(10) unsigned NOT NULL DEFAULT 30,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abonnement_offres_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `abonnement_paiements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abonnement_paiements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `abonnement_id` bigint(20) unsigned DEFAULT NULL,
  `ecole_id` bigint(20) unsigned NOT NULL,
  `offre_id` bigint(20) unsigned NOT NULL,
  `fournisseur` varchar(40) NOT NULL,
  `reference` varchar(80) NOT NULL,
  `reference_fournisseur` varchar(120) DEFAULT NULL,
  `numero_payeur` varchar(40) DEFAULT NULL,
  `montant` decimal(12,2) NOT NULL,
  `devise` varchar(8) NOT NULL DEFAULT 'XOF',
  `statut` varchar(30) NOT NULL DEFAULT 'en_attente',
  `checkout_url` varchar(500) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `paye_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `mode_paiement` varchar(30) NOT NULL DEFAULT 'MANUEL',
  `transaction_ref` varchar(120) DEFAULT NULL,
  `owner_note` text DEFAULT NULL,
  `preuve_url` varchar(255) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abonnement_paiements_reference_unique` (`reference`),
  KEY `abonnement_paiements_abonnement_id_foreign` (`abonnement_id`),
  KEY `abonnement_paiements_offre_id_foreign` (`offre_id`),
  KEY `abonnement_paiements_ecole_id_statut_index` (`ecole_id`,`statut`),
  KEY `abonnement_paiements_fournisseur_statut_index` (`fournisseur`,`statut`),
  KEY `abonnement_paiements_reference_fournisseur_index` (`reference_fournisseur`),
  CONSTRAINT `abonnement_paiements_abonnement_id_foreign` FOREIGN KEY (`abonnement_id`) REFERENCES `abonnements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `abonnement_paiements_offre_id_foreign` FOREIGN KEY (`offre_id`) REFERENCES `abonnement_offres` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `abonnements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abonnements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ecole_id` bigint(20) unsigned NOT NULL,
  `offre_id` bigint(20) unsigned NOT NULL,
  `statut` varchar(30) NOT NULL DEFAULT 'en_attente',
  `debut_at` timestamp NULL DEFAULT NULL,
  `fin_at` timestamp NULL DEFAULT NULL,
  `dernier_paiement_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `abonnements_offre_id_foreign` (`offre_id`),
  KEY `abonnements_ecole_id_statut_fin_at_index` (`ecole_id`,`statut`,`fin_at`),
  CONSTRAINT `abonnements_offre_id_foreign` FOREIGN KEY (`offre_id`) REFERENCES `abonnement_offres` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academie` (
  `id_academie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_academie` varchar(100) NOT NULL,
  `code_academie` varchar(20) NOT NULL,
  `localite_academie` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_academie`),
  UNIQUE KEY `code_academie` (`code_academie`),
  KEY `idx_academie_nom` (`nom_academie`),
  KEY `idx_academie_code` (`code_academie`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `anneescolaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anneescolaire` (
  `id_anneeScolaire` int(11) NOT NULL AUTO_INCREMENT,
  `annee` varchar(50) DEFAULT NULL,
  `date_debut` varchar(50) DEFAULT NULL,
  `date_fin` varchar(50) DEFAULT NULL,
  `id_ecole` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_anneeScolaire`),
  KEY `id_ecole` (`id_ecole`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annonces_academie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annonces_academie` (
  `id_annonce` int(11) NOT NULL AUTO_INCREMENT,
  `id_academie` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `fichier_joint` varchar(255) DEFAULT NULL,
  `type_fichier` varchar(50) DEFAULT NULL,
  `taille_fichier` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `statut_annonce` enum('active','archived') DEFAULT 'active',
  `type_annonce` enum('information','urgence','pedagogique','administrative') DEFAULT 'information',
  `public_cible` enum('tous','cap_only','ecoles_only') DEFAULT 'tous',
  PRIMARY KEY (`id_annonce`),
  KEY `id_academie` (`id_academie`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `idx_annonces_academie_date` (`date_publication`),
  CONSTRAINT `annonces_academie_ibfk_1` FOREIGN KEY (`id_academie`) REFERENCES `academie` (`id_academie`),
  CONSTRAINT `annonces_academie_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annonces_admin_gestionnaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annonces_admin_gestionnaire` (
  `id_annonce` int(11) NOT NULL AUTO_INCREMENT,
  `id_ecole` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `public_cible` varchar(50) DEFAULT 'tous',
  `id_utilisateur` int(11) NOT NULL,
  `fichier_joint` varchar(255) DEFAULT NULL,
  `type_fichier` varchar(100) DEFAULT NULL,
  `taille_fichier` int(11) DEFAULT NULL,
  `statut_annonce` varchar(30) NOT NULL DEFAULT 'publie',
  `date_publication` datetime DEFAULT NULL,
  PRIMARY KEY (`id_annonce`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_ecole` (`id_ecole`),
  CONSTRAINT `annonces_admin_gestionnaire_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`),
  CONSTRAINT `annonces_admin_gestionnaire_ibfk_2` FOREIGN KEY (`id_ecole`) REFERENCES `ecole` (`idEcole`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annonces_cap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annonces_cap` (
  `id_annonce` int(11) NOT NULL AUTO_INCREMENT,
  `id_cap` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `public_cible` varchar(50) DEFAULT 'tous',
  `fichier_joint` varchar(255) DEFAULT NULL,
  `type_fichier` varchar(50) DEFAULT NULL,
  `taille_fichier` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `statut_annonce` enum('active','archived') DEFAULT 'active',
  `type_annonce` enum('information','urgence','pedagogique') DEFAULT 'information',
  PRIMARY KEY (`id_annonce`),
  KEY `id_cap` (`id_cap`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `idx_annonces_cap_date` (`date_publication`),
  CONSTRAINT `annonces_cap_ibfk_1` FOREIGN KEY (`id_cap`) REFERENCES `cap` (`id_cap`),
  CONSTRAINT `annonces_cap_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annonces_fichiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annonces_fichiers` (
  `id_fichier` int(11) NOT NULL AUTO_INCREMENT,
  `id_annonce` int(11) NOT NULL,
  `type_annonce` enum('cap','academie','admin_gestionnaire') NOT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `nom_original` varchar(255) DEFAULT NULL,
  `type_mime` varchar(100) DEFAULT NULL,
  `taille` int(11) DEFAULT NULL,
  `date_ajout` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_fichier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annonces_lues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annonces_lues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_annonce` int(11) NOT NULL,
  `type_annonce` enum('CAP','ACADEMIE','admin_gestionnaire') DEFAULT NULL,
  `date_lecture` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_annonce_lue` (`id_utilisateur`,`id_annonce`,`type_annonce`),
  CONSTRAINT `annonces_lues_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(80) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `app_notifications_user_id_read_at_index` (`user_id`,`read_at`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banques` (
  `id_banques` int(11) NOT NULL AUTO_INCREMENT,
  `numero_compte` varchar(255) NOT NULL,
  `nom_banque` varchar(255) NOT NULL,
  `solde` double NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `date_creation` date NOT NULL,
  `updated_at` date DEFAULT NULL,
  PRIMARY KEY (`id_banques`),
  KEY `id_ecole` (`id_ecole`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bulletin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bulletin` (
  `id_bulletin` int(11) NOT NULL AUTO_INCREMENT,
  `trimestre` varchar(50) DEFAULT NULL,
  `annee_scolaire` date DEFAULT NULL,
  `id_note` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  PRIMARY KEY (`id_bulletin`),
  KEY `id_note` (`id_note`),
  KEY `id_eleve` (`id_eleve`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bulletin_publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bulletin_publications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_ecole` int(10) unsigned NOT NULL,
  `id_classe` int(10) unsigned NOT NULL,
  `id_annee` int(10) unsigned NOT NULL,
  `id_trimestre` int(10) unsigned DEFAULT NULL,
  `mois` tinyint(3) unsigned DEFAULT NULL,
  `published_by` int(10) unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bulletin_publications_unique_period` (`id_ecole`,`id_classe`,`id_annee`,`id_trimestre`,`mois`),
  KEY `bulletin_publications_id_ecole_id_classe_id_annee_index` (`id_ecole`,`id_classe`,`id_annee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `caisse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caisse` (
  `id_caisse` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `created_at` date NOT NULL,
  `montant_initial` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_net` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_ecole` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `updated_at` date DEFAULT NULL,
  PRIMARY KEY (`id_caisse`),
  KEY `id_ecole` (`id_ecole`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cap` (
  `id_cap` int(11) NOT NULL AUTO_INCREMENT,
  `nom_cap` varchar(100) NOT NULL,
  `code_cap` varchar(20) NOT NULL,
  `localite_cap` varchar(100) NOT NULL,
  `id_academie` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cap`),
  UNIQUE KEY `code_cap` (`code_cap`),
  KEY `idx_cap_nom` (`nom_cap`),
  KEY `idx_cap_code` (`code_cap`),
  KEY `idx_cap_academie` (`id_academie`),
  CONSTRAINT `cap_ibfk_1` FOREIGN KEY (`id_academie`) REFERENCES `academie` (`id_academie`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `classe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classe` (
  `id_classe` int(11) NOT NULL AUTO_INCREMENT,
  `nom_classe` varchar(50) DEFAULT NULL,
  `ordreEnseignement` varchar(50) DEFAULT NULL,
  `idEcole` int(11) NOT NULL,
  `id_classe_officielle` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_classe`),
  KEY `idEcole` (`idEcole`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `classes_officielles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes_officielles` (
  `id_classe_officielle` int(11) NOT NULL AUTO_INCREMENT,
  `nom_classe_officielle` varchar(255) NOT NULL,
  `ordre_enseignement` enum('Fondamentale I','Fondamentale II','Secondaire Generale','Secondaire Technique et Professionnel') DEFAULT NULL,
  PRIMARY KEY (`id_classe_officielle`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conduite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conduite` (
  `id_conduite` int(11) NOT NULL AUTO_INCREMENT,
  `id_annee_scolaire` int(11) DEFAULT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_trimestre` int(11) DEFAULT NULL,
  `id_eleve` int(11) DEFAULT NULL,
  `note_conduite` float DEFAULT NULL,
  PRIMARY KEY (`id_conduite`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `controle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `controle` (
  `id_controle` int(11) NOT NULL AUTO_INCREMENT,
  `date_controle` datetime DEFAULT NULL,
  `alertControle` varchar(100) DEFAULT NULL,
  `type_controle` varchar(100) DEFAULT NULL,
  `id_emploi_du_temps` int(11) DEFAULT NULL,
  `id_eleve` int(11) DEFAULT NULL,
  `penalite_conduite` float DEFAULT 0,
  `id_ecole` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_controle`),
  KEY `id_emploi_du_temps` (`id_emploi_du_temps`),
  KEY `id_eleve` (`id_eleve`),
  KEY `id_ecole` (`id_ecole`),
  CONSTRAINT `controle_ibfk_2` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `controle_eleve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `controle_eleve` (
  `id_controle_eleve` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `date` date NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `notifier_parent` tinyint(1) DEFAULT 0,
  `id_controle` int(11) NOT NULL,
  `date_enregistrement` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_controle_eleve`)
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `decaissement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `decaissement` (
  `id_decaissement` int(11) NOT NULL AUTO_INCREMENT,
  `montant_decaissement` double NOT NULL,
  `date_decaissement` date NOT NULL,
  `motif_decaissement` varchar(255) NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `id_caisse` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  `valide` tinyint(1) DEFAULT 0,
  `validated_by` bigint(20) unsigned DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_decaissement`),
  KEY `id_annee_scolaire` (`id_annee_scolaire`),
  KEY `id_caisse` (`id_caisse`),
  CONSTRAINT `decaissement_ibfk_1` FOREIGN KEY (`id_annee_scolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  CONSTRAINT `decaissement_ibfk_2` FOREIGN KEY (`id_caisse`) REFERENCES `caisse` (`id_caisse`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `echeances_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `echeances_paiement` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_paiement_id` bigint(20) unsigned NOT NULL,
  `libelle` varchar(120) NOT NULL,
  `montant_prevu` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_limite` date NOT NULL,
  `statut` varchar(40) NOT NULL DEFAULT 'en_attente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `echeances_paiement_plan_paiement_id_date_limite_index` (`plan_paiement_id`,`date_limite`),
  CONSTRAINT `echeances_paiement_plan_paiement_id_foreign` FOREIGN KEY (`plan_paiement_id`) REFERENCES `plans_paiement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ecole` (
  `idEcole` int(10) NOT NULL AUTO_INCREMENT,
  `nomEcole` varchar(100) NOT NULL,
  `typeEcole` varchar(50) NOT NULL,
  `logoEcole` varchar(150) DEFAULT NULL,
  `nomFondamental` varchar(255) DEFAULT NULL,
  `nomLycee` varchar(255) DEFAULT NULL,
  `nomProfessionnel` varchar(255) DEFAULT NULL,
  `id_academie` int(11) DEFAULT NULL,
  `id_cap` int(11) DEFAULT NULL,
  `nomComplexe` varchar(255) DEFAULT NULL,
  `cap` varchar(255) DEFAULT NULL,
  `statut` enum('public','prive') DEFAULT 'public',
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `academie` varchar(255) NOT NULL,
  `notification_sms` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = accepte notifications SMS, 0 = refuse',
  `notification_email` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idEcole`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eleve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eleve` (
  `id_eleve` int(11) NOT NULL AUTO_INCREMENT,
  `date_naissance` varchar(100) DEFAULT NULL,
  `lieu_naiss` varchar(255) NOT NULL,
  `adresse_eleve` varchar(50) DEFAULT NULL,
  `genre_eleve` varchar(255) NOT NULL,
  `id_annee` int(11) NOT NULL,
  `date_inscription` date NOT NULL,
  `image` varchar(255) NOT NULL,
  `matricule` varchar(50) DEFAULT NULL,
  `id_classe` int(11) NOT NULL,
  `cas_social` varchar(255) NOT NULL,
  `mode_paiement` varchar(255) DEFAULT NULL,
  `statut_paiement` varchar(40) NOT NULL DEFAULT 'normal',
  `id_ecole` int(11) DEFAULT NULL,
  `nom_eleve` varchar(100) NOT NULL,
  `prenom_eleve` varchar(100) NOT NULL,
  `etat_dossier` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_eleve`),
  KEY `anness` (`id_annee`),
  KEY `eleve_ibfk_1` (`id_ecole`),
  CONSTRAINT `anness` FOREIGN KEY (`id_annee`) REFERENCES `anneescolaire` (`id_anneeScolaire`)
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emargement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emargement` (
  `id_emargement` int(11) NOT NULL AUTO_INCREMENT,
  `id_enseignant` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `chapitre` varchar(255) DEFAULT NULL,
  `id_lecon` int(255) NOT NULL,
  `nombre_heure` varchar(100) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `id_anneeScolaire` int(11) NOT NULL,
  `date_emargement` datetime DEFAULT current_timestamp(),
  `id_ecole` int(11) DEFAULT NULL,
  `valide` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_emargement`),
  KEY `emargement_ibfk_3` (`id_anneeScolaire`),
  KEY `emargement_ibfk_4` (`id_trimestre`),
  KEY `emargement_ibfk_5` (`id_matiere`),
  KEY `emargement_ibfk_6` (`id_ecole`),
  CONSTRAINT `emargement_ibfk_3` FOREIGN KEY (`id_anneeScolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  CONSTRAINT `emargement_ibfk_4` FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id_trimestre`),
  CONSTRAINT `emargement_ibfk_5` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emploi_du_temps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emploi_du_temps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `jour` varchar(15) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `annee_scolaire_ibfk_4` (`id_annee_scolaire`),
  KEY `emploi_du_temps_ibfk_2` (`id_matiere`),
  KEY `fk_enseignant` (`id_enseignant`),
  CONSTRAINT `annee_scolaire_ibfk_4` FOREIGN KEY (`id_annee_scolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  CONSTRAINT `emploi_du_temps_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`),
  CONSTRAINT `fk_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `encaissement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encaissement` (
  `id_encaissement` int(11) NOT NULL AUTO_INCREMENT,
  `paiement_id` bigint(20) unsigned DEFAULT NULL,
  `type_operation` varchar(255) DEFAULT NULL,
  `date_encaissement` date DEFAULT NULL,
  `motif_encaissement` varchar(255) DEFAULT NULL,
  `montant_encaissement` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` varchar(40) NOT NULL DEFAULT 'valide',
  `id_annee_scolaire` int(11) NOT NULL,
  `id_caisse` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  PRIMARY KEY (`id_encaissement`),
  KEY `id_caisse` (`id_caisse`),
  KEY `idUtilisateur` (`idUtilisateur`),
  KEY `id_annee_scolaire` (`id_annee_scolaire`),
  KEY `encaissement_paiement_id_index` (`paiement_id`),
  CONSTRAINT `encaissement_ibfk_1` FOREIGN KEY (`id_caisse`) REFERENCES `caisse` (`id_caisse`),
  CONSTRAINT `encaissement_ibfk_2` FOREIGN KEY (`idUtilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`),
  CONSTRAINT `encaissement_ibfk_3` FOREIGN KEY (`id_annee_scolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enseignants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enseignants` (
  `id_enseignant` int(11) NOT NULL AUTO_INCREMENT,
  `nom_prenom_enseignant` varchar(200) NOT NULL,
  `genre_enseignant` varchar(50) NOT NULL,
  `email_enseignant` varchar(255) NOT NULL,
  `telephone_enseignant` varchar(100) NOT NULL,
  `date_naissance_enseignant` varchar(100) NOT NULL,
  `lieu_naissance_enseignant` varchar(100) NOT NULL,
  `diplome_enseignant` varchar(100) NOT NULL,
  `salaire_enseignant` int(100) DEFAULT NULL,
  `salaire_mois_mode` tinyint(3) unsigned NOT NULL DEFAULT 12,
  `type_contrat_enseignant` varchar(50) DEFAULT NULL,
  `matricule` varchar(100) NOT NULL,
  `avatar_enseignant` varchar(255) DEFAULT NULL,
  `id_emploi_du_temps` int(11) DEFAULT NULL,
  `duree_contrat` varchar(100) DEFAULT NULL,
  `nombre_heure` varchar(100) DEFAULT NULL,
  `prix_heure` int(11) DEFAULT NULL,
  `pwd` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `id_ecole` int(11) DEFAULT NULL,
  `statut_matrimonial` varchar(50) DEFAULT NULL,
  `nombre_enfants` int(11) DEFAULT 0,
  `pere_nom_prenom` varchar(255) DEFAULT NULL,
  `mere_nom_prenom` varchar(255) DEFAULT NULL,
  `specialite` varchar(255) DEFAULT NULL,
  `service_employeur` varchar(255) DEFAULT NULL,
  `anciennete_annees` int(11) DEFAULT 0,
  PRIMARY KEY (`id_enseignant`),
  KEY `enseignants_ibfk_1` (`id_ecole`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation` (
  `id_evaluation` int(11) NOT NULL AUTO_INCREMENT,
  `libeller` varchar(255) NOT NULL,
  `date_evaluation` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_evaluation`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluationprof`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluationprof` (
  `id_evaluation` int(11) NOT NULL,
  `id_enseignant` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `frais_scolaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frais_scolaires` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ecole_id` bigint(20) unsigned NOT NULL,
  `classe_id` bigint(20) unsigned DEFAULT NULL,
  `annee_scolaire_id` bigint(20) unsigned NOT NULL,
  `type_frais` varchar(80) NOT NULL,
  `montant` decimal(12,2) NOT NULL DEFAULT 0.00,
  `obligatoire` tinyint(1) NOT NULL DEFAULT 1,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `frais_scolaires_unique_scope` (`ecole_id`,`classe_id`,`annee_scolaire_id`,`type_frais`),
  KEY `frais_scolaires_ecole_id_annee_scolaire_id_actif_index` (`ecole_id`,`annee_scolaire_id`,`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indiscipline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indiscipline` (
  `id_indiscipline` int(11) NOT NULL,
  `date_indiscipline` datetime DEFAULT NULL,
  `type_indiscipline` varchar(50) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `sanction` varchar(50) DEFAULT NULL,
  `id_eleve` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inscription` (
  `id_inscription` int(11) NOT NULL AUTO_INCREMENT,
  `date_inscription` datetime DEFAULT NULL,
  `id_anneeScolaire` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  PRIMARY KEY (`id_inscription`),
  KEY `inscription_ibfk_2` (`id_eleve`),
  CONSTRAINT `inscription_ibfk_2` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lecons_presence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lecons_presence` (
  `id_lecon_presence` int(11) NOT NULL AUTO_INCREMENT,
  `id_presence` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `nombre_heure` decimal(4,2) NOT NULL,
  `progression` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_lecon_presence`),
  KEY `id_presence` (`id_presence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ligne_evaluation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ligne_evaluation` (
  `id_ligneEvaluation` int(11) NOT NULL AUTO_INCREMENT,
  `id_evaluation` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `id_trimestre` int(11) DEFAULT NULL,
  `id_note` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `note` double DEFAULT NULL,
  `validation_status` varchar(30) NOT NULL DEFAULT 'valide',
  `validated_by` int(10) unsigned DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `id_enseignant` int(11) NOT NULL,
  `mois` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_ligneEvaluation`),
  KEY `ligne_evaluation_ibfk_1` (`id_annee_scolaire`),
  KEY `ligne_evaluation_ibfk_2` (`id_classe`),
  KEY `ligne_evaluation_ibfk_4` (`id_note`),
  KEY `ligne_evaluation_ibfk_5` (`id_trimestre`),
  KEY `ligne_evaluation_ibfk_6` (`id_eleve`),
  KEY `ligne_evaluation_ibfk_7` (`id_enseignant`),
  KEY `ligne_evaluation_ibfk_8` (`id_matiere`),
  KEY `ligne_evaluation_ibfk_9` (`id_evaluation`)
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ligne_inscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ligne_inscription` (
  `id_inscription` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) NOT NULL,
  `id_planification` int(11) DEFAULT NULL,
  `date_inscription` date DEFAULT curdate(),
  PRIMARY KEY (`id_inscription`),
  KEY `id_annee` (`id_annee`),
  KEY `id_classe` (`id_classe`),
  KEY `id_planification` (`id_planification`),
  CONSTRAINT `ligne_inscription_ibfk_1` FOREIGN KEY (`id_annee`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  CONSTRAINT `ligne_inscription_ibfk_2` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`),
  CONSTRAINT `ligne_inscription_ibfk_3` FOREIGN KEY (`id_planification`) REFERENCES `planification` (`id_planification`)
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ligne_paiement_eleve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ligne_paiement_eleve` (
  `idligne_paiement_eleve` int(11) NOT NULL AUTO_INCREMENT,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) NOT NULL,
  `id_paiement` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `idEcole` int(11) NOT NULL,
  PRIMARY KEY (`idligne_paiement_eleve`),
  KEY `idEcole` (`idEcole`),
  KEY `id_annee` (`id_annee`),
  KEY `id_classe` (`id_classe`),
  KEY `id_paiement` (`id_paiement`),
  KEY `id_trimestre` (`id_trimestre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ligne_reinscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ligne_reinscription` (
  `id_ligne_reinscription` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) DEFAULT NULL,
  `id_reinscription` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_ligne_reinscription`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ligne_salaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ligne_salaire` (
  `id_ligne_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `id_salaire` int(11) NOT NULL,
  `montant_verse` int(11) NOT NULL,
  `date_paiement` date NOT NULL,
  PRIMARY KEY (`id_ligne_paiement`),
  KEY `ligne_salaire_ibfk_1` (`id_salaire`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ligneclasse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ligneclasse` (
  `id_ligneclasse` int(11) NOT NULL AUTO_INCREMENT,
  `id_matiere` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_enseignants` int(11) DEFAULT NULL,
  `coefficient` decimal(4,2) NOT NULL,
  PRIMARY KEY (`id_ligneclasse`),
  KEY `ligneclasse_ibfk_1` (`id_classe`),
  KEY `ligneclasse_ibfk_2` (`id_matiere`),
  CONSTRAINT `ligneclasse_ibfk_1` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`),
  CONSTRAINT `ligneclasse_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ligneparents_eleves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ligneparents_eleves` (
  `id_ligneParent_eleve` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleve` int(11) NOT NULL,
  `id_parent` int(11) NOT NULL,
  `informer` varchar(100) NOT NULL,
  `lien_parent` varchar(100) NOT NULL,
  PRIMARY KEY (`id_ligneParent_eleve`),
  KEY `id_eleve` (`id_eleve`),
  KEY `id_parent` (`id_parent`),
  CONSTRAINT `ligneparents_eleves_ibfk_1` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`),
  CONSTRAINT `ligneparents_eleves_ibfk_2` FOREIGN KEY (`id_parent`) REFERENCES `parents` (`id_parent`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `matiere`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matiere` (
  `id_matiere` int(11) NOT NULL AUTO_INCREMENT,
  `nom_matiere` varchar(50) DEFAULT NULL,
  `id_ecole` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_matiere`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `matiere_ordre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matiere_ordre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_matiere` int(11) NOT NULL,
  `ordre_enseignement` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moyenne_eleve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moyenne_eleve` (
  `id_moyenne` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_trimestre` int(11) DEFAULT NULL,
  `id_anneeScolaire` int(11) NOT NULL,
  `moyenne` decimal(5,2) NOT NULL DEFAULT 0.00,
  `rang` int(11) NOT NULL DEFAULT 0,
  `mois` varchar(100) DEFAULT NULL,
  `valide` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_moyenne`),
  KEY `moyenne_eleve_ibfk_1` (`id_eleve`),
  KEY `moyenne_eleve_ibfk_2` (`id_classe`),
  KEY `moyenne_eleve_ibfk_3` (`id_trimestre`),
  KEY `moyenne_eleve_ibfk_4` (`id_anneeScolaire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note` (
  `id_note` int(11) NOT NULL AUTO_INCREMENT,
  `typeNote` varchar(100) DEFAULT NULL,
  `codeNote` varchar(100) DEFAULT NULL,
  `valeur` varchar(100) NOT NULL,
  `id_ecole` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_note`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ecole` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `statut` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paiement` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `montant` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_paye` decimal(12,2) DEFAULT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `mode_reglement` varchar(40) NOT NULL DEFAULT 'especes',
  `statut` varchar(40) NOT NULL DEFAULT 'valide',
  `annule_at` timestamp NULL DEFAULT NULL,
  `annule_par` bigint(20) unsigned DEFAULT NULL,
  `motif_annulation` varchar(255) DEFAULT NULL,
  `motif` varchar(50) DEFAULT NULL,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `idEcole` int(11) NOT NULL,
  `id_eleve` int(11) DEFAULT NULL,
  `echeance_id` bigint(20) unsigned DEFAULT NULL,
  `encaissement_id` bigint(20) unsigned DEFAULT NULL,
  `parent` varchar(50) DEFAULT NULL,
  `nom_payeur` varchar(100) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `id_caisse` int(11) DEFAULT NULL,
  `numero_recu` int(11) DEFAULT NULL,
  `id_planification` int(11) NOT NULL,
  PRIMARY KEY (`id_paiement`),
  UNIQUE KEY `paiement_reference_unique` (`reference`),
  UNIQUE KEY `paiement_ecole_numero_recu_unique` (`idEcole`,`numero_recu`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paiement_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paiement_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ecole_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `dernier_numero` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paiement_sequences_ecole_id_type_unique` (`ecole_id`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parents` (
  `id_parent` int(11) NOT NULL AUTO_INCREMENT,
  `nom_prenom_parent` varchar(255) DEFAULT NULL,
  `email_parent` varchar(50) DEFAULT NULL,
  `telephone_parent` varchar(50) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `idEcole` int(11) DEFAULT NULL,
  `pwd` varchar(255) NOT NULL,
  PRIMARY KEY (`id_parent`),
  KEY `parents_ibfk_1` (`idEcole`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=401 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planification` (
  `id_planification` int(11) NOT NULL AUTO_INCREMENT,
  `motif` varchar(255) NOT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_annee` int(11) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `montant_planification` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_planification`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plans_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans_paiement` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint(20) unsigned NOT NULL,
  `ecole_id` bigint(20) unsigned NOT NULL,
  `classe_id` bigint(20) unsigned NOT NULL,
  `annee_scolaire_id` bigint(20) unsigned NOT NULL,
  `mode_paiement` varchar(40) NOT NULL,
  `statut_paiement` varchar(40) NOT NULL DEFAULT 'normal',
  `montant_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_final` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payeur_type` varchar(40) NOT NULL DEFAULT 'parent',
  `payeur_libelle` varchar(120) DEFAULT NULL,
  `details_frais` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_frais`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_paiement_eleve_annee_unique` (`eleve_id`,`annee_scolaire_id`),
  KEY `plans_paiement_ecole_id_annee_scolaire_id_classe_id_index` (`ecole_id`,`annee_scolaire_id`,`classe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presences` (
  `id_presence` int(11) NOT NULL AUTO_INCREMENT,
  `id_enseignant` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `date_presence` datetime NOT NULL,
  `nombre_heure` decimal(5,2) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `id_anneeScolaire` int(11) NOT NULL,
  `id_ecole` int(11) DEFAULT NULL,
  `valide` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_presence`),
  KEY `id_enseignant` (`id_enseignant`),
  KEY `id_classe` (`id_classe`),
  KEY `id_trimestre` (`id_trimestre`),
  KEY `id_anneeScolaire` (`id_anneeScolaire`),
  CONSTRAINT `presences_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`),
  CONSTRAINT `presences_ibfk_2` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`),
  CONSTRAINT `presences_ibfk_3` FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id_trimestre`),
  CONSTRAINT `presences_ibfk_4` FOREIGN KEY (`id_anneeScolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `programme_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programme_classes` (
  `id_programme_classe` int(11) NOT NULL AUTO_INCREMENT,
  `id_programme` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `pour_toutes_ecoles` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_programme_classe`),
  KEY `id_programme` (`id_programme`),
  KEY `id_matiere` (`id_matiere`),
  KEY `programme_classes_ibfk_2` (`id_classe`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `programme_lecons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programme_lecons` (
  `id_lecon` int(11) NOT NULL AUTO_INCREMENT,
  `id_programme_classe` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  PRIMARY KEY (`id_lecon`),
  KEY `id_programme_classe` (`id_programme_classe`)
) ENGINE=InnoDB AUTO_INCREMENT=770 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `programmes_officiels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programmes_officiels` (
  `id_programme` int(11) NOT NULL AUTO_INCREMENT,
  `date_creation` datetime NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `officiel` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_programme`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reduction_paiement_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reduction_paiement_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ecole_id` bigint(20) unsigned NOT NULL,
  `annee_scolaire_id` bigint(20) unsigned DEFAULT NULL,
  `statut_paiement` varchar(40) NOT NULL,
  `type_reduction` varchar(40) NOT NULL DEFAULT 'aucune',
  `valeur` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payeur_libelle` varchar(120) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reduction_configs_unique_scope` (`ecole_id`,`annee_scolaire_id`,`statut_paiement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reinscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reinscription` (
  `id_reinscription` int(11) NOT NULL AUTO_INCREMENT,
  `statut` varchar(20) NOT NULL,
  `statut_propose` varchar(20) DEFAULT NULL,
  `date_reinscription` date NOT NULL,
  `enrolement` tinyint(1) DEFAULT 0,
  `moyenneGeneral` decimal(5,2) DEFAULT NULL,
  `motif_decision` text DEFAULT NULL,
  PRIMARY KEY (`id_reinscription`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resultats_def_terminal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resultats_def_terminal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleve` int(11) DEFAULT NULL,
  `id_annee` int(11) DEFAULT NULL,
  `niveau_examen` enum('DEF','BAC') DEFAULT NULL,
  `decision` enum('admis','échec') DEFAULT NULL,
  `moyenne` float(5,2) DEFAULT NULL,
  `observation` varchar(255) DEFAULT NULL,
  `date_resultat` date DEFAULT NULL,
  `id_classe` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_eleve` (`id_eleve`),
  KEY `id_classe` (`id_classe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retrait`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `retrait` (
  `id_retrait` int(11) NOT NULL AUTO_INCREMENT,
  `id_banque` int(11) NOT NULL,
  `date_retrait` date NOT NULL,
  `montant_retrait` decimal(15,2) NOT NULL,
  `motif_retrait` text DEFAULT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `valide` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_retrait`),
  KEY `id_banque` (`id_banque`),
  KEY `id_annee_scolaire` (`id_annee_scolaire`),
  KEY `idUtilisateur` (`idUtilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permission` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salaire` (
  `id_salaire` int(11) NOT NULL AUTO_INCREMENT,
  `type_paiement` varchar(50) DEFAULT NULL,
  `reference` varchar(100) NOT NULL,
  `montant_a_payer` int(11) NOT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `id_inscription` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `mois` varchar(255) DEFAULT NULL,
  `annee` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_salaire`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transfert`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfert` (
  `id_transfert` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleve` int(11) NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `travail` varchar(255) DEFAULT NULL,
  `conduite` varchar(255) NOT NULL,
  `date_transfert` timestamp NULL DEFAULT NULL,
  `date_retour` timestamp NULL DEFAULT NULL,
  `motif_retour` varchar(255) DEFAULT NULL,
  `retour_effectue_par` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_transfert`),
  KEY `id_ecole` (`id_ecole`),
  KEY `id_eleve` (`id_eleve`),
  CONSTRAINT `transfert_ibfk_1` FOREIGN KEY (`id_ecole`) REFERENCES `ecole` (`idEcole`),
  CONSTRAINT `transfert_ibfk_2` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trimestre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trimestre` (
  `id_trimestre` int(11) NOT NULL AUTO_INCREMENT,
  `nom_trimestre` varchar(255) NOT NULL,
  `id_ecole` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_trimestre`),
  KEY `trimestre_ibfk_1` (`id_ecole`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permission` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  KEY `permission_id` (`permission_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`),
  CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`idUtilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateurs` (
  `idUtilisateur` int(10) NOT NULL AUTO_INCREMENT,
  `nomPrenom` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `pwd` varchar(200) DEFAULT NULL,
  `fonction` varchar(50) DEFAULT NULL,
  `telephone` int(10) DEFAULT NULL,
  `genre` varchar(20) DEFAULT NULL,
  `droit` varchar(100) DEFAULT NULL,
  `idEcole` int(11) DEFAULT NULL,
  `managed_orders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`managed_orders`)),
  `id_academie` int(11) DEFAULT NULL,
  `id_cap` int(11) DEFAULT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `id_parent` int(11) DEFAULT NULL,
  `id_role` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `statut` int(11) NOT NULL DEFAULT 1,
  `derniere_connexion` datetime DEFAULT NULL,
  `theme_preference` varchar(255) NOT NULL DEFAULT 'bleu-sombre',
  `locale_preference` varchar(5) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idUtilisateur`),
  KEY `id_academie` (`id_academie`),
  KEY `id_cap` (`id_cap`),
  CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_academie`) REFERENCES `academie` (`id_academie`) ON DELETE SET NULL,
  CONSTRAINT `utilisateurs_ibfk_2` FOREIGN KEY (`id_cap`) REFERENCES `cap` (`id_cap`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `versement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `versement` (
  `id_versement` int(11) NOT NULL AUTO_INCREMENT,
  `date_versement` date NOT NULL,
  `motif_versement` varchar(255) NOT NULL,
  `montant_versement` double NOT NULL,
  `id_banque` int(11) NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  PRIMARY KEY (`id_versement`),
  KEY `idUtilisateur` (`idUtilisateur`),
  KEY `id_annee_scolaire` (`id_annee_scolaire`),
  KEY `id_banque` (`id_banque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- ============================================================
-- DONNÉES GLOBALES DE PRODUCTION
-- ============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `academie` WRITE;
/*!40000 ALTER TABLE `academie` DISABLE KEYS */;
INSERT INTO `academie` (`id_academie`, `nom_academie`, `code_academie`, `localite_academie`, `created_at`, `updated_at`) VALUES (1,'ACADEMIE DE KAYES','AE-KAYES','KAYES','2025-11-05 17:35:43','2025-11-05 17:35:43'),(4,'ACADEMIE DE BAMAKO RIVE GAUCHE','AE-BKO-RG','BAMAKO','2025-11-05 17:47:01','2025-11-05 17:47:01'),(6,'ACADÉMIE DE BAMAKO RIVE DROITE','AE-BKO-RD','BAMAKO','2025-11-05 18:01:10','2025-11-05 18:01:10'),(7,'ACADÉMIE DE NIORO','AE-NIORO','NIORO','2025-11-06 16:38:46','2025-11-06 16:38:46'),(8,'ACADEMIE DE SEGOU','AE-SEGOU','SEGOU','2025-11-07 11:24:44','2025-11-07 11:24:44');
/*!40000 ALTER TABLE `academie` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `cap` WRITE;
/*!40000 ALTER TABLE `cap` DISABLE KEYS */;
INSERT INTO `cap` (`id_cap`, `nom_cap`, `code_cap`, `localite_cap`, `id_academie`, `created_at`, `updated_at`) VALUES (1,'CAP KAYES RIVE GAUCHE','CAP-KRG','KAYES',1,'2026-01-12 15:32:06','2026-01-12 15:32:06'),(2,'CAP KAYES RIVE DROITE','CAP-KRD','KAYES',1,'2026-01-12 15:32:33','2026-01-12 15:32:33'),(3,'CAP DE SEBENIKORO','CAP-SEBE','BAMAKO',4,'2026-01-12 15:32:49','2026-01-12 15:32:49'),(4,'CAP DE LAFIABOUGOU','CAP-LAFIA','BAMAKO',4,'2026-01-12 15:33:21','2026-01-12 15:33:21'),(5,'CAP DE BAFOULABE','CAP-BAF','KAYES',1,'2026-01-12 15:33:40','2026-01-12 15:33:40');
/*!40000 ALTER TABLE `cap` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `classes_officielles` WRITE;
/*!40000 ALTER TABLE `classes_officielles` DISABLE KEYS */;
INSERT INTO `classes_officielles` (`id_classe_officielle`, `nom_classe_officielle`, `ordre_enseignement`) VALUES (1,'1er année','Fondamentale I'),(2,'2eme année','Fondamentale I'),(3,'3eme année','Fondamentale I'),(4,'4eme année','Fondamentale I'),(5,'5eme année','Fondamentale I'),(6,'6eme année','Fondamentale I'),(7,'7eme année ','Fondamentale II'),(8,'8eme année ','Fondamentale II'),(9,'9eme année','Fondamentale II'),(10,'11eme année SES','Secondaire Generale'),(11,'10eme année commune','Secondaire Generale'),(12,'12eme année Experimental','Secondaire Generale'),(13,'10eme année technique','Secondaire Technique et Professionnel'),(14,'11eme année S','Secondaire Generale'),(15,'11eme année L','Secondaire Generale'),(16,'11eme année STI','Secondaire Technique et Professionnel'),(17,'11eme année STG','Secondaire Technique et Professionnel'),(18,'12eme année SS','Secondaire Generale'),(19,'12eme année S Exacte','Secondaire Generale'),(20,'12eme année S Economique','Secondaire Generale'),(21,'Génie mécanique (GM)','Secondaire Technique et Professionnel'),(22,'Génie Civil (GC)','Secondaire Technique et Professionnel'),(23,'Génie Minier (GMI)','Secondaire Technique et Professionnel'),(24,'Génie Electronique (GELN)','Secondaire Technique et Professionnel'),(25,'Génie Energétique (GEN)','Secondaire Technique et Professionnel'),(26,'Génie électrotechnique (GEL)','Secondaire Technique et Professionnel'),(27,'Comptabilité et Finances (CF)','Secondaire Technique et Professionnel'),(28,'Gestion et commerce (GCO).','Secondaire Technique et Professionnel'),(29,'12eme Terminales Arts /Lettres','Secondaire Generale'),(30,'12eme Terminales Langues/Lettres','Secondaire Generale');
/*!40000 ALTER TABLE `classes_officielles` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `matiere` WRITE;
/*!40000 ALTER TABLE `matiere` DISABLE KEYS */;
INSERT INTO `matiere` (`id_matiere`, `nom_matiere`, `id_ecole`) VALUES (1,'Sciences Naturelle',NULL),(2,'Mathématiques ',NULL),(3,'physique',NULL),(4,'Chimie',NULL),(6,'philosophie ',NULL),(7,'Français ',NULL),(8,'Rédaction ',NULL),(9,'Anglais',NULL),(10,'Musique',NULL),(11,'ECM',NULL),(12,'Histoire ',NULL),(13,'Géographie ',NULL),(14,'Lecture',NULL),(15,'Récitation ',NULL),(16,'Dictée et Question',NULL),(17,'Grammaire',NULL),(18,'LV2',NULL);
/*!40000 ALTER TABLE `matiere` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` (`id`, `name`) VALUES (1,'enseignants_apercu'),(2,'enseignants_creation'),(3,'enseignants_modification'),(4,'enseignants_archiver_ou_reactiver'),(5,'matieres_apercu'),(6,'matieres_creation'),(9,'classes_apercu'),(10,'classes_creation'),(11,'classes_modification'),(12,'classes_supprimer'),(13,'planning_apercu'),(14,'planning_creation'),(16,'bulletins_acces_bulletin'),(17,'inscriptions_inscrire'),(18,'inscriptions_reinscrire'),(19,'eleves_apercu'),(20,'eleves_modification'),(21,'eleves_supprimer'),(22,'eleves_dossier'),(23,'parents_apercu'),(24,'parents_creation'),(25,'parents_modification'),(26,'parents_supprimer'),(27,'evaluation_apercu'),(28,'evaluation_creation'),(29,'evaluation_modification'),(30,'evaluation_supprimer'),(31,'paiements_apercu'),(32,'paiements_faire'),(33,'paiements_annuler'),(35,'planning_imprimer'),(36,'controle_apercu'),(37,'controle_creation'),(38,'controle_modification'),(39,'emargement_apercu'),(40,'emargement_faire'),(42,'emargement_modification'),(43,'enseignants_emploi'),(51,'emargement_validation_admin'),(52,'caisses_apercu'),(53,'caisses_creation'),(54,'caisses_modification'),(55,'banques_apercu'),(56,'banques_creation'),(57,'banques_modification'),(58,'banques_supprimer'),(59,'encaissement_apercu'),(60,'encaissement_creation'),(61,'encaissement_modification'),(62,'encaissement_supprimer'),(63,'decaissements_apercu'),(64,'decaissements_creation'),(65,'decaissements_modification'),(66,'decaissements_supprimer'),(69,'decaissements_validation'),(70,'versements_apercu'),(71,'retraits_apercu'),(72,'retraits_creation'),(73,'retraits_modification'),(74,'retraits_supprimer'),(75,'versements_creation'),(76,'versements_supprimer'),(77,'versements_modification'),(79,'programmes_creation'),(80,'programmes_modification'),(82,'presence_apercu'),(83,'presence_creation'),(84,'presence_modification'),(85,'presence_supprimer'),(88,'classes_programme_officiel'),(90,'dcap_apercu'),(91,'dcap_voiraction'),(92,'dae_apercu'),(93,'dae_voiraction'),(94,'dae_permission'),(95,'dae_activer'),(97,'dcap_permission'),(99,'dcap_activer'),(103,'documents_apercu'),(104,'documents_manage'),(105,'inscriptions_apercu'),(108,'dossiers_eleves_apercu'),(110,'reinscriptions_apercu'),(114,'status_controles_apercu'),(115,'types_notes_apercu'),(116,'trimestres_apercu'),(122,'classes_officielles_apercu'),(124,'programmes_apercu'),(129,'academies_apercu'),(130,'utilisateurs_apercu'),(131,'profiles_apercu'),(132,'permissions_apercu'),(133,'annees_scolaires_apercu'),(371,'historique_paiement_apercu'),(372,'historique_paiement_export'),(373,'programmes_pdf'),(374,'programmes_supprimer'),(375,'dashboard_apercu'),(376,'emargement_supprimer'),(377,'matieres_modification'),(378,'matieres_supprimer'),(379,'planning_supprimer'),(380,'finances_planifications_apercu'),(381,'finances_planifications_creation'),(382,'finances_planifications_modification'),(383,'finances_planifications_supprimer'),(384,'ecoles_apercu'),(387,'abonnements_apercu'),(388,'abonnements_paiement'),(389,'abonnements_configuration'),(390,'abonnements_validation'),(391,'evaluation_validation_notes'),(392,'utilisateurs_supprimer'),(393,'annonces_apercu'),(394,'annonces_creation'),(395,'annonces_supprimer'),(396,'bulletins_publication'),(397,'utilisateurs_creation'),(398,'permissions_assigner'),(399,'permission_assigner'),(400,'permission_voir');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`) VALUES (1,'SuperAdmin');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `role_permission` WRITE;
/*!40000 ALTER TABLE `role_permission` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permission` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `anneescolaire` WRITE;
/*!40000 ALTER TABLE `anneescolaire` DISABLE KEYS */;
INSERT INTO `anneescolaire` (`id_anneeScolaire`, `annee`, `date_debut`, `date_fin`, `id_ecole`) VALUES (1,'2024-2025','2024-09-01','2025-08-31',NULL),(2,'2025-2026','2025-09-01','2026-08-31',NULL);
/*!40000 ALTER TABLE `anneescolaire` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `trimestre` WRITE;
/*!40000 ALTER TABLE `trimestre` DISABLE KEYS */;
INSERT INTO `trimestre` (`id_trimestre`, `nom_trimestre`, `id_ecole`) VALUES (1,'1 trimestre',NULL),(2,'2 trimestre',NULL),(3,'3 trimestre',NULL);
/*!40000 ALTER TABLE `trimestre` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `programmes_officiels` WRITE;
/*!40000 ALTER TABLE `programmes_officiels` DISABLE KEYS */;
INSERT INTO `programmes_officiels` (`id_programme`, `date_creation`, `id_utilisateur`, `officiel`) VALUES (1,'2025-07-07 20:13:52',1,1),(2,'2025-07-10 08:52:08',1,1),(3,'2025-07-10 09:11:11',1,1),(6,'2025-09-24 00:25:19',1,1),(7,'2026-05-24 17:32:31',9,1);
/*!40000 ALTER TABLE `programmes_officiels` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `programme_classes` WRITE;
/*!40000 ALTER TABLE `programme_classes` DISABLE KEYS */;
INSERT INTO `programme_classes` (`id_programme_classe`, `id_programme`, `id_classe`, `id_matiere`, `pour_toutes_ecoles`) VALUES (1,1,7,2,0),(2,1,7,3,0),(3,1,7,4,0),(4,1,7,1,0),(5,1,7,14,0),(6,1,7,8,0),(7,1,7,17,0),(8,1,7,13,0),(9,1,7,12,0),(10,2,11,7,0),(11,2,11,9,0),(12,2,11,3,0),(13,2,11,4,0),(14,2,11,1,0),(15,2,11,2,0),(16,2,11,12,0),(17,2,11,13,0),(18,3,8,14,0),(19,3,8,17,0),(20,3,8,11,0),(21,1,7,11,0),(24,6,9,9,0),(25,1,7,9,0),(26,3,8,9,0),(28,6,9,1,0),(29,3,8,1,0),(30,6,9,3,0),(31,6,9,4,0),(32,6,9,13,0),(33,6,9,12,0),(34,1,7,10,0),(36,3,8,10,0),(37,6,9,10,0),(38,6,9,17,0),(39,6,9,8,0),(40,1,7,38,0),(41,3,8,38,0),(43,6,9,14,0),(44,6,9,11,0),(45,2,11,55,0),(46,2,11,57,0),(47,2,11,58,0),(48,3,8,3,0),(49,3,8,4,0),(50,2,11,59,0),(51,2,11,53,0),(52,2,11,18,0),(53,3,8,12,0),(54,2,11,60,0),(55,7,7,15,1);
/*!40000 ALTER TABLE `programme_classes` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `programme_lecons` WRITE;
/*!40000 ALTER TABLE `programme_lecons` DISABLE KEYS */;
INSERT INTO `programme_lecons` (`id_lecon`, `id_programme_classe`, `numero`, `titre`) VALUES (1,1,1,'Entiers Naturels'),(2,1,2,'Comparaison de 2 naturels'),(3,2,1,'Les 3 états de la matière :état solide, état  liquide, état gazeux'),(4,2,2,'Propriétés physiques de la matière'),(5,2,3,'Volumes des solides et des liquides'),(6,2,4,'Masse d’un corps'),(7,2,5,'Masse d’un corps : simple et double pesée – Masses manquées'),(8,2,6,'Changement d’état physique'),(9,2,7,'Masse volumique des solides, des liquides'),(10,2,8,'Notion de température'),(11,2,9,'Electricité : circuitélectrique simple'),(12,2,10,'Circuit électrique simple et schéma'),(13,2,11,'Montage d’ampoules en série, en parallèle'),(14,3,1,'Eaux Naturelles'),(15,3,2,'Mélanges et corps purs'),(16,3,3,'Etude de l’air-composition de l’air'),(17,3,4,'Combustion : Etude de la combustion du carbone, du soufre des  combustibles habituelles caractérisations des produits formés'),(18,3,5,'Rôle de l’oxygène dans les combustions'),(19,3,6,'Dangers des combustions'),(20,3,7,'Etude de la décomposition de l’eau pure par électrolyse'),(21,3,8,'Notion de symbolisme (se limiter aux corps purs simples)'),(22,3,9,'Gaz carbonique'),(23,3,10,'Sel de cuisine'),(24,4,1,'Organisation générale du corps humain'),(25,4,2,'Le squelette de l’homme'),(26,4,3,'La dent : Structure, Notion de formule dentaire'),(27,4,4,'Les mammifères :(La souris)'),(28,4,5,'Les oiseaux'),(29,4,6,'Les reptiles : le margouillat'),(30,4,7,'Les batraciens : La grenouille'),(31,4,8,'Les poissons : carpe ou capitaine'),(32,4,9,'Caractères communs à l’embranchement des vertébrés'),(33,4,10,'Introduction à la botanique'),(34,4,11,'Etude morphologique de l’appareil reproducteur :du Flamboyant'),(35,4,12,'le cotonnier'),(36,4,13,'L’arachide'),(37,4,14,'Etude comparée de l’appareil reproducteur de quelques plantes à fleurs'),(38,4,15,'Etude pratique d’une plante sans fleurs : Fougère'),(39,4,16,'Notion de peuplement des milieux : l’homme et la nature'),(40,4,17,'La désertification'),(41,4,18,'La pollution'),(42,1,3,'Addition dans IN'),(43,1,4,'Droites parallèles'),(44,1,5,'Soustraction dans IN'),(45,1,6,'Les droites perpendiculaires'),(46,1,7,'Multiplication dans IN'),(47,1,8,'Puissance d’un entier naturel'),(48,1,9,'Eléments de géométrie dans l’espace'),(49,1,10,'Caractères de divisibilité'),(50,1,11,'Secteur angulaire'),(51,1,12,'Caractères de divisibilité ('),(52,1,13,'Nombres premiers'),(53,1,14,'Repérage'),(54,1,15,'Ensemble dans Z'),(55,1,16,'Addition dans Z'),(56,1,17,'Triangle'),(57,1,18,'Quadrilatères'),(59,1,20,'Multiplication dans Z'),(60,1,21,'Puissance des nombres relatifs'),(61,1,22,'Cercle-Disque'),(62,1,22,'Nombres décimaux'),(63,1,24,'Polyèdre'),(64,1,24,'Exercices'),(65,1,25,'Devoirs'),(66,1,26,'Composition'),(67,2,12,'Exercices'),(68,2,13,'Devoirs'),(69,2,14,'Composition'),(70,3,11,'Exercices'),(71,3,12,'Devoirs'),(72,3,13,'Composition'),(73,4,19,'Exercices'),(74,4,20,'Devoir'),(75,4,21,'Composition'),(76,5,1,'Poésie :Extraits des Fables de la Fontaine'),(77,5,2,'Poésie :Poèmes simples de David Diop'),(78,5,3,'Contes :Le pagne noir : B. Dadié'),(79,5,4,'Contes et récits du terroir : Issa Baba Traoré'),(80,5,5,'Contes:	Le mariage du premier homme (conte malgache – la 6ème  en français EDICEF, p.88).'),(81,5,6,'Théâtre : Monsieur Thôgô-gnini : B. Dadié'),(82,5,7,'Théâtre :Une si belle leçon de patience : Massa Makan Diabaté'),(83,5,8,'Portraits physiques et moraux:  La Besace : La Fontaine'),(84,5,9,'Portraits physiques et moraux: Les caractères : Gnaton, Ménalque : La Bruyère'),(85,5,10,'Portraits physiques et moraux :Le rescapé de l’Ethylos : Mamadou Gologo'),(86,5,11,'Enfance et adolescence : Les deux amis : La Fontaine (fables, livre VIII)'),(87,5,12,'Enfance et adolescence :L’enfant noir : Camara Laye'),(88,5,13,'Enfance et adolescence :Black Boy : Richard Wright'),(89,5,14,'Afrique des savanes, Afrique des forêts : l’eau et l’arbre : •	L’eau maîtresse de la vie et de la mort : Chinua Achebé'),(90,5,15,'Afrique des savanes, Afrique des forêts :Le chant des rameurs : B. Diop •	La vengeance de l’arbre sacré : J. Pliya •	Le reboisement : Pourquoi planter des arbres (La 6ème en français EDICEF, p. 202).'),(91,5,16,'Afrique des savanes, Afrique des forêts :L’enfant et la rivière : Henri Bosco'),(92,6,1,'Construction de phrase et paragraphe'),(93,6,2,'Description'),(94,6,3,'Récits et Narration'),(95,7,1,'le nom'),(96,7,2,'le genre du nom'),(97,7,3,'le nombre du nom'),(98,7,4,'les déterminants : les articles'),(99,7,5,'les déterminants : les adjectifs possessif, démonstratif, indéfini'),(100,7,6,'les déterminants : L’adjectif qualificatif et les groupes nominaux prépositionnels'),(101,7,7,'les pronoms personnels sujets'),(102,7,8,'les pronoms personnels compléments : lui, elle, elles, leur, eux, en et y •	emploi de en et de y'),(103,7,9,'les pronoms possessif, démonstratif, relatif, indéfini.'),(104,7,10,'la fonction épithète'),(105,7,11,'la fonction déterminative'),(106,7,12,'la fonction appositive'),(107,8,1,'La terre dans l’espace – Forme et dimension.'),(108,8,2,'L’orientation : localisation d’un point à la surface de la terre.'),(109,8,3,'Les mouvements de la terre et leurs conséquences  géographiques.'),(110,8,4,'La représentation de la terre : les cartes, leur établissement et leur utilisation.'),(111,8,5,'Une heure de T.P. réservée à la lecture de la carte simple.'),(112,8,6,'L’atmosphère : définition – constitution.'),(113,8,7,'Les éléments du climat : températures, pressions, vents, précipitations (Evoquer la notion de climat)'),(114,8,8,'Les grandes zones de climat et de végétation.'),(115,8,9,'Le relief : description et définition des principales formes du relief : plaine, plateau, montagne, figuration du relief sur carte.'),(116,8,10,'L’écoulement des eaux de pluie : infiltration, ruissellement.'),(117,8,11,'Les cours d’eau : description – débits et régimes (T.P.).'),(118,8,12,'L’érosion – érosion par ruissellement, érosion fluviale et éolienne.'),(119,8,13,'La population du globe : notions de répartition, de densité, de race, de natalité, mortalité, accroissement naturel, migrations.'),(120,8,14,'Les différents types d’activités humaines (agriculture, élevage, industrie, transport, commerce).'),(121,9,1,'Objet de l’histoire – Notion de chronologie et divisions de l’histoire. Les       sources de connaissance de l’histoire (insister sur la tradition orale dans le        cas de l’Afrique).'),(122,9,2,'La préhistoire – importance de la préhistoire africaine (sites        préhistoriques).'),(123,9,3,'Le monde paléolithique.'),(124,9,4,'La révolution du néolithique : les nouvelles techniques et la division du        travail – la diffusion de l’agriculture et la sédentarisation, le travail des       métaux.'),(125,9,5,'L’Afrique néolithique – La civilisation néolithique du Sahara et ses        manifestations artistiques (art rupestre) – Conséquences de       l’assèchement du Sahara (migration, nouveaux foyers de civilisations) : la       civilisation de Nok.'),(126,9,6,'L’Egypte : le pays, les grandes périodes de l’histoire égyptienne.'),(127,9,7,'La civilisation égyptienne – la vie économique et sociale.'),(128,9,8,'Les civilisations africaines à la fin du IVè siècle : Nubie, Axoum.'),(129,9,9,'La Grèce : la vie politique, économique, artistique et religieuse.'),(130,9,10,'La formation de l’empire romain.'),(131,9,11,'La civilisation romaine sous l’empire.'),(132,9,12,'Le christianisme dans l’empire romain : naissance et expansion.'),(133,9,13,'L’Afrique romaine.'),(134,9,14,'La fin de l’unité du monde romain : les invasions germaniques – La          formation de nouveaux royaumes en Occident.'),(135,10,1,'Problèmes d’aujourd’hui(L’os de la parole Adam Bâh Konaré)'),(136,10,2,'Problèmes d’aujourd’hui( Soundjata ou l’Epopée Mandingue. D.T. Niane.'),(137,10,3,'Textes explicatifs(-Manuels -Dictionnaire ,-Articles de presse ,-Documents sur la santé, le sport, la culture.'),(138,10,4,'Textes informatifs.(-Manuels -Dictionnaire ,-Articles de presse ,-Documents sur la santé, le sport, la culture.'),(139,11,1,'Functions'),(140,11,2,'Topics'),(141,11,3,'Situation problems'),(142,11,4,'Grammar structures'),(143,12,1,'Forces (définition, caractérisation, représentation    vectorielle, classification) ;'),(144,12,2,'Mesure de forces (instruments de mesure, unité de mesure,  mesure) ;'),(145,12,3,'Principe des actions réciproques (énoncé, cas d’interaction localisée) ;'),(146,12,4,'Equilibre d’un solide soumis à différentes forces;'),(147,12,5,'Poids et masse d’un corps (définitions, caractéristiques, unité, variation du poids avec le lieu, invariance de la masse avec le lieu, relation entre masse et poids) ;'),(148,12,6,'Tension d’un fil ou d’un ressort (définition, équilibre d’un solide suspendu à un fil ou à un ressort) ;'),(149,12,7,'Action d’un plan sur un corps (support horizontal, plan incliné dans le cas de frottements négligeables) ;'),(150,12,8,'Forces s’exerçant sur un corps capable de tourner autour d’un axe :'),(151,12,9,'Moment d’une force par rapport à un axe fixe (définition, expression, unité)'),(152,12,10,'Couple de forces (définition, exemples) ;'),(153,12,11,'Moment d’un couple (définition, expression, unité) ;'),(154,12,12,'Condition d’équilibre d’un solide susceptible de tourner autour d’un axe (équilibre d’un solide  soumis à des forces, théorème des moments);'),(155,12,13,'Cinématique (définition, repères et trajectoires, vitesses, accélérations).'),(156,13,1,'Structure de l’atome (définition, constituants, couche électronique ou niveau d’énergie et règles élémentaires de répartition des électrons) ;'),(157,13,2,'Numéro atomique, nombre de masse (définitions, neutralité électrique de l’atome) ;'),(158,13,3,'L’élément chimique (définition, représentations  et de Lewis, notion d’isotopie) ;'),(159,13,4,'Classification périodique des éléments (historique, constitution, propriétés, notion de familles, intérêt) ;'),(160,13,5,'Liaisons chimiques : définition, mécanisme de formation des liaisons chimiques (covalentes simple et multiple, liaison s ionique, semi polaire) ;'),(161,13,6,'Réactions chimiques : définition, équation-bilan, règle d’écriture, proportions stœchiométrique et non  stœchiométrique, lois de conservation (qualitative et quantitative) ;'),(162,13,7,'La mole : Définitions de la mole, de la masse molaire, du volume molaire, loi d’Avogadro Ampère'),(163,13,8,'Les solutions :La dissolution •	Solvant, soluté, solution, miscibilité •	Concentration massique, molaire •	Solubilité'),(164,13,9,'Les Solutions acides, les solutions basiques : •	Définitions, •	Propriétés •	Notion  de pH.'),(165,14,1,'Ecologie : -	définition ; -	facteurs  écologiques ; -	caractéristiques d’un écosystème ; -	chaîne alimentaire ; -	adaptation des êtres vivants à leur milieu.'),(166,14,2,'Le microscope et la loupe. -	Description du microscope. -	Utilisation du microscope. -	Description de la loupe. -	Utilisation de la loupe.'),(167,14,3,'Organisation de la cellule -	Cellule animale. -	Cellule végétale. -	Cellule procaryote, cellule eucaryote.'),(168,14,4,'Vie cellulaire. -	Rôles des membranes cellulaires, du noyau, des organiques cytoplasmiques. -	Mitose : étapes, notion de reproduction conforme.'),(169,14,5,'Les tissus -	Tissus animaux. -	Tissus végétaux.'),(170,14,6,'Organisation générale du système nerveux. -	Système nerveux neurovégétatif. -	Système nerveux de relation. -	Aires corticales. -	Nerfs et neurones.'),(171,15,1,'Trigonométrie'),(172,15,2,'Fonction numérique d’une variable réelle'),(173,15,3,'Equations et inéquations'),(174,15,4,'Logique'),(175,15,5,'Géométrie plane'),(176,15,6,'Transformations et isométries du plan'),(177,15,7,'Géométrie dans l’espace'),(181,17,1,'Population : répartition, structures -	Grands secteurs d’activités -	Pays riches, pays pauvres -	Faim dans le monde -	Problème de l’eau dans le sahel'),(182,17,2,'Terre : situation, forme, constitution -	Mouvements de la Terre et leurs conséquences -	Représentation de la Terre : carte, globe -	Climats : atmosphère, éléments du climat, circulation générale de l’atmosphère -	Milieux biogéographiques  -	Modelé déserti'),(183,17,3,'-	Migrations -	Tourisme'),(184,17,4,'-	Concepts clés : localisation, cycle de l’eau, relations, paysages, débit, régime, réseaux, habitat, démographie, hydrographie. -	Travaux pratiques : calcul des coordonnées géographiques, calcul de l’heure, diagrammes, pyramides des âges. -	Enquête : Tec'),(185,18,1,'Poésie : Poèmes simples de Guy Tirolien'),(186,18,2,'Poésie : Poèmes simples de Bernard Dadié'),(187,18,3,'Contes : Contes et nouveaux contes d’Amadou Koumba : B. Diop'),(188,18,4,'Théâtre : L’Avare : Molière'),(189,19,1,'Etude du verbe :le verbe'),(190,19,2,'Etude du verbe :le mode indicatif'),(191,19,3,'Etude du verbe:le présent'),(192,19,4,'les futurs : le futur simple et le futur antérieur'),(193,19,5,'les temps du passé'),(194,19,6,'les modes : le conditionnel  -  le subjonctif'),(195,19,7,'les modes : l’impératif  -  l’infinitif  -  le participe'),(196,19,8,'tournures pronominale et impersonnelle'),(197,19,9,'le sens du verbe : sens transitif  -  sens intransitif'),(198,20,1,'La liberté : limites et exigences'),(199,20,2,'Les différentes formes de la liberté'),(200,20,3,'La tolérance- l’intolérance'),(201,20,4,'La dignité'),(202,20,5,'La probité et la loyauté'),(203,20,6,'La bonté'),(204,20,7,'L’Esprit de Sacrifice'),(205,20,8,'Le respect du bien public'),(206,20,9,'La conscience professionnelle'),(207,20,10,'La discipline personnelle et collective'),(208,20,11,'La commune- la mairie'),(209,20,12,'Conseil communal'),(210,20,13,'Le maire et ses adjoints'),(211,20,14,'L’État Civil'),(212,20,15,'L’État Civil : le mariage'),(213,20,16,'Les services municipaux'),(214,20,17,'Budgets et biens communaux'),(215,20,18,'Le village : choix, nomination et attributions'),(216,20,19,'Le cercle, le conseil de cercle'),(217,20,20,'Les Ordres nationaux : création et composition'),(218,20,21,'Le code de la route'),(219,21,1,'La probité et la loyauté'),(220,21,2,'La bonté'),(221,21,3,'Esprit de Sacrifice'),(222,21,4,'Le respect du bien public'),(223,21,5,'La conscience professionnelle'),(224,21,6,'La  discipline personnelle et collective'),(225,21,7,'La commune-là mairie'),(226,21,8,'Conseil communal'),(227,21,9,'Le maire et ses adjoints'),(228,21,10,'Etat Civil'),(229,21,11,'Etat Civil: le mariage'),(230,21,12,'Les services municipaux'),(231,21,13,'Budgets et biens communaux'),(232,21,14,'le village: choix,nomination et attributions'),(233,21,15,'Le cercle, le conseil de cercle'),(234,21,16,'Les Ordres nationaux: création et composition'),(235,21,17,'Le code de la route'),(272,24,1,'going to work'),(273,24,2,'at the hospital'),(274,24,3,'there\'s a good film on this evening'),(275,24,4,'bola\'s grand father comes to lagos'),(276,24,5,'the naming-ceremony'),(277,24,6,'the journey home'),(278,24,7,'a letter from scotland'),(279,24,8,'sikiru comes home'),(280,24,9,'sikiru visits his village'),(281,24,10,'sikiru quarrels with hajo'),(282,24,11,'a car crash'),(283,24,12,'a storm in the country'),(284,24,13,'an elephant hunt'),(285,24,14,'in the market'),(286,24,15,'a party on the beach'),(287,25,1,'my classroom'),(288,25,2,'how are you ?'),(289,25,3,'what ios this in English ?'),(290,25,4,'information one'),(291,25,5,'questions -questions'),(292,25,6,'where is my pen ?'),(293,25,7,'where do you live ?'),(294,25,8,'Reading one'),(295,25,9,'Im hungry'),(296,25,10,'have you got ?'),(297,25,11,'some new sandals'),(298,25,12,'révision one'),(299,25,13,'kuassi the bully'),(300,25,14,'koffi is late'),(301,25,15,'in a shop'),(302,25,16,'information two'),(303,25,17,'every day'),(304,25,18,'outside the school'),(305,26,1,'koffi\'s report'),(306,26,2,'Reading two'),(307,26,3,'a letter from koffi'),(308,26,4,'where are koffi&#039;s trousers ?'),(309,26,5,'back to school'),(310,26,6,'révision two'),(311,26,7,'hot pepper soup'),(312,26,8,'the football match'),(313,26,9,'can you do my homework, Dad ?'),(314,26,10,'information three'),(315,26,11,'going to Abidjan'),(316,26,12,'after the match'),(317,26,13,'the arts festival'),(318,26,14,'Reading three'),(319,26,15,'the class trip (1)'),(320,26,16,'the class trip (2)'),(321,26,17,'the visit'),(322,26,18,'révision three'),(355,28,1,'Organisation générale du corps humain'),(356,28,2,'Le Squelette de l’homme les articulations'),(357,28,3,'Les Os'),(358,28,4,'Hygiène du Squelette'),(359,28,5,'Système Musculaire et les Mouvements'),(360,28,6,'Hygiène du Muscle'),(361,28,7,'Anatomie du Système Nerveux'),(362,28,8,'Fonctionnement du Système Nerveux'),(363,28,9,'Hygiène du Système Nerveux'),(364,28,10,'L’oeil organe de la vue hygiène'),(365,28,11,'La peau organe du toucher hygiène'),(366,28,12,'Correction des compositions du 1er trimestre'),(367,28,13,'L’appareil digestif de l’homme'),(368,28,14,'La digestion'),(369,28,15,'Le Sang'),(370,28,16,'L’appareil circulatoire et la circulation'),(371,28,17,'L’appareil respiratoire et la respiration'),(372,28,18,'L’appareil urinaire et l’Excrétion'),(373,28,19,'L’appareil reproducteur'),(374,28,20,'La microbiologie l’homme et les microbes pathogènes'),(375,28,21,'L’oeuvre de pasteur'),(376,28,22,'L’infection microbienne'),(377,28,23,'Période de Composition'),(378,28,24,'Correction des compositions du 2e trimestre'),(379,28,25,'Le Charbon'),(380,28,26,'La Diphtérie'),(381,28,27,'Fleming et la pénicilline : Notion d’antibiotiques'),(382,28,28,'Le Paludisme'),(383,28,29,'L’amibiase'),(384,28,30,'Le Tétanos'),(385,28,31,'L’alcoolisme Tabagisme et drogue (ensemble)'),(386,28,32,'La Tuberculose'),(387,29,1,'Les orthoptères : Le Criquet'),(388,29,2,'Les coléoptères : Le Hanneton'),(389,29,3,'Les lépidoptères : Le papillon'),(390,29,4,'Les Isoptères : Les Termites'),(391,29,5,'Caractères généraux et classification des insectes étudiés'),(392,29,6,'Les arachnides : L’araignée ou le Scorpion'),(393,29,7,'Les Myriapodes : La scolopendre ou Iule'),(394,29,8,'Généraux des articulés'),(395,29,9,'Les mollusques : Etude d’un mollusque : gastéropode ou lamellibranche'),(396,29,10,'Caractères généraux et classification des mollusques'),(397,29,11,'Correction des compositions du 1er Trimestre'),(398,29,12,'Les vers : Etude d’un annélide : Lombric ou Sangsue'),(399,29,13,'Etude d’un vers parasite : Le Ténia ou bilharzie (insister sur le cycle)'),(400,29,14,'Etude d’un Protozoaire : La paramécie ou l’amibe dysentérique'),(401,29,15,'Vue d’ensemble sur la classification des invertébrés'),(402,29,16,'Définir les coelentérés, les Echinodermes et citer des exemples'),(403,29,17,'Géologie : Introduction à la géologie'),(404,29,18,'Etude des roches sédimentaires : Le sable fluviatile'),(405,29,19,'Le grés -L’argile'),(406,29,20,'Le calcaire'),(407,29,21,'Le pétrole'),(408,29,22,'Les roches magmatiques : Le granite et la dolérite'),(409,29,23,'Les roches métamorphiques : Le gneiss ou le micaschiste'),(410,29,24,'Les eaux'),(411,29,25,'Action chimique de l’eau : Latérite et latérisation'),(412,29,26,'Le vent et ses actions'),(413,29,27,'Les volcans et les séismes'),(414,29,28,'Les déformations de l’écorce terrestre : plis, failles,'),(415,29,29,'Eres et Périodes géologiques'),(416,29,30,'Révision'),(417,30,1,'Poids d’un corps : La verticale (Rappel)'),(418,30,2,'Notion de Force caractéristiqueReprésentation- Unité'),(419,30,3,'Principe des actions réciproques'),(421,30,5,'Equilibre d’un poids d’un corps soumis à deux  Forces-Loi'),(422,30,6,'Variation du poids d’un corps avec l’altitude et  la latitude'),(423,30,7,'Relation entre Poids et masse'),(424,30,8,'Travail d’une Force : Unité'),(425,30,9,'Notion de Puissance –Unité'),(426,30,10,'Notion de Puissance –Unité (suite)'),(427,30,11,'Exercices de Révision  Période de composition'),(428,30,12,'Correction des compositions du 1erTrimestre'),(429,30,13,'Leviers-Poulie-Treuil (description'),(430,30,14,'Principe, Fonctionnement, condition d’équilibre'),(431,30,15,'Rappel sur les quantités de chaleur'),(432,30,16,'Transformation réciproque du travail  mécanique en chaleur'),(433,30,17,'Moteurs thermiques'),(434,30,18,'Rappel sur la notion d’intensité du courant'),(435,30,19,'Quantité d’électricité : Unité'),(436,30,20,'Electrolyse : Etude qualitative et quantitative'),(438,30,22,'Période des compositions'),(439,30,23,'Correction des compositions du 2erTrimestre'),(440,30,24,'Loi de Faraday : application ind'),(441,30,25,'Exercices de Révision'),(442,30,26,'Effet Joule : application de l’effet joule'),(443,30,27,'Résistances'),(445,30,29,'Association en série et en parallèle de résistance'),(446,31,1,'Rappel des équations de réactions sur les fonctions (Acide Base Sel)'),(448,31,3,'Structure de l’atome'),(449,31,4,'Classification Périodique des éléments'),(450,31,5,'Oxydation-Réduction (Oxydo-Réduction)'),(452,31,7,'Exercices de Révision'),(453,31,8,'CHIMIE MINERALE : Introduction à l’étude des métaux'),(455,31,10,'Exercices de Révision'),(456,31,11,'Correction des compositions du 1 Trimestre'),(457,31,12,'L’Aluminium'),(458,31,13,'Le Fer'),(460,31,15,'Le cuivre'),(461,31,16,'Le Zinc'),(462,31,17,'CHIMIE ORGANIQUE : Notion de chimie organique'),(463,31,18,'Le Méthane'),(465,31,20,'L’Ethylène'),(467,31,22,'L’acétylène'),(468,31,23,'L’alcool Ethylique'),(470,31,25,'Révision Générale'),(471,32,1,'Le continent africain : Relief – Climat – Végétation'),(472,32,2,'Le continent africain : Hydrographie – Fleuves et Lacs'),(473,32,3,'Le continent africain : Population – Répartition – Races – Langues – Religions – Démographie – Migrations'),(474,32,4,'Présentation de l’Afrique de l’Ouest – Grands traits du relief – Climat – Végétation – Hydrographie (carte)'),(475,32,5,'L’Afrique de l’Ouest : Population – Économie (Généralité)'),(476,32,6,'Les pays de la zone soudano-sahélienne : (Mauritanie – Sénégal – Gambie – Burkina Faso – Niger) – Traits communs économiques – Leurs problèmes'),(477,32,7,'Le Mali : Le pays et les hommes ; la vie économique'),(478,32,8,'Les pays côtiers de la zone humide (Guinée – Bissau – Sierra Leone – Libéria – Ghana – Bénin – Nigeria) – Traits communs économiques – Leurs problèmes'),(479,32,9,'Les groupements économiques régionaux (UEMOA – CEDEAO – CEEAC – CILSS – CRÉATION – OBJECTIFS – FONCTIONS)'),(480,32,10,'L’Afrique centrale : (Cameroun – RCA – Congo – Zaire – Guinée Équatoriale – Angola) – Présentation grands traits du relief – Climat – Végétation – Hydrographie – États – Population'),(481,32,11,'L’Afrique australe : (Rép. Sud-Africaine – Namibie – Botswana – Lesotho – Swaziland – Zambie – Malawi – Zimbabwe – Mozambique) – Grands traits du relief – Climat – Végétation – Hydrographie – États – Population'),(482,32,12,'L’Afrique orientale : (Burundi – Rwanda – Kenya – Tanzanie – Somalie – Éthiopie – Djibouti) – Présentation grands traits du relief – Climat – Végétation – Hydrographie – États – Population'),(483,32,13,'Afrique du Nord : (Égypte – Soudan – Algérie – Tunisie – Maroc – Libye) – Présentation grands traits du relief – Climat – Végétation – Hydrographie – États – Population'),(484,32,14,'Les problèmes de développement en Afrique – Retard technologique – Problèmes démographiques – Climatique – Déforestation – Désertification – L’urbanisme – Protectionnisme – Éducation – Endettement'),(485,33,1,'La révolution française: cause'),(486,33,2,'La révolution française : les étapes (Présentation succincte)'),(487,33,3,'La révolution française: conséquences'),(488,33,4,'Les progrès scientifique et techniques du XIXe siècle jusqu\'on 1914'),(489,33,5,'La lutte ouvrière et les théories socialistes'),(490,33,6,'Impérialisme : cause et manifestations'),(491,33,7,'Les systèmes coloniaux (français, Anglais) et l\'exploitation coloniale'),(492,33,8,'La première guerre mondiale'),(493,33,9,'La révolution de 1917 en Russie et la Naissance de l\'Etat Soviétique'),(494,33,10,'La 2° guerre mondiale et Afrique'),(495,33,11,'ONU: Création, Objectifs, Institutions'),(496,33,12,'Eveil des peuples colonisés d\'Afrique et les luttes de délibération nationale'),(497,33,13,'La République du Mali : Evolution politique, économique et sociale de 1960 à nos jours'),(498,33,14,'OUA: Création, Objectifs, Institutions'),(499,33,15,'Révision'),(500,34,1,'Solfège (notions élémentaires)'),(501,34,2,'Culture auditive et vocale'),(502,34,3,'Chants (local, scolaire, étranger)'),(503,34,4,'Instruments de musique (classification, notions)'),(508,36,1,'Solfège (intervalles, tonalités majeures)'),(509,36,2,'Culture auditive et vocale'),(510,36,3,'Chants (local, étranger, anglais)'),(511,36,4,'Histoire de la musique (instruments africains)'),(512,37,1,'Culture auditive, vocale et rythmique'),(513,37,2,'Solfège (tonalités majeures et mineures, accords, mesures composées)'),(514,37,3,'Histoire de la musique (folklore malien et africain)'),(515,37,4,'Chants (à une ou deux voix, chorale)'),(516,38,2,'La phrase déclarative'),(517,38,3,'La phrase interrogative'),(518,38,4,'La phrase impérative'),(519,38,5,'La phrase exclamative'),(520,38,6,'La phrase négative'),(521,38,7,'La phrase emphatique'),(522,38,8,'La phrase passive'),(523,38,9,'La coordination dans le groupe nominal et dans la phrase'),(524,38,10,'La subordination dans la phrase'),(525,38,11,'Les subordonnées conjonctives'),(526,38,12,'Les subordonnées relatives'),(527,38,13,'Les subordonnées interrogatives'),(528,38,14,'Les subordonnées infinitives'),(529,38,15,'Les subordonnées participes'),(530,38,16,'Révision'),(531,39,1,'Lettre Ordinaire'),(532,39,2,'Lettres Administrative'),(533,39,3,'Dissertation et Révision'),(534,40,1,'Notion de mise en page'),(535,40,2,'Composition de natures mortes'),(536,40,3,'Dessin de personnages d’après nature'),(537,40,4,'Étude de la couleur (couleurs primaires, secondaires, complémentaires, chaudes/froides, harmonie)'),(538,40,5,'Décoration (définition et moyens décoratifs : point, trait, surface, symétrie)'),(539,40,6,'Techniques décoratives (aperçu)'),(540,41,1,'Dessin à vue (natures mortes, études documentaires)'),(541,41,2,'Moyens décoratifs (inversion, superposition, jeu de fonds)'),(542,41,3,'TTechniques décoratives (marqueterie, céramique, tissu imprimé)'),(543,41,4,'Corps humain et visage (proportions, croquis, silhouettes, portrait)'),(544,41,5,'Croquis côté (exercices simples)'),(549,43,1,'Poésie: Poème simple de L.G DAMAS'),(550,43,2,'Essai: Soundiata (Djibril Tamsir NIARR)'),(551,43,3,'Théâtre : La mort de Chaka (S.B KOUYATE)'),(552,43,4,'Thèmes : Problème de notre temps:'),(553,43,5,'Sous Orage (S.B. KOUYATE)'),(554,43,6,'Le sang des masques (S.B. KOUYATE)'),(555,43,7,'Sahel,Sanglante Sécheresse (Mandé DIARRA)'),(556,43,8,'Quand la drogue en mêle (Issa Baba TRAORE)'),(557,43,9,'L\'égalité des sexes'),(558,43,10,'Ruchers de la capitale (Ismael Samba TRAORE)'),(559,43,11,'Quand la machine chasse l\'homme (S.GIFT)'),(560,43,12,'Pleure, ô Pays bien aimé(A.Paton)'),(561,43,13,'Poésie: Poème simple d\'Aimé CESAIRE'),(562,43,14,'Essai Soundiata (D.T.NIARRE)'),(563,43,15,'Théâtre: Trois Prétandants...un mari(G)'),(564,43,16,'Thème: L\'homme et la nature'),(565,43,17,'Le sang des masques (S.B.KOUYATE'),(566,43,18,'Sahel,sanglante sécheresse (Mandé A.DIARRA)'),(567,43,19,'Chants d\'automna (V.HUGO)'),(568,43,20,'Le lac(Lamartine)'),(569,43,21,'Crépuscule des temps anciens (Nasboni)'),(570,43,22,'Midi(Le conta de Liste)'),(571,43,23,'Le chant du lac (C.B.QUENUM)'),(572,43,24,'Poésie: Poème simple de L.S.SINGHOR Dictée'),(573,43,25,'Thèmes : Les jeunes et la société'),(574,43,26,'Le premier devoir d\'honnêteté (P.H.SIM)'),(575,43,27,'Une chasse à lhomme dans le district six (L.G)'),(576,43,28,'Si tu peux... (P.K)'),(577,43,29,'Abandon de Asis(M.T)'),(578,43,30,'Le vieillard et les trois hommes (La Fontaine) et Révision'),(579,44,1,'Respect des biens public et privé'),(580,44,2,'La foi dans le travail'),(581,44,3,'Les qualités du bon travailleur la conscience professionnelle'),(582,44,4,'Bien choisir son métier'),(583,44,5,'La solidarité entre les travailleurs'),(584,44,6,'La naissance de la république du Mali'),(585,44,7,'La naissance de Armée malienne'),(586,44,8,'Constitution du Mali définition'),(587,44,9,'Les institutions de la république du mali'),(588,44,10,'Etude des principales institutions de la république du Mali'),(589,44,11,'Le président'),(590,44,12,'Assemblée nationale'),(591,44,13,'Les ministères : Organisation d\'un ministère'),(592,44,14,'Le conseil des ministres'),(593,44,15,'Les cours et tribunaux du Mali'),(594,44,16,'Emblème national : les armées le sceau'),(595,44,17,'Organisation des femmes'),(596,44,18,'Journée internationale des femmes'),(597,44,19,'Journée panafricaine des femmes'),(598,44,20,'Code malien du mariage'),(599,44,21,'Syndicats et syndicalisme'),(600,44,22,'OUA et les groupements régionaux'),(601,44,23,'La charte africaine des droits de l\'homme'),(602,44,24,'L\'ONU ses institutions spécialisés'),(603,44,25,'La déclaration universelle des droits de l\'homme'),(604,44,26,'Les élections'),(605,44,27,'Environnement'),(606,44,28,'Les facteurs de dégradation'),(607,44,29,'Les fléaux qui nous menacent'),(608,44,30,'Notre patrimoine richesse et diversité-comment le protéger'),(609,45,1,'Notions de base (Définition de l’information, Définition du traitement, Définition de l’informatique, Définition du système informatique)'),(610,45,2,'Structure de base d’un ordinateur (Schéma fonctionnel d’un ordinateur, Unité centrale, Périphériques)'),(611,45,3,'Types de logiciels (Les logiciels de base, Les logiciels d’application)'),(612,45,4,'Utiliser un système d’exploitation (Fonctionnalités de base d’un système d’exploitation – présentation et historique sommaire, Environnement d’un système d’exploitation – arrière-plan, écran de veille, barre des tâches, icônes…, Gestion des fichiers/dossi'),(613,45,5,'Utiliser un logiciel de traitement de texte (Fonctionnalités d’un texteur, L’environnement de travail, Élaboration d’un document simple)'),(614,45,6,'Utiliser un tableur (Fonctionnalités d’un tableur, L’environnement de travail, Élaboration de feuille de calcul simple)'),(615,45,7,'Connaître les notions de base sur le réseau informatique (Notion de réseau informatique, Typologie de réseaux, Avantages d&#039;un réseau)'),(616,45,8,'Utiliser le réseau Internet (Définition, Utilisation de l’Internet – connexion et types de services, Avantages et inconvénients de l’Internet)'),(617,45,9,'Naviguer sur le Web (Définition, Connexion, Création de compte, Envoi et réception de courrier, Recherche documentaire, Avantages et inconvénients du Web)'),(618,16,1,'Expliquer l’importance de l’histoire (Explication de l’importance de l’histoire, Étude de la chronologie, Explication des sources d’étude de l’histoire africaine)'),(619,16,2,'Étudier les notions générales sur la préhistoire (Description de l’évolution de l’homme préhistorique, Explication de l’importance de la préhistoire africaine)'),(620,16,3,'Étudier les notions générales sur l’Égypte antique (Information sur le pays de l’Égypte antique, Identification des grandes périodes historiques de l’Égypte pharaonique, Description de la civilisation de l’Égypte pharaonique)'),(621,16,4,'Étudier la période des grands empires africains (Étude brève de la genèse des empires du Ghana, du Mali et du Songhoy, Explication des aspects de la civilisation des grands empires africains)'),(622,16,5,'Étudier le commerce transsaharien (Étude brève de la vie d’Ousmane dan Fodio et Sékou Amadou, Explication de l’importance civilisationnelle du commerce transsaharien)'),(623,16,6,'Étudier les relations entre l’Afrique et l’Europe (Étude de la traite des Noirs)'),(624,46,1,'Identifier les différentes activités (Définition d’une activité, Recensement des différentes activités, Distinction d’une activité rémunérée d’une activité non rémunérée, Distinction d’une activité économique d’une activité non économique)'),(625,46,2,'Expliquer les Sciences Économiques et Sociales et déterminer leur objet (Définition des Sciences Économiques et Sociales, Détermination de leur objet)'),(626,46,3,'Donner la structure d’une famille (Recensement des membres d’une famille, Identification des relations entre les membres, Détermination des différentes formes de famille, Définition de la notion de famille)'),(627,46,4,'Déterminer les fonctions de la famille (Identification des différentes fonctions, Regroupement des fonctions)'),(628,46,5,'Déterminer les différents agents économiques (Énumération des différents agents économiques, Caractérisation des agents économiques)'),(629,46,6,'Schématiser les relations entre les agents économiques (Représentation d’un circuit économique simplifié avec deux agents, Représentation d’un circuit économique complet avec tous les agents)'),(630,47,1,'Collecter les données (Détermination de la population statistique, Détermination des caractéristiques de l’ensemble statistique étudié)'),(631,47,2,'Dépouiller l’information (Identification des données biaisées, Élimination des données biaisées, Évaluation des informations retenues)'),(632,47,3,'Traiter et présenter l’information (Calcul des principaux indicateurs statistiques, Présentation des informations statistiques)'),(633,47,4,'Interpréter les résultats (Exploitation de l’information dans les différents domaines)'),(634,46,7,'Expliquer le processus de production (Définition de la fonction de production, Connaissance des facteurs de production et leurs caractéristiques, Connaissance de la combinaison productive)'),(635,46,8,'Effectuer les opérations de répartition (Réalisation des opérations de répartition, Réalisation des opérations de redistribution, Mesure des inégalités de revenus)'),(636,46,9,'Utiliser le revenu (Détermination du comportement du consommateur et de l’épargnant, Mesure des conséquences de la variation du revenu)'),(637,46,10,'Caractériser les différents types d’entreprise (Classification des entreprises, Evolution des entreprises)'),(638,46,11,'Expliquer le fonctionnement de l’entreprise (Présentation de l’organisation de l’entreprise, Présentation des différents cycles de l’activité de l’entreprise)'),(639,48,1,'Poids d’un corps : La verticale (Rappel)'),(640,48,2,'Notion de Force : Unité'),(641,48,3,'Pression au sein d’un liquide'),(642,48,4,'Pression au sein d’un liquide (suite)'),(643,48,5,'Exercices de Révision'),(644,48,6,'Existence de la pression atmosphérique'),(645,48,7,'Mesure de pression'),(646,48,8,'Vases communicants : applications'),(647,48,9,'Principe fondamental de l’hydrostatique'),(648,48,10,'Application : Théorème de Pascal'),(649,48,11,'Révision générale'),(650,48,12,'Correction des compositions, 1 er Trimestre'),(651,48,13,'Poussée d’Archimède'),(652,48,14,'Principe d’Archimède appliqué aux gaz'),(653,48,15,'Etude qualitative de la dilatation'),(654,48,16,'Quantité de chaleur : unité, mesures de quantité de chaleur'),(655,48,17,'Chaleur massique des solides et des liquides'),(656,48,18,'Electrisation : les 2 sortes d’électricité'),(657,48,19,'Explication sommaire du mécanisme de l’électrisation'),(658,48,20,'Le courant électrique :les effets'),(659,48,21,'Révision générale'),(660,48,22,'Compositions du 2e trimestre'),(661,48,23,'Correction des compositions'),(662,48,24,'Le sens conventionnel du courant électrique'),(663,48,25,'La nature du courant électrique'),(664,48,26,'Notion d’intensité du courant électrique'),(665,48,27,'Notion de tension électrique ou(DDP) Mesure-Unité-Le voltmètre'),(666,48,28,'Exercices de révision'),(667,48,29,'Additivité des tensions et unicité du courant'),(668,48,30,'Révision générale'),(669,49,1,'Phénomènes physiques-Phénomènes chimiques'),(670,49,2,'Mélanges et combinaisons'),(671,49,3,'Mélanges et combinaisons'),(672,49,4,'Exercices de révision'),(673,49,5,'Molécules et atomes'),(674,49,6,'Molécules et atomes (suite)'),(675,49,7,'Structure de l’atome'),(676,49,8,'Exercices de révision'),(677,49,9,'Classification périodique des éléments'),(678,49,10,'Notion de valence'),(679,49,11,'Révision générale'),(680,49,12,'Correction des compositions'),(681,49,13,'Notation chimique'),(682,49,14,'Réactions chimiques et équations chimiques'),(683,49,15,'Application aux problèmes de chimie Idem des Equations aux problèmes de chimie'),(684,49,16,'Exercices de Révision'),(685,49,17,'Combustion dans l’oxygène du carbone du soufre, dumagnes, du fer : Formation de la rouille'),(686,49,18,'Idem'),(687,49,19,'Exercices de révision'),(688,49,20,'L’acide chlorhydrique'),(689,49,21,'Révision Générale'),(690,49,22,'Composition du 2e trimestre'),(691,49,23,'Correction des compositions'),(692,49,24,'L’acide sulfurique'),(693,49,25,'Fonction Acide'),(694,49,26,'La soude caustique'),(695,49,27,'Exercices de Révision'),(696,49,28,'Hydroxyde de calcium'),(697,49,29,'Fonction base'),(698,49,30,'Fonction sel'),(699,49,31,'Révision générale'),(700,50,1,'Identifier les types de dessins (Identification correcte de types de dessins)'),(701,50,2,'Identifier et utiliser le matériel de dessin (Choix judicieux des formats de papier, Association correcte des instruments de dessins à leur nom, Utilisation appropriée des instruments de dessin)'),(702,50,3,'Identifier et tracer les traits conventionnels (Identification et description correcte des traits, Propreté et clarté des traits)'),(703,50,4,'Écrire à l’aide de caractères normalisés (Traçage correct des lignes guides, Respect des hauteurs de chiffres et de lettres, Respect de la forme des caractères, Espacements adéquats et uniformes)'),(704,50,5,'Représenter un objet en projection orthogonale (Identification correcte des méthodes de projection, Choix correct des vues, Disposition correcte des vues, Correspondance entre les vues)'),(705,50,6,'Identifier les composantes d’un poste informatique de DAO (Association correcte des composantes à leur nom)'),(706,50,7,'Démarrer le logiciel de dessin industriel (Allumage correct des composantes du poste informatique, Lancement correct du logiciel)'),(707,50,8,'Identifier les dimensions et coter un dessin (Distinction correcte des lignes d’attaches, des lignes de cotes, des cotes, des lignes de renvoie, des flèches, Distinction correcte des symboles et annotations, Exécution correcte de la cotation)'),(708,50,9,'Réaliser des croquis en projection (Utilisation correcte des échelles, Respect des proportions, Clarté et propreté)'),(709,50,10,'Représenter un objet à l’aide de vues, coupes et sections (Identification correcte des types de coupes, Désignation correcte du plan de coupe, Désignation correcte de la vue en coupe, Exécution correcte des hachures, Identification correcte des matériaux'),(710,50,11,'Exécuter un dessin à l’ordinateur (Copie correcte du dessin, Utilisation correcte des commandes nécessaires, Représentation juste des vues)'),(711,51,1,'فهم معنى النص (التعرف على الرموز الكتابية, إدراك بنية الكلمة و نظم الجملة: المفردات و التراكيب النحوية, البحث عن المعلومات, استخراج العنوان الأساس, كشف المعلومات التي ينقلها النص)'),(712,51,2,'الاستفادة من النص (التعرف على طبيعة النص, الكشف عن وظيفة النص, وصف شكل النص ومضمونه, إدراك معاني المفردات, إبراز المعلومات, التعرف على الأنشطة المطلوب حدوثها, رد الفعل تجاه النص)'),(713,51,3,'فهم التعبيرات الجارية في موقف الحياة اليومية (الاستماع إلى المخاطب وفهم كلامه, التعرف على عناصر موقف الاتصال: من المتكلم؟ متى وأين وعلى ماذا وقع الاتصال, إدراك المعلومات الأساسية متدرجاً حسب الأهمية)'),(714,51,4,'المشاركة في حوار حول موقف الحياة اليومية (إبداء الرأي, مطابقة الاتصال لمقتضى حال المخاطب وموضوع الحديث, استخدام اللغة كمثير لاستجابة المخاطب, الاستجابة للمثير بالإيجاب أو السلب)'),(715,51,5,'التحدث بطلاقة دون المقاطعة (استعمال مصادر اللغة: المفردات والتركيب النحوي حسب موقف الاتصال, توظيف قواعد اللغة المختلفة, نقل المعلومات)'),(716,51,6,'استخدام الكلام لبناء المعارف (طرح أسئلة/إجابة على أسئلة, إعادة صياغة التعبير, تلخيص مسرد ما, مقارنة إنتاجه مع إنتاج أقرانه, استخراج قاعدة وتوظيف المعلومات في مواقف جديدة)'),(717,51,7,'فهم معنى النص (التعرف على الرموز الكتابية, إدراك بنية الكلمة ونظم الجملة: المفردات والتراكيب النحوية, البحث عن المعلومات, استخراج العنوان الأساسي, كشف المعلومات التي ينقلها النص)'),(718,51,8,'الاستفادة من النص (التعرف على طبيعة النص, الكشف عن وظيفة النص, وصف شكل النص ومضمونه, إدراك معاني المفردات, إبراز المعلومات, التعرف على الأنشطة المطلوب حدوثها, رد الفعل تجاه النص)'),(719,51,9,'التفكير في طريقة الكتابة (التعرف على نوع النص, مراعاة مراكز اهتمامه وعاداته وأوضاعه في الكتابة, تحديد استراتيجيات الكتابة, اختبار الأفكار)'),(720,51,10,'إنتاج نص مع مراعاة موقف الاتصال (التخطيط, استدعاء مصادر اللغة: المفردات/تركيب اللغة/التهجئة عند تحرير المسودة, استخدام الوسائل المناسبة لكتابة النص)'),(721,51,11,'تحسين طريقة الكتابة (إعادة قراءة المكتوب, المقارنة بين إنتاجه وإنتاجات كتابية أخرى, تشخيص الإنتاجات وتحديد نقاط الضعف, التخطيط لكتابة جديدة)'),(722,52,1,'Identifier les graphèmes et les phonèmes (Bonne articulation des phonèmes et des sons)'),(723,52,2,'Identifier les mots (Bonne articulation des mots, Identification juste des mots du vocabulaire, Identification correcte de la nature grammaticale des mots)'),(724,52,3,'Repérer les caractéristiques morphologiques des mots (Identification correcte de l’accord grammatical, Identification des temps verbaux)'),(725,52,4,'Repérer les rapports syntaxiques dans la phrase (Identification correcte de la fonction grammaticale des mots, Identification de la nature des propositions dans la phrase, Définition juste de la fonction des propositions)'),(726,52,5,'Repérer les rapports de sens à l’intérieur d’un texte simple (Repérage pertinent des idées contenues dans le texte, Identification correcte des connecteurs, Identification correcte des procédés d’expression simple, Caractérisation adéquate de la progressi'),(727,52,6,'Transcrire des mots (Transcription correcte des lettres, Transcription correcte des syllabes, Somme graphie des mots)'),(728,52,7,'Construire une phrase simple (Emploi correct du sujet, Emploi correct du verbe, Emploi correct du complément, Emploi correct du terme propre)'),(729,52,8,'Construire une phrase complexe (Emploi correct des conjonctions de subordination, Emploi correct des temps simples)'),(730,52,9,'Écrire un texte narratif court (Mise en situation adéquate, Bon enchaînement des actions, Dénouement adéquat)'),(731,52,10,'Écrire un texte descriptif court (Identification correcte de l’objet, Caractérisation adéquate des éléments constitutifs de l’objet, Agencement adéquat des parties de la description, Conclusion adéquate)'),(732,52,11,'Écrire une lettre personnelle (Mise en place adéquate de l’en-tête, Énonciation correcte de la formule d’appel, Bon enchaînement des idées, Mise en place adéquate de la formule de salutation)'),(733,52,12,'Remplir un formulaire (Adéquation efficace des réponses aux questions posées, Correction effective orthographique, Adéquation efficace de l’expression à l’objet du formulaire)'),(734,53,1,'Le monde au début du 7e siècle (Presentation générale de l&#039;Afrique,l&#039;Asie Occidentale anti-islamique de l&#039;Europe chretienne'),(735,53,2,'L&#039;islam : Fondement-Formation du monde musulman'),(736,53,3,'La civilisation musulmane, la vie économique, sociale, intellectuelle, l&#039;art musulman'),(737,53,4,'L&#039;empire de Ghana et le mouvement almoravide'),(738,53,5,'L&#039;occident chrétien du VIIe au Xle siècle : la société féodale'),(739,53,6,'Heurts du monde musulman et du monde chrétien : les croisades et leurs conséquences (économiques, sociale et culture)'),(740,53,7,'L&#039;empire du Mali : des origines jusqu&#039;au règne de Soundiata'),(741,53,8,'L&#039;empire du Mali : du règne de Kankou Moussa au déclin'),(742,53,9,'Le Songhoï : des origines à Soni Ali Ber'),(743,53,10,'Le Songhoï : des Askia au déclin'),(744,53,11,'Tombouctou et Djenne au moyen âge : rayonnement culturel et économique'),(745,53,12,'Les grandes voyages et découvertes'),(746,53,13,'Les royaumes bamanan de Ségou et du Kaarta'),(747,53,14,'Le royaume peul du Macina'),(748,52,13,'Identifier les éléments de la situation de communication (Ecoute attentive de son interlocuteur, Identification correcte du locuteur, Identification correcte du destinataire, Identification correcte du moment de l’énonciation, Identification correcte du l'),(749,52,14,'Établir des relations de sens entre les termes d’un énoncé (Identification correcte du thème, Repérage pertinent de l’enchaînement des idées)'),(750,52,15,'Exprimer des actes de parole (Bonne expression des salutations, Bonne expression des formules de présentation, Bonne formulation des demandes de permission, Bonne expression des excuses, Bonne expression des remerciements)'),(751,52,16,'Exprimer une assertion (Bonne expression de l’affirmation, Bonne expression de la négation)'),(752,52,17,'Exprimer une interrogation (Utilisation correcte des différentes expressions de l’interrogation directe, Bonne expression de l’interrogation indirecte, Bonne expression de l’interrogation négative)'),(753,52,18,'Faire une courte narration (Bonne mise en situation, Agencement correct des actions, Dénouement adéquat)'),(754,52,19,'Faire une courte description (Désignation correcte d’un être vivant, d’un lieu, d’un objet, Caractérisation adéquate d’un être, d’un lieu, d’un objet, Agencement des éléments constitutifs, Conclusion adéquate)'),(755,54,1,'Transcrire des mots (Transcription correcte des lettres, Transcription correcte des syllabes, Somme graphie des mots)'),(756,54,2,'Construire une phrase simple (Emploi correct du sujet, Emploi correct du verbe, Emploi correct du complément, Emploi correct du terme propre)'),(757,54,3,'Construire une phrase complexe (Emploi correct des conjonctions de subordination, Emploi correct des temps simples, Emploi correct de pronoms relatifs)'),(758,54,4,'Écrire un texte narratif court (Mise en situation adéquate, Bon enchaînement des actions, Dénouement adéquat)'),(759,54,5,'Écrire un texte descriptif court (Identification correcte de l’objet, Caractérisation adéquate des éléments constitutifs de l’objet, Agencement adéquat des parties de la description, Conclusion adéquate)'),(760,54,6,'Écrire une lettre personnelle (Mise en place adéquate de l’en-tête, Énonciation correcte de la formule d’appel, Bon enchaînement des idées, Mise en place de la formule de salutation)'),(761,54,7,'Remplir un formulaire (Adéquation efficace des réponses aux questions posées, Correction effective orthographique, Adéquation efficace de l’expression à l’objet du formulaire)'),(762,54,8,'Identifier les éléments de la situation de communication (Ecoute attentive de son interlocuteur, Identification correcte du locuteur, Identification correcte du destinataire, Identification correcte du moment de l’énonciation, Identification correcte du l'),(763,54,9,'Établir des relations de sens entre les termes d’un énoncé (Identification correcte du thème, Repérage pertinent de l’enchaînement des idées)'),(764,54,10,'Exprimer des actes de parole (Bonne expression des salutations, Bonne expression des formules de présentation, Bonne formulation des demandes de permission, Bonne expression des excuses, Bonne expression des remerciements)'),(765,54,11,'Exprimer une assertion (Bonne expression de l’affirmation, Bonne expression de la négation)'),(766,54,12,'Exprimer une interrogation (Utilisation correcte des différentes expressions de l’interrogation directe, Bonne expression de l’interrogation indirecte, Bonne expression de l’interrogation négative)'),(767,54,13,'Faire une courte narration (Bonne mise en situation, Agencement correct des actions, Dénouement adéquat)'),(768,54,14,'Faire une courte description (Désignation correcte d’un être vivant, d’un lieu, d’un objet, Caractérisation adéquate d’un être, d’un lieu, d’un objet, Agencement des éléments constitutifs, Conclusion adéquate)'),(769,55,1,'Ma mere');
/*!40000 ALTER TABLE `programme_lecons` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Superutilisateurs seulement
INSERT INTO `utilisateurs` (`idUtilisateur`,`nomPrenom`,`email`,`pwd`,`fonction`,`telephone`,`genre`,`droit`,`idEcole`,`id_academie`,`id_cap`,`id_enseignant`,`id_parent`,`id_role`,`image`,`statut`,`derniere_connexion`,`managed_orders`,`locale_preference`,`theme_preference`,`last_login_at`,`last_activity`) VALUES (1,'Moustapha BARRY','barrymoustapha908@gmail.com','$2y$10$FAj.YYT1/1A9xRxhlrfQE.yoYSGuDULtkKvmK.0uhMRxgG3fIYhze','Developpeur','74745669','masculin','SupAdmin',NULL,NULL,NULL,NULL,NULL,NULL,'default.png','1',NULL,'[]','fr','vert',NULL,NULL);
INSERT INTO `utilisateurs` (`idUtilisateur`,`nomPrenom`,`email`,`pwd`,`fonction`,`telephone`,`genre`,`droit`,`idEcole`,`id_academie`,`id_cap`,`id_enseignant`,`id_parent`,`id_role`,`image`,`statut`,`derniere_connexion`,`managed_orders`,`locale_preference`,`theme_preference`,`last_login_at`,`last_activity`) VALUES (2,'Karifa DOUMBIA','bintoufah@gmail.com','$2y$10$LDoiwocFZCbmGQY/B9sGwO8URpuP25yfW7krxELHsZ.VPP5v5/2e2','Superadmin','76543212','masculin','SupAdmin',NULL,NULL,NULL,NULL,NULL,NULL,'default.png','1',NULL,'[]','fr','bleu-sombre',NULL,NULL);

-- Permissions des superutilisateurs

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `user_permission` WRITE;
/*!40000 ALTER TABLE `user_permission` DISABLE KEYS */;
INSERT INTO `user_permission` (`user_id`, `permission_id`) VALUES (1,129),(1,133),(1,55),(1,56),(1,57),(1,58),(1,16),(1,52),(1,53),(1,54),(1,9),(1,10),(1,11),(1,122),(1,88),(1,12),(1,36),(1,37),(1,38),(1,95),(1,92),(1,94),(1,93),(1,99),(1,90),(1,97),(1,91),(1,63),(1,64),(1,65),(1,66),(1,69),(1,103),(1,104),(1,108),(1,19),(1,22),(1,20),(1,21),(1,39),(1,40),(1,42),(1,51),(1,59),(1,60),(1,61),(1,62),(1,1),(1,4),(1,2),(1,43),(1,3),(1,27),(1,28),(1,29),(1,30),(1,105),(1,17),(1,18),(1,5),(1,6),(1,33),(1,31),(1,32),(1,23),(1,24),(1,25),(1,26),(1,132),(1,13),(1,14),(1,35),(1,82),(1,83),(1,84),(1,85),(1,131),(1,124),(1,79),(1,80),(1,110),(1,71),(1,72),(1,73),(1,74),(1,114),(1,116),(1,115),(1,130),(1,70),(1,75),(1,77),(1,76),(1,377),(1,378),(1,379),(1,376);
/*!40000 ALTER TABLE `user_permission` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Offres d'abonnement système

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `abonnement_offres` WRITE;
/*!40000 ALTER TABLE `abonnement_offres` DISABLE KEYS */;
INSERT INTO `abonnement_offres` (`id`, `code`, `nom`, `description`, `montant`, `devise`, `duree_jours`, `actif`, `created_at`, `updated_at`) VALUES (1,'mensuel','Abonnement mensuel','Accès complet à KalanNet pour un établissement pendant 30 jours.',5000.00,'XOF',30,1,'2026-05-25 10:32:32','2026-05-25 11:51:27'),(2,'trimestriel','Abonnement trimestriel','Accès complet à KalanNet pour un établissement pendant 90 jours.',10000.00,'XOF',90,1,'2026-05-25 10:32:32','2026-05-25 11:51:55'),(3,'annuel','Abonnement annuel','Accès complet à KalanNet pour un établissement pendant une année.',30000.00,'XOF',365,1,'2026-05-25 10:32:32','2026-05-25 11:52:10'),(4,'semestre','Abonnement semestriel',NULL,20000.00,'XOF',180,1,'2026-05-25 11:50:45','2026-05-25 11:50:45');
/*!40000 ALTER TABLE `abonnement_offres` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

