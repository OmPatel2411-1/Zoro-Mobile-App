<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $notifications
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');

$notifications = is_array($notifications ?? null) ? $notifications : [];
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750; letter-spacing: -0.2px;">Notifications</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Updates, reminders, and system messages.
            </div>
        </div>

        <a href="<?= e($base) ?>/dashboard"
           style="display:inline-block; padding: 10px 12px; border-radius: 10px; border: 1px solid #e5e7eb; text-decoration:none; color:#111; font-size: 14px;">
            Back
        </a>
    </div>

    <div style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; overflow:hidden;">
        <div style="padding: 12px 14px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight: 650; font-size: 14px;">Inbox</div>
            <div style="color:#6b7280; font-size: 12px;">
                <?= count($notifications) ?> item<?= count($notifications) === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <div style="padding: 18px 14px; color:#6b7280; font-size: 14px;">
                No notifications yet.
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <?php
                $id = (int)($n['id'] ?? 0);

                $isReadRaw = $n['is_read'] ?? $n['read'] ?? $n['seen'] ?? 0;
                $isRead = ((string)$isReadRaw === '1' || $isReadRaw === 1 || $isReadRaw === true);

                $title = (string)($n['title'] ?? $n['subject'] ?? 'Notification');
                $body  = (string)($n['message'] ?? $n['body'] ?? $n['content'] ?? $n['text'] ?? '');
                $time  = (string)($n['created_at'] ?? $n['created_on'] ?? $n['createdAt'] ?? '');
                ?>
                <div style="padding: 14px; border-bottom:1px solid #f3f4f6; display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:10px; height:10px; border-radius:999px; margin-top: 6px; flex: 0 0 auto; background: <?= $isRead ? '#e5e7eb' : '#111' ?>;"></div>

                    <div style="flex:1 1 auto; min-width: 0;">
                        <div style="display:flex; gap:10px; align-items:baseline; justify-content:space-between;">
                            <div style="font-weight: <?= $isRead ? '600' : '750' ?>; font-size: 14px; letter-spacing: -0.1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= e($title) ?>
                            </div>
                            <?php if ($time !== ''): ?>
                                <div style="color:#6b7280; font-size: 12px; flex:0 0 auto; margin-left: 10px;">
                                    <?= e($time) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($body !== ''): ?>
                            <div style="margin-top: 6px; color:#374151; font-size: 13px; line-height: 1.45; white-space: pre-wrap;">
                                <?= e($body) ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 10px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <?php if (!$isRead && $id > 0): ?>
                                <form method="post" action="<?= e($base) ?>/notifications/mark-read" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= (int)$id ?>">
                                    <button type="submit"
                                            style="padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background:#111; color:#fff; font-size: 13px; cursor:pointer;">
                                        Mark as read
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="display:inline-block; padding: 6px 10px; border-radius: 999px; border:1px solid #e5e7eb; color:#6b7280; font-size: 12px;">
                                    Read
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top: 12px; color:#9ca3af; font-size: 12px;">
        Tip: Notifications appear here when rules, subscriptions, budgets, or system events trigger them.
    </div>
</div>
