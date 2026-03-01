<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requirePermission('contacts');

$pdo = getPDO();
$csrf = getCsrfToken();

$search = trim($_GET['search'] ?? '');
$pipeline = $_GET['pipeline'] ?? '';
$tag = trim($_GET['tag'] ?? '');
$archived = ($_GET['archived'] ?? '0') === '1';
$due = ($_GET['due'] ?? '0') === '1';

// ===== SORTING =====
$sort = $_GET['sort'] ?? 'created_at';
$dir  = strtolower($_GET['dir'] ?? 'desc');

$allowedSort = [
    'created_at' => 'c.created_at',
    'name' => 'c.name',
    'city' => 'c.city',
    'pipeline_status' => 'c.pipeline_status',
    'next_followup_at' => 'c.next_followup_at'
];

if (!array_key_exists($sort, $allowedSort)) {
    $sort = 'created_at';
}

if (!in_array($dir, ['asc', 'desc'], true)) {
    $dir = 'desc';
}

$orderSql = $allowedSort[$sort] . ' ' . strtoupper($dir);

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$allowedPipeline = ['new', 'in_progress', 'quoted', 'won', 'lost'];
if (!in_array($pipeline, $allowedPipeline, true)) {
    $pipeline = '';
}

$where = [];
$params = [];

if ($archived) {
    $where[] = "c.archived_at IS NOT NULL";
} else {
    $where[] = "c.archived_at IS NULL";
}

if ($due) {
    $where[] = "c.next_followup_at IS NOT NULL AND c.next_followup_at <= NOW()";
}

if ($pipeline !== '') {
    $where[] = "c.pipeline_status = :pipeline";
    $params[':pipeline'] = $pipeline;
}

if ($search !== '') {
    $where[] = "(c.name LIKE :search OR c.email LIKE :search OR c.city LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($tag !== '') {
    $where[] = "EXISTS (
        SELECT 1
        FROM contact_tags ct2
        INNER JOIN tags t2 ON t2.id = ct2.tag_id
        WHERE ct2.contact_id = c.id AND t2.name = :tag
    )";
    $params[':tag'] = strtolower($tag);
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

/* Total */
$stmtTotal = $pdo->prepare("
    SELECT COUNT(DISTINCT c.id)
    FROM contacts c
    $whereSql
");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));

/* Rows */
$sql = "
    SELECT
        c.*,
        GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ',') AS tags
    FROM contacts c
    LEFT JOIN contact_tags ct ON ct.contact_id = c.id
    LEFT JOIN tags t ON t.id = ct.tag_id
    $whereSql
    GROUP BY c.id
    ORDER BY $orderSql
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$contacts = $stmt->fetchAll();

function badgeClassForPipeline($s) {
    if ($s === 'won') return 'badge-success';
    if ($s === 'lost') return 'badge-danger';
    if ($s === 'quoted') return 'badge-info';
    if ($s === 'in_progress') return 'badge-warning';
    return 'badge-secondary';
}

function sortLink($column, $label, $currentSort, $currentDir) {

    $dir = 'asc';
    if ($currentSort === $column && $currentDir === 'asc') {
        $dir = 'desc';
    }

    // Icon logic (always show something)
    $iconHtml = '<span class="sort-icon"><i class="fas fa-sort muted"></i></span>';

    if ($currentSort === $column) {
        if ($currentDir === 'asc') {
            $iconHtml = '<span class="sort-icon"><i class="fas fa-sort-up"></i></span>';
        } else {
            $iconHtml = '<span class="sort-icon"><i class="fas fa-sort-down"></i></span>';
        }
    }

    $params = $_GET;
    $params['sort'] = $column;
    $params['dir'] = $dir;
    $params['page'] = 1;

    $url = 'contacts.php?' . http_build_query($params);

    return '<a class="sort-link" href="' . htmlspecialchars($url) . '" style="display:inline-flex; align-items:center; gap:4px;">'
        . '<span>' . htmlspecialchars($label) . '</span>'
        . $iconHtml
        . '</a>';
}

function buildUrl($overrides = []) {
    $base = [
        'search' => $_GET['search'] ?? '',
        'pipeline' => $_GET['pipeline'] ?? '',
        'tag' => $_GET['tag'] ?? '',
        'archived' => $_GET['archived'] ?? '0',
        'due' => $_GET['due'] ?? '0',
        'page' => $_GET['page'] ?? 1,
        'sort' => $_GET['sort'] ?? 'created_at',
        'dir' => $_GET['dir'] ?? 'desc'
    ];
    foreach ($overrides as $k => $v) {
        $base[$k] = $v;
    }
    foreach ($base as $k => $v) {
        if ($v === '' || $v === null) unset($base[$k]);
    }
    return 'contacts.php?' . http_build_query($base);
}

