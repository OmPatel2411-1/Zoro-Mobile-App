<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/DB.php';

final class OnboardingController extends Controller
{
    public function index(): void
    {
        // Auth check
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];

        $user = DB::query(
            "SELECT id, name, email, mobile, ui_mode FROM users WHERE id = ? LIMIT 1",
            [$userId]
        )->fetch();

        if (!$user) {
            session_unset();
            session_destroy();
            session_start();
            $this->redirect('/login');
        }

        $this->view('onboarding/index', [
            'appName' => Env::get('APP_NAME', 'Zoro'),
            'user'    => $user,
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ], 'app');

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function save(): void
    {
        // Auth check
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];

        $name   = trim((string)($_POST['name'] ?? ''));
        $uiMode = trim((string)($_POST['ui_mode'] ?? ''));

        if ($name === '') {
            $_SESSION['flash_error'] = 'Please enter your name.';
            $this->redirect('/onboarding');
        }

        if (!in_array($uiMode, ['simple', 'advanced'], true)) {
            $uiMode = 'simple';
        }

        DB::query(
            "UPDATE users SET name = ?, ui_mode = ? WHERE id = ? LIMIT 1",
            [$name, $uiMode, $userId]
        );

        // Keep session in sync
        $_SESSION['ui_mode'] = $uiMode;

        $_SESSION['flash_success'] = 'Onboarding saved.';
        $this->redirect('/dashboard');
    }
}
