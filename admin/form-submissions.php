<?php
require_once __DIR__ . '/partials/header.php';
requirePermission('forms');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pdo    = getPDO();
$formId = (int)($_GET['form_id'] ?? 0);

if (!$formId) { header('Location: forms.php'); exit; }

$form = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$form->execute([$formId]);
$formRow = $form->fetch(PDO::FETCH_ASSOC);
if (!$formRow) { header('Location: forms.php'); exit; }

$formFields   = json_decode($formRow['fields'],   true) ?: [];
$formSettings = json_decode($formRow['settings'], true) ?: [];
$steps        = $formFields['steps'] ?? [];

// Flat ordered list of field definitions for table headers
$fieldDefs = [];
foreach ($steps as $step) {
    foreach ($step['fields'] ?? [] as $f) {
        $fieldDefs[] = $f;
    }
}

// Filters
$onlyUnread = isset($_GET['unread']);
$search     = trim($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['p'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

// Mark all as read when viewing
if (!$onlyUnread) {
    $pdo->prepare("UPDATE form_submissions SET is_read=1 WHERE form_id=? AND is_read=0")->execute([$formId]);
}

// Count
$countSql = "SELECT COUNT(*) FROM form_submissions WHERE form_id=?";
$countSql .= $onlyUnread ? " AND is_read=0" : "";
$totalSubs = (int)$pdo->prepare($countSql)->execute([$formId]) ? $pdo->prepare($countSql)->execute([$formId]) : 0;
$cStmt = $pdo->prepare($countSql);
$cStmt->execute($onlyUnread ? [$formId] : [$formId]);
$totalSubs = (int)$cStmt->fetchColumn();
$totalPages = max(1, ceil($totalSubs / $perPage));

// Fetch
$sql  = "SELECT * FROM form_submissions WHERE form_id=?";
$sql .= $onlyUnread ? " AND is_read=0" : "";
$sql .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$sStmt = $pdo->prepare($sql);
$sStmt->execute([$formId]);
$subs = $sStmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">
    <i class="fas fa-inbox mr-2"></i>
    Soumissions — <?= htmlspecialchars($formRow['name']) ?>
  </h4>
  <div class="d-flex" style="gap:8px;">
    <a href="form-edit.php?id=<?= $formId ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-edit mr-1"></i>Modifier le formulaire
    </a>
    <a href="forms.php" class="btn btn-secondary btn-sm">
      <i class="fas fa-arrow-left mr-1"></i>Retour
    </a>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success' ? 'success' : 'danger' ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<!-- Stats + filters -->
<div class="d-flex align-items-center mb-3" style="gap:12px;flex-wrap:wrap;">
  <div class="small text-muted">
    <strong><?= $totalSubs ?></strong> soumission<?= $totalSubs > 1 ? 's' : '' ?>
    <?php
    $unreadCStmt = $pdo->prepare("SELECT COUNT(*) FROM form_submissions WHERE form_id=? AND is_read=0");
    $unreadCStmt->execute([$formId]);
    $unreadCount = (int)$unreadCStmt->fetchColumn();
    if ($unreadCount > 0): ?>
      · <span class="badge badge-danger"><?= $unreadCount ?> non lu<?= $unreadCount > 1 ? 's' : '' ?></span>
    <?php endif; ?>
  </div>
  <div class="ml-auto d-flex" style="gap:8px;">
    <?php if ($onlyUnread): ?>
      <a href="?form_id=<?= $formId ?>" class="btn btn-sm btn-outline-secondary">Voir tout</a>
    <?php elseif ($unreadCount > 0): ?>
      <a href="?form_id=<?= $formId ?>&unread=1" class="btn btn-sm btn-outline-warning">Non lus seulement</a>
    <?php endif; ?>
    <a href="actions/form-export-csv.php?form_id=<?= $formId ?>&csrf=<?= $csrf ?>" class="btn btn-sm btn-outline-info">
      <i class="fas fa-download mr-1"></i>Exporter CSV
    </a>
    <form method="POST" action="actions/form-clear-submissions.php" class="d-inline"
          onsubmit="return confirm('Supprimer toutes les soumissions de ce formulaire ?')">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="form_id"    value="<?= $formId ?>">
      <button type="submit" class="btn btn-sm btn-outline-danger">
        <i class="fas fa-trash mr-1"></i>Tout supprimer
      </button>
    </form>
  </div>
</div>

<?php if (empty($subs)): ?>
<div class="card">
  <div class="card-body text-center py-5 text-muted">
    <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
    <p>Aucune soumission<?= $onlyUnread ? ' non lue' : '' ?> pour ce formulaire.</p>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-dark table-hover table-sm mb-0">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <?php foreach ($fieldDefs as $f): ?>
            <th><?= htmlspecialchars($f['label'] ?? $f['name']) ?></th>
          <?php endforeach; ?>
          <th>Date</th>
          <th>IP</th>
          <th style="width:60px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subs as $sub):
          $data = json_decode($sub['data'], true) ?: [];
          $isNew = !$sub['is_read'];
        ?>
          <tr class="<?= $isNew ? 'table-warning' : '' ?>" style="cursor:pointer;" onclick="toggleDetail('sub<?= $sub['id'] ?>')">
            <td><small><?= $sub['id'] ?><?= $isNew ? ' <span class="badge badge-danger" style="font-size:9px;">NEW</span>' : '' ?></small></td>
            <?php foreach ($fieldDefs as $f):
              $val = $data[$f['name']] ?? '';
              $preview = mb_strlen($val) > 40 ? mb_substr($val,0,38).'…' : $val;
            ?>
              <td><small title="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($preview) ?></small></td>
            <?php endforeach; ?>
            <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></small></td>
            <td><small class="text-muted"><?= htmlspecialchars($sub['ip'] ?? '') ?></small></td>
            <td>
              <form method="POST" action="actions/form-submission-delete.php" class="d-inline"
                    onclick="event.stopPropagation();"
                    onsubmit="return confirm('Supprimer cette soumission ?')">
                <input type="hidden" name="csrf_token"     value="<?= $csrf ?>">
                <input type="hidden" name="submission_id"  value="<?= $sub['id'] ?>">
                <input type="hidden" name="form_id"        value="<?= $formId ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <!-- Detail row -->
          <tr id="sub<?= $sub['id'] ?>" style="display:none;">
            <td colspan="<?= count($fieldDefs)+4 ?>" style="background:rgba(255,255,255,.03);">
              <div class="p-3">
                <div class="row">
                  <?php foreach ($fieldDefs as $f):
                    $val = $data[$f['name']] ?? '—';
                  ?>
                  <div class="col-md-6 mb-2">
                    <div class="small text-muted"><?= htmlspecialchars($f['label'] ?? $f['name']) ?></div>
                    <div><?= nl2br(htmlspecialchars($val)) ?></div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <div class="small text-muted mt-2">
                  Soumis le <?= date('d/m/Y à H:i:s', strtotime($sub['created_at'])) ?>
                  — IP: <?= htmlspecialchars($sub['ip']??'') ?>
                  <?php if ($sub['user_agent']): ?>
                    — <span title="<?= htmlspecialchars($sub['user_agent']) ?>">UA connu</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-center mt-3">
  <ul class="pagination pagination-sm">
    <?php for ($pi = 1; $pi <= $totalPages; $pi++): ?>
      <li class="page-item <?= $pi===$page?'active':'' ?>">
        <a class="page-link" href="?form_id=<?= $formId ?>&p=<?= $pi ?><?= $onlyUnread?'&unread=1':'' ?>">
          <?= $pi ?>
        </a>
      </li>
    <?php endfor; ?>
  </ul>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
function toggleDetail(id){
  var row = document.getElementById(id);
  if(row) row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
