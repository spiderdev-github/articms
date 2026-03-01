<?php
// Partials Haussmann — variables disponibles :
// $cmsName, $cmsPhone, $cmsPhoneDisplay, $cmsEmail, $cmsRegion
// $navItems (array), BASE_URL
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<!-- ══ TOPBAR ══════════════════════════════════════════════════════ -->
<div class="hz-topbar">
  <div class="container">
    <div class="hz-topbar-inner">
      <div class="hz-topbar-left">
        <a class="hz-topbar-item" href="tel:<?= htmlspecialchars($cmsPhone) ?>">
          <span class="hz-topbar-dot"></span>
          <?= htmlspecialchars($cmsPhoneDisplay) ?>
        </a>
        <span class="hz-topbar-sep"></span>
        <a class="hz-topbar-item" href="mailto:<?= htmlspecialchars($cmsEmail) ?>">
          <?= htmlspecialchars($cmsEmail) ?>
        </a>
      </div>
      <div class="hz-topbar-right">
        <span class="hz-topbar-item">
          <span class="hz-topbar-dot"></span>
          Intervention en <?= htmlspecialchars($cmsRegion) ?>
        </span>
        <span class="hz-topbar-sep"></span>
        <a class="hz-topbar-item" href="<?= BASE_URL ?>/contact">
          Devis gratuit →
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ══ HEADER PRINCIPAL ═══════════════════════════════════════════ -->
<header class="hz-header">
  <div class="container">

    <!-- Niveau 1 : Badge | Logo centré | CTA -->
    <div class="hz-header-main">

      <!-- Gauche : badge zone d'intervention -->
      <div class="hz-header-badge">
        <span class="hz-header-badge-dot"></span>
        Alsace &amp; alentours
      </div>

      <!-- Centre : logo + nom -->
      <a class="hz-brand" href="<?= BASE_URL ?>" aria-label="<?= htmlspecialchars($cmsName) ?> — Accueil">
        <span class="hz-brand-logo">
          <img src="<?= BASE_URL ?>/assets/images/logo/logo.svg"
               alt="<?= htmlspecialchars($cmsName) ?>"
               width="38" height="38">
        </span>
        <span>
          <span class="hz-brand-name"><?= strtoupper(htmlspecialchars($cmsName)) ?></span>
          <span class="hz-brand-sub">Peinture &amp; Décoration</span>
        </span>
      </a>

      <!-- Droite : téléphone + CTA -->
      <div class="hz-header-cta">
        <a class="phone-pill" href="tel:<?= htmlspecialchars($cmsPhone) ?>">
          <em>Tél</em>
          <?= htmlspecialchars($cmsPhoneDisplay) ?>
        </a>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Devis</a>
        <!-- Burger mobile -->
        <button class="hz-burger" id="hzBurger" aria-label="Menu" aria-expanded="false">
          <svg width="18" height="14" viewBox="0 0 18 14" fill="none">
            <rect y="0"  width="18" height="2" rx="1" fill="currentColor"/>
            <rect y="6"  width="14" height="2" rx="1" fill="currentColor"/>
            <rect y="12" width="18" height="2" rx="1" fill="currentColor"/>
          </svg>
        </button>
      </div>
    </div><!-- /.hz-header-main -->

    <!-- Séparateur doré -->
    <div class="hz-header-divider"></div>

    <!-- Niveau 2 : navigation centrée -->
    <nav class="hz-nav" id="hzNav" aria-label="Navigation principale">
      <div class="hz-nav-inner">
        <ul>
          <?php foreach ($navItems as $item):
            $hasChildren = !empty($item['children']);
            $url         = htmlspecialchars($item['url'] ?? '#');
            $label       = htmlspecialchars($item['label'] ?? '');
            $isActive    = rtrim($currentPath, '/') === rtrim($url, '/');
          ?>
          <li class="<?= $hasChildren ? 'has-dropdown' : '' ?><?= $isActive ? ' hz-active' : '' ?>">
            <a href="<?= BASE_URL ?><?= $url ?>">
              <?= $label ?>
              <?php if ($hasChildren): ?>
                <span class="hz-caret">&#x25BE;</span>
              <?php endif; ?>
            </a>
            <?php if ($hasChildren): ?>
            <ul class="hz-dropdown">
              <?php foreach ($item['children'] as $child): ?>
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
      </div>
    </nav><!-- /.hz-nav -->

  </div><!-- /.container -->
</header>

<!-- Menu mobile overlay -->
<div id="hzMobileMenu" style="
  display:none;position:fixed;inset:0;z-index:200;
  background:rgba(7,6,5,.97);backdrop-filter:blur(16px);
  padding:24px;overflow-y:auto;
">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;">
    <span style="font-family:Georgia,serif;font-size:17px;letter-spacing:.10em;text-transform:uppercase;"><?= strtoupper(htmlspecialchars($cmsName)) ?></span>
    <button onclick="hzCloseMenu()" style="background:rgba(184,154,90,.08);border:1px solid rgba(184,154,90,.18);border-radius:8px;padding:10px 12px;color:#fff;font-size:18px;cursor:pointer;">&#x2715;</button>
  </div>
  <nav>
    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:0;">
      <?php foreach ($navItems as $item):
        $hasChildren = !empty($item['children']);
      ?>
      <li style="border-bottom:1px solid rgba(184,154,90,.12);">
        <a href="<?= BASE_URL ?><?= htmlspecialchars($item['url'] ?? '#') ?>"
           onclick="hzCloseMenu()"
           style="display:block;padding:16px 0;font-size:14px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:rgba(255,250,240,.80);">
          <?= htmlspecialchars($item['label'] ?? '') ?>
        </a>
        <?php if ($hasChildren): ?>
        <ul style="list-style:none;margin:0 0 10px;padding:0 0 0 16px;display:flex;flex-direction:column;gap:4px;">
          <?php foreach ($item['children'] as $child): ?>
          <li>
            <a href="<?= BASE_URL ?><?= htmlspecialchars($child['url']) ?>"
               onclick="hzCloseMenu()"
               style="display:block;padding:8px 0;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:rgba(184,154,90,.80);">
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
  <div style="margin-top:32px;display:flex;flex-direction:column;gap:12px;">
    <a class="btn btn-primary" href="tel:<?= htmlspecialchars($cmsPhone) ?>" style="justify-content:center;">
      <?= htmlspecialchars($cmsPhoneDisplay) ?>
    </a>
    <a class="btn btn-ghost" href="<?= BASE_URL ?>/contact" onclick="hzCloseMenu()" style="justify-content:center;">
      Demander un devis
    </a>
  </div>
</div>

<script>
(function(){
  const burger = document.getElementById('hzBurger');
  const menu   = document.getElementById('hzMobileMenu');
  if (!burger || !menu) return;
  burger.addEventListener('click', function(){
    const open = menu.style.display !== 'none';
    menu.style.display = open ? 'none' : 'block';
    burger.setAttribute('aria-expanded', String(!open));
    document.body.style.overflow = open ? '' : 'hidden';
  });
})();
function hzCloseMenu(){
  const m = document.getElementById('hzMobileMenu');
  if (m) m.style.display = 'none';
  document.body.style.overflow = '';
  const b = document.getElementById('hzBurger');
  if (b) b.setAttribute('aria-expanded', 'false');
}
</script>
