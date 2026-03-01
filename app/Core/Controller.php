<?php

namespace App\Core;

/**
 * Classe de base pour tous les contrôleurs.
 * Fournit les helpers : render, redirect, json, input, isPost…
 */
abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    /* ── Rendu ───────────────────────────────────────────────────────────── */

    /**
     * Rend un template de vue avec des données.
     *
     * @param string $template  Chemin relatif depuis app/Views/ (sans .php)
     * @param array  $data      Variables injectées dans la vue
     */
    protected function render(string $template, array $data = []): void
    {
        $this->view->render($template, $data);
    }

    /* ── Réponses HTTP ───────────────────────────────────────────────────── */

    protected function redirect(string $url, int $code = 302): never
    {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    protected function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function abort(int $code = 403, string $message = ''): never
    {
        http_response_code($code);
        $this->view->render("errors/$code", ['message' => $message]);
        exit;
    }

    /* ── Requête ─────────────────────────────────────────────────────────── */

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    protected function isGet(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
    }

    /**
     * Récupère un champ POST nettoyé.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Récupère et type correctement un entier POST/GET.
     */
    protected function inputInt(string $key, int $default = 0): int
    {
        return (int)$this->input($key, $default);
    }

    /**
     * Récupère un champ POST/GET nettoyé (trim).
     */
    protected function inputStr(string $key, string $default = ''): string
    {
        return trim((string)$this->input($key, $default));
    }

    /* ── Flash / Session ─────────────────────────────────────────────────── */

    protected function flash(string $key, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$key] = $message;
    }

    protected function getFlash(string $key): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}
