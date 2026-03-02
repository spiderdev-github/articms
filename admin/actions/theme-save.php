<?php
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../themes.php"); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header("Location: ../themes.php?notice=csrf"); exit; }

$themeId  = basename($_POST['theme'] ?? '');
$file     = ltrim($_POST['file'] ?? '', '/');
$content  = str_replace("\r\n", "\n", $_POST['content'] ?? '');
$themesDir = __DIR__ . '/../../themes';
$themeDir  = $themesDir . '/' . $themeId;

// Sécurité : valider le fichier cible
$allowedFiles = [
    'variables.css', 'style.css', 'responsive.css',
    'partials/header.php', 
    'partials/home.php',
    'partials/realisations.php', 
    'partials/contact.php', 
    'partials/page.php', 
    'partials/footer.php', 
    'theme.json',
    'partials/home.json'
];
if (!$themeId || !in_array($file, $allowedFiles, true) || !is_dir($themeDir)) {
    header("Location: ../themes.php?notice=error"); exit;
}

$absPath = $themeDir . '/' . $file;

// Créer le répertoire partials si besoin
if (str_starts_with($file, 'partials/') && !is_dir(dirname($absPath))) {
    mkdir(dirname($absPath), 0755, true);
}

// Si c'est un partial vide → supprimer plutôt que créer un fichier vide
if (str_starts_with($file, 'partials/') && trim($content) === '' && !file_exists($absPath)) {
    header("Location: ../theme-edit.php?theme=" . urlencode($themeId) . "&file=" . urlencode($file) . "&saved=1");
    exit;
}

file_put_contents($absPath, $content);

// S'assurer que le fichier est lisible/modifiable par tous (dev multi-user)
@chmod($absPath, 0666);

// Invalider le cache opcache pour que les changements soient pris en compte immédiatement
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($absPath, true);
}

header("Location: ../theme-edit.php?theme=" . urlencode($themeId) . "&file=" . urlencode($file) . "&saved=1");
exit;
