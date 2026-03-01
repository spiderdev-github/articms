<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../forms.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header('Location: ../forms.php'); exit; }

$pdo = getPDO();

$id          = (int)($_POST['form_id'] ?? 0);
$name        = trim($_POST['form_name'] ?? '');
$slug        = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_POST['form_slug'] ?? '')));
$description = trim($_POST['form_description'] ?? '');
$isActive    = (int)($_POST['is_active'] ?? 1);

$fieldsRaw   = $_POST['fields_json']   ?? '{}';
$settingsRaw = $_POST['settings_json'] ?? '{}';

// Validate
if (!$name || !$slug) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Nom et slug requis.'];
    header('Location: ../form-edit.php' . ($id ? "?id=$id" : ''));
    exit;
}

// Validate JSON
$fieldsDecoded   = json_decode($fieldsRaw,   true);
$settingsDecoded = json_decode($settingsRaw, true);
if (!is_array($fieldsDecoded) || !is_array($settingsDecoded)) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Données invalides.'];
    header('Location: ../form-edit.php' . ($id ? "?id=$id" : ''));
    exit;
}

// Sanitize settings
$settings = [
    'email_to'        => filter_var($settingsDecoded['email_to'] ?? '', FILTER_SANITIZE_EMAIL),
    'email_subject'   => htmlspecialchars($settingsDecoded['email_subject'] ?? ''),
    'success_message' => htmlspecialchars($settingsDecoded['success_message'] ?? ''),
    'redirect_url'    => htmlspecialchars($settingsDecoded['redirect_url'] ?? ''),
    'submit_label'    => htmlspecialchars($settingsDecoded['submit_label'] ?? 'Envoyer'),
    'use_recaptcha'   => !empty($settingsDecoded['use_recaptcha']),
    'save_submission' => !empty($settingsDecoded['save_submission']),
];

try {
    if ($id) {
        // Check slug collision (exclude self)
        $chk = $pdo->prepare("SELECT id FROM forms WHERE slug = ? AND id != ?");
        $chk->execute([$slug, $id]);
        if ($chk->fetch()) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'Ce slug est déjà utilisé par un autre formulaire.'];
            header("Location: ../form-edit.php?id=$id"); exit;
        }
        $stmt = $pdo->prepare("UPDATE forms SET name=?, slug=?, description=?, fields=?, settings=?, is_active=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$name, $slug, $description, json_encode($fieldsDecoded, JSON_UNESCAPED_UNICODE), json_encode($settings, JSON_UNESCAPED_UNICODE), $isActive, $id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Formulaire mis à jour.'];
    } else {
        // Check slug collision
        $chk = $pdo->prepare("SELECT id FROM forms WHERE slug = ?");
        $chk->execute([$slug]);
        if ($chk->fetch()) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'Ce slug est déjà utilisé.'];
            header("Location: ../form-edit.php"); exit;
        }
        $stmt = $pdo->prepare("INSERT INTO forms (name, slug, description, fields, settings, is_active) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $slug, $description, json_encode($fieldsDecoded, JSON_UNESCAPED_UNICODE), json_encode($settings, JSON_UNESCAPED_UNICODE), $isActive]);
        $newId = (int)$pdo->lastInsertId();
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Formulaire créé avec succès.'];
        header("Location: ../form-edit.php?id=$newId"); exit;
    }
} catch (Exception $e) {
    error_log('[form-save] '.$e->getMessage());
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Erreur lors de la sauvegarde: '.$e->getMessage()];
    header('Location: ../form-edit.php' . ($id ? "?id=$id" : '')); exit;
}

header('Location: ../forms.php');
exit;
