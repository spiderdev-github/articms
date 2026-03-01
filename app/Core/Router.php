<?php

namespace App\Core;

/**
 * Router — associe des patterns d'URL à des paires [Controller, action].
 *
 * Usage :
 *   $router->get('/',        [HomeController::class, 'index']);
 *   $router->post('/contact', [ContactController::class, 'store']);
 *   $router->dispatch();
 */
class Router
{
    private array $routes = [];

    /* ── Enregistrement ──────────────────────────────────────────────────── */

    public function get(string $pattern, array $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    public function any(string $pattern, array $handler): self
    {
        $this->addRoute('GET',  $pattern, $handler);
        $this->addRoute('POST', $pattern, $handler);
        return $this;
    }

    private function addRoute(string $method, string $pattern, array $handler): self
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
        return $this;
    }

    /* ── Dispatch ────────────────────────────────────────────────────────── */

    /**
     * Analyse la requête courante et appelle le contrôleur correspondant.
     * Les segments dynamiques (:param) sont transmis en paramètres.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = '/' . trim($uri, '/');

        // Strip base path (quand le projet est dans un sous-dossier)
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            if ($this->match($route['pattern'], $uri, $params)) {
                [$class, $action] = $route['handler'];

                if (!class_exists($class)) {
                    throw new \RuntimeException("Contrôleur introuvable : $class");
                }

                $controller = new $class();
                if (!method_exists($controller, $action)) {
                    throw new \RuntimeException("Action introuvable : $class::$action");
                }

                $controller->$action(...$params);
                return;
            }
        }

        // Aucune route correspondante
        http_response_code(404);
        (new View())->render('errors/404');
    }

    /* ── Matching ────────────────────────────────────────────────────────── */

    /**
     * Transforme un pattern «/admin/contacts/:id» en regex et extrait les paramètres.
     */
    private function match(string $pattern, string $uri, array &$params): bool
    {
        $regex = preg_replace_callback('/:([a-zA-Z_]+)/', fn($m) => '(?P<' . $m[1] . '>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return false;
        }

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[] = $value;
            }
        }
        return true;
    }
}
