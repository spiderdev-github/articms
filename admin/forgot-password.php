<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/config.php';

session_start();

$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare("SELECT id, display_name FROM admins WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Toujours afficher le même message (sécurité anti-enumeration)
        $msg = "Si cette adresse correspond à un compte actif, un lien de réinitialisation vient d'être envoyé.";

        if ($admin) {
        $token = bin2hex(random_bytes(32)); // 64 chars hex

            // L'expiration est calculée par MySQL (DATE_ADD) pour éviter tout décalage de timezone PHP/MySQL
            $pdo->prepare("UPDATE admins SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?")
                ->execute([$token, $admin['id']]);

            $resetUrl = BASE_URL . '/admin/reset-password.php?token=' . $token;
            $name     = htmlspecialchars($admin['display_name'] ?: 'Administrateur');

            $content  = '<h3>Réinitialisation de mot de passe</h3>';
            $content .= '<p>Bonjour <strong>' . $name . '</strong>,</p>';
            $content .= '<p>Vous avez demandé la réinitialisation de votre mot de passe pour l\'interface d\'administration <strong>Joker Peintre</strong>.</p>';
            $content .= '<p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe. Ce lien est valable <strong>1 heure</strong>.</p>';
            $content .= '<p style="text-align:center;margin:24px 0;"><a href="' . $resetUrl . '" class="btn" style="background:#b11226;color:#fff;padding:12px 28px;text-decoration:none;border-radius:6px;font-weight:bold;">Réinitialiser mon mot de passe</a></p>';
            $content .= '<p style="font-size:12px;color:#666;">Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email — votre mot de passe restera inchangé.<br>Lien direct : <a href="' . $resetUrl . '">' . $resetUrl . '</a></p>';

            try {
                require_once __DIR__ . '/../classes/MailSender.php';
                $mailer = new MailSender('Réinitialisation de votre mot de passe', $content, 'Joker Peintre Admin');
                $mailer->addDestinataire($email, $name);
                $mailer->send();
            } catch (Throwable $e) {
                // Email failed silently — token is still stored, admin can reset via DB if needed
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mot de passe oublié — Joker Peintre Admin</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; background: #111; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 16px; }
  .card { background: #1c1c1c; padding: 32px; border-radius: 12px; width: 100%; max-width: 360px; }
  h2 { margin: 0 0 6px; font-size: 1.3rem; }
  .subtitle { color: #888; font-size: .85rem; margin-bottom: 24px; }
  label { display: block; font-size: .85rem; color: #ccc; margin-bottom: 4px; }
  input[type=email] { width: 100%; padding: 10px 12px; background: #222; border: 1px solid #333; color: #fff; border-radius: 6px; font-size: .95rem; transition: border-color .2s; }
  input[type=email]:focus { outline: none; border-color: #b11226; }
  button[type=submit] { width: 100%; padding: 11px; background: #b11226; color: #fff; border: none; border-radius: 6px; font-weight: bold; font-size: .95rem; margin-top: 16px; cursor: pointer; transition: background .2s; }
  button[type=submit]:hover { background: #d4142c; }
  .msg-success { background: rgba(40,167,69,.15); border: 1px solid #28a745; color: #6fcf97; padding: 12px 14px; border-radius: 6px; font-size: .85rem; margin-bottom: 16px; }
  .msg-error   { background: rgba(220,53,69,.15); border: 1px solid #dc3545; color: #ff6b6b; padding: 12px 14px; border-radius: 6px; font-size: .85rem; margin-bottom: 16px; }
  .back { display: block; text-align: center; margin-top: 20px; color: #888; font-size: .85rem; text-decoration: none; }
  .back:hover { color: #fff; }
  .icon { text-align: center; margin-bottom: 18px; }
  .icon svg { width: 48px; height: 48px; }
</style>
</head>
<body>
<div class="card">
  <div class="icon">
    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="24" cy="24" r="23" stroke="#b11226" stroke-width="2"/>
      <path d="M24 14a5 5 0 0 1 5 5c0 3-3 4.5-3 7h-4c0-2.5 3-4 3-7a1 1 0 0 0-2 0" stroke="#b11226" stroke-width="2" stroke-linecap="round"/>
      <circle cx="24" cy="31" r="1.5" fill="#b11226"/>
      <path d="M17 28h2m12 0h-2" stroke="#b11226" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
  </div>

  <h2>Mot de passe oublié</h2>
  <p class="subtitle">Saisissez votre adresse email d'administrateur. Un lien de réinitialisation vous sera envoyé.</p>

  <?php if ($msg): ?>
  <div class="msg-success">✓ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="msg-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$msg): ?>
  <form method="POST" novalidate>
    <label for="email">Adresse email</label>
    <input type="email" id="email" name="email" placeholder="admin@exemple.fr" required
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <button type="submit">Envoyer le lien de réinitialisation</button>
  </form>
  <?php endif; ?>

  <a href="login.php" class="back">← Retour à la connexion</a>
</div>
</body>
</html>
