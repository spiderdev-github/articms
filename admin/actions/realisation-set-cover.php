<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) exit;

$imageId = (int)($_POST['image_id'] ?? 0);
if ($imageId <= 0) exit;

// récupérer image + realisation
$stmt = $pdo->prepare("
  SELECT ri.image_path, ri.realisation_id
  FROM realisation_images ri
  WHERE ri.id = ?
  LIMIT 1
");
$stmt->execute([$imageId]);
$row = $stmt->fetch();

if (!$row) exit;

$pdo->prepare("
  UPDATE realisations
  SET cover_image = ?, updated_at = NOW()
  WHERE id = ?
")->execute([$row['image_path'], $row['realisation_id']]);

header("Location: ../realisation-edit.php?id=".$row['realisation_id']."&cover=1");
exit;