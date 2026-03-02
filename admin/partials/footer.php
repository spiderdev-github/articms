      </div>
    </section>
  </div>

  <footer class="main-footer text-sm">
    <strong>JokerPeintre</strong> - Admin by <a href="https://spiderdev.com" target="_blank">Spiderdev</a> - &copy; <?= date('Y') ?> <span style="float:right;"><a href="https://github.com/spiderdev-github/articms" target="_blank">v<?= CMS_VERSION ?></a></span>
  </footer>
</div>

<!-- AdminLTE scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
<script>
  $(function(){ if(window.bsCustomFileInput){ bsCustomFileInput.init(); } });
</script>

<!-- ══ PANEL PERSONNALISATION ══════════════════════════════════════════════ -->
<div id="db-overlay"></div>
<div id="db-panel">
  <div class="db-panel-header">
    <span><i class="fas fa-sliders-h mr-2"></i>Paramètres</span>
    <button id="db-panel-close"><i class="fas fa-times"></i></button>
  </div>

  <!-- ── Onglets ────────────────────────────────────────── -->
  <div class="db-panel-tabs">
    <button class="db-tab-btn active" data-tab="db-tab-widgets">
      <i class="fas fa-th-large mr-1"></i>Widgets
    </button>
    <button class="db-tab-btn" data-tab="db-tab-display">
      <i class="fas fa-palette mr-1"></i>Affichage
    </button>
  </div>

  <!-- ── Onglet Widgets ─────────────────────────────────── -->
  <div class="db-panel-body db-tab-pane" id="db-tab-widgets">
    <p class="text-muted" style="font-size:.8rem;margin-bottom:1rem;">Cochez les widgets à afficher. Votre choix est sauvegardé automatiquement.</p>
    <ul class="db-toggle-list">

      <!-- KPI row -->
      <li class="db-kpi-group">
        <label class="db-toggle-item" style="border-radius: 8px 8px 0 0;">
          <input type="checkbox" class="db-toggle" data-block="block-kpi" checked>
          <span class="db-toggle-icon" style="background:#dc3545"><i class="fas fa-tachometer-alt"></i></span>
          <span class="db-toggle-label">Barre KPI</span>
          <span class="db-toggle-switch"></span>
        </label>
        <button type="button" class="db-kpi-footer" aria-expanded="false">
          <i class="fas fa-sliders-h"></i><span>Choisir les KPI affichés</span><i class="fas fa-chevron-down db-kpi-chevron"></i>
        </button>
        <ul class="db-kpi-sub" style="padding-left: 0.4rem; display:none">

          <?php if (can('contacts')): ?>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-contacts-new" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#dc3545"><i class="fas fa-envelope-open-text"></i></span>
              <span class="db-toggle-label">Contacts nouveaux</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-contacts-month" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#17a2b8"><i class="fas fa-calendar-alt"></i></span>
              <span class="db-toggle-label">Contacts ce mois</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <?php endif; ?>

          <?php if (can('realisations')): ?>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-realisations" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#28a745"><i class="fas fa-paint-roller"></i></span>
              <span class="db-toggle-label">Réalisations publiées</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <?php endif; ?>

          <?php if (can('forms')): ?>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-forms" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#ffc107"><i class="fas fa-paper-plane"></i></span>
              <span class="db-toggle-label">Soumissions non lues</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <?php endif; ?>

          <?php if (can('cms')): ?>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-cms" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#6f42c1"><i class="fas fa-file-alt"></i></span>
              <span class="db-toggle-label">Pages CMS</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <?php endif; ?>

          <?php if (can('crm')): ?>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-crm-clients" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#0d6efd"><i class="fas fa-address-book"></i></span>
              <span class="db-toggle-label">Clients CRM</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-crm-ca" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#20c997"><i class="fas fa-euro-sign"></i></span>
              <span class="db-toggle-label">CA TTC accepté</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <li>
            <label class="db-toggle-item db-toggle-item-sm">
              <input type="checkbox" class="db-toggle" data-block="kpi-crm-pending" checked>
              <span class="db-toggle-icon db-ti-sm" style="background:#fd7e14"><i class="fas fa-hourglass-half"></i></span>
              <span class="db-toggle-label">Devis en attente</span>
              <span class="db-toggle-switch db-sw-sm"></span>
            </label>
          </li>
          <?php endif; ?>

        </ul>
      </li>

      <!-- Widgets individuels -->
      <?php if (can('contacts')): ?>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-contacts-chart" checked>
          <span class="db-toggle-icon" style="background:#007bff"><i class="fas fa-chart-line"></i></span>
          <span class="db-toggle-label">Graphique contacts</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-service-chart" checked>
          <span class="db-toggle-icon" style="background:#dc3545"><i class="fas fa-chart-pie"></i></span>
          <span class="db-toggle-label">Graphique par service</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-recent-contacts" checked>
          <span class="db-toggle-icon" style="background:#ffc107"><i class="fas fa-users"></i></span>
          <span class="db-toggle-label">Derniers contacts</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <?php endif; ?>

      <?php if (can('realisations')): ?>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-recent-realisations" checked>
          <span class="db-toggle-icon" style="background:#28a745"><i class="fas fa-paint-roller"></i></span>
          <span class="db-toggle-label">Dernières réalisations</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <?php endif; ?>

      <?php if (can('crm')): ?>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-crm-devis" checked>
          <span class="db-toggle-icon" style="background:#0d6efd"><i class="fas fa-file-invoice"></i></span>
          <span class="db-toggle-label">Devis &amp; Factures</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-crm-overview" checked>
          <span class="db-toggle-icon" style="background:#20c997"><i class="fas fa-chart-bar"></i></span>
          <span class="db-toggle-label">CRM — Vue d'ensemble</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <?php endif; ?>

      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-shortcuts" checked>
          <span class="db-toggle-icon" style="background:#17a2b8"><i class="fas fa-bolt"></i></span>
          <span class="db-toggle-label">Raccourcis</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-sysinfo" checked>
          <span class="db-toggle-icon" style="background:#6c757d"><i class="fas fa-server"></i></span>
          <span class="db-toggle-label">Infos système</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>

      <?php if (can('users')): ?>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="widget-logins" checked>
          <span class="db-toggle-icon" style="background:#343a40"><i class="fas fa-sign-in-alt"></i></span>
          <span class="db-toggle-label">Dernières connexions</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <?php endif; ?>

    </ul>
    <button id="db-panel-reset" class="btn btn-sm btn-outline-secondary mt-3 w-100">
      <i class="fas fa-undo mr-1"></i>Réinitialiser
    </button>
  </div>

  <!-- ── Onglet Affichage ────────────────────────────────── -->
  <div class="db-panel-body db-tab-pane" id="db-tab-display" style="display:none">

    <p class="db-section-label">Thème du tableau de bord</p>
    <div class="db-mode-picker">
      <button type="button" class="db-mode-btn" data-mode="dark">
        <span class="db-mode-preview db-mode-preview-dark">
          <span></span><span></span><span></span>
        </span>
        <span class="db-mode-name"><i class="fas fa-moon mr-1"></i>Sombre</span>
      </button>
      <button type="button" class="db-mode-btn" data-mode="light">
        <span class="db-mode-preview db-mode-preview-light">
          <span></span><span></span><span></span>
        </span>
        <span class="db-mode-name"><i class="fas fa-sun mr-1"></i>Clair</span>
      </button>
    </div>

    <p class="db-section-label" style="margin-top:1.2rem;">Navigation</p>
    <label class="db-toggle-item" id="db-sidebar-toggle-item">
      <input type="checkbox" id="db-sidebar-chk">
      <span class="db-toggle-icon" style="background:#495057"><i class="fas fa-bars"></i></span>
      <span class="db-toggle-label">Sidebar ouverte</span>
      <span class="db-toggle-switch"></span>
    </label>

  </div>

