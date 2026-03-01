-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : ven. 27 fév. 2026 à 10:34
-- Version du serveur : 8.0.45-0ubuntu0.24.04.1
-- Version de PHP : 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `joker_peintre`
--
CREATE DATABASE IF NOT EXISTS `joker_peintre` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `joker_peintre`;

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL,
  `username` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `role`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$OSlWz46wSSfCAxmHSKFWxuheBqWFSpi90gREkpN9mdw8F2lW8tRc.', 'admin', 1, '2026-02-26 22:45:26', '2026-02-26 13:51:05');

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` int NOT NULL,
  `created_at` datetime NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `service` varchar(120) DEFAULT NULL,
  `surface` varchar(120) DEFAULT NULL,
  `message` text NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `captcha_score` decimal(3,2) DEFAULT NULL,
  `user_agent` text,
  `status` varchar(50) NOT NULL DEFAULT 'new',
  `pipeline_status` varchar(30) NOT NULL DEFAULT 'new',
  `next_followup_at` datetime DEFAULT NULL,
  `followup_count` int NOT NULL DEFAULT '0',
  `archived_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `contacts`
--

INSERT INTO `contacts` (`id`, `created_at`, `name`, `email`, `phone`, `city`, `service`, `surface`, `message`, `ip`, `captcha_score`, `user_agent`, `status`, `pipeline_status`, `next_followup_at`, `followup_count`, `archived_at`, `updated_at`) VALUES
(1, '2026-02-26 14:07:12', 'khalfi', 'akhalfi@acometis.com', '', 'Wittenheim', 'Crepi / Facade', '110', 'qzdzqd', '127.0.0.1', 0.70, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0', 'treated', 'new', NULL, 0, NULL, '2026-02-26 15:07:05'),
(2, '2026-02-26 14:35:55', 'KHALFI Abdeljaouad', 'abdeljaouad.khalfi@gmail.com', '0659337820', 'Wittenheim', 'Peinture exterieure', '220', 'Ravalement de façade complet', '127.0.0.1', 0.70, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0', 'treated', 'new', NULL, 0, NULL, '2026-02-26 15:08:37'),
(3, '2026-02-26 19:14:09', 'KHalfi', 'akhalfi@acometis.com', '0659337820', 'Wittenheim', 'Peinture exterieure', '110', 'ravalement de facade svp devis rapide', '127.0.0.1', 0.90, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', 'new', 'new', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `contact_notes`
--

DROP TABLE IF EXISTS `contact_notes`;
CREATE TABLE `contact_notes` (
  `id` int NOT NULL,
  `contact_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `note` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contact_tags`
--

DROP TABLE IF EXISTS `contact_tags`;
CREATE TABLE `contact_tags` (
  `contact_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `contact_tags`
--

INSERT INTO `contact_tags` (`contact_id`, `tag_id`) VALUES
(2, 1),
(2, 2);

-- --------------------------------------------------------

--
-- Structure de la table `realisations`
--

DROP TABLE IF EXISTS `realisations`;
CREATE TABLE `realisations` (
  `id` int NOT NULL,
  `title` varchar(190) NOT NULL,
  `city` varchar(120) DEFAULT NULL,
  `type` varchar(60) DEFAULT NULL,
  `description` text,
  `cover_image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `cover_thumb` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `realisations`
--

INSERT INTO `realisations` (`id`, `title`, `city`, `type`, `description`, `cover_image`, `is_featured`, `is_published`, `sort_order`, `created_at`, `updated_at`, `cover_thumb`) VALUES
(1, 'Peinture interieure', 'Strasbourg', NULL, 'Renovation complete des murs et plafonds avec finition mate uniforme.', 'assets/images/realisations/9042556185db96680c973772e607824a.webp', 1, 1, 1, '2026-02-26 16:03:09', '2026-02-26 22:46:19', 'assets/images/realisations/thumb_9042556185db96680c973772e607824a.webp'),
(2, 'Facade renovee', 'Colmar', NULL, 'Nettoyage, reparation et application d un crepi resistant aux intemperies.', 'assets/images/realisations/a2f548d35472c5f5d22b7cdea1684ecc.webp', 1, 1, 2, '2026-02-26 16:22:13', '2026-02-26 18:52:08', 'assets/images/realisations/thumb_a2f548d35472c5f5d22b7cdea1684ecc.webp'),
(3, 'Effet pierre decoratif', 'Mulhouse', NULL, 'Finition mosaique effet pierre pour un rendu premium et authentique.', 'assets/images/realisations/5fb6130eafb35c77466d6f58613ecc0b.webp', 1, 1, 3, '2026-02-26 16:22:27', '2026-02-26 18:53:19', 'assets/images/realisations/thumb_5fb6130eafb35c77466d6f58613ecc0b.webp'),
(4, 'Renovation appartement', 'Haguenau', NULL, 'Preparation des supports et peinture complete interieur.', 'assets/images/realisations/54e65ab36ea66b4081c4bad5ab6c9d25.webp', 0, 1, 4, '2026-02-26 16:23:12', '2026-02-26 18:55:45', 'assets/images/realisations/thumb_54e65ab36ea66b4081c4bad5ab6c9d25.webp'),
(5, 'Ravalement facade', 'Selestat', NULL, 'Protection et embellissement durable de la facade.', 'assets/images/realisations/22066b3d5239d2da1db0f460bd67e775.webp', 0, 1, 5, '2026-02-26 16:23:22', '2026-02-26 18:57:20', 'assets/images/realisations/thumb_22066b3d5239d2da1db0f460bd67e775.webp'),
(6, 'Peinture maison', 'Saint-Louis', NULL, 'Application peinture exterieure resistant aux variations climatiques.', 'assets/images/realisations/bfc697d5f60be64965de3a95dcdb8eda.webp', 0, 1, 6, '2026-02-26 16:23:39', '2026-02-26 18:58:34', 'assets/images/realisations/thumb_bfc697d5f60be64965de3a95dcdb8eda.webp');

-- --------------------------------------------------------

--
-- Structure de la table `realisation_images`
--

DROP TABLE IF EXISTS `realisation_images`;
CREATE TABLE `realisation_images` (
  `id` int NOT NULL,
  `realisation_id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(190) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `realisation_images`
--

INSERT INTO `realisation_images` (`id`, `realisation_id`, `image_path`, `alt_text`, `sort_order`, `created_at`) VALUES
(15, 1, 'assets/images/realisations/9042556185db96680c973772e607824a.webp', NULL, 0, '2026-02-26 18:49:35'),
(17, 2, 'assets/images/realisations/a2f548d35472c5f5d22b7cdea1684ecc.webp', NULL, 0, '2026-02-26 18:52:02'),
(18, 3, 'assets/images/realisations/5fb6130eafb35c77466d6f58613ecc0b.webp', NULL, 0, '2026-02-26 18:53:17'),
(19, 4, 'assets/images/realisations/54e65ab36ea66b4081c4bad5ab6c9d25.webp', NULL, 0, '2026-02-26 18:55:42'),
(20, 5, 'assets/images/realisations/22066b3d5239d2da1db0f460bd67e775.webp', NULL, 0, '2026-02-26 18:57:12'),
(21, 6, 'assets/images/realisations/bfc697d5f60be64965de3a95dcdb8eda.webp', NULL, 0, '2026-02-26 18:58:32');

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'realisations_h1', 'Des chantiers soignes, des finitions visibles', '2026-02-26 17:58:06'),
(2, 'realisations_intro', 'Chaque projet est realise avec precision et exigence. Voici quelques exemples de travaux effectues en Alsace : peinture interieure, renovation de facade, crepi et finitions decoratives.', '2026-02-26 17:58:06'),
(3, 'realisations_cta_text', 'Demander un devis', '2026-02-26 17:58:06'),
(4, 'realisations_cta_link', '/contact', '2026-02-26 17:58:06'),
(5, 'realisations_meta_title', 'Realisations - Joker Peintre | Travaux de peinture en Alsace', '2026-02-26 17:58:06'),
(6, 'realisations_meta_desc', 'Photos de chantiers en Alsace : peinture interieure et exterieure, facade, crepi et effet pierre.', '2026-02-26 17:58:06'),
(25, 'realisations_per_page', '6', '2026-02-26 17:58:06'),
(40, 'realisations_before_after_enabled', '1', '2026-02-26 17:58:06'),
(41, 'realisations_before_after_title', 'Avant / Apres', '2026-02-26 17:58:06'),
(42, 'realisations_before_after_subtitle', 'La difference se voit dans les details.', '2026-02-26 17:58:06'),
(43, 'realisations_before_after_label', 'Transformation complete', '2026-02-26 17:58:06'),
(44, 'realisations_before_after_block1_title', 'Preparation minutieuse', '2026-02-26 17:58:06'),
(45, 'realisations_before_after_block1_text', 'Traitement fissures, sous-couche adaptee, protection complete.', '2026-02-26 17:58:06'),
(46, 'realisations_before_after_block2_title', 'Finition propre', '2026-02-26 17:58:06'),
(47, 'realisations_before_after_block2_text', 'Uniformite, angles nets et rendu durable.', '2026-02-26 17:58:06'),
(48, 'realisations_before_after_image_before', 'assets/images/realisations/before_1772124959.webp', '2026-02-26 17:55:59'),
(49, 'realisations_before_after_image_after', 'assets/images/realisations/after_1772125086.webp', '2026-02-26 17:58:06'),
(191, 'home_realisations_title', 'Realisations', '2026-02-26 19:37:52'),
(192, 'home_realisations_text', 'Decouvre quelques projets recents en Alsace. Finition propre, rendu durable.', '2026-02-26 19:37:52');

-- --------------------------------------------------------

--
-- Structure de la table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int NOT NULL,
  `name` varchar(60) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tags`
--

INSERT INTO `tags` (`id`, `name`, `created_at`) VALUES
(1, 'urgent', '2026-02-26 15:08:31'),
(2, 'cuisine', '2026-02-26 15:08:37');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `username_2` (`username`),
  ADD KEY `is_active` (`is_active`);

--
-- Index pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `ip` (`ip`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_pipeline_status` (`pipeline_status`),
  ADD KEY `idx_next_followup` (`next_followup_at`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Index pour la table `contact_notes`
--
ALTER TABLE `contact_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `fk_notes_admin` (`admin_id`);

--
-- Index pour la table `contact_tags`
--
ALTER TABLE `contact_tags`
  ADD PRIMARY KEY (`contact_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Index pour la table `realisations`
--
ALTER TABLE `realisations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_published` (`is_published`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `sort_order` (`sort_order`),
  ADD KEY `created_at` (`created_at`);

--
-- Index pour la table `realisation_images`
--
ALTER TABLE `realisation_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `realisation_id` (`realisation_id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `name_2` (`name`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `contact_notes`
--
ALTER TABLE `contact_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `realisations`
--
ALTER TABLE `realisations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `realisation_images`
--
ALTER TABLE `realisation_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT pour la table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `contact_notes`
--
ALTER TABLE `contact_notes`
  ADD CONSTRAINT `fk_notes_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notes_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contact_tags`
--
ALTER TABLE `contact_tags`
  ADD CONSTRAINT `fk_ct_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ct_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `realisation_images`
--
ALTER TABLE `realisation_images`
  ADD CONSTRAINT `fk_images_realisation` FOREIGN KEY (`realisation_id`) REFERENCES `realisations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
