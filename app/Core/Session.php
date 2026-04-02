<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        $params = session_get_cookie_params();

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');

        session_start();
        self::$started = true;

        if (!isset($_SESSION['_init'])) {
            $_SESSION['_init'] = time();
            $_SESSION['_flash'] = [];
        }

        self::sweepFlash();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate(bool $destroyOld = true): void
    {
        self::start();
        session_regenerate_id($destroyOld);
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool)($params['secure'] ?? false),
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        self::$started = false;
    }

    public static function flash(string $key, mixed $value): void
    {
        self::start();

        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        $_SESSION['_flash'][$key] = [
            'v' => $value,
            'n' => 0,
        ];
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        self::start();

        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }

        $value = $_SESSION['_flash'][$key]['v'] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function allFlash(): array
    {
        self::start();

        $out = [];
        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            return $out;
        }

        foreach ($_SESSION['_flash'] as $k => $payload) {
            $out[$k] = $payload['v'] ?? null;
        }

        return $out;
    }

    public static function csrfToken(): string
    {
        self::start();

        $token = (string)($_SESSION['_csrf'] ?? '');
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION['_csrf'] = $token;
        }

        return $token;
    }

    public static function rotateCsrfToken(): string
    {
        self::start();
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf'] = $token;
        return $token;
    }

    private static function sweepFlash(): void
    {
        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
            return;
        }

        foreach ($_SESSION['_flash'] as $k => $payload) {
            $n = (int)($payload['n'] ?? 0);
            $n++;
            if ($n >= 2) {
                unset($_SESSION['_flash'][$k]);
                continue;
            }
            $_SESSION['_flash'][$k]['n'] = $n;
        }
    }
}
