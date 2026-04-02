<?php
declare(strict_types=1);

/**
 * Router
 * ------
 * Simple Router that maps URL + METHOD to Controller@method
 * Supports subfolder installs like /Zoro/public
 *
 * Usage in routes/web.php:
 *   $router->get('/login', 'AuthController@loginForm');
 *   $router->post('/login', 'AuthController@login');
 *
 * Dispatch from public/index.php:
 *   $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
 */
final class Router
{
    /** @var array<string, array<string, string>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $requestUri, string $method): void
    {
        $rawPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?? '/');
        $rawPath = $this->normalize($rawPath);

        // IMPORTANT: strip base path like /Zoro/public (auto-detected)
        $basePath = $this->detectBasePath(); // e.g. "/Zoro/public"
        $path = $this->stripBasePath($rawPath, $basePath); // becomes "/login"

        // If user opens /index.php explicitly, treat it as "/"
        if ($path === '/index.php') {
            $path = '/';
        }

        $method = strtoupper($method);

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            $this->abort(404, "Route not found: {$method} {$rawPath}");
            return;
        }

        // handler format: Controller@method
        if (!str_contains($handler, '@')) {
            $this->abort(500, "Invalid route handler format for {$method} {$path}");
            return;
        }

        [$controllerName, $action] = explode('@', $handler, 2);

        $controllerFile = __DIR__ . '/../Controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            $this->abort(500, "Controller file missing: {$controllerName}.php");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->abort(500, "Controller class not found: {$controllerName}");
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            $this->abort(500, "Controller method not found: {$controllerName}@{$action}");
            return;
        }

        $controller->{$action}();
    }

    private function normalize(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '/';

        $path = explode('?', $path, 2)[0];

        if ($path[0] !== '/') $path = '/' . $path;

        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * Detect base path from SCRIPT_NAME.
     * Example:
     *   SCRIPT_NAME = /Zoro/public/index.php
     *   base path   = /Zoro/public
     */
    private function detectBasePath(): string
    {
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName === '') return '';

        $dir = str_replace('\\', '/', dirname($scriptName));
        $dir = rtrim($dir, '/');

        // dirname("/index.php") gives "/", treat that as no base path
        if ($dir === '' || $dir === '/') return '';

        return $dir;
    }

    private function stripBasePath(string $path, string $basePath): string
    {
        if ($basePath === '') return $path;

        // If path starts with basePath, remove it
        if (str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
            if ($path === '') $path = '/';
        }

        // ensure normalized
        return $this->normalize($path);
    }

    private function abort(int $statusCode, string $message = ''): void
    {
        http_response_code($statusCode);

        $safeMsg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo "<!doctype html><html><head><meta charset='utf-8'><title>{$statusCode}</title>
              <meta name='viewport' content='width=device-width, initial-scale=1'></head>
              <body style='font-family:Arial; padding:24px; background:#fff; color:#111;'>
              <h1 style='margin:0 0 8px'>{$statusCode}</h1>
              <p style='margin:0; color:#444'>{$safeMsg}</p>
              </body></html>";
        exit;
    }
}
