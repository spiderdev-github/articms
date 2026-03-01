<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion Admin</title>
  <style>
    body{font-family:Arial,sans-serif;background:#111;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
    .card{background:#1c1c1c;padding:32px;border-radius:14px;width:340px;box-shadow:0 8px 32px rgba(0,0,0,.5)}
    h2{margin:0 0 20px;font-size:22px}
    .form-group{margin-bottom:14px}
    label{font-size:13px;color:#aaa;display:block;margin-bottom:4px}
    input[type=text],input[type=password]{width:100%;padding:10px 12px;background:#2a2a2a;border:1px solid #333;color:#fff;border-radius:8px;font-size:14px;box-sizing:border-box}
    input:focus{outline:none;border-color:#b11226}
    .btn-login{width:100%;padding:11px;background:#b11226;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:15px;cursor:pointer;margin-top:6px}
    .btn-login:hover{background:#d1213f}
    .error{color:#ff5f5f;font-size:13px;margin-bottom:12px;padding:8px 12px;background:rgba(255,95,95,.1);border-radius:6px}
    .forgot{text-align:center;margin-top:14px;font-size:12px}
    .forgot a{color:#666;text-decoration:none}
    .forgot a:hover{color:#b11226}
  </style>
</head>
<body>
  <div class="card">
    <h2>Admin Login</h2>
    <?php if ($error ?? null): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/login">
      <div class="form-group">
        <label>Identifiant</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
      </div>
      <button class="btn-login" type="submit">Connexion</button>
    </form>
    <div class="forgot">
      <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/forgot-password">J'ai perdu mon mot de passe</a>
    </div>
  </div>
</body>
</html>
