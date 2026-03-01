<?php
// echo password_hash('password', PASSWORD_DEFAULT);
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = loginAdmin($username, $password);
    if ($result === true) {
        header("Location: dashboard.php");
        exit;
    }
    if ($result === '2fa') {
        header("Location: 2fa-verify.php");
        exit;
    }

    $error = "Identifiants invalides.";
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin - Joker Peintre</title>
<style>
body{font-family:Arial;background:#111;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh}
.card{background:#1c1c1c;padding:30px;border-radius:12px;width:320px}
input{width:100%;padding:10px;margin:8px 0;background:#222;border:1px solid #333;color:#fff;max-width: 297px;}
button{width:100%;padding:10px;background:#b11226;color:#fff;border:none;font-weight:bold}
.error{color:#ff5f5f}

</style>
</head>
<body>

<div class="card">
<h2>Admin Login</h2>

<?php if(isset($error)): ?>
<p class="error"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
<input type="text" name="username" placeholder="Identifiant" required>
<input type="password" name="password" placeholder="Mot de passe" required>
<button type="submit">Connexion</button>
</form>
<p style="text-align:center;margin:14px 0 0;font-size:.82rem;"><a href="forgot-password.php" style="color:#888;text-decoration:none;" onmouseover="this.style.color='#b11226'" onmouseout="this.style.color='#888'">J'ai perdu mon mot de passe</a></p>

</div>

</body>
</html>