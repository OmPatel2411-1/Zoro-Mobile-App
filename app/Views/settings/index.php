<?php
declare(strict_types=1);

/**
 * Variables available:
 * @var array  $user
 * @var string $mode
 * @var array  $settings
 * @var string $baseUrl
 */

require_once __DIR__ . '/../../Middleware/Csrf.php';

$isAdvanced = ($mode ?? 'simple') === 'advanced';

$allowDataUsage = (int)($settings['allow_data_usage'] ?? 0) === 1;
$darkMode       = (int)($settings['dark_mode'] ?? 0) === 1;
$bankConnected  = (int)($settings['bank_connected'] ?? 0) === 1;

$csrfToken = Csrf::token();
?>

<style>
  /* ✅ Scope ALL styles to settings page only */
  .settings-page .page-title{ margin:0 0 6px; font-size:20px; font-weight:900; color:var(--text); }
  .settings-page .page-sub{ margin:0 0 14px; color:var(--muted); font-size:13px; }

  /* For instant dark-mode switch WITHOUT refresh */
  html[data-theme="dark"]{
    --bg:#0b0b0c;
    --text:#f5f5f5;
    --muted:#a1a1aa;
    --border:#232326;
    --card:#111114;
    --black:#000;
    --white:#fff;
  }

  .settings-page .grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:12px;
  }
  .settings-page .card{
    border:1px solid var(--border);
    border-radius:16px;
    padding:16px;
    background:var(--card);
  }
  .settings-page .card h3{ margin:0 0 6px; font-size:14px; font-weight:900; color:var(--text); }
  .settings-page .muted{ color:var(--muted); font-size:13px; line-height:1.35; }

  .settings-page .row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:10px 0;
    border-top:1px solid var(--border);
  }
  .settings-page .row:first-child{ border-top:none; padding-top:0; }
  .settings-page .row:last-child{ padding-bottom:0; }

  .settings-page .btn{
    padding:10px 12px;
    border-radius:999px;
    border:1px solid var(--border);
    background:var(--card);
    cursor:pointer;
    font-weight:800;
    font-size:13px;
    color:var(--text);
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    line-height:1;
    user-select:none;
  }
  .settings-page .btn-primary{
    border:1px solid var(--text);
    background:var(--text);
    color:var(--bg);
  }
  .settings-page .btn-black{
    border:1px solid var(--text);
    background:var(--text);
    color:var(--bg);
  }

  /* Toggle */
  .settings-page .toggle {
    width: 44px;
    height: 26px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: rgba(0,0,0,0.06);
    position: relative;
    cursor: pointer;
    flex: 0 0 auto;
  }
  html[data-theme="dark"] .settings-page .toggle{
    background: rgba(255,255,255,0.08);
  }

  .settings-page .toggle::after {
    content: "";
    width: 22px;
    height: 22px;
    border-radius: 999px;
    background: var(--card);
    border: 1px solid var(--border);
    position: absolute;
    top: 1px;
    left: 1px;
    transition: all .18s ease;
  }
  .settings-page .toggle.on {
    background: var(--text);
    border-color: var(--text);
  }
  .settings-page .toggle.on::after {
    left: 20px;
    border-color: var(--text);
  }

  .settings-page .toast{
    position:fixed;
    left:50%;
    bottom:18px;
    transform:translateX(-50%);
    background:var(--text);
    color:var(--bg);
    padding:10px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    opacity:0;
    pointer-events:none;
    transition:opacity .2s ease;
    z-index:9999;
  }
  .settings-page .toast.show{ opacity:1; }

  .settings-page .btn[disabled]{
    opacity:.55;
    cursor:not-allowed;
  }

  @media (max-width: 900px){
    .settings-page .grid{ grid-template-columns: 1fr; }
  }
</style>

