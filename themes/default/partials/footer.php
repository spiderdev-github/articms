<?php
// Partial footer Default
$tagline = getSetting('footer_tagline', 'Société de bâtiment – Peinture & Décoration');
$zone    = getSetting('footer_zone', 'Intervention en Alsace');
?>

<footer class="df-footer">

  <!-- Bande décorative rouge -->
  <div class="df-footer-accent"></div>

  <!-- Zone principale 4 colonnes -->
  <div class="df-footer-main">
    <div class="container">
      <div class="df-footer-grid">

        <!-- Col 1 : Identité + contact -->
        <div class="df-footer-brand">
          <div class="df-footer-logo">
            <div class="df-footer-logo-icon">
              <img src="<?= BASE_URL ?>/assets/images/logo/logo.svg"
                   alt="<?= htmlspecialchars($cmsName ?? COMPANY_NAME) ?>"
                   width="30" height="30">
            </div>
            <div>
              <div class="df-footer-logo-name"><?= strtoupper(htmlspecialchars($cmsName ?? COMPANY_NAME)) ?></div>
              <div class="df-footer-logo-sub">Peinture &amp; Décoration</div>
            </div>
          </div>

          <p class="df-footer-tagline"><?= htmlspecialchars($tagline) ?></p>
          <p class="df-footer-tagline" style="margin-top:-8px;font-style:italic;"><?= htmlspecialchars($zone) ?></p>

          <div class="df-footer-sep"></div>

          <div class="df-footer-contact-row">
            <a class="df-footer-contact-item" href="tel:<?= htmlspecialchars($cmsPhone ?? PHONE) ?>">
              <span class="df-footer-contact-icon">☎</span>
              <?= htmlspecialchars($cmsPhoneDisplay ?? PHONE_DISPLAY) ?>
            </a>
            <a class="df-footer-contact-item" href="mailto:<?= htmlspecialchars($cmsEmail ?? EMAIL) ?>">
              <span class="df-footer-contact-icon">✉</span>
              <?= htmlspecialchars($cmsEmail ?? EMAIL) ?>
            </a>
          </div>
        </div>

        <!-- Col 2 : Navigation -->
        <div class="df-footer-col">
          <h4>Navigation</h4>
          <ul>
            <li><a href="<?= BASE_URL ?>/">Accueil</a></li>
            <li><a href="<?= BASE_URL ?>/a-propos">À propos</a></li>
            <li><a href="<?= BASE_URL ?>/prestations">Prestations</a></li>
            <li><a href="<?= BASE_URL ?>/realisations">Réalisations</a></li>
            <li><a href="<?= BASE_URL ?>/contact">Contact</a></li>
          </ul>
        </div>

        <!-- Col 3 : Services -->
        <div class="df-footer-col">
          <h4>Nos services</h4>
          <ul>
            <li><a href="<?= BASE_URL ?>/prestations/peinture-interieure-en-alsace">Peinture intérieure</a></li>
            <li><a href="<?= BASE_URL ?>/prestations/isolation-interieure-exterieure">Isolation intérieure / extérieure </a></li>
            <li><a href="<?= BASE_URL ?>/prestations/travaux-de-facade">Travaux de facade</a></li>
            <li><a href="<?= BASE_URL ?>/prestations/revetements-muraux-et-decoration">Revêtements muraux et décoration</a></li>
            <li><a href="<?= BASE_URL ?>/prestations/peinture-exterieure-en-alsace">Peinture exterieure</a></li>
          </ul>
        </div>

        <!-- Col 4 : Devis + infos -->
        <div class="df-footer-col">
          <h4>Devis gratuit</h4>
          <p>Vous avez un projet ? Contactez-nous pour un devis personnalisé, sans engagement.</p>
          <a class="btn btn-primary" href="<?= BASE_URL ?>/contact" style="width:100%;justify-content:center;margin-top:8px;">
            Demander un devis
          </a>
          <div style="margin-top:20px;">
            <h4 style="margin-top:0;">Informations</h4>
            <ul>
              <li><a href="<?= BASE_URL ?>/mentions-legales">Mentions légales</a></li>
              <li><a href="<?= BASE_URL ?>/politique-confidentialite">Confidentialité</a></li>
            </ul>
          </div>
        </div>

      </div><!-- /.df-footer-grid -->
    </div><!-- /.container -->
  </div><!-- /.df-footer-main -->

  <!-- Barre du bas -->
  <div class="df-footer-bottom">
    <div class="container">
      <div class="df-footer-bottom-inner">
        <span class="df-footer-bottom-copy">
          © <?= date('Y') ?> <?= htmlspecialchars($cmsName ?? COMPANY_NAME) ?> — Tous droits réservés
        </span>
        <div class="df-footer-bottom-links">
          <a href="<?= BASE_URL ?>/mentions-legales">Mentions légales</a>
          <a href="<?= BASE_URL ?>/politique-confidentialite">Confidentialité</a>
          <a href="<?= BASE_URL ?>/sitemap.xml" target="_blank">Sitemap</a>
        </div>
      </div>
    </div>
  </div>

</footer>
