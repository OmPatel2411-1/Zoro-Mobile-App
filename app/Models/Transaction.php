<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/DB.php';

/**
 * Transaction Model (Column-Aware)
 * --------------------------------
 * Detects schema differences and aliases to:
 * - occurred_on
 * - category
 * - description
 * - type
 * - amount
 */
final class Transaction
{
    private static bool $schemaLoaded = false;

    private static string $dateCol = 'id';
    private static string $categoryCol = '';
    private static string $descriptionCol = '';
    private static string $typeCol = '';
    private static string $amountCol = '';

    // ----- Schema support helpers -----
    public static function supportsDate(): bool { self::loadSchema(); return self::$dateCol !== 'id'; }
    public static function supportsCategory(): bool { self::loadSchema(); return self::$categoryCol !== ''; }
    public static function supportsDescription(): bool { self::loadSchema(); return self::$descriptionCol !== ''; }
    public static function supportsType(): bool { self::loadSchema(); return self::$typeCol !== ''; }
    public static function supportsAmount(): bool { self::loadSchema(); return self::$amountCol !== ''; }

    /**
     * Fetch transactions for a user with optional filters.
     * Filters keys: q, type, category, from, to
     */
    public static function allForUser(int $userId, array $filters = []): array
    {
        self::loadSchema();

        $select = ["t.*"];

        // Aliases
        $select[] = (self::$dateCol !== 'id')
            ? "t.`" . self::$dateCol . "` AS occurred_on"
            : "NULL AS occurred_on";

        $select[] = (self::$categoryCol !== '')
            ? "t.`" . self::$categoryCol . "` AS category"
            : "'' AS category";

        $select[] = (self::$descriptionCol !== '')
            ? "t.`" . self::$descriptionCol . "` AS description"
            : "'' AS description";

        $select[] = (self::$typeCol !== '')
            ? "t.`" . self::$typeCol . "` AS type"
            : "'expense' AS type";

        $select[] = (self::$amountCol !== '')
            ? "t.`" . self::$amountCol . "` AS amount"
            : "0 AS amount";

        $sql = "SELECT " . implode(", ", $select) . " FROM transactions t WHERE t.user_id = ?";
        $params = [$userId];

        // Filters
        $type = (string)($filters['type'] ?? '');
        if (($type === 'income' || $type === 'expense') && self::$typeCol !== '') {
            $sql .= " AND t.`" . self::$typeCol . "` = ?";
            $params[] = $type;
        }

        $category = trim((string)($filters['category'] ?? ''));
        if ($category !== '' && self::$categoryCol !== '') {
            $sql .= " AND t.`" . self::$categoryCol . "` = ?";
            $params[] = $category;
        }

        $from = trim((string)($filters['from'] ?? ''));
        if ($from !== '' && self::isDate($from) && self::$dateCol !== 'id') {
            $sql .= " AND t.`" . self::$dateCol . "` >= ?";
            $params[] = $from;
        }

        $to = trim((string)($filters['to'] ?? ''));
        if ($to !== '' && self::isDate($to) && self::$dateCol !== 'id') {
            $sql .= " AND t.`" . self::$dateCol . "` <= ?";
            $params[] = $to;
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $parts = [];
            if (self::$categoryCol !== '') {
                $parts[] = "t.`" . self::$categoryCol . "` LIKE ?";
                $params[] = '%' . $q . '%';
            }
            if (self::$descriptionCol !== '') {
                $parts[] = "t.`" . self::$descriptionCol . "` LIKE ?";
                $params[] = '%' . $q . '%';
            }
            if (!empty($parts)) {
                $sql .= " AND (" . implode(" OR ", $parts) . ")";
            }
        }

        // Ordering
        if (self::$dateCol !== 'id') {
            $sql .= " ORDER BY t.`" . self::$dateCol . "` DESC, t.id DESC";
        } else {
            $sql .= " ORDER BY t.id DESC";
        }

        $sql .= " LIMIT 500";

        return DB::query($sql, $params)->fetchAll() ?: [];
    }

