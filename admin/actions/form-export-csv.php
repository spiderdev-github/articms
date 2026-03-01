<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/db.php';

$pdo    = getPDO();
$formId = (int)($_GET['form_id'] ?? 0);
if (!$formId) die('form_id requis.');

$stmt = $pdo->prepare("SELECT * FROM forms WHERE id=?");
$stmt->execute([$formId]);
$formRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$formRow) die('Formulaire introuvable.');

$formFields = json_decode($formRow['fields'], true) ?: [];
$steps      = $formFields['steps'] ?? [];
$fieldDefs  = [];
foreach ($steps as $step) {
    foreach ($step['fields'] ?? [] as $f) {
        $fieldDefs[] = $f;
    }
}

$subs = $pdo->prepare("SELECT * FROM form_submissions WHERE form_id=? ORDER BY created_at DESC");
$subs->execute([$formId]);
$rows = $subs->fetchAll(PDO::FETCH_ASSOC);

// CSV output
$filename = 'form-' . $formRow['slug'] . '-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

// Headers
$headers = array_map(function($f){ return $f['label'] ?? $f['name']; }, $fieldDefs);
array_unshift($headers, 'ID', 'Date', 'IP');
fputcsv($out, $headers, ';');

foreach ($rows as $row) {
    $data = json_decode($row['data'], true) ?: [];
    $line = [$row['id'], date('d/m/Y H:i:s', strtotime($row['created_at'])), $row['ip']];
    foreach ($fieldDefs as $f) {
        $line[] = $data[$f['name']] ?? '';
    }
    fputcsv($out, $line, ';');
}

fclose($out);
exit;
