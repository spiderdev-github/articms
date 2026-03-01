<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../contacts.php"); exit; }

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) { header("Location: ../contacts.php?notice=csrf"); exit; }

$id = (int)($_POST['id'] ?? 0);
$next = trim($_POST['next_followup_at'] ?? '');

if ($id <= 0) { header("Location: ../contacts.php?notice=invalid"); exit; }

$pdo = getPDO();
$stmt = $pdo->prepare("
  UPDATE contacts
  SET followup_count = followup_count + 1,
      next_followup_at = :n,
      updated_at = NOW()
  WHERE id = :id
");
$stmt->execute([':n' => ($next !== '' ? $next : null), ':id' => $id]);

header("Location: ../contact-view.php?id=".$id."&followup=1");
exit;