<?php
// Le layout injecte $content après ob_start dans View::render()
// Ce fichier est surchargé par le layout admin.php
// Les vues n'ont besoin que d'écrire leur contenu HTML.
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</h3>
  </div>
  <div class="card-body">

    <!-- KPI Row -->
    <div class="row">
      <div class="col-6 col-md-3">
        <div class="small-box bg-info">
          <div class="inner"><h3><?= (int)($kpi['contacts']['total'] ?? 0) ?></h3><p>Contacts total</p></div>
          <div class="icon"><i class="fas fa-envelope"></i></div>
          <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/contacts" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="small-box bg-danger">
          <div class="inner"><h3><?= (int)($kpi['contacts']['new'] ?? 0) ?></h3><p>Nouveaux contacts</p></div>
          <div class="icon"><i class="fas fa-bell"></i></div>
          <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/contacts?pipeline=new" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="small-box bg-success">
          <div class="inner"><h3><?= (int)($kpi['realisations_total'] ?? 0) ?></h3><p>Réalisations publiées</p></div>
          <div class="icon"><i class="fas fa-images"></i></div>
          <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/realisations" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="small-box bg-warning">
          <div class="inner"><h3><?= (int)($kpi['submissions_unread'] ?? 0) ?></h3><p>Soumissions non lues</p></div>
          <div class="icon"><i class="fas fa-clipboard-list"></i></div>
          <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/forms" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
    </div>

    <!-- CRM Stats -->
    <?php if (!empty($crmStats)): ?>
    <div class="row">
      <div class="col-6 col-md-3">
        <div class="info-box"><span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
          <div class="info-box-content"><span class="info-box-text">Clients CRM</span><span class="info-box-number"><?= (int)($crmStats['clients'] ?? 0) ?></span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box"><span class="info-box-icon bg-success"><i class="fas fa-file-invoice"></i></span>
          <div class="info-box-content"><span class="info-box-text">Devis</span><span class="info-box-number"><?= (int)($crmStats['devis_count'] ?? 0) ?></span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box"><span class="info-box-icon bg-warning"><i class="fas fa-file-alt"></i></span>
          <div class="info-box-content"><span class="info-box-text">En attente</span><span class="info-box-number"><?= (int)($crmStats['pending'] ?? 0) ?></span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box"><span class="info-box-icon bg-success"><i class="fas fa-euro-sign"></i></span>
          <div class="info-box-content"><span class="info-box-text">CA TTC</span><span class="info-box-number"><?= number_format((float)($crmStats['ca_ttc'] ?? 0), 2, ',', ' ') ?> €</span></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Graphiques -->
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Contacts par mois</h3></div>
          <div class="card-body"><canvas id="chartContacts" height="80"></canvas></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Services</h3></div>
          <div class="card-body"><canvas id="chartServices" height="160"></canvas></div>
        </div>
      </div>
    </div>

    <!-- Derniers contacts -->
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Derniers contacts</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm">
              <thead><tr><th>Nom</th><th>Service</th><th>Statut</th><th>Date</th></tr></thead>
              <tbody>
                <?php foreach ($recentContacts as $c): ?>
                <tr>
                  <td><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/contacts/<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a></td>
                  <td><?= htmlspecialchars($c['service'] ?? '') ?></td>
                  <td><span class="badge badge-<?= $c['status'] === 'new' ? 'danger' : 'success' ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                  <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Dernières réalisations</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm">
              <thead><tr><th>Titre</th><th>Ville</th><th>Publiée</th><th>Date</th></tr></thead>
              <tbody>
                <?php foreach ($recentRealisations as $r): ?>
                <tr>
                  <td><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/realisations/<?= (int)$r['id'] ?>/edit"><?= htmlspecialchars($r['title']) ?></a></td>
                  <td><?= htmlspecialchars($r['city'] ?? '') ?></td>
                  <td><span class="badge badge-<?= $r['is_published'] ? 'success' : 'secondary' ?>"><?= $r['is_published'] ? 'Oui' : 'Non' ?></span></td>
                  <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
(function(){
  const labels   = <?= $chartLabels ?? '[]' ?>;
  const newData  = <?= $chartNew ?? '[]' ?>;
  const treated  = <?= $chartTreated ?? '[]' ?>;
  const sLabels  = <?= $serviceLabels ?? '[]' ?>;
  const sValues  = <?= $serviceValues ?? '[]' ?>;

  new Chart(document.getElementById('chartContacts'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Nouveaux',  data: newData, backgroundColor: '#e74c3c' },
        { label: 'Traités',   data: treated, backgroundColor: '#2ecc71' },
      ]
    },
    options: { responsive: true, scales: { x: { stacked: false }, y: { beginAtZero: true } } }
  });

  new Chart(document.getElementById('chartServices'), {
    type: 'doughnut',
    data: { labels: sLabels, datasets: [{ data: sValues, backgroundColor: ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });
})();
</script>
