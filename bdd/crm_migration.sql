-- ============================================================
-- CRM Migration — Clients, Devis, Lignes de devis
-- ============================================================

USE `joker_peintre`;

-- ── Clients ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `crm_clients` (
  `id`          int           NOT NULL AUTO_INCREMENT,
  `contact_id`  int           DEFAULT NULL COMMENT 'Lien vers contacts si converti',
  `ref`         varchar(20)   NOT NULL,
  `type`        enum('particulier','professionnel') NOT NULL DEFAULT 'particulier',
  `name`        varchar(150)  NOT NULL,
  `company`     varchar(150)  DEFAULT NULL,
  `email`       varchar(190)  DEFAULT NULL,
  `phone`       varchar(50)   DEFAULT NULL,
  `address`     varchar(255)  DEFAULT NULL,
  `city`        varchar(120)  DEFAULT NULL,
  `zip`         varchar(10)   DEFAULT NULL,
  `notes`       text          DEFAULT NULL,
  `created_at`  datetime      NOT NULL,
  `updated_at`  datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`ref`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ── Devis / Factures ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `crm_devis` (
  `id`           int          NOT NULL AUTO_INCREMENT,
  `client_id`    int          NOT NULL,
  `ref`          varchar(30)  NOT NULL,
  `type`         enum('devis','facture') NOT NULL DEFAULT 'devis',
  `status`       enum('draft','sent','accepted','refused','invoiced','paid') NOT NULL DEFAULT 'draft',
  `title`        varchar(190) DEFAULT NULL,
  `intro`        text         DEFAULT NULL,
  `footer_note`  text         DEFAULT NULL,
  `total_ht`     decimal(10,2) NOT NULL DEFAULT '0.00',
  `tva_rate`     decimal(5,2)  NOT NULL DEFAULT '10.00',
  `total_ttc`    decimal(10,2) NOT NULL DEFAULT '0.00',
  `issued_at`    date         DEFAULT NULL,
  `valid_until`  date         DEFAULT NULL,
  `paid_at`      date         DEFAULT NULL,
  `created_at`   datetime     NOT NULL,
  `updated_at`   datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`ref`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ── Lignes de devis ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `crm_devis_lines` (
  `id`          int            NOT NULL AUTO_INCREMENT,
  `devis_id`    int            NOT NULL,
  `sort_order`  int            NOT NULL DEFAULT '0',
  `description` varchar(500)   NOT NULL DEFAULT '',
  `qty`         decimal(8,2)   NOT NULL DEFAULT '1.00',
  `unit`        varchar(30)    DEFAULT NULL,
  `unit_price`  decimal(10,2)  NOT NULL DEFAULT '0.00',
  `total`       decimal(10,2)  NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `devis_id` (`devis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ── Foreign keys ─────────────────────────────────────────────
ALTER TABLE `crm_devis`
  ADD CONSTRAINT `fk_devis_client` FOREIGN KEY (`client_id`)
  REFERENCES `crm_clients` (`id`) ON DELETE CASCADE;

ALTER TABLE `crm_devis_lines`
  ADD CONSTRAINT `fk_lines_devis` FOREIGN KEY (`devis_id`)
  REFERENCES `crm_devis` (`id`) ON DELETE CASCADE;
