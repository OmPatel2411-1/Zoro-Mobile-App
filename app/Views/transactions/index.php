<?php
declare(strict_types=1);

/**
 * @var array $transactions
 * @var array $filters
 * @var string $mode  'simple'|'advanced'
 * @var string $baseUrl
 * @var array|null $user
 * @var array $categories
 * @var string|null $error
 * @var string|null $success
 */

$mode = ($mode ?? 'simple') === 'advanced' ? 'advanced' : 'simple';
$filters = $filters ?? ['q'=>'','type'=>'','category'=>'','from'=>'','to'=>''];
$categories = is_array($categories ?? null) ? $categories : [];

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

function fmtAmount($amount, string $type): string {
    $n = is_numeric($amount) ? (float)$amount : 0.0;
    $sign = ($type === 'income') ? '+' : '-';
    // Keep it simple; Indian formatting can be added later if you want
    return $sign . '₹' . number_format($n, 2, '.', ',');
}

$total = is_array($transactions) ? count($transactions) : 0;

$error = $error ?? null;
$success = $success ?? null;
?>

<style>
  .z-page { max-width: 1100px; margin: 0 auto; padding: 14px; }
  .z-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; }
  .z-title { margin:0; font-size:20px; font-weight:900; letter-spacing:-0.2px; }
  .z-sub { margin:4px 0 0; color:#6b7280; font-size:13px; }
  .z-actions { display:flex; gap:10px; flex-wrap:wrap; }
  .z-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    padding:10px 12px; border-radius:999px; border:1px solid #e5e7eb;
    background:#fff; color:#111; font-weight:900; font-size:13px; text-decoration:none; cursor:pointer;
  }
  .z-btn-black { border-color:#000; background:#000; color:#fff; }
  .z-card { margin-top:12px; border:1px solid #e5e7eb; border-radius:16px; background:#fff; overflow:hidden; }
  .z-filter { padding:12px; border-bottom:1px solid #e5e7eb; background:#fafafa; }
  .z-grid { display:grid; gap:10px; }
  .z-row { display:grid; gap:8px; }
  .z-row-3 { grid-template-columns: 1fr 1fr 1fr; }
  .z-input, .z-select {
    width:100%; padding:11px 12px; border:1px solid #e5e7eb; border-radius:12px;
    font-size:13px; outline:none; background:#fff;
  }
  .z-input:focus, .z-select:focus { border-color:#111; }
  .z-small { font-size:12px; color:#6b7280; }
  .z-table { width:100%; border-collapse:collapse; font-size:13px; }
  .z-table th, .z-table td { padding:12px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top; }
  .z-table thead th { background:#f9fafb; font-weight:900; }
  .z-muted { color:#6b7280; }
  .z-pill {
    display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px;
    border:1px solid #e5e7eb; font-size:11px; font-weight:900; color:#111; background:#fff;
  }
  .z-pill-income { border-color:#111; }
  .z-pill-expense { border-color:#e5e7eb; }
  .z-amount { font-weight:900; white-space:nowrap; }
  .z-empty { padding:16px; color:#6b7280; font-size:13px; }
  .z-bulkbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; padding:12px; border-bottom:1px solid #e5e7eb; }
  .z-bulkbar .z-btn { padding:8px 10px; font-size:12px; }
  .z-inline { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .z-right { margin-left:auto; }
  .z-chip { font-size:12px; font-weight:900; color:#111; border:1px solid #e5e7eb; padding:4px 10px; border-radius:999px; background:#fff; }

  /* Alerts */
  .z-alert {
    margin-top:12px;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:10px 12px;
    font-size:13px;
    font-weight:700;
  }
  .z-alert-success { border-color:#dcfce7; background:#f0fdf4; color:#166534; }
  .z-alert-error { border-color:#fee2e2; background:#fef2f2; color:#991b1b; }

  /* Mobile list */
  .z-mobilelist { display:none; }
  .z-item { padding:12px; border-bottom:1px solid #e5e7eb; }
  .z-item:last-child { border-bottom:none; }
  .z-item-top { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
  .z-item-title { font-weight:900; font-size:13px; margin:0; }
  .z-item-desc { margin-top:6px; color:#6b7280; font-size:12px; line-height:1.35; }
  .z-item-meta { margin-top:8px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .z-item-actions { margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }

  @media (max-width: 720px) {
    .z-tablewrap { display:none; }
    .z-mobilelist { display:block; }
    .z-row-3 { grid-template-columns: 1fr; }
    .z-right { margin-left:0; }
  }
</style>

<div class="z-page">
  <div class="z-head">
    <div>
      <h1 class="z-title">Transactions</h1>
      <p class="z-sub">
        <?= $mode === 'advanced'
            ? 'Filter, bulk actions, and faster review.'
            : 'A clean list of your latest money activity.' ?>
        <span class="z-chip" style="margin-left:8px;"><?= $mode === 'advanced' ? 'Advanced' : 'Simple' ?></span>
      </p>
    </div>

    <div class="z-actions">
      <a class="z-btn z-btn-black" href="<?= h($baseUrl) ?>/transactions/create">+ Add</a>
      <?php if ($mode === 'advanced'): ?>
        <a class="z-btn" href="<?= h($baseUrl) ?>/reports">Reports</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="z-alert z-alert-error"><?= h((string)$error) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="z-alert z-alert-success"><?= h((string)$success) ?></div>
  <?php endif; ?>

  <div class="z-card">
    <?php if ($mode === 'advanced'): ?>
      <div class="z-filter">
        <form method="GET" action="<?= h($baseUrl) ?>/transactions">
          <div class="z-grid">
            <div class="z-row z-row-3">
              <input class="z-input" name="q" value="<?= h((string)($filters['q'] ?? '')) ?>" placeholder="Search category or description…">
              <select class="z-select" name="type">
                <option value="">All types</option>
                <option value="expense" <?= (($filters['type'] ?? '') === 'expense') ? 'selected' : '' ?>>Expense</option>
                <option value="income" <?= (($filters['type'] ?? '') === 'income') ? 'selected' : '' ?>>Income</option>
              </select>
              <select class="z-select" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                  <?php $c = (string)$c; ?>
                  <option value="<?= h($c) ?>" <?= (($filters['category'] ?? '') === $c) ? 'selected' : '' ?>>
                    <?= h($c) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="z-row z-row-3">
              <input class="z-input" type="date" name="from" value="<?= h((string)($filters['from'] ?? '')) ?>">
              <input class="z-input" type="date" name="to" value="<?= h((string)($filters['to'] ?? '')) ?>">

              <div class="z-inline">
                <button class="z-btn z-btn-black" type="submit">Apply</button>
                <a class="z-btn" href="<?= h($baseUrl) ?>/transactions">Reset</a>
                <span class="z-small">Showing up to 500 rows</span>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="z-bulkbar">
        <label class="z-inline" style="gap:8px;">
          <input type="checkbox" id="zSelectAll" onchange="zoroSelectAll(this)">
          <span style="font-weight:900; font-size:12px;">Select all</span>
        </label>

        <span class="z-small" id="zSelectedCount">0 selected</span>

        <div class="z-inline z-right">
          <input class="z-input" id="zBulkCategory" placeholder="Set category (e.g. Food)" style="max-width:240px; padding:9px 10px;">
          <button class="z-btn" type="button" onclick="zoroBulkSetCategory()">Set category</button>
          <button class="z-btn" type="button" onclick="zoroBulkDelete()" style="border-color:#111;">Delete</button>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
      <div class="z-empty">No transactions found.</div>
    <?php else: ?>

      <div class="z-tablewrap">
        <table class="z-table">
          <thead>
            <tr>
              <?php if ($mode === 'advanced'): ?>
                <th style="width:44px;"></th>
              <?php endif; ?>
              <th style="width:120px;">Date</th>
              <th style="width:120px;">Type</th>
              <th style="width:170px;">Category</th>
              <th>Description</th>
              <th style="width:150px;">Amount</th>
              <th style="width:170px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transactions as $t): ?>
              <?php
                $id = (int)($t['id'] ?? 0);
                $type = (string)($t['type'] ?? 'expense');
                if ($type !== 'income') $type = 'expense';
                $cat = (string)($t['category'] ?? '');
                $desc = (string)($t['description'] ?? '');
                $date = (string)($t['occurred_on'] ?? '');
                $amount = $t['amount'] ?? 0;
              ?>
              <tr>
                <?php if ($mode === 'advanced'): ?>
                  <td>
                    <input type="checkbox" class="zTxCheck" value="<?= $id ?>" onchange="zoroUpdateSelectedCount()">
                  </td>
                <?php endif; ?>
                <td><?= h($date) ?></td>
                <td>
                  <span class="z-pill <?= $type === 'income' ? 'z-pill-income' : 'z-pill-expense' ?>">
                    <?= $type === 'income' ? 'Income' : 'Expense' ?>
                  </span>
                </td>
                <td><span class="z-pill"><?= h($cat !== '' ? $cat : '—') ?></span></td>
                <td class="z-muted"><?= h($desc) ?></td>
                <td class="z-amount"><?= h(fmtAmount($amount, $type)) ?></td>
                <td>
                  <a class="z-btn" href="<?= h($baseUrl) ?>/transactions/edit?id=<?= $id ?>" style="padding:8px 10px; font-size:12px;">Edit</a>

                  <form method="POST" action="<?= h($baseUrl) ?>/transactions/delete" style="display:inline; margin-left:6px;" onsubmit="return confirm('Delete this transaction?')">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="z-btn" type="submit" style="padding:8px 10px; font-size:12px;">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="z-mobilelist">
        <?php foreach ($transactions as $t): ?>
          <?php
            $id = (int)($t['id'] ?? 0);
            $type = (string)($t['type'] ?? 'expense');
            if ($type !== 'income') $type = 'expense';
            $cat = (string)($t['category'] ?? '');
            $desc = (string)($t['description'] ?? '');
            $date = (string)($t['occurred_on'] ?? '');
            $amount = $t['amount'] ?? 0;
          ?>
          <div class="z-item">
            <div class="z-item-top">
              <div>
                <p class="z-item-title"><?= h($cat !== '' ? $cat : 'Transaction') ?></p>
                <div class="z-small">
                  <?= h($date) ?> •
                  <span style="font-weight:900;"><?= $type === 'income' ? 'Income' : 'Expense' ?></span>
                </div>
              </div>
              <div class="z-amount"><?= h(fmtAmount($amount, $type)) ?></div>
            </div>

            <?php if ($desc !== ''): ?>
              <div class="z-item-desc"><?= h($desc) ?></div>
            <?php endif; ?>

            <div class="z-item-meta">
              <span class="z-pill <?= $type === 'income' ? 'z-pill-income' : 'z-pill-expense' ?>">
                <?= $type === 'income' ? 'Income' : 'Expense' ?>
              </span>
              <span class="z-pill"><?= h($cat !== '' ? $cat : '—') ?></span>
            </div>

            <div class="z-item-actions">
              <a class="z-btn" href="<?= h($baseUrl) ?>/transactions/edit?id=<?= $id ?>" style="padding:8px 10px; font-size:12px;">Edit</a>
              <form method="POST" action="<?= h($baseUrl) ?>/transactions/delete" style="display:inline;" onsubmit="return confirm('Delete this transaction?')">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button class="z-btn" type="submit" style="padding:8px 10px; font-size:12px;">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>
</div>

<?php if ($mode === 'advanced'): ?>
<script>
  const BASE_URL = <?= json_encode((string)$baseUrl) ?>;

  function zoroSelectedIds() {
    const checks = document.querySelectorAll('.zTxCheck:checked');
    return Array.from(checks).map(c => c.value);
  }

  function zoroUpdateSelectedCount() {
    const count = zoroSelectedIds().length;
    const el = document.getElementById('zSelectedCount');
    if (el) el.textContent = count + ' selected';
  }

  function zoroSelectAll(master) {
    const checks = document.querySelectorAll('.zTxCheck');
    checks.forEach(c => c.checked = !!master.checked);
    zoroUpdateSelectedCount();
  }

  async function zoroBulkDelete() {
    const ids = zoroSelectedIds();
    if (ids.length === 0) return alert('Select at least 1 transaction.');
    if (!confirm('Delete selected transactions?')) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    ids.forEach(id => fd.append('ids[]', id));

    const res = await fetch(BASE_URL + '/transactions/bulk', { method: 'POST', body: fd });
    const json = await res.json().catch(() => null);

    if (json && json.ok) location.reload();
    else alert((json && json.message) ? json.message : 'Bulk delete failed.');
  }

  async function zoroBulkSetCategory() {
    const ids = zoroSelectedIds();
    if (ids.length === 0) return alert('Select at least 1 transaction.');

    const category = (document.getElementById('zBulkCategory')?.value || '').trim();
    if (!category) return alert('Enter category name.');

    const fd = new FormData();
    fd.append('action', 'set-category');
    fd.append('category', category);
    ids.forEach(id => fd.append('ids[]', id));

    const res = await fetch(BASE_URL + '/transactions/bulk', { method: 'POST', body: fd });
    const json = await res.json().catch(() => null);

    if (json && json.ok) location.reload();
    else alert((json && json.message) ? json.message : 'Bulk update failed.');
  }

  zoroUpdateSelectedCount();
</script>
<?php endif; ?>
