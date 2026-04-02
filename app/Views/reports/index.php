<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $totals
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');

$totals = is_array($totals ?? null) ? $totals : ['income'=>0,'expense'=>0,'count'=>0];

$income  = (float)($totals['income'] ?? 0);
$expense = (float)($totals['expense'] ?? 0);
$count   = (int)($totals['count'] ?? 0);

$net = $income - $expense;

$month = trim((string)($_GET['month'] ?? ''));
if ($month === '') {
    $month = date('Y-m');
}
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750; letter-spacing: -0.2px;">Reports</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Quick totals and exports (CSV / Print-PDF).
            </div>
        </div>

        <a href="<?= e($base) ?>/dashboard"
           style="display:inline-block; padding: 10px 12px; border-radius: 10px; border: 1px solid #e5e7eb; text-decoration:none; color:#111; font-size: 14px;">
            Back
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div style="padding: 10px 12px; border-radius: 10px; border: 1px solid #fee2e2; background: #fef2f2; color: #991b1b; font-size: 13px; margin-bottom: 12px;">
            <?= e((string)$error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="padding: 10px 12px; border-radius: 10px; border: 1px solid #dcfce7; background: #f0fdf4; color: #166534; font-size: 13px; margin-bottom: 12px;">
            <?= e((string)$success) ?>
        </div>
    <?php endif; ?>

    <!-- Month Filter -->
    <form method="get" action="<?= e($base) ?>/reports"
          style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; padding: 14px; margin-bottom: 14px;">
        <div style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; justify-content:space-between;">
            <div style="min-width: 220px;">
                <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Month</label>
                <input name="month" type="month" value="<?= e($month) ?>"
                       style="width: 240px; max-width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit"
                        style="padding: 10px 14px; border-radius: 10px; border: 1px solid #e5e7eb; background:#111; color:#fff; font-size: 14px; cursor:pointer;">
                    Apply
                </button>

                <a href="<?= e($base) ?>/reports/export-csv?month=<?= e($month) ?>"
                   style="display:inline-block; padding: 10px 14px; border-radius: 10px; border: 1px solid #e5e7eb; text-decoration:none; color:#111; font-size: 14px;">
                    Export CSV
                </a>

                <a href="<?= e($base) ?>/reports/export-pdf?month=<?= e($month) ?>"
                   target="_blank"
                   style="display:inline-block; padding: 10px 14px; border-radius: 10px; border: 1px solid #e5e7eb; text-decoration:none; color:#111; font-size: 14px;">
                    Print / Save PDF
                </a>
            </div>
        </div>
    </form>

    <!-- Totals -->
    <div style="display:grid; grid-template-columns: 1fr; gap: 12px;">
        <div style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; padding: 14px;">
            <div style="font-weight: 650; margin-bottom: 10px;">Summary (<?= e($month) ?>)</div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="border:1px solid #f3f4f6; border-radius: 12px; padding: 12px;">
                    <div style="color:#6b7280; font-size: 12px;">Income</div>
                    <div style="margin-top: 6px; font-size: 18px; font-weight: 800;">
                        <?= e(number_format($income, 2)) ?>
                    </div>
                </div>

                <div style="border:1px solid #f3f4f6; border-radius: 12px; padding: 12px;">
                    <div style="color:#6b7280; font-size: 12px;">Expense</div>
                    <div style="margin-top: 6px; font-size: 18px; font-weight: 800;">
                        <?= e(number_format($expense, 2)) ?>
                    </div>
                </div>

                <div style="border:1px solid #f3f4f6; border-radius: 12px; padding: 12px;">
                    <div style="color:#6b7280; font-size: 12px;">Net</div>
                    <div style="margin-top: 6px; font-size: 18px; font-weight: 800;">
                        <?= e(number_format($net, 2)) ?>
                    </div>
                </div>

                <div style="border:1px solid #f3f4f6; border-radius: 12px; padding: 12px;">
                    <div style="color:#6b7280; font-size: 12px;">Transactions</div>
                    <div style="margin-top: 6px; font-size: 18px; font-weight: 800;">
                        <?= (int)$count ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 10px; color:#9ca3af; font-size: 12px;">
                Note: Totals are calculated from your transactions table using schema-detection.
            </div>
        </div>
    </div>

    <div style="margin-top: 12px; color:#9ca3af; font-size: 12px;">
        Next: category breakdown charts + exports (later) without external libraries.
    </div>
</div>
