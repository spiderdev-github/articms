<?php
require_once __DIR__ . '/auth.php';
requirePermission('cms');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$csrf    = getCsrfToken();
$pdo     = getPDO();
$updated = isset($_GET['updated']);
$deleted = isset($_GET['deleted']);
$notice  = $_GET['notice'] ?? '';

// Mode : list | new | edit
$editId   = (int)($_GET['edit'] ?? 0);
$isNew    = isset($_GET['new']);
$editPage = null;
$mode     = 'list';

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE id = ?");
    $stmt->execute([$editId]);
    $editPage = $stmt->fetch();
    if ($editPage) $mode = 'edit';
} elseif ($isNew) {
    $mode = 'new';
}

// ── Recherche, filtres & tri ─────────────────────────────────────────
$search       = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$sort         = $_GET['sort'] ?? '';
$dir          = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$allowedSorts = ['title' => 'title', 'slug' => 'slug', 'is_published' => 'is_published', 'date' => 'COALESCE(updated_at, created_at)'];
$sortCol      = isset($allowedSorts[$sort]) ? $allowedSorts[$sort] : '';

$where  = ['1=1'];
$params = [];
if ($search !== '') {
    $where[]  = '(title LIKE ? OR slug LIKE ? OR meta_title LIKE ?)';
    $term     = "%$search%";
    $params   = array_merge($params, [$term, $term, $term]);
}
if ($filterStatus === 'published') {
    $where[] = 'is_published = 1';
} elseif ($filterStatus === 'draft') {
    $where[] = 'is_published = 0';
}
$whereStr = implode(' AND ', $where);

// Toutes les pages (pour slugMap + parentPages)
$allPages = $pdo->query("SELECT * FROM cms_pages ORDER BY parent_id IS NOT NULL, sort_order ASC, created_at DESC")->fetchAll();

// Ordre SQL
$isSorting   = $sortCol !== '';
$orderSql    = $isSorting ? "$sortCol $dir" : 'parent_id IS NOT NULL, sort_order ASC, created_at DESC';

// Pages filtrées pour l'affichage
$stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE $whereStr ORDER BY $orderSql");
$stmt->execute($params);
$pages = $stmt->fetchAll();

// Pages racines disponibles comme parents (max 2 niveaux)
$parentPages = array_filter($allPages, function($p) use ($editId) {
    return $p['parent_id'] === null && $p['id'] !== $editId;
});

// Map id → slug pour l'aperçu URL en JS
$slugMap = [];
foreach ($allPages as $p) { $slugMap[$p['id']] = $p['slug']; }

// Liste hiérarchique sur les pages filtrées
// Si recherche/filtre/tri actif : affiche à plat (sinon, regroupe parents/enfants)
$isFiltering = $search !== '' || $filterStatus !== '' || $isSorting;
if ($isFiltering) {
    $roots    = $pages;
    $children = [];
} else {
    $roots    = array_filter($pages, fn($p) => $p['parent_id'] === null);
    $children = [];
    foreach ($pages as $p) {
        if ($p['parent_id'] !== null) $children[$p['parent_id']][] = $p;
    }
}

function cmsPagesSortLink(string $column, string $label, string $currentSort, string $currentDir): string {
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $params  = array_merge($_GET, ['sort' => $column, 'dir' => $nextDir]);
    unset($params['edit'], $params['new']);
    $url     = 'cms-pages.php?' . http_build_query($params);
    if ($currentSort === $column) {
        $icon = $currentDir === 'asc'
            ? '<i class="fas fa-sort-up ml-1"></i>'
            : '<i class="fas fa-sort-down ml-1"></i>';
    } else {
        $icon = '<i class="fas fa-sort ml-1 text-muted"></i>';
    }
    return '<a href="' . htmlspecialchars($url) . '" class="text-reset text-decoration-none" style="white-space:nowrap;">' . $label . $icon . '</a>';
}

