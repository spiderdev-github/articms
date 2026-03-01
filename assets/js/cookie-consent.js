/**
 * Joker Peintre – Cookie Consent Manager
 * Stockage : localStorage  key = jp_cookie_consent
 * Valeur JSON : { v:1, analytics: bool, marketing: bool, ts: timestamp }
 * Si null/absent → bannière affichée.
 */
(function () {
  'use strict';

  const KEY = 'jp_cookie_consent';

  /* ───────────── Helpers ───────────── */
  function getConsent() {
    try { return JSON.parse(localStorage.getItem(KEY)); } catch (e) { return null; }
  }
  function saveConsent(obj) {
    obj.v = 1;
    obj.ts = Date.now();
    localStorage.setItem(KEY, JSON.stringify(obj));
  }

  /* ───────────── Elements ───────────── */
  var banner  = document.getElementById('jp-cookie-banner');
  var modal   = document.getElementById('jp-cookie-modal');
  var reopen  = document.getElementById('jp-cookie-reopen');

  if (!banner) return; // guard

  /* ───────────── Banner controls ───────────── */
  function showBanner() {
    banner.style.display = '';
    requestAnimationFrame(function () {
      banner.classList.add('jp-cb-visible');
    });
    if (reopen) reopen.style.display = 'none';
  }

  function hideBanner() {
    banner.classList.remove('jp-cb-visible');
    banner.addEventListener('transitionend', function handler() {
      banner.style.display = 'none';
      banner.removeEventListener('transitionend', handler);
    });
    if (reopen) reopen.style.display = 'block';
  }

  /* ───────────── Accept all ───────────── */
  document.getElementById('jp-cb-accept').addEventListener('click', function () {
    saveConsent({ analytics: true, marketing: true });
    hideBanner();
    fireConsent({ analytics: true, marketing: true });
  });

  /* ───────────── Refuse all ───────────── */
  document.getElementById('jp-cb-refuse').addEventListener('click', function () {
    saveConsent({ analytics: false, marketing: false });
    hideBanner();
  });

  /* ───────────── Open modal ───────────── */
  document.getElementById('jp-cb-custom').addEventListener('click', openModal);
  if (reopen) reopen.addEventListener('click', function () {
    hideBanner();
    openModal();
  });

  /* ───────────── Modal ───────────── */
  function openModal() {
    var consent = getConsent() || {};
    var togAnalytics = document.getElementById('jp-toggle-analytics');
    var togMarketing = document.getElementById('jp-toggle-marketing');
    if (togAnalytics) togAnalytics.checked = consent.analytics !== false;
    if (togMarketing) togMarketing.checked = consent.marketing === true;
    if (modal) modal.classList.add('jp-cm-open');
  }
  function closeModal() {
    if (modal) modal.classList.remove('jp-cm-open');
  }

  /* Close modal on backdrop click */
  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });
  }

  /* Modal: save selection */
  var btnSave = document.getElementById('jp-cm-save');
  if (btnSave) {
    btnSave.addEventListener('click', function () {
      var a = document.getElementById('jp-toggle-analytics');
      var m = document.getElementById('jp-toggle-marketing');
      var obj = {
        analytics: a ? a.checked : false,
        marketing: m ? m.checked : false
      };
      saveConsent(obj);
      closeModal();
      hideBanner();
      fireConsent(obj);
    });
  }

  /* Modal: refuse all */
  var btnRefuse = document.getElementById('jp-cm-refuse');
  if (btnRefuse) {
    btnRefuse.addEventListener('click', function () {
      saveConsent({ analytics: false, marketing: false });
      closeModal();
      hideBanner();
    });
  }

  /* ───────────── Fire consent callbacks ───────────── */
  function fireConsent(prefs) {
    /**
     * Point d'extension : charger les scripts de tracking si consentis.
     * Exemple pour Google Analytics :
     *
     *  if (prefs.analytics && typeof window.loadGoogleAnalytics === 'function') {
     *    window.loadGoogleAnalytics();
     *  }
     *
     * Les scripts tiers NE DOIVENT PAS être dans header.php directement,
     * mais chargés ici après consentement.
     */
    // Dispatch custom event for other scripts to listen
    document.dispatchEvent(new CustomEvent('jpCookieConsent', { detail: prefs }));
  }

  /* ───────────── Init ───────────── */
  var existing = getConsent();
  if (!existing) {
    // Premier passage - show banner after short delay
    setTimeout(showBanner, 600);
  } else {
    // Already chosen, show reopen button
    if (reopen) reopen.style.display = 'block';
    // Re-fire consent for scripts that need it on load
    fireConsent(existing);
  }

  /* Expose globally to let other scripts check consent */
  window.jpGetConsent = getConsent;
  window.jpReopenCookies = function () { showBanner(); };

})();
