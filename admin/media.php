<?php
require_once __DIR__ . '/auth.php';
requirePermission('media');
require_once __DIR__ . '/../includes/settings.php';

$csrf    = getCsrfToken();
$updated = $_GET['updated'] ?? '';
$notice  = $_GET['notice']  ?? '';

$baseDir = realpath(__DIR__ . '/../assets/images');
$baseUrl = BASE_URL . '/assets/images';

// ── Charger les métadonnées (alt texts) depuis la BDD ────────────────────────
$meta = [];
try {
    $pdo2 = getPDO();
    $rows = $pdo2->query("SELECT rel, alt_text FROM media_meta")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) $meta[$row['rel']] = ['alt' => $row['alt_text']];
} catch (Exception $e) {}

// ── Scanner les images (hors realisations/) ──────────────────────────────────
function scanImages(string $dir, string $baseDir, string $baseUrl): array {
    $imgs = [];
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg','avif'])) continue;
        $real = $file->getRealPath();
        if (str_contains(str_replace('\\','/',$real), '/realisations/')) continue;
        if (basename($real) === 'meta.json') continue;
        $rel  = ltrim(str_replace(str_replace('\\','/',$baseDir), '', str_replace('\\','/',$real)), '/');
        $url  = $baseUrl . '/' . $rel;
        $imgs[] = [
            'path'      => $real,
            'rel'       => $rel,
            'url'       => $url,
            'name'      => $file->getFilename(),
            'size'      => $file->getSize(),
            'mtime'     => $file->getMTime(),
            'deletable' => str_contains($rel, 'medias/'),
            'editable'  => str_contains($rel, 'medias/'),
        ];
    }
    usort($imgs, fn($a,$b) => $b['mtime'] - $a['mtime']);
    return $imgs;
}

$images = scanImages($baseDir, $baseDir, $baseUrl);

include __DIR__ . '/partials/header.php';
?>

<?php if ($updated === 'uploaded'): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle mr-1"></i> Image uploadée avec succès.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php elseif ($updated === 'deleted'): ?>
<div class="alert alert-info alert-dismissible fade show">
  <i class="fas fa-trash mr-1"></i> Image supprimée.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php elseif ($updated === 'meta'): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-tag mr-1"></i> Métadonnées sauvegardées.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php elseif ($updated === 'edited'): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-crop mr-1"></i> Image modifiée et sauvegardée.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($notice === 'csrf'): ?>
<div class="alert alert-danger"><i class="fas fa-shield-alt mr-1"></i> Erreur CSRF.</div>
<?php elseif ($notice === 'denied'): ?>
<div class="alert alert-danger"><i class="fas fa-ban mr-1"></i> Action non autorisée.</div>
<?php elseif ($notice === 'err'): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-1"></i> <?= htmlspecialchars($_GET['msg'] ?? 'Erreur') ?></div>
<?php endif; ?>

