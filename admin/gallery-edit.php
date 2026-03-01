<?php
require_once __DIR__ . '/auth.php';
requirePermission('galleries');
require_once __DIR__ . '/../includes/db.php';
$pdo = getPDO();
$csrf = getCsrfToken();

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$gal  = null;
$selected = []; // realisation ids already in this gallery

if ($id) {
    $gal = $pdo->prepare("SELECT * FROM galleries WHERE id = ?");
    $gal->execute([$id]);
    $gal = $gal->fetch();
    if (!$gal) { header('Location: galleries.php'); exit; }

    $rows = $pdo->prepare("SELECT realisation_id, sort_order FROM gallery_items WHERE gallery_id = ? ORDER BY sort_order ASC");
    $rows->execute([$id]);
    foreach ($rows->fetchAll() as $row) {
        $selected[$row['realisation_id']] = (int)$row['sort_order'];
    }
}

// All published réalisations
$realisations = $pdo->query("
    SELECT r.*, ri.image_path AS cover
    FROM realisations r
    LEFT JOIN realisation_images ri ON ri.id = (
        SELECT id FROM realisation_images WHERE realisation_id = r.id ORDER BY sort_order ASC LIMIT 1
    )
    ORDER BY r.sort_order ASC, r.created_at DESC
")->fetchAll();

$updated = isset($_GET['updated']);

include __DIR__ . '/partials/header.php';
?>

<?php if ($updated): ?>
  <div class="alert alert-success alert-dismissible fade show">
    Galerie enregistrée. <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">
      <i class="fas fa-layer-group mr-1"></i>
      <?= $id ? 'Éditer la galerie' : 'Nouvelle galerie' ?>
    </h3>
    <a href="galleries.php" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
  </div>

  <form method="post" action="actions/gallery-save.php" id="galleryForm">
    <input type="hidden" name="csrf"       value="<?= $csrf ?>">
    <input type="hidden" name="id"         value="<?= $id ?>">
    <!-- serialized order from JS -->
    <input type="hidden" name="item_order" id="itemOrder" value="">

    <div class="card-body">
      <!-- Info -->
      <div class="row mb-3">
        <div class="col-md-6">
          <div class="form-group">
            <label>Nom de la galerie <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="<?= htmlspecialchars($gal['name'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Ordre d'affichage</label>
            <input type="number" name="sort_order" class="form-control" min="0"
                   value="<?= (int)($gal['sort_order'] ?? 0) ?>">
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Par page <small class="text-muted">(pagination)</small></label>
            <input type="number" name="items_per_page" class="form-control" min="1" max="50"
                   value="<?= (int)($gal['items_per_page'] ?? 6) ?>">
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" class="form-control"
                   value="<?= htmlspecialchars($gal['description'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group mb-0">
            <div class="custom-control custom-switch mb-2">
              <input type="checkbox" class="custom-control-input" id="showGalleryHeader"
                     name="show_gallery_header" value="1"
                     <?= ($gal['show_gallery_header'] ?? 1) ? 'checked' : '' ?>>
              <label class="custom-control-label" for="showGalleryHeader">
                Afficher le <strong>titre</strong> et la <strong>description</strong> de la galerie
              </label>
            </div>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="showItemLabels"
                     name="show_item_labels" value="1"
                     <?= ($gal['show_item_labels'] ?? 1) ? 'checked' : '' ?>>
              <label class="custom-control-label" for="showItemLabels">
                Afficher le <strong>titre</strong> et la <strong>description</strong> de chaque réalisation
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Réalisations picker -->
      <h5 class="mb-2"><i class="fas fa-images mr-1"></i> Réalisations dans cette galerie</h5>
      <p class="text-muted small mb-3">Cochez les réalisations à inclure. Glissez-déposez pour réordonner.</p>

      <div id="realisationGrid" class="row">
        <?php foreach ($realisations as $r): ?>
          <?php
            $checked = isset($selected[$r['id']]);
            $covSrc  = !empty($r['cover'])
                ? BASE_URL . '/' . ltrim($r['cover'], '/')
                : 'https://via.placeholder.com/120x80?text=No+img';
          ?>
          <div class="col-6 col-md-3 col-lg-2 mb-3 real-item" data-id="<?= $r['id'] ?>">
            <div class="card real-card <?= $checked ? 'border-primary' : 'border-secondary' ?>"
                 style="cursor:pointer;border-width:2px;border-style:solid;border-radius:10px;overflow:hidden;"
                 onclick="toggleReal(this)">
              <img src="<?= $covSrc ?>"
                   style="width:100%;height:80px;object-fit:cover;">
              <div class="card-body p-1 text-center">
                <p class="mb-0" style="font-size:11px;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= htmlspecialchars($r['title']) ?>
                </p>
                <p class="mb-0 text-muted" style="font-size:10px;"><?= htmlspecialchars($r['city'] ?? '') ?></p>
              </div>
              <input type="checkbox" name="realisation_ids[]"
                     value="<?= $r['id'] ?>"
                     <?= $checked ? 'checked' : '' ?>
                     style="display:none;" class="real-check">
              <div class="real-badge" style="position:absolute;top:4px;right:4px;display:<?= $checked ? 'block' : 'none' ?>;">
                <span class="badge badge-primary"><i class="fas fa-check"></i></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div><!-- /.card-body -->

    <div class="card-footer d-flex" style="gap:8px;">
      <button type="submit" class="btn btn-success" onclick="serializeOrder()">
        <i class="fas fa-save"></i> Enregistrer
      </button>
      <a href="galleries.php" class="btn btn-secondary">
        <i class="fas fa-times"></i> Annuler
      </a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// Toggle selection
function toggleReal(card) {
  const check = card.querySelector('.real-check');
  const badge = card.querySelector('.real-badge');
  check.checked = !check.checked;
  if (check.checked) {
    card.style.borderColor = '#007bff';
    badge.style.display = 'block';
  } else {
    card.style.borderColor = '#6c757d';
    badge.style.display = 'none';
  }
}

// Sortable drag & drop on the grid
Sortable.create(document.getElementById('realisationGrid'), {
  animation: 150,
  handle: '.real-card',
  ghostClass: 'sortable-ghost'
});

// Before submit, serialize order of ALL items (checked or not – server will filter by checkbox names)
function serializeOrder() {
  const items = document.querySelectorAll('#realisationGrid .real-item');
  const order = Array.from(items).map(el => el.dataset.id);
  document.getElementById('itemOrder').value = order.join(',');
}
</script>
<style>
.sortable-ghost { opacity:.4; }
.real-card:hover { box-shadow:0 0 0 3px #007bff55; }
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
