<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requirePermission('themes');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../page-editor.php'); exit;
}
validateCsrf($_POST['csrf_token'] ?? '');

// ─── Liste blanche stricte des fichiers autorisés ────────────────────────────
$allowed = [
    'home.php',
    'page.php',
    '_contact',
    '_realisations.php',
];

$file = basename($_POST['file'] ?? '');

if (!in_array($file, $allowed, true)) {
    header('Location: ../page-editor.php?error=forbidden'); exit;
}

$rootDir     = __DIR__ . '/../../';
$activeTheme = getSetting('active_theme', 'default');

// ─── Résolution du chemin réel selon la clé ─────────────────────────────────
if ($file === 'home.php') {
    $themeDir = realpath($rootDir . 'themes/' . $activeTheme . '/partials');
    if (!$themeDir) {
        // Le dossier n'existe pas encore, créer
        $themeDirPath = rtrim(realpath($rootDir), '/') . '/themes/' . $activeTheme . '/partials';
        mkdir($themeDirPath, 0755, true);
        $themeDir = $themeDirPath;
    }
    $absPath = $themeDir . '/' . $file;
} else {
    $absPath = realpath($rootDir) . '/' . $file;
}

// Vérification supplémentaire : le fichier doit être dans le rootDir
$realRoot = realpath($rootDir);
if (!str_starts_with($absPath, $realRoot)) {
    header('Location: ../page-editor.php?error=forbidden'); exit;
}

$content = str_replace("\r\n", "\n", $_POST['content'] ?? '');

// ─── Vérifier la syntaxe PHP avant d'écraser ─────────────────────────────────
$tmpFile = sys_get_temp_dir() . '/syntaxcheck_' . uniqid() . '.php';
file_put_contents($tmpFile, $content);
exec('php -l ' . escapeshellarg($tmpFile) . ' 2>&1', $output, $exitCode);
@unlink($tmpFile);

if ($exitCode !== 0) {
    $error = implode(' ', $output);
    // Stocker l'erreur en session flash et renvoyer vers l'éditeur
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Erreur de syntaxe PHP — fichier non sauvegardé : ' . htmlspecialchars($error)];
    header('Location: ../page-editor.php?file=' . urlencode($file) . '&error=syntax'); exit;
}

// ─── Sauvegarde ──────────────────────────────────────────────────────────────
$written = file_put_contents($absPath, $content);
if ($written === false) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Impossible d\'écrire le fichier (vérifier les permissions) : ' . htmlspecialchars($absPath)];
    header('Location: ../page-editor.php?file=' . urlencode($file) . '&error=syntax'); exit;
}
@chmod($absPath, 0664);

// Invalider l'opcache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($absPath, true);
}

header('Location: ../page-editor.php?file=' . urlencode($file) . '&saved=1'); exit;
