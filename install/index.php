<?php
// ── Sécurité : bloquer si déjà installé ────────────────────────────────────
if (file_exists(__DIR__ . '/installed.lock')) {
    http_response_code(403);
    die('🔒 Installation déjà effectuée. Supprime <code>install/installed.lock</code> pour recommencer.');
}

// ── Exigences système ───────────────────────────────────────────────────────
function checkRequirements(): array {
    $root   = dirname(__DIR__);
    $checks = [];

    $checks[] = ['label' => 'PHP ≥ 8.1',              'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'val' => PHP_VERSION];
    $checks[] = ['label' => 'Extension PDO',           'ok' => extension_loaded('pdo'),       'val' => extension_loaded('pdo')       ? 'OK' : 'Manquante'];
    $checks[] = ['label' => 'Extension PDO MySQL',     'ok' => extension_loaded('pdo_mysql'), 'val' => extension_loaded('pdo_mysql') ? 'OK' : 'Manquante'];
    $checks[] = ['label' => 'Extension mbstring',      'ok' => extension_loaded('mbstring'),  'val' => extension_loaded('mbstring')  ? 'OK' : 'Manquante'];
    $checks[] = ['label' => 'Extension openssl',       'ok' => extension_loaded('openssl'),   'val' => extension_loaded('openssl')   ? 'OK' : 'Manquante'];
    $checks[] = ['label' => 'Extension json',          'ok' => extension_loaded('json'),      'val' => extension_loaded('json')      ? 'OK' : 'Manquante'];

    // Permissions écriture
    $cfgWritable  = is_writable($root . '/includes/config.php');
    $instWritable = is_writable($root . '/install');
    $uplWritable  = is_writable($root . '/uploads');

    $checks[] = [
        'label' => 'includes/config.php — écriture',
        'ok'    => $cfgWritable,
        'val'   => $cfgWritable  ? 'Accessible' : '❌ Permission manquante',
        'perm'  => true,
    ];
    $checks[] = [
        'label' => 'install/ — écriture (lock)',
        'ok'    => $instWritable,
        'val'   => $instWritable ? 'Accessible' : '❌ Permission manquante',
        'perm'  => true,
    ];
    $checks[] = [
        'label' => 'uploads/ — écriture',
        'ok'    => $uplWritable,
        'val'   => $uplWritable  ? 'Accessible' : '⚠️ Non accessible',
        'warn'  => !$uplWritable,
    ];

    return $checks;
}

function hasPermIssue(array $checks): bool {
    return (bool) array_filter($checks, fn($c) => ($c['perm'] ?? false) && !$c['ok']);
}

$requirements = checkRequirements();
$allOk        = array_reduce($requirements, fn($c, $r) => $c && ($r['ok'] || ($r['warn'] ?? false)), true);
$permIssue    = hasPermIssue($requirements);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>✨ Installation — ArtiCMS</title>
<style>
  :root {
    --bg:      #0d0d1a;
    --surface: #13131f;
    --card:    #1a1a2e;
    --border:  #2a2a45;
    --accent:  #7c6af7;
    --accent2: #f76ac8;
    --accent3: #6af7c8;
    --text:    #e8e8f0;
    --muted:   #7070a0;
    --ok:      #4ade80;
    --err:     #f87171;
    --warn:    #fbbf24;
    --radius:  14px;
    --inp-bg:  #0f0f20;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 16px 80px;
    overflow-x: hidden;
  }

  /* ── Fond animé ──────────────────────────────────────────────────────────── */
  body::before {
    content: '';
    position: fixed; inset: 0; z-index: -1;
    background:
      radial-gradient(ellipse 700px 400px at 20% 10%, rgba(124,106,247,.18) 0%, transparent 70%),
      radial-gradient(ellipse 500px 300px at 80% 80%, rgba(247,106,200,.12) 0%, transparent 70%),
      radial-gradient(ellipse 600px 300px at 50% 50%, rgba(106,247,200,.07) 0%, transparent 70%),
      var(--bg);
    animation: bgMove 12s ease-in-out infinite alternate;
  }
  @keyframes bgMove {
    from { filter: hue-rotate(0deg); }
    to   { filter: hue-rotate(30deg); }
  }

  /* ── Header ──────────────────────────────────────────────────────────────── */
  .installer-header {
    text-align: center;
    margin-bottom: 40px;
    animation: fadeDown .6s ease both;
  }
  .logo-ring {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 32px;
    box-shadow: 0 0 40px rgba(124,106,247,.4);
    margin-bottom: 16px;
    animation: pulse 3s ease-in-out infinite;
  }
  @keyframes pulse {
    0%,100% { box-shadow: 0 0 30px rgba(124,106,247,.4); transform: scale(1); }
    50%      { box-shadow: 0 0 60px rgba(124,106,247,.7); transform: scale(1.04); }
  }
  .installer-header h1 { font-size: 2rem; font-weight: 800; letter-spacing: -.5px; }
  .installer-header h1 span {
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }
  .installer-header p { color: var(--muted); margin-top: 8px; font-size: .95rem; }

  /* ── Steps nav ───────────────────────────────────────────────────────────── */
  .steps-nav {
    display: flex; align-items: center; gap: 0;
    margin-bottom: 36px;
    animation: fadeDown .7s ease both;
  }
  .step-dot {
    display: flex; flex-direction: column; align-items: center; gap: 5px;
    cursor: default;
  }
  .step-dot .dot {
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 2px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700;
    transition: all .3s ease;
    background: var(--card);
    color: var(--muted);
  }
  .step-dot.active .dot {
    border-color: var(--accent);
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    box-shadow: 0 0 20px rgba(124,106,247,.5);
  }
  .step-dot.done .dot {
    border-color: var(--ok);
    background: var(--ok);
    color: #000;
  }
  .step-dot label { font-size: 11px; color: var(--muted); white-space: nowrap; }
  .step-dot.active label { color: var(--accent); }
  .step-dot.done  label { color: var(--ok); }
  .step-line {
    flex: 1; height: 2px; min-width: 24px; max-width: 60px;
    background: var(--border);
    transition: background .4s ease;
  }
  .step-line.done { background: linear-gradient(90deg, var(--ok), var(--accent3)); }

  /* ── Card ────────────────────────────────────────────────────────────────── */
  .installer-card {
    width: 100%; max-width: 620px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 36px;
    animation: fadeUp .4s ease both;
  }
  @keyframes fadeUp   { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: none; } }
  @keyframes fadeDown { from { opacity:0; transform: translateY(-10px); } to { opacity:1; transform: none; } }

  .card-step { display: none; }
  .card-step.active { display: block; animation: fadeUp .35s ease both; }

  .card-step h2 { font-size: 1.3rem; font-weight: 700; margin-bottom: 6px; }
  .card-step .subtitle { color: var(--muted); font-size: .9rem; margin-bottom: 28px; line-height: 1.5; }

  /* ── Form ────────────────────────────────────────────────────────────────── */
  .form-group { margin-bottom: 18px; }
  .form-group label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 6px; color: #bbb; }
  .form-group label .req { color: #f76ac8; margin-left: 3px; font-size: .9em; }
  .form-group input, .form-group select {
    width: 100%;
    padding: 11px 14px;
    background: var(--inp-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-size: .95rem;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  .form-group input:focus, .form-group select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(124,106,247,.15);
  }
  .form-group small { display: block; margin-top: 5px; color: var(--muted); font-size: .8rem; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

  /* ── Buttons ─────────────────────────────────────────────────────────────── */
  .btn-row { display: flex; gap: 12px; margin-top: 28px; justify-content: flex-end; }
  .btn {
    padding: 12px 24px; border: none; border-radius: 8px;
    font-size: .9rem; font-weight: 700; cursor: pointer;
    transition: all .2s; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    box-shadow: 0 4px 20px rgba(124,106,247,.3);
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 28px rgba(124,106,247,.5); }
  .btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }
  .btn-ghost {
    background: transparent; color: var(--muted);
    border: 1px solid var(--border);
  }
  .btn-ghost:hover { border-color: var(--accent); color: var(--text); }
  .btn-ok { background: var(--ok); color: #000; }
  .btn-ok:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(74,222,128,.4); }
  .btn-danger { background: rgba(248,113,113,.15); color: var(--err); border: 1px solid rgba(248,113,113,.3); }

  /* ── Requirements list ───────────────────────────────────────────────────── */
  .req-list { list-style: none; display: flex; flex-direction: column; gap: 10px; }
  .req-list li {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-radius: 8px;
    background: rgba(255,255,255,.03);
    border: 1px solid var(--border);
    font-size: .9rem;
  }
  .req-list li .req-ok   { color: var(--ok);   font-weight: 700; }
  .req-list li .req-err  { color: var(--err);  font-weight: 700; }
  .req-list li .req-warn { color: var(--warn); font-weight: 700; }
  .req-list li .req-val { color: var(--muted); font-size: .8rem; }

  .alert {
    padding: 12px 16px; border-radius: 8px; font-size: .9rem;
    margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px;
  }
  .alert-err  { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3); color: var(--err); }
  .alert-ok   { background: rgba(74,222,128,.1);  border: 1px solid rgba(74,222,128,.3);  color: var(--ok); }
  .alert-warn { background: rgba(251,191,36,.1);  border: 1px solid rgba(251,191,36,.3);  color: var(--warn); }

  /* ── DB test badge ───────────────────────────────────────────────────────── */
  #db-test-result {
    margin-top: 10px; padding: 10px 14px; border-radius: 8px;
    font-size: .875rem; display: none;
  }

  /* ── Progress ────────────────────────────────────────────────────────────── */
  #progress-wrap { display: flex; flex-direction: column; align-items: center; gap: 20px; }
  .progress-ring-wrap { position: relative; width: 120px; height: 120px; }
  .progress-ring { transform: rotate(-90deg); }
  .progress-ring circle.bg { stroke: var(--border); fill: none; }
  .progress-ring circle.fg {
    fill: none;
    stroke: url(#prog-grad);
    stroke-linecap: round;
    transition: stroke-dashoffset .5s ease;
  }
  .progress-pct {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 800;
  }
  .progress-msg {
    font-size: .9rem; color: var(--muted); text-align: center; min-height: 24px; transition: opacity .3s;
  }
  .progress-log {
    width: 100%; max-height: 160px; overflow-y: auto;
    background: var(--inp-bg); border: 1px solid var(--border); border-radius: 8px;
    padding: 12px; font-size: .78rem; font-family: monospace; color: var(--muted);
    line-height: 1.8;
  }
  .progress-log .ok-line  { color: var(--ok); }
  .progress-log .err-line { color: var(--err); }

  /* ── Success ─────────────────────────────────────────────────────────────── */
  #success-wrap { text-align: center; padding: 10px 0; }
  #success-wrap .big-emoji { font-size: 72px; line-height: 1; margin-bottom: 16px; animation: boing .6s ease both; }
  @keyframes boing {
    0%   { transform: scale(0); }
    60%  { transform: scale(1.2); }
    80%  { transform: scale(.95); }
    100% { transform: scale(1); }
  }
  #success-wrap h2 { font-size: 1.6rem; font-weight: 800; margin-bottom: 8px; }
  #success-wrap p  { color: var(--muted); margin-bottom: 24px; line-height: 1.6; }
  .success-links { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }

  /* ── Password strength ───────────────────────────────────────────────────── */
  .pwd-bar { height: 4px; border-radius: 4px; margin-top: 6px; transition: all .3s; background: var(--border); }
  .pwd-bar[data-s="1"] { width: 25%; background: var(--err); }
  .pwd-bar[data-s="2"] { width: 50%; background: var(--warn); }
  .pwd-bar[data-s="3"] { width: 75%; background: #60a5fa; }
  .pwd-bar[data-s="4"] { width: 100%; background: var(--ok); }

  /* ── Confetti canvas ─────────────────────────────────────────────────────── */
  #confetti-canvas { position: fixed; inset: 0; pointer-events: none; z-index: 999; }

  /* ── Tooltip ─────────────────────────────────────────────────────────────── */
  .tooltip { position: relative; display: inline-flex; align-items: center; margin-left: 5px; cursor: help; }
  .tooltip::after {
    content: attr(data-tip);
    position: absolute; left: 50%; bottom: calc(100% + 6px); transform: translateX(-50%);
    background: #2a2a45; color: #ddd; font-size: .75rem; padding: 4px 8px; border-radius: 6px;
    white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .2s;
  }
  .tooltip:hover::after { opacity: 1; }

  /* ── Toggle password ─────────────────────────────────────────────────────── */
  .pwd-wrap { position: relative; }
  .pwd-wrap input { padding-right: 42px; }
  .pwd-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: var(--muted);
    font-size: 16px; padding: 0; transition: color .2s;
  }
  .pwd-toggle:hover { color: var(--text); }

  /* ── Responsive ──────────────────────────────────────────────────────────── */
  @media (max-width: 480px) {
    .form-row { grid-template-columns: 1fr; }
    .installer-card { padding: 24px 18px; }
    .steps-nav .step-dot label { display: none; }
  }

  /* ── Demo choice cards ─────────────────────────────────────────────────── */
  .demo-choice-group { margin-top: 24px; margin-bottom: 4px; }
  .demo-choice-group > label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 12px; color: #bbb; }
  .demo-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }
  @media (max-width: 520px) { .demo-cards { grid-template-columns: 1fr; } }
  .demo-card {
    position: relative;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 16px 16px;
    cursor: pointer;
    transition: border-color .2s, background .2s, box-shadow .2s;
    background: var(--card);
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .demo-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
  .demo-card:has(input:checked) {
    border-color: var(--accent);
    background: rgba(124,106,247,.10);
    box-shadow: 0 0 0 3px rgba(124,106,247,.18);
  }
  .demo-card-icon { font-size: 1.9rem; line-height: 1; }
  .demo-card-title { font-weight: 700; font-size: 1rem; margin-top: 4px; }
  .demo-card-desc  { font-size: .8rem; color: var(--muted); line-height: 1.5; }
  .demo-card-badge {
    display: inline-block;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    margin-top: 6px;
    width: fit-content;
    letter-spacing: .5px;
    text-transform: uppercase;
  }
  .demo-card-list {
    margin: 6px 0 0 0;
    padding: 0;
    list-style: none;
    font-size: .78rem;
    color: var(--muted);
    line-height: 1.7;
  }
  .demo-card-list li::before { content: '✓  '; color: var(--accent3); font-weight: 700; }
</style>
</head>
<body>

<canvas id="confetti-canvas"></canvas>

<!-- HEADER -->
<header class="installer-header">
  <div class="logo-ring">🚀</div>
  <h1>Bienvenue dans <span>ArtiCMS</span></h1>
  <p>L'installeur qui rend les autres jaloux&nbsp;😏 — Prêt en 2 minutes chrono.</p>
</header>

<!-- STEPS NAV -->
<div class="steps-nav" id="stepsNav">
  <div class="step-dot done" id="sdot-0">
    <div class="dot">✓</div>
    <label>Accueil</label>
  </div>
  <div class="step-line" id="sline-0"></div>
  <div class="step-dot" id="sdot-1">
    <div class="dot">2</div>
    <label>Système</label>
  </div>
  <div class="step-line" id="sline-1"></div>
  <div class="step-dot" id="sdot-2">
    <div class="dot">3</div>
    <label>Base de données</label>
  </div>
  <div class="step-line" id="sline-2"></div>
  <div class="step-dot" id="sdot-3">
    <div class="dot">4</div>
    <label>Site</label>
  </div>
  <div class="step-line" id="sline-3"></div>
  <div class="step-dot" id="sdot-4">
    <div class="dot">5</div>
    <label>Admin</label>
  </div>
  <div class="step-line" id="sline-4"></div>
  <div class="step-dot" id="sdot-5">
    <div class="dot">🎉</div>
    <label>C'est parti !</label>
  </div>
</div>

<!-- CARD -->
<div class="installer-card">

  <!-- ══ STEP 0 — Welcome ═══════════════════════════════════════════════════ -->
  <div class="card-step active" id="step-0">
    <h2>👋 Salut toi !</h2>
    <p class="subtitle">
      On va installer <strong>ArtiCMS</strong> ensemble. C'est pas sorcier, promis 🤙<br>
      Suis les étapes, réponds aux questions, et dans 2 minutes tu auras ton CMS qui tourne.<br><br>
      ⚠️ Assure-toi d'avoir les <strong>infos de ta base de données</strong> sous la main avant de commencer.
    </p>

    <div class="alert alert-warn">
      ⚡ <span>Cet installeur va <strong>créer les tables</strong> et <strong>écrire le fichier de config</strong>. Si la BDD existe déjà, les tables existantes ne seront <strong>pas</strong> écrasées.</span>
    </div>

    <div class="btn-row" style="justify-content:center; margin-top:32px;">
      <button class="btn btn-primary" onclick="goStep(1)">C'est parti ! 🚀</button>
    </div>
  </div>

  <!-- ══ STEP 1 — Vérifications ════════════════════════════════════════════ -->
  <div class="card-step" id="step-1">
    <h2>🔍 Vérifions ton serveur</h2>
    <p class="subtitle">Avant tout, on s'assure que tout est en ordre côté PHP et permissions.</p>

    <?php $checks = $requirements; ?>
    <ul class="req-list">
      <?php foreach ($checks as $c): ?>
      <li>
        <span><?= $c['ok'] ? '✅' : (($c['warn'] ?? false) ? '⚠️' : '❌') ?> &nbsp;<?= htmlspecialchars($c['label']) ?></span>
        <span class="<?= $c['ok'] ? 'req-ok' : (($c['warn'] ?? false) ? 'req-warn' : 'req-err') ?> req-val"><?= htmlspecialchars($c['val']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($permIssue): ?>
    <div class="alert alert-err" style="margin-top:18px;">
      🔐 <span>Permissions manquantes. Apache ne peut pas écrire les fichiers nécessaires.<br>
      Lance cette commande sur le serveur (une seule fois) :</span>
    </div>
    <div style="background:#0a0a18;border:1px solid #2a2a45;border-radius:10px;padding:14px 18px;margin:12px 0;position:relative;">
      <code id="perm-cmd" style="font-family:monospace;font-size:13px;color:#6af7c8;white-space:pre-wrap;">sudo bash <?= htmlspecialchars(dirname(__DIR__)) ?>/fix-perms.sh</code>
      <button onclick="navigator.clipboard.writeText(document.getElementById('perm-cmd').textContent);showToast('✅ Commande copiée !')" style="position:absolute;top:10px;right:10px;background:#2a2a45;border:none;color:#aaa;padding:4px 10px;border-radius:6px;cursor:pointer;font-size:12px;">Copier</button>
    </div>
    <p style="font-size:12px;color:var(--muted);margin-bottom:6px;">Ensuite, <strong>recharge cette page</strong> pour vérifier à nouveau.</p>
    <?php elseif (!$allOk): ?>
    <div class="alert alert-err" style="margin-top:18px;">
      😬 <span>Certaines vérifications ont échoué. Corrige ça avant de continuer.</span>
    </div>
    <?php else: ?>
    <div class="alert alert-ok" style="margin-top:18px;">
      🎉 <span>Tout est nickel ! Ton serveur est prêt à accueillir ArtiCMS.</span>
    </div>
    <?php endif; ?>

    <div class="btn-row">
      <button class="btn btn-ghost" onclick="goStep(0)">← Retour</button>
      <button class="btn btn-primary" onclick="goStep(2)" <?= (!$allOk && !$permIssue) ? 'disabled title="Corrige les erreurs d\'abord"' : ($permIssue ? 'style="opacity:.4" title="Corrige les permissions d\'abord"' : '') ?>>
        Continuer →
      </button>
    </div>
  </div>

  <!-- ══ STEP 2 — Base de données ══════════════════════════════════════════ -->
  <div class="card-step" id="step-2">
    <h2>🗄️ Base de données</h2>
    <p class="subtitle">Les infos de connexion à ta base MySQL. Si elle n'existe pas encore, on peut la créer !</p>

    <div class="form-row">
      <div class="form-group">
        <label>Hôte MySQL <span class="tooltip" data-tip="Souvent '127.0.0.1' ou 'localhost'">ℹ️</span></label>
        <input type="text" id="db_host" value="127.0.0.1" placeholder="127.0.0.1">
      </div>
      <div class="form-group">
        <label>Port</label>
        <input type="text" id="db_port" value="3306" placeholder="3306">
      </div>
    </div>

    <div class="form-group">
      <label>Nom de la base de données <span class="req">*</span></label>
      <input type="text" id="db_name" placeholder="mon_cms" value="arti_cms">
      <small>Sera créée automatiquement si elle n'existe pas encore.</small>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Utilisateur MySQL <span class="req">*</span></label>
        <input type="text" id="db_user" placeholder="root">
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <div class="pwd-wrap">
          <input type="password" id="db_pass" placeholder="••••••">
          <button class="pwd-toggle" type="button" onclick="togglePwd('db_pass',this)">👁</button>
        </div>
      </div>
    </div>

    <button class="btn btn-ghost" style="margin-top:4px; width:100%; justify-content:center;" onclick="testDb()">
      🔌 Tester la connexion
    </button>
    <div id="db-test-result"></div>

    <div class="btn-row">
      <button class="btn btn-ghost" onclick="goStep(1)">← Retour</button>
      <button class="btn btn-primary" id="btn-step2-next" onclick="goStep(3)">Continuer →</button>
    </div>
  </div>

  <!-- ══ STEP 3 — Config site ═══════════════════════════════════════════════ -->
  <div class="card-step" id="step-3">
    <h2>⚙️ Ton site</h2>
    <p class="subtitle">Quelques infos sur ton projet. Tout ça sera modifiable depuis le backoffice après l'installation.</p>

    <div class="form-group">
      <label>URL de base <span class="req">*</span> <span class="tooltip" data-tip="Pas de slash final — ex: https://monsite.fr">ℹ️</span></label>
      <input type="text" id="site_url" placeholder="https://monsite.fr" value="<?php
        $scheme  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $path    = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
        echo htmlspecialchars($scheme . '://' . $_SERVER['HTTP_HOST'] . $path);
      ?>">
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Nom de l'entreprise <span class="req">*</span></label>
        <input type="text" id="company_name" placeholder="Mon Entreprise" value="">
      </div>
      <div class="form-group">
        <label>Région</label>
        <input type="text" id="company_region" placeholder="Alsace" value="France">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Email de contact <span class="req">*</span></label>
        <input type="email" id="company_email" placeholder="contact@monsite.fr">
      </div>
      <div class="form-group">
        <label>Téléphone <span class="tooltip" data-tip="Format international: +33...">ℹ️</span></label>
        <input type="text" id="company_phone" placeholder="+33600000000">
      </div>
    </div>

    <hr style="border-color: var(--border); margin: 22px 0;">
    <p style="font-size:.85rem; color:var(--muted); margin-bottom:16px;">📬 SMTP pour les emails (optionnel — modifiable plus tard)</p>

    <div class="form-row">
      <div class="form-group">
        <label>Serveur SMTP</label>
        <input type="text" id="smtp_host" placeholder="smtp.gmail.com">
      </div>
      <div class="form-group">
        <label>Port SMTP</label>
        <input type="text" id="smtp_port" value="587">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Utilisateur SMTP</label>
        <input type="text" id="smtp_user" placeholder="toi@gmail.com">
      </div>
      <div class="form-group">
        <label>Mot de passe SMTP</label>
        <div class="pwd-wrap">
          <input type="password" id="smtp_pass" placeholder="••••••">
          <button class="pwd-toggle" type="button" onclick="togglePwd('smtp_pass',this)">👁</button>
        </div>
      </div>
    </div>

    <div class="btn-row">
      <button class="btn btn-ghost" onclick="goStep(2)">← Retour</button>
      <button class="btn btn-primary" onclick="validateStep3()">Continuer →</button>
    </div>
  </div>

  <!-- ══ STEP 4 — Compte admin ══════════════════════════════════════════════ -->
  <div class="card-step" id="step-4">
    <h2>🔐 Ton compte admin</h2>
    <p class="subtitle">Le premier compte administrateur. Choisis bien ton mot de passe — tu peux toujours le changer plus tard.</p>

    <div class="form-group">
      <label>Nom d'utilisateur <span class="req">*</span></label>
      <input type="text" id="admin_user" placeholder="admin" value="admin" autocomplete="off">
    </div>

    <div class="form-group">
      <label>Email admin <span class="req">*</span></label>
      <input type="email" id="admin_email" placeholder="admin@monsite.fr" autocomplete="off">
    </div>

    <div class="form-group">
      <label>Nom affiché <span class="req">*</span></label>
      <input type="text" id="admin_display" placeholder="Super Admin" value="Admin">
    </div>

    <div class="form-group">
      <label>Mot de passe <span class="req">*</span></label>
      <div class="pwd-wrap">
        <input type="password" id="admin_pass" placeholder="Min. 8 caractères" autocomplete="new-password" oninput="checkPwd(this)">
        <button class="pwd-toggle" type="button" onclick="togglePwd('admin_pass',this)">👁</button>
      </div>
      <div class="pwd-bar" id="pwd-bar" data-s="0"></div>
      <small id="pwd-hint">Au moins 8 caractères, 1 majuscule, 1 chiffre 💪</small>
    </div>

    <div class="form-group">
      <label>Confirmer le mot de passe <span class="req">*</span></label>
      <div class="pwd-wrap">
        <input type="password" id="admin_pass2" placeholder="••••••••" autocomplete="new-password">
        <button class="pwd-toggle" type="button" onclick="togglePwd('admin_pass2',this)">👁</button>
      </div>
    </div>

    <!-- Démo choice -->
    <div class="demo-choice-group">
      <label>Données de démarrage</label>
      <div class="demo-cards">

        <label class="demo-card" for="demo-none">
          <input type="radio" id="demo-none" name="demo_choice" value="0" checked>
          <span class="demo-card-icon">🧼</span>
          <span class="demo-card-title">Site vierge</span>
          <span class="demo-card-desc">Démarrer avec une installation propre, sans aucune donnée pré-remplie.</span>
        </label>

        <label class="demo-card" for="demo-yes">
          <input type="radio" id="demo-yes" name="demo_choice" value="1">
          <span class="demo-card-icon">🎨</span>
          <span class="demo-card-title">Données de démo</span>
          <span class="demo-card-badge">Recommandé pour tester</span>
          <ul class="demo-card-list">
            <li>Pages CMS (À propos, Mentions légales)</li>
            <li>Réalisations &amp; galerie exemples</li>
            <li>Contacts &amp; notes CRM</li>
            <li>Clients &amp; devis / facture</li>
            <li>Formulaire de contact</li>
          </ul>
        </label>

      </div>
    </div>

    <div class="btn-row">
      <button class="btn btn-ghost" onclick="goStep(3)">← Retour</button>
      <button class="btn btn-primary" onclick="validateStep4()">Installer ! 🚀</button>
    </div>
  </div>

  <!-- ══ STEP 5 — Installation ══════════════════════════════════════════════ -->
  <div class="card-step" id="step-5">
    <div id="progress-wrap">
      <svg class="progress-ring" width="120" height="120" viewBox="0 0 120 120">
        <defs>
          <linearGradient id="prog-grad" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%"   stop-color="#7c6af7"/>
            <stop offset="100%" stop-color="#f76ac8"/>
          </linearGradient>
        </defs>
        <circle class="bg" cx="60" cy="60" r="52" stroke-width="8"/>
        <circle class="fg" id="progress-ring-fg" cx="60" cy="60" r="52" stroke-width="8"
          stroke-dasharray="326.7" stroke-dashoffset="326.7"/>
      </svg>
      <div class="progress-ring-wrap" style="position:absolute;top:0">
        <!-- Overlaid pct is handled by JS positioning on the SVG -->
      </div>
      <div class="progress-pct" id="progress-pct" style="font-size:1.2rem;">0%</div>
      <div class="progress-msg" id="progress-msg">Initialisation...</div>
      <div class="progress-log" id="progress-log"></div>
    </div>

    <div id="success-wrap" style="display:none">
      <div class="big-emoji">🎉</div>
      <h2>C'est dans la boîte !</h2>
      <p>
        ArtiCMS est installé et prêt. Connecte-toi à ton backoffice pour commencer.<br>
        <strong style="color:var(--warn)">⚠️ Supprime ou protège le dossier <code>install/</code> une fois connecté !</strong>
      </p>

      <!-- Étapes manuelles si permissions insuffisantes -->
      <div id="manual-steps" style="display:none;text-align:left;margin:18px 0 8px;">
        <p style="font-weight:700;color:var(--warn);margin-bottom:12px;">🔐 Actions manuelles requises (permissions)</p>
      </div>

      <div class="success-links">
        <a href="../admin/login.php" class="btn btn-primary" style="text-decoration:none">→ Se connecter</a>
        <a href="../" class="btn btn-ghost" style="text-decoration:none">→ Voir le site</a>
      </div>
    </div>
  </div>

</div><!-- /.installer-card -->

<!-- ── SCRIPT ──────────────────────────────────────────────────────────────── -->
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let currentStep = 0;
const TOTAL = 6;

// Fix progress ring positioning
(function(){
  const wrap = document.querySelector('.progress-ring-wrap');
  if(wrap) wrap.style.cssText = 'position:relative;width:120px;height:120px;flex-shrink:0;';
  const pct = document.getElementById('progress-pct');
  if(pct) {
    pct.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;';
    document.querySelector('.progress-ring-wrap').appendChild(pct);
    document.getElementById('progress-wrap').insertBefore(
      document.querySelector('.progress-ring-wrap'),
      document.getElementById('progress-wrap').firstChild
    );
    document.getElementById('progress-wrap').firstChild.appendChild(document.querySelector('.progress-ring'));
  }
})();

// ── Navigation ─────────────────────────────────────────────────────────────────
function goStep(n) {
  document.querySelectorAll('.card-step').forEach(s => s.classList.remove('active'));
  document.getElementById('step-' + n).classList.add('active');

  // Update dots
  for (let i = 0; i < TOTAL; i++) {
    const dot = document.getElementById('sdot-' + i);
    const line = document.getElementById('sline-' + i);
    if (!dot) continue;
    dot.classList.remove('active', 'done');
    if (line) line.classList.remove('done');
    if (i < n)      { dot.classList.add('done'); dot.querySelector('.dot').textContent = '✓'; if(line) line.classList.add('done'); }
    else if (i === n) dot.classList.add('active');
    else {
      // restore number
      const nums = ['✓','2','3','4','5','🎉'];
      dot.querySelector('.dot').textContent = nums[i] ?? i+1;
    }
  }
  currentStep = n;
}

// ── DB Test ────────────────────────────────────────────────────────────────────
async function testDb() {
  const el = document.getElementById('db-test-result');
  el.style.display = 'block';
  el.className = 'alert alert-warn';
  el.textContent = '⏳ Test en cours...';

  const res = await fetch('run.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'test_db', ...getDbData() })
  });
  const d = await res.json();
  if (d.ok) {
    el.className = 'alert alert-ok';
    el.textContent = '✅ Connexion réussie ! La base "' + getDbData().db_name + '" est accessible.';
  } else {
    el.className = 'alert alert-err';
    el.textContent = '❌ ' + d.error;
  }
}

function getDbData() {
  return {
    db_host: document.getElementById('db_host').value.trim(),
    db_port: document.getElementById('db_port').value.trim() || '3306',
    db_name: document.getElementById('db_name').value.trim(),
    db_user: document.getElementById('db_user').value.trim(),
    db_pass: document.getElementById('db_pass').value,
  };
}

// ── Validation Step 3 ──────────────────────────────────────────────────────────
function validateStep3() {
  const url = document.getElementById('site_url').value.trim();
  if (!url || !url.startsWith('http')) {
    showToast('❌ L\'URL doit commencer par http:// ou https://'); return;
  }
  const cn = document.getElementById('company_name').value.trim();
  if (!cn) { showToast('❌ Donne un nom à ton entreprise !'); return; }
  goStep(4);
}

// ── Validation Step 4 ──────────────────────────────────────────────────────────
function validateStep4() {
  const u = document.getElementById('admin_user').value.trim();
  const p = document.getElementById('admin_pass').value;
  const p2 = document.getElementById('admin_pass2').value;
  const e = document.getElementById('admin_email').value.trim();

  if (!u) { showToast('❌ Choisis un nom d\'utilisateur'); return; }
  if (!e || !e.includes('@')) { showToast('❌ Email admin invalide'); return; }
  if (p.length < 8) { showToast('❌ Mot de passe trop court (min 8 caracètres)'); return; }
  if (p !== p2) { showToast('❌ Les mots de passe ne correspondent pas'); return; }

  runInstall();
}

// ── Install ────────────────────────────────────────────────────────────────────
const funMessages = [
  'Mélange de bits et de magie ✨',
  'Préparation du terrain ⛏️',
  'Codage quantique en cours 🔮',
  'Invocation des tables SQL 🧙',
  'Cafétéria des données ouverte ☕',
  'Injection de bonne humeur 💉',
  'Calibration du flux créatif 🌊',
  'Synchronisation avec la lune 🌙',
  'Compilation des rêves 🛠️',
  'Déploiement des super-pouvoirs 🦸',
  'Mise en place des fondations 🏗️',
  'Presque là, courage ! 🤞'
];

async function runInstall() {
  goStep(5);

  const demoChecked = document.querySelector('input[name="demo_choice"]:checked');
  const payload = {
    action: 'install',
    ...getDbData(),
    site_url:       document.getElementById('site_url').value.trim().replace(/\/$/, ''),
    company_name:   document.getElementById('company_name').value.trim(),
    company_region: document.getElementById('company_region').value.trim(),
    company_email:  document.getElementById('company_email').value.trim(),
    company_phone:  document.getElementById('company_phone').value.trim(),
    smtp_host:      document.getElementById('smtp_host').value.trim(),
    smtp_port:      document.getElementById('smtp_port').value.trim() || '587',
    smtp_user:      document.getElementById('smtp_user').value.trim(),
    smtp_pass:      document.getElementById('smtp_pass').value,
    admin_user:     document.getElementById('admin_user').value.trim(),
    admin_email:    document.getElementById('admin_email').value.trim(),
    admin_display:  document.getElementById('admin_display').value.trim(),
    admin_pass:     document.getElementById('admin_pass').value,
    demo_data:      demoChecked?.value === '1',
  };

  // Fake step-by-step progress while waiting
  let fakeProgress = 0;
  const circumference = 326.7;
  const ring = document.getElementById('progress-ring-fg');
  const pct  = document.getElementById('progress-pct');
  const msg  = document.getElementById('progress-msg');
  let msgIdx = 0;

  const fakeInterval = setInterval(() => {
    if (fakeProgress < 85) {
      fakeProgress += Math.random() * 8;
      fakeProgress = Math.min(fakeProgress, 85);
      setProgress(fakeProgress);
      msg.textContent = funMessages[msgIdx % funMessages.length];
      msgIdx++;
    }
  }, 600);

  function setProgress(p) {
    const offset = circumference - (p / 100) * circumference;
    ring.style.strokeDashoffset = offset;
    pct.textContent = Math.round(p) + '%';
  }

  let result;
  try {
    const res = await fetch('run.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    result = await res.json();
  } catch(e) {
    result = { ok: false, error: 'Erreur réseau: ' + e.message, log: [] };
  }

  clearInterval(fakeInterval);

  // Show log
  const logEl = document.getElementById('progress-log');
  logEl.innerHTML = '';
  (result.log || []).forEach(l => {
    const div = document.createElement('div');
    div.className = l.startsWith('❌') ? 'err-line' : 'ok-line';
    div.textContent = l;
    logEl.appendChild(div);
  });
  logEl.scrollTop = logEl.scrollHeight;

  if (result.ok) {
    setProgress(100);
    msg.textContent = result.partial ? '⚠️ Partiel — étapes manuelles requises' : '🎉 Terminé !';
    await sleep(700);
    document.getElementById('progress-wrap').style.display = 'none';
    document.getElementById('success-wrap').style.display = 'block';

    if (result.partial && result.manual_steps) {
      // Inject manual steps panel
      const manualEl = document.getElementById('manual-steps');
      manualEl.innerHTML = '';
      result.manual_steps.forEach((step, i) => {
        const div = document.createElement('div');
        div.style.cssText = 'background:#0a0a18;border:1px solid #f7be6a55;border-radius:10px;padding:16px;margin-bottom:14px;';
        let inner = `<p style="font-weight:700;color:var(--warn);margin-bottom:8px;">⚠️ ${step.title}</p>`;
        if (step.cmd) {
          inner += `<p style="font-size:12px;color:var(--muted);margin-bottom:6px;">Commande à lancer sur le serveur :</p>
          <div style="position:relative;background:#111;border-radius:8px;padding:10px 46px 10px 14px;margin-bottom:8px;">
            <code id="ms-cmd-${i}" style="font-family:monospace;font-size:12px;color:#6af7c8;white-space:pre-wrap;">${step.cmd}</code>
            <button onclick="navigator.clipboard.writeText(document.getElementById('ms-cmd-${i}').textContent);showToast('✅ Copié !')" style="position:absolute;top:8px;right:8px;background:#2a2a45;border:none;color:#aaa;padding:3px 9px;border-radius:5px;cursor:pointer;font-size:11px;">Copier</button>
          </div>`;
        }
        if ((step.type === 'config' || step.type === 'htaccess') && step.content) {
          const label = step.type === 'htaccess' ? '📄 Voir le contenu du .htaccess à copier' : '📄 Voir le contenu du config.php à coller';
          const btnLabel = step.type === 'htaccess' ? '.htaccess copié !' : 'Config copiée !';
          inner += `<details style="margin-top:8px;">
            <summary style="cursor:pointer;font-size:12px;color:var(--accent);margin-bottom:8px;">${label}</summary>
            <div style="position:relative;">
              <textarea id="ms-cfg-${i}" readonly style="width:100%;height:200px;background:#111;border:1px solid #2a2a45;border-radius:8px;padding:12px;font-family:monospace;font-size:11px;color:#a0e0c8;resize:vertical;">${step.content.replace(/</g,'&lt;')}</textarea>
              <button onclick="navigator.clipboard.writeText(document.getElementById('ms-cfg-${i}').value);showToast('✅ ${btnLabel}')" style="position:absolute;top:8px;right:8px;background:#2a2a45;border:none;color:#aaa;padding:3px 9px;border-radius:5px;cursor:pointer;font-size:11px;">Copier</button>
            </div>
          </details>`;
        }
        div.innerHTML = inner;
        manualEl.appendChild(div);
      });
      manualEl.style.display = 'block';
    } else {
      shootConfetti();
    }

    goStep(5);
    const dot5 = document.getElementById('sdot-5');
    if (dot5) { dot5.classList.add('done'); dot5.querySelector('.dot').textContent = '✓'; }
  } else {
    setProgress(0);
    msg.innerHTML = '❌ Erreur: ' + (result.error || 'Inconnue') + '<br><small style="color:var(--muted)">Vérifie le log ci-dessus.</small>';
  }
}

// ── Utils ──────────────────────────────────────────────────────────────────────
function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  if (inp.type === 'password') { inp.type = 'text'; btn.textContent = '🙈'; }
  else { inp.type = 'password'; btn.textContent = '👁'; }
}

function checkPwd(inp) {
  const v = inp.value;
  let s = 0;
  if (v.length >= 8) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  const bar = document.getElementById('pwd-bar');
  bar.setAttribute('data-s', s);
  const hints = ['', 'C\'est un peu court 🥺', 'Pas mal !', 'Bien ! 👍', 'Parfait ! 💪'];
  document.getElementById('pwd-hint').textContent = hints[s] || '';
}

let toastTimeout;
function showToast(msg) {
  let t = document.getElementById('toast');
  if (!t) { t = document.createElement('div'); t.id = 'toast'; t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1a1a2e;border:1px solid rgba(248,113,113,.4);color:var(--err);padding:12px 20px;border-radius:10px;font-size:.875rem;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.4);animation:fadeUp .2s ease;'; document.body.appendChild(t); }
  t.textContent = msg;
  t.style.display = 'block';
  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => { t.style.display='none'; }, 3500);
}

