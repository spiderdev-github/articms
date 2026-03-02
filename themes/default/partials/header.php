<?php
/**
 * Partial header — thème default.
 * Inclus par includes/header.php après avoir défini :
 *   $cmsName, $cmsPhone, $cmsPhoneDisplay, $cmsRegion, $navItems, BASE_URL
 */
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
