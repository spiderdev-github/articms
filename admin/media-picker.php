<?php
/**
 * Media Picker – popup ouvert par TinyMCE via file_picker_callback
 * Reçoit GET['callback'] = nom de la fonction callback renvoyée par TinyMCE
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/db.php';

$csrf    = getCsrfToken();
$baseDir = realpath(__DIR__ . '/../assets/images');
$baseUrl = BASE_URL . '/assets/images';

/* ── Charger les alt texts ───────────────────────────────────────────────── */
$meta = [];
try {
    $rows = getPDO()->query("SELECT rel, alt_text FROM media_meta")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $meta[$r['rel']] = $r['alt_text'];
} catch (Exception $e) {}

/* ── Scanner images ─────────────────────────────────────────────────────── */
$images  = [];
$exts    = ['jpg','jpeg','png','gif','webp','svg','avif'];
$iter    = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($iter as $file) {
    if (!$file->isFile()) continue;
    if (!in_array(strtolower($file->getExtension()), $exts)) continue;
    $realPath = $file->getRealPath();
    $relPath  = str_replace('\\','/', $realPath);
    if (str_contains($relPath, '/realisations/')) continue;
    $rel  = ltrim(str_replace(str_replace('\\','/',$baseDir), '', $relPath), '/');
    $images[] = [
        'rel'  => $rel,
        'url'  => $baseUrl . '/' . $rel,
        'name' => $file->getFilename(),
        'alt'  => $meta[$rel] ?? '',
        'mtime'=> $file->getMTime(),
    ];
}
usort($images, fn($a,$b) => $b['mtime'] - $a['mtime']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bibliothèque médias</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/variables.css">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{background:#1a1d23;color:#d0d4de;font-family:system-ui,sans-serif;display:flex;flex-direction:column;height:100vh;overflow:hidden}

  /* ── Toolbar ── */
  .toolbar{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#0f1115;border-bottom:1px solid #2c2f3a;flex-shrink:0}
  .toolbar h2{font-size:14px;font-weight:600;white-space:nowrap}
  .toolbar input[type=search]{flex:1;background:#252830;border:1px solid #3a3d4a;border-radius:6px;padding:6px 10px;color:#d0d4de;font-size:13px;outline:none}
  .toolbar input[type=search]:focus{border-color:#4f8ef7}
  .toolbar label.upload-btn{display:flex;align-items:center;gap:6px;padding:6px 12px;background:#2563eb;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:background .15s}
  .toolbar label.upload-btn:hover{background:#1d4ed8}
  .toolbar label.upload-btn svg{width:14px;height:14px;fill:currentcolor}
  #fileUpload{display:none}

  /* ── Grid ── */
  .grid-wrapper{flex:1;overflow-y:auto;padding:14px}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}
  .card{background:#252830;border:2px solid transparent;border-radius:8px;overflow:hidden;cursor:pointer;transition:border-color .15s,transform .15s;position:relative}
  .card:hover{border-color:#4f8ef7;transform:translateY(-2px)}
  .card:focus{outline:2px solid #4f8ef7;outline-offset:2px}
  .card img{width:100%;height:90px;object-fit:cover;display:block}
  .card .info{padding:6px 8px}
  .card .fname{font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#a8b0c4}
  .card .alt-badge{font-size:10px;color:#22c55e;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .card .alt-badge.empty{color:#6b7080}
  .card .select-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(37,99,235,.6);opacity:0;transition:opacity .15s;font-size:13px;font-weight:700;color:#fff}
  .card:hover .select-overlay{opacity:1}

  /* ── Empty state ── */
  .empty{text-align:center;padding:40px;color:#6b7080;font-size:13px}

  /* ── Upload progress overlay ── */
  #uploadOverlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:999}
  #uploadOverlay.active{display:flex}
  .upload-card{background:#252830;border-radius:12px;padding:32px 40px;text-align:center}
  .upload-card p{margin-top:12px;font-size:13px;color:#a8b0c4}
  .spinner{width:36px;height:36px;border:3px solid #3a3d4a;border-top-color:#4f8ef7;border-radius:50%;animation:spin .7s linear infinite;margin:auto}
  @keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<!-- Toolbar -->
<div class="toolbar">
  <h2>📁 Bibliothèque médias</h2>
  <input type="search" id="search" placeholder="Rechercher…" autocomplete="off">
  <label class="upload-btn" for="fileUpload">
    <svg viewBox="0 0 20 20"><path d="M10 2a1 1 0 0 1 .707.293l4 4a1 1 0 0 1-1.414 1.414L11 5.414V13a1 1 0 0 1-2 0V5.414L6.707 7.707A1 1 0 0 1 5.293 6.293l4-4A1 1 0 0 1 10 2zm-7 13a1 1 0 0 1 2 0v1h10v-1a1 1 0 0 1 2 0v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1z"/></svg>
    Uploader
  </label>
  <input type="file" id="fileUpload" accept="image/*" multiple>
</div>

<!-- Gallery -->
<div class="grid-wrapper">
  <div class="grid" id="grid">
    <?php if (empty($images)): ?>
      <div class="empty" style="grid-column:1/-1">Aucune image disponible. Uploadez-en une ci-dessus.</div>
    <?php else: ?>
      <?php foreach ($images as $img): ?>
      <div class="card" tabindex="0"
           data-url="<?= htmlspecialchars($img['url']) ?>"
           data-alt="<?= htmlspecialchars($img['alt']) ?>"
           data-name="<?= htmlspecialchars(strtolower($img['name'])) ?>">
        <img src="<?= htmlspecialchars($img['url']) ?>"
             alt="<?= htmlspecialchars($img['alt'] ?: $img['name']) ?>"
             loading="lazy">
        <div class="select-overlay">Insérer</div>
        <div class="info">
          <div class="fname"><?= htmlspecialchars($img['name']) ?></div>
          <?php if ($img['alt']): ?>
            <div class="alt-badge" title="<?= htmlspecialchars($img['alt']) ?>">✓ <?= htmlspecialchars($img['alt']) ?></div>
          <?php else: ?>
            <div class="alt-badge empty">Pas d'alt</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Upload progress overlay -->
<div id="uploadOverlay">
  <div class="upload-card">
    <div class="spinner"></div>
    <p>Upload en cours…</p>
  </div>
</div>

<script>
/* ── Récupérer le nom de la callback TinyMCE ──────────────────────────── */
const params   = new URLSearchParams(window.location.search);
const cbName   = params.get('callback');   // nom de fonction, ex. "picker0"
const fieldName = params.get('field') || 'src';

/* ── Sélection d'une image ─────────────────────────────────────────────── */
function pickImage(url, alt) {
    try {
        if (window.opener && cbName && typeof window.opener[cbName] === 'function') {
            window.opener[cbName](url, { alt: alt, title: alt });
        }
    } catch(e) { console.error(e); }
    window.close();
}

document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('click', () => {
        pickImage(card.dataset.url, card.dataset.alt);
    });
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            pickImage(card.dataset.url, card.dataset.alt);
        }
    });
});

/* ── Recherche ─────────────────────────────────────────────────────────── */
document.getElementById('search').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.card').forEach(card => {
        card.style.display = (!q || card.dataset.name.includes(q)) ? '' : 'none';
    });
});

/* ── Upload inline ─────────────────────────────────────────────────────── */
const fileInput     = document.getElementById('fileUpload');
const overlay       = document.getElementById('uploadOverlay');

fileInput.addEventListener('change', async function() {
    if (!this.files.length) return;
    overlay.classList.add('active');
    const fd  = new FormData();
    fd.append('csrf_token', '<?= $csrf ?>');
    fd.append('_ajax', '1');
    for (const f of this.files) fd.append('images[]', f);
    try {
        const res  = await fetch('<?= BASE_URL ?>/admin/actions/media-upload.php', { method:'POST', body: fd });
        const json = await res.json();
        // Recharger la page (popup) en conservant les params GET
        if (json.ok || json.uploaded > 0) {
            window.location.reload();
        } else {
            alert(json.error || 'Erreur lors de l\'upload.');
        }
    } catch(e) {
        alert('Erreur réseau.');
    } finally {
        overlay.classList.remove('active');
        fileInput.value = '';
    }
});
</script>
</body>
</html>
