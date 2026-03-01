<?php

require_once __DIR__ . '/../auth.php';
requireAdmin();

require_once __DIR__ . '/../../includes/db.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../realisations.php");
  exit;
}


$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
  header("Location: ../realisations.php?notice=csrf");
  exit;
}


$id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$city = trim($_POST['city'] ?? '');
$type = trim($_POST['type'] ?? '');
$description = trim($_POST['description'] ?? '');
$sortOrder = (int)($_POST['sort_order'] ?? 0);
$isPublished = isset($_POST['is_published']) ? 1 : 0;
$isFeatured = isset($_POST['is_featured']) ? 1 : 0;
$removeCover = isset($_POST['remove_cover']) ? 1 : 0;

$allowedTypes = [
  '', 'Peinture interieure', 'Peinture exterieure', 'Crepi / Facade', 'Isolation', 'Mosaique effet pierre'
];
if (!in_array($type, $allowedTypes, true)) $type = '';

if ($title === '' || strlen($title) > 190) {
  $redir = $id > 0 ? "../realisation-edit.php?id=$id&notice=invalid" : "../realisation-create.php?notice=invalid";
  header("Location: $redir");
  exit;
}

function safeFileExt($name) {
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if ($ext === 'jpeg') $ext = 'jpg';
  return $ext;
}

function isAllowedExt($ext) {
  return in_array($ext, ['jpg','png','webp'], true);
}

function ensureDir($path) {
  if (!is_dir($path)) {
    mkdir($path, 0755, true);
  }
}

function saveUpload($file, $destDir) {
  if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;

  $ext = safeFileExt($file['name'] ?? '');
  if (!isAllowedExt($ext)) return null;

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);

  $allowedMime = ['image/jpeg','image/png','image/webp'];
  if (!in_array($mime, $allowedMime, true)) return null;

  ensureDir($destDir);

  $name = bin2hex(random_bytes(16)) . '.' . $ext;
  $abs = rtrim($destDir, '/') . '/' . $name;

  if (!move_uploaded_file($file['tmp_name'], $abs)) return null;

  return $name;
}

// Fetch current cover if edit
$currentCover = null;
if ($id > 0) {
  $stmt = $pdo->prepare("SELECT cover_image FROM realisations WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $id]);
  $currentCover = $stmt->fetchColumn();
  if ($currentCover === false) {
    header("Location: ../realisations.php?notice=invalid");
    exit;
  }
}

// Upload cover if provided
$projectRoot = realpath(__DIR__ . '/../../');
$uploadDirAbs = $projectRoot . '/assets/images/realisations';
$newCoverRel = null;

if (!empty($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
  $saved = saveUpload($_FILES['cover_image'], $uploadDirAbs);
  if ($saved) {
    $newCoverRel = 'assets/images/realisations/' . $saved;
  }
}

// Remove cover if requested (only if no new cover)
if ($removeCover && $newCoverRel === null && $id > 0) {
  $newCoverRel = '';
}

if ($id <= 0) {
  $stmt = $pdo->prepare("
    INSERT INTO realisations
      (title, city, type, description, cover_image, is_featured, is_published, sort_order, created_at, updated_at)
    VALUES
      (:title, :city, :type, :description, :cover, :featured, :published, :sort_order, NOW(), NOW())
  ");
  $stmt->execute([
    ':title' => $title,
    ':city' => ($city !== '' ? $city : null),
    ':type' => ($type !== '' ? $type : null),
    ':description' => ($description !== '' ? $description : null),
    ':cover' => ($newCoverRel !== null ? ($newCoverRel === '' ? null : $newCoverRel) : null),
    ':featured' => $isFeatured,
    ':published' => $isPublished,
    ':sort_order' => $sortOrder
  ]);

  $newId = (int)$pdo->lastInsertId();
  header("Location: ../realisation-edit.php?id=$newId&updated=1");
  exit;
}

// Update
$fields = "
  title = :title,
  city = :city,
  type = :type,
  description = :description,
  is_featured = :featured,
  is_published = :published,
  sort_order = :sort_order,
  updated_at = NOW()
";

$params = [
  ':title' => $title,
  ':city' => ($city !== '' ? $city : null),
  ':type' => ($type !== '' ? $type : null),
  ':description' => ($description !== '' ? $description : null),
  ':featured' => $isFeatured,
  ':published' => $isPublished,
  ':sort_order' => $sortOrder,
  ':id' => $id
];

if ($newCoverRel !== null) {
  $fields .= ", cover_image = :cover";
  $params[':cover'] = ($newCoverRel === '' ? null : $newCoverRel);
}

$stmt = $pdo->prepare("UPDATE realisations SET $fields WHERE id = :id");
$stmt->execute($params);

header("Location: ../realisation-edit.php?id=$id&updated=1");
exit;
*/