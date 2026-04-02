<?php
declare(strict_types=1);

final class RulesController extends Controller
{
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
        if ($type === 'error') $_SESSION['flash_error'] = $msg;
        else $_SESSION['flash_success'] = $msg;
    }

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];
        $cols = $this->getTableColumns('rules');

        if (empty($cols)) {
            $this->flash('error', 'Rules table not found or not accessible.');
            $rules = [];
        } else {
            $selectCols = ['id'];
            foreach ([
                'user_id',
                'name','title',
                'match_text','keyword','contains','query',
                'match_category','category',
                'match_type','type',
                'min_amount','amount_min','amount_gt',
                'max_amount','amount_max','amount_lt',
                'set_category','action_category','target_category',
                'set_note','action_note','target_note',
                'action','rule_json','definition','config',
                'priority',
                'is_active','active','status',
                'created_at'
            ] as $c) {
                if ($this->hasCol($cols, $c)) $selectCols[] = "`$c`";
            }

            if ($this->hasCol($cols, 'user_id')) {
                $rules = DB::query(
                    "SELECT " . implode(', ', $selectCols) . " FROM `rules`
                     WHERE `user_id` = ?
                     ORDER BY `id` DESC",
                    [$userId]
                )->fetchAll();
            } else {
                $rules = DB::query(
                    "SELECT " . implode(', ', $selectCols) . " FROM `rules`
                     ORDER BY `id` DESC"
                )->fetchAll();
            }
        }

        $this->view('rules/index', [
            'rules'   => $rules,
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
        $cols = $this->getTableColumns('rules');

        if (empty($cols)) {
            $this->flash('error', 'Rules table not found or not accessible.');
            $this->redirect('/rules');
        }

        $name         = trim((string)($_POST['name'] ?? ''));
        $matchText    = trim((string)($_POST['match_text'] ?? $_POST['keyword'] ?? ''));
        $matchType    = trim((string)($_POST['match_type'] ?? $_POST['type'] ?? ''));
        $setCategory  = trim((string)($_POST['set_category'] ?? $_POST['action_category'] ?? ''));
        $setNote      = trim((string)($_POST['set_note'] ?? $_POST['action_note'] ?? ''));
        $minAmountIn  = (string)($_POST['min_amount'] ?? $_POST['amount_min'] ?? '');
        $minAmount    = ($minAmountIn !== '' && is_numeric($minAmountIn)) ? (float)$minAmountIn : null;

        if ($name === '') {
            $this->flash('error', 'Please enter a rule name.');
            $this->redirect('/rules');
        }

        // At least one condition and one action
        $hasCondition = ($matchText !== '') || ($matchType !== '') || ($minAmount !== null);
        $hasAction    = ($setCategory !== '') || ($setNote !== '');

        if (!$hasCondition) {
            $this->flash('error', 'Please add at least one condition (keyword/type/min amount).');
            $this->redirect('/rules');
        }
        if (!$hasAction) {
            $this->flash('error', 'Please add at least one action (set category / set note).');
            $this->redirect('/rules');
        }

        $data = [];

        if ($this->hasCol($cols, 'user_id')) $data['user_id'] = $userId;

        if ($this->hasCol($cols, 'name')) $data['name'] = $name;
        elseif ($this->hasCol($cols, 'title')) $data['title'] = $name;

        if ($matchText !== '') {
            if ($this->hasCol($cols, 'match_text')) $data['match_text'] = $matchText;
            elseif ($this->hasCol($cols, 'keyword')) $data['keyword'] = $matchText;
            elseif ($this->hasCol($cols, 'contains')) $data['contains'] = $matchText;
            elseif ($this->hasCol($cols, 'query')) $data['query'] = $matchText;
        }

        if ($matchType !== '') {
            if ($this->hasCol($cols, 'match_type')) $data['match_type'] = $matchType;
            elseif ($this->hasCol($cols, 'type')) $data['type'] = $matchType;
        }

        if ($minAmount !== null) {
            if ($this->hasCol($cols, 'min_amount')) $data['min_amount'] = $minAmount;
            elseif ($this->hasCol($cols, 'amount_min')) $data['amount_min'] = $minAmount;
            elseif ($this->hasCol($cols, 'amount_gt')) $data['amount_gt'] = $minAmount;
        }

        if ($setCategory !== '') {
            if ($this->hasCol($cols, 'set_category')) $data['set_category'] = $setCategory;
            elseif ($this->hasCol($cols, 'action_category')) $data['action_category'] = $setCategory;
            elseif ($this->hasCol($cols, 'target_category')) $data['target_category'] = $setCategory;
        }

        if ($setNote !== '') {
            if ($this->hasCol($cols, 'set_note')) $data['set_note'] = $setNote;
            elseif ($this->hasCol($cols, 'action_note')) $data['action_note'] = $setNote;
            elseif ($this->hasCol($cols, 'target_note')) $data['target_note'] = $setNote;
        }

        // Fallback: store in JSON/config if present
        if ($this->hasCol($cols, 'rule_json') || $this->hasCol($cols, 'definition') || $this->hasCol($cols, 'config')) {
            $payload = [
                'conditions' => [
                    'match_text' => $matchText,
                    'match_type' => $matchType,
                    'min_amount' => $minAmount,
                ],
                'actions' => [
                    'set_category' => $setCategory,
                    'set_note'     => $setNote,
                ],
            ];
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

            if ($json !== false) {
                if ($this->hasCol($cols, 'rule_json')) $data['rule_json'] = $json;
                elseif ($this->hasCol($cols, 'definition')) $data['definition'] = $json;
                elseif ($this->hasCol($cols, 'config')) $data['config'] = $json;
            }
        }

        // Active default
        if ($this->hasCol($cols, 'is_active')) $data['is_active'] = 1;
        elseif ($this->hasCol($cols, 'active')) $data['active'] = 1;
        elseif ($this->hasCol($cols, 'status')) $data['status'] = 'active';

        if (empty($data)) {
            $this->flash('error', 'No compatible columns found in rules table.');
            $this->redirect('/rules');
        }

        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');

            DB::query(
                "INSERT INTO `rules` (" . implode(',', array_map(fn($f) => "`$f`", $fields)) . ")
                 VALUES (" . implode(',', $placeholders) . ")",
                array_values($data)
            );

            $this->flash('success', 'Rule saved.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Failed to save rule.');
        }

        $this->redirect('/rules');
    }

    public function toggle(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];
        $cols = $this->getTableColumns('rules');

        if (empty($cols)) {
            $this->flash('error', 'Rules table not found or not accessible.');
            $this->redirect('/rules');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('/rules');
        }

        $activeCol = null; // is_active | active | status
        if ($this->hasCol($cols, 'is_active')) $activeCol = 'is_active';
        elseif ($this->hasCol($cols, 'active')) $activeCol = 'active';
        elseif ($this->hasCol($cols, 'status')) $activeCol = 'status';

        if ($activeCol === null) {
            $this->flash('error', 'No active/status column found in rules table.');
            $this->redirect('/rules');
        }

        try {
            if ($this->hasCol($cols, 'user_id')) {
                $row = DB::query(
                    "SELECT `id`, `$activeCol` AS v FROM `rules` WHERE `id` = ? AND `user_id` = ? LIMIT 1",
                    [$id, $userId]
                )->fetch();
            } else {
                $row = DB::query(
                    "SELECT `id`, `$activeCol` AS v FROM `rules` WHERE `id` = ? LIMIT 1",
                    [$id]
                )->fetch();
            }

            if (!$row) {
                $this->flash('error', 'Rule not found.');
                $this->redirect('/rules');
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
                    "UPDATE `rules` SET `$activeCol` = ? WHERE `id` = ? AND `user_id` = ? LIMIT 1",
                    [$new, $id, $userId]
                );
            } else {
                DB::query(
                    "UPDATE `rules` SET `$activeCol` = ? WHERE `id` = ? LIMIT 1",
                    [$new, $id]
                );
            }

            $this->flash('success', 'Rule status updated.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Failed to update rule status.');
        }

        $this->redirect('/rules');
    }
}
