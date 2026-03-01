<?php

namespace App\Core;

/**
 * Contrôleur de base pour toutes les pages admin.
 * Ajoute : vérification session, helpers de permission, layout admin.
 */
abstract class AdminController extends Controller
{
    protected string $defaultLayout = 'admin';

    public function __construct()
    {
        parent::__construct();

        // Démarre la session et vérifie l'authentification
        Auth::require();
    }

    /* ── Rendu avec layout admin ─────────────────────────────────────────── */

    protected function render(string $template, array $data = []): void
    {
        $data['layout']       = $data['layout'] ?? $this->defaultLayout;
        $data['currentAdmin'] = Auth::user();
        $data['csrf']         = Auth::csrfToken();
        parent::render($template, $data);
    }

    /* ── Permissions ─────────────────────────────────────────────────────── */

    protected function requirePermission(string $permission): void
    {
        Auth::requirePermission($permission);
    }

    protected function can(string $permission): bool
    {
        return Auth::can($permission);
    }

    /* ── CSRF ────────────────────────────────────────────────────────────── */

    protected function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Auth::verifyCsrf($token)) {
            $this->abort(419, 'Token CSRF invalide.');
        }
    }
}
