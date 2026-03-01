<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../media.php'); exit; }

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ../media.php?notice=csrf'); exit;
}

$rel     = trim($_POST['rel'] ?? '', '/');
$baseDir = realpath(__DIR__ . '/../../assets/images');

// Sécurité : autorisé uniquement dans medias/
if (!str_starts_with($rel, 'medias/') || str_contains($rel, '..')) {
    header('Location: ../media.php?notice=denied'); exit;
}

$fullPath = $baseDir . '/' . $rel;

// Sécurité : le chemin résolu doit rester dans baseDir/medias
$realFull = realpath($fullPath);
$realBase = realpath($baseDir . '/medias');

if (!$realFull || !str_starts_with($realFull, $realBase)) {
    header('Location: ../media.php?notice=denied'); exit;
}

@unlink($realFull);

header('Location: ../media.php?updated=deleted');
exit;