include __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
    <h3 class="card-title m-0">
      <i class="fas fa-envelope mr-1"></i> Contacts
      <span class="badge badge-light ml-2"><?= (int)$total ?></span>
    </h3>

    <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
      <a href="export-csv.php" class="btn btn-sm btn-success">
        <i class="fas fa-file-csv"></i> Export CSV
      </a>

      <a href="<?= buildUrl(['archived' => '0', 'page' => 1]) ?>" class="btn btn-sm <?= !$archived ? 'btn-primary' : 'btn-secondary' ?>">
        Actifs
      </a>
      <a href="<?= buildUrl(['archived' => '1', 'page' => 1]) ?>" class="btn btn-sm <?= $archived ? 'btn-primary' : 'btn-secondary' ?>">
        Archives
      </a>

      <a href="<?= buildUrl(['due' => $due ? '0' : '1', 'page' => 1]) ?>" class="btn btn-sm <?= $due ? 'btn-warning' : 'btn-secondary' ?>">
        Relances dues
      </a>
    </div>
  </div>

  <div class="card-body">
    <form method="GET" class="row">
      <div class="col-md-4 mb-2">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Recherche: nom, email, ville"
               value="<?= htmlspecialchars($search) ?>">
      </div>

      <div class="col-md-3 mb-2">
        <select name="pipeline" class="form-control form-control-sm">
          <option value="">Pipeline: tous</option>
          <?php foreach (['new','in_progress','quoted','won','lost'] as $p): ?>
            <option value="<?= $p ?>" <?= $pipeline === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 mb-2">
        <input type="text" name="tag" class="form-control form-control-sm"
               placeholder="Tag exact (ex: urgent)"
               value="<?= htmlspecialchars($tag) ?>">
      </div>

      <div class="col-md-2 mb-2 d-flex" style="gap:8px;">
        <input type="hidden" name="archived" value="<?= $archived ? '1' : '0' ?>">
        <input type="hidden" name="due" value="<?= $due ? '1' : '0' ?>">
        <button class="btn btn-sm btn-primary" type="submit">
          <i class="fas fa-magnifying-glass"></i>
        </button>
        <a class="btn btn-sm btn-secondary" href="contacts.php">
          <i class="fas fa-rotate-left"></i>
        </a>
      </div>
    </form>
  </div>

  <div class="card-body table-responsive p-0">
    <table class="table table-hover text-sm">
      <thead>
        <tr>
          <th class="sortable"><?= sortLink('created_at','Date',$sort,$dir) ?></th>
          <th class="sortable"><?= sortLink('name','Nom',$sort,$dir) ?></th>
          <th class="sortable"><?= sortLink('city','Ville',$sort,$dir) ?></th>
          <th class="sortable"><?= sortLink('pipeline_status','Pipeline',$sort,$dir) ?></th>
          <th>Tags</th>
          <th class="sortable"><?= sortLink('next_followup_at','Relance',$sort,$dir) ?></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($contacts)): ?>
        <tr><td colspan="7" class="text-muted p-3">Aucun resultat.</td></tr>
      <?php endif; ?>

      <?php foreach ($contacts as $c): ?>
        <?php
          $p = $c['pipeline_status'] ?? 'new';
          $badge = badgeClassForPipeline($p);
          $tagsStr = $c['tags'] ?? '';
          $tagsArr = $tagsStr !== '' ? explode(',', $tagsStr) : [];
          $followup = $c['next_followup_at'] ?? null;
          $isDue = $followup && (strtotime($followup) <= time());
        ?>
        <tr>
          <td><?= htmlspecialchars($c['created_at']) ?></td>

          <td>
            <div class="font-weight-bold"><?= htmlspecialchars($c['name']) ?></div>
            <div class="text-muted">
              <?= htmlspecialchars($c['email']) ?>
              <?php if (!empty($c['phone'])): ?> · <?= htmlspecialchars($c['phone']) ?><?php endif; ?>
            </div>
          </td>

          <td><?= htmlspecialchars($c['city'] ?? '') ?></td>

          <td>
            <span class="badge <?= $badge ?>"><?= htmlspecialchars($p) ?></span>
          </td>

          <td>
            <?php if (empty($tagsArr)): ?>
              <span class="text-muted">-</span>
            <?php else: ?>
              <?php foreach ($tagsArr as $t): ?>
                <span class="badge badge-info mr-1"><?= htmlspecialchars(trim($t)) ?></span>
              <?php endforeach; ?>
            <?php endif; ?>
          </td>

          <td>
            <?php if (!$followup): ?>
              <span class="text-muted">-</span>
            <?php else: ?>
              <span class="badge <?= $isDue ? 'badge-warning' : 'badge-secondary' ?>">
                <?= htmlspecialchars($followup) ?>
              </span>
            <?php endif; ?>
          </td>

          <td class="d-flex flex-wrap" style="gap:6px;">
            <a href="contact-view.php?id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm">
              <i class="fas fa-eye"></i>
            </a>

            <form method="POST" action="actions/update-pipeline.php" class="m-0">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <select name="pipeline_status" class="form-control form-control-sm"
                      onchange="this.form.submit()"
                      style="width:140px; display:inline-block;">
                <?php foreach (['new','in_progress','quoted','won','lost'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= $p === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </form>

            <?php if (empty($c['archived_at'])): ?>
              <form method="POST" action="actions/archive.php" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-secondary btn-sm" type="submit" title="archive">
                  <i class="fas fa-box-archive"></i>
                </button>
              </form>
            <?php else: ?>
              <form method="POST" action="actions/restore.php" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-info btn-sm" type="submit" title="restore">
                  <i class="fas fa-rotate-left"></i>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
    <div class="text-muted text-sm">
      Page <?= (int)$page ?> / <?= (int)$totalPages ?>
    </div>

    <nav>
      <ul class="pagination pagination-sm m-0">
        <?php
          $start = max(1, $page - 3);
          $end = min($totalPages, $page + 3);
        ?>

        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page <= 1 ? '#' : buildUrl(['page' => $page - 1]) ?>">&laquo;</a>
        </li>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page >= $totalPages ? '#' : buildUrl(['page' => $page + 1]) ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>