<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../forms.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header('Location: ../forms.php'); exit; }

$pdo    = getPDO();
$formId = (int)($_POST['form_id'] ?? 0);

if ($formId) {
    $pdo->prepare("DELETE FROM form_submissions WHERE form_id=?")->execute([$formId]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Toutes les soumissions ont été supprimées.'];
}

header("Location: ../form-submissions.php?form_id=$formId");
exit;
