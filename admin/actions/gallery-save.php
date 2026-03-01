<?php
require_once __DIR__ . '/../../admin/auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../galleries.php'); exit; }
if (!verifyCsrfToken($_POST['csrf'] ?? '')) { header('Location: ../galleries.php'); exit; }

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$sortOrder   = (int)($_POST['sort_order'] ?? 0);
$showLabels      = isset($_POST['show_item_labels'])    ? 1 : 0;
$showGalHeader   = isset($_POST['show_gallery_header']) ? 1 : 0;
$itemsPerPage    = max(1, (int)($_POST['items_per_page'] ?? 6));
$realIds     = array_map('intval', $_POST['realisation_ids'] ?? []);
$itemOrder   = array_filter(array_map('intval', explode(',', $_POST['item_order'] ?? '')));

if (!$name) {
    header('Location: ../gallery-edit.php' . ($id ? "?id=$id" : '') . '&error=name');
    exit;
}

// Build ordered list: keep only checked items, in drag-drop order
$orderedIds = [];
foreach ($itemOrder as $rid) {
    if (in_array($rid, $realIds, true)) {
        $orderedIds[] = $rid;
    }
}
// Append any checked not in itemOrder (safety)
foreach ($realIds as $rid) {
    if (!in_array($rid, $orderedIds, true)) {
        $orderedIds[] = $rid;
    }
}

if ($id) {
    // Update gallery meta
    $stmt = $pdo->prepare("UPDATE galleries SET name=?, description=?, sort_order=?, show_item_labels=?, show_gallery_header=?, items_per_page=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$name, $description, $sortOrder, $showLabels, $showGalHeader, $itemsPerPage, $id]);
} else {
    // Insert new gallery
    $stmt = $pdo->prepare("INSERT INTO galleries (name, description, sort_order, show_item_labels, show_gallery_header, items_per_page) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$name, $description, $sortOrder, $showLabels, $showGalHeader, $itemsPerPage]);
    $id = (int)$pdo->lastInsertId();
}

// Sync gallery_items: delete all then re-insert in order
$pdo->prepare("DELETE FROM gallery_items WHERE gallery_id = ?")->execute([$id]);

$ins = $pdo->prepare("INSERT INTO gallery_items (gallery_id, realisation_id, sort_order) VALUES (?,?,?)");
foreach ($orderedIds as $pos => $rid) {
    $ins->execute([$id, $rid, $pos]);
}

header("Location: ../gallery-edit.php?id=$id&updated=1");
exit;
