<?php
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../themes.php"); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header("Location: ../themes.php?notice=csrf"); exit; }

$themeId     = basename($_POST['theme'] ?? '');
$activeTheme = getSetting('active_theme', 'default');
$themesDir   = __DIR__ . '/../../themes';
$themeDir    = $themesDir . '/' . $themeId;

// Refus de supprimer le thème actif ou le thème default
if (!$themeId || $themeId === $activeTheme || $themeId === 'default' || !is_dir($themeDir)) {
    header("Location: ../themes.php?notice=error"); exit;
}

// Suppression récursive du dossier
function deleteThemeDir(string $dir): void {
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}
deleteThemeDir($themeDir);

header("Location: ../themes.php?notice=deleted"); exit;
