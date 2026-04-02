<?php
declare(strict_types=1);
/** @var string $content */
/** @var string $baseUrl */

$mode = $_SESSION['ui_mode'] ?? 'simple';
$path = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

$isDark = !empty($_SESSION['dark_mode']) && (int)$_SESSION['dark_mode'] === 1;
?>
<!doctype html>
<html lang="en" <?= $isDark ? 'data-theme="dark"' : '' ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(Env::get('APP_NAME','Zoro')) ?></title>

  <?php if ($isDark): ?>
    <!-- Dark mode CSS (subfolder-safe) -->
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/dark.css?v=1">
  <?php endif; ?>

  <style>
    :root{
      --bg:#ffffff;
      --text:#111111;
      --muted:#6b7280;
      --border:#e5e7eb;
      --card:#ffffff;
      --radius:16px;
      --black:#000;
      --white:#fff;
    }

    /* Dark mode fallback (works even if dark.css has no rules) */
    <?php if ($isDark): ?>
    html[data-theme="dark"]{
      --bg:#0b0b0c;
      --text:#f5f5f5;
      --muted:#a1a1aa;
      --border:#232326;
      --card:#111114;
      --black:#000;
      --white:#fff;
    }
    <?php endif; ?>

    *{ box-sizing:border-box; }
    body{
      margin:0;
      font-family: Arial, sans-serif;
      background:var(--bg);
      color:var(--text);
    }
    a{ color:inherit; text-decoration:none; }

    /* Layout */
    .shell{
      min-height:100vh;
      display:flex;
    }
    .sidebar{
      width:260px;
      border-right:1px solid var(--border);
      padding:14px;
      display:none; /* show on desktop */
      position:sticky;
      top:0;
      height:100vh;
      background:var(--card);
    }
    .main{
      flex:1;
      min-width:0;
    }

    /* Desktop sidebar visible */
    @media (min-width: 1024px){
      .sidebar{ display:block; }
      .topbar{ display:none; } /* optional: hide topbar on desktop (we’ll keep header inside content) */
      .content{ padding:18px 20px; }
    }

    /* Mobile topbar */
    .topbar{
      position:sticky; top:0; z-index:10;
      display:flex; align-items:center; justify-content:space-between;
      padding:12px 12px;
      border-bottom:1px solid var(--border);
      background:var(--card);
    }
    .brand{ display:flex; align-items:center; gap:10px; font-weight:900; }
    .logo{ width:34px; height:34px; border-radius:12px; background:var(--text); }
    .muted{ color:var(--muted); }
    .actions{ display:flex; align-items:center; gap:10px; }

    .content{
      max-width:1100px;
      margin:0 auto;
      padding:14px;
      padding-bottom:80px; /* space for bottom nav on mobile */
    }

    /* Buttons */
    .btn{
      padding:10px 12px;
      border-radius:999px;
      border:1px solid var(--border);
      background:var(--card);
      cursor:pointer;
      font-weight:800;
      font-size:13px;
      color:var(--text);
    }
    .btn-primary{
      background:var(--text);
      color:var(--bg);
      border:1px solid var(--text);
    }

    /* Sidebar items */
    .side-head{
      display:flex; align-items:center; gap:10px;
      margin-bottom:14px;
      font-weight:900;
    }
    .side-item{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid transparent;
      color:var(--text);
      font-weight:800;
      margin-bottom:8px;
    }
    .side-item.active{
      border-color: var(--border);
      background: rgba(0,0,0,0.03);
    }
    html[data-theme="dark"] .side-item.active{
      background: rgba(255,255,255,0.06);
    }

    .side-foot{
      position:absolute;
      bottom:14px;
      left:14px;
      right:14px;
      border-top:1px solid var(--border);
      padding-top:12px;
    }

    /* Bottom nav (mobile) */
    .bottomnav{
      position:fixed;
      left:0; right:0; bottom:0;
      border-top:1px solid var(--border);
      background:var(--card);
      display:flex;
      justify-content:space-around;
      padding:10px 10px;
      z-index:20;
    }
    .nav-item{
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:4px;
      font-size:11px;
      font-weight:900;
      color:var(--muted);
      min-width:70px;
    }
    .dot{
      width:8px; height:8px;
      border-radius:999px;
      background:transparent;
      border:1px solid var(--border);
    }
    .nav-item.active{
      color:var(--text);
    }
    .nav-item.active .dot{
      background:var(--text);
      border-color:var(--text);
    }

    @media (min-width: 1024px){
      .bottomnav{ display:none; }
      .content{ padding-bottom:18px; }
    }
  </style>
