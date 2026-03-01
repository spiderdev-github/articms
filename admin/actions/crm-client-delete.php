<?php
require_once __DIR__ . '/../auth.php';
requirePermission('crm');
require_once __DIR__ . '/../../includes/db.php';
$pdo  = getPDO();
$csrf = getCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['csrf'] ?? '') !== $csrf) { die('CSRF'); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: ../crm-clients.php'); exit; }

// crm_devis deleted via FK cascade
$pdo->prepare("DELETE FROM crm_clients WHERE id = ?")->execute([$id]);

$_SESSION['flash'] = ['type'=>'success','msg'=>'Client supprimé.'];
header('Location: ../crm-clients.php');
exit;
