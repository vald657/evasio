-- Migration : ajout des colonnes pour la question de sécurité
-- À exécuter une seule fois dans phpMyAdmin ou en ligne de commande MySQL

ALTER TABLE utilisateurs
  ADD COLUMN question_securite VARCHAR(255) DEFAULT NULL AFTER token_expiry,
  ADD COLUMN reponse_securite  VARCHAR(255) DEFAULT NULL AFTER question_securite;

-- Insérer un jeu de questions par défaut (modifiable dans Configuration → Questions de sécurité)
INSERT IGNORE INTO parametres_systeme (cle, valeur, label) VALUES (
  'questions_securite',
  'Quel est le prénom de votre mère ?|Quel est le nom de votre animal de compagnie ?|Dans quelle ville êtes-vous né(e) ?|Quel était le nom de votre école primaire ?|Quel est le surnom de votre enfance ?|Quel est le prénom de votre meilleur(e) ami(e) d''enfance ?',
  'Questions de sécurité disponibles'
);
