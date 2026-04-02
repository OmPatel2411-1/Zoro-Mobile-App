<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array $sessions
 * @var string|null $error
 * @var string|null $success
 */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim((string)($baseUrl ?? ''), '/');
$sessions = is_array($sessions ?? null) ? $sessions : [];
?>
<style>
  .security-page{ max-width: 980px; margin: 0 auto; padding: 16px; }
  .security-head{
    display:flex; align-items:flex-end; justify-content:space-between;
    gap:12px; margin-bottom: 14px;
  }
  .security-title{ font-size: 22px; font-weight: 900; letter-spacing: -0.2px; color: var(--text); }
  .security-sub{ margin-top: 6px; color: var(--muted); font-size: 13px; }

  .btn{
    display:inline-flex; align-items:center; justify-content:center;
    padding: 10px 12px; border-radius: 999px;
    border: 1px solid var(--border);
    background: var(--card);
    color: var(--text);
    text-decoration:none;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
    line-height: 1;
    user-select: none;
  }
  .btn-black{
    border: 1px solid var(--text);
    background: var(--text);
    color: var(--bg);
  }

  .alert{
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.03);
    color: var(--text);
    font-size: 13px;
    margin-bottom: 12px;
  }
  .alert-error{
    border-color: rgba(255, 107, 107, 0.35);
    background: rgba(255, 107, 107, 0.08);
  }
  .alert-success{
    border-color: rgba(45, 212, 191, 0.35);
    background: rgba(45, 212, 191, 0.08);
  }

  .card{
    border: 1px solid var(--border);
    border-radius: 16px;
    background: var(--card);
    overflow: hidden;
  }
  .card-head{
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap: 10px;
  }
  .card-head .h{ font-weight: 900; font-size: 14px; color: var(--text); }
  .card-head .m{ color: var(--muted); font-size: 12px; }

  .row{
    padding: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
  }
  html:not([data-theme="dark"]) .row{
    border-bottom: 1px solid #f3f4f6;
  }
  .row:last-child{ border-bottom:none; }

  .row-top{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
  }

  .tag{
    display:inline-block;
    padding: 5px 10px;
    border-radius: 999px;
    border: 1px solid var(--border);
    color: var(--muted);
    font-size: 12px;
    font-weight: 900;
  }

  .kv{ margin-top: 8px; font-size: 13px; line-height: 1.5; color: var(--text); }
  .kv .k{ color: var(--muted); font-weight: 900; }
  .ua{ margin-top: 6px; color: var(--muted); font-size: 12px; white-space: pre-wrap; }
  .sid{ margin-top: 6px; color: rgba(161,161,170,0.85); font-size: 11px; white-space: pre-wrap; }

  .tiny{ margin-top: 12px; color: rgba(161,161,170,0.85); font-size: 12px; }
  html:not([data-theme="dark"]) .tiny{ color:#9ca3af; }

  .actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom: 12px;
  }
</style>

<div class="security-page">

  <div class="security-head">
    <div>
      <div class="security-title">Security</div>
      <div class="security-sub">Session history and account safety.</div>
    </div>

    <a class="btn" href="<?= e($base) ?>/settings">Back</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e((string)$error) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e((string)$success) ?></div>
  <?php endif; ?>

  <div class="actions">
    <form method="post" action="<?= e($base) ?>/security/logout-all" style="margin:0;" onsubmit="return confirmLogoutAll();">
      <button type="submit" class="btn btn-black">Logout all devices</button>
    </form>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="h">Your sessions</div>
      <div class="m">
        <?= count($sessions) ?> item<?= count($sessions) === 1 ? '' : 's' ?>
      </div>
    </div>

    <?php if (empty($sessions)): ?>
      <div class="row" style="color:var(--muted); font-size: 14px;">
        No session records found.
      </div>
    <?php else: ?>
      <?php foreach ($sessions as $s): ?>
        <?php
          $id = (int)($s['id'] ?? 0);

          $sid = (string)($s['session_id'] ?? $s['sid'] ?? $s['token'] ?? '');
          $ip  = (string)($s['ip_address'] ?? $s['ip'] ?? $s['ip_addr'] ?? '');
          $ua  = (string)($s['user_agent'] ?? $s['ua'] ?? '');
          $created = (string)($s['created_at'] ?? '');
          $last = (string)($s['last_active_at'] ?? $s['updated_at'] ?? '');

          $activeRaw = $s['is_active'] ?? $s['active'] ?? $s['status'] ?? null;
          $active = true;
          if ($activeRaw !== null) {
            if ((string)$activeRaw === '0' || $activeRaw === 0 || $activeRaw === false) $active = false;
            if ((string)$activeRaw === 'inactive') $active = false;
          }
        ?>

        <div class="row">
          <div class="row-top">
            <div style="min-width:0;">
              <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div style="font-weight: 900; font-size: 14px; letter-spacing:-0.1px; color:var(--text);">
                  Session #<?= (int)$id ?>
                </div>

                <span class="tag"><?= $active ? 'Active' : 'Inactive' ?></span>
              </div>

              <div class="kv">
                <?php if ($ip !== ''): ?>
                  <div><span class="k">IP:</span> <?= e($ip) ?></div>
                <?php endif; ?>

                <?php if ($created !== ''): ?>
                  <div><span class="k">Created:</span> <?= e($created) ?></div>
                <?php endif; ?>

                <?php if ($last !== ''): ?>
                  <div><span class="k">Last active:</span> <?= e($last) ?></div>
                <?php endif; ?>

                <?php if ($ua !== ''): ?>
                  <div class="ua"><?= e($ua) ?></div>
                <?php endif; ?>

                <?php if ($sid !== ''): ?>
                  <div class="sid"><?= e($sid) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <div style="color: rgba(161,161,170,0.85); font-size: 12px; white-space:nowrap;">
              #<?= (int)$id ?>
            </div>
          </div>
        </div>

      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="tiny">
    Tip: Use “Logout all devices” after changing password or if you notice unknown sessions.
  </div>

</div>

<script>
function confirmLogoutAll(){
  return confirm('Logout from all devices? You will need to login again on other sessions.');
}
</script>