</div>

<style>
/* ── Dashboard spacing ─────────────────────────────────────── */
.content .db-block { margin-bottom: .75rem; }
.content .db-block:last-child { margin-bottom: 0; }
.content .db-block .card { margin-bottom: .5rem; }
.content .db-block .small-box { margin-bottom: .5rem; }

/* ── Panel overlay ─────────────────────────────────────────── */
#db-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 1049;
}
#db-overlay.open { display: block; }

/* ── Panel ─────────────────────────────────────────────────── */
#db-panel {
  position: fixed;
  top: 0; right: 0;
  width: 320px; height: 100vh;
  background: #1e2130;
  border-left: 1px solid rgba(255,255,255,.1);
  z-index: 1050;
  display: flex; flex-direction: column;
  transform: translateX(105%);
  transition: transform .28s cubic-bezier(.4,0,.2,1);
  box-shadow: -4px 0 24px rgba(0,0,0,.4);
}
#db-panel.open { transform: translateX(0); }

.db-panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .9rem 1.1rem;
  border-bottom: 1px solid rgba(255,255,255,.1);
  font-weight: 600; font-size: .95rem; color: #e0e0e0;
  flex-shrink: 0;
}
#db-panel-close {
  background: none; border: none;
  color: #aaa; font-size: 1.1rem; cursor: pointer; padding: 0;
  line-height: 1;
}
#db-panel-close:hover { color: #fff; }

