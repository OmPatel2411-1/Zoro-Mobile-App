<?php
declare(strict_types=1);

class NotificationsController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int) $_SESSION['user_id'];

        $notifications = DB::query(
            "SELECT *
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC",
            [$userId]
        )->fetchAll();

        $this->view('notifications/index', [
            'notifications' => $notifications
        ]);
    }

    public function markRead(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int) $_SESSION['user_id'];
        $id     = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('/notifications');
        }

        DB::query(
            "UPDATE notifications
             SET is_read = 1
             WHERE id = ? AND user_id = ?
             LIMIT 1",
            [$id, $userId]
        );

        $this->redirect('/notifications');
    }
}
