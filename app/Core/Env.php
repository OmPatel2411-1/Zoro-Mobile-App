<?php
declare(strict_types=1);

/**
 * Env
 * ----
 * .env file loader for the application
 * Usage:
 *   Env::load(__DIR__ . '/../../.env');
 *   Env::get('DB_HOST');
 */
final class Env
{
    private static bool $loaded = false;

    /**
     * Load .env file
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignore comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Must contain =
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (
                ($value[0] ?? '') === '"' && str_ends_with($value, '"') ||
                ($value[0] ?? '') === "'" && str_ends_with($value, "'")
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }

        self::$loaded = true;
    }

    /**
     * Get env value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        return ($value === false || $value === null || $value === '')
            ? $default
            : $value;
    }

    /**
     * Get boolean env value
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = strtolower((string) self::get($key, $default ? 'true' : 'false'));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}
