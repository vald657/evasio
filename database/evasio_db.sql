-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 15 mai 2026 à 06:58
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `evasio_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `demandes_conge`
--

CREATE TABLE `demandes_conge` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(20) NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `type_conge_id` int(10) UNSIGNED NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nombre_jours` decimal(5,1) NOT NULL,
  `motif` text DEFAULT NULL,
  `justificatif` varchar(255) DEFAULT NULL,
  `statut` enum('en_attente','approuve_manager','approuve_rh','refuse_manager','refuse_rh','annule') NOT NULL DEFAULT 'en_attente',
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `commentaire_manager` text DEFAULT NULL,
  `date_decision_manager` datetime DEFAULT NULL,
  `rh_id` int(10) UNSIGNED DEFAULT NULL,
  `commentaire_rh` text DEFAULT NULL,
  `date_decision_rh` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `demandes_conge`
--

INSERT INTO `demandes_conge` (`id`, `reference`, `utilisateur_id`, `type_conge_id`, `date_debut`, `date_fin`, `nombre_jours`, `motif`, `justificatif`, `statut`, `manager_id`, `commentaire_manager`, `date_decision_manager`, `rh_id`, `commentaire_rh`, `date_decision_rh`, `created_at`, `updated_at`) VALUES
(3, 'EVS-2026-B73477', 7, 1, '2026-05-11', '2026-05-14', 3.0, NULL, NULL, 'approuve_rh', 4, NULL, '2026-05-07 09:22:21', 2, NULL, '2026-05-07 09:25:19', '2026-05-07 08:21:47', '2026-05-07 08:25:19'),
(6, 'EVS-2026-6EF839', 12, 3, '2026-05-11', '2026-05-13', 3.0, NULL, NULL, 'approuve_rh', 1, NULL, '2026-05-07 10:33:47', 1, NULL, '2026-05-07 10:33:48', '2026-05-07 09:33:10', '2026-05-07 09:33:48');

-- --------------------------------------------------------

--
-- Structure de la table `departements`
--

CREATE TABLE `departements` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `responsable_id` int(10) UNSIGNED DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `departements`
--

INSERT INTO `departements` (`id`, `nom`, `description`, `responsable_id`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'Direction Générale', 'Direction et stratégie de l\'entreprise', NULL, 1, '2026-05-07 05:41:09', '2026-05-07 05:41:09'),
(2, 'Ressources Humaines', 'Gestion du personnel et des congés', NULL, 1, '2026-05-07 05:41:09', '2026-05-07 05:41:09'),
(3, 'Informatique', 'Systèmes d\'information et développement', 4, 1, '2026-05-07 05:41:09', '2026-05-07 06:12:00'),
(4, 'Comptabilité', 'Finances et comptabilité', 5, 1, '2026-05-07 05:41:09', '2026-05-07 06:12:00'),
(5, 'Commercial', 'Ventes et relation client', 6, 1, '2026-05-07 05:41:09', '2026-05-07 06:12:00'),
(6, 'Logistique', 'Transport et approvisionnement', NULL, 1, '2026-05-07 05:41:09', '2026-05-07 05:41:09');

-- --------------------------------------------------------

--
-- Structure de la table `logs_activite`
--

CREATE TABLE `logs_activite` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `table_cible` varchar(50) DEFAULT NULL,
  `id_cible` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `logs_activite`
--

