<?php

namespace App\Core;

/**
 * Service d'authentification admin.
 * Remplace les fonctions globales de admin/auth.php.
 * Peut être utilisé dans les contrôleurs via Auth::check(), Auth::user(), etc.
 */
class Auth
{
    /* ── Rôles & permissions ─────────────────────────────────────────────── */

    public const ROLES = ['super_admin', 'admin', 'editor', 'author'];

    public const ROLES_LABELS = [
        'super_admin' => 'Super Admin',
        'admin'       => 'Administrateur',
        'editor'      => 'Éditeur',
        'author'      => 'Auteur',
    ];

    public const ROLES_COLORS = [
        'super_admin' => 'danger',
        'admin'       => 'warning',
        'editor'      => 'info',
        'author'      => 'secondary',
    ];

    public const ROLE_PERMISSIONS = [
        'super_admin' => ['dashboard','contacts','crm','realisations','galleries','cms','media','menu','themes','forms','settings','users'],
        'admin'       => ['dashboard','contacts','crm','realisations','galleries','cms','media','menu','themes','forms','settings'],
        'editor'      => ['dashboard','contacts','crm','realisations','galleries','cms','media','forms'],
        'author'      => ['dashboard','realisations','galleries','cms','media'],
    ];

    /* ── Session ─────────────────────────────────────────────────────────── */

    private static function boot(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool
    {
        self::boot();
        return !empty($_SESSION['admin_id']);
    }

    /**
     * Retourne les données de l'admin connecté (cache session).
     */
    public static function user(): ?array
    {
        self::boot();

        if (!self::check()) {
            return null;
        }

        if (!empty($_SESSION['admin_data'])) {
            return $_SESSION['admin_data'];
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT id, username, email, display_name, avatar, role, is_active
             FROM admins WHERE id = ? AND is_active = 1"
        );
        $stmt->execute([$_SESSION['admin_id']]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            self::logout();
            return null;
        }

        $_SESSION['admin_data'] = $row;
        return $row;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): string
    {
        return self::user()['role'] ?? 'author';
    }

    /**
     * Vérifie si l'admin a une permission donnée.
     */
    public static function can(string $permission): bool
    {
        $role  = self::role();
        $perms = self::ROLE_PERMISSIONS[$role] ?? [];
        return in_array($permission, $perms, true);
    }

    /**
     * Redirige vers login si non authentifié.
     */
    public static function require(): void
    {
        self::boot();

        if (!self::check()) {
            $loginUrl = defined('BASE_URL')
                ? BASE_URL . '/admin/login'
                : '/admin/login';
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    /**
     * Redirige avec 403 si permission manquante.
     */
    public static function requirePermission(string $permission): void
    {
        self::require();

        if (!self::can($permission)) {
            http_response_code(403);
            (new View())->render('errors/403', [
                'message' => "Vous n'avez pas la permission d'accéder à cette section."
            ]);
            exit;
        }
    }

    /* ── Login / Logout ──────────────────────────────────────────────────── */

    /**
     * Connecte un admin (après vérification du mot de passe côté contrôleur).
     */
    public static function login(array $adminRow): void
    {
        self::boot();
        session_regenerate_id(true);

        $_SESSION['admin_id']   = $adminRow['id'];
        $_SESSION['admin_data'] = $adminRow;

        // Nettoyage 2FA si besoin
        unset($_SESSION['2fa_pending'], $_SESSION['2fa_admin_id']);
    }

    public static function logout(): void
    {
        self::boot();
        session_unset();
        session_destroy();
    }

    /* ── CSRF ────────────────────────────────────────────────────────────── */

    public static function csrfToken(): string
    {
        self::boot();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        self::boot();
        $expected = $_SESSION['csrf_token'] ?? '';
        return $expected !== '' && hash_equals($expected, $token);
    }
}
