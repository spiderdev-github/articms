<?php
require_once __DIR__ . '/partials/header.php';
requirePermission('crm');

/* ── Filtres & recherche ─────────────────────────────────────── */
$search = trim($_GET['q'] ?? '');
$type   = $_GET['type'] ?? '';
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search !== '') {
    $where[]  = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company LIKE ? OR c.ref LIKE ?)';
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}
if ($type !== '') {
    $where[]  = 'c.type = ?';
    $params[] = $type;
}
$whereStr = implode(' AND ', $where);

$total  = (int)$pdo->prepare("SELECT COUNT(*) FROM crm_clients c WHERE $whereStr");
$total  = (function() use ($pdo, $whereStr, $params) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM crm_clients c WHERE $whereStr");
    $st->execute($params);
    return (int)$st->fetchColumn();
})();
$pages  = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM crm_devis d WHERE d.client_id = c.id) AS devis_count
    FROM crm_clients c
    WHERE $whereStr
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0"><i class="fas fa-users mr-2"></i>Clients</h4>
  <a href="crm-client-edit.php" class="btn btn-primary btn-sm">
    <i class="fas fa-plus mr-1"></i> Nouveau client
  </a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Filtres -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="form-inline" style="gap:8px;flex-wrap:wrap;">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Rechercher…" value="<?= htmlspecialchars($search) ?>" style="min-width:200px;">
      <select name="type" class="form-control form-control-sm">
        <option value="">Tous types</option>
        <option value="particulier" <?= $type === 'particulier' ? 'selected' : '' ?>>Particulier</option>
        <option value="professionnel" <?= $type === 'professionnel' ? 'selected' : '' ?>>Professionnel</option>
      </select>
      <button class="btn btn-sm btn-secondary" type="submit"><i class="fas fa-search mr-1"></i>Filtrer</button>
      <?php if ($search || $type): ?><a href="crm-clients.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a><?php endif; ?>
    </form>
  </div>
</div>

<!-- Liste -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="m-0">
      <?= $total ?> client<?= $total > 1 ? 's' : '' ?>
    </h5>
  </div>
  <div class="card-body p-0">
    <?php if (empty($clients)): ?>
      <div class="p-4 text-center text-muted">
        <i class="fas fa-users fa-2x mb-2 d-block"></i>
        Aucun client<?= $search ? ' pour cette recherche' : '' ?>.
        <?php if (!$search): ?><br><a href="crm-client-edit.php">Créer le premier client</a><?php endif; ?>
      </div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-hover m-0">
      <thead class="thead-dark">
        <tr>
          <th>Réf.</th>
          <th>Nom / Société</th>
          <th>Type</th>
          <th>Email</th>
          <th>Tél.</th>
          <th>Ville</th>
          <th class="text-center">Devis</th>
          <th>Créé le</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($clients as $c): ?>
        <tr>
          <td><code><?= htmlspecialchars($c['ref']) ?></code></td>
          <td>
            <strong><?= htmlspecialchars($c['name']) ?></strong>
            <?php if ($c['company']): ?><br><small class="text-muted"><?= htmlspecialchars($c['company']) ?></small><?php endif; ?>
          </td>
          <td>
            <span class="badge badge-<?= $c['type'] === 'professionnel' ? 'info' : 'secondary' ?>">
              <?= $c['type'] === 'professionnel' ? 'Pro' : 'Part.' ?>
            </span>
          </td>
          <td><?= $c['email'] ? '<a href="mailto:'.htmlspecialchars($c['email']).'">'.htmlspecialchars($c['email']).'</a>' : '<span class="text-muted">—</span>' ?></td>
          <td><?= $c['phone'] ? htmlspecialchars($c['phone']) : '<span class="text-muted">—</span>' ?></td>
          <td><?= $c['city'] ? htmlspecialchars($c['city']) : '—' ?></td>
          <td class="text-center">
            <?php if ($c['devis_count'] > 0): ?>
              <a href="crm-devis.php?client_id=<?= $c['id'] ?>" class="badge badge-primary"><?= $c['devis_count'] ?></a>
            <?php else: ?>
              <span class="text-muted">0</span>
            <?php endif; ?>
          </td>
          <td><small><?= date('d/m/Y', strtotime($c['created_at'])) ?></small></td>
          <td class="text-right">
            <a href="crm-devis-edit.php?client_id=<?= $c['id'] ?>" class="btn btn-xs btn-outline-success" title="Nouveau devis"><i class="fas fa-file-invoice"></i></a>
            <a href="crm-client-edit.php?id=<?= $c['id'] ?>" class="btn btn-xs btn-outline-primary ml-1" title="Modifier"><i class="fas fa-edit"></i></a>
            <button onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')"
                    class="btn btn-xs btn-outline-danger ml-1" title="Supprimer"><i class="fas fa-trash"></i></button>
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

<form id="deleteForm" method="post" action="actions/crm-client-delete.php" style="display:none;">
  <input type="hidden" name="csrf" value="<?= $csrf ?>">
  <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, name) {
  if (confirm('Supprimer le client "' + name + '" et tous ses devis ?')) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteForm').submit();
  }
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