<div class="row">

  <!-- ─── Upload ─────────────────────────────────────────────── -->
  <div class="col-lg-4 mb-3">
    <div class="card card-outline card-primary">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-upload mr-1"></i> Upload d'images</h3>
      </div>
      <div class="card-body">
        <form method="POST" action="actions/media-upload.php" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

          <div id="dropZone" class="border border-secondary rounded text-center p-4 mb-3"
               style="cursor:pointer; border-style:dashed!important; background:rgba(255,255,255,.03); transition:.2s;">
            <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
            <p class="mb-1 text-muted">Glissez vos images ici</p>
            <p class="small text-muted mb-0">ou cliquez pour sélectionner</p>
            <input type="file" name="images[]" id="fileInput" multiple accept="image/*" style="display:none;">
          </div>

          <div id="previewGrid" class="row mb-3" style="display:none!important;"></div>

          <div class="form-group mb-2">
            <label class="small text-muted">Dossier de destination</label>
            <select name="folder" class="form-control form-control-sm" style="background:#1f2937;color:#e5e7eb;border-color:#374151;">
              <option value="medias">medias/ (général)</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-upload mr-1"></i> Envoyer
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ─── Galerie ─────────────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="card card-outline card-secondary">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-photo-video mr-1"></i> Bibliothèque
          <span class="badge badge-secondary ml-1"><?= count($images) ?></span>
        </h3>
        <input type="text" id="searchInput" placeholder="Filtrer…"
               class="form-control form-control-sm w-auto" style="background:#1f2937;color:#e5e7eb;border-color:#374151;max-width:180px;">
      </div>
      <div class="card-body p-2">

        <?php if (empty($images)): ?>
          <p class="text-muted text-center py-4">Aucune image. Uploadez-en une ci-contre.</p>
        <?php else: ?>
        <div class="row" id="mediaGrid">
          <?php foreach ($images as $img):
            $altText = $meta[$img['rel']]['alt'] ?? '';
          ?>
          <div class="col-6 col-sm-4 col-md-3 mb-3 media-item" data-name="<?= htmlspecialchars(strtolower($img['name'])) ?>">
            <div class="card h-100" style="background:#1a1d27;border:1px solid rgba(255,255,255,.08);">

              <!-- Aperçu -->
              <div class="text-center p-1" style="height:90px;overflow:hidden;background:#111;border-radius:6px 6px 0 0;">
                <img src="<?= htmlspecialchars($img['url']) ?>"
                     alt="<?= htmlspecialchars($altText ?: $img['name']) ?>"
                     style="max-height:100%;max-width:100%;object-fit:contain;cursor:pointer;"
                     onclick="openLightbox('<?= htmlspecialchars($img['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($img['name'], ENT_QUOTES) ?>')">
              </div>

              <div class="card-body p-2">
                <p class="small text-truncate mb-1" title="<?= htmlspecialchars($img['name']) ?>" style="color:#9ca3af;font-size:11px;">
                  <?= htmlspecialchars($img['name']) ?>
                </p>

                <!-- Champ ALT -->
                <div class="input-group input-group-sm mb-2">
                  <input type="text"
                         class="form-control alt-input"
                         placeholder="Texte alternatif (alt)…"
                         value="<?= htmlspecialchars($altText) ?>"
                         data-rel="<?= htmlspecialchars($img['rel']) ?>"
                         style="background:#111827;color:#d1d5db;border-color:#374151;font-size:11px;">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-sm btn-outline-success btn-save-alt"
                            data-rel="<?= htmlspecialchars($img['rel'], ENT_QUOTES) ?>"
                            title="Sauvegarder l'alt">
                      <i class="fas fa-save fa-xs"></i>
                    </button>
                  </div>
                </div>

                <!-- Actions -->
                <div class="d-flex" style="gap:3px;">
                  <button type="button" class="btn btn-xs btn-outline-info flex-fill btn-copy-url"
                          data-url="<?= htmlspecialchars($img['url'], ENT_QUOTES) ?>"
                          title="Copier l'URL">
                    <i class="fas fa-copy fa-xs"></i>
                  </button>

                  <button type="button" class="btn btn-xs btn-outline-secondary flex-fill"
                          onclick="openLightbox('<?= htmlspecialchars($img['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($img['name'], ENT_QUOTES) ?>')"
                          title="Aperçu">
                    <i class="fas fa-eye fa-xs"></i>
                  </button>

                  <?php if ($img['editable']): ?>
                  <button type="button" class="btn btn-xs btn-outline-warning flex-fill btn-edit-img"
                          data-url="<?= htmlspecialchars($img['url'], ENT_QUOTES) ?>"
                          data-rel="<?= htmlspecialchars($img['rel'], ENT_QUOTES) ?>"
                          data-name="<?= htmlspecialchars($img['name'], ENT_QUOTES) ?>"
                          title="Modifier (recadrer, redimensionner…)">
                    <i class="fas fa-crop-alt fa-xs"></i>
                  </button>
                  <?php endif; ?>

                  <?php if ($img['deletable']): ?>
                  <form method="POST" action="actions/media-delete.php" class="d-inline m-0"
                        onsubmit="return confirm('Supprimer cette image définitivement ?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="rel" value="<?= htmlspecialchars($img['rel']) ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Supprimer">
                      <i class="fas fa-trash fa-xs"></i>
                    </button>
                  </form>
                  <?php else: ?>
                  <button type="button" class="btn btn-xs btn-outline-secondary disabled" title="Image système" style="opacity:.35;">
                    <i class="fas fa-lock fa-xs"></i>
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

</div>

