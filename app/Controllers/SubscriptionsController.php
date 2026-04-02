<?php
declare(strict_types=1);

final class SubscriptionsController extends Controller
{
    /**
     * Get columns for a table (schema-aware).
     */
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

    private function setFlash(string $type, string $msg): void
    {
        if ($type === 'error') {
            $_SESSION['flash_error'] = $msg;
        } else {
            $_SESSION['flash_success'] = $msg;
        }
    }

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];
        $cols   = $this->getTableColumns('subscriptions');

        $selectCols = ['id'];
        foreach ([
            'user_id','name','title','provider','category','amount','price','currency',
            'billing_cycle','cycle','frequency','interval',
            'next_due_date','next_date','due_date',
            'start_date','end_date',
            'is_active','active','status',
            'notes','note','created_at'
        ] as $c) {
            if ($this->hasCol($cols, $c)) $selectCols[] = "`$c`";
        }

        if (empty($cols)) {
            $subscriptions = [];
            $this->setFlash('error', 'Subscriptions table not found or not accessible.');
        } else {
            if ($this->hasCol($cols, 'user_id')) {
                $subscriptions = DB::query(
                    "SELECT " . implode(', ', $selectCols) . " FROM `subscriptions`
                     WHERE `user_id` = ?
                     ORDER BY `id` DESC",
                    [$userId]
                )->fetchAll();
            } else {
                $subscriptions = DB::query(
                    "SELECT " . implode(', ', $selectCols) . " FROM `subscriptions`
                     ORDER BY `id` DESC"
                )->fetchAll();
            }
        }

        $this->view('subscriptions/index', [
            'subscriptions' => $subscriptions,
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function save(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];
        $cols   = $this->getTableColumns('subscriptions');

        if (empty($cols)) {
            $this->setFlash('error', 'Subscriptions table not found or not accessible.');
            $this->redirect('/subscriptions');
        }

        $id = (int)($_POST['id'] ?? 0);

        $name     = trim((string)($_POST['name'] ?? ''));
        $provider = trim((string)($_POST['provider'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $cycle    = trim((string)($_POST['cycle'] ?? $_POST['billing_cycle'] ?? $_POST['frequency'] ?? 'monthly'));
        $dueDate  = trim((string)($_POST['next_due_date'] ?? $_POST['due_date'] ?? $_POST['next_date'] ?? ''));
        $notes    = trim((string)($_POST['notes'] ?? $_POST['note'] ?? ''));

        $amountIn = (string)($_POST['amount'] ?? $_POST['price'] ?? '');
        $amount   = is_numeric($amountIn) ? (float)$amountIn : null;

        if ($name === '' && $provider === '') {
            $this->setFlash('error', 'Please enter a subscription name or provider.');
            $this->redirect('/subscriptions');
        }
        if ($amount === null) {
            $this->setFlash('error', 'Please enter a valid amount.');
            $this->redirect('/subscriptions');
        }

        // Build schema-aware data map
        $data = [];

        if ($this->hasCol($cols, 'user_id')) $data['user_id'] = $userId;

        if ($name !== '') {
            if ($this->hasCol($cols, 'name')) $data['name'] = $name;
            elseif ($this->hasCol($cols, 'title')) $data['title'] = $name;
        }

        if ($provider !== '' && $this->hasCol($cols, 'provider')) $data['provider'] = $provider;
        if ($category !== '' && $this->hasCol($cols, 'category')) $data['category'] = $category;

        if ($this->hasCol($cols, 'amount')) $data['amount'] = $amount;
        elseif ($this->hasCol($cols, 'price')) $data['price'] = $amount;

        if ($cycle !== '') {
            if ($this->hasCol($cols, 'billing_cycle')) $data['billing_cycle'] = $cycle;
            elseif ($this->hasCol($cols, 'cycle')) $data['cycle'] = $cycle;
            elseif ($this->hasCol($cols, 'frequency')) $data['frequency'] = $cycle;
            elseif ($this->hasCol($cols, 'interval')) $data['interval'] = $cycle;
        }

        // Dates
        if ($dueDate !== '') {
            if ($this->hasCol($cols, 'next_due_date')) $data['next_due_date'] = $dueDate;
            elseif ($this->hasCol($cols, 'next_date')) $data['next_date'] = $dueDate;
            elseif ($this->hasCol($cols, 'due_date')) $data['due_date'] = $dueDate;
        }

        if ($notes !== '') {
            if ($this->hasCol($cols, 'notes')) $data['notes'] = $notes;
            elseif ($this->hasCol($cols, 'note')) $data['note'] = $notes;
        }

        // Active default
        if ($this->hasCol($cols, 'is_active')) $data['is_active'] = 1;
        elseif ($this->hasCol($cols, 'active')) $data['active'] = 1;
        elseif ($this->hasCol($cols, 'status')) $data['status'] = 'active';

        if (empty($data)) {
            $this->setFlash('error', 'No compatible columns found in subscriptions table.');
            $this->redirect('/subscriptions');
        }

        try {
            if ($id > 0 && $this->hasCol($cols, 'id')) {
                // Update
                $setParts = [];
                $params   = [];

                foreach ($data as $k => $v) {
                    if ($k === 'user_id') continue;
                    $setParts[] = "`$k` = ?";
                    $params[] = $v;
                }

                if (empty($setParts)) {
                    $this->setFlash('error', 'Nothing to update.');
                    $this->redirect('/subscriptions');
                }

                $params[] = $id;

                if ($this->hasCol($cols, 'user_id')) {
                    $params[] = $userId;
                    DB::query(
                        "UPDATE `subscriptions` SET " . implode(', ', $setParts) . "
                         WHERE `id` = ? AND `user_id` = ?
                         LIMIT 1",
                        $params
                    );
                } else {
                    DB::query(
                        "UPDATE `subscriptions` SET " . implode(', ', $setParts) . "
                         WHERE `id` = ?
                         LIMIT 1",
                        $params
                    );
                }

                $this->setFlash('success', 'Subscription updated.');
            } else {
                // Insert
                $fields = array_keys($data);
                $placeholders = array_fill(0, count($fields), '?');
                $params = array_values($data);

                DB::query(
                    "INSERT INTO `subscriptions` (" . implode(',', array_map(fn($f) => "`$f`", $fields)) . ")
                     VALUES (" . implode(',', $placeholders) . ")",
                    $params
                );

                $this->setFlash('success', 'Subscription saved.');
            }
        } catch (\Throwable $e) {
            $this->setFlash('error', 'Failed to save subscription.');
        }

        $this->redirect('/subscriptions');
    }

    public function toggleStatus(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];
        $cols   = $this->getTableColumns('subscriptions');

        if (empty($cols)) {
            $this->setFlash('error', 'Subscriptions table not found or not accessible.');
            $this->redirect('/subscriptions');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('/subscriptions');
        }

        // Determine active column style
        $activeCol = null; // 'is_active' | 'active' | 'status'
        if ($this->hasCol($cols, 'is_active')) $activeCol = 'is_active';
        elseif ($this->hasCol($cols, 'active')) $activeCol = 'active';
        elseif ($this->hasCol($cols, 'status')) $activeCol = 'status';

        if ($activeCol === null) {
            $this->setFlash('error', 'No active/status column found in subscriptions table.');
            $this->redirect('/subscriptions');
        }

        try {
            // Fetch current
            if ($this->hasCol($cols, 'user_id')) {
                $row = DB::query(
                    "SELECT `id`, `$activeCol` AS v FROM `subscriptions` WHERE `id` = ? AND `user_id` = ? LIMIT 1",
                    [$id, $userId]
                )->fetch();
            } else {
                $row = DB::query(
                    "SELECT `id`, `$activeCol` AS v FROM `subscriptions` WHERE `id` = ? LIMIT 1",
                    [$id]
                )->fetch();
            }

            if (!$row) {
                $this->setFlash('error', 'Subscription not found.');
                $this->redirect('/subscriptions');
            }

            $cur = $row['v'] ?? null;

            if ($activeCol === 'status') {
                $new = ((string)$cur === 'active') ? 'inactive' : 'active';
            } else {
                $curBool = ((string)$cur === '1' || $cur === 1 || $cur === true);
                $new = $curBool ? 0 : 1;
            }

            if ($this->hasCol($cols, 'user_id')) {
                DB::query(
                    "UPDATE `subscriptions` SET `$activeCol` = ? WHERE `id` = ? AND `user_id` = ? LIMIT 1",
                    [$new, $id, $userId]
                );
            } else {
                DB::query(
                    "UPDATE `subscriptions` SET `$activeCol` = ? WHERE `id` = ? LIMIT 1",
                    [$new, $id]
                );
            }

            $this->setFlash('success', 'Subscription status updated.');
        } catch (\Throwable $e) {
            $this->setFlash('error', 'Failed to update subscription status.');
        }

        $this->redirect('/subscriptions');
    }
}
