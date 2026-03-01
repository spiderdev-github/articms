<?php
require_once __DIR__ . '/auth.php';
requirePermission('realisations');

require_once __DIR__ . '/../includes/settings.php';
$csrf = getCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!verifyCsrfToken($token)) {
    header("Location: realisations-settings.php?notice=csrf"); exit;
  }

  setSetting('realisations_h1', trim($_POST['realisations_h1'] ?? ''));
  setSetting('realisations_intro', trim($_POST['realisations_intro'] ?? ''));
  setSetting('realisations_cta_text', trim($_POST['realisations_cta_text'] ?? ''));
  setSetting('realisations_cta_link', trim($_POST['realisations_cta_link'] ?? ''));
  setSetting('realisations_meta_title', trim($_POST['realisations_meta_title'] ?? ''));
  setSetting('realisations_meta_desc', trim($_POST['realisations_meta_desc'] ?? ''));
  setSetting('realisations_per_page',max(1, (int)($_POST['realisations_per_page'] ?? 6)));

  setSetting('realisations_before_after_enabled',
    isset($_POST['realisations_before_after_enabled']) ? 1 : 0
  );

  setSetting('realisations_before_after_title', $_POST['realisations_before_after_title'] ?? '');
  setSetting('realisations_before_after_subtitle', $_POST['realisations_before_after_subtitle'] ?? '');
  setSetting('realisations_before_after_label', $_POST['realisations_before_after_label'] ?? '');
  setSetting('realisations_before_after_block1_title', $_POST['realisations_before_after_block1_title'] ?? '');
  setSetting('realisations_before_after_block1_text', $_POST['realisations_before_after_block1_text'] ?? '');
  setSetting('realisations_before_after_block2_title', $_POST['realisations_before_after_block2_title'] ?? '');
  setSetting('realisations_before_after_block2_text', $_POST['realisations_before_after_block2_text'] ?? '');

  /* ================================
    UPLOAD IMAGES AVANT / APRES
  ================================ */

  $projectRoot = realpath(__DIR__ . '/../');
  $uploadDir = $projectRoot . '/assets/images/realisations';

  if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
  }

  function convertToWebP($tmpFile, $destination){
      $info = getimagesize($tmpFile);
      if(!$info) return false;

      switch($info['mime']){
          case 'image/jpeg': $img = imagecreatefromjpeg($tmpFile); break;
          case 'image/png':  $img = imagecreatefrompng($tmpFile); break;
          case 'image/webp': $img = imagecreatefromwebp($tmpFile); break;
          default: return false;
      }

      imagewebp($img, $destination, 80);
      imagedestroy($img);
      return true;
  }

  /* IMAGE AVANT */
  if (!empty($_FILES['before_image']['tmp_name'])) {

      $fileName = 'before_' . time() . '.webp';
      $absPath = $uploadDir . '/' . $fileName;
      $relPath = 'assets/images/realisations/' . $fileName;

      if (convertToWebP($_FILES['before_image']['tmp_name'], $absPath)) {
          setSetting('realisations_before_after_image_before', $relPath);
      }
  }

  /* IMAGE APRES */
  if (!empty($_FILES['after_image']['tmp_name'])) {

      $fileName = 'after_' . time() . '.webp';
      $absPath = $uploadDir . '/' . $fileName;
      $relPath = 'assets/images/realisations/' . $fileName;

      if (convertToWebP($_FILES['after_image']['tmp_name'], $absPath)) {
          setSetting('realisations_before_after_image_after', $relPath);
      }
  }
  
  header("Location: realisations-settings.php?updated=1"); exit;
}

$h1 = getSetting('realisations_h1');
$intro = getSetting('realisations_intro');
$ctaText = getSetting('realisations_cta_text');
$ctaLink = getSetting('realisations_cta_link');
$metaTitle = getSetting('realisations_meta_title');
$metaDesc = getSetting('realisations_meta_desc');

