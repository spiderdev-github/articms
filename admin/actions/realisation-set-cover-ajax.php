<?php

require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$pdo = getPDO();

/* ================================
   1. LECTURE INPUT JSON
================================ */

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input) || empty($input['image_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$imageId = (int)$input['image_id'];
if ($imageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

/* ================================
   2. RECUPERATION IMAGE
================================ */

$stmt = $pdo->prepare("
    SELECT image_path, realisation_id
    FROM realisation_images
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $imageId]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Image not found']);
    exit;
}

$imagePathRel = $row['image_path'];
$realisationId = (int)$row['realisation_id'];

/* ================================
   3. UPDATE COVER EN BDD
================================ */

$pdo->prepare("
    UPDATE realisations
    SET cover_image = :img,
        updated_at = NOW()
    WHERE id = :rid
")->execute([
    ':img' => $imagePathRel,
    ':rid' => $realisationId
]);

/* ================================
   4. GENERATION THUMBNAIL
================================ */

$projectRoot = realpath(__DIR__ . '/../../');
$sourcePath = realpath($projectRoot . '/' . $imagePathRel);

if ($sourcePath && file_exists($sourcePath)) {

    $thumbDir = $projectRoot . '/assets/images/realisations';
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }

    $thumbName = 'thumb_' . pathinfo($imagePathRel, PATHINFO_FILENAME) . '.webp';
    $thumbPathAbs = $thumbDir . '/' . $thumbName;
    $thumbPathRel = 'assets/images/realisations/' . $thumbName;

    $imageInfo = getimagesize($sourcePath);

    if ($imageInfo) {

        switch ($imageInfo['mime']) {
            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                break;
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                break;
            default:
                $image = null;
        }

        if ($image) {

            // largeur fixe 600px, hauteur auto proportionnelle
            $width = imagesx($image);
            $height = imagesy($image);

            $newWidth = 600;
            $ratio = $newWidth / $width;
            $newHeight = (int)($height * $ratio);

            $thumb = imagescale($image, $newWidth, $newHeight);

            // compression 75 = bon compromis qualité/performance
            imagewebp($thumb, $thumbPathAbs, 75);

            imagedestroy($image);
            imagedestroy($thumb);

            // update champ cover_thumb
            $pdo->prepare("
                UPDATE realisations
                SET cover_thumb = :thumb
                WHERE id = :rid
            ")->execute([
                ':thumb' => $thumbPathRel,
                ':rid' => $realisationId
            ]);
        }
    }
}

/* ================================
   5. REPONSE JSON
================================ */

echo json_encode([
    'success' => true,
    'realisation_id' => $realisationId
]);
exit;