<!-- ─── Lightbox ─────────────────────────────────────────────── -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;align-items:center;justify-content:center;flex-direction:column;">
  <button onclick="closeLightbox()" style="position:absolute;top:16px;right:24px;background:none;border:none;color:#fff;font-size:28px;cursor:pointer;">&times;</button>
  <img id="lightboxImg" src="" alt="" style="max-width:90vw;max-height:80vh;object-fit:contain;border-radius:8px;">
  <p id="lightboxName" class="mt-3 text-white small"></p>
  <button id="lightboxCopy" class="btn btn-sm btn-outline-info mt-1"><i class="fas fa-copy mr-1"></i> Copier l'URL</button>
</div>

<!-- ─── Modal édition image (Cropper.js) ─────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" data-backdrop="static">
  <div class="modal-dialog modal-xl" role="document" style="max-width:920px;">
    <div class="modal-content" style="background:#1f2937;color:#e5e7eb;">
      <div class="modal-header border-secondary">
        <h5 class="modal-title"><i class="fas fa-crop-alt mr-2"></i><span id="editModalTitle">Modifier l'image</span></h5>
        <button type="button" class="close text-light" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-2">

        <!-- Barre d'outils -->
        <div class="d-flex flex-wrap align-items-center mb-2" style="gap:6px;">
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-light" onclick="cropperAction('rotate',-90)" title="Rotation gauche"><i class="fas fa-undo"></i></button>
            <button type="button" class="btn btn-outline-light" onclick="cropperAction('rotate',90)"  title="Rotation droite"><i class="fas fa-redo"></i></button>
          </div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-light" onclick="cropperAction('flipH')" title="Miroir horizontal"><i class="fas fa-arrows-alt-h"></i></button>
            <button type="button" class="btn btn-outline-light" onclick="cropperAction('flipV')" title="Miroir vertical"><i class="fas fa-arrows-alt-v"></i></button>
          </div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-light" onclick="cropperAction('zoom', 0.1)"  title="Zoom +"><i class="fas fa-search-plus"></i></button>
            <button type="button" class="btn btn-outline-light" onclick="cropperAction('zoom',-0.1)" title="Zoom -"><i class="fas fa-search-minus"></i></button>
          </div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-warning" onclick="cropperAction('reset')" title="Réinitialiser"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div class="btn-group btn-group-sm ml-auto">
            <label class="btn btn-outline-secondary btn-sm mb-0" title="Ratio libre">
              <input type="radio" name="aspectRatio" value="NaN" checked onchange="setRatio(NaN)"> Libre
            </label>
            <label class="btn btn-outline-secondary btn-sm mb-0" title="Carré">
              <input type="radio" name="aspectRatio" value="1"> 1:1
            </label>
            <label class="btn btn-outline-secondary btn-sm mb-0" title="Paysage">
              <input type="radio" name="aspectRatio" value="1.778"> 16:9
            </label>
            <label class="btn btn-outline-secondary btn-sm mb-0" title="Portrait">
              <input type="radio" name="aspectRatio" value="0.75"> 4:3
            </label>
          </div>
        </div>

        <!-- Zone Cropper -->
        <div style="max-height:420px;background:#000;border-radius:8px;overflow:hidden;">
          <img id="cropperImg" src="" alt="" style="max-width:100%;display:block;">
        </div>

        <!-- Redimensionnement -->
        <div class="row mt-2">
          <div class="col-5">
            <div class="input-group input-group-sm">
              <div class="input-group-prepend"><span class="input-group-text" style="background:#374151;color:#e5e7eb;border-color:#4b5563;">L</span></div>
              <input type="number" id="outputWidth" class="form-control" placeholder="auto"
                     style="background:#111827;color:#e5e7eb;border-color:#374151;">
              <div class="input-group-prepend"><span class="input-group-text" style="background:#374151;color:#e5e7eb;border-color:#4b5563;">H</span></div>
              <input type="number" id="outputHeight" class="form-control" placeholder="auto"
                     style="background:#111827;color:#e5e7eb;border-color:#374151;">
              <div class="input-group-append"><span class="input-group-text" style="background:#374151;color:#e5e7eb;border-color:#4b5563;">px</span></div>
            </div>
            <p class="small text-muted mt-1 mb-0">Laisser vide = taille native du recadrage</p>
          </div>
          <div class="col-4">
            <div class="input-group input-group-sm">
              <div class="input-group-prepend"><span class="input-group-text" style="background:#374151;color:#e5e7eb;border-color:#4b5563;">Qualité</span></div>
              <input type="number" id="outputQuality" class="form-control" value="92" min="1" max="100"
                     style="background:#111827;color:#e5e7eb;border-color:#374151;">
              <div class="input-group-append"><span class="input-group-text" style="background:#374151;color:#e5e7eb;border-color:#4b5563;">%</span></div>
            </div>
          </div>
          <div class="col-3">
            <select id="outputFormat" class="form-control form-control-sm" style="background:#111827;color:#e5e7eb;border-color:#374151;">
              <option value="original">Format original</option>
              <option value="image/jpeg">JPEG</option>
              <option value="image/png">PNG</option>
              <option value="image/webp">WebP</option>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer border-secondary">
        <span id="editSaveStatus" class="small text-muted mr-auto"></span>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-warning" id="btnSaveEdit">
          <i class="fas fa-save mr-1"></i> Sauvegarder
        </button>
        <button type="button" class="btn btn-outline-info" id="btnSaveEditCopy">
          <i class="fas fa-copy mr-1"></i> Sauvegarder comme copie
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>

