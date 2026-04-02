<?php
declare(strict_types=1);

/**
 * RuleEngine
 * ----------
 * Applies saved Rules to a transaction payload (schema-aware).
 *
 * Usage (later we will wire into TransactionsController create/edit):
 *   $engine = new RuleEngine();
 *   $result = $engine->applyToTransaction($userId, [
 *      'type'        => 'expense',
 *      'amount'      => 250,
 *      'category'    => 'Uncategorized',
 *      'description' => 'swiggy order #123',
 *   ]);
 *
 *   // $result = [
 *   //   'data' => [ ...possibly updated category/description... ],
 *   //   'applied_rules' => [1, 5],
 *   // ];
 */

final class RuleEngine
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

    private function isTruthy($v): bool
    {
        return ((string)$v === '1' || $v === 1 || $v === true);
    }

    private function normalizeText(string $s): string
    {
        $s = trim($s);
        $s = mb_strtolower($s, 'UTF-8');
        return $s;
    }

    /**
     * Fetch active rules for user (schema-aware).
     */
    private function fetchActiveRules(int $userId): array
    {
        $cols = $this->getTableColumns('rules');
        if (empty($cols)) return [];

        // Identify "active" column, if any
        $activeCol = null; // is_active | active | status
        if ($this->hasCol($cols, 'is_active')) $activeCol = 'is_active';
        elseif ($this->hasCol($cols, 'active')) $activeCol = 'active';
        elseif ($this->hasCol($cols, 'status')) $activeCol = 'status';

        // Identify columns we support (conditions + actions)
        $idCol = $this->hasCol($cols, 'id') ? 'id' : null;
        if ($idCol === null) return [];

        $userCol = $this->hasCol($cols, 'user_id') ? 'user_id' : null;

        $nameCol = null;
        if ($this->hasCol($cols, 'name')) $nameCol = 'name';
        elseif ($this->hasCol($cols, 'title')) $nameCol = 'title';

        $kwCol = null;
        foreach (['match_text', 'keyword', 'contains', 'query'] as $c) {
            if ($this->hasCol($cols, $c)) { $kwCol = $c; break; }
        }

        $typeCol = null;
        foreach (['match_type', 'type'] as $c) {
            if ($this->hasCol($cols, $c)) { $typeCol = $c; break; }
        }

        $minCol = null;
        foreach (['min_amount', 'amount_min', 'amount_gt'] as $c) {
            if ($this->hasCol($cols, $c)) { $minCol = $c; break; }
        }

        $setCatCol = null;
        foreach (['set_category', 'action_category', 'target_category'] as $c) {
            if ($this->hasCol($cols, $c)) { $setCatCol = $c; break; }
        }

        $setNoteCol = null;
        foreach (['set_note', 'action_note', 'target_note'] as $c) {
            if ($this->hasCol($cols, $c)) { $setNoteCol = $c; break; }
        }

        $priorityCol = $this->hasCol($cols, 'priority') ? 'priority' : null;

        // Build SELECT
        $select = [
            "`$idCol` AS id",
        ];

        if ($nameCol !== null)     $select[] = "`$nameCol` AS name";
        if ($kwCol !== null)       $select[] = "`$kwCol` AS kw";
        if ($typeCol !== null)     $select[] = "`$typeCol` AS match_type";
        if ($minCol !== null)      $select[] = "`$minCol` AS min_amount";
        if ($setCatCol !== null)   $select[] = "`$setCatCol` AS set_category";
        if ($setNoteCol !== null)  $select[] = "`$setNoteCol` AS set_note";
        if ($priorityCol !== null) $select[] = "`$priorityCol` AS priority";
        if ($activeCol !== null)   $select[] = "`$activeCol` AS active_v";

        $where = [];
        $params = [];

        if ($userCol !== null) {
            $where[] = "`$userCol` = ?";
            $params[] = $userId;
        }

        if ($activeCol !== null) {
            if ($activeCol === 'status') {
                $where[] = "`$activeCol` = 'active'";
            } else {
                $where[] = "`$activeCol` = 1";
            }
        }

        $sql = "SELECT " . implode(', ', $select) . " FROM `rules`";
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);

        // Priority first (high to low). If not available, newest first.
        if ($priorityCol !== null) $sql .= " ORDER BY `$priorityCol` DESC, `id` DESC";
        else $sql .= " ORDER BY `id` DESC";

        $rows = DB::query($sql, $params)->fetchAll();
        if (!is_array($rows)) return [];

        // If active column exists but values not strictly matching (edge cases),
        // we'll still filter safely in PHP.
        $out = [];
        foreach ($rows as $r) {
            if ($activeCol !== null && isset($r['active_v'])) {
                if ($activeCol === 'status') {
                    if (strtolower((string)$r['active_v']) !== 'active') continue;
                } else {
                    if (!$this->isTruthy($r['active_v'])) continue;
                }
            }
            $out[] = $r;
        }

        return $out;
    }

    /**
     * Apply rules to a transaction payload.
     *
     * Expected $tx keys:
     *  - type (income|expense) optional
     *  - amount numeric optional
     *  - category optional
     *  - description optional (or note)
     *
     * Returns:
     *  [
     *    'data' => updated payload,
     *    'applied_rules' => [ruleIds...]
     *  ]
     */
    public function applyToTransaction(int $userId, array $tx): array
    {
        $data = $tx;

        $type = $this->normalizeText((string)($data['type'] ?? ''));
        $desc = (string)($data['description'] ?? $data['note'] ?? '');
        $descN = $this->normalizeText($desc);

        $amountRaw = $data['amount'] ?? null;
        $amount = null;
        if ($amountRaw !== null && $amountRaw !== '' && is_numeric($amountRaw)) {
            $amount = (float)$amountRaw;
        }

        $rules = $this->fetchActiveRules($userId);
        if (empty($rules)) {
            return [
                'data' => $data,
                'applied_rules' => [],
            ];
        }

        $applied = [];

        foreach ($rules as $r) {
            $ruleId = (int)($r['id'] ?? 0);
            if ($ruleId <= 0) continue;

            $kw = $this->normalizeText((string)($r['kw'] ?? ''));
            $matchType = $this->normalizeText((string)($r['match_type'] ?? ''));
            $minAmount = null;
            if (isset($r['min_amount']) && $r['min_amount'] !== '' && is_numeric($r['min_amount'])) {
                $minAmount = (float)$r['min_amount'];
            }

            // Must have at least one condition (same validation as RulesController)
            $hasCondition = ($kw !== '') || ($matchType !== '') || ($minAmount !== null);
            if (!$hasCondition) continue;

            // Evaluate conditions
            $ok = true;

            if ($kw !== '') {
                if ($descN === '' || mb_strpos($descN, $kw, 0, 'UTF-8') === false) {
                    $ok = false;
                }
            }

            if ($ok && $matchType !== '') {
                if ($type === '' || $type !== $matchType) {
                    $ok = false;
                }
            }

            if ($ok && $minAmount !== null) {
                if ($amount === null || $amount < $minAmount) {
                    $ok = false;
                }
            }

            if (!$ok) continue;

            // Apply actions
            $setCategory = trim((string)($r['set_category'] ?? ''));
            $setNote     = trim((string)($r['set_note'] ?? ''));

            $hasAction = ($setCategory !== '') || ($setNote !== '');
            if (!$hasAction) continue;

            if ($setCategory !== '') {
                $data['category'] = $setCategory;
            }

            if ($setNote !== '') {
                // Prefer description key if present, otherwise note
                if (array_key_exists('description', $data)) {
                    $data['description'] = $setNote;
                } else {
                    $data['note'] = $setNote;
                }
            }

            $applied[] = $ruleId;
        }

        return [
            'data' => $data,
            'applied_rules' => $applied,
        ];
    }
}
