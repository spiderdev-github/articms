<?php
$_themeFooterPartial = __DIR__ . '/../themes/' . ($activeTheme ?? 'default') . '/partials/footer.php';
if (file_exists($_themeFooterPartial)):
    include $_themeFooterPartial;
else:
?>
<footer class="footer">
    <div class="container">
        <div class="footer-col">
            <h3><?= htmlspecialchars($cmsName ?? COMPANY_NAME) ?></h3>
            <p><?= htmlspecialchars(getSetting('footer_tagline', 'Société de bâtiment – Peinture & Décoration')) ?></p>
            <p><?= htmlspecialchars(getSetting('footer_zone', 'Intervention en Alsace')) ?></p>
        </div>

        <div class="footer-col">
            <h3>Contact</h3>
            <p>Tél : <a href="tel:<?= htmlspecialchars($cmsPhone ?? PHONE) ?>"><?= htmlspecialchars($cmsPhoneDisplay ?? PHONE_DISPLAY) ?></a></p>
            <p>Email : <a href="mailto:<?= htmlspecialchars($cmsEmail ?? EMAIL) ?>"><?= htmlspecialchars($cmsEmail ?? EMAIL) ?></a></p>
        </div>

        <div class="footer-col">
            <h3>Informations</h3>
            <ul>
                <li><a href="<?= BASE_URL ?>/mentions-legales.php">Mentions légales</a></li>
                <li><a href="<?= BASE_URL ?>/politique-confidentialite.php">Confidentialité</a></li>
                <li><button onclick="window.jpReopenCookies && window.jpReopenCookies()" style="background:none;border:none;padding:0;color:inherit;font:inherit;cursor:pointer;text-decoration:underline;opacity:.8;">Gérer les cookies</button></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        © <?= date("Y") ?> <?= htmlspecialchars($cmsName ?? COMPANY_NAME) ?> – Tous droits réservés
    </div>
</footer>
<?php endif; ?>
<script src="<?= BASE_URL ?>/assets/js/form.js"></script>

<!-- ── Mobile navigation ── -->
<script>
(function(){
  var burger  = document.getElementById('burgerBtn');
  var nav     = document.getElementById('mobileNav');
  var overlay = document.getElementById('mobileNavOverlay');
  if(!burger || !nav) return;

  function openMenu(){
    burger.classList.add('is-open');
    nav.classList.add('is-open');
    overlay.classList.add('is-open');
    burger.setAttribute('aria-expanded','true');
    nav.setAttribute('aria-hidden','false');
    overlay.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu(){
    burger.classList.remove('is-open');
    nav.classList.remove('is-open');
    overlay.classList.remove('is-open');
    burger.setAttribute('aria-expanded','false');
    nav.setAttribute('aria-hidden','true');
    overlay.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  burger.addEventListener('click', function(){
    nav.classList.contains('is-open') ? closeMenu() : openMenu();
  });

  document.getElementById('mobileNavClose').addEventListener('click', closeMenu);
  overlay.addEventListener('click', closeMenu);

  /* Close on Escape */
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeMenu();
  });

  /* Sub-menu accordion */
  nav.querySelectorAll('.has-sub > a').forEach(function(link){
    link.addEventListener('click', function(e){
      var li = this.closest('.has-sub');
      var sub = li.querySelector('.mobile-nav-sub');
      if(!sub) return;
      e.preventDefault();
      var isOpen = li.classList.contains('sub-open');
      /* Close all */
      nav.querySelectorAll('.has-sub').forEach(function(el){ el.classList.remove('sub-open'); });
      if(!isOpen) li.classList.add('sub-open');
    });
  });
})();
</script>

<link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
  const lightbox = GLightbox();
</script>

