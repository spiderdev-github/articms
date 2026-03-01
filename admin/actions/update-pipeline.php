<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../contacts.php"); exit; }

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) { header("Location: ../contacts.php?notice=csrf"); exit; }

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['pipeline_status'] ?? '';

$allowed = ['new','in_progress','quoted','won','lost'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
  header("Location: ../contacts.php?notice=invalid"); exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare("UPDATE contacts SET pipeline_status=:s, updated_at=NOW() WHERE id=:id");
$stmt->execute([':s'=>$status, ':id'=>$id]);

header("Location: ../contact-view.php?id=".$id."&updated=1");
exit;