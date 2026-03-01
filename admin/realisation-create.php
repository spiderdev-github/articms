<?php
require_once __DIR__ . '/auth.php';
requirePermission('realisations');

require_once __DIR__ . '/../includes/db.php';
$csrf = getCsrfToken();

$types = [
  '' => 'Choisir',
  'Peinture interieure' => 'Peinture interieure',
  'Peinture exterieure' => 'Peinture exterieure',
  'Crepi / Facade' => 'Crepi / Facade',
  'Isolation' => 'Isolation',
  'Mosaique effet pierre' => 'Mosaique effet pierre'
];

include __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title"><i class="fas fa-plus mr-1"></i> Ajouter une realisation</h3>
    <a class="btn btn-sm btn-secondary" href="realisations.php">Retour</a>
  </div>

  <div class="card-body">
    <?php if (isset($_GET['notice']) && $_GET['notice'] === 'invalid'): ?>
      <div class="alert alert-danger">Erreur: donnees invalides.</div>
    <?php endif; ?>

    <form method="POST" action="actions/realisation-save.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="id" value="0">

      <div class="form-group">
        <label>Titre *</label>
        <input type="text" name="title" class="form-control" required maxlength="190" placeholder="Ex: Peinture salon + plafond">
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Ville</label>
          <input type="text" name="city" class="form-control" maxlength="120" placeholder="Ex: Strasbourg">
        </div>

        <div class="form-group col-md-4">
          <label>Type</label>
          <select name="type" class="form-control">
            <?php foreach ($types as $k => $v): ?>
              <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-md-4">
          <label>Ordre (tri)</label>
          <input type="number" name="sort_order" class="form-control" value="0" min="0" step="1">
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Resume du chantier, finition, materiaux, delai..."></textarea>
      </div>

     

      <div class="form-row">
        <div class="form-group col-md-3">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="isPublished" name="is_published" value="1" checked>
            <label class="custom-control-label" for="isPublished">Publie</label>
          </div>
        </div>

        <div class="form-group col-md-3">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="isFeatured" name="is_featured" value="1">
            <label class="custom-control-label" for="isFeatured">Featured</label>
          </div>
        </div>
      </div>

      <button class="btn btn-primary" type="submit">
        <i class="fas fa-save"></i> Enregistrer
      </button>
    </form>
  </div>
</div>

<script>
$(function(){
  bsCustomFileInput && bsCustomFileInput.init && bsCustomFileInput.init();
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>