<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../classes/MailSender.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Acces refuse.");
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Storage paths
$storageDir = realpath(__DIR__ . '/../storage');
if ($storageDir === false) {
    header("Location: " . BASE_URL . "/contact?notice=error");
    exit;
}

$rateFile = $storageDir . '/rate-limit.json';
$blockFile = $storageDir . '/ip-blocklist.json';

// Helpers
function readJsonFile($path) {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function writeJsonFile($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($path, $json, LOCK_EX);
}

function blockIp($blockFile, $ip, $reason, $seconds = 86400) {
    $blocked = readJsonFile($blockFile);
    $now = time();
    $blocked[$ip] = [
        'until' => $now + $seconds,
        'reason' => $reason,
        'updated' => $now
    ];
    writeJsonFile($blockFile, $blocked);
}

function isIpBlocked($blockFile, $ip) {
    $blocked = readJsonFile($blockFile);
    $now = time();
    if (!isset($blocked[$ip])) return false;

    if (!isset($blocked[$ip]['until']) || $blocked[$ip]['until'] < $now) {
        unset($blocked[$ip]);
        writeJsonFile($blockFile, $blocked);
        return false;
    }
    return true;
}

// 0) Blocklist check
if (isIpBlocked($blockFile, $ip)) {
    header("Location: " . BASE_URL . "/contact?notice=blocked");
    exit;
}

// ===============================
// 1) Rate limit: 1 request / 60s / IP
// ===============================
$rate = readJsonFile($rateFile);
$now = time();

if (isset($rate[$ip]) && ($now - intval($rate[$ip]['last'] ?? 0)) < 60) {
    // Flood detected: block for 10 minutes
    blockIp($blockFile, $ip, 'rate_limit', 600);
    header("Location: " . BASE_URL . "/contact?notice=rate");
    exit;
}

// Update last attempt time now (early)
$rate[$ip] = ['last' => $now];
writeJsonFile($rateFile, $rate);

// ===============================
// 2) Honeypot
// ===============================
$hp = trim($_POST['website'] ?? '');
$hpTime = intval($_POST['form_time'] ?? 0);

// If honeypot filled => bot
if ($hp !== '') {
    blockIp($blockFile, $ip, 'honeypot', 86400);
    header("Location: " . BASE_URL . "/contact?notice=blocked");
    exit;
}

// Too fast submit (less than 3 seconds) => suspicious
if ($hpTime > 0 && ($now - $hpTime) < 3) {
    blockIp($blockFile, $ip, 'too_fast', 3600);
    header("Location: " . BASE_URL . "/contact?notice=captcha");
    exit;
}

// ===============================
// 3) Sanitize & Validate
// ===============================
$name    = htmlspecialchars(trim($_POST['name'] ?? ''));
$email   = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone   = htmlspecialchars(trim($_POST['phone'] ?? ''));
$city    = htmlspecialchars(trim($_POST['city'] ?? ''));
$message = htmlspecialchars(trim($_POST['message'] ?? ''));
$service = htmlspecialchars(trim($_POST['service'] ?? ''));
$surface = htmlspecialchars(trim($_POST['surface'] ?? ''));
$token   = $_POST['recaptcha_token'] ?? '';
$step    = $_POST['step_completed'] ?? '0';

if (!$name || !$email || !$city || !$message || $step !== "1") {
    header("Location: " . BASE_URL . "/contact?notice=missing");
    exit;
}

// ===============================
// 4) Verify reCAPTCHA v3
// ===============================
$verifyUrl = "https://www.google.com/recaptcha/api/siteverify";
$data = [
    'secret' => gs('captcha_secret_key', CAPTCHA_SECRET_KEY),
    'response' => $token,
    'remoteip' => $ip
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
        'timeout' => 5
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($verifyUrl, false, $context);
$responseData = json_decode($response);

$captchaMinScore = (float) gs('captcha_min_score', CAPTCHA_MIN_SCORE);

// Si aucune clé configurée, on bypass reCAPTCHA
$captchaSiteKey = gs('captcha_site_key', CAPTCHA_SITE_KEY);

$captchaOk = (
    empty($captchaSiteKey) || // bypass si clé vide
    (
        $responseData &&
        !empty($responseData->success) &&
        isset($responseData->score) &&
        $responseData->score >= $captchaMinScore &&
        isset($responseData->action) &&
        $responseData->action === "contact"
    )
);

if (!$captchaOk) {
    // Count failures and block quickly if repeated
    $blocked = readJsonFile($blockFile);
    $failCount = intval($blocked[$ip]['failCount'] ?? 0) + 1;

    // Save failCount inside blocklist file even if not blocked yet
    $blocked[$ip]['failCount'] = $failCount;
    $blocked[$ip]['updated'] = $now;
    writeJsonFile($blockFile, $blocked);

    // If repeated captcha failures => block 24h
    if ($failCount >= 3) {
        blockIp($blockFile, $ip, 'captcha_failed', 86400);
        header("Location: " . BASE_URL . "/contact?notice=blocked");
        exit;
    }

    header("Location: " . BASE_URL . "/contact?notice=captcha");
    exit;
}


// ===============================
// 5) Prepare Email
// ===============================
$subject = "Nouvelle demande - Joker Peintre";

$body =  "<p>Nouvelle demande via le site Joker Peintre</p>";
$body .= "<p>Nom : $name</p>";
$body .= "<p>Email : $email</p>";
$body .= "<p>Telephone : $phone</p>";
$body .= "<p>Ville : $city</p>";
$body .= "<p>Type de travaux : $service</p>";
$body .= "<p>Surface approx. : $surface</p>";
$body .= "<p>Message :<br>$message</p>";
$body .= "<p>---------------------------</p>";
$body .= "<p>IP : $ip</p>";
$body .= "<p>Score reCAPTCHA : " . $responseData->score . "</p>";

// ===============================
// 5B) Log Contact
// ===============================

// ===============================
// Save to database
// ===============================

try {

    $pdo = getPDO();

    $stmt = $pdo->prepare("
        INSERT INTO contacts 
        (created_at, name, email, phone, city, service, surface, message, ip, captcha_score, user_agent)
        VALUES
        (NOW(), :name, :email, :phone, :city, :service, :surface, :message, :ip, :score, :ua)
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':city' => $city,
        ':service' => $service,
        ':surface' => $surface,
        ':message' => $message,
        ':ip' => $ip,
        ':score' => $responseData->score,
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    $contactId = (int) $pdo->lastInsertId();

} catch (Exception $e) {

    // Optionnel: log erreur en fichier
    error_log($e->getMessage());
    header("Location: " . BASE_URL . "/contact?notice=error");
    exit;
}

// ===============================
// 6) Send via MailSender
// ===============================

// Ajouter le bouton lien vers la demande dans l'admin
if (!empty($contactId)) {
    $contactUrl = BASE_URL . '/admin/contact-view.php?id=' . $contactId;
    $body .= '
<div style="text-align:center;margin:28px 0 8px;">
  <a href="' . $contactUrl . '"
     style="display:inline-block;background:#b11226;color:#ffffff;text-decoration:none;
            padding:13px 32px;border-radius:6px;font-weight:bold;font-size:15px;
            font-family:Arial,sans-serif;letter-spacing:.3px;">
    &#128065; Voir la demande dans l&rsquo;administration
  </a>
</div>
<p style="text-align:center;font-size:11px;color:#888;margin-top:4px;">
  Lien direct : <a href="' . $contactUrl . '" style="color:#b11226;">' . $contactUrl . '</a>
</p>';
}

$mailSender = new MailSender($subject, $body);
$mailSender->addDestinataire(CONTACT_EMAIL);

if (method_exists($mailSender, 'setReplyTo')) {
    $mailSender->setReplyTo($email);
}

if ($mailSender->send()) {
    header("Location: " . BASE_URL . "/contact?success=1");
    exit;
}

header("Location: " . BASE_URL . "/contact?notice=error");
exit;