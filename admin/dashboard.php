<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requirePermission('dashboard');

$pdo = getPDO();

// ─── KPI STATS ────────────────────────────────────────────────────────────────
$contactsTotal     = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$contactsNew       = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status = 'new'")->fetchColumn();
$contactsToday     = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$contactsMonth     = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$contactsTreated   = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status = 'treated'")->fetchColumn();

$realisationsTotal = (int)$pdo->query("SELECT COUNT(*) FROM realisations WHERE is_published = 1")->fetchColumn();
$realisationsDraft = (int)$pdo->query("SELECT COUNT(*) FROM realisations WHERE is_published = 0")->fetchColumn();
$galleriesTotal    = (int)$pdo->query("SELECT COUNT(*) FROM galleries")->fetchColumn();
$galleryItems      = (int)$pdo->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();
$cmsPages          = (int)$pdo->query("SELECT COUNT(*) FROM cms_pages WHERE is_published = 1")->fetchColumn();
$mediaTotal        = (int)$pdo->query("SELECT COUNT(*) FROM media_meta")->fetchColumn();
$submissionsUnread = (int)$pdo->query("SELECT COUNT(*) FROM form_submissions WHERE is_read = 0")->fetchColumn();
$adminsActive      = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE is_active = 1")->fetchColumn();

// ─── CRM STATS ────────────────────────────────────────────────────────────────
$crmEnabled = can('crm');
if ($crmEnabled) {
    $crmClients       = (int)$pdo->query("SELECT COUNT(*) FROM crm_clients")->fetchColumn();
    $crmDevisCount    = (int)$pdo->query("SELECT COUNT(*) FROM crm_devis WHERE type='devis'")->fetchColumn();
    $crmFacturesCount = (int)$pdo->query("SELECT COUNT(*) FROM crm_devis WHERE type='facture'")->fetchColumn();
    $crmPending       = (int)$pdo->query("SELECT COUNT(*) FROM crm_devis WHERE status='sent'")->fetchColumn();
    $crmCaTtc         = (float)$pdo->query("SELECT COALESCE(SUM(total_ttc),0) FROM crm_devis WHERE status IN ('accepted','invoiced','paid')")->fetchColumn();
    $recentDevis      = $pdo->query("
        SELECT d.id, d.ref, d.type, d.status, d.total_ttc, d.issued_at, d.created_at,
               c.name AS client_name
        FROM crm_devis d
        JOIN crm_clients c ON c.id = d.client_id
        ORDER BY d.created_at DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $crmClients = $crmDevisCount = $crmFacturesCount = $crmPending = 0;
    $crmCaTtc = 0.0;
    $recentDevis = [];
}

// ─── CONTACTS PAR MOIS (12 derniers mois) ─────────────────────────────────────
$monthRows = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS m,
           SUM(status = 'new') AS new_c,
           SUM(status = 'treated') AS treated_c
    FROM contacts
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 11 MONTH)
    GROUP BY m
    ORDER BY m ASC
")->fetchAll(PDO::FETCH_ASSOC);

$monthMap = [];
for ($i = 11; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i month"));
    $monthMap[$key] = ['new_c' => 0, 'treated_c' => 0];
}
foreach ($monthRows as $mr) {
    if (isset($monthMap[$mr['m']])) {
        $monthMap[$mr['m']] = ['new_c' => (int)$mr['new_c'], 'treated_c' => (int)$mr['treated_c']];
    }
}
$chartLabels  = [];
$chartNew     = [];
$chartTreated = [];
$monthNames   = ['01'=>'Jan','02'=>'Fév','03'=>'Mar','04'=>'Avr','05'=>'Mai','06'=>'Juin',
                 '07'=>'Juil','08'=>'Aoû','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Déc'];
foreach ($monthMap as $ym => $vals) {
    [$y, $mo] = explode('-', $ym);
    $chartLabels[]  = ($monthNames[$mo] ?? $mo) . " '" . substr($y, 2);
    $chartNew[]     = $vals['new_c'];
    $chartTreated[] = $vals['treated_c'];
}

