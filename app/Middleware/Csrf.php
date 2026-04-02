<?php
declare(strict_types=1);

/**
 * CSRF Middleware
 * ----------------
 * - Token generated per session
 * - Validates POST / PUT / DELETE requests
 * - Schema-agnostic
 * - No framework dependencies
 */

class Csrf
{
    /**
     * Get or generate CSRF token
     */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Validate CSRF token for unsafe requests
     */
    public static function validate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Only protect unsafe methods
        if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            return;
        }

        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (
            empty($token) ||
            empty($_SESSION['_csrf_token']) ||
            !hash_equals($_SESSION['_csrf_token'], (string)$token)
        ) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'CSRF token mismatch';
            exit;
        }
    }

    /**
     * Hidden input helper for forms
     */
    public static function input(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }
}
