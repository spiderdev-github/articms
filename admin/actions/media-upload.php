<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/settings.php';

/* Détecte les appels AJAX (fetch depuis media-picker) */
$isAjax = !empty($_POST['_ajax'])
       || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

function uploadRedirectOrJson(bool $isAjax, string $location, ?string $jsonError = null): void {
    if ($isAjax) {
        header('Content-Type: application/json');
        if ($jsonError !== null) {
            echo json_encode(['ok' => false, 'error' => $jsonError]);
        } else {
            echo json_encode(['ok' => true, 'uploaded' => 1]);
        }
        exit;
    }
    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    uploadRedirectOrJson($isAjax, '../media.php', 'Méthode non autorisée.');
}

// Détecte un dépassement de post_max_size (PHP vide $_POST et $_FILES silencieusement)
if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $maxPost = ini_get('post_max_size');
    uploadRedirectOrJson($isAjax,
        "../media.php?notice=err&msg=Fichiers+trop+lourds+(post_max_size+%3A+$maxPost)",
        "Fichiers trop lourds (post_max_size : $maxPost)"
    );
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    uploadRedirectOrJson($isAjax, '../media.php?notice=csrf', 'Token CSRF invalide.');
}

$allowedFolders = ['medias'];
$folder = $_POST['folder'] ?? 'medias';
if (!in_array($folder, $allowedFolders)) $folder = 'medias';

$destDir = realpath(__DIR__ . '/../../assets/images') . '/' . $folder;
if (!is_dir($destDir)) {
    mkdir($destDir, 0775, true);
    // Assure que Apache (www-data) peut écrire
    @chown($destDir, 'www-data');
    @chgrp($destDir, 'www-data');
}

$files   = $_FILES['images'] ?? [];
$errors  = [];

if (empty($files['name'][0])) {
    uploadRedirectOrJson($isAjax, '../media.php?notice=err&msg=Aucun+fichier+sélectionné', 'Aucun fichier sélectionné.');
}

$allowedExts  = ['jpg','jpeg','png','gif','webp','svg','avif'];
$maxSize      = 8 * 1024 * 1024; // 8 Mo

foreach ($files['name'] as $i => $originalName) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
        $errors[] = $originalName . ' (erreur upload)';
        continue;
    }
    if ($files['size'][$i] > $maxSize) {
        $errors[] = $originalName . ' (trop lourd, max 8 Mo)';
        continue;
    }
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        $errors[] = $originalName . ' (format non autorisé)';
        continue;
    }
    // Vérifie le type MIME réellement
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $files['tmp_name'][$i]);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml','image/avif','image/x-icon'];
    if (!in_array($mime, $allowedMimes)) {
        $errors[] = $originalName . ' (type MIME refusé)';
        continue;
    }

    // Nom sécurisé + unicité
    $safeName = preg_replace('/[^a-z0-9\-_]/', '-', strtolower(pathinfo($originalName, PATHINFO_FILENAME)));
    $safeName = trim(preg_replace('/-+/', '-', $safeName), '-');
    $destFile = $destDir . '/' . $safeName . '.' . $ext;
    if (file_exists($destFile)) {
        $destFile = $destDir . '/' . $safeName . '_' . substr(uniqid(), -5) . '.' . $ext;
    }

    if (!move_uploaded_file($files['tmp_name'][$i], $destFile)) {
        $errors[] = $originalName . ' (écriture impossible)';
    }
}

if (!empty($errors)) {
    $msg = urlencode(implode(', ', $errors));
    uploadRedirectOrJson($isAjax, "../media.php?notice=err&msg=$msg", implode(', ', $errors));
}

uploadRedirectOrJson($isAjax, '../media.php?updated=uploaded');
