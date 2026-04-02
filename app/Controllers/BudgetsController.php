<?php
declare(strict_types=1);

final class BudgetsController extends Controller
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

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];

        $cols = $this->getTableColumns('budgets');

        // Build safest SELECT based on available columns
        $selectCols = ['id'];
        foreach (['user_id','name','title','category','amount','limit_amount','period','frequency','start_date','end_date','is_active','active','status','created_at'] as $c) {
            if ($this->hasCol($cols, $c)) {
                $selectCols[] = "`$c`";
            }
        }

        // If user_id exists, filter by it; otherwise return all (but still only show to logged-in user)
        if ($this->hasCol($cols, 'user_id')) {
            $budgets = DB::query(
                "SELECT " . implode(', ', $selectCols) . " FROM `budgets` WHERE `user_id` = ? ORDER BY `id` DESC",
                [$userId]
            )->fetchAll();
        } else {
            $budgets = DB::query(
                "SELECT " . implode(', ', $selectCols) . " FROM `budgets` ORDER BY `id` DESC"
            )->fetchAll();
        }

        $this->view('budgets/index', [
            'budgets' => $budgets,
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

        $cols = $this->getTableColumns('budgets');
        if (empty($cols)) {
            $_SESSION['flash_error'] = 'Budgets table not found or not accessible.';
            $this->redirect('/budgets');
        }

        $id = (int)($_POST['id'] ?? 0);

        $name     = trim((string)($_POST['name'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $period   = trim((string)($_POST['period'] ?? 'monthly'));
        $amountIn = (string)($_POST['amount'] ?? '');
        $amount   = is_numeric($amountIn) ? (float)$amountIn : null;

        if ($name === '' && $category === '') {
            $_SESSION['flash_error'] = 'Please enter a budget name or category.';
            $this->redirect('/budgets');
        }
        if ($amount === null) {
            $_SESSION['flash_error'] = 'Please enter a valid amount.';
            $this->redirect('/budgets');
        }

        // Map payload to whatever columns exist (schema-aware)
        $data = [];

        if ($this->hasCol($cols, 'user_id')) {
            $data['user_id'] = $userId;
        }

        if ($name !== '') {
            if ($this->hasCol($cols, 'name')) $data['name'] = $name;
            elseif ($this->hasCol($cols, 'title')) $data['title'] = $name;
        }

        if ($category !== '' && $this->hasCol($cols, 'category')) {
            $data['category'] = $category;
        }

        // Amount column could differ
        if ($this->hasCol($cols, 'amount')) $data['amount'] = $amount;
        elseif ($this->hasCol($cols, 'limit_amount')) $data['limit_amount'] = $amount;

        // Period/frequency
        if ($this->hasCol($cols, 'period')) $data['period'] = ($period !== '' ? $period : 'monthly');
        elseif ($this->hasCol($cols, 'frequency')) $data['frequency'] = ($period !== '' ? $period : 'monthly');

        // Active/status defaults
        if ($this->hasCol($cols, 'is_active')) $data['is_active'] = 1;
        elseif ($this->hasCol($cols, 'active')) $data['active'] = 1;
        elseif ($this->hasCol($cols, 'status')) $data['status'] = 'active';

        if (empty($data)) {
            $_SESSION['flash_error'] = 'No compatible columns found in budgets table.';
            $this->redirect('/budgets');
        }

        try {
            if ($id > 0 && $this->hasCol($cols, 'id')) {
                // Update (and ensure ownership if user_id exists)
                $setParts = [];
                $params = [];

                foreach ($data as $k => $v) {
                    // Do not update user_id on edit if you want; but safe either way.
                    if ($k === 'user_id') continue;
                    $setParts[] = "`$k` = ?";
                    $params[] = $v;
                }

                if (empty($setParts)) {
                    $_SESSION['flash_error'] = 'Nothing to update.';
                    $this->redirect('/budgets');
                }

                $params[] = $id;

                if ($this->hasCol($cols, 'user_id')) {
                    $params[] = $userId;
                    DB::query(
                        "UPDATE `budgets` SET " . implode(', ', $setParts) . " WHERE `id` = ? AND `user_id` = ? LIMIT 1",
                        $params
                    );
                } else {
                    DB::query(
                        "UPDATE `budgets` SET " . implode(', ', $setParts) . " WHERE `id` = ? LIMIT 1",
                        $params
                    );
                }

                $_SESSION['flash_success'] = 'Budget updated.';
            } else {
                // Insert
                $fields = array_keys($data);
                $placeholders = array_fill(0, count($fields), '?');
                $params = array_values($data);

                DB::query(
                    "INSERT INTO `budgets` (" . implode(',', array_map(fn($f) => "`$f`", $fields)) . ")
                     VALUES (" . implode(',', $placeholders) . ")",
                    $params
                );

                $_SESSION['flash_success'] = 'Budget saved.';
            }
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to save budget.';
        }

        $this->redirect('/budgets');
    }
}