.db-panel-body {
  flex: 1; overflow-y: auto;
  padding: 1rem 1.1rem;
}

.db-toggle-list {
  list-style: none; padding: 0; margin: 0;
}
.db-toggle-list li + li { margin-top: .4rem; }

.db-toggle-item {
  display: flex; align-items: center; gap: .7rem;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 8px;
  padding: .6rem .75rem;
  margin: 0; cursor: pointer;
  transition: background .15s;
}
.db-toggle-item:hover { background: rgba(255,255,255,.08); }

.db-toggle-item input[type=checkbox] { display: none; }

.db-toggle-icon {
  width: 30px; height: 30px; border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .8rem; flex-shrink: 0;
}

.db-toggle-label {
  flex: 1; color: #d0d0d0; font-size: .85rem;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Toggle switch */
.db-toggle-switch {
  width: 36px; height: 20px; border-radius: 10px;
  background: #444; flex-shrink: 0;
  position: relative; transition: background .2s;
}
.db-toggle-switch::after {
  content: '';
  position: absolute; top: 3px; left: 3px;
  width: 14px; height: 14px; border-radius: 50%;
  background: #888; transition: transform .2s, background .2s;
}
.db-toggle-item:has(input:checked) .db-toggle-switch {
  background: #28a745;
}
.db-toggle-item:has(input:checked) .db-toggle-switch::after {
  transform: translateX(16px);
  background: #fff;
}
.db-toggle-item:has(input:not(:checked)) .db-toggle-label {
  color: #666;
  text-decoration: line-through;
}

/* ── Panel tabs ────────────────────────────────────────────── */
.db-panel-tabs {
  display: flex;
  border-bottom: 1px solid rgba(255,255,255,.1);
  flex-shrink: 0;
}
.db-tab-btn {
  flex: 1;
  background: none; border: none;
  color: #999; font-size: .82rem; font-weight: 500;
  padding: .65rem .5rem;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: color .15s, border-color .15s;
  line-height: 1.2;
}
.db-tab-btn:hover { color: #ddd; }
.db-tab-btn.active {
  color: #fff;
  border-bottom-color: #4f8ef7;
}

/* ── Mode picker (dark/light) ──────────────────────────────── */
.db-section-label {
  font-size: .7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #666;
  margin: 0 0 .75rem;
}
.db-mode-picker {
  display: flex;
  gap: .6rem;
}
.db-mode-btn {
  flex: 1;
  background: rgba(255,255,255,.05);
  border: 2px solid rgba(255,255,255,.1);
  border-radius: 10px;
  padding: .6rem .4rem;
  cursor: pointer;
  display: flex; flex-direction: column; align-items: center; gap: .5rem;
  transition: border-color .2s, background .2s;
  color: #aaa;
}
.db-mode-btn:hover { border-color: rgba(255,255,255,.25); background: rgba(255,255,255,.08); }
.db-mode-btn.active {
  border-color: #4f8ef7;
  background: rgba(79,142,247,.1);
  color: #fff;
}
/* mini preview strip */
.db-mode-preview {
  display: flex; gap: 3px;
  width: 100%; justify-content: center;
}
.db-mode-preview span {
  border-radius: 3px;
  height: 28px;
}
.db-mode-preview span:nth-child(1) { width: 30%; }
.db-mode-preview span:nth-child(2) { width: 50%; }
.db-mode-preview span:nth-child(3) { width: 20%; flex: 1; }
/* dark preview */
.db-mode-preview-dark span:nth-child(1) { background: #2d3035; }
.db-mode-preview-dark span:nth-child(2) { background: #1e2130; }
.db-mode-preview-dark span:nth-child(3) { background: #3a3f50; }
/* light preview */
.db-mode-preview-light span:nth-child(1) { background: #e9ecef; }
.db-mode-preview-light span:nth-child(2) { background: #f4f6f9; }
.db-mode-preview-light span:nth-child(3) { background: #dee2e6; }

.db-mode-name { font-size: .78rem; font-weight: 500; }

/* ── KPI sub-list ───────────────────────────────────────────────── */
.db-kpi-group { list-style: none; }

.db-kpi-footer {
  display: flex; align-items: center; gap: .45rem;
  width: 100%;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-top: none;
  border-radius: 0 0 8px 8px;
  color: #888; font-size: .75rem;
  padding: .4rem .75rem;
  cursor: pointer;
  transition: background .15s, color .15s;
  margin-bottom: 3px;
}
.db-kpi-footer:hover { background: rgba(255,255,255,.08); color: #ccc; }
.db-kpi-footer .db-kpi-chevron {
  margin-left: auto;
  font-size: .65rem;
  transition: transform .2s;
}
.db-kpi-footer[aria-expanded="true"] .db-kpi-chevron { transform: rotate(180deg); }

.db-kpi-sub {
  list-style: none;
  padding: .4rem .4rem .4rem 1.2rem;
  margin: -3px 0 3px;
  background: rgba(0,0,0,.18);
  border: 1px solid rgba(255,255,255,.06);
  border-top: none;
  border-radius: 0 0 8px 8px;
}
.db-kpi-sub li + li { margin-top: .3rem; }

/* Small variants for sub-items */
.db-toggle-item-sm {
  padding: .4rem .6rem;
  border-radius: 6px;
}
.db-ti-sm {
  width: 22px !important; height: 22px !important;
  border-radius: 5px;
  font-size: .7rem;
}
.db-sw-sm {
  width: 28px !important; height: 16px !important;
  border-radius: 8px !important;
}
.db-sw-sm::after {
  width: 10px !important; height: 10px !important;
  top: 3px !important; left: 3px !important;
}
.db-toggle-item-sm:has(input:checked) .db-sw-sm::after {
  transform: translateX(12px) !important;
}
</style>

<script>
(function(){
  const STORAGE_KEY  = 'joker_dashboard_blocks';
  const SORT_KEY     = 'joker_dashboard_order';
  const MODE_KEY     = 'joker_dashboard_mode';
  const SIDEBAR_KEY  = 'joker_sidebar_state';
  const allBlocks = [
    'block-kpi',
    'kpi-contacts-new', 'kpi-contacts-month', 'kpi-realisations',
    'kpi-forms', 'kpi-cms', 'kpi-crm-clients', 'kpi-crm-ca', 'kpi-crm-pending',
    'widget-contacts-chart', 'widget-service-chart',
    'widget-recent-contacts', 'widget-recent-realisations',
    'widget-crm-devis', 'widget-crm-overview',
    'widget-shortcuts', 'widget-sysinfo', 'widget-logins',
  ];
  // Défauts serveur (réglages → Dashboard)
  const _serverDefaults = <?= $jsBlockDefaults ?? '{}' ?>;

  function getDefault(id) {
    return _serverDefaults[id] !== false; // true si non défini ou true
  }

  function loadPrefs() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; }
    catch(e) { return {}; }
  }
  function savePrefs(prefs) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
  }

  function isVisible(prefs, id) {
    return (id in prefs) ? !!prefs[id] : getDefault(id);
  }

  function applyVisibility(prefs) {
    allBlocks.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.style.display = isVisible(prefs, id) ? '' : 'none';
    });
  }

  function syncToggles(prefs) {
    document.querySelectorAll('.db-toggle').forEach(cb => {
      const id = cb.dataset.block;
      cb.checked = isVisible(prefs, id);
    });
  }

  // Init immédiat (avant DOMContentLoaded pour éviter le flash de contenu)
  const prefs = loadPrefs();
  applyVisibility(prefs);

  document.addEventListener('DOMContentLoaded', function(){
    const panel    = document.getElementById('db-panel');
    const overlay  = document.getElementById('db-overlay');
    const btnOpen  = document.getElementById('db-panel-toggle');
    const btnClose = document.getElementById('db-panel-close');
    const btnReset = document.getElementById('db-panel-reset');

    const prefs = loadPrefs();
    syncToggles(prefs);
    applyVisibility(prefs);

    // ── Onglet Widgets : visible uniquement sur le dashboard ─────────────────
    const isDashboard = !!document.getElementById('db-col-left');
    const tabWidgetsBtn  = document.querySelector('.db-tab-btn[data-tab="db-tab-widgets"]');
    const tabWidgetsPane = document.getElementById('db-tab-widgets');
    const tabDisplayBtn  = document.querySelector('.db-tab-btn[data-tab="db-tab-display"]');
    const tabDisplayPane = document.getElementById('db-tab-display');
    if (!isDashboard) {
      if (tabWidgetsBtn)  { tabWidgetsBtn.style.display  = 'none'; tabWidgetsBtn.classList.remove('active'); }
      if (tabWidgetsPane) { tabWidgetsPane.style.display = 'none'; }
      if (tabDisplayBtn)  { tabDisplayBtn.classList.add('active'); }
      if (tabDisplayPane) { tabDisplayPane.style.display = ''; }
    }

    function openPanel()  { panel.classList.add('open'); overlay.classList.add('open'); }
    function closePanel() { panel.classList.remove('open'); overlay.classList.remove('open'); }

    btnOpen.addEventListener('click', openPanel);
    btnClose.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanel(); });

    // ── Onglets ──────────────────────────────────────────────────────────────
    document.querySelectorAll('.db-tab-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.db-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.db-tab-pane').forEach(p => p.style.display = 'none');
        this.classList.add('active');
        const target = document.getElementById(this.dataset.tab);
        if (target) target.style.display = '';
      });
    });

    // ── Mode dark/light ────────────────────────────────────────────────────
    function applyMode(mode) {
      if (mode === 'light') {
        document.body.classList.remove('dark-mode');
      } else {
        document.body.classList.add('dark-mode');
      }
      document.querySelectorAll('.db-mode-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.mode === mode);
      });
    }
    const savedMode = localStorage.getItem(MODE_KEY) || 'dark';
    applyMode(savedMode);
    document.querySelectorAll('.db-mode-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const mode = this.dataset.mode;
        localStorage.setItem(MODE_KEY, mode);
        applyMode(mode);
      });
    });

    // ── Sidebar state ─────────────────────────────────────────────────────────
    const sidebarChk = document.getElementById('db-sidebar-chk');
    function syncSidebarChk() {
      if (sidebarChk) sidebarChk.checked = !document.body.classList.contains('sidebar-collapse');
    }
    syncSidebarChk();
    if (sidebarChk) {
      sidebarChk.addEventListener('change', function() {
        const pushmenu = document.querySelector('[data-widget="pushmenu"]');
        if (pushmenu) pushmenu.click();
      });
    }
    // Sync le checkbox quand AdminLTE toggle la sidebar (clic sur l'icône hamburger)
    $(document.body).on('collapsed.lte.pushmenu shown.lte.pushmenu', function() {
      const isOpen = !document.body.classList.contains('sidebar-collapse');
      localStorage.setItem(SIDEBAR_KEY, isOpen ? 'open' : 'collapsed');
      syncSidebarChk();
    });

    // ── KPI footer toggle ─────────────────────────────────────────────────
    document.querySelectorAll('.db-kpi-footer').forEach(btn => {
      // Auto-open if any kpi pref is set
      const kpiIds = ['kpi-contacts-new','kpi-contacts-month','kpi-realisations',
                      'kpi-forms','kpi-cms','kpi-crm-clients','kpi-crm-ca','kpi-crm-pending'];
      const sub = btn.nextElementSibling;
      if (kpiIds.some(id => id in prefs)) {
        btn.setAttribute('aria-expanded', 'false');
        if (sub) sub.style.display = 'none';
      }
      btn.addEventListener('click', function() {
        const isOpen = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', String(!isOpen));
        if (sub) sub.style.display = isOpen ? 'none' : 'block';
      });
    });

    document.querySelectorAll('.db-toggle').forEach(cb => {
      cb.addEventListener('change', function(){
        const id  = this.dataset.block;
        const cur = loadPrefs();
        cur[id]   = this.checked;
        savePrefs(cur);
        applyVisibility(cur);
      });
    });

    // Réinitialiser = supprimer les préférences locales → retour aux défauts serveur
    btnReset.addEventListener('click', function(){
      localStorage.removeItem(STORAGE_KEY);
      localStorage.removeItem(SORT_KEY);
      localStorage.removeItem(MODE_KEY);
      localStorage.removeItem(SIDEBAR_KEY);
      syncToggles({});
      applyVisibility({});
      location.reload();
    });

    // ── Sortable 2 colonnes ──────────────────────────────────────────────────────
    const colLeft  = document.getElementById('db-col-left');
    const colRight = document.getElementById('db-col-right');
    if (colLeft && colRight && typeof Sortable !== 'undefined') {
      function saveOrder() {
        const left  = Array.from(colLeft.querySelectorAll(':scope > .db-widget')).map(e => e.id);
        const right = Array.from(colRight.querySelectorAll(':scope > .db-widget')).map(e => e.id);
        localStorage.setItem(SORT_KEY, JSON.stringify({left, right}));
      }
      function restoreOrder() {
        try {
          const saved = JSON.parse(localStorage.getItem(SORT_KEY));
          if (!saved) return;
          (saved.left  || []).forEach(id => { const el = document.getElementById(id); if (el) colLeft.appendChild(el); });
          (saved.right || []).forEach(id => { const el = document.getElementById(id); if (el) colRight.appendChild(el); });
        } catch(e) {}
      }
      restoreOrder();
      const opts = { group: 'dashboard', handle: '.db-drag-handle', animation: 150,
                     ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen', dragClass: 'sortable-drag',
                     onEnd: saveOrder };
      Sortable.create(colLeft,  opts);
      Sortable.create(colRight, opts);
    }
  });
})();
</script>


</body>
</html>