// ── Confetti ───────────────────────────────────────────────────────────────────
function shootConfetti() {
  const canvas = document.getElementById('confetti-canvas');
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;
  const ctx = canvas.getContext('2d');
  const pieces = [];
  const colors = ['#7c6af7','#f76ac8','#6af7c8','#fbbf24','#60a5fa','#f87171'];

  for (let i = 0; i < 160; i++) {
    pieces.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height - canvas.height,
      w: 6 + Math.random() * 10,
      h: 6 + Math.random() * 6,
      color: colors[Math.floor(Math.random() * colors.length)],
      r: Math.random() * Math.PI * 2,
      vx: (Math.random() - .5) * 4,
      vy: 1.5 + Math.random() * 3,
      vr: (Math.random() - .5) * .2,
      alpha: 1,
    });
  }

  let frame;
  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    let alive = 0;
    pieces.forEach(p => {
      p.x  += p.vx; p.y  += p.vy;
      p.r  += p.vr; p.vy += .04;
      if (p.y > canvas.height) { p.alpha -= .05; }
      if (p.alpha <= 0) return;
      alive++;
      ctx.save();
      ctx.globalAlpha = p.alpha;
      ctx.translate(p.x + p.w/2, p.y + p.h/2);
      ctx.rotate(p.r);
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
      ctx.restore();
    });
    if (alive > 0) frame = requestAnimationFrame(draw);
    else ctx.clearRect(0,0,canvas.width,canvas.height);
  }
  draw();
  setTimeout(() => { cancelAnimationFrame(frame); ctx.clearRect(0,0,canvas.width,canvas.height); }, 5000);
}
</script>

</body>
</html>
