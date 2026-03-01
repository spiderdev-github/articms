<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../contacts.php"); exit; }

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) { header("Location: ../contacts.php?notice=csrf"); exit; }

$contactId = (int)($_POST['contact_id'] ?? 0);
$tagId = (int)($_POST['tag_id'] ?? 0);

if ($contactId <= 0 || $tagId <= 0) {
  header("Location: ../contacts.php?notice=invalid"); exit;
}

$pdo = getPDO();
$pdo->prepare("DELETE FROM contact_tags WHERE contact_id=:c AND tag_id=:t")
    ->execute([':c'=>$contactId, ':t'=>$tagId]);

$pdo->prepare("UPDATE contacts SET updated_at=NOW() WHERE id=?")->execute([$contactId]);

header("Location: ../contact-view.php?id=".$contactId);
exit;