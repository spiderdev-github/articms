<?php
require_once __DIR__ . '/partials/header.php';
requirePermission('crm');

$statusLabels = [
    'draft'    => ['label' => 'Brouillon',  'color' => 'secondary'],
    'sent'     => ['label' => 'Envoyé',     'color' => 'info'],
    'accepted' => ['label' => 'Accepté',    'color' => 'success'],
    'refused'  => ['label' => 'Refusé',     'color' => 'danger'],
    'invoiced' => ['label' => 'Facturé',    'color' => 'warning'],
    'paid'     => ['label' => 'Payé',       'color' => 'success'],
];

/* ── Filtres ───────────────────────────────────────────────── */
$search    = trim($_GET['q'] ?? '');
$status    = $_GET['status'] ?? '';
$docType   = $_GET['type'] ?? '';
$clientId  = (int)($_GET['client_id'] ?? 0);
$page      = max(1, (int)($_GET['p'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search !== '') {
    $where[]  = '(d.ref LIKE ? OR d.title LIKE ? OR cl.name LIKE ?)';
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term]);
}
if ($status !== '') { $where[] = 'd.status = ?'; $params[] = $status; }
if ($docType !== '') { $where[] = 'd.type = ?';   $params[] = $docType; }
if ($clientId > 0)  { $where[] = 'd.client_id = ?'; $params[] = $clientId; }

$whereStr = implode(' AND ', $where);

$total = (function() use ($pdo, $whereStr, $params) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM crm_devis d LEFT JOIN crm_clients cl ON cl.id=d.client_id WHERE $whereStr");
    $st->execute($params);
    return (int)$st->fetchColumn();
})();
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT d.*, cl.name AS client_name, cl.ref AS client_ref
    FROM crm_devis d
    LEFT JOIN crm_clients cl ON cl.id = d.client_id
    WHERE $whereStr
    ORDER BY d.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Stats rapides */
