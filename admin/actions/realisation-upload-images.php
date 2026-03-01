<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

$pdo = getPDO();

function convertToWebP($source, $destination){
  $info = getimagesize($source);
  if(!$info) return false;

  switch($info['mime']){
    case 'image/jpeg': $image = imagecreatefromjpeg($source); break;
    case 'image/png': $image = imagecreatefrompng($source); break;
    default: return false;
  }

  imagewebp($image, $destination, 80);
  imagedestroy($image);
  return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) exit;

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) exit;

$projectRoot = realpath(__DIR__ . '/../../');
$uploadDirAbs = $projectRoot . '/assets/images/realisations';

if (!is_dir($uploadDirAbs)) mkdir($uploadDirAbs, 0755, true);

foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {

  if (!is_uploaded_file($tmp)) continue;

  $ext = strtolower(pathinfo($_FILES['images']['name'][$k], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;

  $newName = bin2hex(random_bytes(16)) . '.webp';
  $absPath = $uploadDirAbs . '/' . $newName;

  if (convertToWebP($tmp, $absPath)) {
    $pdo->prepare("
      INSERT INTO realisation_images
        (realisation_id, image_path, created_at)
      VALUES (?, ?, NOW())
    ")->execute([$id, 'assets/images/realisations/'.$newName]);
  }
}

header("Location: ../realisation-edit.php?id=$id");
exit;