<div class="settings-page">
  <h2 class="page-title">Settings</h2>
  <p class="page-sub">Manage privacy, security, and preferences. (<?= $isAdvanced ? 'Advanced' : 'Simple' ?>)</p>

  <div class="grid">

    <!-- Data & Privacy -->
    <div class="card">
      <h3>Data & Privacy</h3>
      <p class="muted" style="margin:0 0 10px;">
        Control what data is used and manage connected services.
      </p>

      <div class="row">
        <div>
          <div style="font-weight:900; color:var(--text);">Allow data usage</div>
          <div class="muted">Use anonymized data to improve insights.</div>
        </div>
        <div
          id="toggle-allow"
          class="toggle <?= $allowDataUsage ? 'on' : '' ?>"
          role="switch"
          aria-checked="<?= $allowDataUsage ? 'true' : 'false' ?>"
          onclick="onToggle('allow_data_usage', this)"
        ></div>
      </div>

      <div class="row">
        <div>
          <div style="font-weight:900; color:var(--text);">Bank connection</div>
          <div class="muted"><?= $bankConnected ? 'Connected (demo)' : 'Not connected (demo)' ?></div>
        </div>
        <button class="btn" type="button" disabled title="Bank integration is in demo mode">
          <?= $bankConnected ? 'Disconnect' : 'Connect' ?>
        </button>
      </div>

      <?php if ($isAdvanced): ?>
        <div class="row">
          <div>
            <div style="font-weight:900; color:var(--text);">Export</div>
            <div class="muted">Use Reports to export CSV or print-friendly PDF.</div>
          </div>
          <a class="btn" href="<?= htmlspecialchars($baseUrl) ?>/reports">Open</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Appearance & Security -->
    <div class="card">
      <h3>Appearance & Security</h3>
      <p class="muted" style="margin:0 0 10px;">
        Keep your account secure and customize experience.
      </p>

      <div class="row">
        <div>
          <div style="font-weight:900; color:var(--text);">Dark mode</div>
          <div class="muted">Saves to DB instantly.</div>
        </div>
        <div
          id="toggle-dark"
          class="toggle <?= $darkMode ? 'on' : '' ?>"
          role="switch"
          aria-checked="<?= $darkMode ? 'true' : 'false' ?>"
          onclick="onToggle('dark_mode', this)"
        ></div>
      </div>

      <div class="row">
        <div>
          <div style="font-weight:900; color:var(--text);">Login history</div>
          <div class="muted">See device/IP logins.</div>
        </div>
        <a class="btn" href="<?= htmlspecialchars($baseUrl) ?>/security/sessions">View</a>
      </div>

      <div class="row">
        <div>
          <div style="font-weight:900; color:var(--text);">Logout all devices</div>
          <div class="muted">End sessions everywhere.</div>
        </div>

        <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/security/logout-all" style="margin:0;" onsubmit="return confirmLogoutAll();">
          <?= Csrf::input() ?>
          <button class="btn btn-black" type="submit">Logout</button>
        </form>
      </div>
    </div>

  </div>

  <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn" href="<?= htmlspecialchars($baseUrl) ?>/dashboard">Back to Dashboard</a>
  </div>

  <div id="toast" class="toast">Saved</div>
</div>

<script>
const BASE_URL = <?= json_encode($baseUrl ?? '', JSON_UNESCAPED_SLASHES) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken ?? '', JSON_UNESCAPED_SLASHES) ?>;

function confirmLogoutAll(){
  return confirm('Logout from all devices? You will need to login again on other sessions.');
}

function showToast(msg){
  const t = document.getElementById('toast');
  if(!t) return;
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 1100);
}

function setToggle(el, on){
  if(!el) return;
  if(on){
    el.classList.add('on');
    el.setAttribute('aria-checked', 'true');
  }else{
    el.classList.remove('on');
    el.setAttribute('aria-checked', 'false');
  }
}

function getToggleState(el){
  return !!el && el.classList.contains('on');
}

function ensureDarkCssLoaded(load){
  const id = 'zoro-dark-css';
  let link = document.getElementById(id);

  if(load){
    if(!link){
      link = document.createElement('link');
      link.id = id;
      link.rel = 'stylesheet';
      link.href = BASE_URL + '/assets/css/dark.css?v=2';
      document.head.appendChild(link);
    }
  }else{
    if(link && link.parentNode){
      link.parentNode.removeChild(link);
    }
  }
}

function applyDarkMode(on){
  const html = document.documentElement;
  if(on){
    html.setAttribute('data-theme', 'dark');
    ensureDarkCssLoaded(true);
  }else{
    html.removeAttribute('data-theme');
    ensureDarkCssLoaded(false);
  }
}

async function safeJson(res){
  const ct = (res.headers.get('content-type') || '').toLowerCase();
  if(ct.includes('application/json')){
    return await res.json();
  }
  const text = await res.text();
  return { ok:false, message:'Unexpected response (not JSON).', raw:text };
}

async function savePrefs(payload){
  // ✅ CSRF token included in body for POST /settings/save
  payload._csrf = CSRF_TOKEN;

  const form = new URLSearchParams(payload);

  const res = await fetch(BASE_URL + '/settings/save', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: form.toString()
  });

  if(res.status === 401){
    window.location.href = BASE_URL + '/login';
    return { ok:false, message:'Unauthorized' };
  }

  const data = await safeJson(res);

  if(!data || !data.ok){
    throw new Error((data && data.message) ? data.message : 'Save failed');
  }
  return data;
}

async function onToggle(key, el){
  const nextState = !getToggleState(el);
  setToggle(el, nextState);

  if(key === 'dark_mode'){
    applyDarkMode(nextState);
  }

  const allowEl = document.getElementById('toggle-allow');
  const darkEl  = document.getElementById('toggle-dark');

  const payload = {
    allow_data_usage: getToggleState(allowEl) ? '1' : '0',
    dark_mode: getToggleState(darkEl) ? '1' : '0'
  };

  try{
    await savePrefs(payload);
    showToast('Saved');
  }catch(e){
    setToggle(el, !nextState);
    if(key === 'dark_mode'){
      applyDarkMode(!nextState);
    }
    showToast('Not saved');
    alert(e && e.message ? e.message : 'Could not save');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const darkEl = document.getElementById('toggle-dark');
  if(darkEl){
    applyDarkMode(getToggleState(darkEl));
  }
});
</script>
