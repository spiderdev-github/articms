<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    header("Location: ../dashboard.php?notice=csrf");
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = ['new', 'treated'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
    header("Location: ../dashboard.php?notice=invalid");
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare("UPDATE contacts SET status = :status WHERE id = :id");
$stmt->execute([
    ':status' => $status,
    ':id' => $id
]);

header("Location: ../dashboard.php?updated=1");
exit;