/* ── Score SEO ──────────────────────────────────────────────────────────── */
function seoScore(array $p): array {
    $score = 0;
    // meta_title
    $mtLen = mb_strlen(trim($p['meta_title'] ?? ''));
    if ($mtLen > 0)  $score += 20;
    if ($mtLen >= 50 && $mtLen <= 70) $score += 10;
    // meta_description
    $mdLen = mb_strlen(trim($p['meta_description'] ?? ''));
    if ($mdLen > 0)  $score += 20;
    if ($mdLen >= 120 && $mdLen <= 160) $score += 10;
    // h1
    if (!empty(trim($p['h1'] ?? ''))) $score += 15;
    // contenu
    $contentText = strip_tags($p['content'] ?? '');
    if (mb_strlen($contentText) > 0)   $score += 15;
    if (mb_strlen($contentText) > 300) $score += 10;

    if ($score >= 70)      $color = 'success';
    elseif ($score >= 40)  $color = 'warning';
    else                   $color = 'danger';

    return ['score' => $score, 'color' => $color];
}

include __DIR__ . '/partials/header.php';
?>

<?php if ($updated): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle mr-1"></i> Page enregistrée.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($deleted): ?>
<div class="alert alert-warning alert-dismissible fade show">
  <i class="fas fa-trash mr-1"></i> Page supprimée.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($notice === 'csrf'): ?>
<div class="alert alert-danger"><i class="fas fa-shield-alt mr-1"></i> Erreur CSRF.</div>
<?php endif; ?>
<?php if ($notice === 'missing'): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> Slug et titre requis.</div>
<?php endif; ?>

<?php if ($mode === 'list'): ?>
<!-- ====================================================== LISTE -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0"><i class="fas fa-file-alt mr-2"></i>Pages du site</h4>
  <a href="cms-pages.php?new=1" class="btn btn-primary btn-sm">
    <i class="fas fa-plus mr-1"></i> Nouvelle page
  </a>
</div>

