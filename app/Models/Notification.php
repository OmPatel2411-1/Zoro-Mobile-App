<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/DB.php';

/**
 * Notification Model (Schema-Aware + Automation)
 * ---------------------------------------------
 * Goals:
 * - Never assume notifications table schema
 * - Insert notifications only using columns that exist
 * - Provide "automation" checks for:
 *   - Budgets (spent >= 80% and >= 100%)
 *   - Subscriptions (due soon)
 *   - Goals (deadline soon and behind)
 *
 * IMPORTANT:
 * - This model is safe even if tables/columns differ or are missing.
 * - If required tables/columns are missing, it quietly no-ops.
 */
final class Notification
{
    private static array $colsCache = [];

    // -------------------------
    // Schema helpers
    // -------------------------
    private static function cols(string $table): array
    {
        if (isset(self::$colsCache[$table])) {
            return self::$colsCache[$table];
        }

        try {
            $rows = DB::query("DESCRIBE `$table`")->fetchAll();
            $cols = [];
            foreach ($rows as $r) {
                if (!empty($r['Field'])) $cols[] = (string)$r['Field'];
            }
            self::$colsCache[$table] = $cols;
            return $cols;
        } catch (\Throwable $e) {
            self::$colsCache[$table] = [];
            return [];
        }
    }

    private static function has(array $cols, string $col): bool
    {
        return in_array($col, $cols, true);
    }

    private static function pickFirst(array $cols, array $candidates): string
    {
        foreach ($candidates as $c) {
            if (self::has($cols, $c)) return $c;
        }
        return '';
    }

    private static function normalizeDate(string $d): ?string
    {
        $d = trim($d);
        if ($d === '') return null;

        // Accept Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;

        // Try parse
        $ts = strtotime($d);
        if ($ts === false) return null;
        return date('Y-m-d', $ts);
    }

    private static function daysBetween(string $fromYmd, string $toYmd): int
    {
        $a = strtotime($fromYmd . ' 00:00:00');
        $b = strtotime($toYmd . ' 00:00:00');
        if ($a === false || $b === false) return 999999;
        return (int)floor(($b - $a) / 86400);
    }

    private static function today(): string
    {
        return date('Y-m-d');
    }

    // -------------------------
    // Notifications API
    // -------------------------
    public static function tableReady(): bool
    {
        return !empty(self::cols('notifications'));
    }

