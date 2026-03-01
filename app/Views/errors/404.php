<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>404 — Page introuvable</title>
<style>body{font-family:Arial,sans-serif;background:#0f1116;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;flex-direction:column;gap:12px}h1{font-size:80px;margin:0;color:#b11226}p{font-size:18px;color:#aaa}</style>
</head>
<body>
  <h1>404</h1>
  <p>Page introuvable</p>
  <a href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>" style="color:#b11226;text-decoration:none;">Retour à l'accueil</a>
</body>
</html>
