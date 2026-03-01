<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

session_start();

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;

$pdo = getPDO();

// Validate token
$admin = null;
if ($token !== '') {
    $stmt = $pdo->prepare("SELECT id, display_name FROM admins WHERE reset_token = ? AND reset_token_expires > NOW() AND is_active = 1 LIMIT 1");
    $stmt->execute([$token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = trim($_POST['token'] ?? '');
    $pass1     = $_POST['password']  ?? '';
    $pass2     = $_POST['password2'] ?? '';

    // Re-validate token from POST
    $stmt2 = $pdo->prepare("SELECT id FROM admins WHERE reset_token = ? AND reset_token_expires > NOW() AND is_active = 1 LIMIT 1");
    $stmt2->execute([$postToken]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $error = "Le lien est invalide ou expiré. <a href='forgot-password.php' style='color:#ff6b6b'>Demander un nouveau lien</a>.";
    } elseif (strlen($pass1) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($pass1 !== $pass2) {
        $error = "Les deux mots de passe ne correspondent pas.";
    } else {
        $hash  = password_hash($pass1, PASSWORD_DEFAULT);
        $upd   = $pdo->prepare("UPDATE admins SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $upd->execute([$hash, $row['id']]);
        if ($upd->rowCount() > 0) {
            $done = true;
        } else {
            $error = "Erreur : la mise à jour a échoué (0 lignes modifiées). Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nouveau mot de passe — Joker Peintre Admin</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; background: #111; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 16px; }
  .card { background: #1c1c1c; padding: 32px; border-radius: 12px; width: 100%; max-width: 360px; }
  h2 { margin: 0 0 6px; font-size: 1.3rem; }
  .subtitle { color: #888; font-size: .85rem; margin-bottom: 24px; }
  label { display: block; font-size: .85rem; color: #ccc; margin-bottom: 4px; margin-top: 14px; }
  .input-wrap { position: relative; }
  input[type=password], input[type=text] { width: 100%; padding: 10px 40px 10px 12px; background: #222; border: 1px solid #333; color: #fff; border-radius: 6px; font-size: .95rem; transition: border-color .2s; }
  input:focus { outline: none; border-color: #b11226; }
  .toggle-eye { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; background: none; border: none; padding: 0; font-size: 1rem; }
  .toggle-eye:hover { color: #ccc; }
  button[type=submit] { width: 100%; padding: 11px; background: #b11226; color: #fff; border: none; border-radius: 6px; font-weight: bold; font-size: .95rem; margin-top: 20px; cursor: pointer; transition: background .2s; }
  button[type=submit]:hover { background: #d4142c; }
  .msg-success { background: rgba(40,167,69,.15); border: 1px solid #28a745; color: #6fcf97; padding: 12px 14px; border-radius: 6px; font-size: .85rem; margin-bottom: 16px; }
  .msg-error   { background: rgba(220,53,69,.15); border: 1px solid #dc3545; color: #ff6b6b; padding: 12px 14px; border-radius: 6px; font-size: .85rem; margin-bottom: 16px; }
  .invalid     { border-color: #dc3545 !important; }
  .strength    { height: 4px; border-radius: 2px; margin-top: 6px; background: #333; overflow: hidden; }
  .strength-bar { height: 100%; width: 0; border-radius: 2px; transition: width .3s, background .3s; }
  .strength-label { font-size: .75rem; color: #888; margin-top: 3px; }
  .back { display: block; text-align: center; margin-top: 20px; color: #888; font-size: .85rem; text-decoration: none; }
  .back:hover { color: #fff; }
  .expired-msg { text-align: center; color: #ff6b6b; margin-bottom: 16px; }
  .expired-msg a { color: #b11226; }
</style>
</head>
<body>
<div class="card">

<?php if ($done): ?>
  <h2>✓ Mot de passe modifié</h2>
  <p class="subtitle">Votre mot de passe a été mis à jour avec succès.</p>
  <div class="msg-success">Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</div>
  <a href="login.php" class="back" style="display:block;text-align:center;margin-top:12px;padding:11px;background:#b11226;color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;">Se connecter</a>

<?php elseif (!$admin && !$_POST): ?>
  <h2>Lien invalide</h2>
  <div class="expired-msg">
    <p>Ce lien de réinitialisation est <strong>invalide ou expiré</strong>.</p>
    <p>Les liens sont valables 1 heure.</p>
    <a href="forgot-password.php">→ Demander un nouveau lien</a>
  </div>
  <a href="login.php" class="back">← Retour à la connexion</a>

<?php else: ?>
  <h2>Nouveau mot de passe</h2>
  <p class="subtitle">
    <?php if ($admin): ?>
      Bonjour <strong><?= htmlspecialchars($admin['display_name'] ?: 'Admin') ?></strong>, choisissez un nouveau mot de passe.
    <?php else: ?>
      Définissez votre nouveau mot de passe.
    <?php endif; ?>
  </p>

  <?php if ($error): ?>
  <div class="msg-error">⚠ <?= $error ?></div>
  <?php endif; ?>

  <form method="POST" id="resetForm" novalidate>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?: ($_POST['token'] ?? '')) ?>">

    <label for="password">Nouveau mot de passe <span style="color:#888;font-size:.75rem;">(min. 8 caractères)</span></label>
    <div class="input-wrap">
      <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password">
      <button type="button" class="toggle-eye" data-target="password" title="Afficher/masquer">👁</button>
    </div>
    <div class="strength"><div class="strength-bar" id="strengthBar"></div></div>
    <div class="strength-label" id="strengthLabel"></div>

    <label for="password2">Confirmer le mot de passe</label>
    <div class="input-wrap">
      <input type="password" id="password2" name="password2" placeholder="••••••••" required autocomplete="new-password">
      <button type="button" class="toggle-eye" data-target="password2" title="Afficher/masquer">👁</button>
    </div>
    <div class="strength-label" id="matchLabel" style="margin-top:4px;"></div>

    <button type="submit" id="submitBtn">Enregistrer le nouveau mot de passe</button>
  </form>
  <a href="login.php" class="back">← Retour à la connexion</a>
<?php endif; ?>

</div>

<script>
// Toggle visibility
document.querySelectorAll('.toggle-eye').forEach(btn => {
  btn.addEventListener('click', function() {
    const inp = document.getElementById(this.dataset.target);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.textContent = inp.type === 'password' ? '👁' : '🙈';
  });
});

// Password strength
const passInput  = document.getElementById('password');
const pass2Input = document.getElementById('password2');
const bar        = document.getElementById('strengthBar');
const lbl        = document.getElementById('strengthLabel');
const matchLbl   = document.getElementById('matchLabel');

if (passInput) {
  passInput.addEventListener('input', function() {
    const v = this.value;
    let score = 0;
    if (v.length >= 8)  score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const levels = [
      {pct:'20%', bg:'#dc3545', txt:'Très faible'},
      {pct:'40%', bg:'#fd7e14', txt:'Faible'},
      {pct:'60%', bg:'#ffc107', txt:'Moyen'},
      {pct:'80%', bg:'#17a2b8', txt:'Bon'},
      {pct:'100%',bg:'#28a745', txt:'Excellent'},
    ];
    const l = levels[Math.max(0, score - 1)] || levels[0];
    bar.style.width  = v.length ? l.pct : '0';
    bar.style.background = l.bg;
    lbl.textContent  = v.length ? l.txt : '';
    lbl.style.color  = l.bg;
    checkMatch();
  });

  pass2Input.addEventListener('input', checkMatch);

  function checkMatch() {
    if (!pass2Input.value) { matchLbl.textContent = ''; pass2Input.classList.remove('invalid'); return; }
    if (passInput.value === pass2Input.value) {
      matchLbl.textContent = '✓ Les mots de passe correspondent';
      matchLbl.style.color = '#28a745';
      pass2Input.classList.remove('invalid');
    } else {
      matchLbl.textContent = '✗ Les mots de passe ne correspondent pas';
      matchLbl.style.color = '#dc3545';
      pass2Input.classList.add('invalid');
    }
  }
}
</script>
</body>
</html>
