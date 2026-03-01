<?php
require_once __DIR__ . '/config.php';

$pageTitle = $pageTitle ?? COMPANY_NAME . " - Peinture & Decoration en " . REGION;
$pageDescription = $pageDescription ?? "Entreprise de peinture en " . REGION . " : interieur, exterieur, isolation, crepi facade.";
$pageUrl = BASE_URL . $_SERVER['REQUEST_URI'];
$pageImage = $pageImage ?? BASE_URL . "/assets/images/og-cover.jpg";
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">

<link rel="canonical" href="<?= htmlspecialchars($pageUrl) ?>">

<?php if (!str_contains(BASE_URL, '127.0.0.7')): ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta property="og:url" content="<?= htmlspecialchars($pageUrl) ?>">
<meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>">
<?php endif; ?>

<?php
$_themeName    = $activeTheme ?? 'default';
$_themeDir     = __DIR__ . '/../themes/' . $_themeName;
$_themeCssBase = is_dir($_themeDir) ? (BASE_URL . '/themes/' . $_themeName) : (BASE_URL . '/assets/css');
?>
<link rel="stylesheet" href="<?= $_themeCssBase ?>/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= $_themeCssBase ?>/responsive.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile-nav.css?v=<?= time() ?>">
<link rel="icon" href="<?= BASE_URL ?>/assets/images/logo/favicon.ico">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HousePainter",
  "name": "<?= $companyName ?>",
  "description": "Peinture & decoration en Alsace : interieur, exterieur, isolation, crepi facade, mosaique effet pierre.",
  "areaServed": [
    { "@type": "AdministrativeArea", "name": "Bas-Rhin" },
    { "@type": "AdministrativeArea", "name": "Haut-Rhin" },
    { "@type": "AdministrativeArea", "name": "Alsace" }
  ],
  "telephone": "<?= $phoneE164 ?>",
  "email": "<?= $email ?>",
  "url": "<?= $pageUrl ?>",
  "sameAs": [],
  "serviceType": [
    "Peinture interieure",
    "Peinture exterieure",
    "Isolation",
    "Crepi facade",
    "Mosaique effet pierre"
  ]
}
</script>