// ─── CONTACTS PAR SERVICE (top 6) ─────────────────────────────────────────────
$serviceRows = $pdo->query("
    SELECT service, COUNT(*) AS c FROM contacts
    WHERE service IS NOT NULL AND service != ''
    GROUP BY service ORDER BY c DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
$serviceLabels = array_column($serviceRows, 'service');
$serviceValues = array_map('intval', array_column($serviceRows, 'c'));

// ─── RECENT CONTACTS ──────────────────────────────────────────────────────────
$recentContacts = $pdo->query("
    SELECT id, name, service, status, city, created_at
    FROM contacts ORDER BY created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ─── RECENT REALISATIONS ──────────────────────────────────────────────────────
$recentRealisations = $pdo->query("
    SELECT id, title, city, type, is_published, created_at
    FROM realisations ORDER BY created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ─── ACTIVE THEME ─────────────────────────────────────────────────────────────
$activeTheme = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'active_theme'")->fetchColumn();
$activeTheme = $activeTheme ?: 'default';

// ─── DASHBOARD DISPLAY SETTINGS ───────────────────────────────────────────────
$dbs = [];
$dbKeys = ['dash_block_kpi','dash_block_charts','dash_block_recent','dash_block_crm','dash_block_bottom',
           'dash_kpi_contacts_new','dash_kpi_contacts_month','dash_kpi_realisations',
           'dash_kpi_forms','dash_kpi_cms','dash_kpi_crm_clients','dash_kpi_crm_ca','dash_kpi_crm_pending'];
$dbRows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'dash_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($dbKeys as $k) {
    $dbs[$k] = isset($dbRows[$k]) ? (bool)(int)$dbRows[$k] : true; // default ON
}
// Block defaults as JS object (for localStorage fallback)
$jsBlockDefaults = json_encode([
    'block-kpi'    => $dbs['dash_block_kpi'],
    'block-charts' => $dbs['dash_block_charts'],
    'block-recent' => $dbs['dash_block_recent'],
    'block-crm'    => $dbs['dash_block_crm'],
    'block-bottom' => $dbs['dash_block_bottom'],
]);

$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/partials/header.php';
$lastLogins = $pdo->query("
    SELECT display_name, username, role, last_login, avatar
    FROM admins WHERE is_active = 1 AND last_login IS NOT NULL
    ORDER BY last_login DESC LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/partials/header.php';
?>
<div class="content-wrapper"  style="margin-left:0">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6 d-flex align-items-center">
          <h1 class="m-0">Tableau de bord</h1>
        </div>
        <div class="col-sm-6 d-flex align-items-center justify-content-end">
          <button id="db-panel-toggle" class="btn btn-sm btn-outline-secondary" title="Personnaliser l'affichage">
            <i class="fas fa-th-large mr-1"></i>Affichage
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="fas fa-check-circle mr-1"></i> Modifications enregistrées.
          <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
      <?php endif; ?>

      <!-- ── ROW 1 : KPI CARDS ─────────────────────────────────────────────── -->
      <div class="row db-block" id="block-kpi">

        <?php if (can('contacts')): ?>
        <?php if ($dbs['dash_kpi_contacts_new']): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box bg-danger">
            <div class="inner"><h3><?php echo $contactsNew; ?></h3><p>Contacts nouveaux</p></div>
            <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
            <a href="contacts.php" class="small-box-footer">Voir tous <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($dbs['dash_kpi_contacts_month']): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo $contactsMonth; ?></h3>
              <p style="display: block ruby">Ce mois <small class="text-dark">(<?php echo $contactsToday; ?> auj.)</small></p>
            </div>
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            <a href="contacts.php" class="small-box-footer">Voir tous <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (can('realisations') && $dbs['dash_kpi_realisations']): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo $realisationsTotal; ?></h3>
              <p>Réalisations publiées<?php if ($realisationsDraft): ?> <small>(+<?php echo $realisationsDraft; ?> brouillons)</small><?php endif; ?></p>
            </div>
            <div class="icon"><i class="fas fa-paint-roller"></i></div>
            <a href="realisations.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (can('forms') && $dbs['dash_kpi_forms']): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box <?php echo $submissionsUnread > 0 ? 'bg-warning' : 'bg-secondary'; ?>">
            <div class="inner"><h3><?php echo $submissionsUnread; ?></h3><p>Soumissions non lues</p></div>
            <div class="icon"><i class="fas fa-paper-plane"></i></div>
            <a href="forms.php" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (can('cms') && $dbs['dash_kpi_cms']): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box" style="background:#6f42c1;color:#fff;">
            <div class="inner"><h3><?php echo $cmsPages; ?></h3><p>Pages CMS publiées</p></div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
            <a href="cms-pages.php" class="small-box-footer" style="background:rgba(0,0,0,.15);">Gérer <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (can('crm')): ?>
        <?php if ($dbs['dash_kpi_crm_clients']): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box" style="background:#0d6efd;color:#fff;">
            <div class="inner"><h3><?php echo $crmClients; ?></h3><p>Clients CRM</p></div>
            <div class="icon"><i class="fas fa-address-book"></i></div>
            <a href="crm-clients.php" class="small-box-footer" style="background:rgba(0,0,0,.15);">Voir <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($dbs['dash_kpi_crm_ca']): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box" style="background:#20c997;color:#fff;">
            <div class="inner">
              <h3><?php echo number_format($crmCaTtc, 0, ',', ' '); ?> <sup style="font-size:.5em">€</sup></h3>
              <p>CA TTC accepté</p>
            </div>
            <div class="icon"><i class="fas fa-euro-sign"></i></div>
            <a href="crm-devis.php" class="small-box-footer" style="background:rgba(0,0,0,.15);">Devis &amp; Factures <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($dbs['dash_kpi_crm_pending'] && $crmPending > 0): ?>
        <div class="col-lg col-md-4 col-sm-6 col-12">
          <div class="small-box bg-warning">
            <div class="inner"><h3><?php echo $crmPending; ?></h3><p>Devis en attente</p></div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            <a href="crm-devis.php?status=sent" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

      </div><!-- /.row KPI -->


      <!-- ── ROW 2 : GRAPHIQUES ────────────────────────────────────────────── -->
      <?php if (can('contacts')): ?>
      <div class="row db-block" id="block-charts">
        <div class="col-lg-8 col-12">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Contacts — 12 derniers mois</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
              </div>
            </div>
            <div class="card-body"><canvas id="contactsChart" height="100"></canvas></div>
          </div>
        </div>
        <div class="col-lg-4 col-12">
          <div class="card card-outline card-danger">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Par service</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
              </div>
            </div>
            <div class="card-body d-flex flex-column align-items-center">
              <canvas id="serviceChart" style="max-height:220px;"></canvas>
              <div id="serviceLegend" class="mt-3 w-100" style="font-size:.8rem;"></div>
            </div>
          </div>
        </div>
      </div><!-- /.row charts -->
      <?php endif; ?>


      <!-- ── ROW 3 : DERNIERS CONTACTS + RÉALISATIONS ──────────────────────── -->
      <div class="row db-block" id="block-recent">

        <?php if (can('contacts')): ?>
        <div class="col-lg-7 col-12">
          <div class="card card-outline card-warning">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-users mr-2"></i>Derniers contacts</h3>
              <div class="card-tools"><a href="contacts.php" class="btn btn-sm btn-default">Tous voir</a></div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-hover m-0">
                  <thead>
                    <tr><th>Nom</th><th>Service</th><th>Statut</th><th>Date</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentContacts as $c): ?>
                    <tr>
                      <td>
                        <a href="contact-view.php?id=<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></a>
                        <?php if ($c['city']): ?><small class="text-muted ml-1"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($c['city']); ?></small><?php endif; ?>
                      </td>
                      <td><small><?php echo htmlspecialchars($c['service'] ?: '—'); ?></small></td>
                      <td>
                        <?php if ($c['status'] === 'new'): ?>
                          <span class="badge badge-danger">Nouveau</span>
                        <?php elseif ($c['status'] === 'treated'): ?>
                          <span class="badge badge-success">Traité</span>
                        <?php else: ?>
                          <span class="badge badge-secondary"><?php echo htmlspecialchars($c['status']); ?></span>
                        <?php endif; ?>
                      </td>
                      <td><small class="text-muted"><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentContacts)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Aucun contact</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer text-right">
              <small class="text-muted">
                Total : <strong><?php echo $contactsTotal; ?></strong> contacts
                — <strong><?php echo $contactsTreated; ?></strong> traités
                — <strong><?php echo $contactsNew; ?></strong> en attente
              </small>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if (can('realisations')): ?>
        <div class="col-lg-5 col-12">
          <div class="card card-outline card-success">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-paint-roller mr-2"></i>Dernières réalisations</h3>
              <div class="card-tools"><a href="realisations.php" class="btn btn-sm btn-default">Toutes voir</a></div>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <?php foreach ($recentRealisations as $r): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                  <div>
                    <a href="realisation-edit.php?id=<?php echo $r['id']; ?>" class="font-weight-bold"><?php echo htmlspecialchars($r['title']); ?></a>
                    <br>
                    <small class="text-muted">
                      <?php if ($r['city']): ?><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($r['city']); ?><?php endif; ?>
                      <?php if ($r['type']): ?> &bull; <?php echo htmlspecialchars($r['type']); ?><?php endif; ?>
                    </small>
                  </div>
                  <div class="text-right">
                    <?php if ($r['is_published']): ?>
                      <span class="badge badge-success">Publié</span>
                    <?php else: ?>
                      <span class="badge badge-secondary">Brouillon</span>
                    <?php endif; ?>
                    <br><small class="text-muted"><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></small>
                  </div>
                </li>
                <?php endforeach; ?>
                <?php if (empty($recentRealisations)): ?>
                <li class="list-group-item text-center text-muted py-3">Aucune réalisation</li>
                <?php endif; ?>
              </ul>
            </div>
            <div class="card-footer text-right">
              <small class="text-muted">
                Total : <strong><?php echo $realisationsTotal; ?></strong> publiées
                <?php if ($realisationsDraft): ?> — <strong><?php echo $realisationsDraft; ?></strong> brouillons<?php endif; ?>
              </small>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /.row recent -->


      <!-- ── ROW CRM : DERNIERS DEVIS / FACTURES ──────────────────────────── -->
      <?php if (can('crm')): ?>
      <div class="row db-block" id="block-crm">
        <div class="col-lg-8 col-12">
          <div class="card card-outline" style="border-top-color:#0d6efd;">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-file-invoice mr-2" style="color:#0d6efd"></i>Derniers devis &amp; factures</h3>
              <div class="card-tools"><a href="crm-devis.php" class="btn btn-sm btn-default">Tous voir</a></div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-hover m-0">
                  <thead>
                    <tr><th>Réf.</th><th>Client</th><th>Type</th><th>Montant TTC</th><th>Statut</th><th>Date</th></tr>
                  </thead>
                  <tbody>
                    <?php
                    $dStatusLabels = ['draft'=>'Brouillon','sent'=>'Envoyé','accepted'=>'Accepté','refused'=>'Refusé','invoiced'=>'Facturé','paid'=>'Payé'];
                    $dStatusColors = ['draft'=>'secondary','sent'=>'info','accepted'=>'success','refused'=>'danger','invoiced'=>'warning','paid'=>'success'];
                    foreach ($recentDevis as $d): ?>
                    <tr>
                      <td><a href="crm-devis-edit.php?id=<?= $d['id'] ?>"><code><?= htmlspecialchars($d['ref']) ?></code></a></td>
                      <td><a href="crm-clients.php"><?= htmlspecialchars($d['client_name']) ?></a></td>
                      <td>
                        <?php if ($d['type'] === 'devis'): ?>
                          <span class="badge badge-primary">Devis</span>
                        <?php else: ?>
                          <span class="badge badge-dark">Facture</span>
                        <?php endif; ?>
                      </td>
                      <td><strong><?= number_format((float)$d['total_ttc'], 2, ',', ' ') ?> €</strong></td>
                      <td><span class="badge badge-<?= $dStatusColors[$d['status']] ?? 'secondary' ?>"><?= $dStatusLabels[$d['status']] ?? $d['status'] ?></span></td>
                      <td><small class="text-muted"><?= $d['issued_at'] ? date('d/m/Y', strtotime($d['issued_at'])) : date('d/m/Y', strtotime($d['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentDevis)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucun document CRM</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer text-right">
              <small class="text-muted">
                <?= $crmDevisCount ?> devis &mdash; <?= $crmFacturesCount ?> factures &mdash; CA TTC encaissé : <strong><?= number_format($crmCaTtc, 2, ',', ' ') ?> €</strong>
              </small>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-12">
          <div class="card card-outline" style="border-top-color:#20c997;">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar mr-2" style="color:#20c997"></i>CRM — Vue d&rsquo;ensemble</h3></div>
            <div class="card-body p-0">
              <table class="table table-sm m-0">
                <tbody>
                  <tr><td class="text-muted">Clients</td><td><strong><?= $crmClients ?></strong></td></tr>
                  <tr><td class="text-muted">Devis émis</td><td><strong><?= $crmDevisCount ?></strong></td></tr>
                  <tr><td class="text-muted">Factures</td><td><strong><?= $crmFacturesCount ?></strong></td></tr>
                  <tr><td class="text-muted">En attente de réponse</td><td><strong><?= $crmPending ?></strong></td></tr>
                  <tr><td class="text-muted">CA TTC (accepté/payé)</td><td><strong><?= number_format($crmCaTtc, 2, ',', ' ') ?> €</strong></td></tr>
                </tbody>
              </table>
            </div>
            <div class="card-footer">
              <a href="crm-clients.php" class="btn btn-sm btn-outline-primary mr-1"><i class="fas fa-users mr-1"></i>Clients</a>
              <a href="crm-devis.php" class="btn btn-sm btn-outline-secondary mr-1"><i class="fas fa-file-invoice mr-1"></i>Devis</a>
              <a href="crm-devis-edit.php" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i>Nouveau</a>
            </div>
          </div>
        </div>
      </div><!-- /.row CRM -->
      <?php endif; ?>


      <!-- ── ROW 4 : RACCOURCIS + INFOS SYSTÈME + CONNEXIONS ───────────────── -->
      <div class="row db-block" id="block-bottom">

        <!-- Raccourcis -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Raccourcis</h3></div>
            <div class="card-body">
              <div class="row">

                <?php if (can('contacts')): ?>
                <div class="col-6 mb-2">
                  <a href="contacts.php" class="btn btn-block btn-outline-danger btn-sm">
                    <i class="fas fa-envelope mr-1"></i> Contacts
                    <?php if ($contactsNew): ?><span class="badge badge-danger ml-1"><?php echo $contactsNew; ?></span><?php endif; ?>
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('realisations')): ?>
                <div class="col-6 mb-2">
                  <a href="realisation-create.php" class="btn btn-block btn-outline-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Réalisation
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('cms')): ?>
                <div class="col-6 mb-2">
                  <a href="cms-pages.php" class="btn btn-block btn-outline-secondary btn-sm">
                    <i class="fas fa-file-alt mr-1"></i> Pages CMS
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('galleries')): ?>
                <div class="col-6 mb-2">
                  <a href="galleries.php" class="btn btn-block btn-outline-warning btn-sm">
                    <i class="fas fa-images mr-1"></i> Galeries
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('media')): ?>
                <div class="col-6 mb-2">
                  <a href="media.php" class="btn btn-block btn-outline-info btn-sm">
                    <i class="fas fa-photo-video mr-1"></i> Médias
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('forms')): ?>
                <div class="col-6 mb-2">
                  <a href="forms.php" class="btn btn-block btn-outline-primary btn-sm">
                    <i class="fas fa-paper-plane mr-1"></i> Formulaires
                    <?php if ($submissionsUnread): ?><span class="badge badge-info ml-1"><?php echo $submissionsUnread; ?></span><?php endif; ?>
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('settings')): ?>
                <div class="col-6 mb-2">
                  <a href="settings.php" class="btn btn-block btn-outline-dark btn-sm">
                    <i class="fas fa-cog mr-1"></i> Paramètres
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('themes')): ?>
                <div class="col-6 mb-2">
                  <a href="themes.php" class="btn btn-block btn-outline-dark btn-sm">
                    <i class="fas fa-palette mr-1"></i> Thèmes
                  </a>
                </div>
                <?php endif; ?>

                <?php if (can('crm')): ?>
                <div class="col-6 mb-2">
                  <a href="crm-clients.php" class="btn btn-block btn-sm" style="border:1px solid #0d6efd;color:#0d6efd;">
                    <i class="fas fa-address-book mr-1"></i> Clients CRM
                  </a>
                </div>
                <div class="col-6 mb-2">
                  <a href="crm-devis-edit.php" class="btn btn-block btn-sm" style="border:1px solid #20c997;color:#20c997;">
                    <i class="fas fa-file-invoice mr-1"></i> Nouveau devis
                    <?php if ($crmPending): ?><span class="badge badge-warning ml-1"><?= $crmPending ?></span><?php endif; ?>
                  </a>
                </div>
                <?php endif; ?>

                <div class="col-6 mb-2">
                  <a href="profile.php" class="btn btn-block btn-outline-secondary btn-sm">
                    <i class="fas fa-user-circle mr-1"></i> Mon profil
                  </a>
                </div>
                <div class="col-6 mb-2">
                  <a href="<?php echo BASE_URL; ?>" target="_blank" class="btn btn-block btn-outline-secondary btn-sm">
                    <i class="fas fa-eye mr-1"></i> Voir le site
                  </a>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Infos système -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-server mr-2"></i>Informations système</h3></div>
            <div class="card-body p-0">
              <table class="table table-sm m-0">
                <tbody>
                  <tr><td class="text-muted" style="width:50%">Version PHP</td><td><span class="badge badge-dark"><?php echo phpversion(); ?></span></td></tr>
                  <tr><td class="text-muted">Thème actif</td><td><span class="badge badge-info"><?php echo htmlspecialchars($activeTheme); ?></span></td></tr>
                  <tr><td class="text-muted">Utilisateurs actifs</td><td><strong><?php echo $adminsActive; ?></strong></td></tr>
                  <tr><td class="text-muted">Galeries</td><td><strong><?php echo $galleriesTotal; ?></strong> <small class="text-muted">(<?php echo $galleryItems; ?> items)</small></td></tr>
                  <tr><td class="text-muted">Médias</td><td><strong><?php echo $mediaTotal; ?></strong></td></tr>
                  <tr><td class="text-muted">Pages CMS</td><td><strong><?php echo $cmsPages; ?></strong></td></tr>
                  <?php if (can('crm')): ?>
                  <tr><td class="text-muted">Clients CRM</td><td><strong><?= $crmClients ?></strong></td></tr>
                  <tr><td class="text-muted">Devis / Factures</td><td><strong><?= $crmDevisCount ?></strong> / <strong><?= $crmFacturesCount ?></strong></td></tr>
                  <?php endif; ?>
                  <tr><td class="text-muted">Date serveur</td><td><small><?php echo date('d/m/Y H:i'); ?></small></td></tr>
                  <?php $memUsed = round(memory_get_usage(true)/1024/1024,1); $memPeak = round(memory_get_peak_usage(true)/1024/1024,1); ?>
                  <tr><td class="text-muted">Mémoire PHP</td><td><small><?php echo $memUsed; ?> Mo <span class="text-muted">/ peak <?php echo $memPeak; ?> Mo</span></small></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Dernières connexions -->
        <?php if (can('users')): ?>
        <div class="col-lg-4 col-12">
          <div class="card card-outline card-dark">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-sign-in-alt mr-2"></i>Dernières connexions</h3>
              <div class="card-tools"><a href="users.php" class="btn btn-sm btn-default">Gérer</a></div>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <?php
                $roleColors = ['super_admin'=>'danger','admin'=>'warning','editor'=>'info','author'=>'secondary'];
                $roleLabels = ['super_admin'=>'Super Admin','admin'=>'Admin','editor'=>'Éditeur','author'=>'Auteur'];
                $avatarColors = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c'];
                foreach ($lastLogins as $u):
                  $initials   = strtoupper(mb_substr($u['display_name'] ?: $u['username'], 0, 1));
                  $roleColor  = $roleColors[$u['role']] ?? 'secondary';
                  $roleLabel  = $roleLabels[$u['role']] ?? $u['role'];
                  $ci         = abs(crc32($u['username'])) % count($avatarColors);
                ?>
                <li class="list-group-item px-3 py-2 d-flex align-items-center">
                  <div class="mr-3">
                    <?php if ($u['avatar']): ?>
                      <img src="<?php echo BASE_URL; ?>/uploads/avatars/<?php echo htmlspecialchars($u['avatar']); ?>"
                           alt="" class="img-circle elevation-1" style="width:36px;height:36px;object-fit:cover;">
                    <?php else: ?>
                      <div class="img-circle elevation-1 d-flex align-items-center justify-content-center"
                           style="width:36px;height:36px;background:<?php echo $avatarColors[$ci]; ?>;color:#fff;font-weight:700;font-size:.9rem;">
                        <?php echo $initials; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="flex-grow-1">
                    <div>
                      <strong><?php echo htmlspecialchars($u['display_name'] ?: $u['username']); ?></strong>
                      <span class="badge badge-<?php echo $roleColor; ?> ml-1" style="font-size:.65rem;"><?php echo $roleLabel; ?></span>
                    </div>
                    <small class="text-muted">
                      <i class="fas fa-clock mr-1"></i>
                      <?php
                        $ts = strtotime($u['last_login']);
                        $diff = time() - $ts;
                        if ($diff < 60) echo "à l'instant";
                        elseif ($diff < 3600) echo floor($diff/60) . ' min';
                        elseif ($diff < 86400) echo floor($diff/3600) . ' h';
                        else echo date('d/m/Y H:i', $ts);
                      ?>
                    </small>
                  </div>
                </li>
                <?php endforeach; ?>
                <?php if (empty($lastLogins)): ?>
                <li class="list-group-item text-center text-muted py-3">Aucune connexion récente</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /.row bottom -->

    </div><!-- /.container-fluid -->
  </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<?php if (can('contacts')): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function() {
  // ── Line chart ──────────────────────────────────────────────────────────────
  const lineCtx = document.getElementById('contactsChart');
  if (lineCtx) {
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($chartLabels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [
          {
            label: 'Nouveaux',
            data: <?php echo json_encode($chartNew); ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220,53,69,.12)',
            pointBackgroundColor: '#dc3545',
            tension: .4,
            fill: true,
          },
          {
            label: 'Traités',
            data: <?php echo json_encode($chartTreated); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,.09)',
            pointBackgroundColor: '#28a745',
            tension: .4,
            fill: true,
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom', labels: { color: '#aaa', boxWidth: 12 } },
          tooltip: { mode: 'index', intersect: false }
        },
        scales: {
          x: { grid: { color: 'rgba(255,255,255,.06)' }, ticks: { color: '#aaa' } },
          y: { grid: { color: 'rgba(255,255,255,.06)' }, ticks: { color: '#aaa', stepSize: 1, precision: 0 }, beginAtZero: true }
        }
      }
    });
  }

  // ── Doughnut chart ──────────────────────────────────────────────────────────
  const doCtx = document.getElementById('serviceChart');
  if (doCtx) {
    const PALETTE = ['#dc3545','#ffc107','#28a745','#17a2b8','#6f42c1','#fd7e14'];
    const labels = <?php echo json_encode($serviceLabels, JSON_UNESCAPED_UNICODE); ?>;
    const values = <?php echo json_encode($serviceValues); ?>;
    const colors = PALETTE.slice(0, labels.length);

    new Chart(doCtx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderColor: '#1a1a2e',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        cutout: '65%',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ` ${ctx.label} : ${ctx.raw}` } }
        }
      }
    });

    const legend = document.getElementById('serviceLegend');
    if (legend) {
      legend.innerHTML = labels.map((l, i) =>
        `<div class="d-flex align-items-center mb-1">
          <span style="width:12px;height:12px;background:${colors[i]};display:inline-block;border-radius:2px;margin-right:6px;flex-shrink:0;"></span>
          <span class="text-muted flex-grow-1">${l}</span>
          <strong class="ml-2">${values[i]}</strong>
        </div>`
      ).join('');
    }
  }
})();
</script>
<?php endif; ?>