<script>
const CSRF = <?= json_encode($csrf) ?>;

/* ── Filtrer la galerie ── */
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.media-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});

/* ── Copier URL ── */
document.querySelectorAll('.btn-copy-url').forEach(btn => {
    btn.addEventListener('click', function() {
        navigator.clipboard.writeText(this.dataset.url).then(() => {
            this.innerHTML = '<i class="fas fa-check fa-xs"></i>';
            setTimeout(() => this.innerHTML = '<i class="fas fa-copy fa-xs"></i>', 1500);
        });
    });
});

/* ── Sauvegarder ALT via AJAX ── */
document.querySelectorAll('.btn-save-alt').forEach(btn => {
    btn.addEventListener('click', function() {
        const rel    = this.dataset.rel;
        const input  = document.querySelector(`.alt-input[data-rel="${CSS.escape(rel)}"]`);
        const altVal = input ? input.value : '';
        const origHtml = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin fa-xs"></i>';
        const self = this;
        fetch('actions/media-meta-save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ csrf_token: CSRF, rel, alt: altVal })
        }).then(r => r.json()).then(d => {
            self.innerHTML = d.ok
                ? '<i class="fas fa-check fa-xs text-success"></i>'
                : '<i class="fas fa-times fa-xs text-danger"></i>';
            setTimeout(() => self.innerHTML = origHtml, 1800);
        }).catch(() => { self.innerHTML = origHtml; });
    });
});