<!-- Filtres -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="form-inline" style="gap:8px;flex-wrap:wrap;">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Titre, slug…" value="<?= htmlspecialchars($search) ?>" style="min-width:220px;">
      <select name="status" class="form-control form-control-sm">
        <option value="">Tous statuts</option>
        <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Publiée</option>
        <option value="draft"     <?= $filterStatus === 'draft'     ? 'selected' : '' ?>>Brouillon</option>
      </select>
      <button class="btn btn-sm btn-secondary" type="submit"><i class="fas fa-search mr-1"></i>Filtrer</button>
      <?php if ($search || $filterStatus): ?>
        <a href="cms-pages.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="m-0">
      <?= count($pages) ?> page<?= count($pages) > 1 ? 's' : '' ?>
      <?php if ($isFiltering): ?>
        <small class="text-muted font-weight-normal">sur <?= count($allPages) ?> au total</small>
      <?php endif; ?>
    </h5>
  </div>
  <div class="card-body p-0">
    <?php if (empty($pages)): ?>
    <div class="p-4 text-center text-muted">
      <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
      Aucune page créée. <a href="cms-pages.php?new=1">Créer la première page</a>.
    </div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="thead-dark">
        <tr>
          <th><?= cmsPagesSortLink('title',        'Titre',   $sort, $dir) ?></th>
          <th><?= cmsPagesSortLink('slug',         'Slug / URL', $sort, $dir) ?></th>
          <th><?= cmsPagesSortLink('is_published', 'Statut',  $sort, $dir) ?></th>
          <th class="text-center">SEO</th>
          <th><?= cmsPagesSortLink('date',         'Date',    $sort, $dir) ?></th>
          <th style="width:110px;"></th>
        </tr>
      </thead>
      <tbody>
      <?php
      foreach ($roots as $p):
          if ($isFiltering) {
              // Mode plat : chaque page affichée directement
              $rows = [$p];
          } else {
              $rows = [$p];
              if (!empty($children[$p['id']])) {
                  array_push($rows, ...array_map(fn($c) => array_merge($c, ['_child' => true]), $children[$p['id']]));
              }
          }
          foreach ($rows as $p):
              $child   = !empty($p['_child']) || ($isFiltering && $p['parent_id'] !== null);
              $fullUrl = $p['parent_id'] ? ($slugMap[$p['parent_id']] . '/' . $p['slug']) : $p['slug'];
      ?>
      <tr>
        <td class="align-middle">
          <?php if ($child): ?>
          <span class="text-muted" style="margin-right:6px;">↳</span>
          <?php endif; ?>
          <span class="font-weight-bold"><?= htmlspecialchars($p['title']) ?></span>
        </td>
        <td class="align-middle">
          <code style="color:#f472b6;"><?= htmlspecialchars($fullUrl) ?></code><br>
          <a href="<?= BASE_URL ?>/<?= htmlspecialchars($fullUrl) ?>" target="_blank" class="small text-muted">
            <i class="fas fa-external-link-alt"></i> Voir
          </a>
        </td>
        <td class="align-middle">
          <?php if ($p['is_published']): ?>
          <span class="badge badge-success">Publiée</span>
          <?php else: ?>
          <span class="badge badge-secondary">Brouillon</span>
          <?php endif; ?>
        </td>
        <?php
          $seo = seoScore($p);
          $tips = [];
          if (empty(trim($p['meta_title'] ?? ''))) $tips[] = 'Meta titre manquant';
          elseif (mb_strlen($p['meta_title']) < 50 || mb_strlen($p['meta_title']) > 70) $tips[] = 'Meta titre hors longueur idéale (50-70)';
          if (empty(trim($p['meta_description'] ?? ''))) $tips[] = 'Meta description manquante';
          elseif (mb_strlen($p['meta_description']) < 120 || mb_strlen($p['meta_description']) > 160) $tips[] = 'Meta desc hors longueur idéale (120-160)';
          if (empty(trim($p['h1'] ?? ''))) $tips[] = 'H1 manquant';
          if (mb_strlen(strip_tags($p['content'] ?? '')) < 300) $tips[] = 'Contenu trop court';
          $tooltip = implode(' · ', $tips);
        ?>
        <td class="align-middle text-center">
          <span class="badge badge-<?= $seo['color'] ?>" title="<?= htmlspecialchars($tooltip) ?>"
                style="font-size:13px;min-width:40px;cursor:default;">
            <?= $seo['score'] ?>
          </span>
        </td>
        <td class="align-middle" style="white-space:nowrap;font-size:12px;">
          <?php
            $dt = !empty($p['updated_at']) ? $p['updated_at'] : $p['created_at'];
            $label = !empty($p['updated_at']) ? 'Modif.' : 'Créé';
            echo '<small class="text-muted">' . $label . '</small><br><small>' . date('d/m/Y', strtotime($dt)) . '</small>';
          ?>
        </td>
        <td class="text-right align-middle" style="white-space:nowrap;">
          <a href="cms-pages.php?edit=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary" title="Modifier">
            <i class="fas fa-edit"></i>
          </a>
          <form method="POST" action="actions/page-delete.php" class="d-inline"
                onsubmit="return confirm('Supprimer &laquo; <?= htmlspecialchars(addslashes($p['title'])) ?> &raquo; ?')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button class="btn btn-xs btn-outline-danger ml-1" title="Supprimer">
              <i class="fas fa-trash"></i>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ====================================================== FORMULAIRE (new / edit) -->
