<?php
require_once __DIR__ . '/partials/header.php';
requirePermission('forms');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pdo   = getPDO();
$forms = $pdo->query("SELECT f.*, (SELECT COUNT(*) FROM form_submissions s WHERE s.form_id = f.id) AS sub_count FROM forms f ORDER BY f.created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$fieldTypeIcons = [
    'text'=>'fa-font','email'=>'fa-at','tel'=>'fa-phone','number'=>'fa-hashtag',
    'select'=>'fa-list','textarea'=>'fa-align-left','checkbox'=>'fa-check-square','radio'=>'fa-dot-circle',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0"><i class="fas fa-clipboard-list mr-2"></i>Formulaires</h4>
  <a href="form-edit.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Nouveau formulaire</a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<?php if (empty($forms)): ?>
<div class="card">
  <div class="card-body text-center py-5 text-muted">
    <i class="fas fa-clipboard-list fa-3x mb-3 opacity-50"></i>
    <p>Aucun formulaire pour l'instant.</p>
    <a href="form-edit.php" class="btn btn-primary">Créer le premier formulaire</a>
  </div>
</div>
<?php else: ?>
<div class="row">
  <?php foreach ($forms as $form):
    $fields   = json_decode($form['fields'],   true) ?: [];
    $settings = json_decode($form['settings'], true) ?: [];
    $steps    = $fields['steps'] ?? [];
    $fieldCount = 0;
    foreach ($steps as $step) $fieldCount += count($step['fields'] ?? []);
    $stepCount  = count($steps);
    $unreadSubs = (int)$pdo->query("SELECT COUNT(*) FROM form_submissions WHERE form_id={$form['id']} AND is_read=0")->fetchColumn();
  ?>
  <div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100" style="border:1px solid rgba(255,255,255,.08);">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <h5 class="card-title mb-0" style="font-size:16px;"><?= htmlspecialchars($form['name']) ?></h5>
          <?php if ($form['is_active']): ?>
            <span class="badge badge-success">Actif</span>
          <?php else: ?>
            <span class="badge badge-secondary">Inactif</span>
          <?php endif; ?>
        </div>
        <div class="small text-muted mb-2"><code style="font-size:11px;">[form:<?= htmlspecialchars($form['slug']) ?>]</code></div>
        <?php if ($form['description']): ?>
          <p class="small text-muted mb-2"><?= htmlspecialchars($form['description']) ?></p>
        <?php endif; ?>

        <!-- Stats row -->
        <div class="d-flex gap-3 mb-3" style="gap:12px;">
          <div class="text-center">
            <div style="font-size:20px;font-weight:700;"><?= $fieldCount ?></div>
            <div class="small text-muted">champ<?= $fieldCount > 1 ? 's' : '' ?></div>
          </div>
          <div class="text-center">
            <div style="font-size:20px;font-weight:700;"><?= $stepCount ?></div>
            <div class="small text-muted">étape<?= $stepCount > 1 ? 's' : '' ?></div>
          </div>
          <div class="text-center">
            <div style="font-size:20px;font-weight:700;"><?= (int)$form['sub_count'] ?></div>
            <div class="small text-muted">soumission<?= $form['sub_count'] > 1 ? 's' : '' ?></div>
          </div>
        </div>

        <!-- Field type pills -->
        <div class="mb-3" style="display:flex;flex-wrap:wrap;gap:4px;">
          <?php foreach ($steps as $step): ?>
            <?php foreach ($step['fields'] ?? [] as $f):
              $ico = $fieldTypeIcons[$f['type'] ?? 'text'] ?? 'fa-font'; ?>
              <span class="badge badge-secondary" title="<?= htmlspecialchars($f['label'] ?? $f['name'] ?? '') ?>">
                <i class="fas <?= $ico ?> mr-1"></i><?= htmlspecialchars($f['label'] ?? $f['name'] ?? '?') ?>
              </span>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>

        <!-- Actions -->
        <div class="d-flex" style="gap:6px;flex-wrap:wrap;">
          <a href="form-edit.php?id=<?= $form['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-edit mr-1"></i>Modifier
          </a>
          <a href="form-submissions.php?form_id=<?= $form['id'] ?>" class="btn btn-sm btn-outline-info">
            <i class="fas fa-inbox mr-1"></i>Soumissions
            <?php if ($unreadSubs > 0): ?>
              <span class="badge badge-danger ml-1"><?= $unreadSubs ?></span>
            <?php endif; ?>
          </a>
          <button type="button" class="btn btn-sm btn-outline-warning"
                  onclick="duplicateForm(<?= $form['id'] ?>, '<?= addslashes(htmlspecialchars($form['name'])) ?>')"
                  title="Dupliquer">
            <i class="fas fa-copy"></i>
          </button>
          <?php if ($form['slug'] !== 'contact'): ?>
          <form method="POST" action="actions/form-delete.php" class="d-inline"
                onsubmit="return confirm('Supprimer ce formulaire et toutes ses soumissions ?')">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="form_id"    value="<?= $form['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
              <i class="fas fa-trash"></i>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer small text-muted">
        Créé le <?= date('d/m/Y', strtotime($form['created_at'])) ?>
        · Modifié le <?= date('d/m/Y', strtotime($form['updated_at'])) ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Duplicate modal -->
<div class="modal fade" id="modalDuplicate" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="background:#1a1d23;border:1px solid rgba(255,255,255,.12);">
      <form method="POST" action="actions/form-duplicate.php">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="source_id" id="dupSourceId">
        <div class="modal-header border-0">
          <h5 class="modal-title">Dupliquer le formulaire</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <label>Nom du nouveau formulaire</label>
          <input type="text" name="name" id="dupName" class="form-control" required placeholder="Copie de ...">
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Dupliquer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function duplicateForm(id, name){
  document.getElementById('dupSourceId').value = id;
  document.getElementById('dupName').value      = 'Copie de ' + name;
  $('#modalDuplicate').modal('show');
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
