<?php
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../themes.php"); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header("Location: ../themes.php?notice=csrf"); exit; }

$cloneFrom   = basename($_POST['clone_from'] ?? 'default');
$themeName   = trim($_POST['theme_name'] ?? '');
$themeDesc   = trim($_POST['theme_description'] ?? '');
$themesDir   = __DIR__ . '/../../themes';

if (!$themeName) { header("Location: ../themes.php?notice=error"); exit; }

// Générer un id slug unique
$baseId = preg_replace('/[^a-z0-9]+/', '-', strtolower($themeName));
$baseId = trim($baseId, '-') ?: 'theme';
$newId  = $baseId;
$i = 2;
while (is_dir($themesDir . '/' . $newId)) {
    $newId = $baseId . '-' . $i++;
}

$srcDir = $themesDir . '/' . $cloneFrom;
$dstDir = $themesDir . '/' . $newId;

if (!is_dir($srcDir)) { header("Location: ../themes.php?notice=error"); exit; }

// Copier récursivement le thème source
function copyTheme(string $src, string $dst): void {
    mkdir($dst, 0755, true);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $item) {
        $target = $dst . '/' . $iter->getSubPathname();
        if ($item->isDir()) {
            if (!is_dir($target)) mkdir($target, 0755, true);
        } else {
            copy($item->getPathname(), $target);
        }
    }
}
copyTheme($srcDir, $dstDir);

// Mettre à jour theme.json avec le nouveau nom
$metaFile = $dstDir . '/theme.json';
$meta     = file_exists($metaFile) ? (json_decode(file_get_contents($metaFile), true) ?: []) : [];
$meta['name']        = $themeName;
$meta['description'] = $themeDesc ?: ($meta['description'] ?? '');
$meta['version']     = '1.0';
file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header("Location: ../themes.php?notice=created"); exit;
