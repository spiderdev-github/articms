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

// Cannot delete own account
if ($userId === (int)$me['id']) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Vous ne pouvez pas supprimer votre propre compte.'];
    header('Location: ../users.php'); exit;
}

// Cannot delete last super_admin
$st = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
$st->execute([$userId]);
$target = $st->fetch(PDO::FETCH_ASSOC);
if (!$target) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Utilisateur introuvable.'];
    header('Location: ../users.php'); exit;
}

if ($target['role'] === 'super_admin') {
    $count = $pdo->query("SELECT COUNT(*) FROM admins WHERE role='super_admin'")->fetchColumn();
    if ($count <= 1) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Impossible de supprimer le seul super administrateur.'];
        header('Location: ../users.php'); exit;
    }
}

$pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$userId]);
$_SESSION['flash'] = ['type'=>'success','msg'=>'Utilisateur supprimé.'];
header('Location: ../users.php'); exit;
