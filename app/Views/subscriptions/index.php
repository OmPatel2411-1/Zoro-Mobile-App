<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $subscriptions
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');
$subscriptions = is_array($subscriptions ?? null) ? $subscriptions : [];
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750; letter-spacing: -0.2px;">Subscriptions</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Track recurring payments and renewal dates.
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

    <!-- Add / Edit -->
    <form method="post" action="<?= e($base) ?>/subscriptions/save"
          style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; padding: 14px; margin-bottom: 14px;">
        <div style="font-weight: 650; margin-bottom: 10px;">Add subscription</div>

        <div style="display:grid; grid-template-columns: 1fr; gap: 12px;">
            <input type="hidden" name="id" value="">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Name</label>
                    <input name="name" type="text" placeholder="e.g., Netflix"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>

                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Provider (optional)</label>
                    <input name="provider" type="text" placeholder="e.g., Netflix Inc."
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Category (optional)</label>
                    <input name="category" type="text" placeholder="e.g., Entertainment"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>

                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Amount</label>
                    <input name="amount" type="text" placeholder="e.g., 499"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Billing cycle</label>
                    <select name="cycle" style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background:#fff;">
                        <option value="monthly" selected>Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Next due date (optional)</label>
                    <input name="next_due_date" type="date"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>
            </div>

            <div>
                <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Notes (optional)</label>
                <textarea name="notes" rows="2" placeholder="Any details..."
                          style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; resize: vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                <button type="submit"
                        style="padding: 10px 14px; border-radius: 10px; border: none; background: #111; color: #fff; font-size: 14px; cursor:pointer;">
                    Save subscription
                </button>
            </div>
        </div>
    </form>

    <!-- List -->
    <div style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; overflow:hidden;">
        <div style="padding: 12px 14px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight: 650; font-size: 14px;">Your subscriptions</div>
            <div style="color:#6b7280; font-size: 12px;">
                <?= count($subscriptions) ?> item<?= count($subscriptions) === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($subscriptions)): ?>
            <div style="padding: 18px 14px; color:#6b7280; font-size: 14px;">
                No subscriptions yet. Add one above.
            </div>
        <?php else: ?>
            <?php foreach ($subscriptions as $s): ?>
                <?php
                $id = (int)($s['id'] ?? 0);

                $name = (string)($s['name'] ?? $s['title'] ?? 'Subscription');
                $provider = (string)($s['provider'] ?? '');
                $category = (string)($s['category'] ?? '');
                $amount = (string)($s['amount'] ?? $s['price'] ?? '');
                $cycle = (string)($s['billing_cycle'] ?? $s['cycle'] ?? $s['frequency'] ?? $s['interval'] ?? 'monthly');
                $due = (string)($s['next_due_date'] ?? $s['next_date'] ?? $s['due_date'] ?? '');

                $activeRaw = $s['is_active'] ?? $s['active'] ?? $s['status'] ?? null;
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
                                <?= e($name) ?>
                            </div>
                            <span style="display:inline-block; padding: 5px 10px; border-radius: 999px; border:1px solid #e5e7eb; color:#6b7280; font-size: 12px;">
                                <?= e($cycle) ?>
                            </span>
                            <span style="display:inline-block; padding: 5px 10px; border-radius: 999px; border:1px solid #e5e7eb; color:#6b7280; font-size: 12px;">
                                <?= $active ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <div style="margin-top: 6px; color:#374151; font-size: 13px;">
                            <?php if ($provider !== ''): ?>
                                <span style="color:#6b7280;">Provider:</span> <?= e($provider) ?>
                                <span style="margin: 0 8px; color:#e5e7eb;">|</span>
                            <?php endif; ?>
                            <?php if ($category !== ''): ?>
                                <span style="color:#6b7280;">Category:</span> <?= e($category) ?>
                                <span style="margin: 0 8px; color:#e5e7eb;">|</span>
                            <?php endif; ?>
                            <span style="color:#6b7280;">Amount:</span> <?= e($amount !== '' ? $amount : '-') ?>
                            <?php if ($due !== ''): ?>
                                <span style="margin: 0 8px; color:#e5e7eb;">|</span>
                                <span style="color:#6b7280;">Next due:</span> <?= e($due) ?>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 10px; display:flex; gap:10px; flex-wrap:wrap;">
                            <form method="post" action="<?= e($base) ?>/subscriptions/toggle" style="margin:0;">
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <button type="submit"
                                        style="padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background:#111; color:#fff; font-size: 13px; cursor:pointer;">
                                    Toggle active
                                </button>
                            </form>
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
        Next: auto-reminders + due alerts in Notifications.
    </div>
</div>