    /**
     * Distinct categories for a user (for filters dropdown).
     */
    public static function categoriesForUser(int $userId): array
    {
        self::loadSchema();
        if (self::$categoryCol === '') return [];

        $rows = DB::query(
            "SELECT DISTINCT t.`" . self::$categoryCol . "` AS category
             FROM transactions t
             WHERE t.user_id = ?
               AND t.`" . self::$categoryCol . "` IS NOT NULL
               AND TRIM(t.`" . self::$categoryCol . "`) <> ''
             ORDER BY category ASC
             LIMIT 300",
            [$userId]
        )->fetchAll() ?: [];

        $cats = [];
        foreach ($rows as $r) {
            $c = trim((string)($r['category'] ?? ''));
            if ($c !== '') $cats[] = $c;
        }
        return $cats;
    }

    /**
     * Find one transaction for a user (ownership enforced).
     */
    public static function find(int $id, int $userId): ?array
    {
        self::loadSchema();

        $select = ["t.*"];
        $select[] = (self::$dateCol !== 'id') ? "t.`" . self::$dateCol . "` AS occurred_on" : "NULL AS occurred_on";
        $select[] = (self::$categoryCol !== '') ? "t.`" . self::$categoryCol . "` AS category" : "'' AS category";
        $select[] = (self::$descriptionCol !== '') ? "t.`" . self::$descriptionCol . "` AS description" : "'' AS description";
        $select[] = (self::$typeCol !== '') ? "t.`" . self::$typeCol . "` AS type" : "'expense' AS type";
        $select[] = (self::$amountCol !== '') ? "t.`" . self::$amountCol . "` AS amount" : "0 AS amount";

        $sql = "SELECT " . implode(", ", $select) . "
                FROM transactions t
                WHERE t.id = ? AND t.user_id = ?
                LIMIT 1";

        $row = DB::query($sql, [$id, $userId])->fetch();
        return $row ?: null;
    }

    /**
     * Create transaction. Inserts only columns that exist.
     */
    public static function create(int $userId, array $data): int
    {
        self::loadSchema();

        $cols = ['user_id'];
        $vals = ['?'];
        $params = [$userId];

        if (self::$typeCol !== '') {
            $cols[] = '`' . self::$typeCol . '`';
            $vals[] = '?';
            $params[] = (string)($data['type'] ?? 'expense');
        }

        if (self::$amountCol !== '') {
            $cols[] = '`' . self::$amountCol . '`';
            $vals[] = '?';
            $params[] = (float)($data['amount'] ?? 0);
        }

        if (self::$categoryCol !== '') {
            $cols[] = '`' . self::$categoryCol . '`';
            $vals[] = '?';
            $params[] = (string)($data['category'] ?? '');
        }

        if (self::$descriptionCol !== '') {
            $cols[] = '`' . self::$descriptionCol . '`';
            $vals[] = '?';
            $params[] = (string)($data['description'] ?? '');
        }

        if (self::$dateCol !== 'id') {
            $cols[] = '`' . self::$dateCol . '`';
            $vals[] = '?';
            $params[] = (string)($data['occurred_on'] ?? date('Y-m-d'));
        }

        if (self::columnExists('transactions', 'created_at')) {
            $cols[] = 'created_at';
            $vals[] = 'NOW()';
        }

        $sql = "INSERT INTO transactions (" . implode(',', $cols) . ")
                VALUES (" . implode(',', $vals) . ")";

        DB::query($sql, $params);
        return (int)DB::lastInsertId();
    }

