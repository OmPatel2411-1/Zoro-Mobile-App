<?php
declare(strict_types=1);

/**
 * Admin – Users Management
 *
 * @var string $baseUrl
 * @var array  $users
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base  = rtrim((string)($baseUrl ?? ''), '/');
$users = is_array($users ?? null) ? $users : [];
?>
<div style="max-width: 1100px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750;">Admin · Users</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Manage users and account status.
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
        <div style="padding:12px 14px; border-bottom:1px solid #e5e7eb; font-weight:650;">
            Users (<?= count($users) ?>)
        </div>

        <?php if (empty($users)): ?>
            <div style="padding:16px; color:#6b7280;">No users found.</div>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
                <?php
                $id     = (int)($u['id'] ?? 0);
                $name   = (string)($u['name'] ?? '');
                $email  = (string)($u['email'] ?? '');
                $mobile = (string)($u['mobile'] ?? '');
                $role   = (string)($u['role'] ?? 'user');
                $statusRaw = $u['status'] ?? $u['is_active'] ?? $u['active'] ?? null;

                $active = true;
                if ($statusRaw !== null) {
                    if ((string)$statusRaw === '0' || $statusRaw === 0 || $statusRaw === false) $active = false;
                    if ((string)$statusRaw === 'inactive') $active = false;
                }
                ?>
                <div style="padding:14px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; gap:12px;">
                    <div>
                        <div style="font-weight:700;">
                            <?= e($name !== '' ? $name : 'User #' . $id) ?>
                        </div>
                        <div style="margin-top:4px; font-size:13px; color:#6b7280;">
                            <?= e($email) ?><?= $mobile !== '' ? ' · ' . e($mobile) : '' ?>
                        </div>
                        <div style="margin-top:4px; font-size:12px; color:#6b7280;">
                            Role: <?= e($role) ?>
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <span style="display:inline-block; padding:5px 10px; border-radius:999px; border:1px solid #e5e7eb; font-size:12px;">
                            <?= $active ? 'Active' : 'Disabled' ?>
                        </span>

                        <form method="post" action="<?= e($base) ?>/admin/user/toggle" style="margin-top:8px;">
                            <input type="hidden" name="id" value="<?= (int)$id ?>">
                            <button type="submit"
                                    style="padding:8px 10px; border-radius:10px; border:1px solid #e5e7eb; background:#111; color:#fff; font-size:12px;">
                                Toggle status
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top:12px; color:#9ca3af; font-size:12px;">
        Next: Admin logs + broadcast notifications.
    </div>
</div>
