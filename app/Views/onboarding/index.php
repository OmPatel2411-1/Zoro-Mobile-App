<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $user
 * @var string|null $error
 * @var string|null $success
 */

$name   = (string)($user['name'] ?? '');
$uiMode = (string)($user['ui_mode'] ?? 'simple');

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<div style="max-width: 720px; margin: 0 auto; padding: 16px;">
    <div style="margin-bottom: 14px;">
        <div style="font-size: 22px; font-weight: 700; letter-spacing: -0.2px;">Welcome</div>
        <div style="margin-top: 6px; color: #6b7280; font-size: 14px;">
            Let’s set up your preferences. You can change these later in Settings.
        </div>
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

    <form method="post" action="<?= e(rtrim($baseUrl, '/')) ?>/onboarding/save" style="border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; padding: 16px;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
            <div>
                <label for="name" style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Your name</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="<?= e($name) ?>"
                    placeholder="e.g., Rahul"
                    required
                    style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;"
                >
            </div>

            <div>
                <div style="font-size: 13px; color:#6b7280; margin-bottom: 6px;">Choose your mode</div>

                <div style="display:grid; grid-template-columns: 1fr; gap: 10px;">
                    <label style="display:flex; gap: 10px; align-items:flex-start; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; cursor:pointer;">
                        <input type="radio" name="ui_mode" value="simple" <?= ($uiMode === 'simple' ? 'checked' : '') ?> style="margin-top: 3px;">
                        <div>
                            <div style="font-weight: 650;">Simple</div>
                            <div style="color:#6b7280; font-size: 13px; margin-top: 3px;">
                                Guided view, minimal options, fast entry.
                            </div>
                        </div>
                    </label>

                    <label style="display:flex; gap: 10px; align-items:flex-start; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; cursor:pointer;">
                        <input type="radio" name="ui_mode" value="advanced" <?= ($uiMode === 'advanced' ? 'checked' : '') ?> style="margin-top: 3px;">
                        <div>
                            <div style="font-weight: 650;">Advanced</div>
                            <div style="color:#6b7280; font-size: 13px; margin-top: 3px;">
                                Power-user view: filters, bulk actions, analytics-ready.
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div style="display:flex; gap: 10px; flex-wrap: wrap; justify-content:flex-end; margin-top: 6px;">
                <a href="<?= e(rtrim($baseUrl, '/')) ?>/dashboard"
                   style="display:inline-block; padding: 10px 12px; border-radius: 10px; border: 1px solid #e5e7eb; text-decoration:none; color:#111; font-size: 14px;">
                    Skip for now
                </a>

                <button type="submit"
                        style="padding: 10px 14px; border-radius: 10px; border: none; background: #111; color: #fff; font-size: 14px; cursor:pointer;">
                    Save & Continue
                </button>
            </div>
        </div>
    </form>

    <div style="margin-top: 12px; color:#6b7280; font-size: 13px;">
        Tip: You can switch modes anytime from <a href="<?= e(rtrim($baseUrl, '/')) ?>/settings" style="color:#111; text-decoration:none; font-weight:600;">Settings</a>.
    </div>
</div>
