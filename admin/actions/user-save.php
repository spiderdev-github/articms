<?php
require_once __DIR__ . '/../auth.php';
requirePermission('users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users.php'); exit;
}

validateCsrf($_POST['csrf_token'] ?? '');

$pdo        = getPDO();
$userId     = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$isNew      = ($userId === 0);
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$displayName = trim($_POST['display_name'] ?? '');
$role       = $_POST['role'] ?? 'editor';
$isActive   = isset($_POST['is_active']) ? 1 : 0;
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['password_confirm'] ?? '';

// Validate username
if (!preg_match('/^[a-zA-Z0-9_-]{2,50}$/', $username)) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Nom d\'utilisateur invalide (2-50 caractères, lettres/chiffres/-/_).'];
    header('Location: ' . ($isNew ? '../user-edit.php' : '../user-edit.php?id='.$userId)); exit;
}

// Validate role — can only assign roles at or below own level
$me = getCurrentAdmin();
$myRoleIndex = array_search(getAdminRole(), ROLES);
$targetRoleIndex = array_search($role, ROLES);
if ($targetRoleIndex === false || $targetRoleIndex < $myRoleIndex) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Vous ne pouvez pas attribuer ce rôle.'];
    header('Location: ' . ($isNew ? '../user-edit.php' : '../user-edit.php?id='.$userId)); exit;
}

// Validate email
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Email invalide.'];
    header('Location: ' . ($isNew ? '../user-edit.php' : '../user-edit.php?id='.$userId)); exit;
}

// Validate password
if ($isNew && strlen($password) < 8) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Le mot de passe doit contenir au moins 8 caractères.'];
    header('Location: ../user-edit.php'); exit;
}
if ($password && strlen($password) < 8) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Le mot de passe doit contenir au moins 8 caractères.'];
    header('Location: ../user-edit.php?id='.$userId); exit;
}
if ($password && $password !== $confirm) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Les mots de passe ne correspondent pas.'];
    header('Location: ' . ($isNew ? '../user-edit.php' : '../user-edit.php?id='.$userId)); exit;
}

// Check username uniqueness
$st = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
$st->execute([$username, $userId]);
if ($st->fetch()) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Ce nom d\'utilisateur est déjà pris.'];
    header('Location: ' . ($isNew ? '../user-edit.php' : '../user-edit.php?id='.$userId)); exit;
}

if ($isNew) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO admins (username, email, display_name, role, is_active, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $st->execute([$username, $email ?: null, $displayName ?: null, $role, $isActive, $hash]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Utilisateur créé avec succès.'];
} else {
    // Cannot demote the last super_admin
    if ($role !== 'super_admin') {
        $existing = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
        $existing->execute([$userId]);
        $existingRole = $existing->fetchColumn();
        if ($existingRole === 'super_admin') {
            $count = $pdo->query("SELECT COUNT(*) FROM admins WHERE role='super_admin' AND is_active=1")->fetchColumn();
            if ($count <= 1) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'Impossible de modifier le seul super administrateur.'];
                header('Location: ../user-edit.php?id='.$userId); exit;
            }
        }
    }

    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $pdo->prepare("UPDATE admins SET username=?, email=?, display_name=?, role=?, is_active=?, password_hash=? WHERE id=?");
        $st->execute([$username, $email ?: null, $displayName ?: null, $role, $isActive, $hash, $userId]);
    } else {
        $st = $pdo->prepare("UPDATE admins SET username=?, email=?, display_name=?, role=?, is_active=? WHERE id=?");
        $st->execute([$username, $email ?: null, $displayName ?: null, $role, $isActive, $userId]);
    }

    // If editing self, refresh session
    if ($userId === (int)$me['id']) {
        refreshAdminSession();
    }

    $_SESSION['flash'] = ['type'=>'success','msg'=>'Utilisateur mis à jour.'];
}

header('Location: ../users.php'); exit;