$stats = $pdo->query("
    SELECT
      SUM(type='devis')             AS total_devis,
      SUM(type='facture')           AS total_factures,
      SUM(status='accepted')        AS accepted,
      SUM(status='paid')            AS paid,
      SUM(CASE WHEN status='paid' THEN total_ttc ELSE 0 END) AS ca_ttc
    FROM crm_devis
")->fetch(PDO::FETCH_ASSOC);

/* Filtre client name */
$filterClientName = '';
if ($clientId > 0) {
    $row = $pdo->prepare("SELECT name FROM crm_clients WHERE id=?");
    $row->execute([$clientId]);
    $filterClientName = $row->fetchColumn();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0"><i class="fas fa-file-invoice mr-2"></i>Devis &amp; Factures</h4>
  <a href="crm-devis-edit.php" class="btn btn-primary btn-sm">
    <i class="fas fa-plus mr-1"></i> Nouveau devis
  </a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Stats rapides -->
<div class="row mb-3">
  <div class="col-6 col-md-3">
    <div class="small-box bg-info">
      <div class="inner"><h3><?= (int)$stats['total_devis'] ?></h3><p>Devis</p></div>
      <div class="icon"><i class="fas fa-file-alt"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="small-box bg-warning">
      <div class="inner"><h3><?= (int)$stats['total_factures'] ?></h3><p>Factures</p></div>
      <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="small-box bg-success">
      <div class="inner"><h3><?= (int)$stats['accepted'] ?></h3><p>Acceptés</p></div>
      <div class="icon"><i class="fas fa-check-circle"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="small-box bg-primary">
      <div class="inner">
        <h3><?= number_format((float)$stats['ca_ttc'], 0, ',', ' ') ?> €</h3>
        <p>CA encaissé TTC</p>
      </div>
      <div class="icon"><i class="fas fa-euro-sign"></i></div>
    </div>
  </div>
</div>

<!-- Filtres -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="form-inline" style="gap:8px;flex-wrap:wrap;">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Réf., titre, client…" value="<?= htmlspecialchars($search) ?>" style="min-width:180px;">
      <select name="type" class="form-control form-control-sm">
        <option value="">Devis &amp; Factures</option>
        <option value="devis"    <?= $docType === 'devis'    ? 'selected' : '' ?>>Devis</option>
        <option value="facture"  <?= $docType === 'facture'  ? 'selected' : '' ?>>Factures</option>
      </select>
      <select name="status" class="form-control form-control-sm">
        <option value="">Tous statuts</option>
        <?php foreach ($statusLabels as $k => $v): ?>
        <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($clientId): ?>
        <span class="badge badge-dark"><?= htmlspecialchars($filterClientName) ?> <a href="crm-devis.php" class="text-white ml-1">&times;</a></span>
        <input type="hidden" name="client_id" value="<?= $clientId ?>">
      <?php endif; ?>
      <button class="btn btn-sm btn-secondary" type="submit"><i class="fas fa-search mr-1"></i>Filtrer</button>
      <?php if ($search || $status || $docType): ?><a href="crm-devis.php<?= $clientId ? '?client_id='.$clientId : '' ?>" class="btn btn-sm btn-outline-secondary">Réinitialiser</a><?php endif; ?>
    </form>
  </div>
</div>

<!-- Liste -->
<div class="card">
  <div class="card-header">
    <h5 class="m-0"><?= $total ?> document<?= $total > 1 ? 's' : '' ?></h5>
  </div>
  <div class="card-body p-0">
    <?php if (empty($rows)): ?>
      <div class="p-4 text-center text-muted">
        <i class="fas fa-file-invoice fa-2x mb-2 d-block"></i>
        Aucun document. <a href="crm-devis-edit.php">Créer un devis</a>
      </div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-hover m-0">
      <thead class="thead-dark">
        <tr>
          <th>Réf.</th>
          <th>Type</th>
          <th>Client</th>
          <th>Titre</th>
          <th>Statut</th>
          <th class="text-right">Total TTC</th>
          <th>Date</th>
          <th>Validité</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $d): ?>
        <?php $sl = $statusLabels[$d['status']] ?? ['label'=>$d['status'],'color'=>'secondary']; ?>
        <tr>
          <td><code><?= htmlspecialchars($d['ref']) ?></code></td>
          <td>
            <span class="badge badge-<?= $d['type'] === 'facture' ? 'warning' : 'info' ?>">
              <?= $d['type'] === 'facture' ? 'Facture' : 'Devis' ?>
            </span>
          </td>
          <td>
            <a href="crm-clients.php?q=<?= urlencode($d['client_name']) ?>">
              <?= htmlspecialchars($d['client_name']) ?>
            </a><br>
            <small class="text-muted"><?= htmlspecialchars($d['client_ref']) ?></small>
          </td>
          <td><?= $d['title'] ? htmlspecialchars($d['title']) : '<em class="text-muted">Sans titre</em>' ?></td>
          <td><span class="badge badge-<?= $sl['color'] ?>"><?= $sl['label'] ?></span></td>
          <td class="text-right"><strong><?= number_format((float)$d['total_ttc'], 2, ',', ' ') ?> €</strong></td>
          <td><small><?= $d['issued_at'] ? date('d/m/Y', strtotime($d['issued_at'])) : '—' ?></small></td>
          <td>
            <?php if ($d['valid_until']): ?>
              <?php $expired = strtotime($d['valid_until']) < time() && !in_array($d['status'], ['accepted','invoiced','paid']); ?>
              <small class="<?= $expired ? 'text-danger font-weight-bold' : '' ?>">
                <?= date('d/m/Y', strtotime($d['valid_until'])) ?>
                <?= $expired ? ' ⚠' : '' ?>
              </small>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-right" style="white-space:nowrap;">
            <a href="crm-devis-print.php?id=<?= $d['id'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="Aperçu / Imprimer"><i class="fas fa-print"></i></a>
            <a href="crm-devis-edit.php?id=<?= $d['id'] ?>" class="btn btn-xs btn-outline-primary ml-1" title="Modifier"><i class="fas fa-edit"></i></a>
            <button onclick="confirmDelete(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['ref'])) ?>')" class="btn btn-xs btn-outline-danger ml-1" title="Supprimer"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($pages > 1): ?>
  <div class="card-footer">
    <ul class="pagination pagination-sm m-0">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
    </ul>
  </div>
  <?php endif; ?>
</div>

<form id="deleteForm" method="post" action="actions/crm-devis-delete.php" style="display:none;">
  <input type="hidden" name="csrf" value="<?= $csrf ?>">
  <input type="hidden" name="id" id="deleteId">
</form>
<script>
function confirmDelete(id, ref) {
  if (confirm('Supprimer le document "' + ref + '" ?')) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteForm').submit();
  }
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
