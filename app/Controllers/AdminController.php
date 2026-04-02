<?php
declare(strict_types=1);

final class AdminController extends Controller
{
    private function requireAuth(): int
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        return (int)$_SESSION['user_id'];
    }

    private function getTableColumns(string $table): array
    {
        try {
            $rows = DB::query("DESCRIBE `$table`")->fetchAll();
            $cols = [];
            foreach ($rows as $r) {
                if (!empty($r['Field'])) {
                    $cols[] = (string)$r['Field'];
                }
            }
            return $cols;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function hasCol(array $cols, string $col): bool
    {
        return in_array($col, $cols, true);
    }

    private function flash(string $type, string $msg): void
    {
        if ($type === 'error') {
            $_SESSION['flash_error'] = $msg;
        } else {
            $_SESSION['flash_success'] = $msg;
        }
    }

    private function isAdminUser(int $userId): bool
    {
        $uCols = $this->getTableColumns('users');
        if (empty($uCols)) return false;

        // If schema doesn't support roles, allow access (dev-friendly).
        $roleCol = null;
        if ($this->hasCol($uCols, 'role')) $roleCol = 'role';
        elseif ($this->hasCol($uCols, 'user_role')) $roleCol = 'user_role';

        $isAdminCol = null;
        if ($this->hasCol($uCols, 'is_admin')) $isAdminCol = 'is_admin';
        elseif ($this->hasCol($uCols, 'admin')) $isAdminCol = 'admin';

        if ($roleCol === null && $isAdminCol === null) {
            return true;
        }

        $select = ['id'];
        if ($roleCol !== null) $select[] = "`$roleCol` AS role";
        if ($isAdminCol !== null) $select[] = "`$isAdminCol` AS is_admin";

        $row = DB::query(
            "SELECT " . implode(', ', $select) . " FROM `users` WHERE `id` = ? LIMIT 1",
            [$userId]
        )->fetch();

        if (!$row) return false;

        if ($isAdminCol !== null) {
            $v = $row['is_admin'] ?? 0;
            if ((string)$v === '1' || $v === 1 || $v === true) return true;
        }

        if ($roleCol !== null) {
            $role = strtolower((string)($row['role'] ?? ''));
            if (in_array($role, ['admin', 'superadmin'], true)) return true;
        }

        return false;
    }

    private function guardAdmin(): int
    {
        $userId = $this->requireAuth();
        if (!$this->isAdminUser($userId)) {
            $this->flash('error', 'Access denied.');
            $this->redirect('/dashboard');
        }
        return $userId;
    }

    public function index(): void
    {
        $this->guardAdmin();

        $this->view('admin/index', [
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function users(): void
    {
        $this->guardAdmin();

        $uCols = $this->getTableColumns('users');
        if (empty($uCols)) {
            $this->flash('error', 'Users table not found or not accessible.');
            $this->redirect('/admin');
        }

        $select = ['`id`'];
        foreach (['name','email','mobile','role','user_role','status','is_active','active','created_at'] as $c) {
            if ($this->hasCol($uCols, $c)) $select[] = "`$c`";
        }

        $users = DB::query(
            "SELECT " . implode(', ', $select) . " FROM `users` ORDER BY `id` DESC"
        )->fetchAll();

        $this->view('admin/users', [
            'users'   => $users,
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function toggleUser(): void
    {
        $this->guardAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('/admin/users');
        }

        $uCols = $this->getTableColumns('users');
        if (empty($uCols)) {
            $this->flash('error', 'Users table not found or not accessible.');
            $this->redirect('/admin/users');
        }

        // Determine which "active" column exists
        $activeCol = null; // is_active | active | status
        if ($this->hasCol($uCols, 'is_active')) $activeCol = 'is_active';
        elseif ($this->hasCol($uCols, 'active')) $activeCol = 'active';
        elseif ($this->hasCol($uCols, 'status')) $activeCol = 'status';

        if ($activeCol === null) {
            $this->flash('error', 'No status column found in users table.');
            $this->redirect('/admin/users');
        }

        try {
            $row = DB::query(
                "SELECT `id`, `$activeCol` AS v FROM `users` WHERE `id` = ? LIMIT 1",
                [$id]
            )->fetch();

            if (!$row) {
                $this->flash('error', 'User not found.');
                $this->redirect('/admin/users');
            }

            $cur = $row['v'] ?? null;

            if ($activeCol === 'status') {
                $new = ((string)$cur === 'active') ? 'inactive' : 'active';
            } else {
                $curBool = ((string)$cur === '1' || $cur === 1 || $cur === true);
                $new = $curBool ? 0 : 1;
            }

            DB::query(
                "UPDATE `users` SET `$activeCol` = ? WHERE `id` = ? LIMIT 1",
                [$new, $id]
            );

            $this->flash('success', 'User status updated.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Failed to update user status.');
        }

        $this->redirect('/admin/users');
    }

    public function logs(): void
    {
        $this->guardAdmin();

        $cols = $this->getTableColumns('audit_logs');
        if (empty($cols)) {
            $this->flash('error', 'audit_logs table not found or not accessible.');
            $this->redirect('/admin');
        }

        $select = ['`id`'];
        foreach (['user_id','type','action','event','message','details','data','ip_address','ip','created_at','created_on'] as $c) {
            if ($this->hasCol($cols, $c)) $select[] = "`$c`";
        }

        $logs = DB::query(
            "SELECT " . implode(', ', $select) . " FROM `audit_logs` ORDER BY `id` DESC LIMIT 200"
        )->fetchAll();

        $this->view('admin/logs', [
            'logs'    => $logs,
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function broadcastNotification(): void
    {
        $this->guardAdmin();

        $title = trim((string)($_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($title === '' || $message === '') {
            $this->flash('error', 'Please enter title and message.');
            $this->redirect('/admin');
        }

        $nCols = $this->getTableColumns('notifications');
        $uCols = $this->getTableColumns('users');

        if (empty($nCols) || empty($uCols)) {
            $this->flash('error', 'notifications/users table not found or not accessible.');
            $this->redirect('/admin');
        }

        // Detect notification columns
        $userCol = $this->hasCol($nCols, 'user_id') ? 'user_id' : null;

        $titleCol = null;
        foreach (['title','subject'] as $c) {
            if ($this->hasCol($nCols, $c)) { $titleCol = $c; break; }
        }

        $msgCol = null;
        foreach (['message','body','content','text','details'] as $c) {
            if ($this->hasCol($nCols, $c)) { $msgCol = $c; break; }
        }

        $readCol = null;
        foreach (['is_read','read','seen'] as $c) {
            if ($this->hasCol($nCols, $c)) { $readCol = $c; break; }
        }

        if ($userCol === null || $titleCol === null || $msgCol === null) {
            $this->flash('error', 'Notifications table does not have required columns (user_id/title/message).');
            $this->redirect('/admin');
        }

        // Fetch all user ids
        $users = DB::query("SELECT `id` FROM `users` ORDER BY `id` ASC")->fetchAll();
        if (empty($users)) {
            $this->flash('error', 'No users found.');
            $this->redirect('/admin');
        }

        // Insert one notification per user (simple loop; safe for small-to-medium userbase)
        $createdCount = 0;

        try {
            foreach ($users as $u) {
                $uid = (int)($u['id'] ?? 0);
                if ($uid <= 0) continue;

                $fields = ["`$userCol`", "`$titleCol`", "`$msgCol`"];
                $vals   = [$uid, $title, $message];

                if ($readCol !== null) {
                    $fields[] = "`$readCol`";
                    $vals[] = 0;
                }

                $placeholders = implode(',', array_fill(0, count($fields), '?'));

                DB::query(
                    "INSERT INTO `notifications` (" . implode(',', $fields) . ") VALUES ($placeholders)",
                    $vals
                );

                $createdCount++;
            }

            $this->flash('success', "Broadcast sent to {$createdCount} user(s).");
        } catch (\Throwable $e) {
            $this->flash('error', 'Failed to broadcast notification.');
        }

        $this->redirect('/admin');
    }
}
