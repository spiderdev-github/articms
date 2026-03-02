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

// Confirmation d'import en attente (conflit de configuration)
$importConfirmToken = $_GET['import_confirm'] ?? '';
$importPending = $_SESSION['import_pending'] ?? null;
$showImportConfirm  = (
    $importConfirmToken !== ''
    && $importPending !== null
    && ($importPending['token'] ?? '') === $importConfirmToken
    && ($importPending['expires'] ?? 0) > time()
);

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
        <a class="nav-link active" href="backup.php">
          <i class="fas fa-archive mr-1"></i> Sauvegarde
        </a>
      </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-dark p-4" style="max-width:908px">

      <!-- ═══════════════ TAB SAUVEGARDE ═══════════════ -->

      <?php if ($showImportConfirm && $importPending): ?>
      <?php
        $diffLabels = [
            'BASE_URL' => 'URL de base du site',
            'DB_HOST'  => 'Hôte BDD',
            'DB_PORT'  => 'Port BDD',
            'DB_NAME'  => 'Nom de la BDD',
            'DB_USER'  => 'Utilisateur BDD',
            'DB_PASS'  => 'Mot de passe BDD',
        ];
        $diffs = $importPending['diffs'] ?? [];
      ?>
      <div class="card border-danger mb-4" style="background:#1a1d23">
        <div class="card-header d-flex align-items-center" style="background:#3b1010;border-color:#dc3545">
          <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
          <strong class="text-danger">Confirmation requise — Conflit de configuration</strong>
        </div>
        <div class="card-body">
          <div class="alert alert-danger mb-3">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <strong>Attention :</strong> la configuration de la sauvegarde diffère de la configuration actuelle.
            Si vous restaurez ce fichier, les paramètres de base de données et/ou l'URL du site seront modifiés.
            <strong>Le site risque de ne plus fonctionner correctement</strong> si cette instance utilise
            une BDD ou une URL différente de celle de l'archive.
          </div>

          <p class="text-muted mb-2" style="font-size:.875rem">Différences détectées :</p>
          <div class="table-responsive mb-4">
            <table class="table table-sm table-dark table-bordered" style="font-size:.82rem">
              <thead style="background:#23272f">
                <tr>
                  <th>Paramètre</th>
                  <th>Valeur actuelle (ce serveur)</th>
                  <th>Valeur dans la sauvegarde</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($diffs as $key => $vals): ?>
                <?php
                  $isSensitive = ($key === 'DB_PASS');
                  $cur = $isSensitive ? (strlen($vals['current']) ? '••••••••' : '<em class="text-muted">(vide)</em>') : htmlspecialchars($vals['current'] ?: '(vide)');
                  $bak = $isSensitive ? (strlen($vals['backup'])  ? '••••••••' : '<em class="text-muted">(vide)</em>') : htmlspecialchars($vals['backup']  ?: '(vide)');
                ?>
                <tr>
                  <td class="text-warning font-weight-bold"><?= htmlspecialchars($diffLabels[$key] ?? $key) ?></td>
                  <td class="text-info"><?= $cur ?></td>
                  <td class="text-danger"><?= $bak ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <p class="text-muted mb-3" style="font-size:.82rem">
            <i class="fas fa-info-circle mr-1"></i>
            Confirmez uniquement si vous restaurez sur le <strong>même serveur</strong> que celui d'origine,
            ou si vous avez mis à jour manuellement la configuration après restauration.
          </p>

          <div class="d-flex gap-2">
            <form method="POST" action="actions/backup-import-confirm.php" class="mr-2">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="import_token" value="<?= htmlspecialchars($importPending['token']) ?>">
              <button type="submit" class="btn btn-danger"
                      onclick="return confirm('Confirmer malgré les différences de configuration ? Le site pourrait ne plus fonctionner.')">
                <i class="fas fa-check mr-1"></i> Confirmer quand même
              </button>
            </form>
            <form method="POST" action="actions/backup-import-confirm.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="import_token" value="<?= htmlspecialchars($importPending['token']) ?>">
              <input type="hidden" name="cancel_import" value="1">
              <button type="submit" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i> Annuler l'import
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>

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
      <div class="card bg-darker border-secondary mb-4">
        <div class="card-header d-flex align-items-center" >
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
      <div class="card bg-darker border-secondary">
        <div class="card-header d-flex align-items-center" style="">
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


    </div><!-- /.tab-content -->
  </div>
</div>

  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
