<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../forms.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header('Location: ../forms.php'); exit; }

$pdo    = getPDO();
$formId = (int)($_POST['form_id'] ?? 0);

if (!$formId) { header('Location: ../forms.php'); exit; }

// Safety: don't allow deletion of the core contact form
$form = $pdo->prepare("SELECT slug FROM forms WHERE id = ?");
$form->execute([$formId]);
$row = $form->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Formulaire introuvable.'];
    header('Location: ../forms.php'); exit;
}
if ($row['slug'] === 'contact') {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Le formulaire contact ne peut pas être supprimé.'];
    header('Location: ../forms.php'); exit;
}

try {
    // form_submissions are deleted via CASCADE
    $pdo->prepare("DELETE FROM forms WHERE id = ?")->execute([$formId]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Formulaire supprimé.'];
} catch (Exception $e) {
    error_log('[form-delete] '.$e->getMessage());
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Erreur lors de la suppression.'];
}

header('Location: ../forms.php');
exit;
