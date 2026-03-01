<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 *  FRONT CONTROLLER — Point d'entrée unique de l'application MVC
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  Toutes les requêtes HTTP sont routées ici (via .htaccess).
 *  Le fichier charge l'autoloader Composer, initialise la config,
 *  puis confie le dispatch au Router.
 */

declare(strict_types=1);

/* ── 0. Détection fresh install — redirection vers l'installeur ───────────── */
if (!file_exists(__DIR__ . '/install/installed.lock')) {
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $installUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . $basePath . '/install/';
    header('Location: ' . $installUrl, true, 302);
    exit;
}

/* ── 1. Autoloader Composer (classes App\ + dépendances vendor) ───────────── */
require_once __DIR__ . '/vendor/autoload.php';

/* ── 2. Configuration (constantes DB, BASE_URL, etc.) ────────────────────── */
require_once __DIR__ . '/includes/config.php';

/* ── 3. Settings helper (compatibilité ascendante) ───────────────────────── */
require_once __DIR__ . '/includes/settings.php';

/* ── 4. Routes & Dispatch ────────────────────────────────────────────────── */
/** @var \App\Core\Router $router */
$router = require_once __DIR__ . '/app/routes.php';

$router->dispatch();
