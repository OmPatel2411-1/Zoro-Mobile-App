<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/RuleEngine.php';

final class TransactionsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $filters = [
            'q'        => trim((string)($_GET['q'] ?? '')),
            'type'     => trim((string)($_GET['type'] ?? '')),
            'category' => trim((string)($_GET['category'] ?? '')),
            'from'     => trim((string)($_GET['from'] ?? '')),
            'to'       => trim((string)($_GET['to'] ?? '')),
        ];

        if (!in_array($filters['type'], ['income', 'expense'], true)) $filters['type'] = '';
        if (!$this->isDateOrEmpty($filters['from'])) $filters['from'] = '';
        if (!$this->isDateOrEmpty($filters['to'])) $filters['to'] = '';

        $transactions = Transaction::allForUser($userId, $filters);
        $categories   = Transaction::categoriesForUser($userId);

        $this->view('transactions/index', [
            'appName'      => Env::get('APP_NAME', 'Zoro'),
            'user'         => $user,
            'mode'         => $_SESSION['ui_mode'] ?? 'simple',
            'filters'      => $filters,
            'transactions' => $transactions,
            'categories'   => $categories,

            // Flash for UI (optional)
            'error'        => $_SESSION['flash_error'] ?? null,
            'success'      => $_SESSION['flash_success'] ?? null,
        ], 'app');

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function createForm(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $categories = Transaction::categoriesForUser($userId);

        $this->view('transactions/form', [
            'appName'    => Env::get('APP_NAME', 'Zoro'),
            'user'       => $user,
            'mode'       => $_SESSION['ui_mode'] ?? 'simple',
            'page'       => 'create',
            'categories' => $categories,
            'tx'         => [
                'type'        => 'expense',
                'amount'      => '',
                'category'    => '',
                'description' => '',
                'occurred_on' => date('Y-m-d'),
            ],
            'errors'     => [],
        ], 'app');
    }

    public function create(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        // Apply Rules BEFORE validation (so rules can auto-fill category/description)
        $in = $_POST;

        $engine = new RuleEngine();
        $ruleResult = $engine->applyToTransaction($userId, [
            'type'        => (string)($in['type'] ?? 'expense'),
            'amount'      => (string)($in['amount'] ?? ''),
            'category'    => (string)($in['category'] ?? ''),
            'description' => (string)($in['description'] ?? ''),
        ]);

        $appliedCount = 0;
        if (!empty($ruleResult['applied_rules']) && is_array($ruleResult['applied_rules'])) {
            $appliedCount = count($ruleResult['applied_rules']);
        }

        if (!empty($ruleResult['data']) && is_array($ruleResult['data'])) {
            if (isset($ruleResult['data']['category'])) {
                $in['category'] = (string)$ruleResult['data']['category'];
            }
            if (isset($ruleResult['data']['description'])) {
                $in['description'] = (string)$ruleResult['data']['description'];
            }
        }

        [$data, $errors] = $this->validateTx($in);

        if (!empty($errors)) {
            $categories = Transaction::categoriesForUser($userId);

            $this->view('transactions/form', [
                'appName'    => Env::get('APP_NAME', 'Zoro'),
                'user'       => $user,
                'mode'       => $_SESSION['ui_mode'] ?? 'simple',
                'page'       => 'create',
                'categories' => $categories,
                'tx'         => $data,
                'errors'     => $errors,
            ], 'app');
            return;
        }

        Transaction::create($userId, $data);

        if ($appliedCount > 0) {
            $_SESSION['flash_success'] = "Auto-rule applied ({$appliedCount}).";
        }

        $this->redirect('/transactions');
    }

    public function editForm(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->redirect('/transactions');

        $tx = Transaction::find($id, $userId);
        if (!$tx) $this->redirect('/transactions');

        $categories = Transaction::categoriesForUser($userId);

        $this->view('transactions/form', [
            'appName'    => Env::get('APP_NAME', 'Zoro'),
            'user'       => $user,
            'mode'       => $_SESSION['ui_mode'] ?? 'simple',
            'page'       => 'edit',
            'categories' => $categories,
            'tx'         => $tx,
            'errors'     => [],
        ], 'app');
    }

    public function update(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) $this->redirect('/transactions');

        $existing = Transaction::find($id, $userId);
        if (!$existing) $this->redirect('/transactions');

        // Apply Rules BEFORE validation (so rules can auto-fill category/description)
        $in = $_POST;

        $engine = new RuleEngine();
        $ruleResult = $engine->applyToTransaction($userId, [
            'type'        => (string)($in['type'] ?? ($existing['type'] ?? 'expense')),
            'amount'      => (string)($in['amount'] ?? ($existing['amount'] ?? '')),
            'category'    => (string)($in['category'] ?? ($existing['category'] ?? '')),
            'description' => (string)($in['description'] ?? ($existing['description'] ?? '')),
        ]);

        $appliedCount = 0;
        if (!empty($ruleResult['applied_rules']) && is_array($ruleResult['applied_rules'])) {
            $appliedCount = count($ruleResult['applied_rules']);
        }

        if (!empty($ruleResult['data']) && is_array($ruleResult['data'])) {
            if (isset($ruleResult['data']['category'])) {
                $in['category'] = (string)$ruleResult['data']['category'];
            }
            if (isset($ruleResult['data']['description'])) {
                $in['description'] = (string)$ruleResult['data']['description'];
            }
        }

        [$data, $errors] = $this->validateTx($in);
        $data['id'] = $id;

        if (!empty($errors)) {
            $categories = Transaction::categoriesForUser($userId);

            $this->view('transactions/form', [
                'appName'    => Env::get('APP_NAME', 'Zoro'),
                'user'       => $user,
                'mode'       => $_SESSION['ui_mode'] ?? 'simple',
                'page'       => 'edit',
                'categories' => $categories,
                'tx'         => $data,
                'errors'     => $errors,
            ], 'app');
            return;
        }

        Transaction::update($id, $userId, $data);

        if ($appliedCount > 0) {
            $_SESSION['flash_success'] = "Auto-rule applied ({$appliedCount}).";
        }

        $this->redirect('/transactions');
    }

    public function delete(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Transaction::delete($id, $userId);
        }

        $this->redirect('/transactions');
    }

    public function bulk(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $action = trim((string)($_POST['action'] ?? ''));
        $idsRaw = $_POST['ids'] ?? [];

        $ids = [];
        if (is_array($idsRaw)) {
            foreach ($idsRaw as $v) {
                $id = (int)$v;
                if ($id > 0) $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            $this->json(['ok' => false, 'message' => 'No transactions selected'], 400);
        }

        if ($action === 'delete') {
            Transaction::bulkDelete($userId, $ids);
            $this->json(['ok' => true]);
        }

        if ($action === 'set-category') {
            if (!Transaction::supportsCategory()) {
                $this->json(['ok' => false, 'message' => 'Category is not supported by your DB schema'], 400);
            }

            $category = trim((string)($_POST['category'] ?? ''));
            if ($category === '') {
                $this->json(['ok' => false, 'message' => 'Category required'], 400);
            }

            Transaction::bulkSetCategory($userId, $ids, $category);
            $this->json(['ok' => true]);
        }

        $this->json(['ok' => false, 'message' => 'Unknown bulk action'], 400);
    }

    /**
     * -------- Helpers --------
     */
    private function requireUser(): array
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

        $_SESSION['ui_mode'] = $user['ui_mode'] ?? ($_SESSION['ui_mode'] ?? 'simple');
        return $user;
    }

    /**
     * Schema-aware validation: require only fields that are actually supported.
     */
    private function validateTx(array $in): array
    {
        $errors = [];

        // Type
        $type = (string)($in['type'] ?? 'expense');
        $type = ($type === 'income') ? 'income' : 'expense';
        if (!Transaction::supportsType()) {
            // still keep default for UI consistency
            $type = 'expense';
        }

        // Amount
        $amountRaw = trim((string)($in['amount'] ?? ''));
        $amount = (is_numeric($amountRaw)) ? (float)$amountRaw : 0.0;
        if (Transaction::supportsAmount()) {
            if ($amount <= 0) {
                $errors['amount'] = 'Enter a valid amount';
            }
        }

        // Category
        $category = trim((string)($in['category'] ?? ''));
        if (Transaction::supportsCategory()) {
            if ($category === '') {
                $errors['category'] = 'Category is required';
            }
        } else {
            $category = '';
        }

        // Description
        $description = trim((string)($in['description'] ?? ''));
        if (!Transaction::supportsDescription()) {
            $description = '';
        }

        // Date
        $occurredOn = trim((string)($in['occurred_on'] ?? ''));
        if (Transaction::supportsDate()) {
            if (!$this->isDate($occurredOn)) {
                $errors['occurred_on'] = 'Select a valid date';
                $occurredOn = date('Y-m-d');
            }
        } else {
            $occurredOn = date('Y-m-d');
        }

        $data = [
            'type'        => $type,
            'amount'      => $amount,
            'category'    => $category,
            'description' => $description,
            'occurred_on' => $occurredOn,
        ];

        return [$data, $errors];
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y);
    }

    private function isDateOrEmpty(string $value): bool
    {
        if ($value === '') return true;
        return $this->isDate($value);
    }
}
