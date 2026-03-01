<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Core\Auth;
use App\Core\Database;
use App\Models\AdminModel;

/**
 * Contrôleur d'authentification admin.
 */
class AuthController extends AdminController
{
    public function __construct()
    {
        // Ne pas appeler parent::__construct() ici car il call Auth::require()
        // et on n'est pas encore authentifié sur login/forgot
        $this->view = new \App\Core\View();
    }

    /* ── Login ───────────────────────────────────────────────────────────── */

    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect(defined('BASE_URL') ? BASE_URL . '/admin/dashboard' : '/admin/dashboard');
        }

        $this->view->render('admin/auth/login', [
            'error' => $this->getFlash('login_error'),
        ]);
    }

    public function loginPost(): void
    {
        $username = $this->inputStr('username');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $this->flash('login_error', 'Identifiants invalides.');
            $this->redirect(defined('BASE_URL') ? BASE_URL . '/admin/login' : '/admin/login');
        }

        $model = new AdminModel();
        $admin = $model->findByUsername($username);

        if (!$admin || !$admin['is_active'] || !password_verify($password, $admin['password'])) {
            $this->flash('login_error', 'Identifiants invalides.');
            $this->redirect(defined('BASE_URL') ? BASE_URL . '/admin/login' : '/admin/login');
        }

        // Vérification 2FA si activée
        if (!empty($admin['totp_secret'])) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['2fa_pending']  = true;
            $_SESSION['2fa_admin_id'] = $admin['id'];
            $this->redirect(defined('BASE_URL') ? BASE_URL . '/admin/2fa' : '/admin/2fa');
        }

        $model->updateLastLogin($admin['id']);
        Auth::login($admin);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/dashboard');
    }

    /* ── Logout ──────────────────────────────────────────────────────────── */

    public function logout(): void
    {
        Auth::logout();
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/login');
    }

    /* ── Mot de passe oublié ─────────────────────────────────────────────── */

    public function forgotForm(): void
    {
        $this->view->render('admin/auth/forgot-password', [
            'success' => $this->getFlash('reset_success'),
            'error'   => $this->getFlash('reset_error'),
        ]);
    }

    public function forgotPost(): void
    {
        $email = $this->inputStr('email');
        $model = new AdminModel();
        $admin = $model->findByEmail($email);

        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $model->createResetToken($admin['id'], $token);

            // Envoi email (utilise le MailSender existant)
            $resetUrl = (defined('BASE_URL') ? BASE_URL : '') . '/admin/reset-password?token=' . $token;
            try {
                $mailer = new \MailSender();
                $mailer->send(
                    $admin['email'],
                    'Réinitialisation mot de passe',
                    "Bonjour,<br><br>Cliquez ici pour réinitialiser votre mot de passe :<br><a href=\"$resetUrl\">$resetUrl</a><br><br>Ce lien expire dans 1 heure."
                );
            } catch (\Throwable) {}
        }

        $this->flash('reset_success', 'Si cet email existe, un lien de réinitialisation a été envoyé.');
        $this->redirect(defined('BASE_URL') ? BASE_URL . '/admin/forgot-password' : '/admin/forgot-password');
    }

    /* ── Reset mot de passe ──────────────────────────────────────────────── */

    public function resetForm(): void
    {
        $token = $_GET['token'] ?? '';
        $model = new AdminModel();
        $row   = $model->findByResetToken($token);

        if (!$row) {
            $this->view->render('admin/auth/reset-password', [
                'invalid' => true, 'token' => $token,
            ]);
            return;
        }

        $this->view->render('admin/auth/reset-password', [
            'invalid' => false,
            'token'   => $token,
            'error'   => $this->getFlash('reset_pw_error'),
        ]);
    }

    public function resetPost(): void
    {
        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm']  ?? '';

        $model = new AdminModel();
        $row   = $model->findByResetToken($token);

        if (!$row || strlen($password) < 8 || $password !== $confirm) {
            $this->flash('reset_pw_error', 'Lien invalide ou mots de passe non correspondants.');
            $base = defined('BASE_URL') ? BASE_URL : '';
            $this->redirect($base . '/admin/reset-password?token=' . urlencode($token));
        }

        $model->updatePassword($row['admin_id'], password_hash($password, PASSWORD_DEFAULT));
        $model->deleteResetToken($row['admin_id']);

        $this->flash('login_error', 'Mot de passe mis à jour. Connectez-vous.');
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/login');
    }
}
