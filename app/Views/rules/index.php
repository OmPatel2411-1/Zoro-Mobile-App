<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $rules
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base  = rtrim((string)($baseUrl ?? ''), '/');
$rules = is_array($rules ?? null) ? $rules : [];
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750; letter-spacing: -0.2px;">Rules</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Simple automations for categorization and notes.
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

    <!-- Add Rule -->
    <form method="post" action="<?= e($base) ?>/rules/save"
          style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; padding: 14px; margin-bottom: 14px;">
        <div style="font-weight: 650; margin-bottom: 10px;">Create rule</div>

        <div style="display:grid; grid-template-columns: 1fr; gap: 12px;">
            <div>
                <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Rule name</label>
                <input name="name" type="text" placeholder="e.g., Auto-tag Swiggy"
                       style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Condition: keyword in description</label>
                    <input name="match_text" type="text" placeholder="e.g., swiggy"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>

                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Condition: type (optional)</label>
                    <select name="match_type"
                            style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background:#fff;">
                        <option value="">Any</option>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Condition: min amount (optional)</label>
                    <input name="min_amount" type="text" placeholder="e.g., 200"
                           style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                </div>

                <div style="display:flex; align-items:flex-end; justify-content:flex-end;">
                    <button type="submit"
                            style="padding: 10px 14px; border-radius: 10px; border: none; background: #111; color: #fff; font-size: 14px; cursor:pointer;">
                        Save rule
                    </button>
                </div>
            </div>

            <div style="border-top: 1px solid #f3f4f6; padding-top: 12px;">
                <div style="font-weight: 650; margin-bottom: 10px;">Actions</div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Set category</label>
                        <input name="set_category" type="text" placeholder="e.g., Food"
                               style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Set note</label>
                        <input name="set_note" type="text" placeholder="e.g., Auto-categorized by rule"
                               style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                    </div>
                </div>

                <div style="margin-top: 10px; color:#9ca3af; font-size: 12px;">
                    (Next step: applying rules automatically on transaction create/edit.)
                </div>
            </div>
        </div>
    </form>

    <!-- List -->
    <div style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; overflow:hidden;">
        <div style="padding: 12px 14px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight: 650; font-size: 14px;">Your rules</div>
            <div style="color:#6b7280; font-size: 12px;">
                <?= count($rules) ?> item<?= count($rules) === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($rules)): ?>
            <div style="padding: 18px 14px; color:#6b7280; font-size: 14px;">
                No rules yet. Create one above.
            </div>
        <?php else: ?>
            <?php foreach ($rules as $r): ?>
                <?php
                $id = (int)($r['id'] ?? 0);

                $name = (string)($r['name'] ?? $r['title'] ?? 'Rule');
                $kw = (string)($r['match_text'] ?? $r['keyword'] ?? $r['contains'] ?? $r['query'] ?? '');
                $type = (string)($r['match_type'] ?? $r['type'] ?? '');
                $min = (string)($r['min_amount'] ?? $r['amount_min'] ?? $r['amount_gt'] ?? '');

                $setCat = (string)($r['set_category'] ?? $r['action_category'] ?? $r['target_category'] ?? '');
                $setNote = (string)($r['set_note'] ?? $r['action_note'] ?? $r['target_note'] ?? '');

                $activeRaw = $r['is_active'] ?? $r['active'] ?? $r['status'] ?? null;
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
                                <?= $active ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <div style="margin-top: 6px; color:#374151; font-size: 13px; line-height: 1.45;">
                            <div>
                                <span style="color:#6b7280;">If</span>
                                <?php if ($kw !== ''): ?>
                                    keyword contains <b><?= e($kw) ?></b>
                                <?php else: ?>
                                    (any keyword)
                                <?php endif; ?>

                                <?php if ($type !== ''): ?>
                                    and type is <b><?= e($type) ?></b>
                                <?php endif; ?>

                                <?php if ($min !== ''): ?>
                                    and amount ≥ <b><?= e($min) ?></b>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top: 4px;">
                                <span style="color:#6b7280;">Then</span>
                                <?php if ($setCat !== ''): ?>
                                    set category to <b><?= e($setCat) ?></b>
                                <?php endif; ?>
                                <?php if ($setNote !== ''): ?>
                                    <?= ($setCat !== '' ? 'and' : '') ?> set note to <b><?= e($setNote) ?></b>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="margin-top: 10px; display:flex; gap:10px; flex-wrap:wrap;">
                            <form method="post" action="<?= e($base) ?>/rules/toggle" style="margin:0;">
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
        Next: Apply rules automatically on transaction create/edit, and log to Notifications/Audit.
    </div>
</div>