<script>
(function(){

  const els = document.querySelectorAll('[data-ba]');
  if(!els.length) return;

  const clamp = (v,min,max)=>Math.min(max,Math.max(min,v));

  const vibrateSoft = () => {
    if(navigator.vibrate){
      navigator.vibrate(8); // vibration très légère
    }
  };

  const setPos = (wrap, pct, withSnapClass=false) => {

    const before = wrap.querySelector('[data-ba-before]');
    const handle = wrap.querySelector('[data-ba-handle]');
    const range  = wrap.querySelector('[data-ba-range]');

    pct = clamp(pct, 0, 100);

    if(withSnapClass){
      before.classList.add('ba-snap');
      handle.classList.add('ba-snap');
    } else {
      before.classList.remove('ba-snap');
      handle.classList.remove('ba-snap');
    }

    before.style.clipPath = `inset(0 ${100 - pct}% 0 0)`;
    handle.style.left = pct + '%';
    range.value = pct;
  };

  const initOne = (wrap) => {

    const range = wrap.querySelector('[data-ba-range]');
    let pct = parseFloat(range.value || 50);
    setPos(wrap, pct);

    let dragging = false;
    let lastSnap = false;

    const onDown = (e) => {
      dragging = true;
      wrap.setPointerCapture && wrap.setPointerCapture(e.pointerId);
      onMove(e);
    };

    const onMove = (e) => {
      if(!dragging) return;

      const rect = wrap.getBoundingClientRect();
      const x = e.clientX - rect.left;
      let pctNow = (x / rect.width) * 100;

      // Snap zone centre
      if(Math.abs(pctNow - 50) < 3){
        pctNow = 50;
        if(!lastSnap){
          vibrateSoft();
          lastSnap = true;
        }
      } else {
        lastSnap = false;
      }

      setPos(wrap, pctNow);
    };

    const onUp = () => {
      dragging = false;
      setPos(wrap, parseFloat(range.value), true);
      setTimeout(()=>{
        wrap.querySelector('[data-ba-before]').classList.remove('ba-snap');
        wrap.querySelector('[data-ba-handle]').classList.remove('ba-snap');
      },350);
    };

    wrap.addEventListener('pointerdown', onDown);
    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);

    // Range support (mobile)
    range.addEventListener('input', () => {
      let val = parseFloat(range.value);

      if(Math.abs(val - 50) < 2){
        val = 50;
        vibrateSoft();
      }

      setPos(wrap, val);
    });

  };

  // Reveal animation
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(en=>{
      if(en.isIntersecting){
        en.target.classList.add('is-revealed');
        io.unobserve(en.target);
      }
    });
  }, { threshold: 0.25 });

  els.forEach(wrap=>{
    initOne(wrap);
    io.observe(wrap);
  });
  
  let pct = 50;
  setPos(els[0], pct);
})();
</script>

<div class="sticky-contact" role="navigation" aria-label="Contact rapide">
  <a class="sticky-btn sticky-call" href="tel:<?= PHONE ?>">
    Appeler
  </a>
  <a class="sticky-btn sticky-quote" href="<?= BASE_URL ?>/contact">
    Devis gratuit
  </a>
</div>

<!-- ── Cookie Consent ──────────────────────────────────────────────────── -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cookie-consent.css">

<!-- Bannière -->
<div id="jp-cookie-banner" role="dialog" aria-live="polite" aria-label="Gestion des cookies" style="display:none">
  <div class="jp-cb-inner">
    <div class="jp-cb-text">
      <strong>Ce site utilise des cookies</strong>
      Nous utilisons des cookies pour améliorer votre expérience et mesurer l'audience.
      <a href="<?= BASE_URL ?>/politique-confidentialite">En savoir plus</a>
    </div>
    <div class="jp-cb-actions">
      <button id="jp-cb-custom" class="jp-cb-btn jp-cb-custom">Personnaliser</button>
      <button id="jp-cb-refuse" class="jp-cb-btn jp-cb-refuse">Refuser</button>
      <button id="jp-cb-accept" class="jp-cb-btn jp-cb-accept">Tout accepter</button>
    </div>
  </div>
</div>

<!-- Bouton flottant ré-ouverture -->
<button id="jp-cookie-reopen" title="Gérer mes cookies" aria-label="Gérer mes préférences cookies">🍪 Cookies</button>

<!-- Modal personnalisation -->
<div id="jp-cookie-modal" role="dialog" aria-modal="true" aria-label="Personnaliser les cookies">
  <div class="jp-cm-box">
    <h2>Personnaliser mes préférences</h2>
    <p>Choisissez quels cookies vous souhaitez activer. Les cookies nécessaires au fonctionnement du site ne peuvent pas être désactivés.</p>

    <div class="jp-cm-category">
      <div class="jp-cm-cat-header">
        <div>
          <strong>Cookies nécessaires</strong>
          <small>Session, sécurité, formulaires de contact. Toujours actifs.</small>
        </div>
        <label class="jp-toggle">
          <input type="checkbox" checked disabled>
          <span class="jp-toggle-track"></span>
        </label>
      </div>
    </div>

    <div class="jp-cm-category">
      <div class="jp-cm-cat-header">
        <div>
          <strong>Cookies analytiques</strong>
          <small>Mesure d'audience anonymisée (Google Analytics, etc.).</small>
        </div>
        <label class="jp-toggle">
          <input type="checkbox" id="jp-toggle-analytics" checked>
          <span class="jp-toggle-track"></span>
        </label>
      </div>
    </div>

    <div class="jp-cm-category">
      <div class="jp-cm-cat-header">
        <div>
          <strong>Cookies marketing</strong>
          <small>Publicité personnalisée et partage sur les réseaux sociaux.</small>
        </div>
        <label class="jp-toggle">
          <input type="checkbox" id="jp-toggle-marketing">
          <span class="jp-toggle-track"></span>
        </label>
      </div>
    </div>

    <div class="jp-cm-footer">
      <button class="jp-cm-refuse" id="jp-cm-refuse">Tout refuser</button>
      <button class="jp-cm-save"   id="jp-cm-save">Enregistrer mes choix</button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/cookie-consent.js"></script>
</body>
</html>