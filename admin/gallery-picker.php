<?php
/**
 * Gallery Picker – popup ouvert par TinyMCE via le bouton "Galerie"
 * Affiche les galeries (collections de réalisations) à insérer dans une page CMS.
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pdo = getPDO();

/* ── Toutes les galeries avec nombre de réalisations ──────────────────── */
$galleries = $pdo->query("
    SELECT g.id, g.name, g.description, g.sort_order,
           COUNT(gi.id) AS nb_items,
           (
               SELECT ri.image_path FROM gallery_items gi2
               JOIN realisations r2 ON r2.id = gi2.realisation_id
               LEFT JOIN realisation_images ri ON ri.realisation_id = r2.id
               WHERE gi2.gallery_id = g.id AND r2.is_published = 1
               ORDER BY gi2.sort_order ASC, ri.sort_order ASC
               LIMIT 1
           ) AS first_image
    FROM galleries g
    LEFT JOIN gallery_items gi ON gi.gallery_id = g.id
    GROUP BY g.id
    ORDER BY g.sort_order ASC, g.created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Insérer une galerie</title>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{background:#1a1d23;color:#d0d4de;font-family:system-ui,sans-serif;display:flex;flex-direction:column;height:100vh;overflow:hidden}

  .toolbar{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#0f1115;border-bottom:1px solid #2c2f3a;flex-shrink:0}
  .toolbar h2{font-size:14px;font-weight:600;white-space:nowrap}
  .toolbar input[type=search]{flex:1;background:#252830;border:1px solid #3a3d4a;border-radius:6px;padding:6px 10px;color:#d0d4de;font-size:13px;outline:none}
  .toolbar input[type=search]:focus{border-color:#4f8ef7}
  .toolbar a.new-btn{display:flex;align-items:center;gap:6px;padding:6px 12px;background:#1d4ed8;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;text-decoration:none;color:#fff}
  .toolbar a.new-btn:hover{background:#1e40af}

  .grid-wrapper{flex:1;overflow-y:auto;padding:14px}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}

  .card{background:#252830;border:2px solid transparent;border-radius:10px;overflow:hidden;cursor:pointer;transition:border-color .15s,transform .15s;position:relative}
  .card:hover{border-color:#4f8ef7;transform:translateY(-2px)}
  .card:focus{outline:2px solid #4f8ef7;outline-offset:2px}
  .card img{width:100%;height:110px;object-fit:cover;display:block}
  .card .no-cover{width:100%;height:110px;display:flex;align-items:center;justify-content:center;background:#1a1d23;font-size:36px}
  .card .info{padding:10px 12px}
  .card .title{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .card .desc{font-size:11px;color:#8891a4;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .card .meta{font-size:11px;color:#6b7080;margin-top:4px}
  .card .overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(37,99,235,.55);opacity:0;transition:opacity .15s;font-size:14px;font-weight:700;color:#fff}
  .card:hover .overlay{opacity:1}

  .empty{text-align:center;padding:40px;color:#6b7080;font-size:13px;grid-column:1/-1}
</style>
</head>
<body>

<div class="toolbar">
  <h2>🖼 Choisir une galerie</h2>
  <input type="search" id="search" placeholder="Rechercher…" autocomplete="off">
  <a class="new-btn" href="gallery-edit.php" target="_blank">+ Nouvelle galerie</a>
</div>

<div class="grid-wrapper">
  <div class="grid" id="grid">
    <?php if (empty($galleries)): ?>
      <div class="empty">Aucune galerie.<br>
        <a href="gallery-edit.php" target="_blank" style="color:#4f8ef7">Créer une galerie →</a>
      </div>
    <?php else: ?>
      <?php foreach ($galleries as $g): ?>
      <div class="card" tabindex="0"
           data-id="<?= (int)$g['id'] ?>"
           data-title="<?= htmlspecialchars($g['name']) ?>"
           data-name="<?= htmlspecialchars(strtolower($g['name'])) ?>">
        <?php if ($g['first_image']): ?>
          <img src="<?= htmlspecialchars(BASE_URL . '/' . ltrim($g['first_image'], '/')) ?>"
               alt="<?= htmlspecialchars($g['name']) ?>">
        <?php else: ?>
          <div class="no-cover">🖼</div>
        <?php endif; ?>
        <div class="overlay">Insérer</div>
        <div class="info">
          <div class="title"><?= htmlspecialchars($g['name']) ?></div>
          <?php if (!empty($g['description'])): ?>
            <div class="desc"><?= htmlspecialchars($g['description']) ?></div>
          <?php endif; ?>
          <div class="meta"><?= (int)$g['nb_items'] ?> réalisation<?= $g['nb_items'] != 1 ? 's' : '' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
const params = new URLSearchParams(window.location.search);
const cbKey  = params.get('callback');

function insertGallery(id, title) {
    try {
        if (window.opener && cbKey && typeof window.opener[cbKey] === 'function') {
            window.opener[cbKey](id, title);
        }
    } catch(e) { console.error(e); }
    window.close();
}

document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('click', () => insertGallery(card.dataset.id, card.dataset.title));
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); insertGallery(card.dataset.id, card.dataset.title); }
    });
});

document.getElementById('search').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.card').forEach(c => {
        c.style.display = (!q || c.dataset.name.includes(q)) ? '' : 'none';
    });
});
</script>
</body>
</html>