include __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title"><i class="fas fa-sliders-h mr-1"></i> Parametrage page Realisations</h3>
    <a class="btn btn-sm btn-secondary" href="realisations.php">Retour</a>
  </div>

  <div class="card-body">
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success">Parametres enregistres.</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="form-group">
        <label>H1</label>
        <input class="form-control" name="realisations_h1" value="<?= htmlspecialchars($h1) ?>" required>
      </div>

      <div class="form-group">
        <label>Intro</label>
        <textarea class="form-control" name="realisations_intro" rows="3" required><?= htmlspecialchars($intro) ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label>CTA texte</label>
          <input class="form-control" name="realisations_cta_text" value="<?= htmlspecialchars($ctaText) ?>">
        </div>
        <div class="form-group col-md-6">
          <label>CTA lien</label>
          <input class="form-control" name="realisations_cta_link" value="<?= htmlspecialchars($ctaLink) ?>">
        </div>
      </div>

      <hr>

      <div class="form-group">
        <label>Meta title</label>
        <input class="form-control" name="realisations_meta_title" value="<?= htmlspecialchars($metaTitle) ?>">
      </div>

      <div class="form-group">
        <label>Meta description</label>
        <textarea class="form-control" name="realisations_meta_desc" rows="2"><?= htmlspecialchars($metaDesc) ?></textarea>
      </div>

      <div class="form-group">
      <label>Nombre de realisations par page</label>
      <input type="number"
            name="realisations_per_page"
            class="form-control"
            min="1"
            max="50"
            value="<?= htmlspecialchars(getSetting('realisations_per_page', 6)) ?>">
      </div>

      <hr>
      <h4 class="mt-4">Section Avant / Apres</h4>

      <div class="form-group">
        <div class="custom-control custom-switch">
          <input type="checkbox"
                class="custom-control-input"
                id="beforeAfterEnabled"
                name="realisations_before_after_enabled"
                value="1"
                <?= getSetting('realisations_before_after_enabled',1) ? 'checked' : '' ?>>
          <label class="custom-control-label" for="beforeAfterEnabled">
            Activer la section
          </label>
        </div>
      </div>

      <div class="form-group">
        <label>Titre</label>
        <input class="form-control"
              name="realisations_before_after_title"
              value="<?= htmlspecialchars(getSetting('realisations_before_after_title')) ?>">
      </div>

      <div class="form-group">
        <label>Sous titre</label>
        <input class="form-control"
              name="realisations_before_after_subtitle"
              value="<?= htmlspecialchars(getSetting('realisations_before_after_subtitle')) ?>">
      </div>

      <div class="form-group">
        <label>Label principal</label>
        <input class="form-control"
              name="realisations_before_after_label"
              value="<?= htmlspecialchars(getSetting('realisations_before_after_label')) ?>">
      </div>

      <hr>

      <h5>Bloc droit 1</h5>

      <div class="form-group">
        <label>Titre</label>
        <input class="form-control"
              name="realisations_before_after_block1_title"
              value="<?= htmlspecialchars(getSetting('realisations_before_after_block1_title')) ?>">
      </div>

      <div class="form-group">
        <label>Texte</label>
        <textarea class="form-control"
                  name="realisations_before_after_block1_text"
                  rows="2"><?= htmlspecialchars(getSetting('realisations_before_after_block1_text')) ?></textarea>
      </div>

      <hr>

      <h5>Bloc droit 2</h5>

      <div class="form-group">
        <label>Titre</label>
        <input class="form-control"
              name="realisations_before_after_block2_title"
              value="<?= htmlspecialchars(getSetting('realisations_before_after_block2_title')) ?>">
      </div>

      <div class="form-group">
        <label>Texte</label>
        <textarea class="form-control"
                  name="realisations_before_after_block2_text"
                  rows="2"><?= htmlspecialchars(getSetting('realisations_before_after_block2_text')) ?></textarea>
      </div>

      <hr>

      <h5>Images</h5>

      <div class="form-group">
        <label>Image Avant</label>
        <input type="file" name="before_image" class="form-control">
      </div>

      <div class="form-group">
        <label>Image Apres</label>
        <input type="file" name="after_image" class="form-control">
      </div>
      <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>