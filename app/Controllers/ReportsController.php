<?php
declare(strict_types=1);

final class ReportsController extends Controller
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

        // We'll provide a simple "Reports home" page with basic totals (schema-aware)
        $tCols = $this->getTableColumns('transactions');
        $totals = [
            'income'  => 0.0,
            'expense' => 0.0,
            'count'   => 0,
        ];

        if (!empty($tCols)) {
            $dateCol = null;
            foreach (['txn_date','date','transaction_date','occurred_on','created_at'] as $c) {
                if ($this->hasCol($tCols, $c)) { $dateCol = $c; break; }
            }

            $amountCol = null;
            foreach (['amount','txn_amount','value','total','amt'] as $c) {
                if ($this->hasCol($tCols, $c)) { $amountCol = $c; break; }
            }

            $typeCol = null;
            foreach (['type','txn_type','transaction_type'] as $c) {
                if ($this->hasCol($tCols, $c)) { $typeCol = $c; break; }
            }

            $userCol = $this->hasCol($tCols, 'user_id') ? 'user_id' : null;

            if ($amountCol !== null && $typeCol !== null) {
                $where = [];
                $params = [];

                if ($userCol !== null) {
                    $where[] = "`$userCol` = ?";
                    $params[] = $userId;
                }

                // Optional month filter via ?month=YYYY-MM
                $month = trim((string)($_GET['month'] ?? ''));
                if ($month !== '' && $dateCol !== null) {
                    // very light validation
                    if (preg_match('/^\d{4}\-\d{2}$/', $month)) {
                        $where[] = "DATE_FORMAT(`$dateCol`, '%Y-%m') = ?";
                        $params[] = $month;
                    }
                }

                $sql = "SELECT
                            COUNT(*) AS c,
                            SUM(CASE WHEN LOWER(`$typeCol`) = 'income' THEN `$amountCol` ELSE 0 END) AS inc,
                            SUM(CASE WHEN LOWER(`$typeCol`) = 'expense' THEN `$amountCol` ELSE 0 END) AS exp
                        FROM `transactions`";

                if (!empty($where)) {
                    $sql .= " WHERE " . implode(' AND ', $where);
                }

                $row = DB::query($sql, $params)->fetch();
                if ($row) {
                    $totals['count']   = (int)($row['c'] ?? 0);
                    $totals['income']  = (float)($row['inc'] ?? 0);
                    $totals['expense'] = (float)($row['exp'] ?? 0);
                }
            }
        } else {
            $this->flash('error', 'Transactions table not found or not accessible.');
        }

        $this->view('reports/index', [
            'totals'  => $totals,
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function exportCsv(): void
    {
        $userId = $this->requireAuth();

        $tCols = $this->getTableColumns('transactions');
        if (empty($tCols)) {
            $this->flash('error', 'Transactions table not found or not accessible.');
            $this->redirect('/reports');
        }

        // Detect commonly used columns
        $dateCol = null;
        foreach (['txn_date','date','transaction_date','occurred_on','created_at'] as $c) {
            if ($this->hasCol($tCols, $c)) { $dateCol = $c; break; }
        }

        $amountCol = null;
        foreach (['amount','txn_amount','value','total','amt'] as $c) {
            if ($this->hasCol($tCols, $c)) { $amountCol = $c; break; }
        }

        $typeCol = null;
        foreach (['type','txn_type','transaction_type'] as $c) {
            if ($this->hasCol($tCols, $c)) { $typeCol = $c; break; }
        }

        $categoryCol = null;
        foreach (['category','txn_category','cat'] as $c) {
            if ($this->hasCol($tCols, $c)) { $categoryCol = $c; break; }
        }

        $descCol = null;
        foreach (['description','note','details','narration','remarks'] as $c) {
            if ($this->hasCol($tCols, $c)) { $descCol = $c; break; }
        }

        $userCol = $this->hasCol($tCols, 'user_id') ? 'user_id' : null;

        // Build SELECT list (only what exists)
        $select = [];
        $headers = [];

        if ($dateCol !== null) { $select[] = "`$dateCol` AS txn_date"; $headers[] = 'date'; }
        if ($typeCol !== null) { $select[] = "`$typeCol` AS txn_type"; $headers[] = 'type'; }
        if ($categoryCol !== null) { $select[] = "`$categoryCol` AS category"; $headers[] = 'category'; }
        if ($descCol !== null) { $select[] = "`$descCol` AS description"; $headers[] = 'description'; }
        if ($amountCol !== null) { $select[] = "`$amountCol` AS amount"; $headers[] = 'amount'; }

        // Always include id if exists
        if ($this->hasCol($tCols, 'id')) { array_unshift($select, "`id` AS id"); array_unshift($headers, 'id'); }

        if (empty($select)) {
            $this->flash('error', 'No compatible columns found to export.');
            $this->redirect('/reports');
        }

        $where = [];
        $params = [];

        if ($userCol !== null) {
            $where[] = "`$userCol` = ?";
            $params[] = $userId;
        }

        // Optional month filter via ?month=YYYY-MM
        $month = trim((string)($_GET['month'] ?? ''));
        if ($month !== '' && $dateCol !== null && preg_match('/^\d{4}\-\d{2}$/', $month)) {
            $where[] = "DATE_FORMAT(`$dateCol`, '%Y-%m') = ?";
            $params[] = $month;
        }

        $sql = "SELECT " . implode(', ', $select) . " FROM `transactions`";
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);

        // sort by date if possible
        if ($dateCol !== null) $sql .= " ORDER BY `$dateCol` DESC";
        else $sql .= " ORDER BY `id` DESC";

        $rows = DB::query($sql, $params)->fetchAll();

        $filename = 'zoro-transactions-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // UTF-8 BOM for Excel compatibility
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fputcsv($out, $headers);

        foreach ($rows as $r) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = $r[$h] ?? '';
            }
            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }

    public function exportPdf(): void
    {
        // Without external PDF libraries, we provide a print-friendly HTML report.
        // User can "Print -> Save as PDF" in browser.
        $userId = $this->requireAuth();

        $tCols = $this->getTableColumns('transactions');
        if (empty($tCols)) {
            $this->flash('error', 'Transactions table not found or not accessible.');
            $this->redirect('/reports');
        }

        $dateCol = null;
        foreach (['txn_date','date','transaction_date','occurred_on','created_at'] as $c) {
            if ($this->hasCol($tCols, $c)) { $dateCol = $c; break; }
        }

        $amountCol = null;
        foreach (['amount','txn_amount','value','total','amt'] as $c) {
            if ($this->hasCol($tCols, $c)) { $amountCol = $c; break; }
        }

        $typeCol = null;
        foreach (['type','txn_type','transaction_type'] as $c) {
            if ($this->hasCol($tCols, $c)) { $typeCol = $c; break; }
        }

        $categoryCol = null;
        foreach (['category','txn_category','cat'] as $c) {
            if ($this->hasCol($tCols, $c)) { $categoryCol = $c; break; }
        }

        $descCol = null;
        foreach (['description','note','details','narration','remarks'] as $c) {
            if ($this->hasCol($tCols, $c)) { $descCol = $c; break; }
        }

        $userCol = $this->hasCol($tCols, 'user_id') ? 'user_id' : null;

        $select = [];
        if ($this->hasCol($tCols, 'id')) $select[] = "`id` AS id";
        if ($dateCol !== null) $select[] = "`$dateCol` AS txn_date";
        if ($typeCol !== null) $select[] = "`$typeCol` AS txn_type";
        if ($categoryCol !== null) $select[] = "`$categoryCol` AS category";
        if ($descCol !== null) $select[] = "`$descCol` AS description";
        if ($amountCol !== null) $select[] = "`$amountCol` AS amount";

        if (empty($select)) {
            $this->flash('error', 'No compatible columns found to export.');
            $this->redirect('/reports');
        }

        $where = [];
        $params = [];

        if ($userCol !== null) {
            $where[] = "`$userCol` = ?";
            $params[] = $userId;
        }

        $month = trim((string)($_GET['month'] ?? ''));
        if ($month !== '' && $dateCol !== null && preg_match('/^\d{4}\-\d{2}$/', $month)) {
            $where[] = "DATE_FORMAT(`$dateCol`, '%Y-%m') = ?";
            $params[] = $month;
        }

        $sql = "SELECT " . implode(', ', $select) . " FROM `transactions`";
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        if ($dateCol !== null) $sql .= " ORDER BY `$dateCol` DESC";
        else $sql .= " ORDER BY `id` DESC";

        $rows = DB::query($sql, $params)->fetchAll();

        header('Content-Type: text/html; charset=UTF-8');

        $title = 'Zoro Report';
        if ($month !== '') $title .= ' - ' . htmlspecialchars($month, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . $title . '</title>';
        echo '<style>
            body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#111;margin:24px}
            h1{font-size:20px;margin:0 0 8px}
            .muted{color:#6b7280;font-size:12px;margin-bottom:16px}
            table{width:100%;border-collapse:collapse;font-size:12px}
            th,td{border:1px solid #e5e7eb;padding:8px;vertical-align:top}
            th{background:#f9fafb;text-align:left}
            @media print{body{margin:0;padding:12px}}
        </style></head><body>';

        echo '<h1>' . $title . '</h1>';
        echo '<div class="muted">Tip: Use browser Print → “Save as PDF”. Generated on ' . date('Y-m-d H:i') . '</div>';

        echo '<table><thead><tr>';
        $colsOut = [];
        if ($this->hasCol($tCols, 'id')) { echo '<th>ID</th>'; $colsOut[] = 'id'; }
        if ($dateCol !== null) { echo '<th>Date</th>'; $colsOut[] = 'txn_date'; }
        if ($typeCol !== null) { echo '<th>Type</th>'; $colsOut[] = 'txn_type'; }
        if ($categoryCol !== null) { echo '<th>Category</th>'; $colsOut[] = 'category'; }
        if ($descCol !== null) { echo '<th>Description</th>'; $colsOut[] = 'description'; }
        if ($amountCol !== null) { echo '<th>Amount</th>'; $colsOut[] = 'amount'; }
        echo '</tr></thead><tbody>';

        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($colsOut as $c) {
                $v = (string)($r[$c] ?? '');
                echo '<td>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="' . max(1, count($colsOut)) . '" class="muted">No transactions found for this filter.</td></tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }
}
