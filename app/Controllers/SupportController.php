<?php
declare(strict_types=1);

final class SupportController extends Controller
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
        return in_array($col, $cols, true);
    }

    private function flash(string $type, string $msg): void
    {
        if ($type === 'error') $_SESSION['flash_error'] = $msg;
        else $_SESSION['flash_success'] = $msg;
    }

    private function requireAuth(): int
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        return (int)$_SESSION['user_id'];
    }

    public function index(): void
    {
        $userId = $this->requireAuth();

        $cols = $this->getTableColumns('support_tickets');

        if (empty($cols)) {
            $tickets = [];
            $this->flash('error', 'Support tickets table not found or not accessible.');
        } else {
            $select = ['id'];
            foreach ([
                'user_id',
                'subject','title',
                'message','body','content','details',
                'status',
                'priority',
                'created_at'
            ] as $c) {
                if ($this->hasCol($cols, $c)) $select[] = "`$c`";
            }

            if ($this->hasCol($cols, 'user_id')) {
                $tickets = DB::query(
                    "SELECT " . implode(', ', $select) . " FROM `support_tickets`
                     WHERE `user_id` = ?
                     ORDER BY `id` DESC",
                    [$userId]
                )->fetchAll();
            } else {
                $tickets = DB::query(
                    "SELECT " . implode(', ', $select) . " FROM `support_tickets`
                     ORDER BY `id` DESC"
                )->fetchAll();
            }
        }

        $this->view('support/index', [
            'tickets' => $tickets,
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function create(): void
    {
        $userId = $this->requireAuth();

        $cols = $this->getTableColumns('support_tickets');
        if (empty($cols)) {
            $this->flash('error', 'Support tickets table not found or not accessible.');
            $this->redirect('/support');
        }

        $subject = trim((string)($_POST['subject'] ?? $_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? $_POST['body'] ?? $_POST['content'] ?? ''));

        if ($subject === '' || $message === '') {
            $this->flash('error', 'Please enter subject and message.');
            $this->redirect('/support');
        }

        $data = [];

        if ($this->hasCol($cols, 'user_id')) $data['user_id'] = $userId;

        if ($this->hasCol($cols, 'subject')) $data['subject'] = $subject;
        elseif ($this->hasCol($cols, 'title')) $data['title'] = $subject;

        if ($this->hasCol($cols, 'message')) $data['message'] = $message;
        elseif ($this->hasCol($cols, 'body')) $data['body'] = $message;
        elseif ($this->hasCol($cols, 'content')) $data['content'] = $message;
        elseif ($this->hasCol($cols, 'details')) $data['details'] = $message;

        if ($this->hasCol($cols, 'status')) $data['status'] = 'open';

        if (empty($data)) {
            $this->flash('error', 'No compatible columns found in support_tickets table.');
            $this->redirect('/support');
        }

        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');

            DB::query(
                "INSERT INTO `support_tickets` (" . implode(',', array_map(fn($f) => "`$f`", $fields)) . ")
                 VALUES (" . implode(',', $placeholders) . ")",
                array_values($data)
            );

            $this->flash('success', 'Ticket created.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Failed to create ticket.');
        }

        $this->redirect('/support');
    }
}
