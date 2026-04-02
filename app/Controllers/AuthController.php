<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/DB.php';

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        // If already logged in, go dashboard
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login', [
            'appName' => Env::get('APP_NAME', 'Zoro'),
            'error'   => $_SESSION['flash_error'] ?? null,
        ], 'auth');

        unset($_SESSION['flash_error']);
    }

    public function login(): void
    {
        // Basic input
        $emailOrMobile = trim((string)($_POST['login'] ?? ''));
        $password      = (string)($_POST['password'] ?? '');

        if ($emailOrMobile === '' || $password === '') {
            $_SESSION['flash_error'] = 'Please enter login and password.';
            $this->redirect('/login');
        }

        // Find user by email OR mobile
        $user = DB::query(
            "SELECT * FROM users WHERE (email = ? OR mobile = ?) AND is_active = 1 LIMIT 1",
            [$emailOrMobile, $emailOrMobile]
        )->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = 'Invalid credentials.';
            $this->redirect('/login');
        }

        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid credentials.';
            $this->redirect('/login');
        }

        // Login success
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['ui_mode'] = $user['ui_mode'] ?? 'simple';
        $_SESSION['role']    = $user['role'] ?? 'user';

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();

        // start a fresh session to avoid warnings
        session_start();

        $this->redirect('/login');
    }
}
