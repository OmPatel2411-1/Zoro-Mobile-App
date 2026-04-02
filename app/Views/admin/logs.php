<?php
declare(strict_types=1);

/**
 * Admin – Audit Logs
 *
 * @var string $baseUrl
 * @var array  $logs
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');
$logs = is_array($logs ?? null) ? $logs : [];
?>
<div style="max-width: 1100px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750;">Admin · Audit Logs</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                System actions and security events.
            </div>
        </div>

        <a href="<?= e($base) ?>/admin"
           style="padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; text-decoration:none; color:#111;">
            Back
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div style="padding:10px 12px; border-radius:10px; border:1px solid #fee2e2; background:#fef2f2; color:#991b1b; margin-bottom:12px;">
            <?= e((string)$error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="padding:10px 12px; border-radius:10px; border:1px solid #dcfce7; background:#f0fdf4; color:#166534; margin-bottom:12px;">
            <?= e((string)$success) ?>
        </div>
    <?php endif; ?>

    <div style="border:1px solid #e5e7eb; border-radius:14px; background:#fff; overflow:hidden;">
        <div style="padding:12px 14px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight:650;">Logs</div>
            <div style="color:#6b7280; font-size:12px;"><?= count($logs) ?> item<?= count($logs) === 1 ? '' : 's' ?></div>
        </div>

        <?php if (empty($logs)): ?>
            <div style="padding:16px; color:#6b7280;">No logs found.</div>
        <?php else: ?>
            <?php foreach ($logs as $l): ?>
                <?php
                $id = (int)($l['id'] ?? 0);
                $type = (string)($l['type'] ?? $l['action'] ?? $l['event'] ?? 'event');
                $message = (string)($l['message'] ?? $l['details'] ?? $l['data'] ?? '');
                $userId = (string)($l['user_id'] ?? '');
                $ip = (string)($l['ip_address'] ?? $l['ip'] ?? '');
                $created = (string)($l['created_at'] ?? $l['created_on'] ?? '');
                ?>
                <div style="padding:14px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; gap:12px;">
                    <div style="min-width:0;">
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <div style="font-weight:750; font-size:13px;">
                                <?= e($type) ?>
                            </div>
                            <?php if ($userId !== ''): ?>
                                <span style="display:inline-block; padding:5px 10px; border-radius:999px; border:1px solid #e5e7eb; color:#6b7280; font-size:12px;">
                                    user #<?= e($userId) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($ip !== ''): ?>
                                <span style="display:inline-block; padding:5px 10px; border-radius:999px; border:1px solid #e5e7eb; color:#6b7280; font-size:12px;">
                                    <?= e($ip) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($created !== ''): ?>
                                <span style="display:inline-block; padding:5px 10px; border-radius:999px; border:1px solid #e5e7eb; color:#6b7280; font-size:12px;">
                                    <?= e($created) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($message !== ''): ?>
                            <div style="margin-top:8px; color:#374151; font-size:13px; line-height:1.45; white-space:pre-wrap;">
                                <?= e($message) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="color:#9ca3af; font-size:12px; white-space:nowrap;">
                        #<?= (int)$id ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top:12px; color:#9ca3af; font-size:12px;">
        Next: broadcast notifications UI + admin dashboard widgets.
    </div>
</div>
