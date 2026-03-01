<?php
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../cms.php"); exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    header("Location: ../cms.php?notice=csrf"); exit;
}

$section = $_POST['section'] ?? '';

/* -------------------------------------------------- */
/* Helper : convertit une image uploadée en WebP       */
/* -------------------------------------------------- */
function cmsConvertWebP($tmpFile, $destination) {
    $info = @getimagesize($tmpFile);
    if (!$info) return false;
    switch ($info['mime']) {
        case 'image/jpeg': $img = imagecreatefromjpeg($tmpFile); break;
        case 'image/png':  $img = imagecreatefrompng($tmpFile);  break;
        case 'image/webp': $img = imagecreatefromwebp($tmpFile); break;
        default: return false;
    }
    imagewebp($img, $destination, 82);
    imagedestroy($img);
    return true;
}

function cmsUploadImage($fileKey, $settingKey, $prefix = 'cms') {
    if (empty($_FILES[$fileKey]['tmp_name'])) return;
    $uploadDir = realpath(__DIR__ . '/../../assets/images/cms');
    if (!$uploadDir) {
        mkdir(__DIR__ . '/../../assets/images/cms', 0755, true);
        $uploadDir = realpath(__DIR__ . '/../../assets/images/cms');
    }
    $fileName = $prefix . '_' . time() . '.webp';
    $absPath  = $uploadDir . '/' . $fileName;
    $relPath  = 'assets/images/cms/' . $fileName;
    if (cmsConvertWebP($_FILES[$fileKey]['tmp_name'], $absPath)) {
        setSetting($settingKey, $relPath);
    }
}

function s($key) {
    return trim($_POST[$key] ?? '');
}

