<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requirePermission('contacts');

$pdo = getPDO();
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=contacts.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['Date','Nom','Email','Telephone','Ville','Service','Message','Status']);

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['created_at'],
        $row['name'],
        $row['email'],
        $row['phone'],
        $row['city'],
        $row['service'],
        $row['message'],
        $row['status'],
        $row['pipeline_status'],
        $row['followup_count'],
        $row['next_followup_at'],
        $row['archived_at']
    ]);
}

fclose($output);
exit;