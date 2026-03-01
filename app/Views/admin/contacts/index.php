<?php
$base = defined('BASE_URL') ? BASE_URL : '';
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
    <h3 class="card-title"><i class="fas fa-envelope mr-1"></i> Contacts</h3>
    <div class="d-flex" style="gap:8px;">
      <?php if ($filters['archived']): ?>
        <a href="<?= $base ?>/admin/contacts" class="btn btn-sm btn-outline-secondary"><i class="fas fa-inbox mr-1"></i>Actifs</a>
      <?php else: ?>
        <a href="<?= $base ?>/admin/contacts?archived=1" class="btn btn-sm btn-outline-secondary"><i class="fas fa-archive mr-1"></i>Archives</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filtres -->
  <div class="card-body border-bottom">
    <form method="get" class="form-inline flex-wrap" style="gap:8px;">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher…" value="<?= htmlspecialchars($filters['search']) ?>" style="min-width:180px;">
      <select name="pipeline" class="form-control form-control-sm">
        <option value="">Tous pipelines</option>
        <?php foreach (['new'=>'Nouveau','in_progress'=>'En cours','quoted'=>'Devisé','won'=>'Gagné','lost'=>'Perdu'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $filters['pipeline'] === $k ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($filters['archived']): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
      <button class="btn btn-sm btn-secondary" type="submit"><i class="fas fa-search mr-1"></i>Filtrer</button>
      <?php if ($filters['search'] || $filters['pipeline'] || $filters['tag']): ?>
        <a href="<?= $base ?>/admin/contacts<?= $filters['archived'] ? '?archived=1' : '' ?>" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card-body p-0">
    <p class="text-muted text-sm px-3 pt-2"><?= number_format($total) ?> contact<?= $total > 1 ? 's' : '' ?></p>
    <table class="table table-hover text-sm">
      <thead>
        <tr>
          <th><a href="?sort=name&dir=<?= $sort === 'name' && $dir === 'asc' ? 'desc' : 'asc' ?>">Nom</a></th>
          <th>Service</th>
          <th>Ville</th>
          <th><a href="?sort=pipeline_status&dir=<?= $sort === 'pipeline_status' && $dir === 'asc' ? 'desc' : 'asc' ?>">Pipeline</a></th>
          <th>Statut</th>
          <th><a href="?sort=created_at&dir=<?= $sort === 'created_at' && $dir === 'asc' ? 'desc' : 'asc' ?>">Date</a></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contacts as $c): ?>
        <tr>
          <td><a href="<?= $base ?>/admin/contacts/<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a></td>
          <td><?= htmlspecialchars($c['service'] ?? '') ?></td>
          <td><?= htmlspecialchars($c['city'] ?? '') ?></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($c['pipeline_status'] ?? '') ?></span></td>
          <td><span class="badge badge-<?= $c['status'] === 'new' ? 'danger' : 'success' ?>"><?= htmlspecialchars($c['status']) ?></span></td>
          <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
          <td>
            <a href="<?= $base ?>/admin/contacts/<?= (int)$c['id'] ?>" class="btn btn-xs btn-secondary"><i class="fas fa-eye"></i></a>
            <?php if (!$filters['archived']): ?>
            <form method="POST" action="<?= $base ?>/admin/contacts/archive" class="d-inline m-0">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="btn btn-xs btn-warning" title="Archiver"><i class="fas fa-archive"></i></button>
            </form>
            <?php else: ?>
            <form method="POST" action="<?= $base ?>/admin/contacts/restore" class="d-inline m-0">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="btn btn-xs btn-success" title="Restaurer"><i class="fas fa-trash-restore"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($contacts)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Aucun contact trouvé</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="card-footer">
    <nav>
      <ul class="pagination pagination-sm m-0">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($dir) ?>&search=<?= urlencode($filters['search']) ?>&pipeline=<?= urlencode($filters['pipeline']) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>