<!-- ══ PANEL PERSONNALISATION ══════════════════════════════════════════════ -->
<div id="db-overlay"></div>
<div id="db-panel">
  <div class="db-panel-header">
    <span><i class="fas fa-th-large mr-2"></i>Personnaliser l'affichage</span>
    <button id="db-panel-close"><i class="fas fa-times"></i></button>
  </div>
  <div class="db-panel-body">
    <p class="text-muted" style="font-size:.8rem;margin-bottom:1rem;">Cochez les blocs que vous souhaitez afficher. Votre choix est sauvegardé automatiquement.</p>
    <ul class="db-toggle-list">
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="block-kpi" checked>
          <span class="db-toggle-icon" style="background:#dc3545"><i class="fas fa-chart-bar"></i></span>
          <span class="db-toggle-label">KPI — Métriques clés</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <?php if (can('contacts')): ?>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="block-charts" checked>
          <span class="db-toggle-icon" style="background:#007bff"><i class="fas fa-chart-line"></i></span>
          <span class="db-toggle-label">Graphiques — Contacts</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="block-recent" checked>
          <span class="db-toggle-icon" style="background:#ffc107"><i class="fas fa-list"></i></span>
          <span class="db-toggle-label">Derniers contacts &amp; réalisations</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <?php endif; ?>
      <?php if (can('crm')): ?>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="block-crm" checked>
          <span class="db-toggle-icon" style="background:#0d6efd"><i class="fas fa-file-invoice"></i></span>
          <span class="db-toggle-label">CRM — Devis &amp; Factures</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
      <?php endif; ?>
      <li>
        <label class="db-toggle-item">
          <input type="checkbox" class="db-toggle" data-block="block-bottom" checked>
          <span class="db-toggle-icon" style="background:#6c757d"><i class="fas fa-bolt"></i></span>
          <span class="db-toggle-label">Raccourcis &amp; Infos système</span>
          <span class="db-toggle-switch"></span>
        </label>
      </li>
    </ul>
    <button id="db-panel-reset" class="btn btn-sm btn-outline-secondary mt-3 w-100">
      <i class="fas fa-undo mr-1"></i>Réinitialiser l'affichage
    </button>
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
</style>

<script>
(function(){
  const STORAGE_KEY     = 'joker_dashboard_blocks';
  const allBlocks       = ['block-kpi','block-charts','block-recent','block-crm','block-bottom'];
  // Défauts serveur (réglages → Dashboard)
  const _serverDefaults = <?= $jsBlockDefaults ?>;

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

    function openPanel()  { panel.classList.add('open'); overlay.classList.add('open'); }
    function closePanel() { panel.classList.remove('open'); overlay.classList.remove('open'); }

    btnOpen.addEventListener('click', openPanel);
    btnClose.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanel(); });

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
      syncToggles({});
      applyVisibility({});
    });
  });
})();
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
