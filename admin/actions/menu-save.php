<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../includes/settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../menu.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ../menu.php?notice=csrf');
    exit;
}

$navJson = trim($_POST['nav_json'] ?? '');
$items   = [];

if ($navJson !== '') {
    $decoded = json_decode($navJson, true);
    if (is_array($decoded)) {
        foreach ($decoded as $item) {
            if (empty($item['label']) || empty($item['url'])) continue;
            $row = [
                'label'    => htmlspecialchars_decode(strip_tags($item['label'])),
                'url'      => htmlspecialchars_decode($item['url']),
                'children' => [],
            ];
            foreach (($item['children'] ?? []) as $child) {
                if (empty($child['label']) || empty($child['url'])) continue;
                $row['children'][] = [
                    'label' => htmlspecialchars_decode(strip_tags($child['label'])),
                    'url'   => htmlspecialchars_decode($child['url']),
                ];
            }
            $items[] = $row;
        }
    }
}

setSetting('nav_items', json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

header('Location: ../menu.php?updated=1');
exit;
