<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();

require_once __DIR__ . '/../../classes/TOTP.php';

$pdo = getPDO();
$me  = getCurrentAdmin();

// Handle avatar removal (GET request via link)
if (isset($_GET['remove_avatar']) && isset($_GET['csrf_token'])) {
    validateCsrf($_GET['csrf_token']);
    $st = $pdo->prepare("SELECT avatar FROM admins WHERE id = ?");
    $st->execute([$me['id']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row['avatar']) {
        $path = __DIR__ . '/../../uploads/avatars/' . basename($row['avatar']);
        if (file_exists($path)) @unlink($path);
        $pdo->prepare("UPDATE admins SET avatar = NULL WHERE id = ?")->execute([$me['id']]);
    }
    refreshAdminSession();
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Avatar supprimé.'];
    header('Location: ../profile.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php'); exit;
}

validateCsrf($_POST['csrf_token'] ?? '');

// ── 2FA actions ───────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === '2fa_start') {
    // Generate a fresh secret, store in session, redirect to profile for QR display
    $_SESSION['2fa_pending_secret'] = TOTP::generateSecret(16);
    header('Location: ../profile.php'); exit;
}

if ($action === '2fa_cancel') {
    unset($_SESSION['2fa_pending_secret']);
    header('Location: ../profile.php'); exit;
}

if ($action === '2fa_confirm') {
    $pendingSecret = $_SESSION['2fa_pending_secret'] ?? '';
    $code          = trim($_POST['totp_code'] ?? '');
    if (!$pendingSecret) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Session expirée, recommencez la configuration.'];
        header('Location: ../profile.php'); exit;
    }
    if (!TOTP::verify($pendingSecret, $code)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Code incorrect. Vérifiez votre application et réessayez.'];
        header('Location: ../profile.php'); exit;
    }
    // Code valid → save secret to DB
    $pdo->prepare("UPDATE admins SET totp_secret = ?, totp_enabled = 1 WHERE id = ?")
        ->execute([$pendingSecret, $me['id']]);
    unset($_SESSION['2fa_pending_secret']);
    // Generate 8 recovery codes
    $codes   = TOTP::generateRecoveryCodes(8);
    $pdo->prepare("DELETE FROM admin_recovery_codes WHERE admin_id = ?")->execute([$me['id']]);
    $insStmt = $pdo->prepare("INSERT INTO admin_recovery_codes (admin_id, code_hash) VALUES (?,?)");
    foreach ($codes as $c) {
        $insStmt->execute([$me['id'], hash('sha256', strtoupper(str_replace('-', '', $c)))]);
    }
    $_SESSION['2fa_recovery_codes'] = $codes; // shown once in profile
    refreshAdminSession();
    $_SESSION['flash'] = ['type'=>'warning','msg'=>'✓ 2FA activée ! Sauvegardez vos codes de récupération maintenant.'];
    header('Location: ../profile.php'); exit;
}

if ($action === '2fa_disable') {
    $currentPw = $_POST['current_password'] ?? '';
    $code      = trim($_POST['totp_code'] ?? '');
    // Verify password
    $st = $pdo->prepare("SELECT password_hash, totp_secret FROM admins WHERE id = ?");
    $st->execute([$me['id']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!password_verify($currentPw, $row['password_hash'])) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Mot de passe incorrect.'];
        header('Location: ../profile.php'); exit;
    }
    if ($code && !TOTP::verify($row['totp_secret'], $code)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Code 2FA incorrect.'];
        header('Location: ../profile.php'); exit;
    }
    $pdo->prepare("UPDATE admins SET totp_enabled = 0, totp_secret = NULL WHERE id = ?")
        ->execute([$me['id']]);
    // Clean up recovery codes and trusted devices
    $pdo->prepare("DELETE FROM admin_recovery_codes WHERE admin_id = ?")->execute([$me['id']]);
    $pdo->prepare("DELETE FROM admin_trusted_devices WHERE admin_id = ?")->execute([$me['id']]);
    // Clear trusted device cookie
    $cp = defined('BASE_URL') ? rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/') . '/admin/' : '/';
    setcookie('jp_trusted_device', '', ['expires'=>1,'path'=>$cp,'httponly'=>true,'samesite'=>'Lax']);
    refreshAdminSession();
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Double authentification désactivée.'];
    header('Location: ../profile.php'); exit;
}

