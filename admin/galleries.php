<?php
require_once __DIR__ . '/auth.php';
requirePermission('galleries');
require_once __DIR__ . '/../includes/db.php';
$pdo = getPDO();

// ── Recherche & tri ─────────────────────────────────
$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? '';
$dir    = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$allowedSorts = [
    'name'       => 'g.name',
    'item_count' => 'item_count',
    'date'       => 'g.created_at',
    'ordre'      => 'g.sort_order',
];
$sortSql = isset($allowedSorts[$sort]) ? $allowedSorts[$sort] . ' ' . $dir : 'g.sort_order ASC, g.created_at ASC';

$where  = ['1=1'];
$params = [];
if ($search !== '') {
    $where[]  = '(g.name LIKE ? OR g.description LIKE ?)';
    $term     = "%$search%";
    $params[] = $term;
    $params[] = $term;
}
$whereStr = implode(' AND ', $where);

$totalCount = (function() use ($pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM galleries")->fetchColumn();
})();

$stmt = $pdo->prepare("
    SELECT g.*, COUNT(gi.id) AS item_count
    FROM galleries g
    LEFT JOIN gallery_items gi ON gi.gallery_id = g.id
    WHERE $whereStr
    GROUP BY g.id
    ORDER BY $sortSql
");
$stmt->execute($params);
$galleries = $stmt->fetchAll();

function galSortLink(string $col, string $label, string $cur, string $dir): string {
    $next   = ($cur === $col && $dir === 'asc') ? 'desc' : 'asc';
    $params = array_merge($_GET, ['sort' => $col, 'dir' => $next]);
    $url    = 'galleries.php?' . http_build_query($params);
    $icon   = $cur === $col
        ? '<i class="fas fa-sort-' . ($dir === 'asc' ? 'up' : 'down') . ' ml-1"></i>'
        : '<i class="fas fa-sort ml-1 text-muted"></i>';
    return '<a href="' . htmlspecialchars($url) . '" class="text-reset text-decoration-none" style="white-space:nowrap;">' . $label . $icon . '</a>';
}

include __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0"><i class="fas fa-layer-group mr-2"></i>Galeries</h4>
  <a href="gallery-edit.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Nouvelle galerie</a>
</div>

<!-- Filtres -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="form-inline" style="gap:8px;flex-wrap:wrap;">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Nom, description…" value="<?= htmlspecialchars($search) ?>" style="min-width:220px;">
      <button class="btn btn-sm btn-secondary" type="submit"><i class="fas fa-search mr-1"></i>Filtrer</button>
      <?php if ($search): ?>
        <a href="galleries.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="m-0">
      <?= count($galleries) ?> galerie<?= count($galleries) > 1 ? 's' : '' ?>
      <?php if ($search): ?>
        <small class="text-muted font-weight-normal">sur <?= $totalCount ?> au total</small>
      <?php endif; ?>
    </h5>
  </div>
  <div class="card-body p-0">
    <?php if (empty($galleries)): ?>
      <div class="p-4 text-center text-muted">
        <i class="fas fa-layer-group fa-2x mb-2 d-block"></i>
        Aucune galerie<?= $search ? ' pour cette recherche' : '' ?>.
        <?php if (!$search): ?><br><a href="gallery-edit.php">Créer la première galerie</a><?php endif; ?>
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="thead-dark">
          <tr>
            <th><?= galSortLink('ordre',      'Ordre',        $sort, $dir) ?></th>
            <th><?= galSortLink('name',       'Nom',          $sort, $dir) ?></th>
            <th>Description</th>
            <th class="text-center"><?= galSortLink('item_count', 'Photos', $sort, $dir) ?></th>
            <th><?= galSortLink('date',       'Créée le',     $sort, $dir) ?></th>
            <th style="width:130px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($galleries as $g): ?>
            <tr>
              <td class="align-middle"><?= (int)$g['sort_order'] ?></td>
              <td class="align-middle font-weight-bold"><?= htmlspecialchars($g['name']) ?></td>
              <td class="align-middle text-muted"><?= htmlspecialchars($g['description'] ?? '') ?></td>
              <td class="align-middle text-center"><span class="badge badge-info"><?= (int)$g['item_count'] ?></span></td>
              <td class="align-middle"><small><?= date('d/m/Y', strtotime($g['created_at'])) ?></small></td>
              <td class="align-middle text-right" style="white-space:nowrap;">
                <a href="gallery-edit.php?id=<?= $g['id'] ?>" class="btn btn-xs btn-outline-primary" title="Éditer">
                  <i class="fas fa-edit"></i>
                </a>
                <button class="btn btn-xs btn-outline-danger ml-1"
                  onclick="confirmDelete(<?= $g['id'] ?>, <?= (int)$g['item_count'] ?>)" title="Supprimer">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Delete modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Supprimer la galerie ?</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p id="deleteMsg">Cette galerie sera supprimée définitivement.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Annuler</button>
        <form method="post" action="actions/gallery-delete.php">
          <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>">
          <input type="hidden" name="id" id="deleteId">
          <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, count) {
  document.getElementById('deleteId').value = id;
  document.getElementById('deleteMsg').textContent =
    'Cette galerie et ses ' + count + ' référence(s) seront supprimées.';
  $('#deleteModal').modal('show');
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
