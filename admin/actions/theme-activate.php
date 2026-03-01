<?php
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../themes.php"); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header("Location: ../themes.php?notice=csrf"); exit; }

$themeId  = basename($_POST['theme'] ?? '');
$themeDir = __DIR__ . '/../../themes/' . $themeId;

if (!$themeId || !is_dir($themeDir)) {
    header("Location: ../themes.php?notice=error"); exit;
}

setSetting('active_theme', $themeId);

header("Location: ../themes.php?notice=activated"); exit;
