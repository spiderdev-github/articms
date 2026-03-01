<?php
/**
 * forms/form-process.php — Dynamic form processor for forms managed in the admin.
 * Handles validation, reCAPTCHA, email sending, DB submission save.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../classes/MailSender.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// ── Helpers (rate limit / blocklist) ─────────────────────────────────────────
$storageDir = realpath(__DIR__ . '/../storage');
if ($storageDir === false) {
    redirectBack('error');
}

$rateFile  = $storageDir . '/rate-limit.json';
$blockFile = $storageDir . '/ip-blocklist.json';

function fp_readJson(string $path): array {
    if (!file_exists($path)) return [];
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : [];
}
function fp_writeJson(string $path, array $data): void {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function fp_blockIp(string $blockFile, string $ip, string $reason, int $secs = 86400): void {
    $b = fp_readJson($blockFile);
    $b[$ip] = ['until' => time() + $secs, 'reason' => $reason, 'updated' => time()];
    fp_writeJson($blockFile, $b);
}
function fp_isBlocked(string $blockFile, string $ip): bool {
    $b = fp_readJson($blockFile);
    if (!isset($b[$ip])) return false;
    if (($b[$ip]['until'] ?? 0) < time()) {
        unset($b[$ip]);
        fp_writeJson($blockFile, $b);
        return false;
    }
    return true;
}

// ── Load form from DB ─────────────────────────────────────────────────────────
$pdo     = getPDO();
$formId  = (int)($_POST['form_id'] ?? 0);
$formSlug = trim($_POST['form_slug'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ? AND is_active = 1");
$stmt->execute([$formId]);
$formRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formRow) {
    die('Formulaire introuvable ou inactif.');
}

$formFields   = json_decode($formRow['fields'],   true) ?: [];
$formSettings = json_decode($formRow['settings'], true) ?: [];
$steps        = $formFields['steps'] ?? [];

// Build flat list of all field definitions
$allFields = [];
foreach ($steps as $step) {
    foreach ($step['fields'] ?? [] as $f) {
        $allFields[] = $f;
    }
}

// Determine return URL
function fp_returnUrl(string $formSlug, string $param, string $value): string {
    $formSettings = $GLOBALS['formSettings'] ?? [];
    $redirectUrl  = trim($formSettings['redirect_url'] ?? '');
    if ($redirectUrl) {
        $base = (strpos($redirectUrl, 'http') === 0) ? $redirectUrl : BASE_URL . '/' . ltrim($redirectUrl, '/');
        $sep  = strpos($base, '?') !== false ? '&' : '?';
        return $base . $sep . $param . '=' . urlencode($value) . '&form=' . urlencode($formSlug);
    }
    $ref = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
    $ref = preg_replace('/[?&](success|notice|form)=[^&]*/','', $ref);
    $ref = rtrim($ref, '?&');
    $sep = strpos($ref, '?') !== false ? '&' : '?';
    return $ref . $sep . $param . '=' . urlencode($value) . '&form=' . urlencode($formSlug);
}

function redirectBack(string $notice): never {
    $slug = $GLOBALS['formSlug'] ?? '';
    header('Location: ' . fp_returnUrl($slug, 'notice', $notice));
    exit;
}
function redirectSuccess(): never {
    $slug = $GLOBALS['formSlug'] ?? '';
    header('Location: ' . fp_returnUrl($slug, 'success', '1'));
    exit;
}

// ── Blocklist check ───────────────────────────────────────────────────────────
if (fp_isBlocked($blockFile, $ip)) redirectBack('blocked');

// ── Rate limit (1 req / 60s / IP) ────────────────────────────────────────────
$rate = fp_readJson($rateFile);
$now  = time();
if (isset($rate[$ip]) && ($now - intval($rate[$ip]['last'] ?? 0)) < 60) {
    fp_blockIp($blockFile, $ip, 'rate_limit', 600);
    redirectBack('rate');
}
$rate[$ip] = ['last' => $now];
fp_writeJson($rateFile, $rate);

// ── Honeypot ──────────────────────────────────────────────────────────────────
if (trim($_POST['website'] ?? '') !== '') {
    fp_blockIp($blockFile, $ip, 'honeypot', 86400);
    redirectBack('blocked');
}
$hpTime = intval($_POST['form_time'] ?? 0);
if ($hpTime > 0 && ($now - $hpTime) < 3) {
    fp_blockIp($blockFile, $ip, 'too_fast', 3600);
    redirectBack('captcha');
}

