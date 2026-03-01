<?php
/**
 * ArtiCMS — Installeur AJAX backend
 * Reçoit un JSON POST, exécute les actions d'installation.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ── Sécurité : bloquer si déjà installé ────────────────────────────────────
if (file_exists(__DIR__ . '/installed.lock')) {
    echo json_encode(['ok' => false, 'error' => 'Déjà installé.']);
    exit;
}

// ── Input JSON ──────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?? [];

function i(string $key, string $default = ''): string {
    global $input;
    return trim((string)($input[$key] ?? $default));
}

$action = i('action');

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : test_db
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'test_db') {
    try {
        $pdo = connectDb(i('db_host', '127.0.0.1'), i('db_port', '3306'), i('db_name'), i('db_user'), i('db_pass'), false);
        echo json_encode(['ok' => true]);
    } catch (\Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : install
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'install') {
    $log = [];
    $manualSteps = [];

    try {
        // 1. Connexion sans sélectionner la BDD (pour pouvoir la créer)
        $pdoNoDB = connectDb(i('db_host','127.0.0.1'), i('db_port','3306'), '', i('db_user'), i('db_pass'), true);
        $dbName  = i('db_name', 'arti_cms');

        // 2. Créer la BDD si inexistante
        $pdoNoDB->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $log[] = "✅ Base de données « {$dbName} » prête";

        // 3. Connexion avec la BDD
        $pdo = connectDb(i('db_host','127.0.0.1'), i('db_port','3306'), $dbName, i('db_user'), i('db_pass'), false);
        $log[] = "✅ Connexion établie";

        // 4. Exécuter le schéma SQL
        createSchema($pdo, $log);

        // 5. Insérer les settings par défaut
        insertSettings($pdo, $input, $log);

        // 6. Créer le compte admin
        createAdmin($pdo, $input, $log);

        // 7. Données de démonstration (optionnel)
        if (!empty($input['demo_data'])) {
            insertDemoData($pdo, $input, $log);
        }

        // 8. Écrire includes/config.php (avec fallback si permissions insuffisantes)
        $configContent = buildConfig($input);
        $configPath    = dirname(__DIR__) . '/includes/config.php';
        if (is_writable($configPath)) {
            if (file_exists($configPath)) {
                copy($configPath, $configPath . '.bak.' . date('YmdHis'));
                $log[] = "💾 Ancienne config sauvegardée";
            }
            file_put_contents($configPath, $configContent);
            $log[] = "✅ includes/config.php écrit";
        } else {
            $log[] = "⚠️ Impossible d'écrire includes/config.php — permissions insuffisantes";
            $manualSteps[] = [
                'type'    => 'config',
                'title'   => 'Colle ce contenu dans includes/config.php',
                'content' => $configContent,
                'cmd'     => 'sudo chmod 664 ' . $configPath . ' && sudo chown www-data:www-data ' . $configPath,
            ];
        }

        // 8. Mettre à jour .htaccess avec le vrai nom de dossier
        updateHtaccess($log, $manualSteps);

        // 9. Écrire le fichier lock
        $lockPath = __DIR__ . '/installed.lock';
        if (is_writable(__DIR__)) {
            file_put_contents($lockPath, date('Y-m-d H:i:s') . ' — ArtiCMS installé');
            $log[] = "🔒 Fichier installed.lock créé";
        } else {
            $log[] = "⚠️ Impossible de créer installed.lock — permissions insuffisantes";
            $manualSteps[] = [
                'type'  => 'lock',
                'title' => 'Crée manuellement le fichier lock',
                'cmd'   => 'sudo touch ' . $lockPath . ' && sudo chown www-data:www-data ' . $lockPath,
            ];
        }

        $result = ['ok' => true, 'log' => $log];
        if (!empty($manualSteps)) {
            $result['manual_steps'] = $manualSteps;
            $result['partial']      = true;
        }
        echo json_encode($result);

    } catch (\Exception $e) {
        $log[] = "❌ " . $e->getMessage();
        echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'log' => $log]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Action inconnue']);
exit;

// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

function connectDb(string $host, string $port, string $dbName, string $user, string $pass, bool $noDB): \PDO
{
    $dsn = $noDB
        ? "mysql:host={$host};port={$port};charset=utf8mb4"
        : "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

    return new \PDO($dsn, $user, $pass, [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

// ── Schéma complet ────────────────────────────────────────────────────────────
function createSchema(\PDO $pdo, array &$log): void
{
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = [

      'admins' => "CREATE TABLE IF NOT EXISTS `admins` (
        `id`                  int           NOT NULL AUTO_INCREMENT,
        `username`            varchar(120)  NOT NULL,
        `email`               varchar(180)  DEFAULT NULL,
        `display_name`        varchar(120)  DEFAULT NULL,
        `avatar`              varchar(255)  DEFAULT NULL,
        `password_hash`       varchar(255)  NOT NULL,
        `role`                varchar(50)   NOT NULL DEFAULT 'editor',
        `is_active`           tinyint(1)    DEFAULT 1,
        `last_login`          datetime      DEFAULT NULL,
        `created_at`          datetime      NOT NULL,
        `reset_token`         varchar(64)   DEFAULT NULL,
        `reset_token_expires` datetime      DEFAULT NULL,
        `totp_secret`         varchar(64)   DEFAULT NULL,
        `totp_enabled`        tinyint(1)    NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'admin_recovery_codes' => "CREATE TABLE IF NOT EXISTS `admin_recovery_codes` (
        `id`         int      NOT NULL AUTO_INCREMENT,
        `admin_id`   int      NOT NULL,
        `code_hash`  varchar(64) NOT NULL,
        `used_at`    datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `admin_id` (`admin_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'admin_trusted_devices' => "CREATE TABLE IF NOT EXISTS `admin_trusted_devices` (
        `id`           int          NOT NULL AUTO_INCREMENT,
        `admin_id`     int          NOT NULL,
        `token_hash`   varchar(64)  NOT NULL,
        `device_label` varchar(200) DEFAULT NULL,
        `expires_at`   datetime     NOT NULL,
        `created_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `admin_id` (`admin_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
        `id`            int          NOT NULL AUTO_INCREMENT,
        `setting_key`   varchar(120) NOT NULL,
        `setting_value` text         NOT NULL,
        `updated_at`    datetime     NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'contacts' => "CREATE TABLE IF NOT EXISTS `contacts` (
        `id`               int          NOT NULL AUTO_INCREMENT,
        `created_at`       datetime     NOT NULL,
        `name`             varchar(150) NOT NULL,
        `email`            varchar(190) NOT NULL,
        `phone`            varchar(50)  DEFAULT NULL,
        `city`             varchar(120) DEFAULT NULL,
        `service`          varchar(120) DEFAULT NULL,
        `surface`          varchar(120) DEFAULT NULL,
        `message`          text         NOT NULL,
        `ip`               varchar(45)  DEFAULT NULL,
        `captcha_score`    decimal(3,2) DEFAULT NULL,
        `user_agent`       text,
        `status`           varchar(50)  NOT NULL DEFAULT 'new',
        `pipeline_status`  varchar(30)  NOT NULL DEFAULT 'new',
        `next_followup_at` datetime     DEFAULT NULL,
        `followup_count`   int          NOT NULL DEFAULT 0,
        `archived_at`      datetime     DEFAULT NULL,
        `updated_at`       datetime     DEFAULT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'contact_notes' => "CREATE TABLE IF NOT EXISTS `contact_notes` (
        `id`         int  NOT NULL AUTO_INCREMENT,
        `contact_id` int  NOT NULL,
        `admin_id`   int  NOT NULL,
        `note`       text NOT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `contact_id` (`contact_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'tags' => "CREATE TABLE IF NOT EXISTS `tags` (
        `id`         int         NOT NULL AUTO_INCREMENT,
        `name`       varchar(60) NOT NULL,
        `created_at` datetime    NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'contact_tags' => "CREATE TABLE IF NOT EXISTS `contact_tags` (
        `contact_id` int NOT NULL,
        `tag_id`     int NOT NULL,
        PRIMARY KEY (`contact_id`,`tag_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'realisations' => "CREATE TABLE IF NOT EXISTS `realisations` (
        `id`           int          NOT NULL AUTO_INCREMENT,
        `title`        varchar(190) NOT NULL,
        `city`         varchar(120) DEFAULT NULL,
        `type`         varchar(60)  DEFAULT NULL,
        `description`  text,
        `cover_image`  varchar(255) DEFAULT NULL,
        `cover_thumb`  varchar(255) DEFAULT NULL,
        `is_featured`  tinyint(1)   NOT NULL DEFAULT 0,
        `is_published` tinyint(1)   NOT NULL DEFAULT 1,
        `sort_order`   int          NOT NULL DEFAULT 0,
        `meta_title`   varchar(255) DEFAULT NULL,
        `meta_description` text     DEFAULT NULL,
        `created_at`   datetime     NOT NULL,
        `updated_at`   datetime     DEFAULT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'realisation_images' => "CREATE TABLE IF NOT EXISTS `realisation_images` (
        `id`              int          NOT NULL AUTO_INCREMENT,
        `realisation_id`  int          NOT NULL,
        `image_path`      varchar(255) NOT NULL,
        `alt_text`        varchar(190) DEFAULT NULL,
        `sort_order`      int          NOT NULL DEFAULT 0,
        `created_at`      datetime     NOT NULL,
        PRIMARY KEY (`id`),
        KEY `realisation_id` (`realisation_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'cms_pages' => "CREATE TABLE IF NOT EXISTS `cms_pages` (
        `id`           int          NOT NULL AUTO_INCREMENT,
        `slug`         varchar(120) NOT NULL,
        `parent_id`    int          DEFAULT NULL,
        `title`        varchar(255) NOT NULL,
        `h1`           varchar(255) DEFAULT NULL,
        `kicker`       varchar(255) DEFAULT NULL,
        `content`      longtext,
        `meta_title`   varchar(255) DEFAULT NULL,
        `meta_description` varchar(320) DEFAULT NULL,
        `is_published` tinyint(1)   NOT NULL DEFAULT 1,
        `sort_order`   int          NOT NULL DEFAULT 0,
        `template`     varchar(60)  DEFAULT 'default',
        `created_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`   datetime     DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'galleries' => "CREATE TABLE IF NOT EXISTS `galleries` (
        `id`                  int unsigned NOT NULL AUTO_INCREMENT,
        `name`                varchar(190) NOT NULL,
        `description`         text,
        `show_item_labels`    tinyint(1)   NOT NULL DEFAULT 1,
        `show_gallery_header` tinyint(1)   NOT NULL DEFAULT 1,
        `items_per_page`      tinyint      NOT NULL DEFAULT 6,
        `sort_order`          int          NOT NULL DEFAULT 0,
        `created_at`          datetime     DEFAULT CURRENT_TIMESTAMP,
        `updated_at`          datetime     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'gallery_items' => "CREATE TABLE IF NOT EXISTS `gallery_items` (
        `id`              int unsigned NOT NULL AUTO_INCREMENT,
        `gallery_id`      int unsigned NOT NULL,
        `realisation_id`  int          NOT NULL,
        `sort_order`      int          NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_gi` (`gallery_id`,`realisation_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'media_meta' => "CREATE TABLE IF NOT EXISTS `media_meta` (
        `id`         int unsigned NOT NULL AUTO_INCREMENT,
        `rel`        varchar(500) NOT NULL COMMENT 'chemin relatif depuis assets/images/',
        `alt_text`   varchar(500) NOT NULL DEFAULT '',
        `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `rel` (`rel`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'forms' => "CREATE TABLE IF NOT EXISTS `forms` (
        `id`          int          NOT NULL AUTO_INCREMENT,
        `name`        varchar(100) NOT NULL,
        `slug`        varchar(100) NOT NULL,
        `description` text,
        `fields`      json         NOT NULL DEFAULT (json_array()),
        `settings`    json         NOT NULL DEFAULT (json_object()),
        `is_active`   tinyint(1)   DEFAULT 1,
        `created_at`  datetime     DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  datetime     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'form_submissions' => "CREATE TABLE IF NOT EXISTS `form_submissions` (
        `id`         int         NOT NULL AUTO_INCREMENT,
        `form_id`    int         NOT NULL,
        `data`       json        NOT NULL,
        `ip`         varchar(45) DEFAULT NULL,
        `user_agent` text,
        `is_read`    tinyint(1)  DEFAULT 0,
        `created_at` datetime    DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_form_id` (`form_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'crm_clients' => "CREATE TABLE IF NOT EXISTS `crm_clients` (
        `id`         int          NOT NULL AUTO_INCREMENT,
        `contact_id` int          DEFAULT NULL,
        `ref`        varchar(20)  NOT NULL,
        `type`       enum('particulier','professionnel') NOT NULL DEFAULT 'particulier',
        `name`       varchar(150) NOT NULL,
        `company`    varchar(150) DEFAULT NULL,
        `email`      varchar(190) DEFAULT NULL,
        `phone`      varchar(50)  DEFAULT NULL,
        `address`    varchar(255) DEFAULT NULL,
        `city`       varchar(120) DEFAULT NULL,
        `zip`        varchar(10)  DEFAULT NULL,
        `notes`      text         DEFAULT NULL,
        `created_at` datetime     NOT NULL,
        `updated_at` datetime     DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `ref` (`ref`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'crm_devis' => "CREATE TABLE IF NOT EXISTS `crm_devis` (
        `id`           int           NOT NULL AUTO_INCREMENT,
        `client_id`    int           NOT NULL,
        `ref`          varchar(30)   NOT NULL,
        `type`         enum('devis','facture') NOT NULL DEFAULT 'devis',
        `status`       enum('draft','sent','accepted','refused','invoiced','paid') NOT NULL DEFAULT 'draft',
        `title`        varchar(190)  DEFAULT NULL,
        `intro`        text          DEFAULT NULL,
        `footer_note`  text          DEFAULT NULL,
        `total_ht`     decimal(10,2) NOT NULL DEFAULT 0.00,
        `tva_rate`     decimal(5,2)  NOT NULL DEFAULT 10.00,
        `total_ttc`    decimal(10,2) NOT NULL DEFAULT 0.00,
        `issued_at`    date          DEFAULT NULL,
        `valid_until`  date          DEFAULT NULL,
        `paid_at`      date          DEFAULT NULL,
        `created_at`   datetime      NOT NULL,
        `updated_at`   datetime      DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `ref` (`ref`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

      'crm_devis_lines' => "CREATE TABLE IF NOT EXISTS `crm_devis_lines` (
        `id`          int           NOT NULL AUTO_INCREMENT,
        `devis_id`    int           NOT NULL,
        `sort_order`  int           NOT NULL DEFAULT 0,
        `description` varchar(500)  NOT NULL DEFAULT '',
        `qty`         decimal(8,2)  NOT NULL DEFAULT 1.00,
        `unit`        varchar(30)   DEFAULT NULL,
        `unit_price`  decimal(10,2) NOT NULL DEFAULT 0.00,
        `total`       decimal(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (`id`),
        KEY `devis_id` (`devis_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        $log[] = "✅ Table `{$name}` prête";
    }

    // Foreign keys (ignore si déjà existantes)
    $fkeys = [
        "ALTER TABLE `contact_notes`     ADD CONSTRAINT `fk_notes_contact` FOREIGN KEY IF NOT EXISTS (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE",
        "ALTER TABLE `contact_notes`     ADD CONSTRAINT `fk_notes_admin`   FOREIGN KEY IF NOT EXISTS (`admin_id`)   REFERENCES `admins`   (`id`) ON DELETE CASCADE",
        "ALTER TABLE `contact_tags`      ADD CONSTRAINT `fk_ct_contact`    FOREIGN KEY IF NOT EXISTS (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE",
        "ALTER TABLE `contact_tags`      ADD CONSTRAINT `fk_ct_tag`        FOREIGN KEY IF NOT EXISTS (`tag_id`)     REFERENCES `tags`     (`id`) ON DELETE CASCADE",
        "ALTER TABLE `realisation_images` ADD CONSTRAINT `fk_img_real`     FOREIGN KEY IF NOT EXISTS (`realisation_id`) REFERENCES `realisations` (`id`) ON DELETE CASCADE",
        "ALTER TABLE `form_submissions`  ADD CONSTRAINT `fk_sub_form`      FOREIGN KEY IF NOT EXISTS (`form_id`)    REFERENCES `forms`    (`id`) ON DELETE CASCADE",
        "ALTER TABLE `crm_devis`         ADD CONSTRAINT `fk_devis_client`  FOREIGN KEY IF NOT EXISTS (`client_id`)  REFERENCES `crm_clients` (`id`) ON DELETE CASCADE",
        "ALTER TABLE `crm_devis_lines`   ADD CONSTRAINT `fk_lines_devis`   FOREIGN KEY IF NOT EXISTS (`devis_id`)   REFERENCES `crm_devis` (`id`) ON DELETE CASCADE",
        "ALTER TABLE `admin_recovery_codes`   ADD CONSTRAINT `fk_arc_admin`  FOREIGN KEY IF NOT EXISTS (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE",
        "ALTER TABLE `admin_trusted_devices`  ADD CONSTRAINT `fk_atd_admin`  FOREIGN KEY IF NOT EXISTS (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE",
    ];
    foreach ($fkeys as $fk) {
        try { $pdo->exec($fk); } catch (\Throwable) { /* déjà existante */ }
    }
    $log[] = "✅ Clés étrangères configurées";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
}