</head>
<body>

<div class="shell">

  <!-- DESKTOP SIDEBAR -->
  <aside class="sidebar">
    <div class="side-head">
      <div class="logo"></div>
      <div>
        <?= htmlspecialchars(Env::get('APP_NAME','Zoro')) ?>
        <div class="muted" style="font-size:12px; font-weight:700;">
          <?= htmlspecialchars($mode) ?> mode<?= $isDark ? ' • dark' : '' ?>
        </div>
      </div>
    </div>

    <?php
      $isDash = str_contains($path, '/dashboard') || str_ends_with($path, '/');
      $isTransactions = str_contains($path, '/transactions');
      $isSettings = str_contains($path, '/settings');
    ?>

    <a class="side-item <?= $isDash ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/dashboard">
      <span>Dashboard</span><span class="muted" style="font-weight:900;">›</span>
    </a>

    <a class="side-item <?= $isTransactions ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/transactions">
      <span>Transactions</span><span class="muted" style="font-weight:900;">›</span>
    </a>

    <a class="side-item <?= $isSettings ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/settings">
      <span>Settings</span><span class="muted" style="font-weight:900;">›</span>
    </a>

    <div class="side-foot">
      <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
        <button class="btn" onclick="toggleMode()">Switch</button>

        <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/logout" style="margin:0;">
          <button class="btn btn-primary" type="submit">Logout</button>
        </form>
      </div>
      <p class="muted" style="margin:10px 0 0; font-size:12px;">
        Desktop navigation
      </p>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- MOBILE TOPBAR -->
    <div class="topbar">
      <div class="brand">
        <div class="logo"></div>
        <div>
          <?= htmlspecialchars(Env::get('APP_NAME','Zoro')) ?>
          <div class="muted" style="font-size:12px; font-weight:700;">
            <?= htmlspecialchars($mode) ?> mode<?= $isDark ? ' • dark' : '' ?>
          </div>
        </div>
      </div>

      <div class="actions">
        <button class="btn" onclick="toggleMode()">Switch</button>
        <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/logout" style="margin:0;">
          <button class="btn btn-primary" type="submit">Logout</button>
        </form>
      </div>
    </div>

    <div class="content">
      <?= $content ?>
    </div>

  </main>
</div>

<!-- MOBILE BOTTOM NAV -->
<?php
  $isDash2 = str_contains($path, '/dashboard') || str_ends_with($path, '/');
  $isTransactions2 = str_contains($path, '/transactions');
  $isSettings2 = str_contains($path, '/settings');
?>
<nav class="bottomnav" aria-label="Bottom navigation">
  <a class="nav-item <?= $isDash2 ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/dashboard">
    <span class="dot"></span>
    <span>Home</span>
  </a>

  <a class="nav-item <?= $isTransactions2 ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/transactions">
    <span class="dot"></span>
    <span>Txns</span>
  </a>

  <a class="nav-item <?= $isSettings2 ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/settings">
    <span class="dot"></span>
    <span>Settings</span>
  </a>
</nav>

<script>
const BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;

async function toggleMode(){
  try{
    const res = await fetch(BASE_URL + '/toggle-mode', { method: 'POST' });
    const data = await res.json();
    if (data && data.ok) {
      location.reload();
      return;
    }
    alert((data && data.message) ? data.message : 'Could not toggle mode');
  } catch (e) {
    alert('Network error');
  }
}
</script>

</body>
</html>
