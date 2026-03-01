<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../cms-pages.php"); exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    header("Location: ../cms-pages.php?notice=csrf"); exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) {
    header("Location: ../cms-pages.php"); exit;
}

$pdo = getPDO();
$pdo->prepare("DELETE FROM cms_pages WHERE id = ?")->execute([$id]);

header("Location: ../cms-pages.php?deleted=1");
exit;