INSERT INTO `logs_activite` (`id`, `utilisateur_id`, `action`, `description`, `table_cible`, `id_cible`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:24:17'),
(2, 1, 'profil_modifie', 'Profil mis à jour', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:27:53'),
(3, 1, 'mdp_change', 'Mot de passe modifié', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:28:19'),
(4, 1, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:28:33'),
(5, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:29:00'),
(6, NULL, 'mdp_reset_demande', 'Réinitialisation MDP demandée pour admin@evasio.local', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:31:12'),
(7, 2, 'connexion', 'Connexion réussie', 'utilisateurs', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:31:57'),
(8, 4, 'connexion', 'Connexion réussie', 'utilisateurs', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:32:57'),
(9, 10, 'connexion', 'Connexion réussie', 'utilisateurs', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:36:56'),
(10, 1, 'config_modifiee', 'Paramètres système mis à jour', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:41:11'),
(11, 1, 'employe_desactive', 'Employé #10 désactivé', 'utilisateurs', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:43:32'),
(12, 10, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:44:08'),
(13, 1, 'employe_cree', 'Nouvel utilisateur créé : serge kemdem', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:51:15'),
(14, 1, 'solde_modifie', 'Soldes employé #14 modifiés pour 2026', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:52:44'),
(15, NULL, 'connexion', 'Connexion réussie', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:53:52'),
(16, NULL, 'demande_soumise', 'Demande EVS-2026-40D82E soumise', 'demandes_conge', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 06:54:28'),
(17, 4, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:18:33'),
(18, 4, 'connexion', 'Connexion réussie', 'utilisateurs', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:18:39'),
(19, 4, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:18:56'),
(20, 5, 'connexion', 'Connexion réussie', 'utilisateurs', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:19:02'),
(21, 1, 'employe_active', 'Employé #10 activé', 'utilisateurs', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:35:35'),
(22, 1, 'type_conge_cree', 'Type Congé spec créé', 'types_conge', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:36:34'),
(23, NULL, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:06:20'),
(24, NULL, 'connexion', 'Connexion réussie', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:08:34'),
(25, NULL, 'demande_soumise', 'Demande EVS-2026-359C4C soumise', 'demandes_conge', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:12:35'),
(26, 5, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:13:02'),
(27, 10, 'connexion', 'Connexion réussie', 'utilisateurs', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:13:15'),
(28, 10, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:14:28'),
(29, 4, 'connexion', 'Connexion réussie', 'utilisateurs', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:14:34'),
(30, NULL, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:15:16'),
(31, 7, 'connexion', 'Connexion réussie', 'utilisateurs', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:15:22'),
(32, 2, 'solde_modifie', 'Soldes employé #7 modifiés pour 2026', 'utilisateurs', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:21:26'),
(33, 7, 'demande_soumise', 'Demande EVS-2026-B73477 soumise', 'demandes_conge', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:21:47'),
(34, 4, 'demande_approuvee', 'Demande #3 validée manager', 'demandes_conge', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:22:21'),
(35, 2, 'solde_modifie', 'Soldes employé #7 modifiés pour 2026', 'utilisateurs', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:25:10'),
(36, 2, 'demande_approuvee', 'Demande #3 approuvée', 'demandes_conge', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:25:19'),
(37, 1, 'config_modifiee', 'Paramètres système mis à jour', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:26:02'),
(38, 1, 'config_modifiee', 'Paramètres système mis à jour', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:26:15'),
(39, 1, 'demande_refusee', 'Demande #1 refusée', 'demandes_conge', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:19:20'),
(40, 7, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:19:42'),
(41, NULL, 'connexion', 'Connexion réussie', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:20:12'),
(42, 1, 'export', 'Export EXCEL généré (3 lignes)', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:23:19'),
(43, 1, 'export', 'Export EXCEL généré (3 lignes)', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:23:28'),
(44, 1, 'export', 'Export PDF généré (3 lignes)', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:24:02'),
(45, 1, 'demande_approuvee', 'Demande #2 validée (niveau manager)', 'demandes_conge', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:26:18'),
(46, 1, 'config_modifiee', 'Paramètres système mis à jour', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:26:38'),
(47, 1, 'demande_approuvee', 'Demande #2 approuvée (niveau RH)', 'demandes_conge', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:26:51'),
(48, NULL, 'demande_soumise', 'Demande EVS-2026-42C11D soumise', 'demandes_conge', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:27:32'),
(49, 1, 'demande_approuvee', 'Demande #4 validée (niveau manager)', 'demandes_conge', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:27:41'),
(50, 1, 'demande_approuvee', 'Demande #4 approuvée (niveau RH)', 'demandes_conge', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:27:50'),
(51, NULL, 'demande_soumise', 'Demande EVS-2026-39AE4E soumise', 'demandes_conge', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:28:35'),
(52, 1, 'demande_approuvee', 'Demande #5 validée (manager)', 'demandes_conge', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:28:48'),
(53, 1, 'demande_approuvee', 'Demande #5 approuvée (RH)', 'demandes_conge', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:28:50'),
(54, 1, 'export', 'Export PDF généré (5 lignes)', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:30:10'),
(55, NULL, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:31:56'),
(56, 12, 'connexion', 'Connexion réussie', 'utilisateurs', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:32:03'),
(57, 1, 'solde_modifie', 'Soldes employé #12 modifiés pour 2026', 'utilisateurs', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:33:06'),
(58, 12, 'demande_soumise', 'Demande EVS-2026-6EF839 soumise', 'demandes_conge', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:33:10'),
(59, 1, 'solde_modifie', 'Soldes employé #12 modifiés pour 2026', 'utilisateurs', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:33:26'),
(60, 1, 'demande_approuvee', 'Demande #6 validée (manager)', 'demandes_conge', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:33:47'),
(61, 1, 'demande_approuvee', 'Demande #6 approuvée (RH)', 'demandes_conge', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:33:48'),
(62, 1, 'securite_definie', 'Question de sécurité configurée', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:58:35'),
(63, 1, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:59:05'),
(64, NULL, 'mdp_reset', 'Mot de passe réinitialisé via question de sécurité', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:00:43'),
(65, NULL, 'mdp_reset', 'Mot de passe réinitialisé via question de sécurité', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:01:28'),
(66, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:01:50'),
(67, NULL, 'connexion', 'Connexion réussie', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:54:08'),
(68, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:02:06'),
(69, 1, 'employe_desactive', 'Employé #9 désactivé', 'utilisateurs', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:04:33'),
(70, 1, 'employe_active', 'Employé #9 activé', 'utilisateurs', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:04:43'),
(71, 1, 'export', 'Export PDF généré (6 lignes)', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:06:11'),
(72, NULL, 'connexion', 'Connexion réussie', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:08:30'),
(73, NULL, 'profil_modifie', 'Profil mis à jour', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:08:58'),
(74, 1, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:10:39'),
(75, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:10:57'),
(76, NULL, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:11:36'),
(77, NULL, 'connexion', 'Connexion réussie', 'utilisateurs', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 07:11:52'),
(78, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-10 14:31:24'),
(79, 1, 'deconnexion', 'Déconnexion de l\'utilisateur', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-10 14:32:26'),
(80, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-10 14:33:03'),
(81, 1, 'connexion', 'Connexion réussie', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 20:12:33'),
(82, 1, 'profil_modifie', 'Profil mis à jour', 'utilisateurs', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-15 04:58:19');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `titre` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `lien` varchar(255) DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `titre`, `message`, `type`, `lien`, `lu`, `created_at`) VALUES
(1, 1, 'Nouvelle demande de congé', 'serge kemdem a soumis une demande (3 j).', 'info', 'http://localhost/evasio/detail_demande.php?id=1', 1, '2026-05-07 06:54:28'),
(2, 1, 'Nouvelle demande à valider', 'serge kemdem a soumis une demande de congé (3 j) — à valider.', 'info', 'http://localhost/evasio/detail_demande.php?id=2', 1, '2026-05-07 08:12:35'),
(4, 3, 'Nouvelle demande de congé', 'serge kemdem a soumis une demande de congé (3 j) — à valider.', 'info', 'http://localhost/evasio/detail_demande.php?id=2', 0, '2026-05-07 08:12:35'),
(5, 4, 'Nouvelle demande à valider', 'Nicolas Leroy a soumis une demande de congé (3 j) — à valider.', 'info', 'http://localhost/evasio/detail_demande.php?id=3', 1, '2026-05-07 08:21:47'),
(6, 1, 'Nouvelle demande de congé', 'Nicolas Leroy a soumis une demande de congé (3 j) — à valider.', 'info', 'http://localhost/evasio/detail_demande.php?id=3', 1, '2026-05-07 08:21:47'),
(8, 3, 'Nouvelle demande de congé', 'Nicolas Leroy a soumis une demande de congé (3 j) — à valider.', 'info', 'http://localhost/evasio/detail_demande.php?id=3', 0, '2026-05-07 08:21:47'),
(9, 7, 'Demande validée par votre manager', 'Votre demande a été validée. En attente d\'approbation RH.', 'info', 'http://localhost/evasio/detail_demande.php?id=3', 0, '2026-05-07 08:22:21'),
(11, 3, 'Demande à approuver (RH)', 'Une demande validée par le manager attend votre approbation finale.', 'info', 'http://localhost/evasio/detail_demande.php?id=3', 0, '2026-05-07 08:22:21'),
(12, 7, 'Demande approuvée', 'Votre demande EVS-2026-B73477 a été approuvée définitivement.', 'success', 'http://localhost/evasio/detail_demande.php?id=3', 0, '2026-05-07 08:25:19'),
(15, 1, 'Demande à approuver — Niveau RH', 'serge kemdem — demande validée par le manager, en attente de votre approbation finale.', 'info', 'http://localhost/evasio/detail_demande.php?id=2', 1, '2026-05-07 09:26:18'),
(17, 3, 'Demande à approuver — Niveau RH', 'serge kemdem — demande validée par le manager, en attente de votre approbation finale.', 'info', 'http://localhost/evasio/detail_demande.php?id=2', 0, '2026-05-07 09:26:18'),
(19, 1, 'Nouvelle demande à valider', 'serge kemdem a soumis une demande de congé (2 j) — votre validation est requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=4', 1, '2026-05-07 09:27:32'),
(21, 1, 'Demande à approuver — Niveau RH', 'serge kemdem — demande validée par le manager, en attente de votre approbation finale.', 'info', 'http://localhost/evasio/detail_demande.php?id=4', 1, '2026-05-07 09:27:41'),
(23, 3, 'Demande à approuver — Niveau RH', 'serge kemdem — demande validée par le manager, en attente de votre approbation finale.', 'info', 'http://localhost/evasio/detail_demande.php?id=4', 0, '2026-05-07 09:27:41'),
(25, 1, 'Nouvelle demande à valider', 'serge kemdem a soumis une demande de congé (1 j) — votre validation est requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=5', 1, '2026-05-07 09:28:35'),
(27, 1, 'Demande à approuver (RH)', 'serge kemdem — validation finale requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=5', 1, '2026-05-07 09:28:48'),
(29, 3, 'Demande à approuver (RH)', 'serge kemdem — validation finale requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=5', 0, '2026-05-07 09:28:48'),
(31, 6, 'Nouvelle demande à valider', 'Camille Roux a soumis une demande de congé (3 j) — votre validation est requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=6', 0, '2026-05-07 09:33:10'),
(32, 12, 'Demande validée par votre manager', 'Votre demande EVS-2026-6EF839 a été validée. En attente d\'approbation RH.', 'info', 'http://localhost/evasio/detail_demande.php?id=6', 0, '2026-05-07 09:33:47'),
(33, 1, 'Demande à approuver (RH)', 'Camille Roux — validation finale requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=6', 0, '2026-05-07 09:33:47'),
(34, 2, 'Demande à approuver (RH)', 'Camille Roux — validation finale requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=6', 0, '2026-05-07 09:33:47'),
(35, 3, 'Demande à approuver (RH)', 'Camille Roux — validation finale requise.', 'info', 'http://localhost/evasio/detail_demande.php?id=6', 0, '2026-05-07 09:33:47'),
(36, 12, 'Congé approuvé définitivement ✓', 'Votre demande EVS-2026-6EF839 a été approuvée par le RH.', 'success', 'http://localhost/evasio/detail_demande.php?id=6', 0, '2026-05-07 09:33:48');

-- --------------------------------------------------------

--
-- Structure de la table `parametres_systeme`
--

CREATE TABLE `parametres_systeme` (
  `id` int(10) UNSIGNED NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `label` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres_systeme`
--

INSERT INTO `parametres_systeme` (`id`, `cle`, `valeur`, `label`, `updated_at`) VALUES
(1, 'app_nom', 'Évasio', 'Nom de l\'application', '2026-05-07 05:41:10'),
(2, 'app_slogan', 'Gestion des congés simplifiée', 'Slogan', '2026-05-07 05:41:10'),
(3, 'smtp_host', '', 'Hôte SMTP', '2026-05-07 05:41:10'),
(4, 'smtp_port', '465', 'Port SMTP', '2026-05-07 05:41:10'),
(5, 'smtp_user', 'admin@clinic.com', 'Email SMTP', '2026-05-07 06:41:11'),
(6, 'smtp_pass', 'admin123', 'Mot de passe SMTP', '2026-05-07 06:41:11'),
(7, 'smtp_from_name', 'Évasio RH', 'Nom expéditeur', '2026-05-07 05:41:10'),
(8, 'email_notifications', '1', 'Activer les emails', '2026-05-07 05:41:10'),
(9, 'jours_ouvres', '1,2,3,4,5', 'Jours ouvrés (1=Lun, 7=Dim)', '2026-05-07 09:26:38'),
(10, 'annee_exercice', '2026', 'Année d\'exercice en cours', '2026-05-07 05:41:10'),
(11, 'jours_feries', '[{\"date\":\"2026-05-27\",\"label\":\"valday\"}]', 'Jours fériés (JSON)', '2026-05-07 08:26:51'),
(12, 'questions_securite', 'Quel est le prénom de votre mère ?|Quel est le nom de votre animal de compagnie ?|Dans quelle ville êtes-vous né(e) ?|Quel était le nom de votre école primaire ?|Quel est le surnom de votre enfance ?|Quel est le prénom de votre meilleur(e) ami(e) d\'enfance ?', 'Questions de sécurité disponibles', '2026-05-07 09:57:45');

-- --------------------------------------------------------

--
-- Structure de la table `soldes_conge`
--

CREATE TABLE `soldes_conge` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `type_conge_id` int(10) UNSIGNED NOT NULL,
  `annee` year(4) NOT NULL,
  `jours_alloues` decimal(5,1) NOT NULL DEFAULT 0.0,
  `jours_pris` decimal(5,1) NOT NULL DEFAULT 0.0,
  `jours_restants` decimal(5,1) GENERATED ALWAYS AS (`jours_alloues` - `jours_pris`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `soldes_conge`
--

INSERT INTO `soldes_conge` (`id`, `utilisateur_id`, `type_conge_id`, `annee`, `jours_alloues`, `jours_pris`, `created_at`, `updated_at`) VALUES
(8, 7, 6, '2026', 0.0, 0.0, '2026-05-07 08:21:26', '2026-05-07 08:21:26'),
(9, 7, 1, '2026', 20.0, 3.0, '2026-05-07 08:21:26', '2026-05-07 08:25:19'),
(10, 7, 7, '2026', 0.0, 0.0, '2026-05-07 08:21:26', '2026-05-07 08:21:26'),
(11, 7, 2, '2026', 0.0, 0.0, '2026-05-07 08:21:26', '2026-05-07 08:21:26'),
(12, 7, 3, '2026', 0.0, 0.0, '2026-05-07 08:21:26', '2026-05-07 08:21:26'),
(13, 7, 4, '2026', 0.0, 0.0, '2026-05-07 08:21:26', '2026-05-07 08:21:26'),
(14, 7, 5, '2026', 0.0, 0.0, '2026-05-07 08:21:26', '2026-05-07 08:21:26'),
(15, 7, 8, '2026', 0.0, 0.0, '2026-05-07 08:21:26', '2026-05-07 08:21:26'),
(16, 12, 6, '2026', 0.0, 0.0, '2026-05-07 09:33:06', '2026-05-07 09:33:06'),
(17, 12, 1, '2026', 0.0, 0.0, '2026-05-07 09:33:06', '2026-05-07 09:33:06'),
(18, 12, 7, '2026', 0.0, 0.0, '2026-05-07 09:33:06', '2026-05-07 09:33:06'),
(19, 12, 2, '2026', 0.0, 0.0, '2026-05-07 09:33:06', '2026-05-07 09:33:06'),
(20, 12, 3, '2026', 10.0, 3.0, '2026-05-07 09:33:06', '2026-05-07 09:33:48'),
(21, 12, 4, '2026', 0.0, 0.0, '2026-05-07 09:33:06', '2026-05-07 09:33:06'),
(22, 12, 5, '2026', 0.0, 0.0, '2026-05-07 09:33:06', '2026-05-07 09:33:06'),
(23, 12, 8, '2026', 0.0, 0.0, '2026-05-07 09:33:06', '2026-05-07 09:33:06');

-- --------------------------------------------------------

--
-- Structure de la table `types_conge`
--

CREATE TABLE `types_conge` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `couleur` varchar(7) NOT NULL DEFAULT '#4648d4',
  `jours_max_annuel` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `justificatif_requis` tinyint(1) NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `types_conge`
--

INSERT INTO `types_conge` (`id`, `nom`, `description`, `couleur`, `jours_max_annuel`, `justificatif_requis`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'Congé annuel', 'Congé payé annuel réglementaire', '#4648d4', 30, 0, 1, '2026-05-07 05:41:10', '2026-05-07 05:41:10'),
(2, 'Congé maladie', 'Absence pour raison médicale', '#b4136d', 15, 1, 1, '2026-05-07 05:41:10', '2026-05-07 05:41:10'),
(3, 'Congé maternité', 'Congé lié à la maternité', '#1a7a4a', 90, 1, 1, '2026-05-07 05:41:10', '2026-05-07 05:41:10'),
(4, 'Congé paternité', 'Congé lié à la paternité', '#34c97a', 10, 1, 1, '2026-05-07 05:41:10', '2026-05-07 05:41:10'),
(5, 'Congé sans solde', 'Absence non rémunérée', '#904900', 10, 0, 1, '2026-05-07 05:41:10', '2026-05-07 05:41:10'),
(6, 'Autorisation abs.', 'Absence ponctuelle autorisée', '#767586', 5, 0, 1, '2026-05-07 05:41:10', '2026-05-07 05:41:10'),
(7, 'Congé exceptionnel', 'Événement familial (mariage, décès, etc.)', '#6063ee', 5, 1, 1, '2026-05-07 05:41:10', '2026-05-07 07:31:44'),
(8, 'Congé spec', 'bmf\"ofh\'h', '#4648d4', 30, 1, 0, '2026-05-07 07:36:34', '2026-05-08 07:05:01');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(80) NOT NULL,
  `prenom` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('employe','manager','rh','admin') NOT NULL DEFAULT 'employe',
  `departement_id` int(10) UNSIGNED DEFAULT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `poste` varchar(100) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `token_reset` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `question_securite` varchar(255) DEFAULT NULL,
  `reponse_securite` varchar(255) DEFAULT NULL,
  `derniere_connexion` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `photo`, `role`, `departement_id`, `manager_id`, `date_embauche`, `poste`, `actif`, `token_reset`, `token_expiry`, `question_securite`, `reponse_securite`, `derniere_connexion`, `created_at`, `updated_at`) VALUES
(1, 'Administrateur', 'Évasio', 'admin@evasio.local', '$2y$10$rkkK2e5qyNP7LBh3WLK7J.McAXyZAXDAXVCP/cPpzq6Y/efbkPGHS', '657207189', '1_1778821099.png', 'admin', NULL, NULL, '2026-05-07', 'Administrateur Système', 1, NULL, NULL, 'Dans quelle ville êtes-vous né(e) ?', '$2y$10$Q37E1UrJrVwe5zqJ4zzCM.LMjRaVJPXLQjQmVaI3KPzGDgWvFYOfy', '2026-05-12 21:12:33', '2026-05-07 05:41:10', '2026-05-15 04:58:19'),
(2, 'Dupont', 'Marie', 'rh@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 10 00 00 01', NULL, 'rh', 2, NULL, '2021-03-15', 'Responsable RH', 1, NULL, NULL, NULL, NULL, '2026-05-07 07:31:57', '2026-05-07 06:11:59', '2026-05-07 06:31:57'),
(3, 'Lambert', 'Sophie', 'rh2@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 10 00 00 02', NULL, 'rh', 2, NULL, '2022-06-01', 'Chargée RH', 1, NULL, NULL, NULL, NULL, NULL, '2026-05-07 06:11:59', '2026-05-07 06:24:07'),
(4, 'Martin', 'Thomas', 'manager.info@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 20 00 00 01', NULL, 'manager', 3, NULL, '2019-09-01', 'Responsable Informatique', 1, NULL, NULL, NULL, NULL, '2026-05-07 09:14:34', '2026-05-07 06:11:59', '2026-05-07 08:14:34'),
(5, 'Bernard', 'Claire', 'manager.compta@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 20 00 00 02', NULL, 'manager', 4, NULL, '2020-01-10', 'Responsable Comptabilité', 1, NULL, NULL, NULL, NULL, '2026-05-07 08:19:02', '2026-05-07 06:11:59', '2026-05-07 07:19:02'),
(6, 'Moreau', 'Julien', 'manager.commercial@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 20 00 00 03', NULL, 'manager', 5, NULL, '2018-04-22', 'Responsable Commercial', 1, NULL, NULL, NULL, NULL, NULL, '2026-05-07 06:11:59', '2026-05-07 06:24:07'),
(7, 'Leroy', 'Nicolas', 'n.leroy@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 30 00 00 01', NULL, 'employe', 3, 4, '2022-09-05', 'Développeur PHP', 1, NULL, NULL, NULL, NULL, '2026-05-07 09:15:22', '2026-05-07 06:11:59', '2026-05-07 08:15:22'),
(8, 'Petit', 'Laura', 'l.petit@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 30 00 00 02', NULL, 'employe', 3, 4, '2023-02-13', 'Développeuse Frontend', 1, NULL, NULL, NULL, NULL, NULL, '2026-05-07 06:11:59', '2026-05-07 06:24:07'),
(9, 'Simon', 'Paul', 'p.simon@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 30 00 00 03', NULL, 'employe', 3, 4, '2021-11-20', 'Administrateur Système', 1, NULL, NULL, NULL, NULL, NULL, '2026-05-07 06:11:59', '2026-05-08 07:04:43'),
(10, 'Durand', 'Emma', 'e.durand@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 30 00 00 04', NULL, 'employe', 4, 5, '2020-07-01', 'Comptable', 1, NULL, NULL, NULL, NULL, '2026-05-07 09:13:15', '2026-05-07 06:11:59', '2026-05-07 08:13:15'),
(11, 'Girard', 'Antoine', 'a.girard@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 30 00 00 05', NULL, 'employe', 4, 5, '2023-09-01', 'Assistant Comptable', 1, NULL, NULL, NULL, NULL, NULL, '2026-05-07 06:11:59', '2026-05-07 06:24:07'),
(12, 'Roux', 'Camille', 'c.roux@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 30 00 00 06', NULL, 'employe', 5, 6, '2022-03-14', 'Commercial', 1, NULL, NULL, NULL, NULL, '2026-05-07 10:32:03', '2026-05-07 06:11:59', '2026-05-07 09:32:03'),
(13, 'Fournier', 'Lucas', 'l.fournier@evasio.local', '$2y$10$IXK3q.iGDMMaI.7C.MvhP.Fp1KoTSQ6hcfE1TWmm1aFKsVXOOIwrG', '06 30 00 00 07', NULL, 'employe', 5, 6, '2024-01-08', 'Chargé de clientèle', 1, NULL, NULL, NULL, NULL, NULL, '2026-05-07 06:11:59', '2026-05-07 06:24:07');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `demandes_conge`
--
ALTER TABLE `demandes_conge`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `fk_demande_utilisateur` (`utilisateur_id`),
  ADD KEY `fk_demande_type` (`type_conge_id`),
  ADD KEY `fk_demande_manager` (`manager_id`),
  ADD KEY `fk_demande_rh` (`rh_id`),
  ADD KEY `idx_demande_statut` (`statut`),
  ADD KEY `idx_demande_dates` (`date_debut`,`date_fin`);

--
-- Index pour la table `departements`
--
ALTER TABLE `departements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_departement_responsable` (`responsable_id`);

--
-- Index pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_log_action` (`action`),
  ADD KEY `idx_log_date` (`created_at`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_notif_lu` (`lu`);

--
-- Index pour la table `parametres_systeme`
--
ALTER TABLE `parametres_systeme`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Index pour la table `soldes_conge`
--
ALTER TABLE `soldes_conge`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_solde` (`utilisateur_id`,`type_conge_id`,`annee`),
  ADD KEY `fk_solde_type` (`type_conge_id`);

--
-- Index pour la table `types_conge`
--
ALTER TABLE `types_conge`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_utilisateur_departement` (`departement_id`),
  ADD KEY `fk_utilisateur_manager` (`manager_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `demandes_conge`
--
ALTER TABLE `demandes_conge`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `departements`
--
ALTER TABLE `departements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pour la table `parametres_systeme`
--
ALTER TABLE `parametres_systeme`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `soldes_conge`
--
ALTER TABLE `soldes_conge`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `types_conge`
--
ALTER TABLE `types_conge`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `demandes_conge`
--
ALTER TABLE `demandes_conge`
  ADD CONSTRAINT `fk_demande_manager` FOREIGN KEY (`manager_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_demande_rh` FOREIGN KEY (`rh_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_demande_type` FOREIGN KEY (`type_conge_id`) REFERENCES `types_conge` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_demande_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `departements`
--
ALTER TABLE `departements`
  ADD CONSTRAINT `fk_departement_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD CONSTRAINT `fk_log_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `soldes_conge`
--
ALTER TABLE `soldes_conge`
  ADD CONSTRAINT `fk_solde_type` FOREIGN KEY (`type_conge_id`) REFERENCES `types_conge` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solde_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_utilisateur_departement` FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_utilisateur_manager` FOREIGN KEY (`manager_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
