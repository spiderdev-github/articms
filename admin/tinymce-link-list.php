<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$pdo = getPDO();
$rows = $pdo->query(
    "SELECT id, parent_id, slug, title FROM cms_pages WHERE is_published=1 ORDER BY parent_id, sort_order, id"
)->fetchAll(PDO::FETCH_ASSOC);

// Index par id
$byId = [];
foreach ($rows as $r) $byId[$r['id']] = $r;

// Pages fixes du site (PHP natif, hors CMS)
$staticPages = [
    ['title' => 'Accueil',      'value' => BASE_URL . '/'],
    ['title' => 'À propos',     'value' => BASE_URL . '/a-propos.php'],
    ['title' => 'Prestations',  'value' => BASE_URL . '/prestations.php'],
    ['title' => 'Réalisations', 'value' => BASE_URL . '/realisations.php'],
    ['title' => 'Contact',      'value' => BASE_URL . '/contact'],
];

// Pages CMS : regrouper par parent
$parents  = [];
$children = [];
foreach ($rows as $r) {
    if (empty($r['parent_id'])) {
        $parents[$r['id']] = $r;
    } else {
        $children[$r['parent_id']][] = $r;
    }
}

$cmsEntries = [];
foreach ($parents as $id => $p) {
    $url   = BASE_URL . '/' . $p['slug'];
    $entry = ['title' => $p['title'], 'value' => $url];

    if (!empty($children[$id])) {
        $sub = [['title' => $p['title'] . ' (page)', 'value' => $url]];
        foreach ($children[$id] as $c) {
            $sub[] = [
                'title' => '↳ ' . $c['title'],
                'value' => BASE_URL . '/' . $c['slug'],
            ];
        }
        $entry = ['title' => $p['title'], 'menu' => $sub];
    }

    $cmsEntries[] = $entry;
}

$result = [];
if (!empty($cmsEntries)) {
    $result[] = ['title' => '— Pages CMS —', 'menu' => $cmsEntries];
}

// Liens utiles supplémentaires
$result[] = ['title' => '— Liens utiles —', 'menu' => [
    ['title' => 'Mentions légales',          'value' => BASE_URL . '/mentions-legales.php'],
    ['title' => 'Politique de confidentialité', 'value' => BASE_URL . '/politique-confidentialite.php'],
    ['title' => 'Sitemap',                   'value' => BASE_URL . '/sitemap.xml'],
]];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