// ── Settings par défaut ───────────────────────────────────────────────────────
function insertSettings(\PDO $pdo, array $input, array &$log): void
{
    $cn     = trim($input['company_name']   ?? 'Mon Entreprise');
    $email  = trim($input['company_email']  ?? '');
    $phone  = trim($input['company_phone']  ?? '');
    $region = trim($input['company_region'] ?? 'France');
    $url    = rtrim(trim($input['site_url'] ?? ''), '/');

    // Formatage affichage téléphone (simple)
    $phoneDisplay = $phone;
    if (preg_match('/^\+33(\d{9})$/', $phone, $m)) {
        $n = $m[1];
        $phoneDisplay = '0' . implode(' ', str_split($n, 2));
    }

    $defaults = [
        'company_name'          => $cn,
        'company_phone'         => $phone,
        'company_phone_display' => $phoneDisplay,
        'company_email'         => $email,
        'company_region'        => $region,
        'company_address'       => '',
        'company_siret'         => '',
        'active_theme'          => 'default',
        'home_meta_title'       => $cn . ' - ' . $region,
        'home_meta_desc'        => 'Bienvenue sur le site de ' . $cn,
        'home_hero_kicker'      => 'Votre expert en ' . $region,
        'home_hero_title'       => 'Finitions haut de gamme',
        'home_hero_text'        => '',
        'home_hero_cta_primary' => 'Demander un devis',
        'home_hero_cta_secondary'=> 'Voir les prestations',
        'home_trust_badge1'     => 'Devis rapide',
        'home_trust_badge2'     => 'Finitions propres',
        'home_trust_badge3'     => 'Intervention ' . $region,
        'home_approach_title'   => 'Une approche premium, simple et transparente',
        'home_approach_text'    => '',
        'home_cta_devis_title'  => 'Besoin d\'un devis ?',
        'home_cta_devis_text'   => 'Réponse rapide. Décris ton projet et reçois une estimation.',
        'home_realisations_title'=> 'Réalisations',
        'home_realisations_text' => '',
        'realisations_h1'        => 'Nos réalisations',
        'realisations_intro'     => '',
        'realisations_cta_text'  => 'Demander un devis',
        'realisations_cta_link'  => '/contact',
        'realisations_meta_title'=> 'Réalisations - ' . $cn,
        'realisations_meta_desc' => '',
        'realisations_per_page'  => '6',
        'realisations_before_after_title'   => 'Avant / Après',
        'realisations_before_after_subtitle'=> 'La différence se voit dans les détails.',
        'contact_meta_title'     => 'Contact - ' . $cn,
        'contact_meta_desc'      => 'Contactez ' . $cn . ' pour un devis gratuit.',
        'contact_kicker'         => 'Contact',
        'contact_h1'             => 'Parlons de votre projet',
        'footer_tagline'         => $cn,
        'footer_zone'            => 'Intervention en ' . $region,
        'realisations_before_after_enabled' => '1',
        'section_hero_enabled' => '1',
        'section_prestations_enabled' => '1',
        'section_badges_enabled' => '1',
        'section_approche_enabled' => '1',
        'section_realisations_enabled' => '1',
        'section_ba_enabled' => '1',
        'section_cta_enabled' => '1',
        'section_local_enabled' => '1',
        'home_prestations_footer_enabled' => '1',

        'nav_items'              => json_encode([
            ['label' => 'Accueil',      'url' => '/'],
            ['label' => 'Prestations',  'url' => '/prestations'],
            ['label' => 'Réalisations', 'url' => '/realisations'],
            ['label' => 'Contact',      'url' => '/contact'],
        ], JSON_UNESCAPED_UNICODE),
        'home_prestations_items' => json_encode([
            ['title' => 'Prestation 1', 'url' => '/prestations/prestation-1', 'enabled' => true],
            ['title' => 'Prestation 2', 'url' => '/prestations/prestation-2', 'enabled' => true],
        ], JSON_UNESCAPED_UNICODE),
    ];

    $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`,`setting_value`,`updated_at`) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`");
    foreach ($defaults as $k => $v) {
        $stmt->execute([$k, $v]);
    }
    $log[] = "✅ Paramètres par défaut insérés (" . count($defaults) . ")";
}

