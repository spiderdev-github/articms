<?php
require_once __DIR__ . '/auth.php';
requirePermission('galleries');
require_once __DIR__ . '/../includes/db.php';
$pdo = getPDO();

$galleries = $pdo->query("
    SELECT g.*, COUNT(gi.id) AS item_count
    FROM galleries g
    LEFT JOIN gallery_items gi ON gi.gallery_id = g.id
    GROUP BY g.id
    ORDER BY g.sort_order ASC, g.created_at ASC
")->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
    <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i> Galeries</h3>
    <a href="gallery-edit.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Nouvelle galerie</a>
  </div>

  <div class="card-body p-0">
    <?php if (empty($galleries)): ?>
      <p class="text-muted p-3">Aucune galerie pour l'instant.</p>
    <?php else: ?>
      <table class="table table-hover text-sm mb-0">
        <thead>
          <tr>
            <th>Ordre</th>
            <th>Nom</th>
            <th>Description</th>
            <th>Réalisations</th>
            <th>Créée le</th>
            <th style="width:180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($galleries as $g): ?>
            <tr>
              <td><?= (int)$g['sort_order'] ?></td>
              <td class="font-weight-bold"><?= htmlspecialchars($g['name']) ?></td>
              <td class="text-muted"><?= htmlspecialchars($g['description'] ?? '') ?></td>
              <td><span class="badge badge-info"><?= (int)$g['item_count'] ?></span></td>
              <td><?= date('d/m/Y', strtotime($g['created_at'])) ?></td>
              <td>
                <a href="gallery-edit.php?id=<?= $g['id'] ?>" class="btn btn-xs btn-warning mr-1">
                  <i class="fas fa-edit"></i> Éditer
                </a>
                <button
                  class="btn btn-xs btn-danger"
                  onclick="confirmDelete(<?= $g['id'] ?>, <?= (int)$g['item_count'] ?>)"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
