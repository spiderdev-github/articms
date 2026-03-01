<?php
/**
 * ArtiCMS — Sauvegarde / Export
 * Génère une archive tar.gz contenant :
 *   - database.sql      : dump complet de la base de données
 *   - config.php        : fichier de configuration
 *   - uploads/          : tous les médias uploadés
 *   - themes/           : tous les thèmes (CSS personnalisés inclus)
 *   - robots.txt        : si présent
 *   - sitemap.xml       : si présent
 *   - manifest.json     : métadonnées de la sauvegarde
 */

require_once __DIR__ . '/../auth.php';
requirePermission('settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../settings.php?tab=backup'); exit;
}

validateCsrf($_POST['csrf_token'] ?? '', '../settings.php?tab=backup&notice=csrf');

/* ─── Phar disponible ? ─────────────────────────────────────────────────── */
if (!class_exists('PharData')) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => "L'extension Phar n'est pas disponible sur ce serveur."];
    header('Location: ../settings.php?tab=backup'); exit;
}

/* ─── Phar en lecture seule ? ───────────────────────────────────────────── */
if ((bool) ini_get('phar.readonly')) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => "phar.readonly est activé dans php.ini. Contactez votre hébergeur ou ajoutez <code>phar.readonly = Off</code>."];
    header('Location: ../settings.php?tab=backup'); exit;
}

set_time_limit(0);
ini_set('memory_limit', '512M');

$root    = dirname(__DIR__, 2);
$pdo     = getPDO();

/* ══════════════════════════════════════════════════════════════════════════
   1. Répertoire temporaire de travail
══════════════════════════════════════════════════════════════════════════ */
$tmpBase = sys_get_temp_dir() . '/articms_bk_' . time() . '_' . bin2hex(random_bytes(4));
if (!mkdir($tmpBase, 0700, true)) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => "Impossible de créer le répertoire temporaire."];
    header('Location: ../settings.php?tab=backup'); exit;
}

/* ══════════════════════════════════════════════════════════════════════════
   2. Dump de la base de données
══════════════════════════════════════════════════════════════════════════ */
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql  = "-- ArtiCMS Database Backup\n";
    $sql .= "-- Date : " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Version : " . CMS_VERSION . "\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET time_zone = '+00:00';\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        $createRow = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $sql .= "-- Table : `$table`\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createRow[1] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $values = array_map(function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, array_values($row));
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    file_put_contents($tmpBase . '/database.sql', $sql);
} catch (Throwable $e) {
    _bkCleanup($tmpBase);
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => "Erreur lors du dump SQL : " . htmlspecialchars($e->getMessage())];
    header('Location: ../settings.php?tab=backup'); exit;
}

/* ══════════════════════════════════════════════════════════════════════════
   3. Copie des fichiers
══════════════════════════════════════════════════════════════════════════ */
// config.php
copy($root . '/includes/config.php', $tmpBase . '/config.php');

// robots.txt / sitemap.xml
foreach (['robots.txt', 'sitemap.xml'] as $f) {
    if (file_exists($root . '/' . $f)) {
        copy($root . '/' . $f, $tmpBase . '/' . $f);
    }
}

// uploads/, themes/ et assets/images/
_bkCopyDir($root . '/uploads',        $tmpBase . '/uploads');
_bkCopyDir($root . '/themes',         $tmpBase . '/themes');
_bkCopyDir($root . '/assets/images',  $tmpBase . '/assets/images');

/* ══════════════════════════════════════════════════════════════════════════
   4. Manifest JSON
══════════════════════════════════════════════════════════════════════════ */
$manifest = [
    'articms_version' => CMS_VERSION,
    'created_at'      => date('Y-m-d H:i:s'),
    'php_version'     => PHP_VERSION,
    'db_name'         => DB_NAME,
    'contents'        => ['database', 'config', 'uploads', 'themes', 'assets_images', 'robots', 'sitemap'],
];
file_put_contents($tmpBase . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

/* ══════════════════════════════════════════════════════════════════════════
   5. Création de l'archive tar.gz
══════════════════════════════════════════════════════════════════════════ */
$archiveName = 'articms-backup-' . date('Ymd-His') . '.tar';
$tarPath     = sys_get_temp_dir() . '/' . $archiveName;
$gzPath      = $tarPath . '.gz';

try {
    // Supprimer une éventuelle archive résiduelle
    foreach ([$tarPath, $gzPath] as $p) {
        if (file_exists($p)) unlink($p);
    }

    $phar = new PharData($tarPath);
    $phar->buildFromDirectory($tmpBase);
    $phar->compress(Phar::GZ);  // crée $tarPath.gz
    unset($phar);
    unlink($tarPath); // supprimer le .tar non compressé
} catch (Throwable $e) {
    _bkCleanup($tmpBase);
    @unlink($tarPath);
    @unlink($gzPath);
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => "Erreur lors de la création de l'archive : " . htmlspecialchars($e->getMessage())];
    header('Location: ../settings.php?tab=backup'); exit;
}

_bkCleanup($tmpBase);

/* ══════════════════════════════════════════════════════════════════════════
   6. Envoi du fichier en téléchargement
══════════════════════════════════════════════════════════════════════════ */
if (!file_exists($gzPath)) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => "L'archive n'a pas pu être générée."];
    header('Location: ../settings.php?tab=backup'); exit;
}

header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="' . $archiveName . '.gz"');
header('Content-Length: ' . filesize($gzPath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($gzPath);
unlink($gzPath);
exit;

/* ══════════════════════════════════════════════════════════════════════════
   Helpers
══════════════════════════════════════════════════════════════════════════ */
function _bkCopyDir(string $src, string $dst): void
{
    if (!is_dir($src)) return;
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $item) {
        $rel  = substr($item->getPathname(), strlen($src) + 1);
        $dest = $dst . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($dest)) mkdir($dest, 0755, true);
        } else {
            copy($item->getPathname(), $dest);
        }
    }
}

function _bkCleanup(string $dir): void
{
    if (!is_dir($dir)) return;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}