// ── Compte admin ──────────────────────────────────────────────────────────────
function createAdmin(\PDO $pdo, array $input, array &$log): void
{
    $user    = trim($input['admin_user']    ?? 'admin');
    $email   = trim($input['admin_email']   ?? '');
    $display = trim($input['admin_display'] ?? 'Admin');
    $pass    = $input['admin_pass'] ?? '';

    if (empty($pass)) throw new \RuntimeException('Mot de passe admin vide');

    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

    // Vérifier si un admin existe déjà
    $existing = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ((int)$existing > 0) {
        $log[] = "⚠️ Un compte admin existe déjà — compte ignoré";
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO `admins` (`username`,`email`,`display_name`,`password_hash`,`role`,`is_active`,`created_at`) VALUES (?,?,?,?,'super_admin',1,NOW())");
    $stmt->execute([$user, $email, $display, $hash]);
    $log[] = "✅ Compte admin « {$user} » créé";
}

// ── Mise à jour du .htaccess avec le vrai nom de dossier ─────────────────────
function updateHtaccess(array &$log, array &$manualSteps): void
{
    $root      = dirname(__DIR__);                  // /var/www/html/MonDossier
    $folder    = basename($root);                   // MonDossier
    $htaPath   = $root . '/.htaccess';

    if (!file_exists($htaPath)) {
        $log[] = "⚠️ .htaccess introuvable — ignoré";
        return;
    }

    $content = file_get_contents($htaPath);

    // Remplacer RewriteBase /QuoiQueCSoit/ → RewriteBase /MonDossier/
    $updated = preg_replace(
        '#^(RewriteBase\s+)/[^/\s]+/#m',
        '$1/' . $folder . '/',
        $content
    );
    // Remplacer RewriteCond ... ^/QuoiQueCSoit/admin/ → ^/MonDossier/admin/
    $updated = preg_replace(
        '#^(RewriteCond\s+%\{REQUEST_URI\}\s+\^)/[^/\s]+/admin/#m',
        '$1/' . $folder . '/admin/',
        $updated
    );

    if ($updated === $content) {
        $log[] = "ℹ️ .htaccess déjà correct (RewriteBase /{$folder}/)";
        return;
    }

    if (is_writable($htaPath)) {
        file_put_contents($htaPath, $updated);
        $log[] = "✅ .htaccess mis à jour (RewriteBase /{$folder}/)";
    } else {
        $log[] = "⚠️ Impossible d'écrire .htaccess — permissions insuffisantes";
        $manualSteps[] = [
            'type'    => 'htaccess',
            'title'   => 'Mets à jour manuellement le .htaccess',
            'content' => $updated,
            'cmd'     => "sudo sed -i 's|RewriteBase /[^/]*/|RewriteBase /{$folder}/|' {$htaPath}"
                       . " && sudo sed -i 's|\\^/[^/]*/admin/|\\^/{$folder}/admin/|' {$htaPath}",
        ];
    }
}

// ── Données de démonstration ─────────────────────────────────────────────────
function insertDemoData(\PDO $pdo, array $input, array &$log): void
{
    $cn  = trim($input['company_name'] ?? 'Mon Entreprise');
    $now = date('Y-m-d H:i:s');

    try {
        /* ──────────────────────────────────────────────────────────────────
         * TAGS
         * ─────────────────────────────────────────────────────────────────*/
        $stmtTag = $pdo->prepare("INSERT IGNORE INTO `tags` (`name`, `created_at`) VALUES (?,?)");
        foreach (['Chantier terminé', 'Devis accepté', 'Prospect chaud'] as $t) {
            $stmtTag->execute([$t, $now]);
        }
        $tagRows = $pdo->query("SELECT id, name FROM `tags` WHERE name IN ('Chantier terminé','Devis accepté','Prospect chaud')")->fetchAll();
        $tagIds  = array_column($tagRows, 'id', 'name');
        $log[] = '✅ Tags de démo créés';

        /* ──────────────────────────────────────────────────────────────────
         * CONTACTS
         * ─────────────────────────────────────────────────────────────────*/
        $stmtContact = $pdo->prepare("
            INSERT INTO `contacts`
                (`created_at`,`name`,`email`,`phone`,`city`,`service`,`surface`,`message`,`status`,`pipeline_status`,`updated_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmtContact->execute([
            date('Y-m-d H:i:s', strtotime('-5 days')),
            'Marie Dupont', 'marie.dupont@example.fr', '06 12 34 56 78',
            'Paris', 'Peinture intérieure', '60 m²',
            'Bonjour, je souhaite rénover mon salon et ma chambre. Pouvez-vous me faire un devis ?',
            'new', 'new', $now,
        ]);
        $c1 = (int)$pdo->lastInsertId();

        $stmtContact->execute([
            date('Y-m-d H:i:s', strtotime('-12 days')),
            'Jean-Pierre Martin', 'jp.martin@renov-pro.fr', '07 98 76 54 32',
            'Lyon', 'Ravalement de façade', '300 m²',
            'Immeuble de 5 étages à ravaler. Intervention souhaitée au printemps.',
            'treated', 'qualified', $now,
        ]);
        $c2 = (int)$pdo->lastInsertId();

        // Notes sur les contacts
        $stmtNote = $pdo->prepare("INSERT INTO `contact_notes` (`contact_id`,`admin_id`,`note`,`created_at`) VALUES (?,1,?,?)");
        $stmtNote->execute([$c1, 'Client intéressé, rappel prévu demain matin. Budget estimé 2 500 €.', date('Y-m-d H:i:s', strtotime('-4 days'))]);
        $stmtNote->execute([$c2, 'Devis envoyé par e-mail. En attente de retour.', date('Y-m-d H:i:s', strtotime('-10 days'))]);

        // Tags contacts
        $stmtCT = $pdo->prepare("INSERT IGNORE INTO `contact_tags` VALUES (?,?)");
        if (!empty($tagIds['Prospect chaud'])) $stmtCT->execute([$c1, $tagIds['Prospect chaud']]);
        if (!empty($tagIds['Devis accepté']))  $stmtCT->execute([$c2, $tagIds['Devis accepté']]);
        $log[] = '✅ Contacts de démo créés';

        /* ──────────────────────────────────────────────────────────────────
         * CRM CLIENTS
         * ─────────────────────────────────────────────────────────────────*/
        $pdo->prepare("
            INSERT INTO `crm_clients`
                (`contact_id`,`ref`,`type`,`name`,`email`,`phone`,`city`,`zip`,`notes`,`created_at`,`updated_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([$c1, 'CLI-D001', 'particulier', 'Marie Dupont', 'marie.dupont@example.fr',
            '06 12 34 56 78', 'Paris', '75008', 'Cliente réactive, budget confirmé.', $now, $now]);
        $cli1 = (int)$pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO `crm_clients`
                (`ref`,`type`,`name`,`company`,`email`,`phone`,`address`,`city`,`zip`,`notes`,`created_at`,`updated_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute(['CLI-D002', 'professionnel', 'M. Bernard', 'SARL Rénov Immo',
            'contact@renov-immo.fr', '04 78 00 11 22', '12 rue de la République',
            'Lyon', '69002', 'Gestionnaire d\'immeubles locatifs. Partenaire régulier.', $now, $now]);
        $cli2 = (int)$pdo->lastInsertId();
        $log[] = '✅ Clients CRM de démo créés';

        /* ──────────────────────────────────────────────────────────────────
         * CRM DEVIS & FACTURES
         * ─────────────────────────────────────────────────────────────────*/
        $stmtDevis = $pdo->prepare("
            INSERT INTO `crm_devis`
                (`client_id`,`ref`,`type`,`status`,`title`,`intro`,`footer_note`,
                 `total_ht`,`tva_rate`,`total_ttc`,`issued_at`,`valid_until`,`paid_at`,`created_at`,`updated_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmtLine = $pdo->prepare("
            INSERT INTO `crm_devis_lines`
                (`devis_id`,`sort_order`,`description`,`qty`,`unit`,`unit_price`,`total`)
            VALUES (?,?,?,?,?,?,?)
        ");

        // Devis 1 – accepté (Marie Dupont)
        $iss1 = date('Y-m-d', strtotime('-20 days'));
        $val1 = date('Y-m-d', strtotime('-20 days +30 days'));
        $stmtDevis->execute([
            $cli1, 'DEV-D2025-001', 'devis', 'accepted',
            'Rénovation peinture salon & chambre',
            'Suite à notre visite du ' . date('d/m/Y', strtotime('-22 days')) . ', veuillez trouver ci-joint notre devis.',
            'Devis valable 30 jours. Acompte 30 % à la commande, solde à réception.',
            2200.00, 10.00, 2420.00, $iss1, $val1, null, $now, $now,
        ]);
        $d1 = (int)$pdo->lastInsertId();
        foreach ([
            [1, 'Préparation des surfaces (dégraissage, ponçage, rebouchage)', 1, 'forfait', 350.00, 350.00],
            [2, 'Peinture salon — 2 couches mat premium (35 m²)', 35, 'm²', 18.00, 630.00],
            [3, 'Peinture chambre — 2 couches mat premium (25 m²)', 25, 'm²', 18.00, 450.00],
            [4, 'Peinture plinthes et boiseries', 1, 'forfait', 280.00, 280.00],
            [5, 'Protection mobilier et sol', 1, 'forfait', 90.00, 90.00],
            [6, 'Nettoyage et remise en état', 1, 'forfait', 400.00, 400.00],
        ] as [$o, $desc, $q, $u, $pu, $tot]) {
            $stmtLine->execute([$d1, $o, $desc, $q, $u, $pu, $tot]);
        }

        // Facture correspondante – payée
        $iss2 = date('Y-m-d', strtotime('-8 days'));
        $paid = date('Y-m-d', strtotime('-5 days'));
        $stmtDevis->execute([
            $cli1, 'FAC-D2025-001', 'facture', 'paid',
            'Facture — Rénovation peinture salon & chambre',
            'Travaux réalisés conformément au devis DEV-D2025-001 accepté.',
            'Merci pour votre confiance. Règlement par virement sous 30 jours.',
            2200.00, 10.00, 2420.00, $iss2, null, $paid, $now, $now,
        ]);
        $d2 = (int)$pdo->lastInsertId();
        foreach ([
            [1, 'Préparation des surfaces (dégraissage, ponçage, rebouchage)', 1, 'forfait', 350.00, 350.00],
            [2, 'Peinture salon — 2 couches mat premium (35 m²)', 35, 'm²', 18.00, 630.00],
            [3, 'Peinture chambre — 2 couches mat premium (25 m²)', 25, 'm²', 18.00, 450.00],
            [4, 'Peinture plinthes et boiseries', 1, 'forfait', 280.00, 280.00],
            [5, 'Protection mobilier et sol', 1, 'forfait', 90.00, 90.00],
            [6, 'Nettoyage et remise en état', 1, 'forfait', 400.00, 400.00],
        ] as [$o, $desc, $q, $u, $pu, $tot]) {
            $stmtLine->execute([$d2, $o, $desc, $q, $u, $pu, $tot]);
        }

        // Devis 2 – envoyé (SARL Rénov Immo)
        $iss3 = date('Y-m-d', strtotime('-10 days'));
        $val3 = date('Y-m-d', strtotime('-10 days +45 days'));
        $stmtDevis->execute([
            $cli2, 'DEV-D2025-002', 'devis', 'sent',
            'Ravalement façade — immeuble 5 étages',
            'Suite à notre visite et métrés, veuillez trouver ci-joint notre proposition.',
            'Devis valable 45 jours. Acompte 40 % à la commande.',
            14500.00, 10.00, 15950.00, $iss3, $val3, null, $now, $now,
        ]);
        $d3 = (int)$pdo->lastInsertId();
        foreach ([
            [1, 'Montage/démontage échafaudage', 1, 'forfait', 2800.00, 2800.00],
            [2, 'Nettoyage haute pression façade', 300, 'm²', 4.50, 1350.00],
            [3, 'Traitement anti-mousse et consolidant', 300, 'm²', 5.50, 1650.00],
            [4, 'Reprise des fissures et joints', 1, 'forfait', 1800.00, 1800.00],
            [5, 'Peinture façade 2 couches élastomère', 300, 'm²', 23.00, 6900.00],
        ] as [$o, $desc, $q, $u, $pu, $tot]) {
            $stmtLine->execute([$d3, $o, $desc, $q, $u, $pu, $tot]);
        }
        $log[] = '✅ Devis & facture de démo créés';

        /* ──────────────────────────────────────────────────────────────────
         * RÉALISATIONS
         * ─────────────────────────────────────────────────────────────────*/
        $stmtReal = $pdo->prepare("
            INSERT INTO `realisations`
                (`title`,`city`,`type`,`description`,`is_featured`,`is_published`,`sort_order`,
                 `meta_title`,`meta_description`,`created_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        $stmtReal->execute([
            'Appartement haussmannien — Salon & chambre', 'Paris', 'Peinture intérieure',
            '<p>Rénovation complète d\'un appartement haussmannien dans le 8e arrondissement de Paris. '
            . 'Peinture à la chaux dans le salon, peinture mate premium dans les chambres. '
            . 'Résultat : lumineux, élégant, intemporel.</p>',
            1, 1, 1,
            'Rénovation appartement haussmannien — ' . $cn,
            'Rénovation peinture appartement haussmannien Paris 8e — salon et chambre.',
            $now,
        ]);
        $r1 = (int)$pdo->lastInsertId();

        $stmtReal->execute([
            'Ravalement façade villa provençale', 'Aix-en-Provence', 'Ravalement de façade',
            '<p>Ravalement complet d\'une villa de 280 m² à Aix-en-Provence. Nettoyage haute pression, '
            . 'traitement anti-mousse, reprise des fissures et peinture élastomère ton pierre. '
            . 'Résultat impeccable.</p>',
            1, 1, 2,
            'Ravalement façade villa provençale — ' . $cn,
            'Ravalement de façade villa provençale à Aix-en-Provence.',
            $now,
        ]);
        $r2 = (int)$pdo->lastInsertId();

        $stmtReal->execute([
            'Décoration intérieure — Loft industriel', 'Lyon', 'Décoration intérieure',
            '<p>Transformation d\'un loft industriel en espace de vie moderne et chaleureux. '
            . 'Béton ciré, enduit taloché, peinture noire mat sur les poutres métalliques '
            . 'et accent terracotta sur un mur porteur.</p>',
            0, 1, 3,
            'Loft industriel — décoration intérieure Lyon — ' . $cn,
            'Décoration intérieure loft industriel à Lyon par ' . $cn . '.',
            $now,
        ]);
        $r3 = (int)$pdo->lastInsertId();
        $log[] = '✅ Réalisations de démo créées';

        /* ──────────────────────────────────────────────────────────────────
         * GALERIE
         * ─────────────────────────────────────────────────────────────────*/
        $pdo->prepare("
            INSERT INTO `galleries` (`name`,`description`,`show_item_labels`,`show_gallery_header`,`items_per_page`,`sort_order`)
            VALUES (?,?,?,?,?,?)
        ")->execute(['Nos plus belles réalisations',
            'Une sélection de nos chantiers récents — intérieur, extérieur, décoration.',
            1, 1, 6, 1]);
        $gal = (int)$pdo->lastInsertId();

        $stmtGI = $pdo->prepare("INSERT IGNORE INTO `gallery_items` (`gallery_id`,`realisation_id`,`sort_order`) VALUES (?,?,?)");
        foreach ([$r1, $r2, $r3] as $i => $rid) {
            $stmtGI->execute([$gal, $rid, $i + 1]);
        }
        $log[] = '✅ Galerie de démo créée';

        /* ──────────────────────────────────────────────────────────────────
         * CMS PAGES
         * ─────────────────────────────────────────────────────────────────*/
        $stmtPage = $pdo->prepare("
            INSERT IGNORE INTO `cms_pages`
                (`slug`,`title`,`h1`,`kicker`,`content`,`meta_title`,`meta_description`,
                 `is_published`,`sort_order`,`template`,`created_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmtPage->execute([
            'a-propos', 'À propos', 'Qui sommes-nous ?', 'Notre histoire',
            '<h2>Qui sommes-nous ?</h2>'
            . '<p>' . $cn . ' est une entreprise artisanale fondée par des passionnés de la finition. '
            . 'Nous intervenons sur des chantiers de peinture intérieure, extérieure et décoration.</p>'
            . '<h2>Notre philosophie</h2>'
            . '<p>La qualité d\'exécution avant tout. Matériaux soigneusement sélectionnés, délais respectés, chantier propre.</p>'
            . '<h2>Nos valeurs</h2>'
            . '<ul><li><strong>Transparence</strong> : devis détaillé, sans surprise</li>'
            . '<li><strong>Fiabilité</strong> : ponctualité et respect des délais</li>'
            . '<li><strong>Savoir-faire</strong> : artisans qualifiés et expérimentés</li></ul>',
            'À propos — ' . $cn,
            'Découvrez l\'histoire et les valeurs de ' . $cn . '.',
            1, 1, 'default', $now,
        ]);
        $stmtPage->execute([
            'mentions-legales', 'Mentions légales', 'Mentions légales', '',
            '<h2>Éditeur du site</h2><p>' . $cn . '<br>SIRET : [Votre SIRET]<br>Email : [Votre email]</p>'
            . '<h2>Hébergement</h2><p>[Nom hébergeur — Adresse hébergeur]</p>'
            . '<h2>Données personnelles</h2>'
            . '<p>Conformément au RGPD, vous disposez d\'un droit d\'accès, de rectification et de suppression '
            . 'de vos données personnelles. Contactez-nous par email pour exercer ce droit.</p>',
            'Mentions légales — ' . $cn,
            'Mentions légales de ' . $cn . '.',
            1, 99, 'default', $now,
        ]);
        $log[] = '✅ Pages CMS de démo créées';

        /* ──────────────────────────────────────────────────────────────────
         * FORMULAIRE DE CONTACT + SOUMISSION EXEMPLE
         * ─────────────────────────────────────────────────────────────────*/
        $fields = json_encode([
            ['type' => 'text',     'label' => 'Nom',       'name' => 'nom',     'required' => true,  'placeholder' => 'Votre nom'],
            ['type' => 'email',    'label' => 'Email',     'name' => 'email',   'required' => true,  'placeholder' => 'votre@email.fr'],
            ['type' => 'tel',      'label' => 'Téléphone', 'name' => 'tel',     'required' => false, 'placeholder' => '06 00 00 00 00'],
            ['type' => 'textarea', 'label' => 'Message',   'name' => 'message', 'required' => true,  'placeholder' => 'Décrivez votre projet...'],
        ], JSON_UNESCAPED_UNICODE);
        $settings = json_encode([
            'submit_label'    => 'Envoyer',
            'success_message' => 'Merci ! Nous vous recontactons sous 24 h.',
            'notify_email'    => '',
            'captcha'         => false,
        ], JSON_UNESCAPED_UNICODE);

        $pdo->prepare("
            INSERT IGNORE INTO `forms` (`name`,`slug`,`description`,`fields`,`settings`,`is_active`)
            VALUES (?,?,?,?,?,?)
        ")->execute(['Formulaire de contact', 'contact-demo',
            'Formulaire principal de demande de contact.', $fields, $settings, 1]);
        $formId = (int)$pdo->lastInsertId();

        if ($formId) {
            $submission = json_encode([
                'nom'     => 'Sophie Lemaire',
                'email'   => 'sophie.lemaire@example.fr',
                'tel'     => '06 55 44 33 22',
                'message' => 'Bonjour, je cherche quelqu\'un pour repeindre mon couloir et ma cuisine (≈ 40 m²). Pouvez-vous me contacter ?',
            ], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("
                INSERT INTO `form_submissions` (`form_id`,`data`,`ip`,`is_read`,`created_at`)
                VALUES (?,?,?,?,?)
            ")->execute([$formId, $submission, '127.0.0.1', 0, date('Y-m-d H:i:s', strtotime('-2 days'))]);
        }
        $log[] = '✅ Formulaire de contact de démo + soumission exemple créés';

    } catch (\Throwable $e) {
        // Non-bloquant : on log sans faire échouer toute l'installation
        $log[] = '⚠️ Données démo partiellement insérées : ' . $e->getMessage();
    }
}

// ── Construction du config.php ───────────────────────────────────────────────
function buildConfig(array $input): string
{
    $url    = rtrim(trim($input['site_url']       ?? ''), '/');
    $cn     = trim($input['company_name']         ?? 'Mon CMS');
    $phone  = trim($input['company_phone']        ?? '');
    $email  = trim($input['company_email']        ?? '');
    $region = trim($input['company_region']       ?? 'France');
    $dbHost = trim($input['db_host']              ?? '127.0.0.1');
    $dbPort = trim($input['db_port']              ?? '3306');
    $dbName = trim($input['db_name']              ?? 'arti_cms');
    $dbUser = trim($input['db_user']              ?? '');
    $dbPass = $input['db_pass']                   ?? '';
    $smtpH  = trim($input['smtp_host']            ?? '');
    $smtpP  = trim($input['smtp_port']            ?? '587');
    $smtpU  = trim($input['smtp_user']            ?? '');
    $smtpPw = $input['smtp_pass']                 ?? '';

    // Formatage téléphone affichage
    $phoneDisplay = $phone;
    if (preg_match('/^\+33(\d{9})$/', $phone, $m)) {
        $n = $m[1];
        $phoneDisplay = '0' . implode(' ', str_split($n, 2));
    }

    $e = fn(string $v) => addslashes($v);

    return <<<PHP
<?php
/**
 * ArtiCMS — Configuration générée par l'installeur.
 * Modifiable depuis Admin > Paramètres.
 * Généré le : {$_SERVER['REQUEST_TIME_FLOAT']}
 */

// ── URL ──────────────────────────────────────────────────────────────────────
define('BASE_URL', '{$e($url)}');

// ── Identité CMS ─────────────────────────────────────────────────────────────
define('CMS_VERSION',    '1.0.0');
define('COMPANY_NAME',   '{$e($cn)}');
define('PHONE',          '{$e($phone)}');
define('PHONE_DISPLAY',  '{$e($phoneDisplay)}');
define('EMAIL',          '{$e($email)}');
define('REGION',         '{$e($region)}');

// ── Email réception contacts ─────────────────────────────────────────────────
define('CONTACT_EMAIL', '{$e($email)}');

// ── reCAPTCHA (à remplir si activé) ─────────────────────────────────────────
define('CAPTCHA_SITE_KEY',   '');
define('CAPTCHA_SECRET_KEY', '');
define('CAPTCHA_MIN_SCORE',  0.5);

// ── Base de données ──────────────────────────────────────────────────────────
define('DB_HOST', '{$e($dbHost)}');
define('DB_PORT', '{$e($dbPort)}');
define('DB_NAME', '{$e($dbName)}');
define('DB_USER', '{$e($dbUser)}');
define('DB_PASS', '{$e($dbPass)}');

// ── SMTP ─────────────────────────────────────────────────────────────────────
define('SMTP_HOST',      '{$e($smtpH)}');
define('SMTP_PORT',      (int)'{$e($smtpP)}');
define('SMTP_USER',      '{$e($smtpU)}');
define('SMTP_PASS',      '{$e($smtpPw)}');
define('SMTP_FROM',      '{$e($smtpU)}');
define('SMTP_FROM_NAME', '{$e($cn)}');
PHP;
}
