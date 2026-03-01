<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../forms.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { header('Location: ../forms.php'); exit; }

$pdo      = getPDO();
$sourceId = (int)($_POST['source_id'] ?? 0);
$newName  = trim($_POST['name'] ?? '');

if (!$sourceId || !$newName) { header('Location: ../forms.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$sourceId]);
$source = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$source) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Formulaire source introuvable.'];
    header('Location: ../forms.php'); exit;
}

// Generate unique slug
$baseSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $newName)));
$baseSlug = preg_replace('/-+/', '-', $baseSlug);
$slug     = $baseSlug;
$i        = 1;
while ($pdo->prepare("SELECT id FROM forms WHERE slug=?")->execute([$slug]) &&
       $pdo->query("SELECT id FROM forms WHERE slug='$slug'")->fetch()) {
    $slug = $baseSlug . '-' . (++$i);
}

try {
    $ins = $pdo->prepare("INSERT INTO forms (name, slug, description, fields, settings, is_active) VALUES (?,?,?,?,?,0)");
    $ins->execute([$newName, $slug, $source['description'], $source['fields'], $source['settings']]);
    $newId = (int)$pdo->lastInsertId();
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Formulaire dupliqué. Vous pouvez maintenant le modifier.'];
    header("Location: ../form-edit.php?id=$newId"); exit;
} catch (Exception $e) {
    error_log('[form-duplicate] '.$e->getMessage());
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Erreur lors de la duplication.'];
    header('Location: ../forms.php'); exit;
}
