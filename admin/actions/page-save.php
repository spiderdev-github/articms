<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../cms-pages.php"); exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    header("Location: ../cms-pages.php?notice=csrf"); exit;
}

$id           = (int)($_POST['id'] ?? 0);
$slug         = trim(preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $_POST['slug'] ?? ''))));
$title        = trim($_POST['title'] ?? '');
$h1           = trim($_POST['h1'] ?? '');
$kicker       = trim($_POST['kicker'] ?? '');
$content      = $_POST['content'] ?? '';   // HTML from WYSIWYG (trusted admin input)
$meta_title   = trim($_POST['meta_title'] ?? '');
$meta_desc    = trim($_POST['meta_desc'] ?? '');
$is_published = isset($_POST['is_published']) ? 1 : 0;
$sort_order   = (int)($_POST['sort_order'] ?? 0);
$template     = trim($_POST['template'] ?? 'default');
$parent_id    = (int)($_POST['parent_id'] ?? 0) ?: null;  // null si aucun parent

// Empêche une page d'être son propre parent
if ($id > 0 && $parent_id === $id) { $parent_id = null; }

if (empty($slug) || empty($title)) {
    header("Location: ../cms-pages.php?notice=missing"); exit;
}

$pdo = getPDO();

if ($id > 0) {
    // Mise à jour
    $stmt = $pdo->prepare("
        UPDATE cms_pages
        SET slug=:slug, title=:title, h1=:h1, kicker=:kicker,
            content=:content, meta_title=:mt, meta_description=:md,
            is_published=:ip, sort_order=:so, template=:tpl,
            parent_id=:pid, updated_at=NOW()
        WHERE id=:id
    ");
    $stmt->execute([
        ':slug' => $slug, ':title' => $title, ':h1' => $h1, ':kicker' => $kicker,
        ':content' => $content, ':mt' => $meta_title, ':md' => $meta_desc,
        ':ip' => $is_published, ':so' => $sort_order, ':tpl' => $template,
        ':pid' => $parent_id, ':id' => $id
    ]);
} else {
    // Création
    $stmt = $pdo->prepare("
        INSERT INTO cms_pages (slug, title, h1, kicker, content, meta_title, meta_description, is_published, sort_order, template, parent_id, created_at)
        VALUES (:slug, :title, :h1, :kicker, :content, :mt, :md, :ip, :so, :tpl, :pid, NOW())
    ");
    $stmt->execute([
        ':slug' => $slug, ':title' => $title, ':h1' => $h1, ':kicker' => $kicker,
        ':content' => $content, ':mt' => $meta_title, ':md' => $meta_desc,
        ':ip' => $is_published, ':so' => $sort_order, ':tpl' => $template,
        ':pid' => $parent_id
    ]);
}

$action = $_POST['submit_action'] ?? 'quit';

if ($action === 'stay') {
    $editId = ($id > 0) ? $id : $pdo->lastInsertId();
    header("Location: ../cms-pages.php?edit={$editId}&updated=1");
} else {
    // Quitter → retour à la liste
    header("Location: ../cms-pages.php?updated=1");
}
exit;
