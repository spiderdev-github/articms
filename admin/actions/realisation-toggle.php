<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();

require_once __DIR__ . '/../../includes/db.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../realisations.php"); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header("Location: ../realisations.php?notice=csrf"); exit; }

$id = (int)($_POST['id'] ?? 0);
$field = $_POST['field'] ?? '';
$allowed = ['is_published','is_featured'];

if ($id <= 0 || !in_array($field, $allowed, true)) { header("Location: ../realisations.php?notice=invalid"); exit; }

$pdo->prepare("UPDATE realisations SET $field = IF($field=1,0,1), updated_at=NOW() WHERE id=?")->execute([$id]);
header("Location: ../realisations.php?updated=1");
exit;