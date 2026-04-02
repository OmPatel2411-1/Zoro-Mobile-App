<?php
declare(strict_types=1);

/**
 * Variables available:
 * @var array  $user
 * @var string $mode
 * @var string $appName
 * @var string $baseUrl (usually provided by controller/layout)
 */

$isAdvanced = ($mode ?? 'simple') === 'advanced';
$base = $baseUrl ?? '';

function card(string $title, string $desc, ?string $href = null, string $cta = 'Open'): void {
  ?>
  <div class="card">
    <div class="card-title"><?= htmlspecialchars($title) ?></div>
    <div class="card-desc"><?= htmlspecialchars($desc) ?></div>

    <?php if (!empty($href)): ?>
      <div style="margin-top:12px;">
        <a class="btn-lite" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($cta) ?> →</a>
      </div>
    <?php endif; ?>
  </div>
  <?php
}
?>

<style>
  .grid{
    display:grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap:12px;
  }
  .card{
    border:1px solid var(--border);
    border-radius:16px;
    padding:16px;
    background:var(--card);
  }
  .card-title{ font-weight:900; margin:0 0 6px; }
  .card-desc{ color:var(--muted); font-size:13px; line-height:1.35; }

  .hero{
    border:1px solid var(--border);
    border-radius:16px;
    padding:16px;
    background:var(--card);
    margin-bottom:12px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
  }
  .hero h2{ margin:0; font-size:20px; }
  .pill{
    display:inline-flex;
    align-items:center;
    padding:8px 12px;
    border-radius:999px;
    border:1px solid var(--border);
    font-weight:800;
    font-size:12px;
    background:var(--card);
    color:var(--text);
  }

  .quick-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }
  .btn-black{
    display:inline-block;
    padding:10px 12px;
    border-radius:999px;
    border:1px solid var(--text);
    background:var(--text);
    color:var(--bg);
    font-weight:900;
    cursor:pointer;
    text-decoration:none;
    font-size:13px;
  }
  .btn-lite{
    display:inline-block;
    padding:9px 12px;
    border-radius:999px;
    border:1px solid var(--border);
    background:var(--card);
    color:var(--text);
    font-weight:900;
    cursor:pointer;
    text-decoration:none;
    font-size:13px;
  }

  @media (max-width: 1024px){
    .grid{ grid-template-columns: repeat(2, minmax(0,1fr)); }
  }
  @media (max-width: 700px){
    .grid{ grid-template-columns: 1fr; }
    .hero{ flex-direction:column; align-items:stretch; }
    .quick-actions{ justify-content:flex-start; }
  }
</style>

<div class="hero">
  <div>
    <h2>Hello, <?= htmlspecialchars($user['name'] ?? 'User') ?> 👋</h2>
    <p style="margin:6px 0 0; color:var(--muted); font-size:13px;">
      Welcome to <?= htmlspecialchars($appName ?? 'Zoro') ?>. This is your <?= $isAdvanced ? 'Advanced' : 'Simple' ?> dashboard.
    </p>
    <div style="margin-top:10px;">
      <span class="pill"><?= $isAdvanced ? 'Pro tools unlocked' : 'Guided view enabled' ?></span>
    </div>
  </div>

  <div class="quick-actions">
    <a class="btn-black" href="<?= htmlspecialchars($base) ?>/transactions">Transactions</a>
    <a class="btn-lite" href="<?= htmlspecialchars($base) ?>/rules">Rules</a>
    <a class="btn-lite" href="<?= htmlspecialchars($base) ?>/reports">Reports</a>
    <a class="btn-lite" href="<?= htmlspecialchars($base) ?>/settings">Settings</a>
  </div>
</div>

<div class="grid">
  <?php
    // Always visible
    card('Safe to Spend', 'Quick overview of spending headroom (summary).', $base . '/reports', 'View reports');
    card('Spending Insight', 'See trends and category totals to improve habits.', $base . '/reports', 'Open reports');
    card('Notifications', 'View your latest alerts and updates.', $base . '/notifications', 'Open alerts');

    // Advanced only
    if ($isAdvanced) {
      card('Cashflow Forecast', 'Review upcoming inflows/outflows and plan ahead.', $base . '/reports', 'Open reports');
      card('Category Breakdown', 'Analyze spending categories and patterns.', $base . '/reports', 'View breakdown');
      card('Rules & Automations', 'Auto-tag, auto-alert, and enforce spending rules.', $base . '/rules', 'Manage rules');
    } else {
      card('Quick Tip', 'Enable Advanced mode anytime for deeper analytics and automations.', $base . '/settings', 'Go to settings');
      card('Goals', 'Track savings goals and stay consistent.', $base . '/goals', 'Open goals');
      card('Subscriptions', 'Monitor renewals and recurring charges.', $base . '/subscriptions', 'Open subscriptions');
    }
  ?>
</div>
