<?php

namespace App\Core;

/**
 * Abstraction de la requête HTTP courante.
 * Evite d'accéder directement aux superglobales $_GET, $_POST, $_SERVER, $_FILES.
 */
class Request
{
    /* ── Méthode & URI ───────────────────────────────────────────────────── */

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    public function fullUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    /* ── Accès aux données ───────────────────────────────────────────────── */

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function inputStr(string $key, string $default = ''): string
    {
        return trim((string)$this->input($key, $default));
    }

    public function inputInt(string $key, int $default = 0): int
    {
        return (int)$this->input($key, $default);
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /* ── Fichiers ────────────────────────────────────────────────────────── */

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return !empty($_FILES[$key]['name']);
    }

    /* ── Headers & cookies ───────────────────────────────────────────────── */

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $_COOKIE[$key] ?? $default;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
