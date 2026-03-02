<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requirePermission('themes');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../homepage.php'); exit;
}
validateCsrf($_POST['csrf_token'] ?? '');

// ── Résolution du JSON actif ──────────────────────────────────────────────────
$activeTheme  = getSetting('active_theme', 'default');
$jsonPath     = dirname(dirname(__DIR__)) . '/themes/' . $activeTheme . '/partials/home.json';
$fallbackPath = dirname(dirname(__DIR__)) . '/themes/default/partials/home.json';

if (!file_exists($jsonPath)) {
    $themePartialsDir = dirname(dirname(__DIR__)) . '/themes/' . $activeTheme . '/partials';
    if (is_dir($themePartialsDir) && file_exists($fallbackPath)) {
        @copy($fallbackPath, $jsonPath);
    } else {
        $jsonPath = $fallbackPath;
    }
}

// Charger l'ancienne config pour préserver les champs non gérés par ce formulaire
$old  = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
$oldS = $old['sections'] ?? [];

// ── Helpers ───────────────────────────────────────────────────────────────────
$p  = fn(string $key, string $default = ''): string => trim($_POST[$key] ?? $default);
$pb = fn(string $key): bool => !empty($_POST[$key]);

// ── Prestations items ─────────────────────────────────────────────────────────
$pIndices = $_POST['prestation_index'] ?? [];
$pItems   = [];
foreach ($pIndices as $i) {
    $i     = (int)$i;
    $title = trim($_POST['prestation_title'][$i] ?? '');
    if ($title === '') continue;
    $pItems[] = [
        'title'    => $title,
        'subtitle' => trim($_POST['prestation_subtitle'][$i] ?? ''),
        'url'      => trim($_POST['prestation_url'][$i] ?? ''),
        'enabled'  => isset($_POST['prestation_enabled'][$i]),
    ];
}

// ── Build new JSON config ─────────────────────────────────────────────────────
$cfg = [
    '_comment' => $old['_comment'] ?? ('Configuration de la page d\'accueil — thème ' . $activeTheme . '. Ce fichier est la source de vérité des contenus.'),
    '_version' => $old['_version'] ?? 1,

    'seo' => [
        'meta_title'       => $p('home_meta_title'),
        'meta_description' => $p('home_meta_desc'),
    ],

    'sections' => [

        'hero' => [
            'enabled'       => $pb('section_hero_enabled'),
            'kicker'        => $p('home_hero_kicker'),
            'title'         => $p('home_hero_title'),
            'text'          => $p('home_hero_text'),
            'cta_primary'   => [
                'label' => $p('home_hero_cta_primary', 'Demander un devis gratuit'),
                'url'   => $oldS['hero']['cta_primary']['url']   ?? '/contact',
            ],
            'cta_secondary' => [
                'label' => $p('home_hero_cta_secondary', 'Voir les prestations'),
                'url'   => $oldS['hero']['cta_secondary']['url'] ?? '/prestations',
            ],
        ],

        'badges' => [
            'enabled' => $pb('section_badges_enabled'),
            'items'   => array_values(array_filter([
                $p('home_trust_badge1'),
                $p('home_trust_badge2'),
                $p('home_trust_badge3'),
            ])),
        ],

        'prestations_card' => [
            'enabled'       => $pb('section_prestations_enabled'),
            'card_title'    => $p('home_prestations_card_title',    'Nos prestations'),
            'card_subtitle' => $p('home_prestations_card_subtitle', 'Peinture & Décoration'),
            'items'         => $pItems,
            'footer'        => [
                'enabled'    => $pb('home_prestations_footer_enabled'),
                'city_label' => $p('home_prestations_footer_city', 'Alsace - Bas-Rhin - Haut-Rhin'),
                'cta_label'  => $oldS['prestations_card']['footer']['cta_label'] ?? 'Devis',
                'cta_url'    => $oldS['prestations_card']['footer']['cta_url']   ?? '/contact',
            ],
        ],

        'approche' => [
            'enabled' => $pb('section_approche_enabled'),
            'title'   => $p('home_approach_title'),
            'text'    => $p('home_approach_text'),
            'cards'   => [
                [
                    'icon_variant' => $oldS['approche']['cards'][0]['icon_variant'] ?? 'default',
                    'title'        => $p('home_approach_card1_title'),
                    'text'         => $p('home_approach_card1_text'),
                ],
                [
                    'icon_variant' => $oldS['approche']['cards'][1]['icon_variant'] ?? 'gold',
                    'title'        => $p('home_approach_card2_title'),
                    'text'         => $p('home_approach_card2_text'),
                ],
                [
                    'icon_variant' => $oldS['approche']['cards'][2]['icon_variant'] ?? 'default',
                    'title'        => $p('home_approach_card3_title'),
                    'text'         => $p('home_approach_card3_text'),
                ],
            ],
        ],

        'realisations' => [
            'enabled'                 => $pb('section_realisations_enabled'),
            'title'                   => $p('home_realisations_title', 'Réalisations'),
            'text'                    => $p('home_realisations_text'),
            'featured_realisation_id' => ($v = $p('home_featured_realisation_id')) !== '' ? (int)$v : null,
            'gallery_cta'             => $oldS['realisations']['gallery_cta'] ?? ['label' => 'Voir la galerie', 'url' => '/realisations'],
        ],

        'avant_apres' => [
            'enabled'  => $pb('realisations_before_after_enabled'),
            'title'    => $p('realisations_before_after_title', 'Avant / Après'),
            'subtitle' => $p('realisations_before_after_subtitle'),
            'kpis'     => $oldS['avant_apres']['kpis'] ?? [],
            'cta'      => $oldS['avant_apres']['cta']  ?? ['label' => 'Voir le Avant/Après', 'url' => '/realisations'],
        ],

        'local' => [
            'enabled'     => $pb('section_local_enabled'),
            'badge_title' => $p('home_local_badge_title', "Zone d'intervention"),
            'title'       => $p('home_local_title'),
            'intro'       => $p('home_local_intro'),
            'cities'      => array_values(array_filter(array_map('trim', explode(',', $p('home_local_cities'))))),
        ],

        'cta_devis' => [
            'enabled'     => $pb('section_cta_enabled'),
            'title'       => $p('home_cta_devis_title', "Besoin d'un devis ?"),
            'text'        => $p('home_cta_devis_text'),
            'cta_primary' => $oldS['cta_devis']['cta_primary'] ?? ['label' => 'Demander un devis', 'url' => '/contact'],
        ],

    ],
];

// ── Écriture du fichier JSON ───────────────────────────────────────────────────
$json = json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (file_put_contents($jsonPath, $json) === false) {
    header('Location: ../homepage.php?error=write'); exit;
}

header('Location: ../homepage.php?saved=1'); exit;

