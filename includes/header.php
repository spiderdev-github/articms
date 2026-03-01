<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

// ─── Thème actif ─────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$activeTheme = getSetting('active_theme', 'default');
if (isset($_GET['_theme_preview']) && isset($_SESSION['admin_id'])) {
    $previewT = basename($_GET['_theme_preview']);
    if (is_dir(__DIR__ . '/../themes/' . $previewT)) $activeTheme = $previewT;
}

// ─── Paramètres entreprise (DB en priorité, constantes en fallback) ───────────
$cmsName         = getSetting('company_name',         COMPANY_NAME);
$cmsPhone        = getSetting('company_phone',        PHONE);
$cmsPhoneDisplay = getSetting('company_phone_display',PHONE_DISPLAY);
$cmsEmail        = getSetting('company_email',        EMAIL);
$cmsRegion       = getSetting('company_region',       REGION);

// Variables attendues par seo.php
$companyName = $cmsName;
$phoneE164   = $cmsPhone;
$email       = $cmsEmail;

// ─── Navigation dynamique ─────────────────────────────────────────────────────
$rawNav   = getSetting('nav_items', '');
$navItems = $rawNav ? (json_decode($rawNav, true) ?: []) : [];
$defaultNav = [
    ['label' => 'Accueil',      'url' => '/'],
    ['label' => 'A propos',     'url' => '/a-propos.php'],
    ['label' => 'Prestations',  'url' => '/prestations.php'],
    ['label' => 'Realisations', 'url' => '/realisations.php'],
    ['label' => 'Contact',      'url' => '/contact'],
];
if (empty($navItems)) $navItems = $defaultNav;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include __DIR__ . "/seo.php"; ?>
<?php if (!empty($_SESSION['admin_id'])): ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── Admin bar ──────────────────────────────────────────────────────── */
#jp-adminbar{
  position:fixed;top:0;left:0;right:0;z-index:99999;
  height:32px;background:#1e1e2d;color:#c2c3ca;
  display:flex;align-items:center;
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  font-size:12px;line-height:1;
  box-shadow:0 1px 4px rgba(0,0,0,.4);
  user-select:none;
}
#jp-adminbar a{color:#c2c3ca;text-decoration:none;}
#jp-adminbar a:hover{color:#fff;}
.jp-ab-logo{display:flex;align-items:center;padding:0 10px;height:100%;background:#12121e;flex-shrink:0;}
.jp-ab-logo svg{width:18px;height:18px;fill:#e74c3c;}
.jp-ab-items{display:flex;align-items:center;height:100%;flex:1;overflow:hidden;}
.jp-ab-item{position:relative;display:flex;align-items:center;height:100%;white-space:nowrap;cursor:pointer;}
.jp-ab-item:hover{background:#2a2a3d;}
.jp-ab-item > a{display:flex;align-items:center;height:100%;padding:0 10px;color:#c2c3ca !important;text-decoration:none;}
.jp-ab-item > a:hover{color:#fff !important;}
.jp-ab-item i{margin-right:5px;font-size:11px;opacity:.8;}
.jp-ab-divider{width:1px;height:18px;background:rgba(255,255,255,.1);flex-shrink:0;}
.jp-ab-sep{padding:0 6px;opacity:.3;}
/* Dropdown */
.jp-ab-item .jp-ab-sub{
  display:none;position:absolute;top:32px;left:0;
  background:#1e1e2d;border:1px solid rgba(255,255,255,.08);
  border-top:2px solid #e74c3c;min-width:180px;
  box-shadow:0 4px 12px rgba(0,0,0,.4);
  z-index:100000;
}
.jp-ab-item:hover .jp-ab-sub{display:block;}
.jp-ab-sub a{display:flex;align-items:center;gap:8px;padding:7px 14px;color:#c2c3ca !important;transition:background .15s;}
.jp-ab-sub a:hover{background:#2a2a3d !important;color:#fff !important;}
.jp-ab-sub a i{width:14px;text-align:center;opacity:.7;}
.jp-ab-sub-sep{height:1px;background:rgba(255,255,255,.06);margin:3px 0;}
/* Right side */
.jp-ab-right{display:flex;align-items:center;height:100%;margin-left:auto;flex-shrink:0;}
.jp-ab-user{display:flex;align-items:center;gap:7px;padding:0 12px;height:100%;cursor:pointer;}
.jp-ab-user:hover{background:#2a2a3d;}
.jp-ab-avatar{width:22px;height:22px;border-radius:50%;object-fit:cover;border:1px solid rgba(255,255,255,.2);}
.jp-ab-avatar-initials{width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;}
.jp-ab-role{font-size:10px;opacity:.5;margin-left:2px;}
.jp-ab-logout{display:flex;align-items:center;height:100%;padding:0 12px;border-left:1px solid rgba(255,255,255,.07);}
.jp-ab-logout a{display:flex;align-items:center;gap:5px;}
.jp-ab-logout a:hover{color:#e74c3c !important;}
/* Push site content down */
body{padding-top:32px !important;}
/* Hide on print */
@media print{#jp-adminbar{display:none;}body{padding-top:0 !important;}}
</style>
<?php endif; ?>
</head>
<body>

<?php if (!empty($_SESSION['admin_id'])):
  $abAdmin = $_SESSION['admin_data'] ?? null;
  $abName  = $abAdmin ? ($abAdmin['display_name'] ?: $abAdmin['username']) : 'Admin';
  $abRole  = $abAdmin['role'] ?? '';
  $abAvatar= $abAdmin['avatar'] ?? '';
  $abRoleLabels = ['super_admin'=>'Super Admin','admin'=>'Admin','editor'=>'Éditeur','author'=>'Auteur'];
  $abRoleLabel  = $abRoleLabels[$abRole] ?? $abRole;
  $abColors = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c'];
  $abColor  = $abColors[abs(crc32($abAdmin['username'] ?? '')) % count($abColors)];
  $abInitial = strtoupper(mb_substr($abName, 0, 1));
?>
<div id="jp-adminbar" role="navigation" aria-label="Barre d'administration">

  <!-- Logo -->
  <a class="jp-ab-logo" href="<?= BASE_URL ?>/admin/dashboard.php" title="Tableau de bord">
    <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
      <path d="M10 2a8 8 0 100 16A8 8 0 0010 2zm-1 11H7V9h2v4zm4 0h-2V7h2v6z"/>
    </svg>
  </a>

  <div class="jp-ab-items">

    <!-- Dashboard -->
    <a class="jp-ab-item" href="<?= BASE_URL ?>/admin/dashboard.php" style="padding: 8px;">
      <i class="fas fa-tachometer-alt"></i> Tableau de bord
    </a>

    <div class="jp-ab-divider"></div>

    <!-- Contacts -->
    <div class="jp-ab-item">
      <a href="<?= BASE_URL ?>/admin/contacts.php"><i class="fas fa-envelope"></i> Contacts</a>
      <div class="jp-ab-sub">
        <a href="<?= BASE_URL ?>/admin/contacts.php"><i class="fas fa-list"></i> Tous les contacts</a>
      </div>
    </div>

    <div class="jp-ab-divider"></div>

    <!-- Réalisations -->
    <div class="jp-ab-item">
      <a href="<?= BASE_URL ?>/admin/realisations.php"><i class="fas fa-paint-roller"></i> Réalisations</a>
      <div class="jp-ab-sub">
        <a href="<?= BASE_URL ?>/admin/realisations.php"><i class="fas fa-list"></i> Toutes les réalisations</a>
        <div class="jp-ab-sub-sep"></div>
        <a href="<?= BASE_URL ?>/admin/realisation-create.php"><i class="fas fa-plus"></i> Nouvelle réalisation</a>
      </div>
    </div>

    <div class="jp-ab-divider"></div>

    <!-- Pages -->
    <div class="jp-ab-item">
      <a href="<?= BASE_URL ?>/admin/cms-pages.php"><i class="fas fa-file-alt"></i> Pages</a>
      <div class="jp-ab-sub">
        <a href="<?= BASE_URL ?>/admin/cms-pages.php"><i class="fas fa-list"></i> Toutes les pages</a>
        <div class="jp-ab-sub-sep"></div>
        <a href="<?= BASE_URL ?>/admin/galleries.php"><i class="fas fa-images"></i> Galeries</a>
        <a href="<?= BASE_URL ?>/admin/media.php"><i class="fas fa-photo-video"></i> Médias</a>
      </div>
    </div>

    <div class="jp-ab-divider"></div>

    <!-- Apparence -->
    <div class="jp-ab-item">
      <a href="<?= BASE_URL ?>/admin/themes.php"><i class="fas fa-palette"></i> Apparence</a>
      <div class="jp-ab-sub">
        <a href="<?= BASE_URL ?>/admin/themes.php"><i class="fas fa-paint-brush"></i> Thèmes</a>
        <a href="<?= BASE_URL ?>/admin/settings.php"><i class="fas fa-sliders-h"></i> Paramètres</a>
      </div>
    </div>

  </div><!-- /.jp-ab-items -->

  <!-- Right: User + Logout -->
  <div class="jp-ab-right">
    <div class="jp-ab-item jp-ab-user">
      <?php if ($abAvatar): ?>
        <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars($abAvatar) ?>" alt="" class="jp-ab-avatar">
      <?php else: ?>
        <span class="jp-ab-avatar-initials" style="background:<?= $abColor ?>"><?= $abInitial ?></span>
      <?php endif; ?>
      <span><?= htmlspecialchars($abName) ?></span>
      <span class="jp-ab-role"><?= htmlspecialchars($abRoleLabel) ?></span>
      <div class="jp-ab-sub" style="right:0;left:auto;">
        <a href="<?= BASE_URL ?>/admin/profile.php"><i class="fas fa-user-circle"></i> Mon profil</a>
        <div class="jp-ab-sub-sep"></div>
        <a href="<?= BASE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Utilisateurs</a>
      </div>
    </div>
    <div class="jp-ab-logout">
      <a href="<?= BASE_URL ?>/admin/logout.php" title="Déconnexion"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
  </div>

</div><!-- /#jp-adminbar -->
<?php endif; ?>

<?php
$_themeHeaderPartial = __DIR__ . '/../themes/' . $activeTheme . '/partials/header.php';
if (file_exists($_themeHeaderPartial)):
    include $_themeHeaderPartial;
else:
?>
<header class="header">
  <div class="container">
    <div class="header-inner">
      <a class="brand" href="<?= BASE_URL ?>" aria-label="<?= htmlspecialchars($cmsName) ?> - Accueil">
        <span class="brand-logo">
          <img src="<?= BASE_URL ?>/assets/images/logo/logo.svg" alt="<?= htmlspecialchars($cmsName) ?>">
        </span>
        <span class="brand-name">
          <strong><?= strtoupper(htmlspecialchars($cmsName)) ?></strong>
          <span>Peinture & Decoration - <?= htmlspecialchars($cmsRegion) ?></span>
        </span>
      </a>

      <nav class="nav" aria-label="Navigation principale">
        <ul>
          <?php foreach ($navItems as $navItem):
            $hasChildren = !empty($navItem['children']);
          ?>
          <li<?= $hasChildren ? ' class="has-dropdown"' : '' ?>>
            <a href="<?= BASE_URL ?><?= htmlspecialchars($navItem['url']) ?>">
              <?= htmlspecialchars($navItem['label']) ?>
              <?php if ($hasChildren): ?><i class="nav-chevron">&#x25BE;</i><?php endif; ?>
            </a>
            <?php if ($hasChildren): ?>
            <ul class="dropdown">
              <?php foreach ($navItem['children'] as $child): ?>
              <li>
                <a href="<?= BASE_URL ?><?= htmlspecialchars($child['url']) ?>">
                  <?= htmlspecialchars($child['label']) ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div class="header-cta">
        <a class="phone-pill" href="tel:<?= htmlspecialchars($cmsPhone) ?>">
          <em>Tél</em> <?= htmlspecialchars($cmsPhoneDisplay) ?>
        </a>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Devis</a>
      </div>

      <!-- Burger button (mobile) -->
      <button class="burger" id="burgerBtn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobileNav">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </div>
</header>

<!-- Overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay" aria-hidden="true"></div>

<!-- Mobile navigation drawer -->
<nav class="mobile-nav" id="mobileNav" aria-label="Navigation mobile" aria-hidden="true">

  <div class="mobile-nav-head">
    <span class="mobile-nav-brand"><?= strtoupper(htmlspecialchars($cmsName)) ?></span>
    <button class="mobile-nav-close" id="mobileNavClose" aria-label="Fermer le menu">&#x2715;</button>
  </div>

  <ul class="mobile-nav-list">
    <?php foreach ($navItems as $navItem):
      $hasChildren = !empty($navItem['children']);
    ?>
    <li<?= $hasChildren ? ' class="has-sub"' : '' ?>>
      <a href="<?= BASE_URL ?><?= htmlspecialchars($navItem['url']) ?>">
        <?= htmlspecialchars($navItem['label']) ?>
        <?php if ($hasChildren): ?>
          <span class="mobile-nav-toggle">&#9660;</span>
        <?php endif; ?>
      </a>
      <?php if ($hasChildren): ?>
      <ul class="mobile-nav-sub">
        <?php foreach ($navItem['children'] as $child): ?>
        <li><a href="<?= BASE_URL ?><?= htmlspecialchars($child['url']) ?>"><?= htmlspecialchars($child['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>

  <div class="mobile-nav-footer">
    <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Demander un devis</a>
    <a class="phone-pill" href="tel:<?= htmlspecialchars($cmsPhone) ?>">
      &#9742; <?= htmlspecialchars($cmsPhoneDisplay) ?>
    </a>
  </div>

</nav>
<?php
endif;
?>