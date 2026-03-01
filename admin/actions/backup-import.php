<?php
/**
 * ArtiCMS — Sauvegarde / Import
 * Restaure une archive tar.gz exportée par ArtiCMS.
 * Options sélectionnables :
 *   - restore_db      : importe database.sql
 *   - restore_uploads : restaure uploads/
 *   - restore_themes  : restaure themes/
 *   - restore_config  : restaure config.php
 */

require_once __DIR__ . '/../auth.php';
requirePermission('settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../settings.php?tab=backup'); exit;
}

validateCsrf($_POST['csrf_token'] ?? '', '../settings.php?tab=backup&notice=csrf');

/* ─── Sécurité : super_admin ou admin uniquement ─────────────────────── */
requireRole('admin');

set_time_limit(0);
ini_set('memory_limit', '512M');

$root = dirname(__DIR__, 2);

/* ──────────────────────────────────────────────────────────────────────── */
/* 0. Vérifications préliminaires                                           */
/* ──────────────────────────────────────────────────────────────────────── */
if (!class_exists('PharData')) {
    _impFail("L'extension Phar n'est pas disponible sur ce serveur.");
}

if (empty($_FILES['backup_file']['tmp_name']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => "Le fichier dépasse upload_max_filesize dans php.ini.",
        UPLOAD_ERR_FORM_SIZE  => "Le fichier dépasse MAX_FILE_SIZE du formulaire.",
        UPLOAD_ERR_PARTIAL    => "L'upload du fichier a été interrompu.",
        UPLOAD_ERR_NO_FILE    => "Aucun fichier sélectionné.",
        UPLOAD_ERR_NO_TMP_DIR => "Répertoire temporaire manquant.",
        UPLOAD_ERR_CANT_WRITE => "Impossible d'écrire le fichier.",
        UPLOAD_ERR_EXTENSION  => "Upload bloqué par une extension PHP.",
    ];
    $errCode = $_FILES['backup_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    _impFail($uploadErrors[$errCode] ?? "Erreur d'upload inconnue (code $errCode).");
}

// Vérification de l'extension
$origName = $_FILES['backup_file']['name'] ?? '';
if (!preg_match('/\.tar\.gz$/i', $origName)) {
    _impFail("Le fichier doit être une archive <code>.tar.gz</code> exportée par ArtiCMS.");
}

// Options de restauration
$restoreDb      = !empty($_POST['restore_db']);
$restoreUploads = !empty($_POST['restore_uploads']);
$restoreThemes  = !empty($_POST['restore_themes']);
$restoreConfig  = !empty($_POST['restore_config']);

$restoreImages = !empty($_POST['restore_images']);
if (!$restoreDb && !$restoreUploads && !$restoreThemes && !$restoreConfig && !$restoreImages) {
    _impFail("Sélectionnez au moins un élément à restaurer.");
}

/* ──────────────────────────────────────────────────────────────────────── */
/* 1. Copie du fichier uploadé vers un chemin avec extension correcte       */
/* ──────────────────────────────────────────────────────────────────────── */
$tmpUpload = sys_get_temp_dir() . '/articms_import_' . time() . '_' . bin2hex(random_bytes(4)) . '.tar.gz';
if (!move_uploaded_file($_FILES['backup_file']['tmp_name'], $tmpUpload)) {
    _impFail("Impossible de déplacer le fichier uploadé vers le répertoire temporaire.");
}

/* ──────────────────────────────────────────────────────────────────────── */
/* 2. Extraction de l'archive                                               */
/* ──────────────────────────────────────────────────────────────────────── */
$extractDir = sys_get_temp_dir() . '/articms_extract_' . time() . '_' . bin2hex(random_bytes(4));
mkdir($extractDir, 0700, true);

try {
    $phar = new PharData($tmpUpload);
    $phar->extractTo($extractDir, null, true);
    unset($phar);
} catch (Throwable $e) {
    @unlink($tmpUpload);
    _impFail("Impossible d'extraire l'archive : " . htmlspecialchars($e->getMessage()));
}
@unlink($tmpUpload);

/* ──────────────────────────────────────────────────────────────────────── */
/* 3. Lecture du manifeste                                                  */
/* ──────────────────────────────────────────────────────────────────────── */
// PharData extrait parfois dans un sous-dossier portant le nom de l'archive
$baseDir = _impFindBase($extractDir);

$manifestPath = $baseDir . '/manifest.json';
if (!file_exists($manifestPath)) {
    _impCleanup($extractDir);
    _impFail("Le fichier <code>manifest.json</code> est absent. Cette archive ne semble pas être une sauvegarde ArtiCMS valide.");
}

$manifest = json_decode(file_get_contents($manifestPath), true) ?? [];

/* ──────────────────────────────────────────────────────────────────────── */
/* 4. Restauration                                                          */
/* ──────────────────────────────────────────────────────────────────────── */
$log = [];
$errors = [];

/* ── 4a. Base de données ─── */
if ($restoreDb) {
    $sqlFile = $baseDir . '/database.sql';
    if (!file_exists($sqlFile)) {
        $errors[] = "database.sql introuvable dans l'archive.";
    } else {
        try {
            $pdo = getPDO();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $statements = _impParseSql(file_get_contents($sqlFile));
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

/* ── 4b. Uploads ─── */
if ($restoreUploads) {
    $srcUploads = $baseDir . '/uploads';
    if (!is_dir($srcUploads)) {
        $errors[] = "Le dossier <code>uploads/</code> est absent de l'archive.";
    } else {
        try {
            _impSyncDir($srcUploads, $root . '/uploads');
            $log[] = "✔ Médias (uploads/) restaurés.";
        } catch (Throwable $e) {
            $errors[] = "Erreur lors de la restauration des uploads : " . htmlspecialchars($e->getMessage());
        }
    }
}

/* ── 4c. Images (assets/images/) ─── */
$restoreImages = !empty($_POST['restore_images']);
if ($restoreImages) {
    $srcImages = $baseDir . '/assets/images';
    if (!is_dir($srcImages)) {
        $errors[] = "Le dossier <code>assets/images/</code> est absent de l'archive.";
    } else {
        try {
            _impSyncDir($srcImages, $root . '/assets/images');
            $log[] = "✔ Images (assets/images/) restaurées.";
        } catch (Throwable $e) {
            $errors[] = "Erreur lors de la restauration des images : " . htmlspecialchars($e->getMessage());
        }
    }
}

/* ── 4d. Thèmes ─── */
if ($restoreThemes) {
    $srcThemes = $baseDir . '/themes';
    if (!is_dir($srcThemes)) {
        $errors[] = "Le dossier <code>themes/</code> est absent de l'archive.";
    } else {
        try {
            _impSyncDir($srcThemes, $root . '/themes');
            $log[] = "✔ Thèmes restaurés.";
        } catch (Throwable $e) {
            $errors[] = "Erreur lors de la restauration des thèmes : " . htmlspecialchars($e->getMessage());
        }
    }
}

/* ── 4e. Configuration ─── */
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

    // robots.txt et sitemap.xml (optionnel, présents si dispo)
    foreach (['robots.txt', 'sitemap.xml'] as $f) {
        $src = $baseDir . '/' . $f;
        if (file_exists($src)) {
            copy($src, $root . '/' . $f);
            $log[] = "✔ $f restauré.";
        }
    }
}

_impCleanup($extractDir);

/* ──────────────────────────────────────────────────────────────────────── */
/* 5. Résumé                                                                */
/* ──────────────────────────────────────────────────────────────────────── */
$hasErrors = count($errors) > 0;
$msgType   = $hasErrors ? 'warning' : 'success';

$summary  = implode('<br>', $log);
if ($hasErrors) {
    $summary .= '<br><strong>Avertissements :</strong><br>' . implode('<br>', $errors);
}

if (!$summary) {
    $summary = "Aucune opération effectuée.";
}

$_SESSION['flash'] = ['type' => $msgType, 'msg' => $summary];
header('Location: ../settings.php?tab=backup');
exit;

/* ══════════════════════════════════════════════════════════════════════════
   Helpers
══════════════════════════════════════════════════════════════════════════ */

function _impFail(string $msg): never
{
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => $msg];
    header('Location: ../settings.php?tab=backup');
    exit;
}

/** Cherche le répertoire racine dans l'archive extraite (peut être un sous-dossier). */
function _impFindBase(string $extractDir): string
{
    // Si manifest.json est directement dans $extractDir -> c'est la base
    if (file_exists($extractDir . '/manifest.json')) {
        return $extractDir;
    }
    // Sinon chercher dans le premier sous-dossier
    foreach (scandir($extractDir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $sub = $extractDir . '/' . $entry;
        if (is_dir($sub) && file_exists($sub . '/manifest.json')) {
            return $sub;
        }
    }
    return $extractDir;
}

/** Parse un fichier SQL en tableau de statements. */
function _impParseSql(string $sql): array
{
    // Supprimer les commentaires -- et /* */
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $lines = explode("\n", $sql);
    $filtered = [];
    foreach ($lines as $line) {
        $t = ltrim($line);
        if ($t === '' || str_starts_with($t, '--')) continue;
        $filtered[] = $line;
    }
    $sql = implode("\n", $filtered);
    // Couper sur les ";" en fin de statement (hors chaînes)
    $stmts = [];
    $current = '';
    $inStr = false;
    $strChar = '';
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($inStr) {
            $current .= $c;
            if ($c === '\\') {
                $current .= $sql[++$i] ?? '';
            } elseif ($c === $strChar) {
                $inStr = false;
            }
        } elseif ($c === "'" || $c === '"' || $c === '`') {
            $inStr   = true;
            $strChar = $c;
            $current .= $c;
        } elseif ($c === ';') {
            $stmts[] = trim($current);
            $current = '';
        } else {
            $current .= $c;
        }
    }
    if (trim($current) !== '') {
        $stmts[] = trim($current);
    }
    return $stmts;
}

/** Copie récursivement $src dans $dst (crée si absent, écrase si existant). */
function _impSyncDir(string $src, string $dst): void
{
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
            @copy($item->getPathname(), $dest);
        }
    }
}

/** Supprime récursivement un répertoire temporaire. */
function _impCleanup(string $dir): void
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
