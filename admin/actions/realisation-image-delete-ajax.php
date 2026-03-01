<?php

require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$pdo = getPDO();

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input) || empty($input['image_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$imageId = (int)$input['image_id'];
if ($imageId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

// récupérer image + realisation
$stmt = $pdo->prepare("
    SELECT image_path, realisation_id
    FROM realisation_images
    WHERE id = ?
");
$stmt->execute([$imageId]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['success' => false]);
    exit;
}

// vérifier si c'est la cover
$stmt = $pdo->prepare("
    SELECT cover_image
    FROM realisations
    WHERE id = ?
");
$stmt->execute([$row['realisation_id']]);
$currentCover = $stmt->fetchColumn();

if ($currentCover === $row['image_path']) {
    echo json_encode([
        'success' => false,
        'error' => 'Cannot delete cover'
    ]);
    exit;
}

// supprimer fichier
$projectRoot = realpath(__DIR__ . '/../../');
$filePath = realpath($projectRoot . '/' . $row['image_path']);

if ($filePath && file_exists($filePath)) {
    unlink($filePath);
}

// supprimer en BDD
$pdo->prepare("
    DELETE FROM realisation_images
    WHERE id = ?
")->execute([$imageId]);

echo json_encode(['success' => true]);
exit;