// ── Validate required fields ──────────────────────────────────────────────────
$errors = [];
foreach ($allFields as $f) {
    if (!empty($f['required'])) {
        $val = trim($_POST[$f['name']] ?? '');
        if ($val === '') $errors[] = $f['name'];
    }
}
// Email validation
foreach ($allFields as $f) {
    if ($f['type'] === 'email' && !empty($_POST[$f['name']])) {
        if (!filter_var($_POST[$f['name']], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $f['name'];
        }
    }
}

// Step completed check (multi-step forms need final step)
$steps_count = count($steps);
if ($steps_count > 1) {
    $stepCompleted = (int)($_POST['step_completed'] ?? 0);
    if ($stepCompleted < $steps_count - 1) {
        redirectBack('missing');
    }
}

if (!empty($errors)) redirectBack('missing');

// ── reCAPTCHA v3 ─────────────────────────────────────────────────────────────
$useRecaptcha  = !empty($formSettings['use_recaptcha']);
$captchaSiteKey = gs('captcha_site_key', defined('CAPTCHA_SITE_KEY') ? CAPTCHA_SITE_KEY : '');

$captchaScore = null;
if ($useRecaptcha && $captchaSiteKey) {
    $token = $_POST['recaptcha_token'] ?? '';
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $ctx = stream_context_create(['http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query([
            'secret'   => gs('captcha_secret_key', defined('CAPTCHA_SECRET_KEY') ? CAPTCHA_SECRET_KEY : ''),
            'response' => $token,
            'remoteip' => $ip,
        ]),
        'timeout' => 5,
    ]]);
    $resp = @file_get_contents($verifyUrl, false, $ctx);
    $rd   = $resp ? json_decode($resp) : null;
    $captchaMinScore = (float) gs('captcha_min_score', defined('CAPTCHA_MIN_SCORE') ? CAPTCHA_MIN_SCORE : 0.5);
    $captchaScore    = $rd->score ?? null;

    $captchaOk = (
        $rd &&
        !empty($rd->success) &&
        isset($rd->score) &&
        $rd->score >= $captchaMinScore &&
        isset($rd->action) &&
        $rd->action === 'contact'
    );

    if (!$captchaOk) {
        $bl = fp_readJson($blockFile);
        $fc = intval($bl[$ip]['failCount'] ?? 0) + 1;
        $bl[$ip]['failCount'] = $fc;
        $bl[$ip]['updated']   = $now;
        fp_writeJson($blockFile, $bl);
        if ($fc >= 3) {
            fp_blockIp($blockFile, $ip, 'captcha_failed', 86400);
            redirectBack('blocked');
        }
        redirectBack('captcha');
    }
}

// ── Collect submitted data ────────────────────────────────────────────────────
$submissionData = [];
foreach ($allFields as $f) {
    $name  = $f['name'] ?? '';
    $type  = $f['type'] ?? 'text';
    $value = isset($_POST[$name]) ? trim($_POST[$name]) : '';
    if ($type === 'email') {
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
    } else {
        $value = htmlspecialchars($value);
    }
    $submissionData[$name] = $value;
}

// ── Save submission to DB ─────────────────────────────────────────────────────
if (!empty($formSettings['save_submission'])) {
    try {
        $st = $pdo->prepare("INSERT INTO form_submissions (form_id, data, ip, user_agent) VALUES (?, ?, ?, ?)");
        $st->execute([
            $formRow['id'],
            json_encode($submissionData, JSON_UNESCAPED_UNICODE),
            $ip,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    } catch (Exception $e) {
        error_log('[form-process] DB save error: ' . $e->getMessage());
    }
}

// ── If this is the contact form, also save to contacts table ──────────────────
if ($formSlug === 'contact') {
    try {
        $st = $pdo->prepare("
            INSERT INTO contacts (created_at, name, email, phone, city, service, surface, message, ip, captcha_score, user_agent)
            VALUES (NOW(), :name, :email, :phone, :city, :service, :surface, :message, :ip, :score, :ua)
        ");
        $st->execute([
            ':name'    => $submissionData['name']    ?? '',
            ':email'   => $submissionData['email']   ?? '',
            ':phone'   => $submissionData['phone']   ?? '',
            ':city'    => $submissionData['city']    ?? '',
            ':service' => $submissionData['service'] ?? '',
            ':surface' => $submissionData['surface'] ?? '',
            ':message' => $submissionData['message'] ?? '',
            ':ip'      => $ip,
            ':score'   => $captchaScore,
            ':ua'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    } catch (Exception $e) {
        error_log('[form-process] contacts insert error: ' . $e->getMessage());
    }
}

// ── Build & send email ────────────────────────────────────────────────────────
$emailTo      = $formSettings['email_to']      ?? gs('company_email', CONTACT_EMAIL ?? '');
$emailSubject = $formSettings['email_subject'] ?? ('Nouveau message - ' . $formRow['name']);

$body  = '<p>Nouveau message via le formulaire <strong>' . htmlspecialchars($formRow['name']) . '</strong></p>';
$body .= '<table cellspacing="0" cellpadding="6" style="border-collapse:collapse;">';
foreach ($allFields as $f) {
    $fieldName  = $f['name'] ?? '';
    $fieldLabel = htmlspecialchars($f['label'] ?? $fieldName);
    $fieldValue = htmlspecialchars($submissionData[$fieldName] ?? '');
    if ($fieldValue !== '') {
        $body .= '<tr><td style="padding:4px 12px 4px 0;color:#888;white-space:nowrap;">' . $fieldLabel . '</td>';
        $body .= '<td style="padding:4px 0;">' . nl2br($fieldValue) . '</td></tr>';
    }
}
$body .= '</table>';
$body .= '<hr><p style="color:#888;font-size:12px;">IP: ' . $ip;
if ($captchaScore !== null) $body .= ' | reCAPTCHA score: ' . $captchaScore;
$body .= '</p>';

try {
    $mailer = new MailSender($emailSubject, $body);
    $mailer->addDestinataire($emailTo);
    if (method_exists($mailer, 'setReplyTo') && isset($submissionData['email'])) {
        $mailer->setReplyTo($submissionData['email']);
    }
    $mailer->send();
} catch (Exception $e) {
    error_log('[form-process] Mail send error: ' . $e->getMessage());
    // Don't block the user just because mail failed — submission is still saved
}

redirectSuccess();
