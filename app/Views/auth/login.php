<?php
declare(strict_types=1);

/**
 * Variables available:
 * @var string $baseUrl
 * @var string|null $error
 * @var string|null $email
 */

require_once __DIR__ . '/../../Middleware/Csrf.php';

$baseUrl = $baseUrl ?? '';
$error   = $error ?? null;
$email   = $email ?? '';
?>
<style>
  .auth-wrap{
    max-width:420px;
    margin:24px auto;
    padding:16px;
  }
  .auth-card{
    border:1px solid var(--border);
    border-radius:16px;
    padding:16px;
    background:var(--card);
  }
  .auth-title{
    margin:0 0 6px;
    font-size:20px;
    font-weight:900;
    color:var(--text);
  }
  .auth-sub{
    margin:0 0 14px;
    color:var(--muted);
    font-size:13px;
    line-height:1.35;
  }
  .field{
    margin:10px 0;
  }
  label{
    display:block;
    margin:0 0 6px;
    font-weight:900;
    font-size:12px;
    color:var(--text);
  }
  input{
    width:100%;
    padding:10px 12px;
    border-radius:12px;
    border:1px solid var(--border);
    background:var(--bg);
    color:var(--text);
    outline:none;
  }
  input:focus{
    border-color:var(--text);
  }
  .btn{
    width:100%;
    padding:11px 12px;
    border-radius:999px;
    border:1px solid var(--text);
    background:var(--text);
    color:var(--bg);
    font-weight:900;
    cursor:pointer;
    margin-top:10px;
  }
  .alert{
    border:1px solid var(--border);
    background:var(--card);
    padding:10px 12px;
    border-radius:12px;
    color:var(--text);
    font-size:13px;
    margin:0 0 10px;
  }
</style>

<div class="auth-wrap">
  <div class="auth-card">
    <h1 class="auth-title">Login</h1>
    <p class="auth-sub">Sign in to continue to Zoro.</p>

    <?php if (!empty($error)): ?>
      <div class="alert"><?= htmlspecialchars((string)$error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/login" autocomplete="on">
      <?= Csrf::input() ?>

      <div class="field">
        <label for="email">Email</label>
        <input
          id="email"
          name="email"
          type="email"
          value="<?= htmlspecialchars((string)$email) ?>"
          required
          autofocus
          autocomplete="email"
        >
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input
          id="password"
          name="password"
          type="password"
          required
          autocomplete="current-password"
        >
      </div>

      <button class="btn" type="submit">Login</button>
    </form>
  </div>
</div>
