<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/DB.php';

final class SettingsController extends Controller
{
    /**
     * Settings page
     */
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];

        $user = DB::query(
            "SELECT id, name, ui_mode, role FROM users WHERE id = ? LIMIT 1",
            [$userId]
        )->fetch();

        if (!$user) {
            session_unset();
            session_destroy();
            session_start();
            $this->redirect('/login');
        }

        // Ensure settings row exists
        $settings = DB::query(
            "SELECT * FROM user_settings WHERE user_id = ? LIMIT 1",
            [$userId]
        )->fetch();

        if (!$settings) {
            DB::query(
                "INSERT INTO user_settings
                 (user_id, bank_connected, allow_data_usage, notify_in_app, notify_email, notify_sms, currency, timezone, dark_mode)
                 VALUES (?, 0, 0, 1, 0, 0, 'INR', 'Asia/Kolkata', 0)",
                [$userId]
            );

            $settings = DB::query(
                "SELECT * FROM user_settings WHERE user_id = ? LIMIT 1",
                [$userId]
            )->fetch();
        }

        // Session: UI mode (existing)
        $_SESSION['ui_mode'] = $user['ui_mode'] ?? ($_SESSION['ui_mode'] ?? 'simple');

        // ✅ Session: Dark mode (FIX)
        // Layout reads $_SESSION['dark_mode'], so we hydrate it from DB here.
        if (is_array($settings) && array_key_exists('dark_mode', $settings)) {
            $_SESSION['dark_mode'] = ((int)$settings['dark_mode'] === 1) ? 1 : 0;
        }

        $this->view('settings/index', [
            'appName'  => Env::get('APP_NAME', 'Zoro'),
            'user'     => $user,
            'mode'     => $_SESSION['ui_mode'] ?? 'simple',
            'settings' => $settings ?: [],
        ], 'app');
    }

    /**
     * Save settings (AJAX)
     * POST /settings/save
     */
    public function save(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = (int)$_SESSION['user_id'];

        // Normalize inputs
        $allowDataUsage = isset($_POST['allow_data_usage']) && $_POST['allow_data_usage'] === '1' ? 1 : 0;
        $darkMode       = isset($_POST['dark_mode']) && $_POST['dark_mode'] === '1' ? 1 : 0;

        // Ensure row exists
        $exists = DB::query(
            "SELECT id FROM user_settings WHERE user_id = ? LIMIT 1",
            [$userId]
        )->fetch();

        if ($exists) {
            DB::query(
                "UPDATE user_settings
                 SET allow_data_usage = ?, dark_mode = ?
                 WHERE user_id = ?",
                [$allowDataUsage, $darkMode, $userId]
            );
        } else {
            DB::query(
                "INSERT INTO user_settings (user_id, allow_data_usage, dark_mode)
                 VALUES (?, ?, ?)",
                [$userId, $allowDataUsage, $darkMode]
            );
        }

        // ✅ Update session immediately so layout can apply dark mode right away
        $_SESSION['dark_mode'] = $darkMode;

        // If you use session-throttled hydration elsewhere, this ensures next request doesn't keep stale values
        $_SESSION['__settings_sync_ts'] = 0;

        $this->json([
            'ok' => true,
            'data' => [
                'allow_data_usage' => $allowDataUsage,
                'dark_mode'        => $darkMode,
            ],
        ]);
    }

    /**
     * One-click Simple/Advanced mode toggle
     */
    public function toggleMode(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = (int)$_SESSION['user_id'];

        $current = $_SESSION['ui_mode'] ?? 'simple';
        $newMode = ($current === 'advanced') ? 'simple' : 'advanced';

        DB::query("UPDATE users SET ui_mode = ? WHERE id = ?", [$newMode, $userId]);

        $_SESSION['ui_mode'] = $newMode;

        $this->json(['ok' => true, 'mode' => $newMode]);
    }
}
