<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../forms.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header('Location: ../forms.php'); exit; }

$pdo    = getPDO();
$subId  = (int)($_POST['submission_id'] ?? 0);
$formId = (int)($_POST['form_id'] ?? 0);

if ($subId) {
    $pdo->prepare("DELETE FROM form_submissions WHERE id=?")->execute([$subId]);
}

header("Location: ../form-submissions.php?form_id=$formId");
exit;
