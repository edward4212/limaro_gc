<?php

namespace App\Core;

/**
 * Router HTTP — resuelve URL a Controlador@metodo con middleware.
 * Soporta parámetros de ruta {param}.
 */
class Router
{
    /** @var array Rutas registradas */
    private array $routes = [];

    /** @var array Middlewares registrados globalmente */
    private array $middlewares = [];

    /**
     * Registrar ruta GET.
     */
    public function get(string $uri, string $action, array $middlewares = []): void
    {
        $this->add('GET', $uri, $action, $middlewares);
    }

    /**
     * Registrar ruta POST.
     */
    public function post(string $uri, string $action, array $middlewares = []): void
    {
        $this->add('POST', $uri, $action, $middlewares);
    }

    /**
     * Registrar ruta para cualquier método.
     */
    public function any(string $uri, string $action, array $middlewares = []): void
    {
        $this->add('ANY', $uri, $action, $middlewares);
    }

    /**
     * Agregar ruta al registro.
     */
    private function add(
        string $method,
        string $uri,
        string $action,
        array  $middlewares
    ): void {
        $this->routes[] = [
            'method'      => $method,
            'uri'         => $uri,
            'action'      => $action,
            'middlewares' => $middlewares,
            'pattern'     => $this->buildPattern($uri),
        ];
    }

    /**
     * Convertir URI con parámetros {param} a regex.
     */
    private function buildPattern(string $uri): string
    {
        $pattern = preg_replace('/\{[a-z_]+\}/', '([^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /**
     * Resolver la ruta actual y despachar.
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Quitar prefijo de APP_URL si aplica
        $base = parse_url(APP_URL, PHP_URL_PATH) ?: '';
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }

        // Quitar /public/ si quedó por rewrite interno
        if (str_starts_with($uri, '/public/')) {
            $uri = substr($uri, strlen('/public')) ?: '/';
        } elseif ($uri === '/public') {
            $uri = '/';
        }

        // Normalizar
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            // Verificar método
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }

            // Verificar URI con regex
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            // Extraer parámetros de ruta
            $params = array_slice($matches, 1);

            // Ejecutar middlewares
            foreach ($route['middlewares'] as $mw) {
                $this->runMiddleware($mw);
            }

            // Despachar controlador
            $this->runAction($route['action'], $params);
            return;
        }

        // 404
        http_response_code(404);
        $this->render404();
    }

    /**
     * Ejecutar middleware por nombre.
     */
    private function runMiddleware(string $name): void
    {
        $map = [
            'auth'    => \App\Middlewares\AuthMiddleware::class,
            'permiso' => \App\Middlewares\PermisoMiddleware::class,
        ];

        if (isset($map[$name])) {
            $instance = new $map[$name]();
            $instance->handle();
        }
    }

    /**
     * Instanciar controlador y llamar método con parámetros.
     */
    private function runAction(string $action, array $params): void
    {
        [$controllerName, $methodName] = explode('@', $action, 2);

        $fqn = "\\App\\Controllers\\$controllerName";

        if (!class_exists($fqn)) {
            Response::abort(500, "Controlador $controllerName no encontrado.");
        }

        $controller = new $fqn();

        if (!method_exists($controller, $methodName)) {
            Response::abort(500, "Método $methodName no existe en $controllerName.");
        }

        $controller->{$methodName}(...$params);
    }

    /**
     * Página 404.
     */
    private function render404(): void
    {
        if (file_exists(APP_ROOT . '/app/views/errors/404.php')) {
            require APP_ROOT . '/app/views/errors/404.php';
        } else {
            echo '<h1>404 — Página no encontrada</h1>';
            echo '<p><a href="' . APP_URL . '/inicio">Volver al inicio</a></p>';
        }
    }
}
