<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/settings.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Méthode invalide']); exit;
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'error' => 'CSRF']); exit;
}

$baseDir = realpath(__DIR__ . '/../../assets/images');
$rel     = trim($_POST['rel']      ?? '', '/');
$dataUrl = trim($_POST['data_url'] ?? '');
$asCopy  = ($_POST['as_copy'] ?? '0') === '1';
$mime    = $_POST['mime'] ?? 'image/jpeg';

// ── Validations ──────────────────────────────────────────────────────────────
if (empty($rel) || empty($dataUrl)) {
    echo json_encode(['ok' => false, 'error' => 'Données manquantes']); exit;
}
// Autorisé uniquement dans medias/
if (!str_starts_with($rel, 'medias/') || str_contains($rel, '..')) {
    echo json_encode(['ok' => false, 'error' => 'Non autorisé (médias uniquement)']); exit;
}
$destPath = $baseDir . '/' . $rel;
$realDest = realpath(dirname($destPath)) . '/' . basename($destPath);
if (!str_starts_with(realpath(dirname($destPath)), $baseDir)) {
    echo json_encode(['ok' => false, 'error' => 'Chemin invalide']); exit;
}

// ── Décoder le data URL ───────────────────────────────────────────────────────
if (!preg_match('/^data:(image\/[a-z]+);base64,(.+)$/s', $dataUrl, $matches)) {
    echo json_encode(['ok' => false, 'error' => 'Format data URL invalide']); exit;
}
$imgData = base64_decode($matches[2]);
if ($imgData === false || strlen($imgData) < 100) {
    echo json_encode(['ok' => false, 'error' => 'Décodage base64 échoué']); exit;
}

// ── Déterminer l'extension de sortie ─────────────────────────────────────────
$mimeExtMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
$outputExt = $mimeExtMap[$mime] ?? pathinfo($rel, PATHINFO_EXTENSION);

// ── Calculer le chemin de destination ────────────────────────────────────────
if ($asCopy) {
    $base     = pathinfo($rel, PATHINFO_FILENAME);
    $dir      = dirname($baseDir . '/' . $rel);
    $copyName = $base . '_edit_' . substr(uniqid(), -5) . '.' . $outputExt;
    $savePath = $dir . '/' . $copyName;
    $filename = $copyName;
} else {
    // Remplace l'extension si format changé
    $origExt = pathinfo($rel, PATHINFO_EXTENSION);
    if ($outputExt !== $origExt) {
        $savePath = $baseDir . '/' . preg_replace('/\.' . preg_quote($origExt) . '$/', '.' . $outputExt, $rel);
        // Supprimer l'original si l'extension change
        @unlink($baseDir . '/' . $rel);
    } else {
        $savePath = $baseDir . '/' . $rel;
    }
    $filename = basename($savePath);
}

// ── Écriture ─────────────────────────────────────────────────────────────────
if (file_put_contents($savePath, $imgData) === false) {
    echo json_encode(['ok' => false, 'error' => 'Écriture impossible (permissions ?)']); exit;
}

// Permissions
@chmod($savePath, 0664);

echo json_encode(['ok' => true, 'filename' => $filename]);
