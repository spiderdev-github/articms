<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requirePermission('themes');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../homepage.php'); exit;
}
validateCsrf($_POST['csrf_token'] ?? '');

// Liste blanche des clés autorisées
$allowed = [
    'home_meta_title',
    'home_meta_desc',
    'home_hero_kicker',
    'home_hero_title',
    'home_hero_text',
    'home_hero_cta_primary',
    'home_hero_cta_secondary',
    'home_trust_badge1',
    'home_trust_badge2',
    'home_trust_badge3',
    'home_realisations_title',
    'home_realisations_text',
    'home_approach_title',
    'home_approach_text',
    'home_approach_card1_title',
    'home_approach_card1_text',
    'home_approach_card2_title',
    'home_approach_card2_text',
    'home_approach_card3_title',
    'home_approach_card3_text',
    'home_cta_devis_title',
    'home_cta_devis_text',
    'realisations_before_after_enabled',
    'realisations_before_after_title',
    'realisations_before_after_subtitle',
    'home_featured_realisation_id',
    'home_local_title',
    'home_local_intro',
    'home_local_cities',
    'section_hero_enabled',
    'section_prestations_enabled',
    'section_badges_enabled',
    'section_approche_enabled',
    'section_realisations_enabled',
    'section_ba_enabled',
    'section_cta_enabled',
    'section_local_enabled',
    'home_prestations_footer_enabled',
    'home_prestations_footer_city',
    'home_local_badge_title'
];

$checkboxKeys = [
    'realisations_before_after_enabled',
    'section_hero_enabled',
    'section_prestations_enabled',
    'section_badges_enabled',
    'section_approche_enabled',
    'section_realisations_enabled',
    'section_ba_enabled',
    'section_cta_enabled',
    'section_local_enabled',
    'home_prestations_footer_enabled'
];
foreach ($allowed as $key) {
    if (in_array($key, $checkboxKeys)) {
        setSetting($key, isset($_POST[$key]) ? '1' : '0');
    } else {
        setSetting($key, trim($_POST[$key] ?? ''));
    }
}

// Prestations JSON
$pIndices = $_POST['prestation_index'] ?? [];
$pItems   = [];
foreach ($pIndices as $i) {
    $i     = (int)$i;
    $title = trim($_POST['prestation_title'][$i] ?? '');
    if ($title === '') continue; // ignorer les lignes vides
    $pItems[] = [
        'title'    => $title,
        'subtitle' => trim($_POST['prestation_subtitle'][$i] ?? ''),
        'url'      => trim($_POST['prestation_url'][$i] ?? ''),
        'enabled'  => isset($_POST['prestation_enabled'][$i]),
    ];
}
setSetting('home_prestations_items',       json_encode($pItems, JSON_UNESCAPED_UNICODE));
setSetting('home_prestations_card_title',    trim($_POST['home_prestations_card_title']    ?? 'Prestations'));
setSetting('home_prestations_card_subtitle', trim($_POST['home_prestations_card_subtitle'] ?? 'Peinture & Decoration'));

header('Location: ../homepage.php?saved=1'); exit;
