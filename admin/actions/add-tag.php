<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../contacts.php"); exit; }

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) { header("Location: ../contacts.php?notice=csrf"); exit; }

$contactId = (int)($_POST['contact_id'] ?? 0);
$tagName = strtolower(trim($_POST['tag'] ?? ''));

if ($contactId <= 0 || $tagName === '' || strlen($tagName) > 60) {
  header("Location: ../contact-view.php?id=".$contactId."&notice=invalid"); exit;
}

$pdo = getPDO();

// create tag if not exists
$stmt = $pdo->prepare("SELECT id FROM tags WHERE name = :n LIMIT 1");
$stmt->execute([':n'=>$tagName]);
$tag = $stmt->fetch();

if (!$tag) {
  $pdo->prepare("INSERT INTO tags (name, created_at) VALUES (:n, NOW())")->execute([':n'=>$tagName]);
  $tagId = (int)$pdo->lastInsertId();
} else {
  $tagId = (int)$tag['id'];
}

// attach
$pdo->prepare("INSERT IGNORE INTO contact_tags (contact_id, tag_id) VALUES (:c,:t)")
    ->execute([':c'=>$contactId, ':t'=>$tagId]);

$pdo->prepare("UPDATE contacts SET updated_at=NOW() WHERE id=?")->execute([$contactId]);

header("Location: ../contact-view.php?id=".$contactId."&tag=1");
exit;