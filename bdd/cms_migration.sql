-- =============================================
-- CMS Migration - Joker Peintre
-- Ajoute la table cms_pages + settings CMS
-- =============================================

-- Table pages dynamiques
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------
-- Settings : Entreprise
-- ----------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('company_name',         'Joker Peintre',         NOW()),
('company_phone',        '+33783868622',           NOW()),
('company_phone_display','07 83 86 86 22',          NOW()),
('company_email',        'contact@joker-peintre.fr', NOW()),
('company_region',       'Alsace',                  NOW()),
('company_address',      '',                        NOW()),
('company_siret',        '',                        NOW())
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- ----------------------------------------
-- Settings : Navigation
-- ----------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('nav_items', '[{"label":"Accueil","url":"/"},{"label":"A propos","url":"/a-propos.php"},{"label":"Prestations","url":"/prestations.php"},{"label":"Realisations","url":"/realisations.php"},{"label":"Contact","url":"/contact"}]', NOW())
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- ----------------------------------------
-- Settings : Page Accueil
-- ----------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('home_meta_title',          'Joker Peintre - Peinture & Decoration en Alsace', NOW()),
('home_meta_desc',           'Entreprise de peinture en Alsace : interieur, exterieur, isolation, crepi facade et mosaique effet pierre. Devis gratuit rapide.', NOW()),
('home_hero_kicker',         'Votre artisan peintre en Alsace', NOW()),
('home_hero_title',          'Finitions haut de gamme pour vos murs, facades et renovations', NOW()),
('home_hero_text',           'Peinture interieure et exterieure, isolation, rénovation, revêtements muraux, boiserie, décoration et mosaïques... Votre projet maitrisé de A a Z, avec une attention particulière aux détails et finitions', NOW()),
('home_hero_cta_primary',    'Demander un devis gratuit', NOW()),
('home_hero_cta_secondary',  'Voir les prestations', NOW()),
('home_approach_title',      'Une approche premium, simple et transparente', NOW()),
('home_approach_text',       'Preparation serieuse, materiaux adaptes, execution propre. L objectif : un resultat net et durable.', NOW()),
('home_trust_badge1',        'Devis rapide', NOW()),
('home_trust_badge2',        'Finitions propres', NOW()),
('home_trust_badge3',        'Intervention Alsace', NOW()),
('home_cta_devis_title',     'Besoin d un devis ?', NOW()),
('home_cta_devis_text',      'Reponse rapide. Decris ton projet, surface, ville et delai.', NOW())
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- ----------------------------------------
-- Settings : Page A propos
-- ----------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('about_meta_title',      'A propos - Joker Peintre | Entreprise de peinture en Alsace', NOW()),
('about_meta_desc',       'Decouvrez Joker Peintre, entreprise de peinture en Alsace specialisee en interieur, exterieur, isolation et crepi facade. Travail propre et finitions haut de gamme.', NOW()),
('about_kicker',          'Entreprise locale en Alsace', NOW()),
('about_h1',              'Une entreprise de peinture engagee pour un travail propre et durable', NOW()),
('about_intro',           'Joker Peintre est une entreprise de peinture basee en Alsace, specialisee dans la peinture interieure, exterieure, l isolation, le crepi facade et la mosaique effet pierre. Chaque chantier est realise avec rigueur, precision et souci du detail.', NOW()),
('about_card1_title',     'Rigueur et preparation', NOW()),
('about_card1_text',      'La qualite d une finition depend de la preparation. Protection des surfaces, rebouchage, poncage et traitement des supports sont effectues avec precision.', NOW()),
('about_card2_title',     'Finition haut de gamme', NOW()),
('about_card2_text',      'Application reguliere, angles nets, rendu uniforme. L objectif est un resultat esthetique et durable.', NOW()),
('about_card3_title',     'Respect du chantier', NOW()),
('about_card3_text',      'Organisation, proprete et respect des delais. Chaque projet est realise avec professionnalisme.', NOW()),
('about_expertise_title', 'Une expertise adaptee a chaque projet', NOW()),
('about_expertise_sub',   'Chaque logement ou facade a ses contraintes. Une solution adaptee est proposee selon le support et l environnement.', NOW()),
('about_expertise_body',  'Que ce soit pour une renovation interieure complete, une remise en etat de facade ou un projet decoratif comme la mosaique effet pierre, Joker Peintre accompagne ses partenaires de A a Z : conseil sur les teintes, choix des materiaux, preparation technique et execution soignee.', NOW()),
('about_expertise_zone',  'L entreprise intervient dans toute l Alsace (Bas-Rhin et Haut-Rhin) pour des projets de particuliers et professionnels.', NOW()),
('about_zone_cities',     'Strasbourg,Haguenau,Selestat,Colmar,Mulhouse,Saint-Louis', NOW()),
('about_cta_title',       'Besoin d un devis pour votre projet ?', NOW()),
('about_cta_text',        'Decrivez votre projet (surface, type de support, ville) et recevez une estimation rapide.', NOW())
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- ----------------------------------------
-- Settings : Page Prestations
-- ----------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('services_meta_title',  'Prestations - Joker Peintre | Peinture & Decoration en Alsace', NOW()),
('services_meta_desc',   'Peinture interieure et exterieure en Alsace, isolation, crepi facade et mosaique effet pierre. Devis gratuit et travail soigne.', NOW()),
('services_kicker',      'Nos prestations en Alsace', NOW()),
('services_h1',          'Des solutions completes pour vos travaux de peinture et renovation', NOW()),
('services_intro',       'Joker Peintre intervient pour tous vos projets de peinture interieure, peinture exterieure, isolation, crepi facade et mosaique effet pierre. Chaque prestation est adaptee au support et aux contraintes du chantier.', NOW()),
('services_card1_title', 'Peinture interieure', NOW()),
('services_card1_text',  'Murs, plafonds, boiseries, renovation complete d appartement ou maison. Preparation soignee et finition uniforme.', NOW()),
('services_card1_link',  '/prestations/peinture-interieure.php', NOW()),
('services_card2_title', 'Peinture exterieure', NOW()),
('services_card2_text',  'Protection et embellissement de facades, volets et surfaces exterieures. Peintures resistantes aux intemperies.', NOW()),
('services_card2_link',  '/prestations/peinture-exterieure.php', NOW()),
('services_card3_title', 'Crepi / Facade', NOW()),
('services_card3_text',  'Renovation et ravalement de facade. Protection durable et amelioration esthetique.', NOW()),
('services_card3_link',  '/prestations/crepi-facade.php', NOW()),
('services_card4_title', 'Isolation', NOW()),
('services_card4_text',  'Solutions pour ameliorer le confort thermique et reduire les pertes energetiques.', NOW()),
('services_card4_link',  '/prestations/isolation.php', NOW()),
('services_card5_title', 'Mosaique effet pierre', NOW()),
('services_card5_text',  'Finition decorative effet pierre pour facades et murs. Apporte relief et cachet haut de gamme.', NOW()),
('services_card5_link',  '/prestations/mosaique-effet-pierre.php', NOW()),
('services_method_title','Notre methode de travail', NOW()),
('services_method_sub',  'Une approche professionnelle pour un resultat durable.', NOW()),
('services_cta_title',   'Un projet en cours ?', NOW()),
('services_cta_text',    'Contactez Joker Peintre pour un devis gratuit et une estimation adaptee a votre projet.', NOW())
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- ----------------------------------------
-- Settings : Page Contact
-- ----------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('contact_meta_title', 'Contact - Joker Peintre | Devis gratuit en Alsace', NOW()),
('contact_meta_desc',  'Contactez Joker Peintre pour un devis gratuit en Alsace. Peinture interieure, facade, isolation et decoration.', NOW()),
('contact_kicker',     'Contact & Devis gratuit', NOW()),
('contact_h1',         'Parlons de votre projet', NOW()),
('contact_intro',      'Decrivez votre projet (surface, type de travaux, ville) et recevez une estimation rapide.', NOW())
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- ----------------------------------------
-- Settings : Footer & SEO global
-- ----------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('footer_tagline',     'Société de bâtiment – Peinture & Décoration', NOW()),
('footer_zone',        'Intervention en Alsace', NOW()),
('seo_global_og_image', '', NOW())
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;
