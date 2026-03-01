<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();

require_once __DIR__ . '/../../includes/db.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../realisations.php"); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header("Location: ../realisations.php?notice=csrf"); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header("Location: ../realisations.php?notice=invalid"); exit; }

$pdo->prepare("DELETE FROM realisations WHERE id=?")->execute([$id]);
header("Location: ../realisations.php?deleted=1");
exit;