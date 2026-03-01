<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../contacts.php"); exit; }

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) { header("Location: ../contacts.php?notice=csrf"); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header("Location: ../contacts.php?notice=invalid"); exit; }

$pdo = getPDO();
$pdo->prepare("UPDATE contacts SET archived_at = NULL, updated_at = NOW() WHERE id = ?")->execute([$id]);

header("Location: ../contacts.php?restored=1");
exit;