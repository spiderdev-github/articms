<?php
require_once __DIR__ . '/auth.php';
requirePermission('realisations');

require_once __DIR__ . '/../includes/db.php';
$pdo = getPDO();
$csrf = getCsrfToken();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: realisations.php?notice=invalid");
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM realisations WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();

if (!$item) {
  header("Location: realisations.php?notice=invalid");
  exit;
}

$types = [
  '' => 'Choisir',
  'Peinture interieure' => 'Peinture interieure',
  'Peinture exterieure' => 'Peinture exterieure',
  'Crepi / Facade' => 'Crepi / Facade',
  'Isolation' => 'Isolation',
  'Mosaique effet pierre' => 'Mosaique effet pierre'
];

$coverUrl = '';
if (!empty($item['cover_image'])) {
  $coverUrl = BASE_URL . '/' . ltrim($item['cover_image'], '/');
}

include __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title"><i class="fas fa-pen mr-1"></i> Modifier la realisation</h3>
    <a class="btn btn-sm btn-secondary" href="realisations.php">Retour</a>
  </div>

  <div class="card-body">
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success">Enregistre.</div>
    <?php endif; ?>

    <form method="POST" action="actions/realisation-save.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

      <div class="form-group">
        <label>Titre *</label>
        <input type="text" name="title" class="form-control" required maxlength="190"
               value="<?= htmlspecialchars($item['title']) ?>">
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Ville</label>
          <input type="text" name="city" class="form-control" maxlength="120"
                 value="<?= htmlspecialchars($item['city'] ?? '') ?>">
        </div>

        <div class="form-group col-md-4">
          <label>Type</label>
          <select name="type" class="form-control">
            <?php foreach ($types as $k => $v): ?>
              <option value="<?= htmlspecialchars($k) ?>" <?= (($item['type'] ?? '') === $k) ? 'selected' : '' ?>>
                <?= htmlspecialchars($v) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-md-4">
          <label>Ordre (tri)</label>
          <input type="number" name="sort_order" class="form-control"
                 value="<?= (int)($item['sort_order'] ?? 0) ?>" min="0" step="1">
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Image de couverture (remplacer)</label>

        <?php if ($coverUrl): ?>
          <div class="mb-2">
            <img src="<?= htmlspecialchars($coverUrl) ?>" style="max-width:260px;border-radius:12px;object-fit:cover;">
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="removeCover" name="remove_cover" value="1">
            <label class="custom-control-label" for="removeCover">Supprimer la couverture actuelle</label>
          </div>
        <?php endif; ?>

        <div class="custom-file">
          <input type="file" name="cover_image" class="custom-file-input" id="coverImage" accept=".jpg,.jpeg,.png,.webp">
          <label class="custom-file-label" for="coverImage">Choisir un fichier</label>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="isPublished" name="is_published" value="1" <?= !empty($item['is_published']) ? 'checked' : '' ?>>
            <label class="custom-control-label" for="isPublished">Publie</label>
          </div>
        </div>

        <div class="form-group col-md-3">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="isFeatured" name="is_featured" value="1" <?= !empty($item['is_featured']) ? 'checked' : '' ?>>
            <label class="custom-control-label" for="isFeatured">Featured</label>
          </div>
        </div>
      </div>

      <button class="btn btn-primary" type="submit">
        <i class="fas fa-save"></i> Enregistrer
      </button>
      <a class="btn btn-secondary ml-2" href="realisations.php">Retour liste</a>
    </form>
    <hr>

    <h5><i class="fas fa-images mr-1"></i> Galerie images</h5>

    <form method="POST" action="actions/realisation-upload-images.php" enctype="multipart/form-data" class="mb-3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

    <div class="form-group">
        <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp" class="form-control">
        <small class="text-muted">Vous pouvez sélectionner plusieurs images.</small>
    </div>

    <button class="btn btn-primary btn-sm">
        <i class="fas fa-upload"></i> Upload images
    </button>
    </form>

    <?php
    $imgs = $pdo->prepare("
    SELECT *
    FROM realisation_images
    WHERE realisation_id = ?
    ORDER BY sort_order ASC, id ASC
    ");
    $imgs->execute([$item['id']]);
    $imgs = $imgs->fetchAll();

    $currentCover = $item['cover_image'] ?? '';
    ?>

    <div class="row mt-3" id="sortable-gallery">
      <?php foreach ($imgs as $img): ?>
        <?php
          $imgUrl = BASE_URL . '/' . $img['image_path'];
          $isCover = ($img['image_path'] === $currentCover);
        ?>
        <div class="col-md-3 mb-3 sortable-item"
            data-id="<?= (int)$img['id'] ?>">

          <div class="card p-2 text-center position-relative">

            <?php if ($isCover): ?>
              <span class="badge badge-warning"
                    style="position:absolute;top:8px;left:8px;">
                ⭐ Cover
              </span>
            <?php endif; ?>

            <img src="<?= $imgUrl ?>"
                class="gallery-img"
                data-id="<?= (int)$img['id'] ?>"
                style="width:100%;height:140px;object-fit:cover;border-radius:10px;cursor:pointer;">

            <div class="mt-2">

              <?php if (!$isCover): ?>
                <button class="btn btn-danger btn-sm btn-block delete-btn"
                        data-id="<?= (int)$img['id'] ?>">
                  Supprimer
                </button>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm btn-block" disabled>
                  Image cover
                </button>
              <?php endif; ?>

            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  if (window.bsCustomFileInput) {
    bsCustomFileInput.init();
  }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
  
const gallery = document.getElementById('sortable-gallery');

if(gallery){


  document.addEventListener('DOMContentLoaded', function(){

    const gallery = document.getElementById('sortable-gallery');

    if(gallery){

      new Sortable(gallery, {
        animation: 150,
        onEnd: function () {
          let order = [];
          document.querySelectorAll('.sortable-item').forEach((el, index)=>{
            order.push({
              id: el.dataset.id,
              position: index
            });
          });

          fetch('actions/realisation-image-sort.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(order)
          });
        }
      });

      document.querySelectorAll('.gallery-img').forEach(img=>{
        img.addEventListener('dblclick', function(){
          fetch('actions/realisation-set-cover-ajax.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({image_id: this.dataset.id})
          })
          .then(res => {
            if(!res.ok) throw new Error();
            return res.json();
          })
          .then(data => {
            if(data.success){
              location.reload();
            }
          })
          .catch(()=> alert('Erreur serveur'));
        });
      });

    }
  });

  // DOUBLE CLICK = SET COVER
  document.querySelectorAll('.gallery-img').forEach(img=>{
    img.addEventListener('dblclick', function(){
      fetch('actions/realisation-set-cover-ajax.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({image_id: this.dataset.id})
      }).then(res => res.json())
        .then(data => {
            if(data.success){
                location.reload();
            }
        });
    });
  });

  // DELETE
  document.querySelectorAll('.delete-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
      if(confirm('Supprimer cette image ?')){
        fetch('actions/realisation-image-delete-ajax.php',{
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({image_id: this.dataset.id})
        }).then(()=> location.reload());
      }
    });
  });

}
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>