<?php
require_once __DIR__ . '/../../admin/auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../galleries.php'); exit; }
if (!verifyCsrfToken($_POST['csrf'] ?? '')) { header('Location: ../galleries.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    // CASCADE will remove gallery_items
    $pdo->prepare("DELETE FROM galleries WHERE id = ?")->execute([$id]);
}

header('Location: ../galleries.php?deleted=1');
exit;