    /**
     * Update transaction (ownership enforced).
     */
    public static function update(int $id, int $userId, array $data): bool
    {
        self::loadSchema();

        $sets = [];
        $params = [];

        if (self::$typeCol !== '') {
            $sets[] = '`' . self::$typeCol . '` = ?';
            $params[] = (string)($data['type'] ?? 'expense');
        }
        if (self::$amountCol !== '') {
            $sets[] = '`' . self::$amountCol . '` = ?';
            $params[] = (float)($data['amount'] ?? 0);
        }
        if (self::$categoryCol !== '') {
            $sets[] = '`' . self::$categoryCol . '` = ?';
            $params[] = (string)($data['category'] ?? '');
        }
        if (self::$descriptionCol !== '') {
            $sets[] = '`' . self::$descriptionCol . '` = ?';
            $params[] = (string)($data['description'] ?? '');
        }
        if (self::$dateCol !== 'id') {
            $sets[] = '`' . self::$dateCol . '` = ?';
            $params[] = (string)($data['occurred_on'] ?? date('Y-m-d'));
        }

        if (empty($sets)) return true;

        $params[] = $id;
        $params[] = $userId;

        DB::query(
            "UPDATE transactions SET " . implode(', ', $sets) . " WHERE id = ? AND user_id = ?",
            $params
        );

        return true;
    }

    public static function delete(int $id, int $userId): void
    {
        DB::query("DELETE FROM transactions WHERE id = ? AND user_id = ?", [$id, $userId]);
    }

    public static function bulkDelete(int $userId, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
        if (empty($ids)) return;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$userId]);

        DB::query("DELETE FROM transactions WHERE id IN ($placeholders) AND user_id = ?", $params);
    }

    public static function bulkSetCategory(int $userId, array $ids, string $category): void
    {
        self::loadSchema();
        if (self::$categoryCol === '') return;

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
        if (empty($ids)) return;

        $category = trim($category);
        if ($category === '') return;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$category], $ids, [$userId]);

        DB::query(
            "UPDATE transactions
             SET `" . self::$categoryCol . "` = ?
             WHERE id IN ($placeholders) AND user_id = ?",
            $params
        );
    }

    // ----- Schema detection -----
    private static function loadSchema(): void
    {
        if (self::$schemaLoaded) return;

        $cols = DB::query("SHOW COLUMNS FROM transactions")->fetchAll() ?: [];
        $names = [];
        foreach ($cols as $c) {
            if (!empty($c['Field'])) $names[] = (string)$c['Field'];
        }

        self::$dateCol = self::pickColumn($names, [
            'occurred_on', 'transaction_date', 'txn_date', 'date', 'occurred_at', 'created_at'
        ], $cols, true);

        self::$categoryCol = self::pickColumn($names, [
            'category', 'txn_category', 'transaction_category', 'merchant_category', 'cat'
        ], $cols, false);

        self::$descriptionCol = self::pickColumn($names, [
            'description', 'note', 'notes', 'remarks', 'narration', 'details', 'memo'
        ], $cols, false);

        self::$typeCol = self::pickColumn($names, [
            'type', 'txn_type', 'transaction_type', 'entry_type'
        ], $cols, false);

        self::$amountCol = self::pickColumn($names, [
            'amount', 'txn_amount', 'transaction_amount', 'value', 'total'
        ], $cols, false);

        self::$schemaLoaded = true;
    }

    private static function pickColumn(array $names, array $candidates, array $cols, bool $preferDateType): string
    {
        foreach ($candidates as $cand) {
            if (in_array($cand, $names, true)) return $cand;
        }

        if ($preferDateType) {
            foreach ($cols as $c) {
                $field = (string)($c['Field'] ?? '');
                $type  = strtolower((string)($c['Type'] ?? ''));
                if ($field !== '' && (str_contains($type, 'date') || str_contains($type, 'timestamp'))) {
                    return $field;
                }
            }
            return 'id';
        }

        return '';
    }

    private static function columnExists(string $table, string $column): bool
    {
        $rows = DB::query("SHOW COLUMNS FROM `$table` LIKE ?", [$column])->fetchAll() ?: [];
        return !empty($rows);
    }

    private static function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y);
    }
}
