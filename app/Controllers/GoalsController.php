<?php
declare(strict_types=1);

final class GoalsController extends Controller
{
    private function getTableColumns(string $table): array
    {
        try {
            $rows = DB::query("DESCRIBE `$table`")->fetchAll();
            $cols = [];
            foreach ($rows as $r) {
                if (!empty($r['Field'])) $cols[] = (string)$r['Field'];
            }
            return $cols;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function hasCol(array $cols, string $col): bool
    {
        return in_array($col, $col? $cols : $cols, true); // safe no-op
    }

    private function setFlash(string $type, string $msg): void
    {
        if ($type === 'error') $_SESSION['flash_error'] = $msg;
        else $_SESSION['flash_success'] = $msg;
    }

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];
        $cols = $this->getTableColumns('goals');

        $selectCols = ['id'];
        foreach ([
            'user_id','name','title','target_amount','target','goal_amount',
            'current_amount','current','progress_amount',
            'deadline','due_date','target_date',
            'category','notes','note',
            'status','is_active','active',
            'created_at'
        ] as $c) {
            if ($this->hasCol($cols, $c)) $selectCols[] = "`$c`";
        }

        if (empty($cols)) {
            $goals = [];
            $this->setFlash('error', 'Goals table not found or not accessible.');
        } else {
            if ($this->hasCol($cols, 'user_id')) {
                $goals = DB::query(
                    "SELECT " . implode(', ', $selectCols) . " FROM `goals`
                     WHERE `user_id` = ?
                     ORDER BY `id` DESC",
                    [$userId]
                )->fetchAll();
            } else {
                $goals = DB::query(
                    "SELECT " . implode(', ', $selectCols) . " FROM `goals`
                     ORDER BY `id` DESC"
                )->fetchAll();
            }
        }

        $this->view('goals/index', [
            'goals'   => $goals,
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function save(): void
    {
        if (empty($_SESSION['user_id'])) $this->redirect('/login');

        $userId = (int)$_SESSION['user_id'];
        $cols = $this->getTableColumns('goals');

        if (empty($cols)) {
            $this->setFlash('error', 'Goals table not found or not accessible.');
            $this->redirect('/goals');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $targetIn = (string)($_POST['target_amount'] ?? $_POST['target'] ?? '');
        $target = is_numeric($targetIn) ? (float)$targetIn : null;
        $deadline = trim((string)($_POST['deadline'] ?? $_POST['due_date'] ?? $_POST['target_date'] ?? ''));

        if ($name === '') {
            $this->setFlash('error', 'Please enter a goal name.');
            $this->redirect('/goals');
        }
        if ($target === null) {
            $this->setFlash('error', 'Please enter a valid target amount.');
            $this->redirect('/goals');
        }

        $data = [];

        if ($this->hasCol($cols, 'user_id')) $data['user_id'] = $userId;

        if ($this->hasCol($cols, 'name')) $data['name'] = $name;
        elseif ($this->hasCol($cols, 'title')) $data['title'] = $name;

        if ($this->hasCol($cols, 'target_amount')) $data['target_amount'] = $target;
        elseif ($this->hasCol($cols, 'target')) $data['target'] = $target;
        elseif ($this->hasCol($cols, 'goal_amount')) $data['goal_amount'] = $target;

        // Default current/progress
        if ($this->hasCol($cols, 'current_amount')) $data['current_amount'] = 0;
        elseif ($this->hasCol($cols, 'current')) $data['current'] = 0;
        elseif ($this->hasCol($cols, 'progress_amount')) $data['progress_amount'] = 0;

        if ($deadline !== '') {
            if ($this->hasCol($cols, 'deadline')) $data['deadline'] = $deadline;
            elseif ($this->hasCol($cols, 'due_date')) $data['due_date'] = $deadline;
            elseif ($this->hasCol($cols, 'target_date')) $data['target_date'] = $deadline;
        }

        if ($this->hasCol($cols, 'status')) $data['status'] = 'active';
        elseif ($this->hasCol($cols, 'is_active')) $data['is_active'] = 1;
        elseif ($this->hasCol($cols, 'active')) $data['active'] = 1;

        if (empty($data)) {
            $this->setFlash('error', 'No compatible columns found in goals table.');
            $this->redirect('/goals');
        }

        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');

            DB::query(
                "INSERT INTO `goals` (" . implode(',', array_map(fn($f) => "`$f`", $fields)) . ")
                 VALUES (" . implode(',', $placeholders) . ")",
                array_values($data)
            );

            $this->setFlash('success', 'Goal saved.');
        } catch (\Throwable $e) {
            $this->setFlash('error', 'Failed to save goal.');
        }

        $this->redirect('/goals');
    }

    public function updateProgress(): void
    {
        if (empty($_SESSION['user_id'])) $this->redirect('/login');

        $userId = (int)$_SESSION['user_id'];
        $cols = $this->getTableColumns('goals');

        if (empty($cols)) {
            $this->setFlash('error', 'Goals table not found or not accessible.');
            $this->redirect('/goals');
        }

        $id = (int)($_POST['id'] ?? 0);
        $progressIn = (string)($_POST['current_amount'] ?? $_POST['current'] ?? $_POST['progress_amount'] ?? '');
        $progress = is_numeric($progressIn) ? (float)$progressIn : null;

        if ($id <= 0 || $progress === null) {
            $this->setFlash('error', 'Invalid goal or progress amount.');
            $this->redirect('/goals');
        }

        // Determine progress column
        $col = null;
        if ($this->hasCol($cols, 'current_amount')) $col = 'current_amount';
        elseif ($this->hasCol($cols, 'current')) $col = 'current';
        elseif ($this->hasCol($cols, 'progress_amount')) $col = 'progress_amount';

        if ($col === null) {
            $this->setFlash('error', 'No progress column found in goals table.');
            $this->redirect('/goals');
        }

        try {
            if ($this->hasCol($cols, 'user_id')) {
                DB::query(
                    "UPDATE `goals` SET `$col` = ? WHERE `id` = ? AND `user_id` = ? LIMIT 1",
                    [$progress, $id, $userId]
                );
            } else {
                DB::query(
                    "UPDATE `goals` SET `$col` = ? WHERE `id` = ? LIMIT 1",
                    [$progress, $id]
                );
            }
            $this->setFlash('success', 'Goal progress updated.');
        } catch (\Throwable $e) {
            $this->setFlash('error', 'Failed to update goal progress.');
        }

        $this->redirect('/goals');
    }
}
