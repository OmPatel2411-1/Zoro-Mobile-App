<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $budgets
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');
$budgets = is_array($budgets ?? null) ? $budgets : [];
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750; letter-spacing: -0.2px;">Budgets</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Create simple category budgets. (Advanced analytics later)
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

    <!-- Create / Edit -->
    <form method="post" action="<?= e($base) ?>/budgets/save"
          style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; padding: 14px; margin-bottom: 14px;">
        <div style="font-weight: 650; margin-bottom: 10px;">Add budget</div>

        <div style="display:grid; grid-template-columns: 1fr; gap: 12px;">
            <input type="hidden" name="id" value="">

            <div>
                <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Name (optional)</label>
                <input name="name" type="text" placeholder="e.g., Monthly Groceries"
                       style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
            </div>

            <div>
                <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Category (optional)</label>
                <input name="category" type="text" placeholder="e.g., Groceries"
                       style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Period</label>
                    <select name="period" style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background:#fff;">
                        <option value="monthly" selected>Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Limit amount</label>
                    <input name="amount" type="text" placeholder="e.g., 5000"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                <button type="submit"
                        style="padding: 10px 14px; border-radius: 10px; border: none; background: #111; color: #fff; font-size: 14px; cursor:pointer;">
                    Save budget
                </button>
            </div>
        </div>
    </form>

    <!-- List -->
    <div style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; overflow:hidden;">
        <div style="padding: 12px 14px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight: 650; font-size: 14px;">Your budgets</div>
            <div style="color:#6b7280; font-size: 12px;">
                <?= count($budgets) ?> item<?= count($budgets) === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($budgets)): ?>
            <div style="padding: 18px 14px; color:#6b7280; font-size: 14px;">
                No budgets yet. Add one above.
            </div>
        <?php else: ?>
            <?php foreach ($budgets as $b): ?>
                <?php
                $id = (int)($b['id'] ?? 0);
                $name = (string)($b['name'] ?? $b['title'] ?? '');
                $category = (string)($b['category'] ?? '');
                $amount = (string)($b['amount'] ?? $b['limit_amount'] ?? '');
                $period = (string)($b['period'] ?? $b['frequency'] ?? '');
                $activeRaw = $b['is_active'] ?? $b['active'] ?? $b['status'] ?? null;
                $active = true;
                if ($activeRaw !== null) {
                    if ((string)$activeRaw === '0' || $activeRaw === 0 || $activeRaw === false) $active = false;
                    if ((string)$activeRaw === 'inactive') $active = false;
                }
                ?>
                <div style="padding: 14px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                    <div style="min-width:0;">
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <div style="font-weight: 750; font-size: 14px; letter-spacing:-0.1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= e($name !== '' ? $name : ($category !== '' ? $category : 'Budget')) ?>
                            </div>
                            <span style="display:inline-block; padding: 5px 10px; border-radius: 999px; border:1px solid #e5e7eb; color:#6b7280; font-size: 12px;">
                                <?= e($period !== '' ? $period : 'monthly') ?>
                            </span>
                            <span style="display:inline-block; padding: 5px 10px; border-radius: 999px; border:1px solid #e5e7eb; color:#6b7280; font-size: 12px;">
                                <?= $active ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <div style="margin-top: 6px; color:#374151; font-size: 13px;">
                            <?php if ($category !== ''): ?>
                                <span style="color:#6b7280;">Category:</span> <?= e($category) ?>
                                <span style="margin: 0 8px; color:#e5e7eb;">|</span>
                            <?php endif; ?>
                            <span style="color:#6b7280;">Limit:</span> <?= e($amount !== '' ? $amount : '-') ?>
                        </div>
                    </div>

                    <div style="color:#9ca3af; font-size: 12px; white-space:nowrap;">
                        #<?= (int)$id ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top: 12px; color:#9ca3af; font-size: 12px;">
        Next: budget tracking + alerts will be added under Notifications/Reports.
    </div>
</div>
