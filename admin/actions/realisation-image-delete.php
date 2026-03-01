<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) exit;

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) exit;

$stmt = $pdo->prepare("SELECT image_path, realisation_id FROM realisation_images WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if ($row) {
  $file = realpath(__DIR__ . '/../../' . $row['image_path']);
  if ($file && file_exists($file)) unlink($file);

  $pdo->prepare("DELETE FROM realisation_images WHERE id=?")->execute([$id]);

  header("Location: ../realisation-edit.php?id=".$row['realisation_id']);
  exit;
}