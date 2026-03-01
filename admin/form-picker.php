<?php
/**
 * Form Picker — popup ouvert par TinyMCE via le bouton "Formulaire"
 * Affiche les formulaires actifs à insérer dans une page CMS.
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pdo = getPDO();

$forms = $pdo->query("
    SELECT f.*,
           (SELECT COUNT(*) FROM form_submissions s WHERE s.form_id = f.id) AS sub_count
    FROM forms f
    WHERE f.is_active = 1
    ORDER BY f.created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

$fieldTypeIcons = [
    'text'=>'⌨','email'=>'@','tel'=>'📞','number'=>'#',
    'select'=>'▾','textarea'=>'¶','checkbox'=>'☑','radio'=>'◎',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Insérer un formulaire</title>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{background:#1a1d23;color:#d0d4de;font-family:system-ui,sans-serif;display:flex;flex-direction:column;height:100vh;overflow:hidden}

  .toolbar{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#0f1115;border-bottom:1px solid #2c2f3a;flex-shrink:0}
  .toolbar h2{font-size:14px;font-weight:600;white-space:nowrap}
  .toolbar input[type=search]{flex:1;background:#252830;border:1px solid #3a3d4a;border-radius:6px;padding:6px 10px;color:#d0d4de;font-size:13px;outline:none}
  .toolbar input[type=search]:focus{border-color:#22c55e}
  .toolbar a.new-btn{display:flex;align-items:center;gap:6px;padding:6px 12px;background:#15803d;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;text-decoration:none;color:#fff}
  .toolbar a.new-btn:hover{background:#166534}

  .grid-wrapper{flex:1;overflow-y:auto;padding:14px}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}

  .card{background:#252830;border:2px solid transparent;border-radius:10px;overflow:hidden;cursor:pointer;transition:border-color .15s,transform .15s;position:relative;padding:16px 18px}
  .card:hover{border-color:#22c55e;transform:translateY(-2px)}
  .card:focus{outline:2px solid #22c55e;outline-offset:2px}

  .card .icon{font-size:28px;margin-bottom:8px;line-height:1}
  .card .title{font-size:14px;font-weight:700;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .card .slug{font-size:11px;color:#22c55e;font-family:monospace;margin-bottom:6px}
  .card .desc{font-size:11px;color:#8891a4;margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-height:14px}
  .card .fields-row{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px}
  .card .field-pill{font-size:10px;background:rgba(255,255,255,.08);border-radius:4px;padding:2px 6px;color:#aab}
  .card .meta{font-size:11px;color:#6b7080}

  .card .overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(21,128,61,.55);opacity:0;transition:opacity .15s;font-size:14px;font-weight:700;color:#fff;border-radius:8px}
  .card:hover .overlay{opacity:1}

  .empty{text-align:center;padding:40px;color:#6b7080;font-size:13px;grid-column:1/-1}
</style>
</head>
<body>

<div class="toolbar">
  <h2>📋 Insérer un formulaire</h2>
  <input type="search" id="search" placeholder="Rechercher…" autocomplete="off">
  <a class="new-btn" href="form-edit.php" target="_blank">+ Nouveau formulaire</a>
</div>

<div class="grid-wrapper">
  <div class="grid" id="grid">
    <?php if (empty($forms)): ?>
      <div class="empty">Aucun formulaire actif.<br>
        <a href="form-edit.php" target="_blank" style="color:#22c55e">Créer un formulaire →</a>
      </div>
    <?php else: ?>
      <?php foreach ($forms as $form):
        $fields   = json_decode($form['fields'],   true) ?: [];
        $settings = json_decode($form['settings'], true) ?: [];
        $steps    = $fields['steps'] ?? [];
        $allFields = [];
        foreach ($steps as $step) {
            foreach ($step['fields'] ?? [] as $f) $allFields[] = $f;
        }
        $stepCount  = count($steps);
        $fieldCount = count($allFields);
      ?>
      <div class="card" tabindex="0"
           data-slug="<?= htmlspecialchars($form['slug']) ?>"
           data-name="<?= htmlspecialchars($form['name']) ?>"
           data-search="<?= htmlspecialchars(strtolower($form['name'] . ' ' . $form['slug'])) ?>">
        <div class="overlay">Insérer</div>
        <div class="icon">📋</div>
        <div class="title"><?= htmlspecialchars($form['name']) ?></div>
        <div class="slug">[form:<?= htmlspecialchars($form['slug']) ?>]</div>
        <div class="desc"><?= htmlspecialchars($form['description'] ?: '—') ?></div>
        <div class="fields-row">
          <?php foreach (array_slice($allFields, 0, 5) as $f):
            $ico = $fieldTypeIcons[$f['type'] ?? 'text'] ?? '⌨'; ?>
            <span class="field-pill"><?= $ico ?> <?= htmlspecialchars($f['label'] ?? $f['name']) ?></span>
          <?php endforeach; ?>
          <?php if (count($allFields) > 5): ?>
            <span class="field-pill">+<?= count($allFields)-5 ?></span>
          <?php endif; ?>
        </div>
        <div class="meta">
          <?= $fieldCount ?> champ<?= $fieldCount>1?'s':'' ?>
          <?= $stepCount>1 ? ' · '.$stepCount.' étapes' : '' ?>
          · <?= (int)$form['sub_count'] ?> soumission<?= $form['sub_count']>1?'s':'' ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
const params = new URLSearchParams(window.location.search);
const cbKey  = params.get('callback');

function insertForm(slug, name) {
    try {
        if (window.opener && cbKey && typeof window.opener[cbKey] === 'function') {
            window.opener[cbKey](slug, name);
        }
    } catch(e) { console.error(e); }
    window.close();
}

document.querySelectorAll('.card').forEach(function(card) {
    card.addEventListener('click', function(){ insertForm(card.dataset.slug, card.dataset.name); });
    card.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); insertForm(card.dataset.slug, card.dataset.name); }
    });
});

document.getElementById('search').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.card').forEach(function(c) {
        c.style.display = (!q || c.dataset.search.includes(q)) ? '' : 'none';
    });
});
</script>
</body>
</html>