if ($action === '2fa_regen_codes') {
    $currentPw = $_POST['current_password'] ?? '';
    $st = $pdo->prepare("SELECT password_hash, totp_enabled FROM admins WHERE id = ?");
    $st->execute([$me['id']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row['totp_enabled']) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'La 2FA n\'est pas activée.'];
        header('Location: ../profile.php'); exit;
    }
    if (!password_verify($currentPw, $row['password_hash'])) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Mot de passe incorrect.'];
        header('Location: ../profile.php'); exit;
    }
    $codes   = TOTP::generateRecoveryCodes(8);
    $pdo->prepare("DELETE FROM admin_recovery_codes WHERE admin_id = ?")->execute([$me['id']]);
    $insStmt = $pdo->prepare("INSERT INTO admin_recovery_codes (admin_id, code_hash) VALUES (?,?)");
    foreach ($codes as $c) {
        $insStmt->execute([$me['id'], hash('sha256', strtoupper(str_replace('-', '', $c)))]);
    }
    $_SESSION['2fa_recovery_codes'] = $codes;
    $_SESSION['flash'] = ['type'=>'warning','msg'=>'Nouveaux codes générés. Sauvegardez-les immédiatement !'];
    header('Location: ../profile.php'); exit;
}

if ($action === '2fa_revoke_device') {
    $deviceId = (int)($_POST['device_id'] ?? 0);
    $st = $pdo->prepare("SELECT token_hash FROM admin_trusted_devices WHERE id = ? AND admin_id = ?");
    $st->execute([$deviceId, $me['id']]);
    $device = $st->fetch(PDO::FETCH_ASSOC);
    if ($device) {
        $pdo->prepare("DELETE FROM admin_trusted_devices WHERE id = ?")->execute([$deviceId]);
        // If this is the current browser's cookie, clear it
        $cookieVal = $_COOKIE['jp_trusted_device'] ?? '';
        if ($cookieVal && str_contains($cookieVal, ':')) {
            [, $tok] = explode(':', $cookieVal, 2);
            if (hash('sha256', $tok) === $device['token_hash']) {
                $cp = defined('BASE_URL') ? rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/') . '/admin/' : '/';
                setcookie('jp_trusted_device', '', ['expires'=>1,'path'=>$cp,'httponly'=>true,'samesite'=>'Lax']);
            }
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Appareil de confiance révoqué.'];
    }
    header('Location: ../profile.php'); exit;
}

// Password change mode
if (!empty($_POST['change_password'])) {
    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    // Verify current password
    $st = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $st->execute([$me['id']]);
    $hash = $st->fetchColumn();

    if (!password_verify($currentPw, $hash)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Mot de passe actuel incorrect.'];
        header('Location: ../profile.php'); exit;
    }
    if (strlen($newPw) < 8) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
        header('Location: ../profile.php'); exit;
    }
    if ($newPw !== $confirmPw) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Les mots de passe ne correspondent pas.'];
        header('Location: ../profile.php'); exit;
    }

    $newHash = password_hash($newPw, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([$newHash, $me['id']]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Mot de passe changé avec succès.'];
    header('Location: ../profile.php'); exit;
}

// Profile update mode
$displayName = trim($_POST['display_name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$avatarCol   = null; // will not update unless new file

// Validate email
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Email invalide.'];
    header('Location: ../profile.php'); exit;
}

// Handle avatar upload
if (!empty($_FILES['avatar']['tmp_name'])) {
    $file = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Format d\'image non supporté (jpg, png, gif, webp).'];
        header('Location: ../profile.php'); exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'L\'image ne doit pas dépasser 2 Mo.'];
        header('Location: ../profile.php'); exit;
    }

    $uploadDir = __DIR__ . '/../../uploads/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Remove old avatar
    $st = $pdo->prepare("SELECT avatar FROM admins WHERE id = ?");
    $st->execute([$me['id']]);
    $oldAvatar = $st->fetchColumn();
    if ($oldAvatar) {
        $oldPath = $uploadDir . basename($oldAvatar);
        if (file_exists($oldPath)) @unlink($oldPath);
    }

    $ext = match($mime) {
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $filename  = 'avatar_' . $me['id'] . '_' . time() . '.' . $ext;
    $imagick   = null;

    // Resize to 150x150 if GD available
    $dest = $uploadDir . $filename;
    move_uploaded_file($file['tmp_name'], $dest);

    $avatarCol = $filename;
}

if ($avatarCol !== null) {
    $st = $pdo->prepare("UPDATE admins SET display_name=?, email=?, avatar=? WHERE id=?");
    $st->execute([$displayName ?: null, $email ?: null, $avatarCol, $me['id']]);
} else {
    $st = $pdo->prepare("UPDATE admins SET display_name=?, email=? WHERE id=?");
    $st->execute([$displayName ?: null, $email ?: null, $me['id']]);
}

refreshAdminSession();
$_SESSION['flash'] = ['type'=>'success','msg'=>'Profil mis à jour.'];
header('Location: ../profile.php'); exit;
