-- =============================================
--  ÉVASIO — Schéma de base de données
--  SGBD : MySQL / MariaDB
--  Encodage : UTF-8 (utf8mb4)
--  Dernière mise à jour : 2026-05-07
-- =============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+01:00";

-- NOTE DÉPLOIEMENT o2switch :
-- Créez la base de données depuis cPanel (MySQL Databases) AVANT d'importer ce fichier.
-- Sélectionnez ensuite cette base dans phpMyAdmin, puis importez ce fichier.
-- Ne pas décommenter les lignes CREATE DATABASE / USE — elles sont gérées par cPanel.

-- =============================================
-- TABLE : departements
-- =============================================
CREATE TABLE `departements` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`            VARCHAR(100) NOT NULL,
  `description`    TEXT DEFAULT NULL,
  `responsable_id` INT UNSIGNED DEFAULT NULL,
  `actif`          TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE : utilisateurs
-- =============================================
CREATE TABLE `utilisateurs` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`                VARCHAR(80) NOT NULL,
  `prenom`             VARCHAR(80) NOT NULL,
  `email`              VARCHAR(150) NOT NULL UNIQUE,
  `mot_de_passe`       VARCHAR(255) NOT NULL,
  `telephone`          VARCHAR(20) DEFAULT NULL,
  `photo`              VARCHAR(255) DEFAULT NULL,
  `role`               ENUM('employe','manager','rh','admin') NOT NULL DEFAULT 'employe',
  `departement_id`     INT UNSIGNED DEFAULT NULL,
  `manager_id`         INT UNSIGNED DEFAULT NULL,
  `date_embauche`      DATE DEFAULT NULL,
  `poste`              VARCHAR(100) DEFAULT NULL,
  `actif`              TINYINT(1) NOT NULL DEFAULT 1,
  `token_reset`        VARCHAR(255) DEFAULT NULL,
  `token_expiry`       DATETIME DEFAULT NULL,
  `question_securite`  VARCHAR(255) DEFAULT NULL,
  `reponse_securite`   VARCHAR(255) DEFAULT NULL,
  `derniere_connexion` DATETIME DEFAULT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_utilisateur_departement` (`departement_id`),
  KEY `fk_utilisateur_manager` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clé étrangère : responsable de département
ALTER TABLE `departements`
  ADD CONSTRAINT `fk_departement_responsable`
  FOREIGN KEY (`responsable_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_utilisateur_departement`
  FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_utilisateur_manager`
  FOREIGN KEY (`manager_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- =============================================
-- TABLE : types_conge
-- =============================================
CREATE TABLE `types_conge` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`                 VARCHAR(100) NOT NULL,
  `description`         TEXT DEFAULT NULL,
  `couleur`             VARCHAR(7) NOT NULL DEFAULT '#4648d4',
  `jours_max_annuel`    INT UNSIGNED NOT NULL DEFAULT 30,
  `justificatif_requis` TINYINT(1) NOT NULL DEFAULT 0,
  `actif`               TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE : soldes_conge
-- =============================================
CREATE TABLE `soldes_conge` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `type_conge_id`  INT UNSIGNED NOT NULL,
  `annee`          YEAR NOT NULL,
  `jours_alloues`  DECIMAL(5,1) NOT NULL DEFAULT 0,
  `jours_pris`     DECIMAL(5,1) NOT NULL DEFAULT 0,
  `jours_restants` DECIMAL(5,1) GENERATED ALWAYS AS (`jours_alloues` - `jours_pris`) STORED,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_solde` (`utilisateur_id`, `type_conge_id`, `annee`),
  KEY `fk_solde_type` (`type_conge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `soldes_conge`
  ADD CONSTRAINT `fk_solde_utilisateur`
  FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solde_type`
  FOREIGN KEY (`type_conge_id`) REFERENCES `types_conge` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- =============================================
-- TABLE : demandes_conge
-- =============================================
CREATE TABLE `demandes_conge` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`             VARCHAR(20) NOT NULL UNIQUE,
  `utilisateur_id`        INT UNSIGNED NOT NULL,
  `type_conge_id`         INT UNSIGNED NOT NULL,
  `date_debut`            DATE NOT NULL,
  `date_fin`              DATE NOT NULL,
  `nombre_jours`          DECIMAL(5,1) NOT NULL,
  `motif`                 TEXT DEFAULT NULL,
  `justificatif`          VARCHAR(255) DEFAULT NULL,
  `statut`                ENUM(
                            'en_attente',
                            'approuve_manager',
                            'approuve_rh',
                            'refuse_manager',
                            'refuse_rh',
                            'annule'
                          ) NOT NULL DEFAULT 'en_attente',
  `manager_id`            INT UNSIGNED DEFAULT NULL,
  `commentaire_manager`   TEXT DEFAULT NULL,
  `date_decision_manager` DATETIME DEFAULT NULL,
  `rh_id`                 INT UNSIGNED DEFAULT NULL,
  `commentaire_rh`        TEXT DEFAULT NULL,
  `date_decision_rh`      DATETIME DEFAULT NULL,
  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_demande_utilisateur` (`utilisateur_id`),
  KEY `fk_demande_type` (`type_conge_id`),
  KEY `fk_demande_manager` (`manager_id`),
  KEY `fk_demande_rh` (`rh_id`),
  KEY `idx_demande_statut` (`statut`),
  KEY `idx_demande_dates` (`date_debut`, `date_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `demandes_conge`
  ADD CONSTRAINT `fk_demande_utilisateur`
  FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_demande_type`
  FOREIGN KEY (`type_conge_id`) REFERENCES `types_conge` (`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_demande_manager`
  FOREIGN KEY (`manager_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_demande_rh`
  FOREIGN KEY (`rh_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- =============================================
-- TABLE : notifications
-- =============================================
CREATE TABLE `notifications` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `titre`          VARCHAR(150) NOT NULL,
  `message`        TEXT NOT NULL,
  `type`           ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
  `lien`           VARCHAR(255) DEFAULT NULL,
  `lu`             TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_utilisateur` (`utilisateur_id`),
  KEY `idx_notif_lu` (`lu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_utilisateur`
  FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- =============================================
-- TABLE : logs_activite
-- =============================================
CREATE TABLE `logs_activite` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED DEFAULT NULL,
  `action`         VARCHAR(100) NOT NULL,
  `description`    TEXT DEFAULT NULL,
  `table_cible`    VARCHAR(50) DEFAULT NULL,
  `id_cible`       INT UNSIGNED DEFAULT NULL,
  `ip_address`     VARCHAR(45) DEFAULT NULL,
  `user_agent`     VARCHAR(255) DEFAULT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_log_utilisateur` (`utilisateur_id`),
  KEY `idx_log_action` (`action`),
  KEY `idx_log_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `logs_activite`
  ADD CONSTRAINT `fk_log_utilisateur`
  FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- =============================================
-- TABLE : parametres_systeme
-- =============================================
CREATE TABLE `parametres_systeme` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cle`        VARCHAR(100) NOT NULL UNIQUE,
  `valeur`     TEXT DEFAULT NULL,
  `label`      VARCHAR(150) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DONNÉES INITIALES
-- =============================================

-- Départements
INSERT INTO `departements` (`nom`, `description`) VALUES
('Direction Générale',  'Direction et stratégie de l\'entreprise'),
('Ressources Humaines', 'Gestion du personnel et des congés'),
('Informatique',        'Systèmes d\'information et développement'),
('Comptabilité',        'Finances et comptabilité'),
('Commercial',          'Ventes et relation client'),
('Logistique',          'Transport et approvisionnement');

-- Types de congé
INSERT INTO `types_conge` (`nom`, `description`, `couleur`, `jours_max_annuel`, `justificatif_requis`) VALUES
('Congé annuel',       'Congé payé annuel réglementaire',           '#4648d4', 30, 0),
('Congé maladie',      'Absence pour raison médicale',              '#b4136d', 15, 1),
('Congé maternité',    'Congé lié à la maternité',                  '#1a7a4a', 90, 1),
('Congé paternité',    'Congé lié à la paternité',                  '#34c97a', 10, 1),
('Congé sans solde',   'Absence non rémunérée',                     '#904900', 10, 0),
('Autorisation abs.',  'Absence ponctuelle autorisée',              '#767586',  5, 0),
('Congé exceptionnel', 'Événement familial (mariage, décès, etc.)', '#6063ee',  5, 1);

-- Compte admin par défaut  (mot de passe : Admin@2026)
INSERT INTO `utilisateurs` (`nom`, `prenom`, `email`, `mot_de_passe`, `role`, `poste`, `date_embauche`) VALUES
('Administrateur', 'Évasio', 'admin@evasio.local',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uVEFkS7y6',
 'admin', 'Administrateur Système', CURDATE());

-- Paramètres système par défaut
INSERT INTO `parametres_systeme` (`cle`, `valeur`, `label`) VALUES
('app_nom',           'Évasio',                                        'Nom de l\'application'),
('app_slogan',        'Gestion des congés simplifiée',                 'Slogan'),
('jours_ouvres',      '1,2,3,4,5',                                     'Jours ouvrés (1=Lun, 7=Dim)'),
('annee_exercice',    YEAR(CURDATE()),                                  'Année d\'exercice en cours'),
('questions_securite',
 'Quel est le prénom de votre mère ?|Quel est le nom de votre animal de compagnie ?|Dans quelle ville êtes-vous né(e) ?|Quel était le nom de votre école primaire ?|Quel est le surnom de votre enfance ?|Quel est le prénom de votre meilleur(e) ami(e) d\'enfance ?',
 'Questions de sécurité disponibles');

COMMIT;
