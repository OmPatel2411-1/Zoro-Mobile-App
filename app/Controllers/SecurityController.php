<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/DB.php';

final class SecurityController extends Controller
{
    public function sessions(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];

        $success = $_SESSION['flash_success'] ?? null;
        $error   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $sessions = [];
        try {
            if (!$this->tableExists('user_sessions')) {
                $error = $error ?: 'Sessions table not found.';
                $sessions = [];
            } else {
                $cols = $this->getTableColumns('user_sessions');

                // Required-ish: user_id
                if (!in_array('user_id', $cols, true)) {
                    $error = $error ?: 'Sessions table missing user_id column.';
                    $sessions = [];
                } else {
                    // Build safe SELECT using only existing columns
                    $select = ['id'];

                    foreach ([
                        'session_id',
                        'sid',
                        'token',
                        'ip_address',
                        'ip',
                        'ip_addr',
                        'user_agent',
                        'ua',
                        'created_at',
                        'last_active_at',
                        'updated_at',
                        'is_active',
                        'active',
                        'status'
                    ] as $c) {
                        if (in_array($c, $cols, true) && !in_array($c, $select, true)) {
                            $select[] = $c;
                        }
                    }

                    // Order by best available timestamp
                    $orderCol = null;
                    foreach (['last_active_at', 'updated_at', 'created_at', 'id'] as $c) {
                        if (in_array($c, $cols, true)) { $orderCol = $c; break; }
                    }
                    if ($orderCol === null) {
                        $orderCol = 'id';
                    }

                    $sql = "SELECT " . implode(', ', array_map([$this, 'quoteIdent'], $select))
                         . " FROM " . $this->quoteIdent('user_sessions')
                         . " WHERE " . $this->quoteIdent('user_id') . " = ?"
                         . " ORDER BY " . $this->quoteIdent($orderCol) . " DESC";

                    $sessions = DB::query($sql, [$userId])->fetchAll();
                }
            }
        } catch (Throwable $e) {
            $error = 'Could not load sessions.';
            $sessions = [];
        }

        $this->view('security/sessions', [
            'sessions' => is_array($sessions) ? $sessions : [],
            'success'  => $success,
            'error'    => $error,
        ], 'app');
    }

    /**
     * POST /security/logout-all
     * Ends sessions everywhere and logs the current session out too.
     */
    public function logoutAll(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int)$_SESSION['user_id'];

        try {
            if ($this->tableExists('user_sessions')) {
                $cols = $this->getTableColumns('user_sessions');

                if (in_array('user_id', $cols, true)) {
                    // Prefer marking inactive if column exists
                    $inactiveCol = null;
                    foreach (['is_active', 'active', 'status'] as $c) {
                        if (in_array($c, $cols, true)) { $inactiveCol = $c; break; }
                    }

                    if ($inactiveCol === 'status') {
                        // status column might be varchar; set to 'inactive'
                        DB::query(
                            "UPDATE " . $this->quoteIdent('user_sessions')
                            . " SET " . $this->quoteIdent('status') . " = ?"
                            . " WHERE " . $this->quoteIdent('user_id') . " = ?",
                            ['inactive', $userId]
                        );
                    } elseif ($inactiveCol !== null) {
                        // boolean/int columns
                        DB::query(
                            "UPDATE " . $this->quoteIdent('user_sessions')
                            . " SET " . $this->quoteIdent($inactiveCol) . " = 0"
                            . " WHERE " . $this->quoteIdent('user_id') . " = ?",
                            [$userId]
                        );
                    } else {
                        // Fallback: delete all rows for user
                        DB::query(
                            "DELETE FROM " . $this->quoteIdent('user_sessions')
                            . " WHERE " . $this->quoteIdent('user_id') . " = ?",
                            [$userId]
                        );
                    }
                }
            }
        } catch (Throwable $e) {
            // Don't block logout; still end current session
        }

        // Log out current session
        session_unset();
        session_destroy();
        session_start();

        $_SESSION['flash_success'] = 'Logged out on all devices. Please login again.';

        $this->redirect('/login');
    }

    /* =========================
       Helpers (schema-aware)
       ========================= */

    private function tableExists(string $table): bool
    {
        try {
            DB::query("SELECT 1 FROM " . $this->quoteIdent($table) . " LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    private function getTableColumns(string $table): array
    {
        $rows = DB::query("DESCRIBE " . $this->quoteIdent($table))->fetchAll();
        $cols = [];
        foreach ($rows as $r) {
            if (isset($r['Field'])) {
                $cols[] = (string)$r['Field'];
            }
        }
        return $cols;
    }

    private function quoteIdent(string $name): string
    {
        // Basic backtick quoting; safe for column/table identifiers.
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
