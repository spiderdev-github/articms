<?php
require_once __DIR__ . '/auth.php';
requirePermission('realisations');

require_once __DIR__ . '/../includes/db.php';
$pdo = getPDO();
$csrf = getCsrfToken();

$rows = $pdo->query("SELECT * FROM realisations ORDER BY sort_order ASC, created_at DESC")->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
    <h3 class="card-title"><i class="fas fa-images mr-1"></i> Realisations</h3>
    <div class="d-flex" style="gap:8px;">
      <a class="btn btn-sm btn-primary" href="realisation-create.php"><i class="fas fa-plus"></i> Nouvelle réalisation</a>
    </div>
  </div>

  <div class="card-body table-responsive p-0">
    <table class="table table-hover text-sm">
      <thead>
        <tr>
          <th>Ordre</th>
          <th>Couverture</th>
          <th>Titre</th>
          <th>Ville</th>
          <th>Type</th>
          <th>Publie</th>
          <th>Featured</th>
          <th style="width:240px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['sort_order'] ?></td>
            <td>
              <?php if (!empty($r['cover_image'])): ?>
                <img src="<?= BASE_URL . '/' . ltrim($r['cover_image'],'/') ?>" style="width:70px;height:46px;object-fit:cover;border-radius:8px;">
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td class="font-weight-bold"><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['city'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['type'] ?? '') ?></td>
            <td>
              <span class="badge <?= $r['is_published'] ? 'badge-success' : 'badge-secondary' ?>">
                <?= $r['is_published'] ? 'Oui' : 'Non' ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $r['is_featured'] ? 'badge-warning' : 'badge-secondary' ?>">
                <?= $r['is_featured'] ? 'Oui' : 'Non' ?>
              </span>
            </td>
            <td class="d-flex flex-wrap" style="gap:6px;">
              <a class="btn btn-secondary btn-sm" href="realisation-edit.php?id=<?= (int)$r['id'] ?>"><i class="fas fa-pen"></i></a>

              <form method="POST" action="actions/realisation-toggle.php" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="field" value="is_published">
                <button class="btn btn-sm btn-<?= $r['is_published'] ? 'success' : 'secondary' ?>" type="submit" title="toggle publish">
                  <i class="fas fa-toggle-on"></i>
                </button>
              </form>

              <form method="POST" action="actions/realisation-toggle.php" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="field" value="is_featured">
                <button class="btn btn-sm btn-<?= $r['is_featured'] ? 'warning' : 'secondary' ?>" type="submit" title="toggle featured">
                  <i class="fas fa-star"></i>
                </button>
              </form>

              <form method="POST" action="actions/realisation-delete.php" class="m-0" onsubmit="return confirm('Supprimer cette realisation ?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-muted p-3">Aucune realisation.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>