<?php
declare(strict_types=1);

/**
 * Controller (Base)
 * -----------------
 * Provides:
 * - view rendering
 * - redirect helper (supports subfolder installs like /Zoro/public)
 * - json response helper
 * - baseUrl helper
 */
class Controller
{
    /**
     * Render a view inside a layout.
     *
     * @param string $view   Example: "auth/login" => app/Views/auth/login.php
     * @param array  $data   Variables passed to view
     * @param string $layout Example: "auth" => app/Views/layouts/auth.php
     */
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: " . htmlspecialchars($viewFile);
            exit;
        }

        if (!file_exists($layoutFile)) {
            http_response_code(500);
            echo "Layout not found: " . htmlspecialchars($layoutFile);
            exit;
        }

        // Make $data variables available in view
        extract($data, EXTR_SKIP);

        // Make baseUrl available in every view/layout
        $baseUrl = $this->baseUrl();

        // Capture view output
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout with $content
        require $layoutFile;
    }

    /**
     * Redirect helper (prepends base path)
     * Example:
     *   redirect('/login') => /Zoro/public/login
     */
    protected function redirect(string $path): void
    {
        header('Location: ' . $this->url($path));
        exit;
    }

    /**
     * Build an app URL with base path (supports subfolder install)
     * Example:
     *   url('/dashboard') => /Zoro/public/dashboard
     */
    protected function url(string $path): string
    {
        $path = trim($path);
        if ($path === '') $path = '/';
        if ($path[0] !== '/') $path = '/' . $path;

        $base = $this->baseUrl();
        return $base . $path;
    }

    /**
     * Detect base path from SCRIPT_NAME
     * Example:
     *   SCRIPT_NAME = /Zoro/public/index.php
     *   baseUrl     = /Zoro/public
     */
    protected function baseUrl(): string
    {
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName === '') return '';

        $dir = str_replace('\\', '/', dirname($scriptName));
        $dir = rtrim($dir, '/');

        if ($dir === '' || $dir === '/') return '';
        return $dir;
    }

    /**
     * JSON response helper
     */
    protected function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
