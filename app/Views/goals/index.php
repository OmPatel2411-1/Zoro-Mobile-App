<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $goals
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');
$goals = is_array($goals ?? null) ? $goals : [];

function asFloat($v): float {
    if ($v === null) return 0.0;
    if (is_numeric($v)) return (float)$v;
    return 0.0;
}
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750; letter-spacing: -0.2px;">Goals</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Save towards targets and track progress.
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

    <!-- Add Goal -->
    <form method="post" action="<?= e($base) ?>/goals/save"
          style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; padding: 14px; margin-bottom: 14px;">
        <div style="font-weight: 650; margin-bottom: 10px;">Add goal</div>

        <div style="display:grid; grid-template-columns: 1fr; gap: 12px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Goal name</label>
                    <input name="name" type="text" placeholder="e.g., Emergency Fund"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>

                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Target amount</label>
                    <input name="target_amount" type="text" placeholder="e.g., 100000"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Deadline (optional)</label>
                    <input name="deadline" type="date"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>

                <div style="display:flex; align-items:flex-end; justify-content:flex-end;">
                    <button type="submit"
                            style="padding: 10px 14px; border-radius: 10px; border: none; background: #111; color: #fff; font-size: 14px; cursor:pointer;">
                        Save goal
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- List -->
    <div style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; overflow:hidden;">
        <div style="padding: 12px 14px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight: 650; font-size: 14px;">Your goals</div>
            <div style="color:#6b7280; font-size: 12px;">
                <?= count($goals) ?> item<?= count($goals) === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($goals)): ?>
            <div style="padding: 18px 14px; color:#6b7280; font-size: 14px;">
                No goals yet. Add one above.
            </div>
        <?php else: ?>
            <?php foreach ($goals as $g): ?>
                <?php
                $id = (int)($g['id'] ?? 0);
                $name = (string)($g['name'] ?? $g['title'] ?? 'Goal');

                $target = asFloat($g['target_amount'] ?? $g['target'] ?? $g['goal_amount'] ?? 0);
                $current = asFloat($g['current_amount'] ?? $g['current'] ?? $g['progress_amount'] ?? 0);

                $deadline = (string)($g['deadline'] ?? $g['due_date'] ?? $g['target_date'] ?? '');

                $pct = 0.0;
                if ($target > 0) {
                    $pct = ($current / $target) * 100.0;
                    if ($pct < 0) $pct = 0;
                    if ($pct > 100) $pct = 100;
                }
                ?>
                <div style="padding: 14px; border-bottom:1px solid #f3f4f6;">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                        <div style="min-width:0;">
                            <div style="font-weight: 750; font-size: 14px; letter-spacing:-0.1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= e($name) ?>
                            </div>

                            <div style="margin-top: 6px; color:#374151; font-size: 13px;">
                                <span style="color:#6b7280;">Progress:</span>
                                <?= e((string)$current) ?> / <?= e((string)$target) ?>
                                <span style="margin: 0 8px; color:#e5e7eb;">|</span>
                                <span style="color:#6b7280;">Done:</span> <?= e((string)round($pct, 1)) ?>%
                                <?php if ($deadline !== ''): ?>
                                    <span style="margin: 0 8px; color:#e5e7eb;">|</span>
                                    <span style="color:#6b7280;">Deadline:</span> <?= e($deadline) ?>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top: 10px; height: 10px; border-radius: 999px; background:#f3f4f6; overflow:hidden;">
                                <div style="height: 10px; width: <?= e((string)$pct) ?>%; background:#111;"></div>
                            </div>

                            <form method="post" action="<?= e($base) ?>/goals/update-progress" style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <input name="current_amount" type="text" placeholder="Update current amount"
                                       style="padding: 9px 10px; border-radius: 10px; border: 1px solid #e5e7eb; font-size: 13px; min-width: 180px;">
                                <button type="submit"
                                        style="padding: 9px 12px; border-radius: 10px; border: 1px solid #e5e7eb; background:#111; color:#fff; font-size: 13px; cursor:pointer;">
                                    Update
                                </button>
                            </form>
                        </div>

                        <div style="color:#9ca3af; font-size: 12px; white-space:nowrap;">
                            #<?= (int)$id ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top: 12px; color:#9ca3af; font-size: 12px;">
        Next: reminders when you’re behind target (Notifications).
    </div>
</div>
