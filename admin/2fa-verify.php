<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/TOTP.php';

session_start();

// Must have a pending 2FA login
if (empty($_SESSION['2fa_pending']['id'])) {
    header('Location: login.php'); exit;
}

$error    = '';
$adminId  = (int) $_SESSION['2fa_pending']['id'];

// ── Helpers ───────────────────────────────────────────────────────────────

function finalizeLogin(int $adminId, PDO $pdo): void {
    unset($_SESSION['2fa_pending']);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    unset($_SESSION['admin_data']);
    $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$adminId]);
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function registerTrustedDevice(int $adminId, PDO $pdo): void {
    $token   = bin2hex(random_bytes(32));
    $hash    = hash('sha256', $token);
    $label   = mb_strimwidth($_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu', 0, 200);
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    $pdo->prepare("INSERT INTO admin_trusted_devices (admin_id, token_hash, device_label, expires_at) VALUES (?,?,?,?)")
        ->execute([$adminId, $hash, $label, $expires]);
    $cp = defined('BASE_URL') ? rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/') . '/admin/' : '/';
    setcookie('jp_trusted_device', $adminId . ':' . $token, [
        'expires'  => strtotime('+30 days'),
        'path'     => $cp,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function verifyRecoveryCode(int $adminId, string $code, PDO $pdo): bool {
    $normalized = strtoupper(str_replace(['-', ' '], '', trim($code)));
    if (strlen($normalized) !== 12) return false;
    $hash = hash('sha256', $normalized);
    $stmt = $pdo->prepare("SELECT id FROM admin_recovery_codes WHERE admin_id = ? AND code_hash = ? AND used_at IS NULL LIMIT 1");
    $stmt->execute([$adminId, $hash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    $pdo->prepare("UPDATE admin_recovery_codes SET used_at = NOW() WHERE id = ?")
        ->execute([$row['id']]);
    return true;
}

// ── POST handling ─────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo          = getPDO();
    $rememberMe   = !empty($_POST['remember_device']);
    $useRecovery  = !empty($_POST['use_recovery']);

    $stmt = $pdo->prepare("SELECT id, totp_secret FROM admins WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        unset($_SESSION['2fa_pending']);
        header('Location: login.php'); exit;
    }

    $ok = false;

    if ($useRecovery) {
        $recoveryCode = trim($_POST['recovery_code'] ?? '');
        if (!$recoveryCode) {
            $error = "Veuillez saisir un code de récupération.";
        } elseif (!verifyRecoveryCode($adminId, $recoveryCode, $pdo)) {
            $error = "Code de récupération invalide ou déjà utilisé.";
        } else {
            $ok = true;
        }
    } else {
        $code = trim($_POST['totp_code'] ?? '');
        if (!TOTP::verify($admin['totp_secret'], $code)) {
            $error = "Code incorrect. Réessayez avec le prochain code.";
        } else {
            $ok = true;
        }
    }

    if ($ok) {
        finalizeLogin($adminId, $pdo);
        if ($rememberMe) {
            registerTrustedDevice($adminId, $pdo);
        }
        header('Location: dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vérification 2FA — Joker Peintre Admin</title>
<style>
*, *::before, *::after { box-sizing: border-box; }
body { font-family: Arial, sans-serif; background: #111; color: #fff;
       display: flex; justify-content: center; align-items: center;
       min-height: 100vh; margin: 0; padding: 16px; }
.card { background: #1c1c1c; padding: 32px; border-radius: 12px; width: 100%; max-width: 360px; text-align: center; }
.icon { font-size: 2.4rem; margin-bottom: 10px; }
h2 { margin: 0 0 6px; font-size: 1.2rem; }
.subtitle { color: #888; font-size: .83rem; margin-bottom: 20px; line-height: 1.5; }
.tab-btns { display: flex; gap: 6px; margin-bottom: 20px; }
.tab-btn { flex: 1; padding: 8px; background: #222; border: 1px solid #333; color: #888;
           border-radius: 6px; cursor: pointer; font-size: .82rem; transition: all .2s; }
.tab-btn.active { background: #b11226; border-color: #b11226; color: #fff; }
.pane { display: none; text-align: left; }
.pane.active { display: block; }

/* TOTP code input */
.code-input { width: 100%; padding: 14px 12px; background: #222; border: 2px solid #333;
              color: #fff; border-radius: 8px; font-size: 2rem; font-weight: bold;
              text-align: center; letter-spacing: 10px; transition: border-color .2s; }
.code-input:focus { outline: none; border-color: #b11226; }

/* Recovery code input */
.recovery-input { width: 100%; padding: 10px 12px; background: #222; border: 2px solid #333;
                  color: #fff; border-radius: 8px; font-size: 1rem; letter-spacing: 3px;
                  text-align: center; transition: border-color .2s; text-transform: uppercase; }
.recovery-input:focus { outline: none; border-color: #ffc107; }
.recovery-hint { color: #666; font-size: .78rem; margin-top: 6px; text-align: center; }

/* Remember device */
.remember-row { display: flex; align-items: center; gap: 8px; margin-top: 14px;
                font-size: .82rem; color: #888; cursor: pointer; }
.remember-row input[type=checkbox] { width: 16px; height: 16px; accent-color: #b11226; cursor: pointer; }
.remember-row:hover { color: #ccc; }

button[type=submit] { width: 100%; padding: 12px; background: #b11226; color: #fff; border: none;
                      border-radius: 6px; font-weight: bold; font-size: .95rem; margin-top: 16px;
                      cursor: pointer; transition: background .2s; }
button[type=submit]:hover { background: #d4142c; }
.recovery-submit { background: #856404 !important; }
.recovery-submit:hover { background: #a07800 !important; }

.msg-error { background: rgba(220,53,69,.15); border: 1px solid #dc3545; color: #ff6b6b;
             padding: 10px 14px; border-radius: 6px; font-size: .85rem; margin-bottom: 16px;
             text-align: left; }
.timer { font-size: .75rem; color: #666; margin-top: 8px; text-align: center; }
.timer span { color: #ffc107; font-weight: bold; }
.back { display: block; margin-top: 18px; color: #555; font-size: .82rem; text-decoration: none; }
.back:hover { color: #ccc; }
</style>
</head>
<body>
<div class="card">
  <div class="icon">🛡️</div>
  <h2>Double authentification</h2>
  <p class="subtitle">Confirmez votre identité pour accéder à l'administration.</p>

  <?php if ($error): ?>
  <div class="msg-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="tab-btns">
    <button type="button" class="tab-btn active" onclick="showTab('totp',this)">
      📱 Application
    </button>
    <button type="button" class="tab-btn" onclick="showTab('recovery',this)">
      🔑 Code de récupération
    </button>
  </div>

  <!-- TOTP pane -->
  <div class="pane active" id="pane-totp">
    <p style="color:#888;font-size:.82rem;margin-bottom:12px;">
      Ouvrez <em>Google Authenticator</em>, <em>Authy</em> ou votre application TOTP.
    </p>
    <form method="POST" id="fTotp">
      <input type="hidden" name="use_recovery" value="0">
      <input type="text" name="totp_code" class="code-input" id="codeInput"
             maxlength="6" pattern="\d{6}" placeholder="000000"
             autocomplete="one-time-code" inputmode="numeric" autofocus>
      <p class="timer">Code valide encore <span id="countdown">--</span>s</p>
      <label class="remember-row">
        <input type="checkbox" name="remember_device" value="1">
        Se souvenir de cet appareil 30 jours
      </label>
      <button type="submit">Vérifier</button>
    </form>
  </div>

  <!-- Recovery pane -->
  <div class="pane" id="pane-recovery">
    <p style="color:#888;font-size:.82rem;margin-bottom:12px;">
      Utilisez l'un de vos codes de récupération à usage unique (format&nbsp;<code style="color:#ffc107">XXXX-XXXX-XXXX</code>).
    </p>
    <form method="POST" id="fRecovery">
      <input type="hidden" name="use_recovery" value="1">
      <input type="text" name="recovery_code" class="recovery-input"
             maxlength="14" placeholder="XXXX-XXXX-XXXX"
             autocomplete="off" spellcheck="false" id="recoveryInput">
      <p class="recovery-hint">Ce code sera invalidé après utilisation.</p>
      <label class="remember-row">
        <input type="checkbox" name="remember_device" value="1">
        Se souvenir de cet appareil 30 jours
      </label>
      <button type="submit" class="recovery-submit">Utiliser ce code</button>
    </form>
  </div>

  <a href="login.php" class="back">← Retour à la connexion</a>
</div>

<script>
// Tab switching
function showTab(name, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('pane-' + name).classList.add('active');
  // Focus
  const inp = document.querySelector('#pane-' + name + ' input:not([type=hidden]):not([type=checkbox])');
  if (inp) setTimeout(() => inp.focus(), 50);
}

// Auto-submit TOTP when 6 digits
document.getElementById('codeInput').addEventListener('input', function() {
  this.value = this.value.replace(/\D/g,'').slice(0,6);
  if (this.value.length === 6) document.getElementById('fTotp').submit();
});

// Auto-format recovery code XXXX-XXXX-XXXX
document.getElementById('recoveryInput').addEventListener('input', function() {
  let v = this.value.toUpperCase().replace(/[^A-F0-9]/g,'');
  if (v.length > 4)  v = v.slice(0,4) + '-' + v.slice(4);
  if (v.length > 9)  v = v.slice(0,9) + '-' + v.slice(9);
  this.value = v.slice(0, 14);
});

// Countdown timer
function updateCountdown() {
  const rem = 30 - (Math.floor(Date.now() / 1000) % 30);
  const el = document.getElementById('countdown');
  if (el) el.textContent = rem;
}
updateCountdown();
setInterval(updateCountdown, 1000);
</script>
</body>
</html>

