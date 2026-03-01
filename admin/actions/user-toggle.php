<?php
require_once __DIR__ . '/../auth.php';
requirePermission('users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users.php'); exit;
}

validateCsrf($_POST['csrf_token'] ?? '');

$pdo    = getPDO();
$userId = (int)($_POST['user_id'] ?? 0);
$me     = getCurrentAdmin();

if (!$userId) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Utilisateur invalide.'];
    header('Location: ../users.php'); exit;
}

// Cannot deactivate own account
if ($userId === (int)$me['id']) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Vous ne pouvez pas désactiver votre propre compte.'];
    header('Location: ../users.php'); exit;
}

// Check target exists + role
$st = $pdo->prepare("SELECT role, is_active FROM admins WHERE id = ?");
$st->execute([$userId]);
$target = $st->fetch(PDO::FETCH_ASSOC);
if (!$target) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Utilisateur introuvable.'];
    header('Location: ../users.php'); exit;
}

// Cannot deactivate last super_admin
if ($target['role'] === 'super_admin' && $target['is_active']) {
    $count = $pdo->query("SELECT COUNT(*) FROM admins WHERE role='super_admin' AND is_active=1")->fetchColumn();
    if ($count <= 1) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Impossible de désactiver le seul super administrateur actif.'];
        header('Location: ../users.php'); exit;
    }
}

$newStatus = $target['is_active'] ? 0 : 1;
$pdo->prepare("UPDATE admins SET is_active = ? WHERE id = ?")->execute([$newStatus, $userId]);

$msg = $newStatus ? 'Compte activé.' : 'Compte désactivé.';
$_SESSION['flash'] = ['type'=>'success','msg'=>$msg];
header('Location: ../users.php'); exit;
