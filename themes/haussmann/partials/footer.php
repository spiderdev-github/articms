<?php
// Partial footer Haussmann
// Variables disponibles : $cmsName, $cmsPhone, $cmsPhoneDisplay, $cmsEmail, BASE_URL
$tagline = getSetting('footer_tagline', 'Société de bâtiment – Peinture & Décoration');
$zone    = getSetting('footer_zone', 'Intervention en Alsace');
?>

<footer class="hz-footer">

  <!-- Bande décorative dorée -->
  <div class="hz-footer-accent"></div>

  <!-- Zone principale 4 colonnes -->
  <div class="hz-footer-main">
    <div class="container">
      <div class="hz-footer-grid">

        <!-- Col 1 : Identité de la marque + contact -->
        <div class="hz-footer-brand">
          <div class="hz-footer-logo">
            <div class="hz-footer-logo-icon">
              <img src="<?= BASE_URL ?>/assets/images/logo/logo.svg"
                   alt="<?= htmlspecialchars($cmsName ?? COMPANY_NAME) ?>"
                   width="30" height="30">
            </div>
            <div>
              <div class="hz-footer-logo-name"><?= strtoupper(htmlspecialchars($cmsName ?? COMPANY_NAME)) ?></div>
              <div class="hz-footer-logo-sub">Peinture &amp; Décoration</div>
            </div>
          </div>

          <p class="hz-footer-tagline"><?= htmlspecialchars($tagline) ?></p>
          <p class="hz-footer-tagline" style="margin-top:-8px;font-style:italic;"><?= htmlspecialchars($zone) ?></p>

          <div class="hz-footer-sep"></div>

          <div class="hz-footer-contact-row">
            <a class="hz-footer-contact-item" href="tel:<?= htmlspecialchars($cmsPhone ?? PHONE) ?>">
              <span class="hz-footer-contact-icon">☎</span>
              <?= htmlspecialchars($cmsPhoneDisplay ?? PHONE_DISPLAY) ?>
            </a>
            <a class="hz-footer-contact-item" href="mailto:<?= htmlspecialchars($cmsEmail ?? EMAIL) ?>">
              <span class="hz-footer-contact-icon">✉</span>
              <?= htmlspecialchars($cmsEmail ?? EMAIL) ?>
            </a>
          </div>
        </div>

        <!-- Col 2 : Navigation -->
        <div class="hz-footer-col">
          <h4>Navigation</h4>
          <ul>
            <li><a href="<?= BASE_URL ?>/">Accueil</a></li>
            <li><a href="<?= BASE_URL ?>/a-propos.php">À propos</a></li>
            <li><a href="<?= BASE_URL ?>/prestations.php">Prestations</a></li>
            <li><a href="<?= BASE_URL ?>/realisations.php">Réalisations</a></li>
            <li><a href="<?= BASE_URL ?>/contact">Contact</a></li>
          </ul>
        </div>

        <!-- Col 3 : Services -->
        <div class="hz-footer-col">
          <h4>Nos services</h4>
          <ul>
            <li><a href="<?= BASE_URL ?>/prestations.php">Peinture intérieure</a></li>
            <li><a href="<?= BASE_URL ?>/prestations.php">Peinture extérieure</a></li>
            <li><a href="<?= BASE_URL ?>/prestations.php">Isolation thermique</a></li>
            <li><a href="<?= BASE_URL ?>/prestations.php">Crépi façade</a></li>
            <li><a href="<?= BASE_URL ?>/prestations.php">Mosaïque effet pierre</a></li>
          </ul>
        </div>

        <!-- Col 4 : Devis + info -->
        <div class="hz-footer-col">
          <h4>Devis gratuit</h4>
          <p>Vous avez un projet ? Contactez-nous pour un devis personnalisé, sans engagement.</p>
          <a class="btn btn-primary" href="<?= BASE_URL ?>/contact" style="width:100%;justify-content:center;margin-top:8px;">
            Demander un devis
          </a>
          <div style="margin-top:16px;">
            <h4 style="margin-top:0;">Informations</h4>
            <ul>
              <li><a href="<?= BASE_URL ?>/mentions-legales.php">Mentions légales</a></li>
              <li><a href="<?= BASE_URL ?>/politique-confidentialite.php">Confidentialité</a></li>
            </ul>
          </div>
        </div>

      </div><!-- /.hz-footer-grid -->
    </div><!-- /.container -->
  </div><!-- /.hz-footer-main -->

  <!-- Barre du bas -->
  <div class="hz-footer-bottom">
    <div class="container">
      <div class="hz-footer-bottom-inner">
        <span class="hz-footer-bottom-copy">
          © <?= date('Y') ?> <?= htmlspecialchars($cmsName ?? COMPANY_NAME) ?> — Tous droits réservés
        </span>
        <div class="hz-footer-bottom-links">
          <a href="<?= BASE_URL ?>/mentions-legales.php">Mentions légales</a>
          <a href="<?= BASE_URL ?>/politique-confidentialite.php">Confidentialité</a>
          <a href="<?= BASE_URL ?>/sitemap.xml" target="_blank">Sitemap</a>
        </div>
      </div>
    </div>
  </div>

</footer>
