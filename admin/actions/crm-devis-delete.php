<?php
require_once __DIR__ . '/../auth.php';
requirePermission('crm');
require_once __DIR__ . '/../../includes/db.php';
$pdo  = getPDO();
$csrf = getCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['csrf'] ?? '') !== $csrf) { die('CSRF'); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: ../crm-devis.php'); exit; }

// crm_devis_lines deleted via FK cascade
$pdo->prepare("DELETE FROM crm_devis WHERE id = ?")->execute([$id]);

$_SESSION['flash'] = ['type'=>'success','msg'=>'Document supprimé.'];
header('Location: ../crm-devis.php');
exit;
