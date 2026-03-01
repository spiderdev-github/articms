<?php
require_once __DIR__ . '/auth.php';
requirePermission('settings');
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/config.php';

$csrf      = getCsrfToken();
$updated   = isset($_GET['updated']);
$notice    = $_GET['notice'] ?? '';
$activeTab = $_GET['tab'] ?? 'company';
$allowedTabs = ['company', 'smtp', 'recaptcha', 'robots', 'sitemap', 'dashboard', 'backup'];
if (!in_array($activeTab, $allowedTabs)) $activeTab = 'company';

// Dashboard settings
$dashSettings = [
    'dash_block_kpi'            => (int)getSetting('dash_block_kpi', 1),
    'dash_block_charts'         => (int)getSetting('dash_block_charts', 1),
    'dash_block_recent'         => (int)getSetting('dash_block_recent', 1),
    'dash_block_crm'            => (int)getSetting('dash_block_crm', 1),
    'dash_block_bottom'         => (int)getSetting('dash_block_bottom', 1),
    'dash_kpi_contacts_new'     => (int)getSetting('dash_kpi_contacts_new', 1),
    'dash_kpi_contacts_month'   => (int)getSetting('dash_kpi_contacts_month', 1),
    'dash_kpi_realisations'     => (int)getSetting('dash_kpi_realisations', 1),
    'dash_kpi_forms'            => (int)getSetting('dash_kpi_forms', 1),
    'dash_kpi_cms'              => (int)getSetting('dash_kpi_cms', 1),
    'dash_kpi_crm_clients'      => (int)getSetting('dash_kpi_crm_clients', 1),
    'dash_kpi_crm_ca'           => (int)getSetting('dash_kpi_crm_ca', 1),
    'dash_kpi_crm_pending'      => (int)getSetting('dash_kpi_crm_pending', 1),
];

// Robots.txt path
$robotsPath    = __DIR__ . '/../robots.txt';
$robotsContent = file_exists($robotsPath) ? file_get_contents($robotsPath) : '';

// Sitemap
$sitemapPath    = __DIR__ . '/../sitemap.xml';
$sitemapDomain  = getSetting('sitemap_domain', 'https://joker-peintre.fr');
$sitemapFreq    = getSetting('sitemap_changefreq', 'monthly');
// Count of published CMS pages for preview info
try {
    $sitemapPageCount = getPDO()->query("SELECT COUNT(*) FROM cms_pages WHERE is_published=1")->fetchColumn();
} catch (Throwable $e) { $sitemapPageCount = 0; }

include __DIR__ . '/partials/header.php';
?>

<?php if ($updated): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle mr-1"></i> Réglages enregistrés avec succès.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($notice === 'csrf'): ?>
<div class="alert alert-danger"><i class="fas fa-shield-alt mr-1"></i> Erreur de sécurité CSRF.</div>
<?php endif; ?>

