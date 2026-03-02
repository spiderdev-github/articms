<?php
/**
 * ArtiCMS — Confirmation d'import avec conflit de configuration.
 * Reprend l'import en attente stocké en session après confirmation
 * de l'utilisateur concernant les différences de BDD / BASE_URL.
 */

require_once __DIR__ . '/../auth.php';
requirePermission('settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../settings.php?tab=backup'); exit;
}

validateCsrf($_POST['csrf_token'] ?? '', '../settings.php?tab=backup&notice=csrf');
requireRole('admin');

/* ─── Validation du token de confirmation ────────────────────────────── */
$token   = $_POST['import_token'] ?? '';
$pending = $_SESSION['import_pending'] ?? null;

if (!$pending || ($pending['token'] ?? '') !== $token || ($pending['expires'] ?? 0) < time()) {
    unset($_SESSION['import_pending']);
    $_SESSION['flash'] = ['type' => 'warning', 'msg' => "Session de confirmation expirée ou invalide. Veuillez relancer l'import."];
    header('Location: ../settings.php?tab=backup'); exit;
}

/* ─── Annulation ─────────────────────────────────────────────────────── */
if (isset($_POST['cancel_import'])) {
    _impCfgCleanup($pending['extractDir'] ?? '');
    unset($_SESSION['import_pending']);
    $_SESSION['flash'] = ['type' => 'info', 'msg' => "Import annulé. Aucune modification effectuée."];
    header('Location: ../settings.php?tab=backup'); exit;
}

/* ─── Restauration ───────────────────────────────────────────────────── */
$root       = dirname(__DIR__, 2);
$baseDir    = $pending['baseDir'];
$extractDir = $pending['extractDir'];

$restoreDb      = $pending['restoreDb']      ?? false;
$restoreUploads = $pending['restoreUploads'] ?? false;
$restoreThemes  = $pending['restoreThemes']  ?? false;
$restoreConfig  = $pending['restoreConfig']  ?? false;
$restoreImages  = $pending['restoreImages']  ?? false;

unset($_SESSION['import_pending']);

set_time_limit(0);
ini_set('memory_limit', '512M');

$log    = [];
$errors = [];

/* ── Base de données ─── */
if ($restoreDb) {
    $sqlFile = $baseDir . '/database.sql';
    if (!file_exists($sqlFile)) {
        $errors[] = "database.sql introuvable dans l'archive.";
    } else {
        try {
            $pdo = getPDO();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $statements = _impCfgParseSql(file_get_contents($sqlFile));
            $count = 0;
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') continue;
                $pdo->exec($stmt);
                $count++;
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $log[] = "✔ Base de données restaurée ($count requêtes exécutées).";
        } catch (Throwable $e) {
            $errors[] = "Erreur SQL : " . htmlspecialchars($e->getMessage());
            try { getPDO()->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch (Throwable $ignored) {}
        }
    }
}

/* ── Uploads ─── */
if ($restoreUploads) {
    $srcUploads = $baseDir . '/uploads';
    if (!is_dir($srcUploads)) {
        $errors[] = "Le dossier <code>uploads/</code> est absent de l'archive.";
    } else {
        try {
            _impCfgSyncDir($srcUploads, $root . '/uploads');
            $log[] = "✔ Médias (uploads/) restaurés.";
        } catch (Throwable $e) {
            $errors[] = "Erreur lors de la restauration des uploads : " . htmlspecialchars($e->getMessage());
        }
    }
}

/* ── Images ─── */
if ($restoreImages) {
    $srcImages = $baseDir . '/assets/images';
    if (!is_dir($srcImages)) {
        $errors[] = "Le dossier <code>assets/images/</code> est absent de l'archive.";
    } else {
        try {
            _impCfgSyncDir($srcImages, $root . '/assets/images');
            $log[] = "✔ Images (assets/images/) restaurées.";
        } catch (Throwable $e) {
            $errors[] = "Erreur lors de la restauration des images : " . htmlspecialchars($e->getMessage());
        }
    }
}

/* ── Thèmes ─── */
if ($restoreThemes) {
    $srcThemes = $baseDir . '/themes';
    if (!is_dir($srcThemes)) {
        $errors[] = "Le dossier <code>themes/</code> est absent de l'archive.";
    } else {
        try {
            _impCfgSyncDir($srcThemes, $root . '/themes');
            $log[] = "✔ Thèmes restaurés.";
        } catch (Throwable $e) {
            $errors[] = "Erreur lors de la restauration des thèmes : " . htmlspecialchars($e->getMessage());
        }
    }
}

/* ── Configuration ─── */
if ($restoreConfig) {
    $srcConfig = $baseDir . '/config.php';
    if (!file_exists($srcConfig)) {
        $errors[] = "config.php est absent de l'archive.";
    } elseif (!is_writable($root . '/includes/config.php')) {
        $errors[] = "includes/config.php n'est pas accessible en écriture.";
    } else {
        copy($srcConfig, $root . '/includes/config.php');
        $log[] = "✔ Configuration (includes/config.php) restaurée.";
    }
    foreach (['robots.txt', 'sitemap.xml'] as $f) {
        $src = $baseDir . '/' . $f;
        if (file_exists($src)) {
            copy($src, $root . '/' . $f);
            $log[] = "✔ $f restauré.";
        }
    }
}

_impCfgCleanup($extractDir);

/* ─── Résumé ─────────────────────────────────────────────────────────── */
$hasErrors = count($errors) > 0;
$msgType   = $hasErrors ? 'warning' : 'success';
$summary   = implode('<br>', $log);
if ($hasErrors) {
    $summary .= '<br><strong>Avertissements :</strong><br>' . implode('<br>', $errors);
}
if (!$summary) $summary = "Aucune opération effectuée.";

$_SESSION['flash'] = ['type' => $msgType, 'msg' => $summary];
header('Location: ../settings.php?tab=backup');
exit;

/* ══════════════════════════════════════════════════════════════════════════
   Helpers locaux (préfixés _impCfg_ pour éviter les conflits de noms)
══════════════════════════════════════════════════════════════════════════ */

function _impCfgParseSql(string $sql): array
{
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $lines = explode("\n", $sql);
    $filtered = [];
    foreach ($lines as $line) {
        $t = ltrim($line);
        if ($t === '' || str_starts_with($t, '--')) continue;
        $filtered[] = $line;
    }
    $sql  = implode("\n", $filtered);
    $stmts   = [];
    $current = '';
    $inStr   = false;
    $strChar = '';
    $len     = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($inStr) {
            $current .= $c;
            if ($c === '\\') { $current .= $sql[++$i] ?? ''; }
            elseif ($c === $strChar) { $inStr = false; }
        } elseif ($c === "'" || $c === '"' || $c === '`') {
            $inStr = true; $strChar = $c; $current .= $c;
        } elseif ($c === ';') {
            $stmts[] = trim($current); $current = '';
        } else {
            $current .= $c;
        }
    }
    if (trim($current) !== '') $stmts[] = trim($current);
    return $stmts;
}

function _impCfgSyncDir(string $src, string $dst): void
{
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $item) {
        $rel  = substr($item->getPathname(), strlen($src) + 1);
        $dest = $dst . '/' . $rel;
        if ($item->isDir()) { if (!is_dir($dest)) mkdir($dest, 0755, true); }
        else { @copy($item->getPathname(), $dest); }
    }
}

function _impCfgCleanup(string $dir): void
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
