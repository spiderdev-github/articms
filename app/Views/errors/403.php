<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>403 — Accès refusé</title>
<style>body{font-family:Arial,sans-serif;background:#0f1116;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;flex-direction:column;gap:12px}h1{font-size:80px;margin:0;color:#e67e22}p{font-size:18px;color:#aaa}</style>
</head>
<body>
  <h1>403</h1>
  <p><?= htmlspecialchars($message ?? "Vous n'avez pas l'autorisation d'accéder à cette page.") ?></p>
  <a href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>/admin/dashboard" style="color:#e67e22;text-decoration:none;">Retour au dashboard</a>
</body>
</html>
