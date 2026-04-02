<?php
declare(strict_types=1);

session_start();

/**
 * Bootstrapping
 */
require_once __DIR__ . '/../app/Core/Env.php';
Env::load(__DIR__ . '/../.env');

// Error display (only for local/dev)
if (Env::bool('APP_DEBUG', true)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Router.php';

/**
 * ✅ CSRF protection (global)
 * - Validates unsafe requests (POST/PUT/DELETE)
 * - Accepts token via hidden input: _csrf
 * - Or via header: X-CSRF-TOKEN
 */
require_once __DIR__ . '/../app/Middleware/Csrf.php';
Csrf::validate();

/**
 * Session hydration (global user preferences)
 * - Keeps UI consistent across all pages.
 * - Safe for schema differences (wrap in try/catch).
 * - Throttled to avoid querying DB on every request.
 */
if (!empty($_SESSION['user_id'])) {
    $now = time();
    $lastSync = (int)($_SESSION['__settings_sync_ts'] ?? 0);

    // refresh at most once per 60 seconds
    if ($lastSync === 0 || ($now - $lastSync) > 60) {
        try {
            $userId = (int)$_SESSION['user_id'];

            // Try to read settings (may vary by schema)
            $row = DB::query(
                "SELECT * FROM user_settings WHERE user_id = ? LIMIT 1",
                [$userId]
            )->fetch();

            if (is_array($row)) {
                // Dark mode (if column exists)
                if (array_key_exists('dark_mode', $row)) {
                    $_SESSION['dark_mode'] = ((int)$row['dark_mode'] === 1) ? 1 : 0;
                }

                // If you ever want more global settings later, add here safely with array_key_exists(...)
            }

            $_SESSION['__settings_sync_ts'] = $now;
        } catch (PDOException $e) {
            // Schema/table may differ — do not break the app
            $_SESSION['__settings_sync_ts'] = $now;
        }
    }
}

/**
 * Create router + load routes
 */
$router = new Router();
require_once __DIR__ . '/../routes/web.php';

/**
 * Dispatch
 */
$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');
