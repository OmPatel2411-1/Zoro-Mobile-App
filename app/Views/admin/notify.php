<?php
declare(strict_types=1);

/**
 * Admin – Broadcast Notification
 *
 * @var string $baseUrl
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750;">Admin · Broadcast</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Send a notification to all users.
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

    <form method="post" action="<?= e($base) ?>/admin/notify"
          style="border:1px solid #e5e7eb; border-radius:14px; background:#fff; padding:14px;">
        <div style="display:grid; grid-template-columns: 1fr; gap:12px;">
            <div>
                <label style="display:block; font-size:13px; color:#6b7280; margin-bottom:6px;">Title</label>
                <input name="title" type="text" placeholder="e.g., Maintenance update"
                       style="width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; font-size:14px;">
            </div>

            <div>
                <label style="display:block; font-size:13px; color:#6b7280; margin-bottom:6px;">Message</label>
                <textarea name="message" rows="4" placeholder="Write your message..."
                          style="width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; font-size:14px; resize:vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit"
                        style="padding:10px 14px; border-radius:10px; border:1px solid #e5e7eb; background:#111; color:#fff; font-size:14px; cursor:pointer;">
                    Send to all users
                </button>
            </div>

            <div style="color:#9ca3af; font-size:12px;">
                This will create one notification row per user in the <b>notifications</b> table (next file will implement controller changes if needed).
            </div>
        </div>
    </form>
</div>
