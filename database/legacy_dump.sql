-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : lun. 12 jan. 2026 à 19:13
-- Version du serveur : 10.4.28-MariaDB
-- Version de PHP : 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `db_gesco`
--

-- --------------------------------------------------------

--
-- Structure de la table `academie`
--

CREATE TABLE `academie` (
  `id_academie` int(11) NOT NULL,
  `nom_academie` varchar(100) NOT NULL,
  `code_academie` varchar(20) NOT NULL,
  `localite_academie` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `academie`
--

INSERT INTO `academie` (`id_academie`, `nom_academie`, `code_academie`, `localite_academie`, `created_at`, `updated_at`) VALUES
(1, 'ACADEMIE DE KAYES', 'AE-KAYES', 'KAYES', '2025-11-05 17:35:43', '2025-11-05 17:35:43'),
(4, 'ACADEMIE DE BAMAKO RIVE GAUCHE', 'AE-BKO-RG', 'BAMAKO', '2025-11-05 17:47:01', '2025-11-05 17:47:01'),
(6, 'ACADÉMIE DE BAMAKO RIVE DROITE', 'AE-BKO-RD', 'BAMAKO', '2025-11-05 18:01:10', '2025-11-05 18:01:10'),
(7, 'ACADÉMIE DE NIORO', 'AE-NIORO', 'NIORO', '2025-11-06 16:38:46', '2025-11-06 16:38:46'),
(8, 'ACADEMIE DE SEGOU', 'AE-SEGOU', 'SEGOU', '2025-11-07 11:24:44', '2025-11-07 11:24:44');

-- --------------------------------------------------------

--
-- Structure de la table `anneescolaire`
--

CREATE TABLE `anneescolaire` (
  `id_anneeScolaire` int(11) NOT NULL,
  `annee` varchar(50) DEFAULT NULL,
  `date_debut` varchar(50) DEFAULT NULL,
  `date_fin` varchar(50) DEFAULT NULL,
  `id_ecole` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `anneescolaire`
--

INSERT INTO `anneescolaire` (`id_anneeScolaire`, `annee`, `date_debut`, `date_fin`, `id_ecole`) VALUES
(1, '2024-2025', '2024-09-01', '2025-08-31', NULL),
(2, '2025-2026', '2025-09-01', '2026-08-31', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `annonces_academie`
--

CREATE TABLE `annonces_academie` (
  `id_annonce` int(11) NOT NULL,
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
  `public_cible` enum('tous','cap_only','ecoles_only') DEFAULT 'tous'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annonces_admin_gestionnaire`
--

CREATE TABLE `annonces_admin_gestionnaire` (
  `id_annonce` int(11) NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `public_cible` varchar(50) DEFAULT 'tous',
  `id_utilisateur` int(11) NOT NULL,
  `fichier_joint` varchar(255) DEFAULT NULL,
  `type_fichier` varchar(100) DEFAULT NULL,
  `taille_fichier` int(11) DEFAULT NULL,
  `date_publication` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annonces_cap`
--

CREATE TABLE `annonces_cap` (
  `id_annonce` int(11) NOT NULL,
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
  `type_annonce` enum('information','urgence','pedagogique') DEFAULT 'information'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annonces_fichiers`
--

CREATE TABLE `annonces_fichiers` (
  `id_fichier` int(11) NOT NULL,
  `id_annonce` int(11) NOT NULL,
  `type_annonce` enum('cap','academie','admin_gestionnaire') NOT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `nom_original` varchar(255) DEFAULT NULL,
  `type_mime` varchar(100) DEFAULT NULL,
  `taille` int(11) DEFAULT NULL,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annonces_lues`
--

CREATE TABLE `annonces_lues` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_annonce` int(11) NOT NULL,
  `type_annonce` enum('CAP','ACADEMIE','admin_gestionnaire') DEFAULT NULL,
  `date_lecture` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `banques`
--

CREATE TABLE `banques` (
  `id_banques` int(11) NOT NULL,
  `numero_compte` varchar(255) NOT NULL,
  `nom_banque` varchar(255) NOT NULL,
  `solde` double NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `date_creation` date NOT NULL,
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bulletin`
--

CREATE TABLE `bulletin` (
  `id_bulletin` int(11) NOT NULL,
  `trimestre` varchar(50) DEFAULT NULL,
  `annee_scolaire` date DEFAULT NULL,
  `id_note` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `caisse`
--

CREATE TABLE `caisse` (
  `id_caisse` int(11) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `created_at` date NOT NULL,
  `montant_initial` double NOT NULL,
  `montant_net` double NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cap`
--

CREATE TABLE `cap` (
  `id_cap` int(11) NOT NULL,
  `nom_cap` varchar(100) NOT NULL,
  `code_cap` varchar(20) NOT NULL,
  `localite_cap` varchar(100) NOT NULL,
  `id_academie` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cap`
--

INSERT INTO `cap` (`id_cap`, `nom_cap`, `code_cap`, `localite_cap`, `id_academie`, `created_at`, `updated_at`) VALUES
(1, 'CAP KAYES RIVE GAUCHE', 'CAP-KRG', 'KAYES', 1, '2026-01-12 15:32:06', '2026-01-12 15:32:06'),
(2, 'CAP KAYES RIVE DROITE', 'CAP-KRD', 'KAYES', 1, '2026-01-12 15:32:33', '2026-01-12 15:32:33'),
(3, 'CAP DE SEBENIKORO', 'CAP-SEBE', 'BAMAKO', 4, '2026-01-12 15:32:49', '2026-01-12 15:32:49'),
(4, 'CAP DE LAFIABOUGOU', 'CAP-LAFIA', 'BAMAKO', 4, '2026-01-12 15:33:21', '2026-01-12 15:33:21'),
(5, 'CAP DE BAFOULABE', 'CAP-BAF', 'KAYES', 1, '2026-01-12 15:33:40', '2026-01-12 15:33:40');

-- --------------------------------------------------------

--
-- Structure de la table `classe`
--

CREATE TABLE `classe` (
  `id_classe` int(11) NOT NULL,
  `nom_classe` varchar(50) DEFAULT NULL,
  `ordreEnseignement` varchar(50) DEFAULT NULL,
  `idEcole` int(11) NOT NULL,
  `id_classe_officielle` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classe`
--

INSERT INTO `classe` (`id_classe`, `nom_classe`, `ordreEnseignement`, `idEcole`, `id_classe_officielle`) VALUES
(1, '7eme année', 'fondamentale2', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `classes_officielles`
--

CREATE TABLE `classes_officielles` (
  `id_classe_officielle` int(11) NOT NULL,
  `nom_classe_officielle` varchar(255) NOT NULL,
  `ordre_enseignement` enum('Fondamentale I','Fondamentale II','Secondaire Generale','Secondaire Technique et Professionnel') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classes_officielles`
--

INSERT INTO `classes_officielles` (`id_classe_officielle`, `nom_classe_officielle`, `ordre_enseignement`) VALUES
(1, '7eme année', 'Fondamentale II');

-- --------------------------------------------------------

--
-- Structure de la table `conduite`
--

CREATE TABLE `conduite` (
  `id_conduite` int(11) NOT NULL,
  `id_annee_scolaire` int(11) DEFAULT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_trimestre` int(11) DEFAULT NULL,
  `id_eleve` int(11) DEFAULT NULL,
  `note_conduite` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `controle`
--

CREATE TABLE `controle` (
  `id_controle` int(11) NOT NULL,
  `date_controle` datetime DEFAULT NULL,
  `alertControle` varchar(100) DEFAULT NULL,
  `type_controle` varchar(100) DEFAULT NULL,
  `id_emploi_du_temps` int(11) DEFAULT NULL,
  `id_eleve` int(11) DEFAULT NULL,
  `penalite_conduite` float DEFAULT 0,
  `id_ecole` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `controle_eleve`
--

CREATE TABLE `controle_eleve` (
  `id_controle_eleve` int(11) NOT NULL,
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
  `date_enregistrement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `decaissement`
--

CREATE TABLE `decaissement` (
  `id_decaissement` int(11) NOT NULL,
  `montant_decaissement` double NOT NULL,
  `date_decaissement` date NOT NULL,
  `motif_decaissement` varchar(255) NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `id_caisse` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  `valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ecole`
--

CREATE TABLE `ecole` (
  `idEcole` int(10) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ecole`
--

INSERT INTO `ecole` (`idEcole`, `nomEcole`, `typeEcole`, `logoEcole`, `nomFondamental`, `nomLycee`, `nomProfessionnel`, `id_academie`, `id_cap`, `nomComplexe`, `cap`, `statut`, `adresse`, `telephone`, `email`, `academie`, `notification_sms`, `created_at`, `updated_at`) VALUES
(1, 'COLLEGE MODENE YELEN', 'Fondamentale II', '/images_ecoles/default_logo.png', 'COLLEGE MODENE YELEN', NULL, NULL, 1, 1, NULL, 'CAP KAYES RIVE GAUCHE', 'prive', 'KAYES-LEGAL SEGOU', '76543212', '', 'ACADEMIE DE KAYES', 1, '2026-01-12 16:13:09', '2026-01-12 16:13:09');

-- --------------------------------------------------------

--
-- Structure de la table `eleve`
--

CREATE TABLE `eleve` (
  `id_eleve` int(11) NOT NULL,
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
  `id_ecole` int(11) DEFAULT NULL,
  `nom_eleve` varchar(100) NOT NULL,
  `prenom_eleve` varchar(100) NOT NULL,
  `etat_dossier` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emargement`
--

CREATE TABLE `emargement` (
  `id_emargement` int(11) NOT NULL,
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
  `valide` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emploi_du_temps`
--

CREATE TABLE `emploi_du_temps` (
  `id` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `jour` varchar(15) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `encaissement`
--

CREATE TABLE `encaissement` (
  `id_encaissement` int(11) NOT NULL,
  `type_operation` varchar(255) DEFAULT NULL,
  `date_encaissement` date DEFAULT NULL,
  `motif_encaissement` varchar(255) DEFAULT NULL,
  `montant_encaissement` double NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `id_caisse` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

CREATE TABLE `enseignants` (
  `id_enseignant` int(11) NOT NULL,
  `nom_prenom_enseignant` varchar(200) NOT NULL,
  `genre_enseignant` varchar(50) NOT NULL,
  `email_enseignant` varchar(255) NOT NULL,
  `telephone_enseignant` varchar(100) NOT NULL,
  `date_naissance_enseignant` varchar(100) NOT NULL,
  `lieu_naissance_enseignant` varchar(100) NOT NULL,
  `diplome_enseignant` varchar(100) NOT NULL,
  `salaire_enseignant` int(100) DEFAULT NULL,
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
  `anciennete_annees` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evaluation`
--

CREATE TABLE `evaluation` (
  `id_evaluation` int(11) NOT NULL,
  `libeller` varchar(255) NOT NULL,
  `date_evaluation` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evaluationprof`
--

CREATE TABLE `evaluationprof` (
  `id_evaluation` int(11) NOT NULL,
  `id_enseignant` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `indiscipline`
--

CREATE TABLE `indiscipline` (
  `id_indiscipline` int(11) NOT NULL,
  `date_indiscipline` datetime DEFAULT NULL,
  `type_indiscipline` varchar(50) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `sanction` varchar(50) DEFAULT NULL,
  `id_eleve` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inscription`
--

CREATE TABLE `inscription` (
  `id_inscription` int(11) NOT NULL,
  `date_inscription` datetime DEFAULT NULL,
  `id_anneeScolaire` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lecons_presence`
--

CREATE TABLE `lecons_presence` (
  `id_lecon_presence` int(11) NOT NULL,
  `id_presence` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `nombre_heure` decimal(4,2) NOT NULL,
  `progression` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligneclasse`
--

CREATE TABLE `ligneclasse` (
  `id_ligneclasse` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_enseignants` int(11) DEFAULT NULL,
  `coefficient` decimal(4,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ligneclasse`
--

INSERT INTO `ligneclasse` (`id_ligneclasse`, `id_matiere`, `id_classe`, `id_enseignants`, `coefficient`) VALUES
(1, 1, 1, NULL, 2.00),
(2, 2, 1, NULL, 3.00),
(3, 3, 1, NULL, 1.50),
(4, 4, 1, NULL, 1.50),
(5, 8, 1, NULL, 3.00),
(6, 9, 1, NULL, 2.00),
(7, 10, 1, NULL, 1.00),
(8, 11, 1, NULL, 2.00),
(9, 12, 1, NULL, 2.00),
(10, 13, 1, NULL, 2.00),
(11, 14, 1, NULL, 1.00),
(12, 15, 1, NULL, 1.00),
(13, 16, 1, NULL, 2.00),
(14, 17, 1, NULL, 1.00);

-- --------------------------------------------------------

--
-- Structure de la table `ligneparents_eleves`
--

CREATE TABLE `ligneparents_eleves` (
  `id_ligneParent_eleve` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_parent` int(11) NOT NULL,
  `informer` varchar(100) NOT NULL,
  `lien_parent` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_evaluation`
--

CREATE TABLE `ligne_evaluation` (
  `id_ligneEvaluation` int(11) NOT NULL,
  `id_evaluation` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `id_trimestre` int(11) DEFAULT NULL,
  `id_note` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `note` double DEFAULT NULL,
  `id_enseignant` int(11) NOT NULL,
  `mois` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_inscription`
--

CREATE TABLE `ligne_inscription` (
  `id_inscription` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) NOT NULL,
  `id_planification` int(11) NOT NULL,
  `date_inscription` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_paiement_eleve`
--

CREATE TABLE `ligne_paiement_eleve` (
  `idligne_paiement_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) NOT NULL,
  `id_paiement` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `idEcole` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_reinscription`
--

CREATE TABLE `ligne_reinscription` (
  `id_ligne_reinscription` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) DEFAULT NULL,
  `id_reinscription` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_salaire`
--

CREATE TABLE `ligne_salaire` (
  `id_ligne_paiement` int(11) NOT NULL,
  `id_salaire` int(11) NOT NULL,
  `montant_verse` int(11) NOT NULL,
  `date_paiement` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `matiere`
--

CREATE TABLE `matiere` (
  `id_matiere` int(11) NOT NULL,
  `nom_matiere` varchar(50) DEFAULT NULL,
  `id_ecole` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matiere`
--

INSERT INTO `matiere` (`id_matiere`, `nom_matiere`, `id_ecole`) VALUES
(1, 'Sciences Naturelle', NULL),
(2, 'Mathématiques ', NULL),
(3, 'physique', NULL),
(4, 'Chimie', NULL),
(6, 'philosophie ', NULL),
(7, 'Français ', NULL),
(8, 'Rédaction ', NULL),
(9, 'Anglais', NULL),
(10, 'Musique', NULL),
(11, 'ECM', NULL),
(12, 'Histoire ', NULL),
(13, 'Géographie ', NULL),
(14, 'Lecture', NULL),
(15, 'Récitation ', NULL),
(16, 'Dictée et Question', NULL),
(17, 'Grammaire', NULL),
(18, 'LV2', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `matiere_ordre`
--

CREATE TABLE `matiere_ordre` (
  `id` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `ordre_enseignement` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matiere_ordre`
--

INSERT INTO `matiere_ordre` (`id`, `id_matiere`, `ordre_enseignement`) VALUES
(1, 1, 'Fondamentale II'),
(2, 1, 'Secondaire Generale'),
(3, 2, 'Fondamentale II'),
(4, 2, 'Secondaire Generale'),
(5, 2, 'Secondaire Technique et Professionnel'),
(6, 3, 'Fondamentale II'),
(7, 3, 'Secondaire Generale'),
(8, 3, 'Secondaire Technique et Professionnel'),
(9, 4, 'Fondamentale II'),
(10, 4, 'Secondaire Generale'),
(11, 4, 'Secondaire Technique et Professionnel'),
(12, 6, 'Secondaire Generale'),
(18, 9, 'Fondamentale II'),
(19, 9, 'Secondaire Generale'),
(20, 9, 'Secondaire Technique et Professionnel'),
(23, 11, 'Fondamentale I'),
(24, 11, 'Fondamentale II'),
(25, 11, 'Secondaire Generale'),
(26, 12, 'Fondamentale I'),
(27, 12, 'Fondamentale II'),
(28, 12, 'Secondaire Generale'),
(29, 13, 'Fondamentale I'),
(30, 13, 'Fondamentale II'),
(31, 13, 'Secondaire Generale'),
(32, 10, 'Fondamentale I'),
(33, 10, 'Fondamentale II'),
(34, 10, 'Secondaire Generale'),
(35, 14, 'Fondamentale I'),
(36, 14, 'Fondamentale II'),
(37, 15, 'Fondamentale I'),
(38, 15, 'Fondamentale II'),
(39, 16, 'Fondamentale I'),
(40, 16, 'Fondamentale II'),
(41, 8, 'Fondamentale I'),
(42, 8, 'Fondamentale II'),
(43, 8, 'Secondaire Generale'),
(44, 7, 'Secondaire Generale'),
(45, 7, 'Secondaire Technique et Professionnel'),
(46, 17, 'Fondamentale I'),
(47, 17, 'Fondamentale II'),
(48, 17, 'Secondaire Generale'),
(49, 18, 'Secondaire Generale'),
(50, 18, 'Secondaire Technique et Professionnel');

-- --------------------------------------------------------

--
-- Structure de la table `moyenne_eleve`
--

CREATE TABLE `moyenne_eleve` (
  `id_moyenne` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_trimestre` int(11) DEFAULT NULL,
  `id_anneeScolaire` int(11) NOT NULL,
  `moyenne` decimal(5,2) NOT NULL DEFAULT 0.00,
  `rang` int(11) NOT NULL DEFAULT 0,
  `mois` varchar(100) DEFAULT NULL,
  `valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `note`
--

CREATE TABLE `note` (
  `id_note` int(11) NOT NULL,
  `typeNote` varchar(100) DEFAULT NULL,
  `codeNote` varchar(100) DEFAULT NULL,
  `valeur` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `statut` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `id_paiement` int(11) NOT NULL,
  `montant` int(11) DEFAULT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `motif` varchar(50) DEFAULT NULL,
  `id_classe` int(11) NOT NULL,
  `id_annee` int(11) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `idEcole` int(11) NOT NULL,
  `id_eleve` int(11) DEFAULT NULL,
  `parent` varchar(50) DEFAULT NULL,
  `nom_payeur` varchar(100) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `id_caisse` int(11) DEFAULT NULL,
  `numero_recu` int(11) DEFAULT NULL,
  `id_planification` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parents`
--

CREATE TABLE `parents` (
  `id_parent` int(11) NOT NULL,
  `nom_prenom_parent` varchar(255) DEFAULT NULL,
  `email_parent` varchar(50) DEFAULT NULL,
  `telephone_parent` varchar(50) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `idEcole` int(11) DEFAULT NULL,
  `pwd` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `name`) VALUES
(1, 'enseignants_apercu'),
(2, 'enseignants_création'),
(3, 'enseignants_modification'),
(4, 'enseignants_archiver ou réactiver'),
(5, 'matieres_apercu'),
(6, 'matieres_creation'),
(7, 'matieres_modif'),
(8, 'matieres_supp'),
(9, 'classes_apercu'),
(10, 'classes_creation'),
(11, 'classes_modification'),
(12, 'classes_supprimer'),
(13, 'planning_apercu'),
(14, 'planning_création'),
(15, 'planning_supp'),
(16, 'bulletins_acces bulletin'),
(17, 'inscription_inscrire'),
(18, 'inscription_reinscrire'),
(19, 'eleves_apercu'),
(20, 'eleves_modification'),
(21, 'eleves_suppression'),
(22, 'eleves_dossier'),
(23, 'parents_apercu'),
(24, 'parents_création'),
(25, 'parents_modification'),
(26, 'parents_supprimer'),
(27, 'evaluation_apercu'),
(28, 'evaluation_création'),
(29, 'evaluation_modification'),
(30, 'evaluation_supprimer'),
(31, 'paiements_apercu'),
(32, 'paiements_faire'),
(33, 'paiements_annuler'),
(34, 'configurations_acces au configuration'),
(35, 'planning_imprimer'),
(36, 'controle_apercu'),
(37, 'controle_création'),
(38, 'controle_modification'),
(39, 'emargement_apercu'),
(40, 'emargement_faire'),
(41, 'emargement_paiement enseignant'),
(42, 'emargement_modification'),
(43, 'enseignants_emploi'),
(44, 'matieres_action'),
(45, 'emargement_etat de payement'),
(46, 'emargement_suppresion'),
(47, 'Planification de paiements_apercu'),
(48, 'Planification de paiements_création'),
(49, 'Planification de paiements_modification'),
(50, 'Planification de paiements_suppression'),
(51, 'emargement_validation_admin'),
(52, 'caisses_apercu'),
(53, 'caisses_création'),
(54, 'caisses_modification'),
(55, 'banques_apercu'),
(56, 'banques_création'),
(57, 'banques_modification'),
(58, 'banques_suppression'),
(59, 'encaissement_apercu'),
(60, 'encaissement_création'),
(61, 'encaissement_modification'),
(62, 'encaissement_suppression'),
(63, 'decaissements_apercu'),
(64, 'decaissements_création'),
(65, 'decaissements_modification'),
(66, 'decaissements_suppression'),
(67, 'mouvements_apercu'),
(69, 'decaissements_validation'),
(70, 'versements_apercu'),
(71, 'retraits_apercu'),
(72, 'retraits_création'),
(73, 'retraits_modification'),
(74, 'retraits_suppression'),
(75, 'versements_création'),
(76, 'versements_suppression'),
(77, 'versements_modification'),
(78, 'programme_apercu'),
(79, 'programme_création'),
(80, 'programme_modification'),
(81, 'programme_activer ou réactiver'),
(82, 'presence_apercu'),
(83, 'presence_création'),
(84, 'presence_modification'),
(85, 'presence_suppression'),
(86, 'presence_paiement enseignant'),
(87, 'presence_etat de payement'),
(88, 'classes_programme_officiel'),
(90, 'dcap_apercu'),
(91, 'dcap_voiraction'),
(92, 'dae_apercu'),
(93, 'dae_voiraction'),
(94, 'dae_permission'),
(95, 'dae_activer'),
(96, 'dae_modifier'),
(97, 'dcap_permission'),
(98, 'dcap_modifier'),
(99, 'dcap_activer'),
(100, 'administrateur_tabsConfig'),
(101, 'enseignants_tabsConfig'),
(102, 'parents_tabsConfig'),
(103, 'documents_apercu'),
(104, 'documents_manage'),
(105, 'inscriptions_apercu'),
(106, 'planifications_apercu'),
(108, 'dossiers_eleves_apercu'),
(110, 'reinscriptions_apercu'),
(114, 'status_controles_apercu'),
(115, 'types_notes_apercu'),
(116, 'trimestres_apercu'),
(122, 'classes_officielles_apercu'),
(124, 'programmes_apercu'),
(129, 'academies_apercu'),
(130, 'utilisateurs_apercu'),
(131, 'profiles_apercu'),
(132, 'permissions_apercu'),
(133, 'annees_scolaires_apercu'),
(135, 'pdf_access');

-- --------------------------------------------------------

--
-- Structure de la table `planification`
--

CREATE TABLE `planification` (
  `id_planification` int(11) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_annee` int(11) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `montant_planification` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presences`
--

CREATE TABLE `presences` (
  `id_presence` int(11) NOT NULL,
  `id_enseignant` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `date_presence` datetime NOT NULL,
  `nombre_heure` decimal(5,2) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `id_anneeScolaire` int(11) NOT NULL,
  `id_ecole` int(11) DEFAULT NULL,
  `valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `programmes_officiels`
--

CREATE TABLE `programmes_officiels` (
  `id_programme` int(11) NOT NULL,
  `date_creation` datetime NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `officiel` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `programmes_officiels`
--

INSERT INTO `programmes_officiels` (`id_programme`, `date_creation`, `id_utilisateur`, `officiel`) VALUES
(1, '2025-07-07 20:13:52', 1, 1),
(2, '2025-07-10 08:52:08', 1, 1),
(3, '2025-07-10 09:11:11', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `programme_classes`
--

CREATE TABLE `programme_classes` (
  `id_programme_classe` int(11) NOT NULL,
  `id_programme` int(11) NOT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_matiere` int(11) NOT NULL,
  `pour_toutes_ecoles` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `programme_classes`
--

INSERT INTO `programme_classes` (`id_programme_classe`, `id_programme`, `id_classe`, `id_matiere`, `pour_toutes_ecoles`) VALUES
(1, 1, 1, 2, 0),
(2, 1, 1, 3, 0),
(3, 1, 1, 4, 0),
(4, 1, 1, 1, 0),
(5, 1, 1, 14, 0),
(6, 1, 1, 8, 0),
(7, 1, 1, 17, 0),
(8, 1, 1, 13, 0),
(9, 1, 1, 12, 0),
(10, 2, NULL, 7, 0),
(11, 2, NULL, 9, 0),
(12, 2, NULL, 3, 0),
(13, 2, NULL, 4, 0),
(14, 2, NULL, 1, 0),
(15, 2, NULL, 2, 0),
(16, 2, NULL, 12, 0),
(17, 2, NULL, 13, 0),
(18, 3, NULL, 14, 0),
(19, 3, NULL, 17, 0);

-- --------------------------------------------------------

--
-- Structure de la table `programme_lecons`
--

CREATE TABLE `programme_lecons` (
  `id_lecon` int(11) NOT NULL,
  `id_programme_classe` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `programme_lecons`
--

INSERT INTO `programme_lecons` (`id_lecon`, `id_programme_classe`, `numero`, `titre`) VALUES
(1, 1, 1, 'Entiers Naturels'),
(2, 1, 2, 'Comparaison de 2 naturels'),
(3, 2, 1, 'Les 3 états de la matière :état solide, état  liquide, état gazeux'),
(4, 2, 2, 'Propriétés physiques de la matière'),
(5, 2, 3, 'Volumes des solides et des liquides'),
(6, 2, 4, 'Masse d’un corps'),
(7, 2, 5, 'Masse d’un corps : simple et double pesée – Masses manquées'),
(8, 2, 6, 'Changement d’état physique'),
(9, 2, 7, 'Masse volumique des solides, des liquides'),
(10, 2, 8, 'Notion de température'),
(11, 2, 9, 'Electricité : circuitélectrique simple'),
(12, 2, 10, 'Circuit électrique simple et schéma'),
(13, 2, 11, 'Montage d’ampoules en série, en parallèle'),
(14, 3, 1, 'Eaux Naturelles'),
(15, 3, 2, 'Mélanges et corps purs'),
(16, 3, 3, 'Etude de l’air-composition de l’air'),
(17, 3, 4, 'Combustion : Etude de la combustion du carbone, du soufre des  combustibles habituelles caractérisations des produits formés'),
(18, 3, 5, 'Rôle de l’oxygène dans les combustions'),
(19, 3, 6, 'Dangers des combustions'),
(20, 3, 7, 'Etude de la décomposition de l’eau pure par électrolyse'),
(21, 3, 8, 'Notion de symbolisme (se limiter aux corps purs simples)'),
(22, 3, 9, 'Gaz carbonique'),
(23, 3, 10, 'Sel de cuisine'),
(24, 4, 1, 'Organisation générale du corps humain'),
(25, 4, 2, 'Le squelette de l’homme'),
(26, 4, 3, 'La dent : Structure, Notion de formule dentaire'),
(27, 4, 4, 'Les mammifères :(La souris)'),
(28, 4, 5, 'Les oiseaux'),
(29, 4, 6, 'Les reptiles : le margouillat'),
(30, 4, 7, 'Les batraciens : La grenouille'),
(31, 4, 8, 'Les poissons : carpe ou capitaine'),
(32, 4, 9, 'Caractères communs à l’embranchement des vertébrés'),
(33, 4, 10, 'Introduction à la botanique'),
(34, 4, 11, 'Etude morphologique de l’appareil reproducteur :du Flamboyant'),
(35, 4, 12, 'le cotonnier'),
(36, 4, 13, 'L’arachide'),
(37, 4, 14, 'Etude comparée de l’appareil reproducteur de quelques plantes à fleurs'),
(38, 4, 15, 'Etude pratique d’une plante sans fleurs : Fougère'),
(39, 4, 16, 'Notion de peuplement des milieux : l’homme et la nature'),
(40, 4, 17, 'La désertification'),
(41, 4, 18, 'La pollution'),
(42, 1, 3, 'Addition dans IN'),
(43, 1, 4, 'Droites parallèles'),
(44, 1, 5, 'Soustraction dans IN'),
(45, 1, 6, 'Les droites perpendiculaires'),
(46, 1, 7, 'Multiplication dans IN'),
(47, 1, 8, 'Puissance d’un entier naturel'),
(48, 1, 9, 'Eléments de géométrie dans l’espace'),
(49, 1, 10, 'Caractères de divisibilité'),
(50, 1, 11, 'Secteur angulaire'),
(51, 1, 12, 'Caractères de divisibilité ('),
(52, 1, 13, 'Nombres premiers'),
(53, 1, 14, 'Repérage'),
(54, 1, 15, 'Ensemble dans Z'),
(55, 1, 16, 'Addition dans Z'),
(56, 1, 17, 'Triangle'),
(57, 1, 18, 'Quadrilatères'),
(59, 1, 20, 'Multiplication dans Z'),
(60, 1, 21, 'Puissance des nombres relatifs'),
(61, 1, 22, 'Cercle-Disque'),
(62, 1, 23, 'Nombres décimaux'),
(63, 1, 24, 'Polyèdre'),
(64, 1, 24, 'Exercices'),
(65, 1, 25, 'Devoirs'),
(66, 1, 26, 'Composition'),
(67, 2, 12, 'Exercices'),
(68, 2, 13, 'Devoirs'),
(69, 2, 14, 'Composition'),
(70, 3, 11, 'Exercices'),
(71, 3, 12, 'Devoirs'),
(72, 3, 13, 'Composition'),
(73, 4, 19, 'Exercices'),
(74, 4, 20, 'Devoir'),
(75, 4, 21, 'Composition'),
(76, 5, 1, 'Poésie :Extraits des Fables de la Fontaine'),
(77, 5, 2, 'Poésie :Poèmes simples de David Diop'),
(78, 5, 3, 'Contes :Le pagne noir : B. Dadié'),
(79, 5, 4, 'Contes et récits du terroir : Issa Baba Traoré'),
(80, 5, 5, 'Contes:	Le mariage du premier homme (conte malgache – la 6ème  en français EDICEF, p.88).'),
(81, 5, 6, 'Théâtre : Monsieur Thôgô-gnini : B. Dadié'),
(82, 5, 7, 'Théâtre :Une si belle leçon de patience : Massa Makan Diabaté'),
(83, 5, 8, 'Portraits physiques et moraux:  La Besace : La Fontaine'),
(84, 5, 9, 'Portraits physiques et moraux: Les caractères : Gnaton, Ménalque : La Bruyère'),
(85, 5, 10, 'Portraits physiques et moraux :Le rescapé de l’Ethylos : Mamadou Gologo'),
(86, 5, 11, 'Enfance et adolescence : Les deux amis : La Fontaine (fables, livre VIII)'),
(87, 5, 12, 'Enfance et adolescence :L’enfant noir : Camara Laye'),
(88, 5, 13, 'Enfance et adolescence :Black Boy : Richard Wright'),
(89, 5, 14, 'Afrique des savanes, Afrique des forêts : l’eau et l’arbre : •	L’eau maîtresse de la vie et de la mort : Chinua Achebé'),
(90, 5, 15, 'Afrique des savanes, Afrique des forêts :Le chant des rameurs : B. Diop •	La vengeance de l’arbre sacré : J. Pliya •	Le reboisement : Pourquoi planter des arbres (La 6ème en français EDICEF, p. 202).'),
(91, 5, 16, 'Afrique des savanes, Afrique des forêts :L’enfant et la rivière : Henri Bosco'),
(92, 6, 1, 'Construction de phrase et paragraphe'),
(93, 6, 2, 'Description'),
(94, 6, 3, 'Récits et Narration'),
(95, 7, 1, 'le nom'),
(96, 7, 2, 'le genre du nom'),
(97, 7, 3, 'le nombre du nom'),
(98, 7, 4, 'les déterminants : les articles'),
(99, 7, 5, 'les déterminants : les adjectifs possessif, démonstratif, indéfini'),
(100, 7, 6, 'les déterminants : L’adjectif qualificatif et les groupes nominaux prépositionnels'),
(101, 7, 7, 'les pronoms personnels sujets'),
(102, 7, 8, 'les pronoms personnels compléments : lui, elle, elles, leur, eux, en et y •	emploi de en et de y'),
(103, 7, 9, 'les pronoms possessif, démonstratif, relatif, indéfini.'),
(104, 7, 10, 'la fonction épithète'),
(105, 7, 11, 'la fonction déterminative'),
(106, 7, 12, 'la fonction appositive'),
(107, 8, 1, 'La terre dans l’espace – Forme et dimension.'),
(108, 8, 2, 'L’orientation : localisation d’un point à la surface de la terre.'),
(109, 8, 3, 'Les mouvements de la terre et leurs conséquences  géographiques.'),
(110, 8, 4, 'La représentation de la terre : les cartes, leur établissement et leur utilisation.'),
(111, 8, 5, 'Une heure de T.P. réservée à la lecture de la carte simple.'),
(112, 8, 6, 'L’atmosphère : définition – constitution.'),
(113, 8, 7, 'Les éléments du climat : températures, pressions, vents, précipitations (Evoquer la notion de climat)'),
(114, 8, 8, 'Les grandes zones de climat et de végétation.'),
(115, 8, 9, 'Le relief : description et définition des principales formes du relief : plaine, plateau, montagne, figuration du relief sur carte.'),
(116, 8, 10, 'L’écoulement des eaux de pluie : infiltration, ruissellement.'),
(117, 8, 11, 'Les cours d’eau : description – débits et régimes (T.P.).'),
(118, 8, 12, 'L’érosion – érosion par ruissellement, érosion fluviale et éolienne.'),
(119, 8, 13, 'La population du globe : notions de répartition, de densité, de race, de natalité, mortalité, accroissement naturel, migrations.'),
(120, 8, 14, 'Les différents types d’activités humaines (agriculture, élevage, industrie, transport, commerce).'),
(121, 9, 1, 'Objet de l’histoire – Notion de chronologie et divisions de l’histoire. Les       sources de connaissance de l’histoire (insister sur la tradition orale dans le        cas de l’Afrique).'),
(122, 9, 2, 'La préhistoire – importance de la préhistoire africaine (sites        préhistoriques).'),
(123, 9, 3, 'Le monde paléolithique.'),
(124, 9, 4, 'La révolution du néolithique : les nouvelles techniques et la division du        travail – la diffusion de l’agriculture et la sédentarisation, le travail des       métaux.'),
(125, 9, 5, 'L’Afrique néolithique – La civilisation néolithique du Sahara et ses        manifestations artistiques (art rupestre) – Conséquences de       l’assèchement du Sahara (migration, nouveaux foyers de civilisations) : la       civilisation de Nok.'),
(126, 9, 6, 'L’Egypte : le pays, les grandes périodes de l’histoire égyptienne.'),
(127, 9, 7, 'La civilisation égyptienne – la vie économique et sociale.'),
(128, 9, 8, 'Les civilisations africaines à la fin du IVè siècle : Nubie, Axoum.'),
(129, 9, 9, 'La Grèce : la vie politique, économique, artistique et religieuse.'),
(130, 9, 10, 'La formation de l’empire romain.'),
(131, 9, 11, 'La civilisation romaine sous l’empire.'),
(132, 9, 12, 'Le christianisme dans l’empire romain : naissance et expansion.'),
(133, 9, 13, 'L’Afrique romaine.'),
(134, 9, 14, 'La fin de l’unité du monde romain : les invasions germaniques – La          formation de nouveaux royaumes en Occident.'),
(135, 10, 1, 'Problèmes d’aujourd’hui(L’os de la parole Adam Bâh Konaré)'),
(136, 10, 2, 'Problèmes d’aujourd’hui( Soundjata ou l’Epopée Mandingue. D.T. Niane.'),
(137, 10, 3, 'Textes explicatifs(-Manuels -Dictionnaire ,-Articles de presse ,-Documents sur la santé, le sport, la culture.'),
(138, 10, 4, 'Textes informatifs.(-Manuels -Dictionnaire ,-Articles de presse ,-Documents sur la santé, le sport, la culture.'),
(139, 11, 1, 'Functions'),
(140, 11, 2, 'Topics'),
(141, 11, 3, 'Situation problems'),
(142, 11, 4, 'Grammar structures'),
(143, 12, 1, 'Forces (définition, caractérisation, représentation    vectorielle, classification) ;'),
(144, 12, 2, 'Mesure de forces (instruments de mesure, unité de mesure,  mesure) ;'),
(145, 12, 3, 'Principe des actions réciproques (énoncé, cas d’interaction localisée) ;'),
(146, 12, 4, 'Equilibre d’un solide soumis à différentes forces;'),
(147, 12, 5, 'Poids et masse d’un corps (définitions, caractéristiques, unité, variation du poids avec le lieu, invariance de la masse avec le lieu, relation entre masse et poids) ;'),
(148, 12, 6, 'Tension d’un fil ou d’un ressort (définition, équilibre d’un solide suspendu à un fil ou à un ressort) ;'),
(149, 12, 7, 'Action d’un plan sur un corps (support horizontal, plan incliné dans le cas de frottements négligeables) ;'),
(150, 12, 8, 'Forces s’exerçant sur un corps capable de tourner autour d’un axe :'),
(151, 12, 9, 'Moment d’une force par rapport à un axe fixe (définition, expression, unité)'),
(152, 12, 10, 'Couple de forces (définition, exemples) ;'),
(153, 12, 11, 'Moment d’un couple (définition, expression, unité) ;'),
(154, 12, 12, 'Condition d’équilibre d’un solide susceptible de tourner autour d’un axe (équilibre d’un solide  soumis à des forces, théorème des moments);'),
(155, 12, 13, 'Cinématique (définition, repères et trajectoires, vitesses, accélérations).'),
(156, 13, 1, 'Structure de l’atome (définition, constituants, couche électronique ou niveau d’énergie et règles élémentaires de répartition des électrons) ;'),
(157, 13, 2, 'Numéro atomique, nombre de masse (définitions, neutralité électrique de l’atome) ;'),
(158, 13, 3, 'L’élément chimique (définition, représentations  et de Lewis, notion d’isotopie) ;'),
(159, 13, 4, 'Classification périodique des éléments (historique, constitution, propriétés, notion de familles, intérêt) ;'),
(160, 13, 5, 'Liaisons chimiques : définition, mécanisme de formation des liaisons chimiques (covalentes simple et multiple, liaison s ionique, semi polaire) ;'),
(161, 13, 6, 'Réactions chimiques : définition, équation-bilan, règle d’écriture, proportions stœchiométrique et non  stœchiométrique, lois de conservation (qualitative et quantitative) ;'),
(162, 13, 7, 'La mole : Définitions de la mole, de la masse molaire, du volume molaire, loi d’Avogadro Ampère'),
(163, 13, 8, 'Les solutions :La dissolution •	Solvant, soluté, solution, miscibilité •	Concentration massique, molaire •	Solubilité'),
(164, 13, 9, 'Les Solutions acides, les solutions basiques : •	Définitions, •	Propriétés •	Notion  de pH.'),
(165, 14, 1, 'Ecologie : -	définition ; -	facteurs  écologiques ; -	caractéristiques d’un écosystème ; -	chaîne alimentaire ; -	adaptation des êtres vivants à leur milieu.'),
(166, 14, 2, 'Le microscope et la loupe. -	Description du microscope. -	Utilisation du microscope. -	Description de la loupe. -	Utilisation de la loupe.'),
(167, 14, 3, 'Organisation de la cellule -	Cellule animale. -	Cellule végétale. -	Cellule procaryote, cellule eucaryote.'),
(168, 14, 4, 'Vie cellulaire. -	Rôles des membranes cellulaires, du noyau, des organiques cytoplasmiques. -	Mitose : étapes, notion de reproduction conforme.'),
(169, 14, 5, 'Les tissus -	Tissus animaux. -	Tissus végétaux.'),
(170, 14, 6, 'Organisation générale du système nerveux. -	Système nerveux neurovégétatif. -	Système nerveux de relation. -	Aires corticales. -	Nerfs et neurones.'),
(171, 15, 1, 'Trigonométrie'),
(172, 15, 2, 'Fonction numérique d’une variable réelle'),
(173, 15, 3, 'Equations et inéquations'),
(174, 15, 4, 'Logique'),
(175, 15, 5, 'Géométrie plane'),
(176, 15, 6, 'Transformations et isométries du plan'),
(177, 15, 7, 'Géométrie dans l’espace'),
(178, 16, 1, 'Histoire : -	Définition de l’histoire -	Problème de périodisation -	Sociétés préhistoriques -	Sociétés traditionnelles -	Croyances animistes'),
(179, 16, 2, 'Histoire     - Grandes civilisations antiques : Egypte  -	Fondements historiques de notre société et ceux de l’Afrique : les Empires du Soudan Occidental et les Etats du XIX ème siècle -	Relations entre l’Afrique et l’Europe : la traite des Noirs'),
(180, 16, 3, 'Histoire : -	Concepts clefs : notion de temps, de chronologie, d’ère, de période, de sources historiques -	Enquête : Techniques de recherche, étude documentaire. -	Visite de site -	Conférence (personnes ressources)'),
(181, 17, 1, 'Population : répartition, structures -	Grands secteurs d’activités -	Pays riches, pays pauvres -	Faim dans le monde -	Problème de l’eau dans le sahel'),
(182, 17, 2, 'Terre : situation, forme, constitution -	Mouvements de la Terre et leurs conséquences -	Représentation de la Terre : carte, globe -	Climats : atmosphère, éléments du climat, circulation générale de l’atmosphère -	Milieux biogéographiques  -	Modelé déserti'),
(183, 17, 3, '-	Migrations -	Tourisme'),
(184, 17, 4, '-	Concepts clés : localisation, cycle de l’eau, relations, paysages, débit, régime, réseaux, habitat, démographie, hydrographie. -	Travaux pratiques : calcul des coordonnées géographiques, calcul de l’heure, diagrammes, pyramides des âges. -	Enquête : Tec'),
(185, 18, 1, 'Poésie : Poèmes simples de Guy Tirolien'),
(186, 18, 2, 'Poésie : Poèmes simples de Bernard Dadié'),
(187, 18, 3, 'Contes : Contes et nouveaux contes d’Amadou Koumba : B. Diop'),
(188, 18, 4, 'Théâtre : L’Avare : Molière'),
(189, 19, 1, 'Etude du verbe :le verbe'),
(190, 19, 2, 'Etude du verbe :le mode indicatif'),
(191, 19, 3, 'Etude du verbe:le présent'),
(192, 19, 4, 'les futurs : le futur simple et le futur antérieur'),
(193, 19, 5, 'les temps du passé'),
(194, 19, 6, 'les modes : le conditionnel  -  le subjonctif'),
(195, 19, 7, 'les modes : l’impératif  -  l’infinitif  -  le participe'),
(196, 19, 8, 'tournures pronominale et impersonnelle'),
(197, 19, 9, 'le sens du verbe : sens transitif  -  sens intransitif');

-- --------------------------------------------------------

--
-- Structure de la table `reinscription`
--

CREATE TABLE `reinscription` (
  `id_reinscription` int(11) NOT NULL,
  `statut` varchar(20) NOT NULL,
  `date_reinscription` date NOT NULL,
  `enrolement` tinyint(1) DEFAULT 0,
  `moyenneGeneral` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `resultats_def_terminal`
--

CREATE TABLE `resultats_def_terminal` (
  `id` int(11) NOT NULL,
  `id_eleve` int(11) DEFAULT NULL,
  `id_annee` int(11) DEFAULT NULL,
  `niveau_examen` enum('DEF','BAC') DEFAULT NULL,
  `decision` enum('admis','échec') DEFAULT NULL,
  `moyenne` float(5,2) DEFAULT NULL,
  `observation` varchar(255) DEFAULT NULL,
  `date_resultat` date DEFAULT NULL,
  `id_classe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `retrait`
--

CREATE TABLE `retrait` (
  `id_retrait` int(11) NOT NULL,
  `id_banque` int(11) NOT NULL,
  `date_retrait` date NOT NULL,
  `montant_retrait` decimal(15,2) NOT NULL,
  `motif_retrait` text DEFAULT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'SuperAdmin');

-- --------------------------------------------------------

--
-- Structure de la table `role_permission`
--

CREATE TABLE `role_permission` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `salaire`
--

CREATE TABLE `salaire` (
  `id_salaire` int(11) NOT NULL,
  `type_paiement` varchar(50) DEFAULT NULL,
  `reference` varchar(100) NOT NULL,
  `montant_a_payer` int(11) NOT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `id_inscription` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `mois` varchar(255) DEFAULT NULL,
  `annee` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transfert`
--

CREATE TABLE `transfert` (
  `id_transfert` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_ecole` int(11) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `travail` varchar(255) DEFAULT NULL,
  `conduite` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `trimestre`
--

CREATE TABLE `trimestre` (
  `id_trimestre` int(11) NOT NULL,
  `nom_trimestre` varchar(255) NOT NULL,
  `id_ecole` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `trimestre`
--

INSERT INTO `trimestre` (`id_trimestre`, `nom_trimestre`, `id_ecole`) VALUES
(1, '1 trimestre', 1),
(2, '2 trimestre', NULL),
(3, '3 trimestre', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_permission`
--

CREATE TABLE `user_permission` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user_permission`
--

INSERT INTO `user_permission` (`user_id`, `permission_id`) VALUES
(1, 129),
(1, 100),
(1, 133),
(1, 55),
(1, 56),
(1, 57),
(1, 58),
(1, 16),
(1, 52),
(1, 53),
(1, 54),
(1, 9),
(1, 10),
(1, 11),
(1, 122),
(1, 88),
(1, 12),
(1, 34),
(1, 36),
(1, 37),
(1, 38),
(1, 95),
(1, 92),
(1, 96),
(1, 94),
(1, 93),
(1, 99),
(1, 90),
(1, 98),
(1, 97),
(1, 91),
(1, 63),
(1, 64),
(1, 65),
(1, 66),
(1, 69),
(1, 103),
(1, 104),
(1, 108),
(1, 19),
(1, 22),
(1, 20),
(1, 21),
(1, 39),
(1, 45),
(1, 40),
(1, 42),
(1, 41),
(1, 46),
(1, 51),
(1, 59),
(1, 60),
(1, 61),
(1, 62),
(1, 1),
(1, 4),
(1, 2),
(1, 43),
(1, 3),
(1, 101),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 105),
(1, 17),
(1, 18),
(1, 44),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 67),
(1, 33),
(1, 31),
(1, 32),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 102),
(1, 135),
(1, 132),
(1, 47),
(1, 48),
(1, 49),
(1, 50),
(1, 106),
(1, 13),
(1, 14),
(1, 35),
(1, 15),
(1, 82),
(1, 83),
(1, 87),
(1, 84),
(1, 86),
(1, 85),
(1, 131),
(1, 124),
(1, 81),
(1, 78),
(1, 79),
(1, 80),
(1, 110),
(1, 71),
(1, 72),
(1, 73),
(1, 74),
(1, 114),
(1, 116),
(1, 115),
(1, 130),
(1, 70),
(1, 75),
(1, 77),
(1, 76),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 43),
(3, 101),
(3, 5),
(3, 9),
(3, 10),
(3, 11),
(3, 12),
(3, 88),
(3, 13),
(3, 14),
(3, 15),
(3, 35),
(3, 16),
(3, 17),
(3, 18),
(3, 19),
(3, 20),
(3, 21),
(3, 22),
(3, 23),
(3, 24),
(3, 25),
(3, 26),
(3, 102),
(3, 27),
(3, 28),
(3, 29),
(3, 30),
(3, 31),
(3, 32),
(3, 33),
(3, 34),
(3, 36),
(3, 37),
(3, 38),
(3, 39),
(3, 40),
(3, 41),
(3, 42),
(3, 45),
(3, 46),
(3, 51),
(3, 47),
(3, 48),
(3, 49),
(3, 50),
(3, 52),
(3, 53),
(3, 54),
(3, 55),
(3, 56),
(3, 57),
(3, 58),
(3, 59),
(3, 60),
(3, 61),
(3, 62),
(3, 63),
(3, 64),
(3, 65),
(3, 66),
(3, 69),
(3, 67),
(3, 70),
(3, 75),
(3, 76),
(3, 77),
(3, 71),
(3, 72),
(3, 73),
(3, 74),
(3, 82),
(3, 83),
(3, 84),
(3, 85),
(3, 86),
(3, 87),
(3, 100),
(3, 103),
(3, 104),
(3, 105),
(3, 106),
(3, 108),
(3, 110),
(3, 114),
(3, 115),
(3, 116),
(3, 124),
(3, 129),
(3, 130),
(3, 131),
(3, 132),
(3, 133),
(3, 135);

-- --------------------------------------------------------

--
-- Structure de la table `user_role`
--

CREATE TABLE `user_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `idUtilisateur` int(10) NOT NULL,
  `nomPrenom` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `pwd` varchar(200) DEFAULT NULL,
  `fonction` varchar(50) DEFAULT NULL,
  `telephone` int(10) DEFAULT NULL,
  `genre` varchar(20) DEFAULT NULL,
  `droit` varchar(100) DEFAULT NULL,
  `idEcole` int(11) DEFAULT NULL,
  `id_academie` int(11) DEFAULT NULL,
  `id_cap` int(11) DEFAULT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `id_parent` int(11) DEFAULT NULL,
  `id_role` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `statut` int(11) NOT NULL DEFAULT 1,
  `derniere_connexion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`idUtilisateur`, `nomPrenom`, `email`, `pwd`, `fonction`, `telephone`, `genre`, `droit`, `idEcole`, `id_academie`, `id_cap`, `id_enseignant`, `id_parent`, `id_role`, `image`, `statut`, `derniere_connexion`) VALUES
(1, 'Moustapha BARRY', 'barrymoustapha908@gmail.com', '$2y$10$FAj.YYT1/1A9xRxhlrfQE.yoYSGuDULtkKvmK.0uhMRxgG3fIYhze', 'Developpeur', 74745669, 'masculin', 'SupAdmin', 1, NULL, NULL, NULL, NULL, NULL, 'default.png', 1, '2026-01-12 17:41:05'),
(2, 'Karifa DOUMBIA', 'bintoufah@gmail.com', '$2y$10$LDoiwocFZCbmGQY/B9sGwO8URpuP25yfW7krxELHsZ.VPP5v5/2e2', 'Superadmin', 76543212, 'masculin', 'SupAdmin', NULL, NULL, NULL, NULL, NULL, NULL, 'default.png', 1, '2026-01-12 15:48:49'),
(3, 'Idi DIALLO', 'ididiallo@gmail.com', '$2y$10$GWYCnI8mZ8JbjRKYxEUrb.nAyiRS0mkIhBZrLOAZ6JI2n5hivz6uO', 'Promoteur', 76543212, 'masculin', 'Admin', 1, NULL, NULL, NULL, NULL, NULL, 'profile_696520baa5e3a2.60492962.jpg', 1, '2026-01-12 18:10:24');

-- --------------------------------------------------------

--
-- Structure de la table `versement`
--

CREATE TABLE `versement` (
  `id_versement` int(11) NOT NULL,
  `date_versement` date NOT NULL,
  `motif_versement` varchar(255) NOT NULL,
  `montant_versement` double NOT NULL,
  `id_banque` int(11) NOT NULL,
  `id_annee_scolaire` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `academie`
--
ALTER TABLE `academie`
  ADD PRIMARY KEY (`id_academie`),
  ADD UNIQUE KEY `code_academie` (`code_academie`),
  ADD KEY `idx_academie_nom` (`nom_academie`),
  ADD KEY `idx_academie_code` (`code_academie`);

--
-- Index pour la table `anneescolaire`
--
ALTER TABLE `anneescolaire`
  ADD PRIMARY KEY (`id_anneeScolaire`),
  ADD KEY `id_ecole` (`id_ecole`);

--
-- Index pour la table `annonces_academie`
--
ALTER TABLE `annonces_academie`
  ADD PRIMARY KEY (`id_annonce`),
  ADD KEY `id_academie` (`id_academie`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_annonces_academie_date` (`date_publication`);

--
-- Index pour la table `annonces_admin_gestionnaire`
--
ALTER TABLE `annonces_admin_gestionnaire`
  ADD PRIMARY KEY (`id_annonce`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_ecole` (`id_ecole`);

--
-- Index pour la table `annonces_cap`
--
ALTER TABLE `annonces_cap`
  ADD PRIMARY KEY (`id_annonce`),
  ADD KEY `id_cap` (`id_cap`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_annonces_cap_date` (`date_publication`);

--
-- Index pour la table `annonces_fichiers`
--
ALTER TABLE `annonces_fichiers`
  ADD PRIMARY KEY (`id_fichier`);

--
-- Index pour la table `annonces_lues`
--
ALTER TABLE `annonces_lues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_annonce_lue` (`id_utilisateur`,`id_annonce`,`type_annonce`);

--
-- Index pour la table `banques`
--
ALTER TABLE `banques`
  ADD PRIMARY KEY (`id_banques`),
  ADD KEY `id_ecole` (`id_ecole`);

--
-- Index pour la table `bulletin`
--
ALTER TABLE `bulletin`
  ADD PRIMARY KEY (`id_bulletin`),
  ADD KEY `id_note` (`id_note`),
  ADD KEY `id_eleve` (`id_eleve`);

--
-- Index pour la table `caisse`
--
ALTER TABLE `caisse`
  ADD PRIMARY KEY (`id_caisse`),
  ADD KEY `id_ecole` (`id_ecole`);

--
-- Index pour la table `cap`
--
ALTER TABLE `cap`
  ADD PRIMARY KEY (`id_cap`),
  ADD UNIQUE KEY `code_cap` (`code_cap`),
  ADD KEY `idx_cap_nom` (`nom_cap`),
  ADD KEY `idx_cap_code` (`code_cap`),
  ADD KEY `idx_cap_academie` (`id_academie`);

--
-- Index pour la table `classe`
--
ALTER TABLE `classe`
  ADD PRIMARY KEY (`id_classe`),
  ADD KEY `idEcole` (`idEcole`);

--
-- Index pour la table `classes_officielles`
--
ALTER TABLE `classes_officielles`
  ADD PRIMARY KEY (`id_classe_officielle`);

--
-- Index pour la table `conduite`
--
ALTER TABLE `conduite`
  ADD PRIMARY KEY (`id_conduite`);

--
-- Index pour la table `controle`
--
ALTER TABLE `controle`
  ADD PRIMARY KEY (`id_controle`),
  ADD KEY `id_emploi_du_temps` (`id_emploi_du_temps`),
  ADD KEY `id_eleve` (`id_eleve`),
  ADD KEY `id_ecole` (`id_ecole`);

--
-- Index pour la table `controle_eleve`
--
ALTER TABLE `controle_eleve`
  ADD PRIMARY KEY (`id_controle_eleve`);

--
-- Index pour la table `decaissement`
--
ALTER TABLE `decaissement`
  ADD PRIMARY KEY (`id_decaissement`),
  ADD KEY `id_annee_scolaire` (`id_annee_scolaire`),
  ADD KEY `id_caisse` (`id_caisse`);

--
-- Index pour la table `ecole`
--
ALTER TABLE `ecole`
  ADD PRIMARY KEY (`idEcole`);

--
-- Index pour la table `eleve`
--
ALTER TABLE `eleve`
  ADD PRIMARY KEY (`id_eleve`),
  ADD KEY `anness` (`id_annee`),
  ADD KEY `eleve_ibfk_1` (`id_ecole`);

--
-- Index pour la table `emargement`
--
ALTER TABLE `emargement`
  ADD PRIMARY KEY (`id_emargement`),
  ADD KEY `emargement_ibfk_3` (`id_anneeScolaire`),
  ADD KEY `emargement_ibfk_4` (`id_trimestre`),
  ADD KEY `emargement_ibfk_5` (`id_matiere`),
  ADD KEY `emargement_ibfk_6` (`id_ecole`);

--
-- Index pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `annee_scolaire_ibfk_4` (`id_annee_scolaire`),
  ADD KEY `emploi_du_temps_ibfk_2` (`id_matiere`),
  ADD KEY `fk_enseignant` (`id_enseignant`);

--
-- Index pour la table `encaissement`
--
ALTER TABLE `encaissement`
  ADD PRIMARY KEY (`id_encaissement`),
  ADD KEY `id_caisse` (`id_caisse`),
  ADD KEY `idUtilisateur` (`idUtilisateur`),
  ADD KEY `id_annee_scolaire` (`id_annee_scolaire`);

--
-- Index pour la table `enseignants`
--
ALTER TABLE `enseignants`
  ADD PRIMARY KEY (`id_enseignant`),
  ADD KEY `enseignants_ibfk_1` (`id_ecole`);

--
-- Index pour la table `evaluation`
--
ALTER TABLE `evaluation`
  ADD PRIMARY KEY (`id_evaluation`);

--
-- Index pour la table `inscription`
--
ALTER TABLE `inscription`
  ADD PRIMARY KEY (`id_inscription`),
  ADD KEY `inscription_ibfk_2` (`id_eleve`);

--
-- Index pour la table `lecons_presence`
--
ALTER TABLE `lecons_presence`
  ADD PRIMARY KEY (`id_lecon_presence`),
  ADD KEY `id_presence` (`id_presence`);

--
-- Index pour la table `ligneclasse`
--
ALTER TABLE `ligneclasse`
  ADD PRIMARY KEY (`id_ligneclasse`),
  ADD KEY `ligneclasse_ibfk_1` (`id_classe`),
  ADD KEY `ligneclasse_ibfk_2` (`id_matiere`);

--
-- Index pour la table `ligneparents_eleves`
--
ALTER TABLE `ligneparents_eleves`
  ADD PRIMARY KEY (`id_ligneParent_eleve`),
  ADD KEY `id_eleve` (`id_eleve`),
  ADD KEY `id_parent` (`id_parent`);

--
-- Index pour la table `ligne_evaluation`
--
ALTER TABLE `ligne_evaluation`
  ADD PRIMARY KEY (`id_ligneEvaluation`),
  ADD KEY `ligne_evaluation_ibfk_1` (`id_annee_scolaire`),
  ADD KEY `ligne_evaluation_ibfk_2` (`id_classe`),
  ADD KEY `ligne_evaluation_ibfk_4` (`id_note`),
  ADD KEY `ligne_evaluation_ibfk_5` (`id_trimestre`),
  ADD KEY `ligne_evaluation_ibfk_6` (`id_eleve`),
  ADD KEY `ligne_evaluation_ibfk_7` (`id_enseignant`),
  ADD KEY `ligne_evaluation_ibfk_8` (`id_matiere`),
  ADD KEY `ligne_evaluation_ibfk_9` (`id_evaluation`);

--
-- Index pour la table `ligne_inscription`
--
ALTER TABLE `ligne_inscription`
  ADD PRIMARY KEY (`id_inscription`),
  ADD KEY `id_annee` (`id_annee`),
  ADD KEY `id_classe` (`id_classe`),
  ADD KEY `id_planification` (`id_planification`);

--
-- Index pour la table `ligne_paiement_eleve`
--
ALTER TABLE `ligne_paiement_eleve`
  ADD PRIMARY KEY (`idligne_paiement_eleve`),
  ADD KEY `idEcole` (`idEcole`),
  ADD KEY `id_annee` (`id_annee`),
  ADD KEY `id_classe` (`id_classe`),
  ADD KEY `id_paiement` (`id_paiement`),
  ADD KEY `id_trimestre` (`id_trimestre`);

--
-- Index pour la table `ligne_reinscription`
--
ALTER TABLE `ligne_reinscription`
  ADD PRIMARY KEY (`id_ligne_reinscription`);

--
-- Index pour la table `ligne_salaire`
--
ALTER TABLE `ligne_salaire`
  ADD PRIMARY KEY (`id_ligne_paiement`),
  ADD KEY `ligne_salaire_ibfk_1` (`id_salaire`);

--
-- Index pour la table `matiere`
--
ALTER TABLE `matiere`
  ADD PRIMARY KEY (`id_matiere`);

--
-- Index pour la table `matiere_ordre`
--
ALTER TABLE `matiere_ordre`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `moyenne_eleve`
--
ALTER TABLE `moyenne_eleve`
  ADD PRIMARY KEY (`id_moyenne`),
  ADD KEY `moyenne_eleve_ibfk_1` (`id_eleve`),
  ADD KEY `moyenne_eleve_ibfk_2` (`id_classe`),
  ADD KEY `moyenne_eleve_ibfk_3` (`id_trimestre`),
  ADD KEY `moyenne_eleve_ibfk_4` (`id_anneeScolaire`);

--
-- Index pour la table `note`
--
ALTER TABLE `note`
  ADD PRIMARY KEY (`id_note`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id_paiement`);

--
-- Index pour la table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id_parent`),
  ADD KEY `parents_ibfk_1` (`idEcole`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `planification`
--
ALTER TABLE `planification`
  ADD PRIMARY KEY (`id_planification`);

--
-- Index pour la table `presences`
--
ALTER TABLE `presences`
  ADD PRIMARY KEY (`id_presence`),
  ADD KEY `id_enseignant` (`id_enseignant`),
  ADD KEY `id_classe` (`id_classe`),
  ADD KEY `id_trimestre` (`id_trimestre`),
  ADD KEY `id_anneeScolaire` (`id_anneeScolaire`);

--
-- Index pour la table `programmes_officiels`
--
ALTER TABLE `programmes_officiels`
  ADD PRIMARY KEY (`id_programme`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `programme_classes`
--
ALTER TABLE `programme_classes`
  ADD PRIMARY KEY (`id_programme_classe`),
  ADD KEY `id_programme` (`id_programme`),
  ADD KEY `id_matiere` (`id_matiere`),
  ADD KEY `programme_classes_ibfk_2` (`id_classe`);

--
-- Index pour la table `programme_lecons`
--
ALTER TABLE `programme_lecons`
  ADD PRIMARY KEY (`id_lecon`),
  ADD KEY `id_programme_classe` (`id_programme_classe`);

--
-- Index pour la table `reinscription`
--
ALTER TABLE `reinscription`
  ADD PRIMARY KEY (`id_reinscription`);

--
-- Index pour la table `resultats_def_terminal`
--
ALTER TABLE `resultats_def_terminal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_eleve` (`id_eleve`),
  ADD KEY `id_classe` (`id_classe`);

--
-- Index pour la table `retrait`
--
ALTER TABLE `retrait`
  ADD PRIMARY KEY (`id_retrait`),
  ADD KEY `id_banque` (`id_banque`),
  ADD KEY `id_annee_scolaire` (`id_annee_scolaire`),
  ADD KEY `idUtilisateur` (`idUtilisateur`);

--
-- Index pour la table `salaire`
--
ALTER TABLE `salaire`
  ADD PRIMARY KEY (`id_salaire`);

--
-- Index pour la table `transfert`
--
ALTER TABLE `transfert`
  ADD PRIMARY KEY (`id_transfert`),
  ADD KEY `id_ecole` (`id_ecole`),
  ADD KEY `id_eleve` (`id_eleve`);

--
-- Index pour la table `trimestre`
--
ALTER TABLE `trimestre`
  ADD PRIMARY KEY (`id_trimestre`),
  ADD KEY `trimestre_ibfk_1` (`id_ecole`);

--
-- Index pour la table `user_permission`
--
ALTER TABLE `user_permission`
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`idUtilisateur`),
  ADD KEY `id_academie` (`id_academie`),
  ADD KEY `id_cap` (`id_cap`);

--
-- Index pour la table `versement`
--
ALTER TABLE `versement`
  ADD PRIMARY KEY (`id_versement`),
  ADD KEY `idUtilisateur` (`idUtilisateur`),
  ADD KEY `id_annee_scolaire` (`id_annee_scolaire`),
  ADD KEY `id_banque` (`id_banque`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `academie`
--
ALTER TABLE `academie`
  MODIFY `id_academie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `anneescolaire`
--
ALTER TABLE `anneescolaire`
  MODIFY `id_anneeScolaire` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `annonces_academie`
--
ALTER TABLE `annonces_academie`
  MODIFY `id_annonce` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `annonces_admin_gestionnaire`
--
ALTER TABLE `annonces_admin_gestionnaire`
  MODIFY `id_annonce` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `annonces_cap`
--
ALTER TABLE `annonces_cap`
  MODIFY `id_annonce` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `annonces_fichiers`
--
ALTER TABLE `annonces_fichiers`
  MODIFY `id_fichier` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `annonces_lues`
--
ALTER TABLE `annonces_lues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `banques`
--
ALTER TABLE `banques`
  MODIFY `id_banques` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bulletin`
--
ALTER TABLE `bulletin`
  MODIFY `id_bulletin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `caisse`
--
ALTER TABLE `caisse`
  MODIFY `id_caisse` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `cap`
--
ALTER TABLE `cap`
  MODIFY `id_cap` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `classe`
--
ALTER TABLE `classe`
  MODIFY `id_classe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `classes_officielles`
--
ALTER TABLE `classes_officielles`
  MODIFY `id_classe_officielle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `conduite`
--
ALTER TABLE `conduite`
  MODIFY `id_conduite` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `controle`
--
ALTER TABLE `controle`
  MODIFY `id_controle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `controle_eleve`
--
ALTER TABLE `controle_eleve`
  MODIFY `id_controle_eleve` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `decaissement`
--
ALTER TABLE `decaissement`
  MODIFY `id_decaissement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ecole`
--
ALTER TABLE `ecole`
  MODIFY `idEcole` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `eleve`
--
ALTER TABLE `eleve`
  MODIFY `id_eleve` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `emargement`
--
ALTER TABLE `emargement`
  MODIFY `id_emargement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `encaissement`
--
ALTER TABLE `encaissement`
  MODIFY `id_encaissement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `enseignants`
--
ALTER TABLE `enseignants`
  MODIFY `id_enseignant` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `evaluation`
--
ALTER TABLE `evaluation`
  MODIFY `id_evaluation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inscription`
--
ALTER TABLE `inscription`
  MODIFY `id_inscription` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lecons_presence`
--
ALTER TABLE `lecons_presence`
  MODIFY `id_lecon_presence` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ligneclasse`
--
ALTER TABLE `ligneclasse`
  MODIFY `id_ligneclasse` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `ligneparents_eleves`
--
ALTER TABLE `ligneparents_eleves`
  MODIFY `id_ligneParent_eleve` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ligne_evaluation`
--
ALTER TABLE `ligne_evaluation`
  MODIFY `id_ligneEvaluation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ligne_inscription`
--
ALTER TABLE `ligne_inscription`
  MODIFY `id_inscription` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ligne_paiement_eleve`
--
ALTER TABLE `ligne_paiement_eleve`
  MODIFY `idligne_paiement_eleve` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ligne_reinscription`
--
ALTER TABLE `ligne_reinscription`
  MODIFY `id_ligne_reinscription` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ligne_salaire`
--
ALTER TABLE `ligne_salaire`
  MODIFY `id_ligne_paiement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `matiere`
--
ALTER TABLE `matiere`
  MODIFY `id_matiere` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `matiere_ordre`
--
ALTER TABLE `matiere_ordre`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT pour la table `moyenne_eleve`
--
ALTER TABLE `moyenne_eleve`
  MODIFY `id_moyenne` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `note`
--
ALTER TABLE `note`
  MODIFY `id_note` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id_paiement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parents`
--
ALTER TABLE `parents`
  MODIFY `id_parent` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=371;

--
-- AUTO_INCREMENT pour la table `planification`
--
ALTER TABLE `planification`
  MODIFY `id_planification` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presences`
--
ALTER TABLE `presences`
  MODIFY `id_presence` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `programmes_officiels`
--
ALTER TABLE `programmes_officiels`
  MODIFY `id_programme` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `programme_classes`
--
ALTER TABLE `programme_classes`
  MODIFY `id_programme_classe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `programme_lecons`
--
ALTER TABLE `programme_lecons`
  MODIFY `id_lecon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

--
-- AUTO_INCREMENT pour la table `reinscription`
--
ALTER TABLE `reinscription`
  MODIFY `id_reinscription` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `resultats_def_terminal`
--
ALTER TABLE `resultats_def_terminal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `retrait`
--
ALTER TABLE `retrait`
  MODIFY `id_retrait` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `salaire`
--
ALTER TABLE `salaire`
  MODIFY `id_salaire` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `transfert`
--
ALTER TABLE `transfert`
  MODIFY `id_transfert` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `trimestre`
--
ALTER TABLE `trimestre`
  MODIFY `id_trimestre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `idUtilisateur` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `versement`
--
ALTER TABLE `versement`
  MODIFY `id_versement` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `annonces_academie`
--
ALTER TABLE `annonces_academie`
  ADD CONSTRAINT `annonces_academie_ibfk_1` FOREIGN KEY (`id_academie`) REFERENCES `academie` (`id_academie`),
  ADD CONSTRAINT `annonces_academie_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `annonces_admin_gestionnaire`
--
ALTER TABLE `annonces_admin_gestionnaire`
  ADD CONSTRAINT `annonces_admin_gestionnaire_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`),
  ADD CONSTRAINT `annonces_admin_gestionnaire_ibfk_2` FOREIGN KEY (`id_ecole`) REFERENCES `ecole` (`idEcole`);

--
-- Contraintes pour la table `annonces_cap`
--
ALTER TABLE `annonces_cap`
  ADD CONSTRAINT `annonces_cap_ibfk_1` FOREIGN KEY (`id_cap`) REFERENCES `cap` (`id_cap`),
  ADD CONSTRAINT `annonces_cap_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `annonces_lues`
--
ALTER TABLE `annonces_lues`
  ADD CONSTRAINT `annonces_lues_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cap`
--
ALTER TABLE `cap`
  ADD CONSTRAINT `cap_ibfk_1` FOREIGN KEY (`id_academie`) REFERENCES `academie` (`id_academie`) ON DELETE CASCADE;

--
-- Contraintes pour la table `controle`
--
ALTER TABLE `controle`
  ADD CONSTRAINT `controle_ibfk_2` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`);

--
-- Contraintes pour la table `decaissement`
--
ALTER TABLE `decaissement`
  ADD CONSTRAINT `decaissement_ibfk_1` FOREIGN KEY (`id_annee_scolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  ADD CONSTRAINT `decaissement_ibfk_2` FOREIGN KEY (`id_caisse`) REFERENCES `caisse` (`id_caisse`);

--
-- Contraintes pour la table `eleve`
--
ALTER TABLE `eleve`
  ADD CONSTRAINT `anness` FOREIGN KEY (`id_annee`) REFERENCES `anneescolaire` (`id_anneeScolaire`);

--
-- Contraintes pour la table `emargement`
--
ALTER TABLE `emargement`
  ADD CONSTRAINT `emargement_ibfk_3` FOREIGN KEY (`id_anneeScolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  ADD CONSTRAINT `emargement_ibfk_4` FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id_trimestre`),
  ADD CONSTRAINT `emargement_ibfk_5` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`);

--
-- Contraintes pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  ADD CONSTRAINT `annee_scolaire_ibfk_4` FOREIGN KEY (`id_annee_scolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  ADD CONSTRAINT `emploi_du_temps_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`),
  ADD CONSTRAINT `fk_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`);

--
-- Contraintes pour la table `encaissement`
--
ALTER TABLE `encaissement`
  ADD CONSTRAINT `encaissement_ibfk_1` FOREIGN KEY (`id_caisse`) REFERENCES `caisse` (`id_caisse`),
  ADD CONSTRAINT `encaissement_ibfk_2` FOREIGN KEY (`idUtilisateur`) REFERENCES `utilisateurs` (`idUtilisateur`),
  ADD CONSTRAINT `encaissement_ibfk_3` FOREIGN KEY (`id_annee_scolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`);

--
-- Contraintes pour la table `inscription`
--
ALTER TABLE `inscription`
  ADD CONSTRAINT `inscription_ibfk_2` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`);

--
-- Contraintes pour la table `ligneclasse`
--
ALTER TABLE `ligneclasse`
  ADD CONSTRAINT `ligneclasse_ibfk_1` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`),
  ADD CONSTRAINT `ligneclasse_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`);

--
-- Contraintes pour la table `ligneparents_eleves`
--
ALTER TABLE `ligneparents_eleves`
  ADD CONSTRAINT `ligneparents_eleves_ibfk_1` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`),
  ADD CONSTRAINT `ligneparents_eleves_ibfk_2` FOREIGN KEY (`id_parent`) REFERENCES `parents` (`id_parent`);

--
-- Contraintes pour la table `ligne_inscription`
--
ALTER TABLE `ligne_inscription`
  ADD CONSTRAINT `ligne_inscription_ibfk_1` FOREIGN KEY (`id_annee`) REFERENCES `anneescolaire` (`id_anneeScolaire`),
  ADD CONSTRAINT `ligne_inscription_ibfk_2` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`),
  ADD CONSTRAINT `ligne_inscription_ibfk_3` FOREIGN KEY (`id_planification`) REFERENCES `planification` (`id_planification`);

--
-- Contraintes pour la table `presences`
--
ALTER TABLE `presences`
  ADD CONSTRAINT `presences_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`),
  ADD CONSTRAINT `presences_ibfk_2` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`),
  ADD CONSTRAINT `presences_ibfk_3` FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id_trimestre`),
  ADD CONSTRAINT `presences_ibfk_4` FOREIGN KEY (`id_anneeScolaire`) REFERENCES `anneescolaire` (`id_anneeScolaire`);

--
-- Contraintes pour la table `transfert`
--
ALTER TABLE `transfert`
  ADD CONSTRAINT `transfert_ibfk_1` FOREIGN KEY (`id_ecole`) REFERENCES `ecole` (`idEcole`),
  ADD CONSTRAINT `transfert_ibfk_2` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`);

--
-- Contraintes pour la table `user_permission`
--
ALTER TABLE `user_permission`
  ADD CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`),
  ADD CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`idUtilisateur`);

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_academie`) REFERENCES `academie` (`id_academie`) ON DELETE SET NULL,
  ADD CONSTRAINT `utilisateurs_ibfk_2` FOREIGN KEY (`id_cap`) REFERENCES `cap` (`id_cap`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