/* ── Lightbox ── */
function openLightbox(url, name) {
    document.getElementById('lightboxImg').src = url;
    document.getElementById('lightboxName').textContent = name;
    document.getElementById('lightboxCopy').onclick = () => {
        navigator.clipboard.writeText(url);
        document.getElementById('lightboxCopy').innerHTML = '<i class="fas fa-check mr-1"></i> Copié !';
    };
    document.getElementById('lightbox').style.display = 'flex';
}
function closeLightbox() { document.getElementById('lightbox').style.display = 'none'; }
document.getElementById('lightbox').addEventListener('click', e => { if (e.target === e.currentTarget) closeLightbox(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

/* ── Drop zone ── */
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.background='rgba(99,179,237,.1)'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.background='rgba(255,255,255,.03)'; });
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.style.background='rgba(255,255,255,.03)';
    fileInput.files = e.dataTransfer.files; showPreview(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => showPreview(fileInput.files));
function showPreview(files) {
    const grid = document.getElementById('previewGrid');
    grid.innerHTML = '';
    if (!files.length) { grid.style.display='none'; return; }
    grid.style.removeProperty('display');
    Array.from(files).forEach(f => {
        const reader = new FileReader();
        reader.onload = ev => {
            const col = document.createElement('div');
            col.className = 'col-4 mb-1';
            col.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:70px;object-fit:cover;border-radius:6px;">
                             <p class="small text-truncate text-muted mb-0">${f.name}</p>`;
            grid.appendChild(col);
        };
        reader.readAsDataURL(f);
    });
}

/* ═══════════════════════════════════════════════════
   CROPPER.JS — Éditeur d'image
   ═══════════════════════════════════════════════════ */
let cropper   = null;
let editRel   = null;
let scaleXVal = 1;
let scaleYVal = 1;

document.querySelectorAll('.btn-edit-img').forEach(btn => {
    btn.addEventListener('click', function() {
        editRel = this.dataset.rel;
        document.getElementById('editModalTitle').textContent = this.dataset.name;
        document.getElementById('editSaveStatus').textContent = '';
        // Réinitialiser ratio
        document.querySelectorAll('input[name="aspectRatio"]')[0].checked = true;

        const img = document.getElementById('cropperImg');
        img.src = this.dataset.url + '?t=' + Date.now();
        $('#editModal').modal('show');
    });
});

$('#editModal').on('shown.bs.modal', function() {
    const img = document.getElementById('cropperImg');
    if (cropper) { cropper.destroy(); cropper = null; }
    scaleXVal = 1; scaleYVal = 1;

    function initCropper() {
        if (cropper) return;
        cropper = new Cropper(img, {
            viewMode: 1,
            autoCropArea: 1,
            responsive: true,
            background: false,
            toggleDragModeOnDblclick: false,
        });
    }

    // Attendre que l'image soit vraiment chargée avant d'init Cropper
    if (img.complete && img.naturalWidth > 0) {
        initCropper();
    } else {
        img.onload = function() { initCropper(); };
        img.onerror = function() {
            document.getElementById('editSaveStatus').textContent = '❌ Impossible de charger l\'image';
        };
    }
});

$('#editModal').on('hidden.bs.modal', function() {
    if (cropper) { cropper.destroy(); cropper = null; }
});

function cropperAction(action, val) {
    if (!cropper) return;
    if (action === 'rotate') { cropper.rotate(val); }
    if (action === 'flipH')  { scaleXVal *= -1; cropper.scaleX(scaleXVal); }
    if (action === 'flipV')  { scaleYVal *= -1; cropper.scaleY(scaleYVal); }
    if (action === 'zoom')   { cropper.zoom(val); }
    if (action === 'reset')  { cropper.reset(); scaleXVal=1; scaleYVal=1; }
}

function setRatio(r) {
    if (!cropper) return;
    cropper.setAspectRatio(isNaN(r) ? NaN : parseFloat(r));
}
document.querySelectorAll('input[name="aspectRatio"]').forEach(radio => {
    radio.addEventListener('change', function() { setRatio(this.value); });
});

async function saveEdit(asCopy) {
    if (!cropper || !editRel) return;
    const btn = document.getElementById(asCopy ? 'btnSaveEditCopy' : 'btnSaveEdit');
    const status = document.getElementById('editSaveStatus');
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Traitement…';
    btn.disabled = true;
    status.textContent = '';

    const w    = parseInt(document.getElementById('outputWidth').value)  || 0;
    const h    = parseInt(document.getElementById('outputHeight').value) || 0;
    const qual = (parseInt(document.getElementById('outputQuality').value) || 92) / 100;
    const fmt  = document.getElementById('outputFormat').value;

    // Déterminer le format MIME
    const extMap = {'image/jpeg':'jpg','image/png':'png','image/webp':'webp'};
    let mimeType = fmt === 'original' ? null : fmt;

    const canvasOpts = {};
    if (w > 0) canvasOpts.width  = w;
    if (h > 0) canvasOpts.height = h;

    const canvas = cropper.getCroppedCanvas(canvasOpts);
    const useMime = mimeType || 'image/jpeg';
    const dataUrl = canvas.toDataURL(useMime, qual);

    try {
        const resp = await fetch('actions/media-edit-save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                csrf_token: CSRF,
                rel: editRel,
                data_url: dataUrl,
                as_copy: asCopy ? '1' : '0',
                mime: useMime,
            })
        });
        const d = await resp.json();
        if (d.ok) {
            status.textContent = '✅ ' + (asCopy ? 'Copie créée : ' + d.filename : 'Image sauvegardée');
            // Rafraîchir la vignette dans la grille si on écrase
            if (!asCopy) {
                const thumbImg = document.querySelector(`.btn-edit-img[data-rel="${CSS.escape(editRel)}"]`)
                    ?.closest('.card')?.querySelector('img:first-child');
                if (thumbImg) thumbImg.src = thumbImg.src.split('?')[0] + '?t=' + Date.now();
            }
            if (!asCopy) setTimeout(() => $('#editModal').modal('hide'), 1200);
        } else {
            status.textContent = '❌ ' + (d.error || 'Erreur');
        }
    } catch(err) {
        status.textContent = '❌ Erreur réseau';
    }

    btn.innerHTML = origHtml;
    btn.disabled = false;
}

document.getElementById('btnSaveEdit').addEventListener('click', () => saveEdit(false));
document.getElementById('btnSaveEditCopy').addEventListener('click', () => saveEdit(true));
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