<div class="row">
  <div class="col-12">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-0" id="settingsTabs">
       <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'dashboard' ? 'active' : '' ?>"
           href="settings.php?tab=dashboard">
          <i class="fas fa-th-large mr-1"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'company' ? 'active' : '' ?>"
           href="settings.php?tab=company">
          <i class="fas fa-building mr-1"></i> Entreprise
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'smtp' ? 'active' : '' ?>"
           href="settings.php?tab=smtp">
          <i class="fas fa-envelope mr-1"></i> SMTP
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'recaptcha' ? 'active' : '' ?>"
           href="settings.php?tab=recaptcha">
          <i class="fas fa-shield-alt mr-1"></i> reCAPTCHA
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'robots' ? 'active' : '' ?>"
           href="settings.php?tab=robots">
          <i class="fas fa-robot mr-1"></i> robots.txt
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'sitemap' ? 'active' : '' ?>"
           href="settings.php?tab=sitemap">
          <i class="fas fa-sitemap mr-1"></i> Sitemap
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'backup' ? 'active' : '' ?>"
           href="settings.php?tab=backup">
          <i class="fas fa-archive mr-1"></i> Sauvegarde
        </a>
      </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-dark p-4" style="max-width:908px">

      <!-- ═══════════════ TAB ENTREPRISE ═══════════════ -->
      <?php if ($activeTab === 'company'): ?>
      <form method="POST" action="actions/cms-save.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="section" value="company">

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Nom de l'entreprise</label>
            <input class="form-control" name="company_name" value="<?= gs('company_name', 'Joker Peintre') ?>">
          </div>
          <div class="form-group col-md-6">
            <label>Région</label>
            <input class="form-control" name="company_region" value="<?= gs('company_region', 'Alsace') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Téléphone <small class="text-muted">(format E.164 : +33…)</small></label>
            <input class="form-control" name="company_phone" placeholder="+33783868622"
                   value="<?= gs('company_phone', PHONE) ?>">
          </div>
          <div class="form-group col-md-6">
            <label>Téléphone affiché <small class="text-muted">(ex : 07 83 86 86 22)</small></label>
            <input class="form-control" name="company_phone_display"
                   value="<?= gs('company_phone_display', PHONE_DISPLAY) ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" class="form-control" name="company_email"
                 value="<?= gs('company_email', EMAIL) ?>">
        </div>

        <div class="form-group">
          <label>Adresse <small class="text-muted">(N° et rue)</small></label>
          <input class="form-control" name="company_address" value="<?= gs('company_address') ?>">
        </div>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Code postal</label>
            <input class="form-control" name="company_zip" value="<?= gs('company_zip') ?>">
          </div>
          <div class="form-group col-md-9">
            <label>Ville</label>
            <input class="form-control" name="company_city" value="<?= gs('company_city') ?>">
          </div>
        </div>

        <div class="form-group">
          <label>SIRET</label>
          <input class="form-control" name="company_siret" value="<?= gs('company_siret') ?>">
        </div>

        <hr>
        <h5 class="mb-3"><i class="fas fa-align-left mr-1 text-muted"></i> Footer</h5>

        <div class="form-group">
          <label>Tagline <small class="text-muted">(ex : Société de bâtiment – Peinture & Décoration)</small></label>
          <input class="form-control" name="footer_tagline"
                 value="<?= gs('footer_tagline', 'Société de bâtiment – Peinture & Décoration') ?>">
        </div>

        <div class="form-group">
          <label>Zone d'intervention footer</label>
          <input class="form-control" name="footer_zone"
                 value="<?= gs('footer_zone', 'Intervention en Alsace') ?>">
        </div>

        <div class="alert alert-warning mt-2">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          Ces informations remplacent les constantes de <code>config.php</code> sur les pages publiques.
        </div>

        <button class="btn btn-primary btn-lg">
          <i class="fas fa-save mr-1"></i> Enregistrer
        </button>
      </form>

      <?php elseif ($activeTab === 'smtp'): ?>
      <form method="POST" action="actions/cms-save.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="section" value="smtp">

        <div class="form-row">
          <div class="form-group col-md-8">
            <label>Serveur SMTP <small class="text-muted">(ex : smtp.ionos.fr)</small></label>
            <input class="form-control" name="smtp_host"
                   value="<?= gs('smtp_host', SMTP_HOST) ?>">
          </div>
          <div class="form-group col-md-4">
            <label>Port <small class="text-muted">(587 STARTTLS / 465 SSL)</small></label>
            <input type="number" class="form-control" name="smtp_port"
                   value="<?= gs('smtp_port', SMTP_PORT) ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Nom d'utilisateur SMTP</label>
            <input class="form-control" name="smtp_user"
                   value="<?= gs('smtp_user', SMTP_USER) ?>" autocomplete="off">
          </div>
          <div class="form-group col-md-6">
            <label>Mot de passe SMTP</label>
            <input type="password" class="form-control" name="smtp_pass"
                   placeholder="<?= gs('smtp_pass') ? '••••••••' : 'Non défini' ?>"
                   autocomplete="new-password">
            <small class="text-muted">Laisser vide pour ne pas modifier le mot de passe.</small>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Adresse expéditeur <small class="text-muted">(From)</small></label>
            <input type="email" class="form-control" name="smtp_from"
                   value="<?= gs('smtp_from', SMTP_FROM) ?>">
          </div>
          <div class="form-group col-md-6">
            <label>Nom expéditeur</label>
            <input class="form-control" name="smtp_from_name"
                   value="<?= gs('smtp_from_name', SMTP_FROM_NAME) ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Email de réception des contacts</label>
          <input type="email" class="form-control" name="smtp_contact_email"
                 value="<?= gs('smtp_contact_email', CONTACT_EMAIL) ?>">
          <small class="text-muted">Les formulaires de contact sont envoyés à cette adresse.</small>
        </div>

        <div class="alert alert-info mt-2 mb-3">
          <i class="fas fa-info-circle mr-1"></i>
          Ces valeurs remplacent celles de <code>includes/config.php</code> pour l'envoi des emails.
        </div>

        <button class="btn btn-warning btn-lg">
          <i class="fas fa-save mr-1"></i> Enregistrer
        </button>
      </form>

      <!-- ═══════════════ TAB RECAPTCHA ═══════════════ -->
      <?php elseif ($activeTab === 'recaptcha'): ?>

      <div class="alert alert-info">
        <strong><i class="fas fa-info-circle mr-1"></i> Pas encore de compte reCAPTCHA ?</strong><br>
        <ol class="mb-0 mt-2 pl-3">
          <li>Aller sur <a href="https://www.google.com/recaptcha/admin/create" target="_blank">google.com/recaptcha/admin/create</a></li>
          <li>Choisir <strong>reCAPTCHA v3</strong></li>
          <li>Ajouter votre domaine (<code><?= str_replace(["http://", "https://"], "", BASE_URL) ?></code>)</li>
          <li>Copier la <strong>clé de site</strong> et la <strong>clé secrète</strong> ci-dessous</li>
        </ol>
      </div>

      <form method="POST" action="actions/cms-save.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="section" value="recaptcha">

        <div class="form-group">
          <label>Clé de site <small class="text-muted">(Site Key – côté public, visible dans le HTML)</small></label>
          <input class="form-control font-monospace" name="captcha_site_key"
                 placeholder="6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                 value="<?= gs('captcha_site_key', CAPTCHA_SITE_KEY) ?>">
        </div>

        <div class="form-group">
          <label>Clé secrète <small class="text-muted">(Secret Key – côté serveur uniquement)</small></label>
          <input class="form-control font-monospace" name="captcha_secret_key"
                 placeholder="6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                 value="<?= gs('captcha_secret_key', CAPTCHA_SECRET_KEY) ?>">
        </div>

        <div class="form-group" style="max-width:220px">
          <label>Score minimum acceptable
            <small class="text-muted">(0.0 = tout accepter · 1.0 = très strict)</small>
          </label>
          <input type="number" step="0.1" min="0" max="1" class="form-control" name="captcha_min_score"
                 value="<?= gs('captcha_min_score', CAPTCHA_MIN_SCORE) ?>">
          <small class="text-muted">Recommandé : <strong>0.5</strong></small>
        </div>

        <div class="alert alert-warning mt-2">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          Ces valeurs remplacent celles de <code>includes/config.php</code>.
          Mettre la clé de site à vide désactive reCAPTCHA (score ignoré, tous les formulaires passés).
        </div>

        <button class="btn btn-primary btn-lg">
          <i class="fas fa-save mr-1"></i> Enregistrer
        </button>
      </form>

      <?php endif; ?>

      <!-- ═══════════════ TAB ROBOTS.TXT ═══════════════ -->
      <?php if ($activeTab === 'robots'): ?>
      <form method="POST" action="actions/cms-save.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="section" value="robots">

        <div class="form-group">
          <label class="font-weight-bold">Contenu de <code>robots.txt</code></label>
          <small class="form-text text-muted mb-2">
            Fichier servi à l'URL <a href="<?= BASE_URL ?>/robots.txt" target="_blank" class="text-info"><?= BASE_URL ?>/robots.txt</a>
          </small>
          <?php if (!is_writable($robotsPath)): ?>
          <div class="alert alert-danger py-2">
            <i class="fas fa-lock mr-1"></i> Le fichier <code>robots.txt</code> n'est pas accessible en écriture par le serveur web.
          </div>
          <?php endif; ?>

          <div class="mb-2 d-flex" style="gap:6px;">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="setRobotsPreset('disallow-all')"
                    title="Bloque tous les robots (désindexation complète)">
              <i class="fas fa-ban mr-1"></i> Disallow all
            </button>
            <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="setRobotsPreset('allow-all')"
                    title="Autorise tous les robots">
              <i class="fas fa-check mr-1"></i> Allow all
            </button>
          </div>

          <textarea id="robotsTextarea" name="robots_content" class="form-control font-monospace"
                    rows="16" style="font-size:13px;line-height:1.6;background:#1a1d23;color:#e0e4ef;border-color:#3a3d4a;"
                    spellcheck="false"><?= htmlspecialchars($robotsContent) ?></textarea>
        </div>

        <script>
        const ROBOTS_PRESETS = {
          'disallow-all': "User-agent: *\nDisallow: /\n",
          'allow-all':    "User-agent: *\nAllow: /\nDisallow: /admin/\n\nSitemap: https://joker-peintre.fr/sitemap.xml\n"
        };
        function setRobotsPreset(key) {
          if (!confirm('Remplacer le contenu actuel par ce préréglage ?')) return;
          document.getElementById('robotsTextarea').value = ROBOTS_PRESETS[key];
        }
        </script>

        <div class="alert alert-info py-2" style="font-size:12px;">
          <i class="fas fa-info-circle mr-1"></i>
          Exemples : <code>Disallow: /admin/</code> &middot; <code>Disallow: /includes/</code> &middot; <code>Sitemap: https://joker-peintre.fr/sitemap.xml</code>
        </div>

        <div class="d-flex" style="gap:8px;">
          <button class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Enregistrer
          </button>
          <a href="<?= BASE_URL ?>/robots.txt" target="_blank" class="btn btn-secondary">
            <i class="fas fa-external-link-alt mr-1"></i> Voir le fichier
          </a>
        </div>
      </form>
      <?php endif; ?>

      <!-- ═══════════════ TAB SITEMAP ═══════════════ -->
      <?php if ($activeTab === 'sitemap'): ?>
      <form method="POST" action="actions/cms-save.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="section" value="sitemap">

        <div class="row">
          <div class="col-md-7">
            <div class="form-group">
              <label class="font-weight-bold">Domaine du site <span class="text-danger">*</span></label>
              <small class="form-text text-muted mb-1">URL de base utilisée dans chaque &lt;loc&gt; du sitemap.</small>
              <input type="url" name="sitemap_domain" class="form-control"
                     value="<?= htmlspecialchars($sitemapDomain) ?>"
                     placeholder="https://joker-peintre.fr" required>
            </div>
          </div>
          <div class="col-md-5">
            <div class="form-group">
              <label class="font-weight-bold">Fréquence de mise à jour</label>
              <small class="form-text text-muted mb-1">Utilisée dans chaque &lt;changefreq&gt; du sitemap.</small>
              <select name="sitemap_changefreq" class="form-control">
                <?php foreach (['always','hourly','daily','weekly','monthly','yearly','never'] as $f): ?>
                <option value="<?= $f ?>" <?= $sitemapFreq === $f ? 'selected' : '' ?>><?= ucfirst($f) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="alert alert-secondary py-2 mb-3" style="font-size:12px;">
          <i class="fas fa-info-circle mr-1"></i>
          Le sitemap généré inclura automatiquement :
          <strong>Accueil</strong> (priorité 1.0) +
          <strong><?= (int)$sitemapPageCount ?> page(s) CMS publiée(s)</strong> +
          pages statiques (contact, info).
          Chemin : <code>sitemap.xml</code>
          <?php if (file_exists($sitemapPath) && filesize($sitemapPath) > 0): ?>
          &mdash; <a href="<?= BASE_URL ?>/sitemap.xml" target="_blank" class="text-info">Voir le fichier actuel</a>
          <?php endif; ?>
        </div>

        <div class="d-flex" style="gap:8px;">
          <button class="btn btn-primary">
            <i class="fas fa-cogs mr-1"></i> Enregistrer &amp; Générer sitemap.xml
          </button>
          <?php if (file_exists($sitemapPath) && filesize($sitemapPath) > 0): ?>
          <a href="<?= BASE_URL ?>/sitemap.xml" target="_blank" class="btn btn-secondary">
            <i class="fas fa-external-link-alt mr-1"></i> Voir le sitemap
          </a>
          <?php endif; ?>
        </div>

        <?php if ($updated && $activeTab === 'sitemap' && file_exists($sitemapPath) && filesize($sitemapPath) > 0): ?>
        <div class="mt-4">
          <label class="font-weight-bold">Aperçu du sitemap généré</label>
          <pre class="p-3 rounded" style="font-size:11px;max-height:320px;overflow:auto;background:#1a1d23;color:#a9b7d0;border:1px solid #3a3d4a;"><?= htmlspecialchars(file_get_contents($sitemapPath)) ?></pre>
        </div>
        <?php endif; ?>
      </form>

      <!-- ═════════════════ TAB DASHBOARD ═════════════════ -->
      <?php elseif ($activeTab === 'dashboard'): ?>
      <form method="POST" action="actions/cms-save.php">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="section" value="dashboard">

        <h5 class="mb-3"><i class="fas fa-cubes mr-2 text-primary"></i>Blocs affichés par défaut</h5>
        <p class="text-muted mb-3" style="font-size:.85rem;">Ces réglages définissent la visibilité par défaut pour <strong>tous les administrateurs</strong>. Chaque utilisateur peut ensuite personnaliser sa vue via le panel «&nbsp;Affichage&nbsp;» du tableau de bord.</p>

        <?php
        $blockRows = [
          ['key'=>'dash_block_kpi',    'icon'=>'fas fa-chart-bar',    'color'=>'#dc3545', 'label'=>'KPI — Métriques clés'],
          ['key'=>'dash_block_charts', 'icon'=>'fas fa-chart-line',   'color'=>'#007bff', 'label'=>'Graphiques — Contacts 12 mois'],
          ['key'=>'dash_block_recent', 'icon'=>'fas fa-list',         'color'=>'#ffc107', 'label'=>'Derniers contacts &amp; réalisations'],
          ['key'=>'dash_block_crm',    'icon'=>'fas fa-file-invoice', 'color'=>'#0d6efd', 'label'=>'CRM — Devis &amp; Factures'],
          ['key'=>'dash_block_bottom', 'icon'=>'fas fa-bolt',         'color'=>'#6c757d', 'label'=>'Raccourcis &amp; Infos système'],
        ];
        foreach ($blockRows as $row): ?>
        <div class="form-group d-flex align-items-center justify-content-between py-2 border-bottom" style="border-color:rgba(255,255,255,.08)!important">
          <div class="d-flex align-items-center">
            <span style="width:28px;height:28px;background:<?= $row['color'] ?>;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;">
              <i class="<?= $row['icon'] ?>"></i>
            </span>
            <span class="ml-2"><?= $row['label'] ?></span>
          </div>
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="<?= $row['key'] ?>"
                   name="<?= $row['key'] ?>" value="1"
                   <?= $dashSettings[$row['key']] ? 'checked' : '' ?>>
            <label class="custom-control-label" for="<?= $row['key'] ?>"></label>
          </div>
        </div>
        <?php endforeach; ?>

        <h5 class="mt-4 mb-3"><i class="fas fa-th-large mr-2 text-warning"></i>Cartes KPI — Visibilité individuelle</h5>
        <p class="text-muted mb-3" style="font-size:.85rem;">Choisissez quelles métriques afficher dans la rangée de cartes en haut du tableau de bord.</p>

        <?php
        $kpiRows = [
          ['key'=>'dash_kpi_contacts_new',   'color'=>'#dc3545', 'icon'=>'fas fa-envelope-open-text', 'label'=>'Contacts nouveaux'],
          ['key'=>'dash_kpi_contacts_month', 'color'=>'#17a2b8', 'icon'=>'fas fa-calendar-alt',       'label'=>'Contacts ce mois'],
          ['key'=>'dash_kpi_realisations',   'color'=>'#28a745', 'icon'=>'fas fa-paint-roller',       'label'=>'Réalisations publiées'],
          ['key'=>'dash_kpi_forms',          'color'=>'#ffc107', 'icon'=>'fas fa-paper-plane',        'label'=>'Soumissions non lues'],
          ['key'=>'dash_kpi_cms',            'color'=>'#6f42c1', 'icon'=>'fas fa-file-alt',           'label'=>'Pages CMS'],
          ['key'=>'dash_kpi_crm_clients',    'color'=>'#0d6efd', 'icon'=>'fas fa-address-book',       'label'=>'Clients CRM'],
          ['key'=>'dash_kpi_crm_ca',         'color'=>'#20c997', 'icon'=>'fas fa-euro-sign',          'label'=>'CA TTC accepté'],
          ['key'=>'dash_kpi_crm_pending',    'color'=>'#fd7e14', 'icon'=>'fas fa-hourglass-half',     'label'=>'Devis en attente'],
        ];
        foreach ($kpiRows as $row): ?>
        <div class="form-group d-flex align-items-center justify-content-between py-2 border-bottom" style="border-color:rgba(255,255,255,.08)!important">
          <div class="d-flex align-items-center">
            <span style="width:28px;height:28px;background:<?= $row['color'] ?>;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;">
              <i class="<?= $row['icon'] ?>"></i>
            </span>
            <span class="ml-2"><?= $row['label'] ?></span>
          </div>
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="<?= $row['key'] ?>"
                   name="<?= $row['key'] ?>" value="1"
                   <?= $dashSettings[$row['key']] ? 'checked' : '' ?>>
            <label class="custom-control-label" for="<?= $row['key'] ?>"></label>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i>Enregistrer les réglages
          </button>
          <small class="text-muted ml-3">Les utilisateurs ayant une préférence locale conservent leur choix personnel.</small>
        </div>
      </form>
      <?php endif; ?>

      <!-- ═══════════════ TAB SAUVEGARDE ═══════════════ -->
      <?php if ($activeTab === 'backup'): ?>

      <?php
        $pharOk    = class_exists('PharData');
        $pharWrite = $pharOk && !(bool)ini_get('phar.readonly');
      ?>

      <?php if (!$pharOk): ?>
      <div class="alert alert-danger">
        <i class="fas fa-times-circle mr-1"></i>
        <strong>Phar non disponible.</strong> L'extension PHP <code>Phar</code> est absente sur ce serveur.
        Contactez votre hébergeur.
      </div>
      <?php elseif (!$pharWrite): ?>
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>phar.readonly activé.</strong> Ajoutez <code>phar.readonly = Off</code> dans votre <code>php.ini</code>
        pour activer l'export.
      </div>
      <?php endif; ?>

      <!-- ─── EXPORT ─── -->
      <div class="card bg-darker border-secondary mb-4" style="background:#1a1d23">
        <div class="card-header d-flex align-items-center" style="background:#23272f;border-color:#3a3d4a">
          <i class="fas fa-download mr-2 text-primary"></i>
          <strong>Exporter une sauvegarde</strong>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3" style="font-size:.875rem;">
            Génère une archive <code>.tar.gz</code> contenant l'ensemble de vos données. Conservez-la
            précieusement pour pouvoir migrer ou restaurer votre site sur un autre serveur.
          </p>

          <div class="row mb-3" style="font-size:.82rem">
            <div class="col-sm-6">
              <ul class="list-unstyled text-muted mb-0">
                <li><i class="fas fa-database mr-1 text-info"></i> <code>database.sql</code> — dump complet de la BDD</li>
                <li><i class="fas fa-cog mr-1 text-warning"></i> <code>config.php</code> — configuration du CMS</li>
                <li><i class="fas fa-images mr-1 text-success"></i> <code>uploads/</code> — médias uploadés</li>
              </ul>
            </div>
            <div class="col-sm-6">
              <ul class="list-unstyled text-muted mb-0">
                <li><i class="fas fa-photo-video mr-1 text-danger"></i> <code>assets/images/</code> — images du site</li>
                <li><i class="fas fa-paint-brush mr-1 text-primary"></i> <code>themes/</code> — tous les thèmes</li>
                <li><i class="fas fa-robot mr-1 text-secondary"></i> <code>robots.txt</code> &amp; <code>sitemap.xml</code></li>
                <li><i class="fas fa-info-circle mr-1 text-muted"></i> <code>manifest.json</code> — métadonnées</li>
              </ul>
            </div>
          </div>

          <div class="alert alert-secondary py-2" style="font-size:.8rem">
            <i class="fas fa-info-circle mr-1"></i>
            La taille de l'archive dépend du volume de vos médias.
            L'opération peut prendre quelques secondes. Ne fermez pas la page.
          </div>

          <form method="POST" action="actions/backup-export.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <button type="submit" class="btn btn-primary" <?= !$pharWrite ? 'disabled' : '' ?>>
              <i class="fas fa-download mr-1"></i> Télécharger la sauvegarde (.tar.gz)
            </button>
          </form>
        </div>
      </div>

      <!-- ─── IMPORT ─── -->
      <div class="card bg-darker border-secondary" style="background:#1a1d23">
        <div class="card-header d-flex align-items-center" style="background:#23272f;border-color:#3a3d4a">
          <i class="fas fa-upload mr-2 text-warning"></i>
          <strong>Importer une sauvegarde</strong>
        </div>
        <div class="card-body">
          <div class="alert alert-warning mb-3">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Attention :</strong> la restauration <strong>écrase</strong> les données existantes
            pour les éléments sélectionnés ci-dessous. Cette opération est irréversible.
            Assurez-vous de l'avoir fait sur une instance ArtiCMS fraîchement installée
            ou d'avoir fait une sauvegarde préalable.
          </div>

          <form method="POST" action="actions/backup-import.php"
                enctype="multipart/form-data" id="importForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <!-- Upload -->
            <div class="form-group">
              <label class="font-weight-bold">Archive à importer <span class="text-danger">*</span></label>
              <small class="form-text text-muted mb-2">Sélectionnez un fichier <code>.tar.gz</code> exporté par ArtiCMS.</small>
              <div id="dropZone" class="border border-secondary rounded p-4 text-center"
                   style="cursor:pointer;border-style:dashed!important;transition:border-color .2s"
                   onclick="document.getElementById('backup_file').click()"
                   ondragover="event.preventDefault();this.style.borderColor='#4e9af1'"
                   ondragleave="this.style.borderColor=''"
                   ondrop="handleDrop(event)">
                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                <p class="mb-0 text-muted" id="dropLabel">Glissez-déposez ici ou <u>cliquez pour sélectionner</u></p>
              </div>
              <input type="file" name="backup_file" id="backup_file" accept=".tar.gz,application/gzip"
                     class="d-none" onchange="updateDropLabel(this)" required>
            </div>

            <!-- Options -->
            <div class="form-group">
              <label class="font-weight-bold">Éléments à restaurer</label>
              <small class="form-text text-muted mb-2">
                Cochez uniquement ce que vous souhaitez écraser. Laissez décoché pour conserver l'existant.
              </small>
              <div class="row">
                <div class="col-sm-6">
                  <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="restore_db" name="restore_db" value="1" checked>
                    <label class="custom-control-label" for="restore_db">
                      <i class="fas fa-database mr-1 text-info"></i> Base de données
                      <small class="text-muted d-block" style="font-size:.75rem">Recrée toutes les tables et réimporte les données</small>
                    </label>
                  </div>
                  <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="restore_config" name="restore_config" value="1">
                    <label class="custom-control-label" for="restore_config">
                      <i class="fas fa-cog mr-1 text-warning"></i> Configuration
                      <small class="text-muted d-block" style="font-size:.75rem">Remplace includes/config.php, robots.txt, sitemap.xml</small>
                    </label>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="restore_uploads" name="restore_uploads" value="1" checked>
                    <label class="custom-control-label" for="restore_uploads">
                      <i class="fas fa-images mr-1 text-success"></i> Médias (uploads/)
                      <small class="text-muted d-block" style="font-size:.75rem">Copie tous les fichiers médias uploadés</small>
                    </label>
                  </div>
                  <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="restore_images" name="restore_images" value="1" checked>
                    <label class="custom-control-label" for="restore_images">
                      <i class="fas fa-photo-video mr-1 text-danger"></i> Images (assets/images/)
                      <small class="text-muted d-block" style="font-size:.75rem">Restaure les images statiques du site</small>
                    </label>
                  </div>
                  <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="restore_themes" name="restore_themes" value="1" checked>
                    <label class="custom-control-label" for="restore_themes">
                      <i class="fas fa-paint-brush mr-1 text-primary"></i> Thèmes
                      <small class="text-muted d-block" style="font-size:.75rem">Restaure les fichiers CSS des thèmes</small>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-warning"
                    onclick="return confirm('Confirmer la restauration ? Les données sélectionnées seront écrasées.')">
              <i class="fas fa-upload mr-1"></i> Importer et restaurer
            </button>
          </form>
        </div>
      </div>

      <script>
      function updateDropLabel(input) {
        const label = document.getElementById('dropLabel');
        if (input.files && input.files[0]) {
          label.innerHTML = '<i class="fas fa-file-archive mr-1 text-success"></i><strong>' +
            input.files[0].name + '</strong> (' +
            (input.files[0].size / 1024 / 1024).toFixed(2) + ' Mo)';
          document.getElementById('dropZone').style.borderColor = '#28a745';
        }
      }
      function handleDrop(e) {
        e.preventDefault();
        document.getElementById('dropZone').style.borderColor = '';
        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files[0]) {
          const inp = document.getElementById('backup_file');
          // Assign via DataTransfer
          const dts = new DataTransfer();
          dts.items.add(dt.files[0]);
          inp.files = dts.files;
          updateDropLabel(inp);
        }
      }
      </script>

      <?php endif; ?>

    </div><!-- /.tab-content -->
  </div>
</div>

  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
