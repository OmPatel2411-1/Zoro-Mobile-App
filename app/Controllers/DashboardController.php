<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Notification.php';

final class DashboardController extends Controller
{
    public function index(): void
    {
        // Auth guard (do NOT assume middleware exists)
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        $userId = (int)$_SESSION['user_id'];

        /**
         * ✅ Run notification automation
         * - Safe to call repeatedly
         * - Internally de-duplicated
         * - Schema-aware
         */
        try {
            Notification::runAutomationForUser($userId);
        } catch (\Throwable $e) {
            // Never break dashboard for automation
        }

        /**
         * Render dashboard
         * (No schema assumptions here)
         */
        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user'  => $_SESSION['user'] ?? [],
            'mode'  => $_SESSION['ui_mode'] ?? 'simple',
        ]);
    }
}
