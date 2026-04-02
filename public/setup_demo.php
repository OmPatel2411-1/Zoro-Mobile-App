<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/Core/Env.php';
Env::load(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/Core/DB.php';

header('Content-Type: text/plain; charset=utf-8');

$loginEmail  = 'demo@zoro.com';
$loginMobile = '9999999999';
$password    = '123456';

try {
    $pdo = DB::conn();

    // Ensure table exists (basic check)
    $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$check) {
        echo "ERROR: 'users' table not found. Run DB SQL schema first.\n";
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR mobile = ? LIMIT 1");
    $stmt->execute([$loginEmail, $loginMobile]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing demo user password + activate
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, is_active = 1 WHERE id = ?");
        $upd->execute([$hash, (int)$existing['id']]);

        echo "OK: Demo user already existed. Password RESET to 123456.\n";
        echo "Login: {$loginEmail} OR {$loginMobile}\n";
        exit;
    }

    // Insert new demo user
    $ins = $pdo->prepare("
        INSERT INTO users (name, email, mobile, password_hash, ui_mode, role, is_active)
        VALUES (?, ?, ?, ?, 'simple', 'user', 1)
    ");
    $ins->execute(['Demo User', $loginEmail, $loginMobile, $hash]);

    echo "OK: Demo user CREATED.\n";
    echo "Login: {$loginEmail} OR {$loginMobile}\n";
    echo "Password: 123456\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