/* ================================================== */
switch ($section) {

    /* ------ ENTREPRISE ------ */
    case 'company':
        setSetting('company_name',          s('company_name'));
        setSetting('company_phone',         s('company_phone'));
        setSetting('company_phone_display', s('company_phone_display'));
        setSetting('company_email',         s('company_email'));
        setSetting('company_region',        s('company_region'));
        setSetting('company_address',       s('company_address'));
        setSetting('company_zip',           s('company_zip'));
        setSetting('company_city',          s('company_city'));
        setSetting('company_siret',         s('company_siret'));
        setSetting('footer_tagline',        s('footer_tagline'));
        setSetting('footer_zone',           s('footer_zone'));
        break;

    /* ------ NAVIGATION ------ */
    case 'nav':
        $labels = $_POST['nav_label'] ?? [];
        $urls   = $_POST['nav_url']   ?? [];
        $items = [];
        foreach ($labels as $i => $lbl) {
            $lbl = trim($lbl);
            $url = trim($urls[$i] ?? '');
            if ($lbl !== '') {
                $items[] = ['label' => $lbl, 'url' => $url];
            }
        }
        setSetting('nav_items', json_encode($items, JSON_UNESCAPED_UNICODE));
        break;

    /* ------ ACCUEIL ------ */
    case 'home':
        setSetting('home_meta_title',         s('home_meta_title'));
        setSetting('home_meta_desc',          s('home_meta_desc'));
        setSetting('home_hero_kicker',        s('home_hero_kicker'));
        setSetting('home_hero_title',         s('home_hero_title'));
        setSetting('home_hero_text',          s('home_hero_text'));
        setSetting('home_hero_cta_primary',   s('home_hero_cta_primary'));
        setSetting('home_hero_cta_secondary', s('home_hero_cta_secondary'));
        setSetting('home_approach_title',     s('home_approach_title'));
        setSetting('home_approach_text',      s('home_approach_text'));
        setSetting('home_trust_badge1',       s('home_trust_badge1'));
        setSetting('home_trust_badge2',       s('home_trust_badge2'));
        setSetting('home_trust_badge3',       s('home_trust_badge3'));
        setSetting('home_realisations_title', s('home_realisations_title'));
        setSetting('home_realisations_text',  s('home_realisations_text'));
        setSetting('home_cta_devis_title',    s('home_cta_devis_title'));
        setSetting('home_cta_devis_text',     s('home_cta_devis_text'));
        break;

    /* ------ A PROPOS ------ */
    case 'about':
        setSetting('about_meta_title',      s('about_meta_title'));
        setSetting('about_meta_desc',       s('about_meta_desc'));
        setSetting('about_kicker',          s('about_kicker'));
        setSetting('about_h1',              s('about_h1'));
        setSetting('about_intro',           s('about_intro'));
        setSetting('about_card1_title',     s('about_card1_title'));
        setSetting('about_card1_text',      s('about_card1_text'));
        setSetting('about_card2_title',     s('about_card2_title'));
        setSetting('about_card2_text',      s('about_card2_text'));
        setSetting('about_card3_title',     s('about_card3_title'));
        setSetting('about_card3_text',      s('about_card3_text'));
        setSetting('about_expertise_title', s('about_expertise_title'));
        setSetting('about_expertise_sub',   s('about_expertise_sub'));
        setSetting('about_expertise_body',  s('about_expertise_body'));
        setSetting('about_expertise_zone',  s('about_expertise_zone'));
        setSetting('about_zone_cities',     s('about_zone_cities'));
        setSetting('about_cta_title',       s('about_cta_title'));
        setSetting('about_cta_text',        s('about_cta_text'));
        break;

    /* ------ PRESTATIONS ------ */
    case 'services':
        setSetting('services_meta_title',  s('services_meta_title'));
        setSetting('services_meta_desc',   s('services_meta_desc'));
        setSetting('services_kicker',      s('services_kicker'));
        setSetting('services_h1',          s('services_h1'));
        setSetting('services_intro',       s('services_intro'));
        for ($i = 1; $i <= 5; $i++) {
            setSetting("services_card{$i}_title", s("services_card{$i}_title"));
            setSetting("services_card{$i}_text",  s("services_card{$i}_text"));
            setSetting("services_card{$i}_link",  s("services_card{$i}_link"));
        }
        setSetting('services_method_title', s('services_method_title'));
        setSetting('services_method_sub',   s('services_method_sub'));
        setSetting('services_cta_title',    s('services_cta_title'));
        setSetting('services_cta_text',     s('services_cta_text'));
        break;

    /* ------ CONTACT ------ */
    case 'contact':
        setSetting('contact_meta_title', s('contact_meta_title'));
        setSetting('contact_meta_desc',  s('contact_meta_desc'));
        setSetting('contact_kicker',     s('contact_kicker'));
        setSetting('contact_h1',         s('contact_h1'));
        setSetting('contact_intro',      s('contact_intro'));
        break;

    /* ------ SMTP ------ */
    case 'smtp':
        setSetting('smtp_host',          s('smtp_host'));
        setSetting('smtp_port',          (int)($_POST['smtp_port'] ?? 587));
        setSetting('smtp_user',          s('smtp_user'));
        // Ne pas écraser si champ vide
        if (!empty(trim($_POST['smtp_pass'] ?? ''))) {
            setSetting('smtp_pass', trim($_POST['smtp_pass']));
        }
        setSetting('smtp_from',          s('smtp_from'));
        setSetting('smtp_from_name',     s('smtp_from_name'));
        setSetting('smtp_contact_email', s('smtp_contact_email'));
        break;

    /* ------ RECAPTCHA ------ */
    case 'recaptcha':
        setSetting('captcha_site_key',   s('captcha_site_key'));
        setSetting('captcha_secret_key', s('captcha_secret_key'));
        setSetting('captcha_min_score',  (float)($_POST['captcha_min_score'] ?? 0.5));
        break;

    /* ------ ROBOTS.TXT ------ */
    case 'robots':
        $robotsPath    = __DIR__ . '/../../robots.txt';
        $robotsContent = str_replace("\r\n", "\n", $_POST['robots_content'] ?? '');
        file_put_contents($robotsPath, $robotsContent);
        break;

    /* ------ DASHBOARD ------ */
    case 'dashboard':
        $boolKeys = [
            'dash_block_kpi','dash_block_charts','dash_block_recent','dash_block_crm','dash_block_bottom',
            'dash_kpi_contacts_new','dash_kpi_contacts_month','dash_kpi_realisations',
            'dash_kpi_forms','dash_kpi_cms','dash_kpi_crm_clients','dash_kpi_crm_ca','dash_kpi_crm_pending',
        ];
        foreach ($boolKeys as $k) {
            setSetting($k, isset($_POST[$k]) ? '1' : '0');
        }
        break;

    /* ------ SITEMAP ------ */
    case 'sitemap':
        $domain = rtrim(s('sitemap_domain'), '/');
        $freq   = s('sitemap_changefreq') ?: 'monthly';
        if (!$domain) $domain = 'https://joker-peintre.fr';
        setSetting('sitemap_domain',     $domain);
        setSetting('sitemap_changefreq', $freq);

        // Récupérer toutes les pages CMS publiées avec leur slug parent
        $pdo      = getPDO();
        $allPages = $pdo->query(
            "SELECT id, parent_id, slug, updated_at FROM cms_pages WHERE is_published=1 ORDER BY sort_order, id"
        )->fetchAll(PDO::FETCH_ASSOC);
        $slugMap  = array_column($allPages, 'slug', 'id');

        $urls = [];
        $today = date('Y-m-d');

        // Accueil
        $urls[] = ['loc' => $domain . '/', 'priority' => '1.0', 'freq' => 'weekly',  'lastmod' => $today];

        // Pages statiques fixes
        $statics = [
            ['slug' => 'contact', 'priority' => '0.7'],
            ['slug' => 'info',    'priority' => '0.5'],
        ];
        foreach ($statics as $sp) {
            $urls[] = ['loc' => $domain . '/' . $sp['slug'], 'priority' => $sp['priority'], 'freq' => $freq, 'lastmod' => $today];
        }

        // Pages CMS publiées
        foreach ($allPages as $p) {
            $path    = (!empty($p['parent_id']) && isset($slugMap[$p['parent_id']]))
                       ? $slugMap[$p['parent_id']] . '/' . $p['slug']
                       : $p['slug'];
            $lastmod = !empty($p['updated_at']) ? (new DateTime($p['updated_at']))->format('Y-m-d') : $today;
            $urls[]  = ['loc' => $domain . '/' . $path, 'priority' => '0.8', 'freq' => $freq, 'lastmod' => $lastmod];
        }

        // Construction du XML
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
            $xml .= "    <changefreq>" . $u['freq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        file_put_contents(__DIR__ . '/../../sitemap.xml', $xml);
        break;

    default:
        header("Location: ../cms.php?notice=unknown"); exit;
}

if (in_array($section, ['company', 'smtp', 'recaptcha', 'robots', 'sitemap', 'dashboard'])) {
    header("Location: ../settings.php?tab={$section}&updated=1");
} else {
    header("Location: ../cms.php?tab={$section}&updated=1");
}
exit;
