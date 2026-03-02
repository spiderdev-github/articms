<?php
require_once __DIR__ . '/auth.php';
requirePermission('realisations');

require_once __DIR__ . '/../includes/db.php';
$pdo  = getPDO();
$csrf = getCsrfToken();

// ── Recherche & tri ─────────────────────────────────
$search        = trim($_GET['q'] ?? '');
$filterPublish = $_GET['published'] ?? '';
$filterFeatured= $_GET['featured'] ?? '';
$sort          = $_GET['sort'] ?? '';
$dir           = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$allowedSorts = [
    'title'        => 'title',
    'city'         => 'city',
    'type'         => 'type',
    'is_published' => 'is_published',
    'is_featured'  => 'is_featured',
    'ordre'        => 'sort_order',
    'date'         => 'created_at',
];
$sortSql = isset($allowedSorts[$sort]) ? $allowedSorts[$sort] . ' ' . $dir : 'sort_order ASC, created_at DESC';

$where  = ['1=1'];
$params = [];
if ($search !== '') {
    $where[]  = '(title LIKE ? OR city LIKE ? OR type LIKE ?)';
    $term     = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}
if ($filterPublish === '1')  { $where[] = 'is_published = 1'; }
if ($filterPublish === '0')  { $where[] = 'is_published = 0'; }
if ($filterFeatured === '1') { $where[] = 'is_featured = 1'; }
$whereStr = implode(' AND ', $where);

$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM realisations")->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM realisations WHERE $whereStr ORDER BY $sortSql");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$isFiltering = $search !== '' || $filterPublish !== '' || $filterFeatured !== '';

function realSortLink(string $col, string $label, string $cur, string $dir): string {
    $next   = ($cur === $col && $dir === 'asc') ? 'desc' : 'asc';
    $params = array_merge($_GET, ['sort' => $col, 'dir' => $next]);
    $url    = 'realisations.php?' . http_build_query($params);
    $icon   = $cur === $col
        ? '<i class="fas fa-sort-' . ($dir === 'asc' ? 'up' : 'down') . ' ml-1"></i>'
        : '<i class="fas fa-sort ml-1 text-muted"></i>';
    return '<a href="' . htmlspecialchars($url) . '" class="text-reset text-decoration-none" style="white-space:nowrap;">' . $label . $icon . '</a>';
}

include __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0"><i class="fas fa-images mr-2"></i>Réalisations</h4>
  <a class="btn btn-primary btn-sm" href="realisation-create.php"><i class="fas fa-plus mr-1"></i> Nouvelle réalisation</a>
</div>

<!-- Filtres -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="form-inline" style="gap:8px;flex-wrap:wrap;">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Titre, ville, type…" value="<?= htmlspecialchars($search) ?>" style="min-width:220px;">
      <select name="published" class="form-control form-control-sm">
        <option value="">Tous statuts</option>
        <option value="1" <?= $filterPublish === '1' ? 'selected' : '' ?>>Publié</option>
        <option value="0" <?= $filterPublish === '0' ? 'selected' : '' ?>>Non publié</option>
      </select>
      <select name="featured" class="form-control form-control-sm">
        <option value="">Tous</option>
        <option value="1" <?= $filterFeatured === '1' ? 'selected' : '' ?>>Mis en avant</option>
      </select>
      <button class="btn btn-sm btn-secondary" type="submit"><i class="fas fa-search mr-1"></i>Filtrer</button>
      <?php if ($isFiltering): ?>
        <a href="realisations.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="m-0">
      <?= count($rows) ?> réalisation<?= count($rows) > 1 ? 's' : '' ?>
      <?php if ($isFiltering): ?>
        <small class="text-muted font-weight-normal">sur <?= $totalCount ?> au total</small>
      <?php endif; ?>
    </h5>
  </div>
  <div class="card-body p-0">
    <?php if (empty($rows)): ?>
      <div class="p-4 text-center text-muted">
        <i class="fas fa-images fa-2x mb-2 d-block"></i>
        Aucune réalisation<?= $isFiltering ? ' pour cette recherche' : '' ?>.
        <?php if (!$isFiltering): ?><br><a href="realisation-create.php">Créer la première réalisation</a><?php endif; ?>
      </div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="thead-dark">
        <tr>
          <th><?= realSortLink('ordre',        'Ordre',    $sort, $dir) ?></th>
          <th>Couverture</th>
          <th><?= realSortLink('title',        'Titre',    $sort, $dir) ?></th>
          <th><?= realSortLink('city',         'Ville',    $sort, $dir) ?></th>
          <th><?= realSortLink('type',         'Type',     $sort, $dir) ?></th>
          <th><?= realSortLink('is_published', 'Publié',   $sort, $dir) ?></th>
          <th><?= realSortLink('is_featured',  'Featured', $sort, $dir) ?></th>
          <th><?= realSortLink('date',         'Date',     $sort, $dir) ?></th>
          <th style="width:130px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="align-middle"><?= (int)$r['sort_order'] ?></td>
            <td class="align-middle">
              <?php if (!empty($r['cover_image'])): ?>
                <img src="<?= BASE_URL . '/' . ltrim($r['cover_image'],'/') ?>" style="width:70px;height:46px;object-fit:cover;border-radius:8px;">
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="align-middle font-weight-bold"><?= htmlspecialchars($r['title']) ?></td>
            <td class="align-middle"><?= htmlspecialchars($r['city'] ?? '') ?></td>
            <td class="align-middle"><?= htmlspecialchars($r['type'] ?? '') ?></td>
            <td class="align-middle">
              <span class="badge <?= $r['is_published'] ? 'badge-success' : 'badge-secondary' ?>">
                <?= $r['is_published'] ? 'Publié' : 'Brouillon' ?>
              </span>
            </td>
            <td class="align-middle">
              <span class="badge <?= $r['is_featured'] ? 'badge-warning' : 'badge-secondary' ?>">
                <?= $r['is_featured'] ? 'Oui' : 'Non' ?>
              </span>
            </td>
            <td class="align-middle"><small><?= date('d/m/Y', strtotime($r['created_at'])) ?></small></td>
            <td class="align-middle text-right" style="white-space:nowrap;">
              <a class="btn btn-xs btn-outline-primary" href="realisation-edit.php?id=<?= (int)$r['id'] ?>" title="Modifier"><i class="fas fa-edit"></i></a>

              <form method="POST" action="actions/realisation-toggle.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="field" value="is_published">
                <button class="btn btn-xs ml-1 btn-<?= $r['is_published'] ? 'success' : 'secondary' ?>" type="submit" title="Toggle publié">
                  <i class="fas fa-toggle-on"></i>
                </button>
              </form>

              <form method="POST" action="actions/realisation-toggle.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="field" value="is_featured">
                <button class="btn btn-xs ml-1 btn-<?= $r['is_featured'] ? 'warning' : 'secondary' ?>" type="submit" title="Toggle featured">
                  <i class="fas fa-star"></i>
                </button>
              </form>

              <form method="POST" action="actions/realisation-delete.php" class="d-inline" onsubmit="return confirm('Supprimer cette réalisation ?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-xs btn-outline-danger ml-1" type="submit" title="Supprimer"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

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