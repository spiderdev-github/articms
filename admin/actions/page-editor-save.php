<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../auth.php';
requirePermission('themes');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../page-editor.php'); exit;
}
validateCsrf($_POST['csrf_token'] ?? '');

// ─── Liste blanche stricte des fichiers autorisés ────────────────────────────
$allowed = [
    'index.php',
    'page.php',
    '_contact',
    '_realisations.php',
];

$file = basename($_POST['file'] ?? '');

if (!in_array($file, $allowed, true)) {
    header('Location: ../page-editor.php?error=forbidden'); exit;
}

$rootDir = __DIR__ . '/../../';
$absPath = realpath($rootDir) . '/' . $file;

// Vérification supplémentaire : le fichier doit être dans le rootDir
if (!str_starts_with($absPath, realpath($rootDir))) {
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
file_put_contents($absPath, $content);
@chmod($absPath, 0664);

// Invalider l'opcache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($absPath, true);
}

header('Location: ../page-editor.php?file=' . urlencode($file) . '&saved=1'); exit;
