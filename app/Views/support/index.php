<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $tickets
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');
$tickets = is_array($tickets ?? null) ? $tickets : [];
?>
<div style="max-width: 980px; margin: 0 auto; padding: 16px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 22px; font-weight: 750; letter-spacing: -0.2px;">Support</div>
            <div style="margin-top: 6px; color:#6b7280; font-size: 13px;">
                Create a ticket for help / bug reports.
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

    <!-- Create Ticket -->
    <form method="post" action="<?= e($base) ?>/support/create"
          style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; padding: 14px; margin-bottom: 14px;">
        <div style="font-weight: 650; margin-bottom: 10px;">Create ticket</div>

        <div style="display:grid; grid-template-columns: 1fr; gap: 12px;">
            <div>
                <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Subject</label>
                <input name="subject" type="text" placeholder="e.g., Transactions page error"
                       style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
            </div>

            <div>
                <label style="display:block; font-size: 13px; color:#6b7280; margin-bottom: 6px;">Message</label>
                <textarea name="message" rows="4" placeholder="Describe the issue..."
                          style="width:100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; resize: vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit"
                        style="padding: 10px 14px; border-radius: 10px; border: 1px solid #e5e7eb; background:#111; color:#fff; font-size: 14px; cursor:pointer;">
                    Create ticket
                </button>
            </div>
        </div>
    </form>

    <!-- Tickets List -->
    <div style="border: 1px solid #e5e7eb; border-radius: 14px; background:#fff; overflow:hidden;">
        <div style="padding: 12px 14px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight: 650; font-size: 14px;">Your tickets</div>
            <div style="color:#6b7280; font-size: 12px;">
                <?= count($tickets) ?> item<?= count($tickets) === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($tickets)): ?>
            <div style="padding: 18px 14px; color:#6b7280; font-size: 14px;">
                No tickets yet.
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <?php
                $id = (int)($t['id'] ?? 0);
                $subject = (string)($t['subject'] ?? $t['title'] ?? 'Ticket');
                $message = (string)($t['message'] ?? $t['body'] ?? $t['content'] ?? $t['details'] ?? '');
                $status  = (string)($t['status'] ?? 'open');
                $created = (string)($t['created_at'] ?? '');
                ?>
                <div style="padding: 14px; border-bottom:1px solid #f3f4f6;">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                        <div style="min-width:0;">
                            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                <div style="font-weight: 750; font-size: 14px; letter-spacing:-0.1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= e($subject) ?>
                                </div>

                                <span style="display:inline-block; padding: 5px 10px; border-radius: 999px; border:1px solid #e5e7eb; color:#6b7280; font-size: 12px;">
                                    <?= e($status) ?>
                                </span>

                                <?php if ($created !== ''): ?>
                                    <span style="display:inline-block; padding: 5px 10px; border-radius: 999px; border:1px solid #e5e7eb; color:#6b7280; font-size: 12px;">
                                        <?= e($created) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($message !== ''): ?>
                                <div style="margin-top: 8px; color:#374151; font-size: 13px; line-height: 1.45; white-space: pre-wrap;">
                                    <?= e($message) ?>
                                </div>
                            <?php endif; ?>
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
        Next: admin replies + email notifications (later).
    </div>
</div>
