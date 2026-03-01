<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../contacts.php"); exit; }

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) { header("Location: ../contacts.php?notice=csrf"); exit; }

$contactId = (int)($_POST['contact_id'] ?? 0);
$note = trim($_POST['note'] ?? '');

if ($contactId <= 0 || $note === '') {
  header("Location: ../contact-view.php?id=".$contactId."&notice=missing"); exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare("INSERT INTO contact_notes (contact_id, admin_id, note, created_at) VALUES (:c,:a,:n,NOW())");
$stmt->execute([
  ':c' => $contactId,
  ':a' => (int)$_SESSION['admin_id'],
  ':n' => $note
]);

$pdo->prepare("UPDATE contacts SET updated_at=NOW() WHERE id=?")->execute([$contactId]);

header("Location: ../contact-view.php?id=".$contactId."&note=1");
exit;