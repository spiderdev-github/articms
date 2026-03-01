<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

/* ═══════════════════════════════════════════════════════════════════════════
   RÔLES & PERMISSIONS
   ─────────────────────────────────────────────────────────────────────────
   Rôles disponibles (du plus au moins privilégié) :
     super_admin  — accès total, gestion utilisateurs
     admin        — accès total sauf gestion utilisateurs
     editor       — contacts, réalisations, galeries, CMS, médias, formulaires
     author       — réalisations, galeries, CMS, médias uniquement
═══════════════════════════════════════════════════════════════════════════ */

const ROLES = ['super_admin', 'admin', 'editor', 'author'];

const ROLES_LABELS = [
    'super_admin' => 'Super Admin',
    'admin'       => 'Administrateur',
    'editor'      => 'Éditeur',
    'author'      => 'Auteur',
];

const ROLES_COLORS = [
    'super_admin' => 'danger',
    'admin'       => 'warning',
    'editor'      => 'info',
    'author'      => 'secondary',
];

/* Permissions par rôle */
const ROLE_PERMISSIONS = [
    'super_admin' => ['dashboard','contacts','crm','realisations','galleries','cms','media','menu','themes','forms','settings','users'],
    'admin'       => ['dashboard','contacts','crm','realisations','galleries','cms','media','menu','themes','forms','settings'],
    'editor'      => ['dashboard','contacts','crm','realisations','galleries','cms','media','forms'],
    'author'      => ['dashboard','realisations','galleries','cms','media'],
];

/* ── Session helpers ──────────────────────────────────────────────────────── */
function isAdminLogged(): bool {
    return isset($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLogged()) {
        header('Location: ' . (basename($_SERVER['PHP_SELF']) !== 'index.php' ? 'index.php' : ''));
        exit;
    }
}

/** Return full current admin row (cached in session). */
function getCurrentAdmin(): ?array {
    if (!isAdminLogged()) return null;
    if (!empty($_SESSION['admin_data'])) return $_SESSION['admin_data'];

    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, avatar, role, is_active FROM admins WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { session_destroy(); header('Location: index.php'); exit; }
    $_SESSION['admin_data'] = $row;
    return $row;
}

function getAdminRole(): string {
    $a = getCurrentAdmin();
    return $a['role'] ?? 'author';
}

/** Check if current user has a specific permission. */
function can(string $perm): bool {
    $role  = getAdminRole();
    $perms = ROLE_PERMISSIONS[$role] ?? [];
    return in_array($perm, $perms, true);
}

/** Redirect with 403 if permission missing. */
function requirePermission(string $perm): void {
    requireAdmin();
    if (!can($perm)) {
        http_response_code(403);
        // Try to show a nice error inside admin layout if header was already included
        if (!headers_sent()) {
            // Simple redirect to dashboard with error flash
            $_SESSION['flash'] = ['type'=>'error','msg'=>'Accès refusé. Vous n\'avez pas la permission pour cette section.'];
            header('Location: dashboard.php');
        } else {
            echo '<div class="alert alert-danger m-3"><i class="fas fa-lock mr-2"></i>Accès refusé.</div>';
        }
        exit;
    }
}

/** Require a specific role (or higher). */
function requireRole(string $minRole): void {
    requireAdmin();
    $order    = array_flip(ROLES); // ['super_admin'=>0,'admin'=>1,'editor'=>2,'author'=>3]
    $userRole = getAdminRole();
    if (($order[$userRole] ?? 99) > ($order[$minRole] ?? 99)) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Accès refusé.'];
        header('Location: dashboard.php');
        exit;
    }
}

/* ── CSRF ─────────────────────────────────────────────────────────────────── */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Validate CSRF token and die/redirect on failure. */
function validateCsrf(string $token, string $redirect = '../dashboard.php'): void {
    if (!verifyCsrfToken($token)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Token CSRF invalide. Veuillez réessayer.'];
        header('Location: ' . $redirect);
        exit;
    }
}

/* ── Login ────────────────────────────────────────────────────────────────── */
/**
 * Returns true on success, false on invalid credentials,
 * or the string '2fa' when 2FA verification is required.
 */
function loginAdmin(string $username, string $password): bool|string {
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT id, password_hash, role, is_active, totp_enabled FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !$admin['is_active']) return false;
    if (!password_verify($password, $admin['password_hash'])) return false;

    // 2FA required — check trusted device before asking code
    if (!empty($admin['totp_enabled'])) {
        if (checkTrustedDevice($admin['id'])) {
            // Trusted device: finalize login without 2FA prompt
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            unset($_SESSION['admin_data']);
            getCsrfToken();
            $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            return true;
        }
        session_regenerate_id(true);
        $_SESSION['2fa_pending'] = ['id' => $admin['id']];
        return '2fa';
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    unset($_SESSION['admin_data']); // force refresh
    getCsrfToken();

    $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);

    return true;
}

/** Invalidate cached admin data (call after profile update). */
function refreshAdminSession(): void {
    unset($_SESSION['admin_data']);
}

/* ── Trusted devices ──────────────────────────────────────────────────────── */

/** Check if the current browser has a valid trusted-device cookie for $adminId. */
function checkTrustedDevice(int $adminId): bool {
    $cookieVal = $_COOKIE['jp_trusted_device'] ?? '';
    if (!$cookieVal || !str_contains($cookieVal, ':')) return false;
    [, $token] = explode(':', $cookieVal, 2);
    if (!$token) return false;
    $hash = hash('sha256', $token);
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT id FROM admin_trusted_devices WHERE admin_id = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$adminId, $hash]);
    return (bool)$stmt->fetch();
}