<div class="row justify-content-center">
  <div class="col-lg-12">

    <div class="mb-3" style="text-align:right;">
      <a href="cms-pages.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Retour à la liste
      </a>
    </div>

    <div class="card card-outline card-primary">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-<?= $mode === 'edit' ? 'edit' : 'plus-circle' ?> mr-1"></i>
          <?= $mode === 'edit' ? 'Modifier : ' . htmlspecialchars($editPage['title']) : 'Nouvelle page' ?>
        </h3>
      </div>
      <div class="card-body">
        <form method="POST" action="actions/page-save.php"
              onkeydown="if(event.key==='Enter'&&event.target.tagName!=='TEXTAREA'){event.preventDefault();}">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="id" value="<?= $editPage ? (int)$editPage['id'] : 0 ?>">

          <div class="form-row">
            <div class="form-group col-md-7">
              <label>Titre <span class="text-danger">*</span></label>
              <input class="form-control" name="title" required
                     value="<?= htmlspecialchars($editPage['title'] ?? '') ?>"
                     id="page-title-input">
            </div>
            <div class="form-group col-md-5">
              <label>Page parente <small class="text-muted">(optionnel — 1 niveau max)</small></label>
              <select class="form-control" name="parent_id" id="page-parent-select">
                <option value="0">— Aucune (page racine)</option>
                <?php foreach ($parentPages as $pp): ?>
                <option value="<?= $pp['id'] ?>"
                  data-slug="<?= htmlspecialchars($pp['slug']) ?>"
                  <?= ($editPage['parent_id'] ?? 0) == $pp['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($pp['title']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-7">
              <label>Slug (URL) <span class="text-danger">*</span>
                <small class="text-muted">lettres-minuscules-tirets</small></label>
              <input class="form-control" name="slug" required id="page-slug-input"
                     pattern="[a-z0-9\-]+"
                     value="<?= htmlspecialchars($editPage['slug'] ?? '') ?>">
              <small class="text-muted">URL : <code id="slug-preview"><?php
                $previewParent = '';
                if (!empty($editPage['parent_id']) && isset($slugMap[$editPage['parent_id']])) {
                    $previewParent = $slugMap[$editPage['parent_id']] . '/';
                }
                echo BASE_URL . '/' . $previewParent . htmlspecialchars($editPage['slug'] ?? 'slug');
              ?></code></small>
            </div>
          </div>

          <div class="form-group">
            <label>Kicker <small class="text-muted">(badge au-dessus du H1)</small></label>
            <input class="form-control" name="kicker"
                   value="<?= htmlspecialchars($editPage['kicker'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>H1 <small class="text-muted">(si vide = titre de la page)</small></label>
            <input class="form-control" name="h1"
                   value="<?= htmlspecialchars($editPage['h1'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>Contenu</label>
            <textarea class="form-control tinymce-editor" name="content" rows="16"
                      id="page-content"><?= htmlspecialchars($editPage['content'] ?? '') ?></textarea>
          </div>

          <hr>
          <h6 class="mb-3"><i class="fas fa-search mr-1 text-muted"></i> SEO</h6>
          <div class="form-group">
            <label>Meta title</label>
            <input class="form-control" name="meta_title"
                   value="<?= htmlspecialchars($editPage['meta_title'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Meta description</label>
            <textarea class="form-control" name="meta_desc" rows="2"><?= htmlspecialchars($editPage['meta_description'] ?? '') ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Ordre d'affichage</label>
              <input type="number" class="form-control" name="sort_order" min="0"
                     value="<?= (int)($editPage['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group col-md-4">
              <label>Template</label>
              <select class="form-control" name="template">
                <option value="default" <?= ($editPage['template'] ?? 'default') === 'default' ? 'selected' : '' ?>>Par défaut</option>
              </select>
            </div>
            <div class="form-group col-md-4 d-flex align-items-end pb-2">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="is_pub"
                       name="is_published" value="1"
                       <?= ($editPage['is_published'] ?? 1) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="is_pub">Publiée</label>
              </div>
            </div>
          </div>

          <div class="mt-2 d-flex align-items-center gap-2" style="gap:.5rem">
            <input type="hidden" name="submit_action" id="submit_action_input" value="stay">
            <button type="submit" onclick="document.getElementById('submit_action_input').value='stay'"
                    class="btn btn-primary btn-lg">
              <i class="fas fa-save mr-1"></i>
              <?= $mode === 'edit' ? 'Enregistrer' : 'Créer la page' ?>
            </button>
            <?php if ($mode === 'edit'): ?>
            <button type="submit" onclick="document.getElementById('submit_action_input').value='quit'"
                    class="btn btn-success btn-lg ml-2">
              <i class="fas fa-check mr-1"></i> Enregistrer et quitter
            </button>
            <?php endif; ?>
            <a href="cms-pages.php" class="btn btn-secondary ml-2">Annuler</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/2j0afis9i9wz33xc5awewqvl0xbuw003r4jb7e9em6phf2je/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '.tinymce-editor',
    language: 'fr_FR',

    /* ---- Plugins ---- */
    plugins: 'link lists image code table media wordcount visualblocks noneditable fullscreen',

    /* ---- Toolbar ---- */
    toolbar: [
        'undo redo | styleselect | bold italic | forecolor',
        'alignleft aligncenter alignright | bullist numlist | outdent indent | table | link image media | code visualblocks | galerie formulaire | fullscreen'
    ],
    toolbar_mode: 'wrap',
    menubar: false,
    height: 540,

    /* ---- Apparence : styles du site ---- */
    skin: 'oxide-dark',
    content_css: ['dark', '<?= BASE_URL ?>/admin/tinymce-content.css'],
    body_class: 'tinymce-preview',

    /* ---- Formats personnalisés (classes du site) ---- */
    style_formats: [
        { title: 'Titres', items: [
            { title: 'H1', block: 'h1' },
            { title: 'H2', block: 'h2' },
            { title: 'H3', block: 'h3' },
        ]},
        { title: 'Paragraphe', items: [
            { title: 'Normal',         block: 'p' },
            { title: 'Texte atténué',  block: 'p', classes: 'muted' },
        ]},
        { title: 'Mise en page', items: [
            { title: 'Section',    block: 'div', classes: 'section',    wrapper: true },
            { title: 'Grille 2 col', block: 'div', classes: 'grid-2',  wrapper: true },
            { title: 'Grille 3 col', block: 'div', classes: 'grid-3',  wrapper: true },
            { title: 'Grille 4 col', block: 'div', classes: 'grid-4',  wrapper: true },
            { title: 'Actions (boutons)', block: 'div', classes: 'actions', wrapper: true },
        ]},
        { title: 'Composants', items: [
            { title: 'Card',           block: 'div',  classes: 'card',        wrapper: true },
            { title: 'Kicker',         inline: 'span', classes: 'kicker' },
            { title: 'Bloc Local',     block: 'div',  classes: 'local',       wrapper: true },
            { title: 'KPI (3 col)',    block: 'div',  classes: 'kpis',        wrapper: true },
            { title: 'KPI item',       block: 'div',  classes: 'kpi',         wrapper: true },
            { title: 'Badge',          inline: 'span', classes: 'badge' },
        ]},
        { title: 'Boutons', items: [
            { title: 'Bouton primaire (rouge)', inline: 'a', classes: 'btn btn-primary' },
            { title: 'Bouton ghost',            inline: 'a', classes: 'btn btn-ghost' },
            { title: 'Bouton doré',             inline: 'a', classes: 'btn btn-gold' },
        ]},
    ],
    style_formats_merge: false,

    /* ---- Options liens ---- */
    link_list: '<?= BASE_URL ?>/admin/tinymce-link-list.php',
    link_title: false,
    link_target_list: [
        { title: 'Même onglet',    value: '' },
        { title: 'Nouvel onglet',  value: '_blank' },
    ],
    link_rel_list: [
        { title: 'Aucun',     value: '' },
        { title: 'nofollow',  value: 'nofollow' },
        { title: 'noopener',  value: 'noopener noreferrer' },
    ],
    default_link_target: '_self',

    /* ---- Options images ---- */
    image_advtab: true,
    image_uploadtab: false,
    file_picker_types: 'image',

    /* ---- Bibliothèque médias personnalisée ---- */
    file_picker_callback: function(cb, value, meta) {
        if (meta.filetype !== 'image') return;

        /* Nom unique pour la callback exposée sur window */
        const cbKey = 'tinymcePicker_' + Date.now();
        window[cbKey] = function(url, info) {
            cb(url, { alt: info.alt || '', title: info.title || '' });
            delete window[cbKey];
        };

        const pickerUrl = '<?= BASE_URL ?>/admin/media-picker.php?callback=' + cbKey;
        const w = 900, h = 600;
        const left = Math.round(screen.width  / 2 - w / 2);
        const top  = Math.round(screen.height / 2 - h / 2);
        window.open(pickerUrl, 'mediaPicker',
            'width=' + w + ',height=' + h + ',top=' + top + ',left=' + left +
            ',resizable=yes,scrollbars=yes');
    },

    /* ---- Table ---- */
    table_default_styles: {
        'width': '100%',
        'border-collapse': 'collapse',
    },

    /* ---- Options non-éditables (placeholders galerie + formulaire) ---- */
    noneditable_noneditable_class: 'gallery-shortcode form-shortcode',

    /* ---- Bouton personnalisé : Galerie ---- */
    setup: function(ed) {

        /* Enregistrement du bouton toolbar "galerie" */
        ed.ui.registry.addButton('galerie', {
            text: '🖼 Galerie',
            tooltip: 'Insérer une galerie',
            onAction: function() {
                const cbKey = 'galleryPicker_' + Date.now();
                window[cbKey] = function(galleryId, galleryTitle) {
                    const html = '<figure class="gallery-shortcode" data-gallery-id="' + galleryId + '" contenteditable="false">' +
                        '<span class="gs-icon">🖼</span>' +
                        '<span class="gs-title">Galerie — ' + galleryTitle + '</span>' +
                        '<span class="gs-meta">id=' + galleryId + '</span>' +
                        '</figure><p></p>';
                    ed.insertContent(html);
                    delete window[cbKey];
                };
                const pickerUrl = '<?= BASE_URL ?>/admin/gallery-picker.php?callback=' + cbKey;
                const w = 900, h = 560;
                const left = Math.round(screen.width  / 2 - w / 2);
                const top  = Math.round(screen.height / 2 - h / 2);
                window.open(pickerUrl, 'galleryPicker',
                    'width=' + w + ',height=' + h + ',top=' + top + ',left=' + left +
                    ',resizable=yes,scrollbars=yes');
            }
        });

        /* Enregistrement du bouton toolbar "formulaire" */
        ed.ui.registry.addButton('formulaire', {
            text: '📋 Formulaire',
            tooltip: 'Insérer un formulaire',
            onAction: function() {
                const cbKey = 'formPicker_' + Date.now();
                window[cbKey] = function(formSlug, formName) {
                    const html = '<figure class="form-shortcode" data-form-slug="' + formSlug + '" contenteditable="false">' +
                        '<span class="fs-icon">📋</span>' +
                        '<span class="fs-title">Formulaire — ' + formName + '</span>' +
                        '<span class="fs-meta">slug=' + formSlug + '</span>' +
                        '</figure><p></p>';
                    ed.insertContent(html);
                    delete window[cbKey];
                };
                const pickerUrl = '<?= BASE_URL ?>/admin/form-picker.php?callback=' + cbKey;
                const w = 760, h = 540;
                const left = Math.round(screen.width  / 2 - w / 2);
                const top  = Math.round(screen.height / 2 - h / 2);
                window.open(pickerUrl, 'formPicker',
                    'width=' + w + ',height=' + h + ',top=' + top + ',left=' + left +
                    ',resizable=yes,scrollbars=yes');
            }
        });

        ed.on('change input', function(){ ed.save(); });

        /* Synchronise le contenu avant soumission du formulaire */
        ed.on('submit', function(){ ed.save(); });

        /* Double-clic sur une image → ouvre le panneau Insérer/modifier image */
        ed.on('dblclick', function(e) {
            var target = e.target;
            if (target && target.nodeName === 'IMG') {
                ed.selection.select(target);
                ed.execCommand('mceImage');
            }
        });

        /* Clic sur un lien → ouvre le panneau Insérer/modifier lien */
        ed.on('click', function(e) {
            var target = e.target;
            if (!target) return;
            var anchor = target.nodeName === 'A' ? target : target.closest('a');
            if (anchor) {
                ed.selection.select(anchor);
                ed.execCommand('mceLink');
            }
        });
    }
});

const titleInput  = document.getElementById('page-title-input');
const slugInput   = document.getElementById('page-slug-input');
const slugPreview = document.getElementById('slug-preview');
const parentSelect = document.getElementById('page-parent-select');
const isEdit      = <?= $mode === 'edit' ? 'true' : 'false' ?>;
const baseUrl     = '<?= BASE_URL ?>';

function getParentSlug() {
    if (!parentSelect) return '';
    const opt = parentSelect.options[parentSelect.selectedIndex];
    return opt && opt.dataset.slug ? opt.dataset.slug + '/' : '';
}

function updatePreview() {
    if (slugPreview && slugInput) {
        slugPreview.textContent = baseUrl + '/' + getParentSlug() + (slugInput.value || 'slug');
    }
}

if (titleInput && slugInput && !isEdit) {
    titleInput.addEventListener('input', function(){
        const slug = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '').trim()
            .replace(/[\s-]+/g, '-');
        slugInput.value = slug;
        updatePreview();
    });
}
if (slugInput)   slugInput.addEventListener('input', updatePreview);
if (parentSelect) parentSelect.addEventListener('change', updatePreview);
</script>

<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
