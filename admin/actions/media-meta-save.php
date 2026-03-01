<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Méthode invalide']); exit;
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'error' => 'CSRF']); exit;
}

$baseDir = realpath(__DIR__ . '/../../assets/images');
$rel     = trim($_POST['rel'] ?? '', '/');
$alt     = trim($_POST['alt'] ?? '');

if (empty($rel)) {
    echo json_encode(['ok' => false, 'error' => 'rel manquant']); exit;
}

$realPath = realpath($baseDir . '/' . $rel);
if (!$realPath || !str_starts_with($realPath, $baseDir)) {
    echo json_encode(['ok' => false, 'error' => 'Chemin invalide']); exit;
}

try {
    $pdo = getPDO();
    if ($alt === '') {
        $pdo->prepare("DELETE FROM media_meta WHERE rel = :rel")
            ->execute([':rel' => $rel]);
    } else {
        $pdo->prepare("
            INSERT INTO media_meta (rel, alt_text)
            VALUES (:rel, :alt)
            ON DUPLICATE KEY UPDATE alt_text = :alt2, updated_at = NOW()
        ")->execute([':rel' => $rel, ':alt' => $alt, ':alt2' => $alt]);
    }
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