    /**
     * Create notification with schema-aware columns.
     * Uses best-effort mapping:
     * - title: title | subject | heading | name
     * - body: message | body | description | content | note
     * - type: type | level | category
     * - read: is_read | read | seen
     * - created_at: created_at | created_on | created
     */
    public static function create(int $userId, string $title, string $message, string $type = 'info', array $meta = []): bool
    {
        $nCols = self::cols('notifications');
        if (empty($nCols)) return false;

        $userCol = self::pickFirst($nCols, ['user_id', 'uid']);
        if ($userCol === '') {
            // Without user column, skip (multi-user app)
            return false;
        }

        $titleCol = self::pickFirst($nCols, ['title','subject','heading','name']);
        $bodyCol  = self::pickFirst($nCols, ['message','body','description','content','note','details']);
        $typeCol  = self::pickFirst($nCols, ['type','level','category','severity','kind']);
        $readCol  = self::pickFirst($nCols, ['is_read','read','seen','is_seen']);
        $metaCol  = self::pickFirst($nCols, ['meta','payload','data','extra']);
        $createdCol = self::pickFirst($nCols, ['created_at','created_on','created','created_time']);

        $fields = [];
        $placeholders = [];
        $params = [];

        // required user
        $fields[] = "`$userCol`";
        $placeholders[] = "?";
        $params[] = $userId;

        if ($titleCol !== '') {
            $fields[] = "`$titleCol`";
            $placeholders[] = "?";
            $params[] = $title;
        }

        if ($bodyCol !== '') {
            $fields[] = "`$bodyCol`";
            $placeholders[] = "?";
            $params[] = $message;
        }

        if ($typeCol !== '') {
            $fields[] = "`$typeCol`";
            $placeholders[] = "?";
            $params[] = $type;
        }

        if ($readCol !== '') {
            $fields[] = "`$readCol`";
            $placeholders[] = "?";
            $params[] = 0;
        }

        if ($metaCol !== '') {
            $fields[] = "`$metaCol`";
            $placeholders[] = "?";
            $params[] = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // If created timestamp column exists, try to set it.
        // If DB column has default CURRENT_TIMESTAMP, it’s fine to omit, but setting NOW() is also fine.
        if ($createdCol !== '') {
            $fields[] = "`$createdCol`";
            $placeholders[] = "NOW()";
        }

        // De-dup guard: if table has enough columns, avoid spamming same title in short window.
        // Best-effort only (no assumptions).
        if (self::isDuplicateLikely($userId, $title)) {
            return false;
        }

        $sql = "INSERT INTO `notifications` (" . implode(',', $fields) . ")
                VALUES (" . implode(',', $placeholders) . ")";
        try {
            DB::query($sql, $params);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Best-effort duplicate prevention:
     * If notifications has a title-like and created-like column, block same title in last 12 hours.
     */
    private static function isDuplicateLikely(int $userId, string $title): bool
    {
        $nCols = self::cols('notifications');
        if (empty($nCols)) return false;

        $userCol = self::pickFirst($nCols, ['user_id', 'uid']);
        $titleCol = self::pickFirst($nCols, ['title','subject','heading','name']);
        $createdCol = self::pickFirst($nCols, ['created_at','created_on','created','created_time']);

        if ($userCol === '' || $titleCol === '' || $createdCol === '') return false;

        try {
            $row = DB::query(
                "SELECT 1
                 FROM `notifications`
                 WHERE `$userCol` = ?
                   AND `$titleCol` = ?
                   AND `$createdCol` >= (NOW() - INTERVAL 12 HOUR)
                 LIMIT 1",
                [$userId, $title]
            )->fetch();
            return !empty($row);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // -------------------------
    // Automation (budgets/subs/goals)
    // -------------------------

    /**
     * Run notification automation for a user.
     * Safe to call on dashboard load (kept light + schema aware).
     */
    public static function runAutomationForUser(int $userId): void
    {
        if ($userId <= 0) return;
        if (!self::tableReady()) return;

        // Run each module safely
        self::budgetAlerts($userId);
        self::subscriptionAlerts($userId);
        self::goalAlerts($userId);
    }

    /**
     * Budget alerts:
     * - Requires: budgets table + transactions schema with amount + date + type
     * - Alerts at 80% and 100% usage for current month (monthly budgets)
     * - If budget period column exists and says weekly/yearly, it adjusts.
     */
    private static function budgetAlerts(int $userId): void
    {
        $bCols = self::cols('budgets');
        if (empty($bCols)) return;

        $bUserCol = self::pickFirst($bCols, ['user_id','uid']);
        $bNameCol = self::pickFirst($bCols, ['name','title']);
        $bCatCol  = self::pickFirst($bCols, ['category']);
        $bAmtCol  = self::pickFirst($bCols, ['amount','limit_amount']);
        $bPeriodCol = self::pickFirst($bCols, ['period','frequency','cycle','billing_cycle']);

        if ($bAmtCol === '') return;

        // Transactions schema (needed)
        $tCols = self::cols('transactions');
        if (empty($tCols)) return;

        $tUserCol = self::pickFirst($tCols, ['user_id','uid']);
        $tAmtCol  = self::pickFirst($tCols, ['amount','txn_amount','value','price']);
        $tTypeCol = self::pickFirst($tCols, ['type','txn_type','transaction_type']);
        $tDateCol = self::pickFirst($tCols, ['txn_date','date','occurred_on','transaction_date','created_at']);
        $tCatCol  = self::pickFirst($tCols, ['category','cat']);

        if ($tUserCol === '' || $tAmtCol === '' || $tTypeCol === '' || $tDateCol === '') return;

        // Fetch budgets
        try {
            $select = ["`id`"];
            foreach ([$bUserCol,$bNameCol,$bCatCol,$bAmtCol,$bPeriodCol] as $c) {
                if ($c !== '' && $c !== 'id') $select[] = "`$c`";
            }

            $sql = "SELECT " . implode(',', array_unique($select)) . " FROM `budgets`";
            $params = [];

            if ($bUserCol !== '') {
                $sql .= " WHERE `$bUserCol` = ?";
                $params[] = $userId;
            }

            $sql .= " ORDER BY `id` DESC LIMIT 100";
            $budgets = DB::query($sql, $params)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return;
        }

        if (empty($budgets)) return;

        $today = self::today();

        foreach ($budgets as $b) {
            $limit = (float)($b[$bAmtCol] ?? 0);
            if ($limit <= 0) continue;

            $period = 'monthly';
            if ($bPeriodCol !== '' && !empty($b[$bPeriodCol])) {
                $period = strtolower(trim((string)$b[$bPeriodCol]));
            }

            // Period window
            $from = null;
            $to = $today;

            if (str_contains($period, 'week')) {
                $from = date('Y-m-d', strtotime('-6 days'));
            } elseif (str_contains($period, 'year')) {
                $from = date('Y-01-01');
            } else {
                // default monthly
                $from = date('Y-m-01');
            }

            $cat = ($bCatCol !== '' ? trim((string)($b[$bCatCol] ?? '')) : '');
            $name = ($bNameCol !== '' ? trim((string)($b[$bNameCol] ?? '')) : '');
            if ($name === '' && $cat !== '') $name = $cat;
            if ($name === '') $name = 'Budget';

            // Sum expenses in period (and category if budget category exists AND transaction category exists)
            $sum = 0.0;

            try {
                $sql = "SELECT SUM(CAST(`$tAmtCol` AS DECIMAL(18,2))) AS s
                        FROM `transactions`
                        WHERE `$tUserCol` = ?
                          AND `$tTypeCol` = ?
                          AND DATE(`$tDateCol`) >= ?
                          AND DATE(`$tDateCol`) <= ?";

                $params = [$userId, 'expense', $from, $to];

                if ($cat !== '' && $tCatCol !== '') {
                    $sql .= " AND `$tCatCol` = ?";
                    $params[] = $cat;
                }

                $row = DB::query($sql, $params)->fetch();
                $sum = (float)($row['s'] ?? 0);
            } catch (\Throwable $e) {
                continue;
            }

            if ($sum <= 0) continue;

            $pct = ($limit > 0) ? (($sum / $limit) * 100.0) : 0.0;

            // Alerts
            if ($pct >= 100.0) {
                self::create(
                    $userId,
                    $name . ' exceeded',
                    'You have spent ' . number_format($sum, 2) . ' against a limit of ' . number_format($limit, 2) . ' (' . (int)$pct . '%).',
                    'danger',
                    ['module' => 'budgets', 'threshold' => 100, 'from' => $from, 'to' => $to, 'category' => $cat]
                );
            } elseif ($pct >= 80.0) {
                self::create(
                    $userId,
                    $name . ' at 80%',
                    'You have used ' . (int)$pct . '% of your budget (' . number_format($sum, 2) . ' of ' . number_format($limit, 2) . ').',
                    'warning',
                    ['module' => 'budgets', 'threshold' => 80, 'from' => $from, 'to' => $to, 'category' => $cat]
                );
            }
        }
    }

    /**
     * Subscription alerts:
     * - Alerts if due date is within next 3 days
     */
    private static function subscriptionAlerts(int $userId): void
    {
        $sCols = self::cols('subscriptions');
        if (empty($sCols)) return;

        $sUserCol = self::pickFirst($sCols, ['user_id','uid']);
        $sNameCol = self::pickFirst($sCols, ['name','title','provider']);
        $sDueCol  = self::pickFirst($sCols, ['next_due_date','next_date','due_date']);
        $sAmtCol  = self::pickFirst($sCols, ['amount','price']);
        $sActiveCol = self::pickFirst($sCols, ['is_active','active','status']);

        if ($sDueCol === '') return;

        $today = self::today();

        try {
            $select = ["`id`"];
            foreach ([$sUserCol,$sNameCol,$sDueCol,$sAmtCol,$sActiveCol] as $c) {
                if ($c !== '' && $c !== 'id') $select[] = "`$c`";
            }

            $sql = "SELECT " . implode(',', array_unique($select)) . " FROM `subscriptions`";
            $params = [];

            if ($sUserCol !== '') {
                $sql .= " WHERE `$sUserCol` = ?";
                $params[] = $userId;
            }

            $sql .= " ORDER BY `id` DESC LIMIT 200";
            $subs = DB::query($sql, $params)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return;
        }

        foreach ($subs as $s) {
            // active check (best-effort)
            if ($sActiveCol !== '') {
                $v = $s[$sActiveCol] ?? null;
                if ($sActiveCol === 'status') {
                    if (strtolower((string)$v) !== 'active') continue;
                } else {
                    if (!((string)$v === '1' || $v === 1 || $v === true)) continue;
                }
            }

            $due = self::normalizeDate((string)($s[$sDueCol] ?? ''));
            if ($due === null) continue;

            $days = self::daysBetween($today, $due);

            if ($days < 0) continue; // already past
            if ($days > 3) continue; // not soon

            $name = ($sNameCol !== '' ? trim((string)($s[$sNameCol] ?? '')) : '');
            if ($name === '') $name = 'Subscription';

            $amount = 0.0;
            if ($sAmtCol !== '' && is_numeric((string)($s[$sAmtCol] ?? ''))) {
                $amount = (float)$s[$sAmtCol];
            }

            $title = ($days === 0) ? ($name . ' due today') : ($name . ' due in ' . $days . ' day' . ($days === 1 ? '' : 's'));
            $msg = ($amount > 0)
                ? ('Renewal due on ' . $due . ' for ' . number_format($amount, 2) . '.')
                : ('Renewal due on ' . $due . '.');

            self::create(
                $userId,
                $title,
                $msg,
                ($days <= 1 ? 'warning' : 'info'),
                ['module' => 'subscriptions', 'due_date' => $due, 'subscription_id' => (int)($s['id'] ?? 0)]
            );
        }
    }

    /**
     * Goal alerts:
     * - Alerts if goal deadline is within 7 days and progress is < 80% of target
     */
    private static function goalAlerts(int $userId): void
    {
        $gCols = self::cols('goals');
        if (empty($gCols)) return;

        $gUserCol = self::pickFirst($gCols, ['user_id','uid']);
        $gNameCol = self::pickFirst($gCols, ['name','title']);
        $gTargetCol = self::pickFirst($gCols, ['target_amount','target','goal_amount']);
        $gProgCol = self::pickFirst($gCols, ['current_amount','current','progress_amount']);
        $gDueCol = self::pickFirst($gCols, ['deadline','due_date','target_date']);
        $gActiveCol = self::pickFirst($gCols, ['status','is_active','active']);

        if ($gTargetCol === '' || $gProgCol === '' || $gDueCol === '') return;

        $today = self::today();

        try {
            $select = ["`id`"];
            foreach ([$gUserCol,$gNameCol,$gTargetCol,$gProgCol,$gDueCol,$gActiveCol] as $c) {
                if ($c !== '' && $c !== 'id') $select[] = "`$c`";
            }

            $sql = "SELECT " . implode(',', array_unique($select)) . " FROM `goals`";
            $params = [];

            if ($gUserCol !== '') {
                $sql .= " WHERE `$gUserCol` = ?";
                $params[] = $userId;
            }

            $sql .= " ORDER BY `id` DESC LIMIT 200";
            $goals = DB::query($sql, $params)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return;
        }

        foreach ($goals as $g) {
            // active check (best-effort)
            if ($gActiveCol !== '') {
                $v = $g[$gActiveCol] ?? null;
                if ($gActiveCol === 'status') {
                    $st = strtolower((string)$v);
                    if ($st !== '' && $st !== 'active') continue;
                } else {
                    if ($v !== null && !((string)$v === '1' || $v === 1 || $v === true)) continue;
                }
            }

            $due = self::normalizeDate((string)($g[$gDueCol] ?? ''));
            if ($due === null) continue;

            $days = self::daysBetween($today, $due);
            if ($days < 0) continue;
            if ($days > 7) continue;

            $target = (float)($g[$gTargetCol] ?? 0);
            if ($target <= 0) continue;

            $progress = (float)($g[$gProgCol] ?? 0);
            $pct = ($target > 0) ? (($progress / $target) * 100.0) : 0.0;

            // Only alert if behind (below 80%)
            if ($pct >= 80.0) continue;

            $name = ($gNameCol !== '' ? trim((string)($g[$gNameCol] ?? '')) : '');
            if ($name === '') $name = 'Goal';

            $title = ($days === 0) ? ($name . ' deadline today') : ($name . ' deadline in ' . $days . ' day' . ($days === 1 ? '' : 's'));
            $msg = 'Progress is ' . number_format($progress, 2) . ' of ' . number_format($target, 2) . ' (' . (int)$pct . '%). Deadline: ' . $due . '.';

            self::create(
                $userId,
                $title,
                $msg,
                'warning',
                ['module' => 'goals', 'due_date' => $due, 'goal_id' => (int)($g['id'] ?? 0)]
            );
        }
    }
}
