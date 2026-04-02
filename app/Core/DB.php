<?php
declare(strict_types=1);

/**
 * DB
 * --
 * PDO Database connection handler
 * Usage:
 *   $db = DB::conn();
 *   DB::query("SELECT * FROM users WHERE id = ?", [$id]);
 */
final class DB
{
    private static ?PDO $pdo = null;

    /**
     * Get PDO connection (singleton)
     */
    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // Load database config
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['db'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$pdo = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                $options
            );
        } catch (PDOException $e) {
            // Never expose DB details in production
            if (Env::bool('APP_DEBUG', false)) {
                die('Database connection failed: ' . $e->getMessage());
            }
            die('Database connection error.');
        }

        return self::$pdo;
    }

    /**
     * Run a prepared query
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get last inserted ID
     */
    public static function lastInsertId(): string
    {
        return self::conn()->lastInsertId();
    }

    /**
     * Transaction helpers
     */
    public static function begin(): void
    {
        self::conn()->beginTransaction();
    }

    public static function commit(): void
    {
        self::conn()->commit();
    }

    public static function rollBack(): void
    {
        if (self::conn()->inTransaction()) {
            self::conn()->rollBack();
        }
    }
}
