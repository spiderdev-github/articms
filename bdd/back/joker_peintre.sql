-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : sam. 28 fév. 2026 à 17:47
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
  `email` varchar(180) DEFAULT NULL,
  `display_name` varchar(120) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'editor',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `totp_secret` varchar(64) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `display_name`, `avatar`, `password_hash`, `role`, `is_active`, `last_login`, `created_at`, `reset_token`, `reset_token_expires`, `totp_secret`, `totp_enabled`) VALUES
(1, 'akhalfi', 'akhalfi@acometis.com', 'LALSAROKIN', 'avatar_1_1772226052.jpg', '$2y$10$J8SFBk5EsOv4TLQDNVO7D.dB9JZKhJqLnkKccwxmC7WVgZj18LdBa', 'super_admin', 1, '2026-02-28 17:16:38', '2026-02-26 13:51:05', NULL, NULL, 'KFMUZL7YGHYK3OMZ', 1),
(2, 'yann', 'abdeljaouad.khalfi@gmail.com', 'Yann Bodet', NULL, '$2y$10$YTAd2KJqU8FX72yCnRpdCe8B5yYmOGR.EzkH/2gMGqyyX6d7PMZ3W', 'editor', 1, '2026-02-28 05:31:59', '2026-02-27 22:03:26', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `admin_recovery_codes`
--

DROP TABLE IF EXISTS `admin_recovery_codes`;
CREATE TABLE `admin_recovery_codes` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `code_hash` varchar(64) NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admin_recovery_codes`
--

INSERT INTO `admin_recovery_codes` (`id`, `admin_id`, `code_hash`, `used_at`, `created_at`) VALUES
(1, 1, '20a6b6f9c29a36b169a4b5866b5c23461b17f4eea7043b42ac189f65c19373b4', '2026-02-28 02:51:43', '2026-02-28 02:44:40'),
(2, 1, '04cded61aaaecc40cc2b2346cce868e1c1757ea4d811cd985ee33f92a5a79302', NULL, '2026-02-28 02:44:40'),
(3, 1, '67449c0b47394f3fbf897279a8fc385449f7d94b76dd96875286721612d44f55', NULL, '2026-02-28 02:44:40'),
(4, 1, '89f8567ec9736de7a1894f4222b383a83e8a243ac3180476f754314e8869f377', NULL, '2026-02-28 02:44:40'),
(5, 1, 'e3d5db498b178adef3f79caa70aa2221f5d40fd9803fd698ace33031852c7b58', NULL, '2026-02-28 02:44:40'),
(6, 1, 'e593ad94256e65520caf09dd6bc7569cfcb901f963adae2832e459083b907dc7', NULL, '2026-02-28 02:44:40'),
(7, 1, 'ed5be2e4d4b3879e0fc6456bbf441ee720e8150d11110b9782278005c2a8cd51', NULL, '2026-02-28 02:44:40'),
(8, 1, 'bc5177f63cff973f3ede7807ba4e88f05910d4eba3b0932b2f2d0017110e6435', NULL, '2026-02-28 02:44:40');

-- --------------------------------------------------------

--
-- Structure de la table `admin_trusted_devices`
--

DROP TABLE IF EXISTS `admin_trusted_devices`;
CREATE TABLE `admin_trusted_devices` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `device_label` varchar(200) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admin_trusted_devices`
--

INSERT INTO `admin_trusted_devices` (`id`, `admin_id`, `token_hash`, `device_label`, `expires_at`, `created_at`) VALUES
(2, 1, 'b159f0798567eb00c2be3ac0dadc3fc30609a221098a20097d6a750df9884822', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-30 01:51:43', '2026-02-28 02:51:43');

-- --------------------------------------------------------

--
-- Structure de la table `cms_pages`
--

DROP TABLE IF EXISTS `cms_pages`;
CREATE TABLE `cms_pages` (
  `id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `slug` varchar(120) NOT NULL,
  `title` varchar(255) NOT NULL,
  `h1` varchar(255) DEFAULT NULL,
  `kicker` varchar(255) DEFAULT NULL,
  `content` longtext,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_desc` varchar(320) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `template` varchar(60) DEFAULT 'default',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `cms_pages`
--

INSERT INTO `cms_pages` (`id`, `parent_id`, `slug`, `title`, `h1`, `kicker`, `content`, `meta_title`, `meta_desc`, `is_published`, `sort_order`, `template`, `created_at`, `updated_at`) VALUES
(2, NULL, 'a-propos', 'A propos', 'Une entreprise de peinture engagee pour un travail propre et durable', 'Entreprise locale en Alsace', '<section class=\"section top\">\r\n<div class=\"container\">\r\n<div style=\"margin-top: 18px; max-width: 900px;\">\r\n<p class=\"muted\" style=\"font-size: 18px; line-height: 1.75;\">Joker Peintre est une entreprise de peinture basee en Alsace, specialisee dans la peinture interieure, exterieure, l isolation, le crepi facade et la mosaique effet pierre. Chaque chantier est realise avec rigueur, precision et souci du detail.</p>\r\n</div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"grid-3\">\r\n<article class=\"card\"><img style=\"display: block; margin-left: auto; margin-right: auto;\" src=\"../assets/images/medias/rigueur-et-preparation.png\" alt=\"Rigeur et preparation\" width=\"335\" height=\"224\">\r\n<h3>Rigueur et preparation</h3>\r\n<p>La qualite d une finition depend de la preparation. Protection des surfaces, rebouchage, poncage et traitement des supports sont effectues avec precision.</p>\r\n</article>\r\n<article class=\"card\"><img style=\"display: block; margin-left: auto; margin-right: auto;\" src=\"../assets/images/medias/finition-haut-de-gamme.png\" alt=\"Finition haut de gamme\" width=\"335\" height=\"223\">\r\n<h3>Finition haut de gamme</h3>\r\n<p>Application reguliere, angles nets, rendu uniforme. L objectif est un resultat esthetique et durable.</p>\r\n</article>\r\n<article class=\"card\"><img style=\"display: block; margin-left: auto; margin-right: auto;\" src=\"../assets/images/medias/respect-du-chantier.png\" alt=\"Respect du chantier\" width=\"335\" height=\"223\">\r\n<h3>Respect du chantier</h3>\r\n<p>Organisation, proprete et respect des delais. Chaque projet est realise avec professionnalisme.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Une expertise adaptee a chaque projet</h2>\r\n<p>Chaque logement ou facade a ses contraintes. Une solution adaptee est proposee selon le support et l environnement.</p>\r\n</div>\r\n</div>\r\n<div class=\"card\" style=\"padding: 28px;\">\r\n<p style=\"line-height: 1.8; font-size: 17px;\">Que ce soit pour une renovation interieure complete, une remise en etat de facade ou un projet decoratif comme la mosaique effet pierre, Joker Peintre accompagne ses partenaires de A a Z : conseil sur les teintes, choix des materiaux, preparation technique et execution soignee.</p>\r\n<p class=\"muted\" style=\"margin-top: 16px;\">L entreprise intervient dans toute l Alsace (Bas-Rhin et Haut-Rhin) pour des projets de particuliers et professionnels.</p>\r\n</div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"local\"><span class=\"kicker\"><strong>Zone d intervention</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Intervention dans toute l Alsace</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient dans les principales villes d Alsace pour des travaux de peinture interieure, exterieure, isolation et renovation facade.</p>\r\n<ul>\r\n<li>Strasbourg</li>\r\n<li>Haguenau</li>\r\n<li>Selestat</li>\r\n<li>Colmar</li>\r\n<li>Mulhouse</li>\r\n<li>Saint-Louis</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Besoin d un devis pour votre projet ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px;\">Decrivez votre projet (surface, type de support, ville) et recevez une estimation rapide.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"../contact\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"tel:+33783868622\">Appeler 07 83 86 86 22</a></div>\r\n</div>\r\n</section>', 'A propos - Joker Peintre | Entreprise de peinture en Alsace', 'Decouvrez Joker Peintre, entreprise de peinture en Alsace specialisee en interieur, exterieur, isolation et crepi facade. Travail propre et finitions haut de gamme.', 1, 10, 'default', '2026-02-27 12:29:02', '2026-02-28 03:35:50'),
(4, NULL, 'prestations', 'Prestations', 'Des solutions completes pour vos travaux de peinture et renovation', 'Nos prestations en Alsace', '<section class=\"section top\">\r\n<div class=\"container\">\r\n<p class=\"muted\" style=\"max-width: 800px; font-size: 18px; line-height: 1.75;\">Joker Peintre intervient pour tous vos projets de peinture int&eacute;rieure, peinture ext&eacute;rieure, isolation, cr&eacute;pi fa&ccedil;ade et mosa&iuml;que effet pierre. Chaque prestation est adapt&eacute;e au support et aux contraintes du chantier.</p>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<div class=\"icon\">&nbsp;</div>\r\n<h3>Peinture int&eacute;rieure</h3>\r\n<p>Murs, plafonds, boiseries, r&eacute;novation compl&egrave;te d appartement ou maison. Pr&eacute;paration soign&eacute;e et finition uniforme.</p>\r\n<div style=\"margin-top: 14px;\"><a class=\"btn btn-ghost\" href=\"../peinture-interieure-en-alsace\">En savoir plus</a></div>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"icon gold\">&nbsp;</div>\r\n<h3>Isolation int&eacute;rieure / ext&eacute;rieure</h3>\r\n<p>Solutions pour ameliorer le confort thermique et r&eacute;duire les pertes &eacute;nerg&eacute;tiques.</p>\r\n<div style=\"margin-top: 14px;\"><a class=\"btn btn-ghost\" href=\"../isolation-interieure-exterieure\">En savoir plus</a></div>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"icon\">&nbsp;</div>\r\n<h3>Travaux de fa&ccedil;ade</h3>\r\n<p>R&eacute;novation et ravalement de fa&ccedil;ade. Protection durable et am&eacute;lioration esth&eacute;tique.</p>\r\n<div style=\"margin-top: 14px;\"><a class=\"btn btn-ghost\" href=\"../travaux-de-facade\">En savoir plus</a></div>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"icon gold\">&nbsp;</div>\r\n<h3>Rev&ecirc;tements muraux et d&eacute;coration</h3>\r\n<p>Finition decorative effet pierre pour fa&ccedil;ades et murs. Apporte relief et cachet haut de gamme.</p>\r\n<div style=\"margin-top: 14px;\"><a class=\"btn btn-ghost\" href=\"../revetements-muraux-et-decoration\">En savoir plus</a></div>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"icon\">&nbsp;</div>\r\n<h3>Peinture ext&eacute;rieure</h3>\r\n<p>Protection et embellissement de fa&ccedil;ades, volets et surfaces ext&eacute;rieures. Peintures r&eacute;sistantes aux intemp&eacute;ries.</p>\r\n<div style=\"margin-top: 14px;\"><a class=\"btn btn-ghost\" href=\"../peinture-exterieure-en-alsace\">En savoir plus</a></div>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Notre m&eacute;thode de travail</h2>\r\n<p>Une approche professionnelle pour un r&eacute;sultat durable.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<div class=\"card\">\r\n<h3>1. Analyse du support</h3>\r\n<p>&Eacute;valuation des surfaces, humidit&eacute;, fissures et contraintes techniques.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>2. Pr&eacute;paration technique</h3>\r\n<p>Protection des zones, rebouchage, pon&ccedil;age et application des sous-couches adapt&eacute;es.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>3. Application et finition</h3>\r\n<p>Peinture ou rev&ecirc;tement appliqu&eacute; avec pr&eacute;cision pour un rendu uniforme et propre.</p>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"local\"><span class=\"kicker\"><strong>Intervention locale</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Travaux de peinture dans toute l\'Alsace</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient dans le Bas-Rhin et le Haut-Rhin pour vos projets de r&eacute;novation et d&eacute;coration.</p>\r\n<ul>\r\n<li>Strasbourg</li>\r\n<li>Haguenau</li>\r\n<li>S&eacute;lestat</li>\r\n<li>Colmar</li>\r\n<li>Mulhouse</li>\r\n<li>Saint-Louis</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Un projet en cours ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px;\">Contactez Joker Peintre pour un devis gratuit et une estimation adaptee a votre projet.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"../contact\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"tel:+33783868622\">Appeler 07 83 86 86 22</a></div>\r\n</div>\r\n</section>', 'Prestations - Joker Peintre | Peinture & Decoration en Alsace', 'Peinture interieure et exterieure en Alsace, isolation, crepi facade et mosaique effet pierre. Devis gratuit et travail soigne.', 1, 0, 'default', '2026-02-27 12:53:26', '2026-02-28 03:36:29'),
(5, 4, 'peinture-interieure-en-alsace', 'Peinture interieure en Alsace', 'Peinture interieure en Alsace : un rendu propre, net et durable', 'Prestations - Peinture interieure', '<!-- HERO -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div style=\"display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; flex-wrap: wrap; margin-top: 14px;\">\r\n<div style=\"max-width: 820px;\">\r\n<p class=\"muted\" style=\"font-size: 18px; line-height: 1.75; margin: 0;\">Joker Peintre realise vos travaux de peinture interieure avec une preparation rigoureuse et une finition haut de gamme. Murs, plafonds, boiseries, renovation complete : l objectif est un rendu uniforme, durable et sans surprises.</p>\r\n<div style=\"display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n<div class=\"muted small\" style=\"margin-top: 12px;\">Intervention Bas-Rhin et Haut-Rhin : Strasbourg, Colmar, Mulhouse, Haguenau, Selestat et alentours.</div>\r\n</div>\r\n<div class=\"card\" style=\"min-width: 280px; max-width: 360px;\">\r\n<div class=\"kicker\" style=\"margin-bottom: 14px;\"><strong>Inclus</strong></div>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Protection sols et mobilier</li>\r\n<li>Preparation supports (rebouchage, poncage)</li>\r\n<li>Peinture murs / plafonds / boiseries</li>\r\n<li>Finition propre (angles nets)</li>\r\n<li>Nettoyage fin de chantier</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- BENEFITS -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Une methode simple et professionnelle</h2>\r\n<p class=\"muted\">Chaque etape compte pour obtenir une finition haut de gamme.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<div class=\"kicker\" style=\"margin-bottom: 18px;\">&nbsp;</div>\r\n<h3>Preparation des supports</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Protection, rebouchage, poncage et accroche. Une surface bien preparee garantit un rendu uniforme et durable.</p>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"kicker\" style=\"margin-bottom: 18px;\">&nbsp;</div>\r\n<h3>Application reguliere</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Produits adaptes, gestes precis, couches bien tendues. L objectif : une finition nette, sans traces ni reprises visibles.</p>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"kicker\" style=\"margin-bottom: 18px;\">&nbsp;</div>\r\n<h3>Chantier propre</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Organisation et protection des zones de passage. Nettoyage en fin d intervention pour rendre un espace impeccable.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- SERVICES LIST -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Ce que nous realisons en peinture interieure</h2>\r\n<p class=\"muted\">Du rafraichissement a la renovation complete.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>Murs et plafonds</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Peinture mate, velours ou satin selon vos pieces. Travail soigne des jonctions, angles, et finitions.</p>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Renovation appartement / maison</li>\r\n<li>Reprises apres degats (petites fissures, impacts)</li>\r\n<li>Plafonds (anti-traces, rendu uniforme)</li>\r\n</ul>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Boiseries et details</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Portes, plinthes, encadrements : un travail de precision pour un rendu propre et elegant.</p>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Portes, plinthes, escaliers</li>\r\n<li>Radiateurs (selon configuration)</li>\r\n<li>Petites finitions et retouches</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- PROCESS -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Deroulement d une intervention</h2>\r\n<p class=\"muted\">Un cadre clair pour un resultat sans surprise.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>1. Evaluation</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Etat des supports, surfaces, niveau de finition souhaite, contraintes (occupation du logement, acces, delais).</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>2. Preparation</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Protection, rebouchage, poncage, sous-couche si necessaire. Base propre = finition premium.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>3. Finition</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Application reguliere, controle du rendu, nettoyage du chantier. Livraison d un espace propre.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- LOCAL SEO -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"local\"><span class=\"kicker\"><strong>Peinture interieure en Alsace</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Intervention Bas-Rhin et Haut-Rhin</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient en Alsace pour vos travaux de peinture interieure : maisons, appartements, bureaux et locaux. Nous nous deplacons notamment autour de Strasbourg, Colmar, Mulhouse, Haguenau et Selestat.</p>\r\n<ul>\r\n<li>Strasbourg et Eurometropole</li>\r\n<li>Colmar et environs</li>\r\n<li>Mulhouse et environs</li>\r\n<li>Haguenau</li>\r\n<li>Selestat</li>\r\n<li>Autres communes d Alsace sur demande</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- FAQ -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Questions frequentes</h2>\r\n<p class=\"muted\">Les reponses aux questions les plus courantes.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>Faut il toujours une sous-couche ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Pas toujours. Cela depend du support (etat, porosite, ancienne peinture). Nous choisissons la solution la plus fiable.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Combien de temps dure un chantier ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Selon les surfaces et la preparation necessaire. Un planning clair est defini avant demarrage.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>Le chantier est il protege ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Oui. Sols, meubles, zones sensibles : la protection fait partie de la methode pour un rendu premium.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Proposez vous des couleurs sur mesure ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Oui. Nous pouvons vous conseiller pour un rendu harmonieux, moderne et durable selon la piece et la lumiere.</p>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- CTA FINAL -->\r\n<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Un projet de peinture interieure ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px; line-height: 1.75;\">Decris ton projet (surfaces, ville, delai) et nous te repondons rapidement avec une estimation claire.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n</div>\r\n</section>', 'Peinture interieure en Alsace | Joker Peintre', 'Peinture intérieure premium en Alsace : murs, plafonds, boiseries. Préparation soignée, finitions nettes, chantier propre. Devis gratuit.', 1, 0, 'default', '2026-02-27 13:19:58', '2026-02-28 17:53:37'),
(6, 4, 'peinture-exterieure-en-alsace', 'Peinture exterieure en Alsace', 'Peinture exterieure en Alsace : protection durable et rendu impeccable', 'Prestations - Peinture exterieure', '<!-- HERO -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div style=\"display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; flex-wrap: wrap; margin-top: 14px;\">\r\n<div style=\"max-width: 820px;\">\r\n<p class=\"muted\" style=\"font-size: 18px; line-height: 1.75; margin: 0;\">Joker Peintre realise vos travaux de peinture exterieure avec des produits adaptes aux intemperies et une preparation rigoureuse. Facades, volets, boiseries, portails ou murets : l objectif est un resultat propre, resistant et durable.</p>\r\n<div style=\"display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n<div class=\"muted small\" style=\"margin-top: 12px;\">Intervention Bas-Rhin et Haut-Rhin : Strasbourg, Colmar, Mulhouse, Haguenau, Selestat et alentours.</div>\r\n</div>\r\n<div class=\"card\" style=\"min-width: 280px; max-width: 360px;\">\r\n<div class=\"kicker\" style=\"margin-bottom: 14px;\"><strong>Inclus</strong></div>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Protection zones sensibles</li>\r\n<li>Nettoyage et preparation supports</li>\r\n<li>Traitement (si necessaire)</li>\r\n<li>Peintures resistantes UV / pluie</li>\r\n<li>Nettoyage fin de chantier</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- BENEFITS -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Une finition exterieure qui tient dans le temps</h2>\r\n<p class=\"muted\">La durabilite depend de la preparation et du bon systeme de peinture.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<div class=\"kicker\" style=\"margin-bottom: 18px;\">&nbsp;</div>\r\n<h3>Preparation et accroche</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Nettoyage, poncage, reprises et accroche. La base est essentielle pour eviter cloques, ecaillage et traces.</p>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"kicker\" style=\"margin-bottom: 18px;\">&nbsp;</div>\r\n<h3>Protection contre les intemperies</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Produits adaptes : UV, pluie, humidite. Un choix coherent pour garder couleur et protection dans le temps.</p>\r\n</article>\r\n<article class=\"card\">\r\n<div class=\"kicker\" style=\"margin-bottom: 18px;\">&nbsp;</div>\r\n<h3>Rendu propre, sans reprises</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Application reguliere, angles et aretes nettes. Un rendu uniforme qui valorise votre facade et vos exterieurs.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- SERVICES LIST -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Ce que nous realisons en peinture exterieure</h2>\r\n<p class=\"muted\">Protection, esthetique et durabilite.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>Boiseries et volets</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Renovation et protection des boiseries exposees : volets, portes, dessous de toit, encadrements.</p>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Decapage / poncage selon etat</li>\r\n<li>Traitement et sous-couche adaptee</li>\r\n<li>Finition reguliere et durable</li>\r\n</ul>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Murs exterieurs et murets</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Peinture exterieure pour murs, murets et elements de cloture. Objectif : proprete visuelle et protection longue duree.</p>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Reprises fissures et petites degradations</li>\r\n<li>Peintures adaptees aux supports</li>\r\n<li>Finition uniforme</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- PROCESS -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Deroulement d une intervention</h2>\r\n<p class=\"muted\">Une organisation claire, adaptee a la meteo et au support.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>1. Diagnostic</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Support, humidite, etat general, contraintes et acces. Choix du systeme de peinture adapte.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>2. Preparation</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Nettoyage, reprises, poncage, protection. Une base propre garantit tenue et rendu.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>3. Application</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Application reguliere, controle du rendu, finitions. Nettoyage de la zone en fin de chantier.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- LOCAL SEO -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"local\"><span class=\"kicker\"><strong>Peinture exterieure en Alsace</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Bas-Rhin et Haut-Rhin</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient en Alsace pour vos travaux de peinture exterieure : volets, boiseries, murs et elements exterieurs. Nous nous deplacons notamment autour de Strasbourg, Colmar, Mulhouse, Haguenau et Selestat.</p>\r\n<ul>\r\n<li>Strasbourg et Eurometropole</li>\r\n<li>Colmar et environs</li>\r\n<li>Mulhouse et environs</li>\r\n<li>Haguenau</li>\r\n<li>Selestat</li>\r\n<li>Autres communes d Alsace sur demande</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- FAQ -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Questions frequentes</h2>\r\n<p class=\"muted\">Peinture exterieure : ce qu il faut savoir.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>Quelle est la meilleure periode pour peindre dehors ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">On privilegie une meteo stable. Le planning est adapte pour garantir une bonne prise et une finition propre.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Faut il traiter avant de repeindre ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Selon l etat du support (humidite, traces, anciennes peintures). Nous adaptons la preparation pour assurer la tenue.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>La peinture exterieure tient combien de temps ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Cela depend du support, exposition et produits utilises. Une preparation serieuse augmente fortement la durabilite.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Est ce que vous protegeez les abords ?</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Oui. Protections des zones sensibles, masquage et organisation font partie de la methode.</p>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- CTA FINAL -->\r\n<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Un projet de peinture exterieure ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px; line-height: 1.75;\">Decris ton projet (elements a peindre, surfaces, ville, delai) et nous te repondons rapidement avec une estimation claire.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n</div>\r\n</section>', 'Peinture exterieure en Alsace | Joker Peintre', 'Peinture extérieure premium en Alsace : façades, volets, boiseries, murets. Préparation soignée, produits résistants aux intempéries. Devis gratuit.', 1, 0, 'default', '2026-02-27 14:52:43', '2026-02-28 17:53:12'),
(7, NULL, 'realisations', 'Réalisations', 'II Des chantiers soignes, des finitions visibles', 'Nos realisations en Alsace', '<section class=\"section top\">\r\n<div class=\"container\">\r\n<p class=\"muted\" style=\"max-width: 800px; font-size: 18px; line-height: 1.75;\">Chaque projet est realise avec precision et exigence. Voici quelques exemples de travaux effectues en Alsace : peinture interieure, renovation de facade, crepi et finitions decoratives.</p>\r\n<div style=\"margin-top: 14px;\"><a class=\"btn btn-primary\" href=\"../contact\">Demander un devis</a></div>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<figure class=\"gallery-shortcode\" contenteditable=\"false\" data-gallery-id=\"1\"><span class=\"gs-icon\">🖼</span><span class=\"gs-title\">Galerie &mdash; Galerie r&eacute;alisations</span><span class=\"gs-meta\">id=1</span></figure>\r\n<div class=\"local\"><span class=\"kicker\"> <strong> Chantiers en Alsace</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Projets realises dans le Bas-Rhin et le Haut-Rhin</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient dans toute l Alsace pour des travaux de peinture interieure, exterieure et renovation facade.</p>\r\n<ul>\r\n<li>Strasbourg</li>\r\n<li>Colmar</li>\r\n<li>Mulhouse</li>\r\n<li>Haguenau</li>\r\n<li>Selestat</li>\r\n<li>Saint-Louis</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>', 'Realisations - Joker Peintre | Travaux de peinture en Alsace', 'Photos de chantiers en Alsace : peinture interieure et exterieure, facade, crepi et effet pierre.', 1, 0, 'default', '2026-02-27 17:28:33', '2026-02-28 05:07:07'),
(8, NULL, 'contact', 'Contact', 'Parlons de votre projet', 'Contact & Devis gratuit', '<section class=\"section top\">\r\n<div class=\"container\">\r\n<p class=\"muted\" style=\"max-width: 700px; font-size: 18px;\">Decrivez votre projet (surface, type de travaux, ville) et recevez une estimation rapide.</p>\r\n</div>\r\n</section>\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"grid-3\" style=\"grid-template-columns: 1.2fr .8fr; gap: 28px;\">\r\n<div class=\"card\">\r\n<figure class=\"form-shortcode\" contenteditable=\"false\" data-form-slug=\"contact\"><span class=\"fs-icon\">📋</span><span class=\"fs-title\">Formulaire &mdash; Formulaire contact</span><span class=\"fs-meta\">slug=contact</span></figure>\r\n<p>&nbsp;</p>\r\n</div>\r\n<div>\r\n<div>\r\n<div class=\"card\" style=\"margin-bottom: 18px;\">\r\n<h3>Coordonnees</h3>\r\n<p class=\"muted\">Tel : 07 83 86 86 22<br>Email : joker-peintre@outlook.fr<br>Zone : Alsace</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Pourquoi nous contacter ?</h3>\r\n<p class=\"muted\">✔ Devis gratuit<br>✔ Reponse rapide<br>✔ Travail soign&eacute;<br>✔ Intervention Bas-Rhin &amp; Haut-Rhin</p>\r\n</div>\r\n<div class=\"card\" style=\"margin-top: 18px;\">\r\n<h3>Ce que vous obtenez</h3>\r\n<p class=\"muted\">✔ Estimation rapide<br>✔ Conseils sur finitions et teintes<br>✔ Intervention Alsace (Bas-Rhin / Haut-Rhin)<br>✔ Travail soigne et propre</p>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>', 'Contact - Joker Peintre | Devis gratuit en Alsace', 'Contactez Joker Peintre pour un devis gratuit en Alsace. Peinture interieure, facade, isolation et decoration.', 1, 0, 'default', '2026-02-27 20:05:58', '2026-02-27 21:41:59'),
(9, NULL, 'mentions-legales', 'Mentions légales', '', '', '<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Besoin d un devis pour votre projet ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px;\">Decrivez votre projet (surface, type de support, ville) et recevez une estimation rapide.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"../contact\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"tel:+33783868622\">Appeler 07 83 86 86 22</a></div>\r\n</div>\r\n</section>', '', '', 1, 0, 'default', '2026-02-27 22:13:40', '2026-02-28 03:37:37'),
(10, NULL, 'politique-confidentialite', 'Confidentialité', '', '', '<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Besoin d un devis pour votre projet ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px;\">Decrivez votre projet (surface, type de support, ville) et recevez une estimation rapide.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"../contact\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"tel:+33783868622\">Appeler 07 83 86 86 22</a></div>\r\n</div>\r\n</section>', '', '', 1, 0, 'default', '2026-02-27 22:13:52', '2026-02-28 03:37:24'),
(11, 4, 'isolation-interieure-exterieure', 'Isolation intérieure / extérieure', 'Isolation interieure et exterieure en Alsace', 'Prestations - Isolation interieure & exterieure', '<!-- HERO -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div style=\"display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; flex-wrap: wrap; margin-top: 14px;\">\r\n<div style=\"max-width: 820px;\">\r\n<p class=\"muted\" style=\"font-size: 18px; line-height: 1.75; margin: 0;\">Joker Peintre vous accompagne dans vos travaux d isolation pour ameliorer le confort thermique de votre habitation et reduire les pertes energetiques. Une solution efficace pour valoriser votre bien et realiser des economies.</p>\r\n<div style=\"display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n<div class=\"muted small\" style=\"margin-top: 12px;\">Intervention Bas-Rhin et Haut-Rhin : Strasbourg, Colmar, Mulhouse, Haguenau, Selestat et alentours.</div>\r\n</div>\r\n<div class=\"card\" style=\"min-width: 280px; max-width: 360px;\">\r\n<div class=\"kicker\" style=\"margin-bottom: 14px;\"><strong>Objectifs</strong></div>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Reduction des pertes thermiques</li>\r\n<li>Amelioration du confort interieur</li>\r\n<li>Optimisation energetique</li>\r\n<li>Valorisation du bien immobilier</li>\r\n<li>Solution durable</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- TYPES D ISOLATION -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Nos solutions d isolation</h2>\r\n<p class=\"muted\">Une approche adaptee a votre configuration.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>Isolation interieure</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Mise en oeuvre de solutions isolantes par l interieur pour limiter les pertes thermiques et optimiser le confort.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Isolation exterieure</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Solution performante pour envelopper le batiment et reduire significativement les deperditions energetiques.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Integration esthetique</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Finition soignee et harmonieuse pour conserver l esthetique de votre facade ou de vos espaces interieurs.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- PROCESS -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Notre methode</h2>\r\n<p class=\"muted\">Une intervention claire et organisee.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>1. Evaluation technique</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Analyse des besoins thermiques et de la configuration du batiment.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>2. Mise en oeuvre</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Installation des solutions isolantes adaptees selon le support.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>3. Finition et controle</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Finition propre et verification du rendu pour un resultat durable.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- AVANTAGES -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Pourquoi isoler son habitation ?</h2>\r\n<p class=\"muted\">Un investissement rentable et durable.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>Confort thermique</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Temperature plus stable en hiver comme en ete.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Economies d energie</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Reduction des consommations energetiques et meilleure performance globale.</p>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- SEO LOCAL -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"local\"><span class=\"kicker\"><strong>Isolation en Alsace</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Bas-Rhin et Haut-Rhin</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient en Alsace pour vos travaux d isolation interieure et exterieure.</p>\r\n<ul>\r\n<li>Strasbourg</li>\r\n<li>Colmar</li>\r\n<li>Mulhouse</li>\r\n<li>Haguenau</li>\r\n<li>Selestat</li>\r\n<li>Communes environnantes</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- CTA FINAL -->\r\n<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Un projet d isolation ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px; line-height: 1.75;\">Decris ton projet (type d isolation, surface, ville, delai) et nous te repondons rapidement avec une estimation claire.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n</div>\r\n</section>', 'Isolation interieure et exterieure en Alsace | Joker Peintre', 'Isolation interieure et exterieure en Alsace : amelioration thermique, reduction des pertes energetiques et confort durable. Intervention Bas-Rhin et Haut-Rhin. Devis gratuit.', 1, 0, 'default', '2026-02-27 22:28:23', '2026-02-28 17:41:28'),
(12, 4, 'travaux-de-facade', 'Travaux de facade', 'Travaux de facade en Alsace : protection et valorisation de votre bien', 'Prestations - Travaux de facade', '<section class=\"section\">\r\n<div class=\"container\">\r\n<div style=\"display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; flex-wrap: wrap; margin-top: 14px;\">\r\n<div style=\"max-width: 820px;\">\r\n<p class=\"muted\" style=\"font-size: 18px; line-height: 1.75; margin: 0;\">Joker Peintre realise vos travaux de facade avec rigueur et savoir-faire. Renovation, ravalement, traitement et finition : l objectif est de proteger durablement votre habitation tout en ameliorant son esthetique.</p>\r\n<div style=\"display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n<div class=\"muted small\" style=\"margin-top: 12px;\">Intervention Bas-Rhin et Haut-Rhin : Strasbourg, Colmar, Mulhouse, Haguenau, Selestat et alentours.</div>\r\n</div>\r\n<div class=\"card\" style=\"min-width: 280px; max-width: 360px;\">\r\n<div class=\"kicker\" style=\"margin-bottom: 14px;\"><strong>Inclus</strong></div>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Diagnostic de l etat de la facade</li>\r\n<li>Nettoyage et preparation</li>\r\n<li>Reprise fissures et imperfections</li>\r\n<li>Application systeme adapte</li>\r\n<li>Finition propre et durable</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- TYPES DE TRAVAUX -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Nos travaux de facade</h2>\r\n<p class=\"muted\">Une intervention adaptee a chaque support.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>Ravalement de facade</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Nettoyage et remise en etat pour redonner eclat et protection a votre habitation.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Reprise fissures</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Traitement et reparation des fissures pour eviter infiltrations et degradations.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Protection et finition</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Application de revetements ou peintures specifiques pour une tenue durable face aux intemperies.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- PROCESS -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Notre methode</h2>\r\n<p class=\"muted\">Une approche serieuse pour un resultat durable.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>1. Analyse du support</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Evaluation de l etat general, identification des zones fragilisees.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>2. Preparation</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Nettoyage, traitement, reprise fissures et mise en condition du support.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>3. Application</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Mise en oeuvre du systeme choisi avec finition propre et uniforme.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- AVANTAGES -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Pourquoi renover sa facade ?</h2>\r\n<p class=\"muted\">Un investissement utile et valorisant.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-2\">\r\n<div class=\"card\">\r\n<h3>Protection durable</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Protection contre humidite, fissures et degradations liees aux intemperies.</p>\r\n</div>\r\n<div class=\"card\">\r\n<h3>Valorisation du bien</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Une facade renovee ameliore l apparence et la valeur de votre habitation.</p>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- SEO LOCAL -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"local\"><span class=\"kicker\"><strong>Travaux de facade en Alsace</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Bas-Rhin et Haut-Rhin</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient en Alsace pour vos travaux de facade, ravalement et renovation exterieure.</p>\r\n<ul>\r\n<li>Strasbourg</li>\r\n<li>Colmar</li>\r\n<li>Mulhouse</li>\r\n<li>Haguenau</li>\r\n<li>Selestat</li>\r\n<li>Communes environnantes</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- CTA FINAL -->\r\n<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Un projet de renovation de facade ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px; line-height: 1.75;\">Decris ton projet (type de facade, surface, ville, delai) et nous te repondons rapidement avec une estimation claire.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n</div>\r\n</section>', 'Travaux de facade en Alsace | Joker Peintre', 'Travaux de facade en Alsace : renovation, ravalement, protection durable et finition soignee. Intervention Bas-Rhin et Haut-Rhin. Devis gratuit.', 1, 0, 'default', '2026-02-27 22:28:39', '2026-02-28 17:38:48'),
(13, 4, 'revetements-muraux-et-decoration', 'Revêtements muraux et décoration', 'Revetements muraux et decoration en Alsace', 'Prestations - Revetements muraux & Decoration', '<!-- HERO -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div style=\"display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; flex-wrap: wrap; margin-top: 14px;\">\r\n<div style=\"max-width: 820px;\">\r\n<p class=\"muted\" style=\"font-size: 18px; line-height: 1.75; margin: 0;\">Joker Peintre realise vos projets decoratifs avec precision et esthetique. Papier peint, toile de verre, effets decoratifs ou finitions speciales : chaque detail est soigne pour un rendu harmonieux et durable.</p>\r\n<div style=\"display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n<div class=\"muted small\" style=\"margin-top: 12px;\">Intervention Bas-Rhin et Haut-Rhin : Strasbourg, Colmar, Mulhouse, Haguenau, Selestat et alentours.</div>\r\n</div>\r\n<div class=\"card\" style=\"min-width: 280px; max-width: 360px;\">\r\n<div class=\"kicker\" style=\"margin-bottom: 14px;\"><strong>Inclus</strong></div>\r\n<ul class=\"muted\" style=\"line-height: 1.9; padding-left: 18px; margin: 0;\">\r\n<li>Preparation soignee des supports</li>\r\n<li>Pose precise et ajustements</li>\r\n<li>Finitions propres et nettes</li>\r\n<li>Conseil sur choix decoratif</li>\r\n<li>Nettoyage fin de chantier</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- TYPES DE REVETEMENTS -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Types de revetements muraux</h2>\r\n<p class=\"muted\">Des solutions decoratives adaptees a chaque interieur.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>Papier peint</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Pose de papier peint classique, panoramique ou design. Alignement precis des motifs et finitions nettes.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Toile de verre</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Solution durable pour renforcer et uniformiser les murs. Ideal en renovation.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Effets decoratifs</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Enduits decoratifs, effet beton, effet pierre ou finitions structurees. Apport de relief et cachet haut de gamme.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- APPROCHE -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"section-title\">\r\n<div>\r\n<h2>Une approche esthetique et technique</h2>\r\n<p class=\"muted\">La decoration demande rigueur et precision.</p>\r\n</div>\r\n</div>\r\n<div class=\"grid-3\">\r\n<article class=\"card\">\r\n<h3>Preparation du support</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Rebouchage, poncage et mise en etat du mur pour assurer une pose parfaite.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Pose meticuleuse</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Ajustement des raccords, angles propres, decoupes precises.</p>\r\n</article>\r\n<article class=\"card\">\r\n<h3>Rendu harmonieux</h3>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Integration coherente dans l espace pour un interieur valorise.</p>\r\n</article>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- SEO LOCAL -->\r\n<section class=\"section\">\r\n<div class=\"container\">\r\n<div class=\"local\"><span class=\"kicker\"><strong>Decoration murale en Alsace</strong></span>\r\n<h2 style=\"margin: 14px 0 8px;\">Intervention Bas-Rhin et Haut-Rhin</h2>\r\n<p class=\"muted\" style=\"line-height: 1.75;\">Joker Peintre intervient en Alsace pour vos projets de decoration murale et revetements : maisons, appartements, commerces et bureaux.</p>\r\n<ul>\r\n<li>Strasbourg</li>\r\n<li>Colmar</li>\r\n<li>Mulhouse</li>\r\n<li>Haguenau</li>\r\n<li>Selestat</li>\r\n<li>Communes environnantes</li>\r\n</ul>\r\n</div>\r\n</div>\r\n</section>\r\n<!-- CTA -->\r\n<section class=\"section\">\r\n<div class=\"container\" style=\"text-align: center;\">\r\n<h2>Un projet de decoration murale ?</h2>\r\n<p class=\"muted\" style=\"max-width: 650px; margin: 0 auto 20px; line-height: 1.75;\">Decris ton projet (surface, type de revetement, ville, delai) et nous te repondons rapidement avec une estimation claire.</p>\r\n<div style=\"display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;\"><a class=\"btn btn-primary\" href=\"&lt;?= BASE_URL ?&gt;/contact.php\">Demander un devis</a> <a class=\"btn btn-ghost\" href=\"&lt;?= BASE_URL ?&gt;/realisations.php\">Voir des realisations</a></div>\r\n</div>\r\n</section>', 'Revetements muraux et decoration en Alsace | Joker Peintre', 'Revetements muraux et decoration en Alsace : papier peint, toile de verre, effets decoratifs, finitions haut de gamme. Devis gratuit.', 1, 0, 'default', '2026-02-27 22:28:52', '2026-02-28 17:33:19');

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
(8, '2026-02-28 02:53:19', 'KHALFI Abdeljaouad', 'akhalfi@acometis.com', '0659337820', 'Wittennheim', 'Peinture extérieure', '110m2', 'Faire un ravalement de facade bi-color.', '127.0.0.1', 0.90, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', 'treated', 'won', NULL, 0, NULL, '2026-02-28 01:58:57'),
(9, '2026-02-28 02:56:23', 'John Smith', 'abdeljaouad.khalfi@gmail.com', '0659337820', 'Mulhouse', 'Isolation', '45m2', 'Isolation des combles', '127.0.0.1', 0.90, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', 'new', 'lost', NULL, 0, '2026-02-28 03:20:12', '2026-02-28 03:20:12'),
(10, '2026-02-28 02:58:05', 'Spiderdev', 'abdeljaouad.khalfi@gmail.com', '', 'Mulhouse', 'Crépi / Façade', '110', 'details', '127.0.0.1', 0.90, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', 'treated', 'won', NULL, 0, NULL, '2026-02-28 02:21:07');

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

-- --------------------------------------------------------

--
-- Structure de la table `crm_clients`
--

DROP TABLE IF EXISTS `crm_clients`;
CREATE TABLE `crm_clients` (
  `id` int NOT NULL,
  `contact_id` int DEFAULT NULL COMMENT 'Lien vers contacts si converti',
  `ref` varchar(20) NOT NULL,
  `type` enum('particulier','professionnel') NOT NULL DEFAULT 'particulier',
  `name` varchar(150) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `crm_clients`
--

INSERT INTO `crm_clients` (`id`, `contact_id`, `ref`, `type`, `name`, `company`, `email`, `phone`, `address`, `city`, `zip`, `notes`, `created_at`, `updated_at`) VALUES
(2, 8, 'CLI-0001', 'particulier', 'KHALFI Abdeljaouad', '', 'akhalfi@acometis.com', '0659337820', '185 rue du Dc. Albert Schweitzer', 'Wittennheim', '68270', 'Converti depuis contact #8\r\nService : Peinture extérieure\r\nMessage : Faire un ravalement de facade bi-color.', '2026-02-28 01:58:57', '2026-02-28 01:59:19'),
(3, 10, 'CLI-0002', 'professionnel', 'Spiderdev', 'Spiderdev', 'abdeljaouad.khalfi@gmail.com', '', '10 rue Hector Berlioz', 'Ruelisheim', '68270', 'Converti depuis contact #10\r\nService : Crépi / Façade\r\nMessage : details', '2026-02-28 02:21:07', '2026-02-28 02:21:43');

-- --------------------------------------------------------

--
-- Structure de la table `crm_devis`
--

DROP TABLE IF EXISTS `crm_devis`;
CREATE TABLE `crm_devis` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `ref` varchar(30) NOT NULL,
  `type` enum('devis','facture') NOT NULL DEFAULT 'devis',
  `status` enum('draft','sent','accepted','refused','invoiced','paid') NOT NULL DEFAULT 'draft',
  `title` varchar(190) DEFAULT NULL,
  `intro` text,
  `footer_note` text,
  `total_ht` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tva_rate` decimal(5,2) NOT NULL DEFAULT '10.00',
  `total_ttc` decimal(10,2) NOT NULL DEFAULT '0.00',
  `issued_at` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `paid_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `crm_devis`
--

INSERT INTO `crm_devis` (`id`, `client_id`, `ref`, `type`, `status`, `title`, `intro`, `footer_note`, `total_ht`, `tva_rate`, `total_ttc`, `issued_at`, `valid_until`, `paid_at`, `created_at`, `updated_at`) VALUES
(2, 2, 'DEV-2026-001', 'facture', 'paid', 'Ravalement de facade - Wittenheim', 'Faire un ravalement de facade bi-color.', 'Devis valable 30 jours. TVA 10 % (travaux de rénovation). Paiement à réception de facture.', 3000.00, 20.00, 3600.00, '2026-02-28', '2026-03-30', '2026-03-30', '2026-02-28 02:02:20', '2026-02-28 04:31:03');

-- --------------------------------------------------------

--
-- Structure de la table `crm_devis_lines`
--

DROP TABLE IF EXISTS `crm_devis_lines`;
CREATE TABLE `crm_devis_lines` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `description` varchar(500) NOT NULL DEFAULT '',
  `qty` decimal(8,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(30) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `crm_devis_lines`
--

INSERT INTO `crm_devis_lines` (`id`, `devis_id`, `sort_order`, `description`, `qty`, `unit`, `unit_price`, `total`) VALUES
(26, 2, 0, 'Peinture facade nord, 2 couches', 45.00, 'm2', 20.00, 900.00),
(27, 2, 1, 'Peinture facade sud, 2 couches', 45.00, 'm2', 20.00, 900.00),
(28, 2, 2, 'Peinture pignon droit, 2 couches', 60.00, 'm2', 20.00, 1200.00);

-- --------------------------------------------------------

--
-- Structure de la table `forms`
--

DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `fields` json NOT NULL DEFAULT (json_array()),
  `settings` json NOT NULL DEFAULT (json_object()),
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `forms`
--

INSERT INTO `forms` (`id`, `name`, `slug`, `description`, `fields`, `settings`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Formulaire contact', 'contact', 'Formulaire de contact / demande de devis présent sur la page Contact', '{\"steps\": [{\"label\": \"Coordonnées\", \"fields\": [{\"id\": \"f69a1fd3edb9f6\", \"name\": \"name\", \"type\": \"text\", \"label\": \"Nom complet\", \"required\": 1, \"placeholder\": \"\"}, {\"id\": \"f69a1fd3edb9f7\", \"name\": \"email\", \"type\": \"email\", \"label\": \"Email\", \"required\": 1, \"placeholder\": \"\"}, {\"id\": \"f69a1fd3edb9f8\", \"name\": \"phone\", \"type\": \"tel\", \"label\": \"Téléphone\", \"required\": 0, \"placeholder\": \"\"}, {\"id\": \"f69a1fd3edb9f9\", \"name\": \"city\", \"type\": \"text\", \"label\": \"Ville\", \"required\": 1, \"placeholder\": \"Ex: Strasbourg\"}]}, {\"label\": \"Projet\", \"fields\": [{\"id\": \"f69a1fd3edb9fa\", \"name\": \"service\", \"type\": \"select\", \"label\": \"Type de travaux\", \"options\": [\"Peinture intérieure\", \"Peinture extérieure\", \"Crépi / Façade\", \"Isolation\", \"Mosaïque effet pierre\"], \"required\": 0}, {\"id\": \"f69a1fd3edb9fb\", \"name\": \"surface\", \"type\": \"text\", \"label\": \"Surface approx.\", \"required\": 0, \"placeholder\": \"Ex: 80 m2\"}, {\"id\": \"f69a1fd3edb9fc\", \"name\": \"message\", \"rows\": 5, \"type\": \"textarea\", \"label\": \"Description du projet\", \"required\": 1, \"placeholder\": \"Ex: murs salon + plafond, support propre, délai 3 semaines...\"}]}]}', '{\"email_to\": \"akhalfi@acometis.com\", \"redirect_url\": \"contact\", \"submit_label\": \"Envoyer la demande\", \"email_subject\": \"Nouveau contact - Joker Peintre\", \"use_recaptcha\": true, \"save_submission\": true, \"success_message\": \"Merci. Nous vous recontactons rapidement.\"}', 1, '2026-02-27 21:13:29', '2026-02-27 21:31:50');

-- --------------------------------------------------------

--
-- Structure de la table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
CREATE TABLE `form_submissions` (
  `id` int NOT NULL,
  `form_id` int NOT NULL,
  `data` json NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `form_submissions`
--

INSERT INTO `form_submissions` (`id`, `form_id`, `data`, `ip`, `user_agent`, `is_read`, `created_at`) VALUES
(5, 1, '{\"city\": \"Wittennheim\", \"name\": \"KHALFI Abdeljaouad\", \"email\": \"akhalfi@acometis.com\", \"phone\": \"0659337820\", \"message\": \"Faire un ravalement de facade bi-color.\", \"service\": \"Peinture extérieure\", \"surface\": \"110m2\"}', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', 1, '2026-02-28 02:53:19'),
(6, 1, '{\"city\": \"Mulhouse\", \"name\": \"John Smith\", \"email\": \"abdeljaouad.khalfi@gmail.com\", \"phone\": \"0659337820\", \"message\": \"Isolation des combles\", \"service\": \"Isolation\", \"surface\": \"45m2\"}', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', 1, '2026-02-28 02:56:23'),
(7, 1, '{\"city\": \"Mulhouse\", \"name\": \"Spiderdev\", \"email\": \"abdeljaouad.khalfi@gmail.com\", \"phone\": \"\", \"message\": \"details\", \"service\": \"Crépi / Façade\", \"surface\": \"110\"}', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0', 1, '2026-02-28 02:58:05');

-- --------------------------------------------------------

--
-- Structure de la table `galleries`
--

DROP TABLE IF EXISTS `galleries`;
CREATE TABLE `galleries` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(190) NOT NULL,
  `description` text,
  `show_item_labels` tinyint(1) NOT NULL DEFAULT '1',
  `show_gallery_header` tinyint(1) NOT NULL DEFAULT '1',
  `items_per_page` tinyint NOT NULL DEFAULT '6',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `galleries`
--

INSERT INTO `galleries` (`id`, `name`, `description`, `show_item_labels`, `show_gallery_header`, `items_per_page`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Galerie réalisations', 'Toutes nos réalisations', 1, 0, 6, 0, '2026-02-27 17:20:47', '2026-02-27 18:13:48'),
(3, 'Home Page', '', 1, 1, 6, 0, '2026-02-27 17:47:17', '2026-02-27 19:09:53');

-- --------------------------------------------------------

--
-- Structure de la table `gallery_items`
--

DROP TABLE IF EXISTS `gallery_items`;
CREATE TABLE `gallery_items` (
  `id` int UNSIGNED NOT NULL,
  `gallery_id` int UNSIGNED NOT NULL,
  `realisation_id` int NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `gallery_items`
--

INSERT INTO `gallery_items` (`id`, `gallery_id`, `realisation_id`, `sort_order`) VALUES
(35, 2, 8, 0),
(36, 2, 1, 1),
(37, 2, 2, 2),
(38, 2, 3, 3),
(39, 2, 4, 4),
(40, 2, 5, 5),
(41, 2, 6, 6),
(67, 1, 1, 0),
(68, 1, 2, 1),
(69, 1, 3, 2),
(70, 1, 4, 3),
(71, 1, 5, 4),
(72, 1, 6, 5),
(73, 3, 8, 0),
(74, 3, 1, 1),
(75, 3, 2, 2),
(76, 3, 3, 3),
(77, 3, 4, 4),
(78, 3, 5, 5),
(79, 3, 6, 6);

-- --------------------------------------------------------

--
-- Structure de la table `media_meta`
--

DROP TABLE IF EXISTS `media_meta`;
CREATE TABLE `media_meta` (
  `id` int UNSIGNED NOT NULL,
  `rel` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'chemin relatif depuis assets/images/',
  `alt_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `media_meta`
--

INSERT INTO `media_meta` (`id`, `rel`, `alt_text`, `created_at`, `updated_at`) VALUES
(1, 'medias/finition-haut-de-gamme.png', 'Finition haut de gamme', '2026-02-27 15:33:01', '2026-02-27 15:33:01'),
(2, 'medias/rigueur-et-preparation.png', 'Rigeur et preparation', '2026-02-27 15:33:11', '2026-02-27 15:33:11'),
(3, 'medias/preparation-des-supports.png', 'Preparation des supports', '2026-02-27 15:33:21', '2026-02-27 15:33:21'),
(4, 'medias/respect-du-chantier.png', 'Respect du chantier', '2026-02-27 15:33:26', '2026-02-27 15:33:26');

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
(21, 6, 'assets/images/realisations/bfc697d5f60be64965de3a95dcdb8eda.webp', NULL, 0, '2026-02-26 18:58:32'),
(25, 1, 'assets/images/realisations/0488ed5d2a4c06452ecd46126a118945.webp', NULL, 0, '2026-02-27 17:36:42'),
(26, 1, 'assets/images/realisations/11c723018a2d560e785d437053d1b1bd.webp', NULL, 0, '2026-02-27 17:36:42');

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
(4, 'realisations_cta_link', '/contact.php', '2026-02-26 17:58:06'),
(5, 'realisations_meta_title', 'Realisations - Joker Peintre | Travaux de peinture en Alsace', '2026-02-26 17:58:06'),
(6, 'realisations_meta_desc', 'Photos de chantiers en Alsace : peinture interieure et exterieure, facade, crepi et effet pierre.', '2026-02-26 17:58:06'),
(25, 'realisations_per_page', '6', '2026-02-26 17:58:06'),
(40, 'realisations_before_after_enabled', '1', '2026-02-28 02:16:56'),
(41, 'realisations_before_after_title', 'Avant / Apres', '2026-02-28 02:16:56'),
(42, 'realisations_before_after_subtitle', 'La difference se voit dans les details.', '2026-02-28 02:16:56'),
(43, 'realisations_before_after_label', 'Transformation complete', '2026-02-26 17:58:06'),
(44, 'realisations_before_after_block1_title', 'Preparation minutieuse', '2026-02-26 17:58:06'),
(45, 'realisations_before_after_block1_text', 'Traitement fissures, sous-couche adaptee, protection complete.', '2026-02-26 17:58:06'),
(46, 'realisations_before_after_block2_title', 'Finition propre', '2026-02-26 17:58:06'),
(47, 'realisations_before_after_block2_text', 'Uniformite, angles nets et rendu durable.', '2026-02-26 17:58:06'),
(48, 'realisations_before_after_image_before', 'assets/images/realisations/before_1772124959.webp', '2026-02-26 17:55:59'),
(49, 'realisations_before_after_image_after', 'assets/images/realisations/after_1772125086.webp', '2026-02-26 17:58:06'),
(191, 'home_realisations_title', 'Realisations', '2026-02-28 02:16:56'),
(192, 'home_realisations_text', 'Decouvre quelques projets recents en Alsace. Finition propre, rendu durable.', '2026-02-28 02:16:56'),
(193, 'company_name', 'Joker Peintre', '2026-02-27 16:59:53'),
(194, 'company_phone', '+33783868622', '2026-02-27 16:59:53'),
(195, 'company_phone_display', '07 83 86 86 22', '2026-02-27 16:59:53'),
(196, 'company_email', 'contact@joker-peintre.fr', '2026-02-27 16:59:53'),
(197, 'company_region', 'Alsace', '2026-02-27 16:59:53'),
(198, 'company_address', '', '2026-02-27 16:59:53'),
(199, 'company_siret', '', '2026-02-27 16:59:53'),
(200, 'nav_items', '[{\"label\":\"Accueil\",\"url\":\"/\",\"children\":[]},{\"label\":\"A propos\",\"url\":\"/a-propos\",\"children\":[]},{\"label\":\"Prestations\",\"url\":\"/prestations\",\"children\":[{\"label\":\"Peinture interieure\",\"url\":\"/prestations/peinture-interieure-en-alsace\"},{\"label\":\"Isolation intérieure / extérieure\",\"url\":\"/prestations/isolation-interieure-exterieure\"},{\"label\":\"Travaux de facade\",\"url\":\"/prestations/travaux-de-facade\"},{\"label\":\"Revêtements muraux et décoration\",\"url\":\"/prestations/revetements-muraux-et-decoration\"},{\"label\":\"Peinture exterieure\",\"url\":\"/prestations/peinture-exterieure-en-alsace\"}]},{\"label\":\"Réalisations\",\"url\":\"/realisations\",\"children\":[]},{\"label\":\"Contact\",\"url\":\"/contact\",\"children\":[]}]', '2026-02-27 22:29:33'),
(201, 'home_meta_title', 'Joker Peintre - Peinture & Decoration en Alsace', '2026-02-28 02:16:56'),
(202, 'home_meta_desc', 'Entreprise de peinture en Alsace : interieur, exterieur, isolation, crepi facade et mosaique effet pierre. Devis gratuit rapide.', '2026-02-28 02:16:56'),
(203, 'home_hero_kicker', 'Votre artisan peintre en Alsace', '2026-02-28 02:16:56'),
(204, 'home_hero_title', 'Finitions haut de gamme pour vos murs, facades et renovations', '2026-02-28 02:16:56'),
(205, 'home_hero_text', 'Peinture interieure et exterieure, isolation, rénovation, revêtements muraux, boiserie, décoration et mosaïques... Votre projet maitrisé de A a Z, avec une attention particulière aux détails et finitions', '2026-02-28 02:16:56'),
(206, 'home_hero_cta_primary', 'Demander un devis gratuit', '2026-02-28 02:16:56'),
(207, 'home_hero_cta_secondary', 'Voir les prestations', '2026-02-28 02:16:56'),
(208, 'home_approach_title', 'Une approche premium, simple et transparente', '2026-02-28 02:16:56'),
(209, 'home_approach_text', 'Preparation serieuse, materiaux adaptes, execution propre. L objectif : un resultat net et durable.', '2026-02-28 02:16:56'),
(210, 'home_trust_badge1', 'Devis rapide', '2026-02-28 02:16:56'),
(211, 'home_trust_badge2', 'Finitions propres', '2026-02-28 02:16:56'),
(212, 'home_trust_badge3', 'Intervention Alsace', '2026-02-28 02:16:56'),
(213, 'home_cta_devis_title', 'Besoin d un devis ?', '2026-02-28 02:16:56'),
(214, 'home_cta_devis_text', 'Reponse rapide. Decris ton projet, surface, ville et delai.', '2026-02-28 02:16:56'),
(215, 'about_meta_title', 'A propos - Joker Peintre | Entreprise de peinture en Alsace', '2026-02-27 12:36:58'),
(216, 'about_meta_desc', 'Decouvrez Joker Peintre, entreprise de peinture en Alsace specialisee en interieur, exterieur, isolation et crepi facade. Travail propre et finitions haut de gamme.', '2026-02-27 12:36:58'),
(217, 'about_kicker', 'Entreprise locale en Alsace', '2026-02-27 12:36:58'),
(218, 'about_h1', 'Une entreprise de peinture engagee pour un travail propre et durable 25', '2026-02-27 12:36:58'),
(219, 'about_intro', 'Joker Peintre est une entreprise de peinture basee en Alsace, specialisee dans la peinture interieure, exterieure, l isolation, le crepi facade et la mosaique effet pierre. Chaque chantier est realise avec rigueur, precision et souci du detail.', '2026-02-27 12:36:58'),
(220, 'about_card1_title', 'Rigueur et preparation', '2026-02-27 12:36:58'),
(221, 'about_card1_text', 'La qualite d une finition depend de la preparation. Protection des surfaces, rebouchage, poncage et traitement des supports sont effectues avec precision.', '2026-02-27 12:36:58'),
(222, 'about_card2_title', 'Finition haut de gamme', '2026-02-27 12:36:58'),
(223, 'about_card2_text', 'Application reguliere, angles nets, rendu uniforme. L objectif est un resultat esthetique et durable.', '2026-02-27 12:36:58'),
(224, 'about_card3_title', 'Respect du chantier', '2026-02-27 12:36:58'),
(225, 'about_card3_text', 'Organisation, proprete et respect des delais. Chaque projet est realise avec professionnalisme.', '2026-02-27 12:36:58'),
(226, 'about_expertise_title', 'Une expertise adaptee a chaque projet', '2026-02-27 12:36:58'),
(227, 'about_expertise_sub', 'Chaque logement ou facade a ses contraintes. Une solution adaptee est proposee selon le support et l environnement.', '2026-02-27 12:36:58'),
(228, 'about_expertise_body', 'Que ce soit pour une renovation interieure complete, une remise en etat de facade ou un projet decoratif comme la mosaique effet pierre, Joker Peintre accompagne ses partenaires de A a Z : conseil sur les teintes, choix des materiaux, preparation technique et execution soignee.', '2026-02-27 12:36:58'),
(229, 'about_expertise_zone', 'L entreprise intervient dans toute l Alsace (Bas-Rhin et Haut-Rhin) pour des projets de particuliers et professionnels.', '2026-02-27 12:36:58'),
(230, 'about_zone_cities', 'Strasbourg,Haguenau,Selestat,Colmar,Mulhouse,Saint-Louis', '2026-02-27 12:36:58'),
(231, 'about_cta_title', 'Besoin d un devis pour votre projet ?', '2026-02-27 12:36:58'),
(232, 'about_cta_text', 'Decrivez votre projet (surface, type de support, ville) et recevez une estimation rapide.', '2026-02-27 12:36:58'),
(233, 'services_meta_title', 'Prestations - Joker Peintre | Peinture & Decoration en Alsace', '2026-02-27 12:19:31'),
(234, 'services_meta_desc', 'Peinture interieure et exterieure en Alsace, isolation, crepi facade et mosaique effet pierre. Devis gratuit et travail soigne.', '2026-02-27 12:19:31'),
(235, 'services_kicker', 'Nos prestations en Alsace', '2026-02-27 12:19:31'),
(236, 'services_h1', 'Des solutions completes pour vos travaux de peinture et renovation', '2026-02-27 12:19:31'),
(237, 'services_intro', 'Joker Peintre intervient pour tous vos projets de peinture interieure, peinture exterieure, isolation, crepi facade et mosaique effet pierre. Chaque prestation est adaptee au support et aux contraintes du chantier.', '2026-02-27 12:19:31'),
(238, 'services_card1_title', 'Peinture interieure', '2026-02-27 12:19:31'),
(239, 'services_card1_text', 'Murs, plafonds, boiseries, renovation complete d appartement ou maison. Preparation soignee et finition uniforme.', '2026-02-27 12:19:31'),
(240, 'services_card1_link', '/prestations/peinture-interieure.php', '2026-02-27 12:19:31'),
(241, 'services_card2_title', 'Peinture exterieure', '2026-02-27 12:19:31'),
(242, 'services_card2_text', 'Protection et embellissement de facades, volets et surfaces exterieures. Peintures resistantes aux intemperies.', '2026-02-27 12:19:31'),
(243, 'services_card2_link', '/prestations/peinture-exterieure.php', '2026-02-27 12:19:31'),
(244, 'services_card3_title', 'Crepi / Facade', '2026-02-27 12:19:31'),
(245, 'services_card3_text', 'Renovation et ravalement de facade. Protection durable et amelioration esthetique.', '2026-02-27 12:19:31'),
(246, 'services_card3_link', '/prestations/crepi-facade.php', '2026-02-27 12:19:31'),
(247, 'services_card4_title', 'Isolation', '2026-02-27 12:19:31'),
(248, 'services_card4_text', 'Solutions pour ameliorer le confort thermique et reduire les pertes energetiques.', '2026-02-27 12:19:31'),
(249, 'services_card4_link', '/prestations/isolation.php', '2026-02-27 12:19:31'),
(250, 'services_card5_title', 'Mosaique effet pierre', '2026-02-27 12:19:31'),
(251, 'services_card5_text', 'Finition decorative effet pierre pour facades et murs. Apporte relief et cachet haut de gamme.', '2026-02-27 12:19:31'),
(252, 'services_card5_link', '/prestations/mosaique-effet-pierre.php', '2026-02-27 12:19:31'),
(253, 'services_method_title', 'Notre methode de travail', '2026-02-27 12:19:31'),
(254, 'services_method_sub', 'Une approche professionnelle pour un resultat durable.', '2026-02-27 12:19:31'),
(255, 'services_cta_title', 'Un projet en cours ?', '2026-02-27 12:19:31'),
(256, 'services_cta_text', 'Contactez Joker Peintre pour un devis gratuit et une estimation adaptee a votre projet.', '2026-02-27 12:19:31'),
(257, 'contact_meta_title', 'Contact - Joker Peintre | Devis gratuit en Alsace', '2026-02-27 12:19:31'),
(258, 'contact_meta_desc', 'Contactez Joker Peintre pour un devis gratuit en Alsace. Peinture interieure, facade, isolation et decoration.', '2026-02-27 12:19:31'),
(259, 'contact_kicker', 'Contact & Devis gratuit', '2026-02-27 12:19:31'),
(260, 'contact_h1', 'Parlons de votre projet', '2026-02-27 12:19:31'),
(261, 'contact_intro', 'Decrivez votre projet (surface, type de travaux, ville) et recevez une estimation rapide.', '2026-02-27 12:19:31'),
(262, 'footer_tagline', 'Société de bâtiment – Peinture & Décoration', '2026-02-27 16:59:53'),
(263, 'footer_zone', 'Intervention en Alsace', '2026-02-27 16:59:53'),
(264, 'seo_global_og_image', '', '2026-02-27 12:19:31'),
(343, 'smtp_host', 'smtp.ionos.fr', '2026-02-27 17:13:35'),
(344, 'smtp_port', '587', '2026-02-27 17:13:35'),
(345, 'smtp_user', 'support@myacometis.fr', '2026-02-27 17:13:35'),
(346, 'smtp_from', 'support@myacometis.fr', '2026-02-27 17:13:35'),
(347, 'smtp_from_name', 'Joker Peintre', '2026-02-27 17:13:35'),
(348, 'smtp_contact_email', 'akhalfi@acometis.com', '2026-02-27 17:13:35'),
(349, 'captcha_site_key', '6Lfv3morAAAAADU6rI6wuE1g6pNqGa_5oA4tGV8D', '2026-02-27 17:03:42'),
(350, 'captcha_secret_key', '6Lfv3morAAAAAOpnfCrB9W67nczN9nOWfmbk6VTv', '2026-02-27 17:03:42'),
(351, 'captcha_min_score', '0.5', '2026-02-27 17:03:42'),
(355, 'smtp_pass', 'Acosd!f4e@AcoP', '2026-02-27 17:13:35'),
(372, 'sitemap_domain', 'https://joker-peintre.fr', '2026-02-28 03:28:34'),
(373, 'sitemap_changefreq', 'monthly', '2026-02-28 03:28:34'),
(374, 'active_theme', 'default', '2026-02-28 03:18:31'),
(450, 'home_featured_realisation_id', '', '2026-02-28 02:16:56'),
(451, 'home_local_title', 'Joker Peintre intervient dans toute l\'Alsace', '2026-02-28 02:16:56'),
(452, 'home_local_intro', 'Bas-Rhin et Haut-Rhin : peinture intérieure, extérieure, isolation, crépi facade et décoration.', '2026-02-28 02:16:56'),
(453, 'home_local_cities', 'Strasbourg, Haguenau, Selestat, Colmar, Mulhouse, Saint-Louis', '2026-02-28 02:16:56'),
(525, 'section_hero_enabled', '1', '2026-02-28 02:16:56'),
(526, 'section_prestations_enabled', '1', '2026-02-28 02:16:56'),
(527, 'section_badges_enabled', '1', '2026-02-28 02:16:56'),
(528, 'section_approche_enabled', '1', '2026-02-28 02:16:56'),
(529, 'section_realisations_enabled', '1', '2026-02-28 02:16:56'),
(530, 'section_ba_enabled', '1', '2026-02-28 02:16:56'),
(531, 'section_cta_enabled', '1', '2026-02-28 02:16:56'),
(532, 'section_local_enabled', '1', '2026-02-28 02:16:56'),
(533, 'home_prestations_items', '[{\"title\":\"Peinture intérieure\",\"subtitle\":\"Tous types de travaux\",\"url\":\"\\/prestations\\/peinture-interieure-en-alsace\",\"enabled\":true},{\"title\":\"Isolation intérieure \\/ extérieure\",\"subtitle\":\"Confort thermique, economies d\'energie, reduction des nuisances sonores\",\"url\":\"\\/prestations\\/isolation-interieure-exterieure\",\"enabled\":true},{\"title\":\"Travaux de facade\",\"subtitle\":\"Rénovation, protection aux intempéries\",\"url\":\"\\/prestations\\/travaux-de-facade\",\"enabled\":true},{\"title\":\"Revêtements muraux et décoration\",\"subtitle\":\"Decoratif, relief, cachet premium\",\"url\":\"\\/prestations\\/revetements-muraux-et-decoration\",\"enabled\":true},{\"title\":\"Peinture exterieure\",\"subtitle\":\"Nettoyage, protection, tenue aux intemperies, rendu durable\",\"url\":\"\\/prestations\\/peinture-exterieure-en-alsace\",\"enabled\":true}]', '2026-02-28 02:16:56'),
(534, 'home_prestations_card_title', 'Prestations', '2026-02-28 02:16:56'),
(535, 'home_prestations_card_subtitle', 'Peinture & Decoration', '2026-02-28 02:16:56'),
(612, 'dash_block_kpi', '1', '2026-02-28 05:25:41'),
(613, 'dash_block_charts', '1', '2026-02-28 05:25:41'),
(614, 'dash_block_recent', '1', '2026-02-28 05:25:41'),
(615, 'dash_block_crm', '1', '2026-02-28 05:25:41'),
(616, 'dash_block_bottom', '1', '2026-02-28 05:25:41'),
(617, 'dash_kpi_contacts_new', '1', '2026-02-28 05:25:41'),
(618, 'dash_kpi_contacts_month', '1', '2026-02-28 05:25:41'),
(619, 'dash_kpi_realisations', '0', '2026-02-28 05:25:41'),
(620, 'dash_kpi_forms', '0', '2026-02-28 05:25:41'),
(621, 'dash_kpi_cms', '0', '2026-02-28 05:25:41'),
(622, 'dash_kpi_crm_clients', '1', '2026-02-28 05:25:41'),
(623, 'dash_kpi_crm_ca', '1', '2026-02-28 05:25:41'),
(624, 'dash_kpi_crm_pending', '1', '2026-02-28 05:25:41'),
(717, 'home_approach_card1_title', 'Préparation des supports', '2026-02-28 02:16:56'),
(718, 'home_approach_card1_text', 'Protection, rebouchage, poncéage et accroche. C\'est la clé d\'une finition haut de gamme.', '2026-02-28 02:16:56'),
(719, 'home_approach_card2_title', 'Finition nette', '2026-02-28 02:16:56'),
(720, 'home_approach_card2_text', 'Angles propres, uniformité, rendu régulier. Un travail qui se voit, sans surprises.', '2026-02-28 02:16:56'),
(721, 'home_approach_card3_title', 'Chantier maîtrisé', '2026-02-28 02:16:56'),
(722, 'home_approach_card3_text', 'Organisation, respect des lieux, nettoyage. Vous retrouvez un espace impeccable.', '2026-02-28 02:16:56');

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
-- Index pour la table `admin_recovery_codes`
--
ALTER TABLE `admin_recovery_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Index pour la table `admin_trusted_devices`
--
ALTER TABLE `admin_trusted_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Index pour la table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_cms_parent` (`parent_id`);

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
-- Index pour la table `crm_clients`
--
ALTER TABLE `crm_clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref` (`ref`),
  ADD KEY `contact_id` (`contact_id`);

--
-- Index pour la table `crm_devis`
--
ALTER TABLE `crm_devis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref` (`ref`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `status` (`status`);

--
-- Index pour la table `crm_devis_lines`
--
ALTER TABLE `crm_devis_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `devis_id` (`devis_id`);

--
-- Index pour la table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `form_submissions`
--
ALTER TABLE `form_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_form_id` (`form_id`);

--
-- Index pour la table `galleries`
--
ALTER TABLE `galleries`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gallery_items`
--
ALTER TABLE `gallery_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_gi` (`gallery_id`,`realisation_id`),
  ADD KEY `idx_gal` (`gallery_id`);

--
-- Index pour la table `media_meta`
--
ALTER TABLE `media_meta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rel` (`rel`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `admin_recovery_codes`
--
ALTER TABLE `admin_recovery_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `admin_trusted_devices`
--
ALTER TABLE `admin_trusted_devices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `cms_pages`
--
ALTER TABLE `cms_pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `contact_notes`
--
ALTER TABLE `contact_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `crm_clients`
--
ALTER TABLE `crm_clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `crm_devis`
--
ALTER TABLE `crm_devis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `crm_devis_lines`
--
ALTER TABLE `crm_devis_lines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `forms`
--
ALTER TABLE `forms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `form_submissions`
--
ALTER TABLE `form_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `galleries`
--
ALTER TABLE `galleries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `gallery_items`
--
ALTER TABLE `gallery_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT pour la table `media_meta`
--
ALTER TABLE `media_meta`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `realisations`
--
ALTER TABLE `realisations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `realisation_images`
--
ALTER TABLE `realisation_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=760;

--
-- AUTO_INCREMENT pour la table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admin_recovery_codes`
--
ALTER TABLE `admin_recovery_codes`
  ADD CONSTRAINT `admin_recovery_codes_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `admin_trusted_devices`
--
ALTER TABLE `admin_trusted_devices`
  ADD CONSTRAINT `admin_trusted_devices_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD CONSTRAINT `fk_cms_parent` FOREIGN KEY (`parent_id`) REFERENCES `cms_pages` (`id`) ON DELETE SET NULL;

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
-- Contraintes pour la table `crm_devis`
--
ALTER TABLE `crm_devis`
  ADD CONSTRAINT `fk_devis_client` FOREIGN KEY (`client_id`) REFERENCES `crm_clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `crm_devis_lines`
--
ALTER TABLE `crm_devis_lines`
  ADD CONSTRAINT `fk_lines_devis` FOREIGN KEY (`devis_id`) REFERENCES `crm_devis` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `form_submissions`
--
ALTER TABLE `form_submissions`
  ADD CONSTRAINT `form_submissions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `realisation_images`
--
ALTER TABLE `realisation_images`
  ADD CONSTRAINT `fk_images_realisation` FOREIGN KEY (`realisation_id`) REFERENCES `realisations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
