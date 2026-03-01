<?php
$base = defined('BASE_URL') ? BASE_URL : '';
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
    <h3 class="card-title"><i class="fas fa-images mr-1"></i> Réalisations</h3>
    <a class="btn btn-sm btn-primary" href="<?= $base ?>/admin/realisations/create"><i class="fas fa-plus mr-1"></i>Nouvelle réalisation</a>
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
          <th>Publié</th>
          <th>Featured</th>
          <th style="width:200px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['sort_order'] ?></td>
          <td>
            <?php if (!empty($r['cover_image'])): ?>
              <img src="<?= $base . '/' . ltrim($r['cover_image'], '/') ?>" style="width:70px;height:46px;object-fit:cover;border-radius:8px;" alt="">
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="font-weight-bold"><?= htmlspecialchars($r['title']) ?></td>
          <td><?= htmlspecialchars($r['city'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['type'] ?? '') ?></td>
          <td><span class="badge badge-<?= $r['is_published'] ? 'success' : 'secondary' ?>"><?= $r['is_published'] ? 'Oui' : 'Non' ?></span></td>
          <td><span class="badge badge-<?= $r['is_featured'] ? 'warning' : 'secondary' ?>"><?= $r['is_featured'] ? 'Oui' : 'Non' ?></span></td>
          <td class="d-flex flex-wrap" style="gap:6px;">
            <a class="btn btn-secondary btn-sm" href="<?= $base ?>/admin/realisations/<?= (int)$r['id'] ?>/edit"><i class="fas fa-pen"></i></a>

            <form method="POST" action="<?= $base ?>/admin/realisations/<?= (int)$r['id'] ?>/toggle" class="m-0">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="field" value="is_published">
              <button class="btn btn-sm btn-<?= $r['is_published'] ? 'success' : 'secondary' ?>" type="submit" title="toggle publish"><i class="fas fa-toggle-on"></i></button>
            </form>

            <form method="POST" action="<?= $base ?>/admin/realisations/<?= (int)$r['id'] ?>/toggle" class="m-0">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="field" value="is_featured">
              <button class="btn btn-sm btn-<?= $r['is_featured'] ? 'warning' : 'secondary' ?>" type="submit" title="toggle featured"><i class="fas fa-star"></i></button>
            </form>

            <form method="POST" action="<?= $base ?>/admin/realisations/<?= (int)$r['id'] ?>/destroy" class="m-0"
                  onsubmit="return confirm('Supprimer cette réalisation ?');">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Aucune réalisation pour l'instant.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
