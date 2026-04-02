<?php
declare(strict_types=1);

/**
 * @var string $baseUrl
 * @var array|null $user
 * @var string $mode  'simple'|'advanced'
 * @var string $page  'create'|'edit'
 * @var array $tx
 * @var array $errors
 * @var array $categories  category suggestions from DB (may be empty)
 */

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$mode = ($mode ?? 'simple') === 'advanced' ? 'advanced' : 'simple';
$page = ($page ?? 'create') === 'edit' ? 'edit' : 'create';

$tx = is_array($tx ?? null) ? $tx : [];
$errors = is_array($errors ?? null) ? $errors : [];
$categories = is_array($categories ?? null) ? $categories : [];

$isEdit = ($page === 'edit');

$action = $isEdit ? ($baseUrl . '/transactions/edit') : ($baseUrl . '/transactions/create');
$title  = $isEdit ? 'Edit Transaction' : 'Add Transaction';

$id          = (string)($tx['id'] ?? '');
$type        = (string)($tx['type'] ?? 'expense');
$amount      = (string)($tx['amount'] ?? '');
$category    = (string)($tx['category'] ?? '');
$description = (string)($tx['description'] ?? '');
$occurredOn  = (string)($tx['occurred_on'] ?? date('Y-m-d'));

if ($type !== 'income') $type = 'expense';

/**
 * Schema-aware field visibility:
 * If your DB doesn't have a column, model/controller will often pass empty aliases.
 * We'll hide fields that appear unsupported.
 */
$hasType = array_key_exists('type', $tx) || isset($tx['type']);
$hasAmount = array_key_exists('amount', $tx) || isset($tx['amount']);
$hasCategory = !empty($categories) || array_key_exists('category', $tx) || isset($tx['category']);
$hasDescription = array_key_exists('description', $tx) || isset($tx['description']);
$hasDate = array_key_exists('occurred_on', $tx) || isset($tx['occurred_on']);

function fieldError(array $errors, string $key): string {
    if (empty($errors[$key])) return '';
    return '<div style="margin-top:6px; font-size:12px; color:#b91c1c; font-weight:700;">' . h((string)$errors[$key]) . '</div>';
}
?>

<style>
  .z-page { max-width: 920px; margin: 0 auto; padding: 14px; }
  .z-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; }
  .z-title { margin:0; font-size:20px; font-weight:900; letter-spacing:-0.2px; }
  .z-sub { margin:4px 0 0; color:#6b7280; font-size:13px; }
  .z-btn {
    display:inline-flex; align-items:center; justify-content:center;
    padding:10px 12px; border-radius:999px; border:1px solid #e5e7eb;
    background:#fff; color:#111; font-weight:900; font-size:13px; text-decoration:none; cursor:pointer;
  }
  .z-btn-black { border-color:#000; background:#000; color:#fff; }
  .z-card { margin-top:12px; border:1px solid #e5e7eb; border-radius:16px; background:#fff; padding:14px; }
  .z-grid { display:grid; gap:12px; }
  .z-row { display:grid; gap:10px; }
  .z-row-2 { grid-template-columns: 1fr 1fr; }
  .z-label { font-size:13px; font-weight:900; margin:0 0 6px; }
  .z-help { font-size:12px; color:#6b7280; margin-top:6px; }
  .z-input, .z-select, .z-textarea {
    width:100%; padding:11px 12px; border:1px solid #e5e7eb; border-radius:12px;
    font-size:13px; outline:none; background:#fff;
  }
  .z-textarea { min-height: 90px; resize: vertical; }
  .z-input:focus, .z-select:focus, .z-textarea:focus { border-color:#111; }
  .z-topline { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
  .z-pill {
    display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px;
    border:1px solid #e5e7eb; font-size:11px; font-weight:900; color:#111; background:#fff;
  }
  .z-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
  @media (max-width: 720px) {
    .z-row-2 { grid-template-columns: 1fr; }
  }
</style>

<div class="z-page">
  <div class="z-head">
    <div>
      <div class="z-topline">
        <h1 class="z-title"><?= h($title) ?></h1>
        <span class="z-pill"><?= $mode === 'advanced' ? 'Advanced' : 'Simple' ?></span>
      </div>
      <p class="z-sub">
        <?= $mode === 'advanced'
            ? 'Add details for tracking and reports.'
            : 'Keep it quick. You can always edit later.' ?>
      </p>
    </div>

    <div class="z-actions">
      <a class="z-btn" href="<?= h($baseUrl) ?>/transactions">Back</a>
    </div>
  </div>

  <div class="z-card">
    <form method="POST" action="<?= h($action) ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= h($id) ?>">
      <?php endif; ?>

      <div class="z-grid">

        <?php if ($hasType || $hasDate): ?>
          <div class="z-row z-row-2">
            <?php if ($hasType): ?>
              <div>
                <div class="z-label">Type</div>
                <select class="z-select" name="type">
                  <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Expense</option>
                  <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>Income</option>
                </select>
              </div>
            <?php endif; ?>

            <?php if ($hasDate): ?>
              <div>
                <div class="z-label">Date</div>
                <input class="z-input" type="date" name="occurred_on" value="<?= h($occurredOn) ?>">
                <?= fieldError($errors, 'occurred_on') ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($hasAmount || $hasCategory): ?>
          <div class="z-row z-row-2">
            <?php if ($hasAmount): ?>
              <div>
                <div class="z-label"><?= $mode === 'advanced' ? 'Amount' : 'Amount (₹)' ?></div>
                <input class="z-input" name="amount" value="<?= h($amount) ?>" placeholder="e.g. 2500">
                <?= fieldError($errors, 'amount') ?>
                <?php if ($mode === 'simple'): ?>
                  <div class="z-help">Enter a positive number.</div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($hasCategory): ?>
              <div>
                <div class="z-label">Category</div>

                <input class="z-input" name="category" value="<?= h($category) ?>"
                       placeholder="e.g. Food, Rent, Salary"
                       list="zoroCategoryList" autocomplete="off">

                <?php if (!empty($categories)): ?>
                  <datalist id="zoroCategoryList">
                    <?php foreach ($categories as $c): ?>
                      <?php $c = trim((string)$c); if ($c === '') continue; ?>
                      <option value="<?= h($c) ?>"></option>
                    <?php endforeach; ?>
                  </datalist>
                <?php endif; ?>

                <?= fieldError($errors, 'category') ?>

                <?php if ($mode === 'simple'): ?>
                  <div class="z-help">Start typing and pick a suggestion (if available).</div>
                <?php else: ?>
                  <div class="z-help">Tip: consistent categories improve reports.</div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($hasDescription): ?>
          <div>
            <div class="z-label">Description (optional)</div>
            <textarea class="z-textarea" name="description" placeholder="Any note..."><?= h($description) ?></textarea>
          </div>
        <?php endif; ?>

        <div class="z-actions">
          <button class="z-btn z-btn-black" type="submit">
            <?= $isEdit ? 'Update' : 'Save' ?>
          </button>

          <?php if ($isEdit): ?>
            <a class="z-btn" href="<?= h($baseUrl) ?>/transactions/edit?id=<?= h($id) ?>">Reset</a>
          <?php else: ?>
            <a class="z-btn" href="<?= h($baseUrl) ?>/transactions/create">Reset</a>
          <?php endif; ?>

          <a class="z-btn" href="<?= h($baseUrl) ?>/transactions">Cancel</a>
        </div>

      </div>
    </form>
  </div>
</div>
