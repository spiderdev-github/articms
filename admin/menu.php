<?php
require_once __DIR__ . '/auth.php';
requirePermission('menu');
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/db.php';

$csrf    = getCsrfToken();
$updated = isset($_GET['updated']);
$notice  = $_GET['notice'] ?? '';

$rawNav   = getSetting('nav_items', '');
$navItems = $rawNav ? (json_decode($rawNav, true) ?: []) : [];

if (empty($navItems)) {
    $navItems = [
        ['label' => 'Accueil',      'url' => '/'],
        ['label' => 'Prestations',  'url' => '/prestations', 'children' => []],
        ['label' => 'Réalisations', 'url' => '/realisations.php'],
        ['label' => 'Contact',      'url' => '/contact'],
    ];
}

// Normalize : assure que children existe sur chaque item
foreach ($navItems as &$item) {
    if (!isset($item['children'])) $item['children'] = [];
}
unset($item);

// Pages pour la modale
$staticPages = [
    ['label' => 'Accueil',       'url' => '/'],
    ['label' => 'Réalisations',  'url' => '/realisations.php'],
    ['label' => 'Contact',       'url' => '/contact'],
];
$pdo = getPDO();
$cmsPages = [];
try {
    $cmsPages = $pdo->query(
        "SELECT p.title, p.slug, pp.slug AS parent_slug FROM cms_pages p
         LEFT JOIN cms_pages pp ON p.parent_id = pp.id
         WHERE p.is_published = 1 ORDER BY pp.slug ASC, p.sort_order ASC, p.title ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include __DIR__ . '/partials/header.php';
?>

<?php if ($updated): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle mr-1"></i> Navigation mise à jour avec succès.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($notice === 'csrf'): ?>
<div class="alert alert-danger"><i class="fas fa-shield-alt mr-1"></i> Erreur de sécurité CSRF.</div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8">
    <div class="card card-outline card-primary">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-bars mr-1"></i> Menu de navigation</h3>
        <small class="text-muted">Glissez pour réordonner · ▶ pour ajouter des sous-liens</small>
      </div>
      <div class="card-body">

        <form method="POST" action="actions/menu-save.php" id="menuForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="nav_json" id="nav_json">

          <div id="menuItems">
            <?php foreach ($navItems as $pi => $item): ?>
            <div class="menu-parent-block" data-id="<?= $pi ?>">

              <!-- Ligne parent -->
              <div class="menu-parent-row d-flex align-items-center gap-2 mb-1">
                <span class="parent-handle px-2 text-muted" style="cursor:grab;"><i class="fas fa-grip-vertical"></i></span>
                <input type="text" class="form-control form-control-sm parent-label"
                       placeholder="Label" value="<?= htmlspecialchars($item['label']) ?>">
                <input type="text" class="form-control form-control-sm parent-url"
                       placeholder="/url" value="<?= htmlspecialchars($item['url']) ?>">
                <button type="button" class="btn btn-sm btn-outline-info btn-toggle-children"
                        title="Sous-liens" style="white-space:nowrap;">
                  <i class="fas fa-chevron-right fa-sm chevron-icon"></i>
                  <span class="child-count badge badge-info ml-1">
                    <?= count($item['children'] ?? []) ?>
                  </span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-parent" title="Supprimer">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <!-- Sous-liens (masqués par défaut si vides) -->
              <div class="children-block pl-4 <?= empty($item['children']) ? 'd-none' : '' ?>">
                <div class="children-list mb-1">
                  <?php foreach (($item['children'] ?? []) as $ci => $child): ?>
                  <div class="child-row d-flex align-items-center gap-2 mb-1">
                    <span class="child-handle px-2 text-muted" style="cursor:grab; font-size:11px;"><i class="fas fa-grip-vertical"></i></span>
                    <span class="text-muted mr-1" style="font-size:13px;">↳</span>
                    <input type="text" class="form-control form-control-sm child-label"
                           placeholder="Label sous-lien" value="<?= htmlspecialchars($child['label']) ?>">
                    <input type="text" class="form-control form-control-sm child-url"
                           placeholder="/url" value="<?= htmlspecialchars($child['url']) ?>">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-child" title="Supprimer">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-xs btn-outline-secondary btn-add-child mb-2">
                  <i class="fas fa-plus fa-sm mr-1"></i> Ajouter un sous-lien
                </button>
              </div>

              <hr class="my-1" style="border-color:rgba(255,255,255,.08);">
            </div>
            <?php endforeach; ?>
          </div><!-- /#menuItems -->

          <div class="d-flex align-items-center justify-content-between mt-3">
            <button type="button" id="btnAddParent" class="btn btn-outline-secondary"
                    data-toggle="modal" data-target="#pagePickerModal" data-target-type="parent">
              <i class="fas fa-plus mr-1"></i> Ajouter un lien
            </button>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-save mr-1"></i> Enregistrer
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card card-outline card-warning">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-lightbulb mr-1"></i> Conseils</h3>
      </div>
      <div class="card-body small text-muted">
        <ul class="pl-3 mb-0">
          <li>Glissez les lignes pour réordonner</li>
          <li>Cliquez <i class="fas fa-chevron-right fa-sm"></i> pour ajouter des sous-liens à un item</li>
          <li>Les sous-liens créent un menu déroulant sur le site</li>
          <li>Max 2 niveaux (parent → sous-lien)</li>
          <li>URLs internes : <code>/ma-page</code></li>
          <li>Pages CMS libres : <code>/mon-slug</code></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- ====================== MODALE SÉLECTION PAGE ====================== -->
<div class="modal fade" id="pagePickerModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" style="background:#1f2937; color:#e5e7eb;">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="pickerTitle"><i class="fas fa-plus-circle mr-2"></i>Ajouter un lien</h5>
        <button type="button" class="close text-light" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">

        <h6 class="text-uppercase text-muted mb-2" style="font-size:11px;">
          <i class="fas fa-file-code mr-1"></i> Pages statiques
        </h6>
        <div class="row mb-4">
          <?php foreach ($staticPages as $p): ?>
          <div class="col-sm-6 col-md-4 mb-2">
            <button type="button" class="btn btn-block btn-sm btn-outline-light text-left pick-page"
                    data-label="<?= htmlspecialchars($p['label']) ?>"
                    data-url="<?= htmlspecialchars($p['url']) ?>">
              <i class="fas fa-home mr-1 text-muted"></i>
              <?= htmlspecialchars($p['label']) ?>
              <small class="d-block text-muted"><?= htmlspecialchars($p['url']) ?></small>
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($cmsPages)): ?>
        <h6 class="text-uppercase text-muted mb-2" style="font-size:11px;">
          <i class="fas fa-file-alt mr-1"></i> Pages CMS libres
        </h6>
        <div class="row mb-4">
          <?php foreach ($cmsPages as $p):
            $fullSlug = $p['parent_slug'] ? $p['parent_slug'] . '/' . $p['slug'] : $p['slug'];
          ?>
          <div class="col-sm-6 col-md-4 mb-2">
            <button type="button" class="btn btn-block btn-sm btn-outline-info text-left pick-page"
                    data-label="<?= htmlspecialchars($p['title']) ?>"
                    data-url="/<?= htmlspecialchars($fullSlug) ?>">
              <i class="fas fa-file mr-1 text-muted"></i>
              <?= htmlspecialchars($p['title']) ?>
              <small class="d-block text-muted">/<?= htmlspecialchars($fullSlug) ?></small>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h6 class="text-uppercase text-muted mb-2" style="font-size:11px;">
          <i class="fas fa-external-link-alt mr-1"></i> Lien personnalisé
        </h6>
        <div class="input-group">
          <input type="text" id="customLabel" class="form-control form-control-sm"
                 placeholder="Label" style="background:#374151; border-color:#4b5563; color:#e5e7eb;">
          <input type="text" id="customUrl" class="form-control form-control-sm"
                 placeholder="URL (/blog ou https://...)" style="background:#374151; border-color:#4b5563; color:#e5e7eb;">
          <div class="input-group-append">
            <button type="button" id="btnAddCustom" class="btn btn-sm btn-secondary">
              <i class="fas fa-plus"></i> Ajouter
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
const menuItems = document.getElementById('menuItems');
let pickerTargetType = 'parent'; // 'parent' | 'child'
let pickerTargetBlock = null;    // element .menu-parent-block courant

/* ── Drag & drop parents ── */
Sortable.create(menuItems, {
    handle: '.parent-handle',
    animation: 150,
    draggable: '.menu-parent-block',
    ghostClass: 'bg-secondary'
});

/* ── Drag & drop enfants (init sur chaque .children-list) ── */
function initChildSort(listEl) {
    Sortable.create(listEl, {
        handle: '.child-handle',
        animation: 150,
        draggable: '.child-row',
        ghostClass: 'bg-secondary'
    });
}
document.querySelectorAll('.children-list').forEach(initChildSort);

/* ── Toggle sous-liens d'un parent ── */
menuItems.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-toggle-children');
    if (btn) {
        const block = btn.closest('.menu-parent-block');
        const cb = block.querySelector('.children-block');
        const icon = btn.querySelector('.chevron-icon');
        if (cb.classList.contains('d-none')) {
            cb.classList.remove('d-none');
            icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
        } else {
            cb.classList.add('d-none');
            icon.classList.replace('fa-chevron-down', 'fa-chevron-right');
        }
        return;
    }

    /* ── Supprimer parent ── */
    const rp = e.target.closest('.btn-remove-parent');
    if (rp) {
        if (confirm('Supprimer ce lien et ses sous-liens ?'))
            rp.closest('.menu-parent-block').remove();
        return;
    }

    /* ── Supprimer enfant ── */
    const rc = e.target.closest('.btn-remove-child');
    if (rc) {
        rc.closest('.child-row').remove();
        return;
    }

    /* ── Ouvrir modale pour ajouter sous-lien ── */
    const ac = e.target.closest('.btn-add-child');
    if (ac) {
        pickerTargetType = 'child';
        pickerTargetBlock = ac.closest('.menu-parent-block');
        document.getElementById('pickerTitle').innerHTML =
            '<i class="fas fa-level-down-alt mr-2"></i>Ajouter un sous-lien à « ' +
            (pickerTargetBlock.querySelector('.parent-label').value || '…') + ' »';
        $('#pagePickerModal').modal('show');
    }
});

/* ── Ajouter parent via bouton principal ── */
document.getElementById('btnAddParent').addEventListener('click', function() {
    pickerTargetType = 'parent';
    pickerTargetBlock = null;
    document.getElementById('pickerTitle').innerHTML =
        '<i class="fas fa-plus-circle mr-2"></i>Ajouter un lien';
});

/* ── Créer une ligne parent ── */
function createParentBlock(label, url) {
    const div = document.createElement('div');
    div.className = 'menu-parent-block';
    div.innerHTML = `
      <div class="menu-parent-row d-flex align-items-center gap-2 mb-1">
        <span class="parent-handle px-2 text-muted" style="cursor:grab;"><i class="fas fa-grip-vertical"></i></span>
        <input type="text" class="form-control form-control-sm parent-label" placeholder="Label" value="${esc(label)}">
        <input type="text" class="form-control form-control-sm parent-url" placeholder="/url" value="${esc(url)}">
        <button type="button" class="btn btn-sm btn-outline-info btn-toggle-children" title="Sous-liens" style="white-space:nowrap;">
          <i class="fas fa-chevron-right fa-sm chevron-icon"></i>
          <span class="child-count badge badge-info ml-1">0</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-parent" title="Supprimer">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="children-block pl-4 d-none">
        <div class="children-list mb-1"></div>
        <button type="button" class="btn btn-xs btn-outline-secondary btn-add-child mb-2">
          <i class="fas fa-plus fa-sm mr-1"></i> Ajouter un sous-lien
        </button>
      </div>
      <hr class="my-1" style="border-color:rgba(255,255,255,.08);">`;
    initChildSort(div.querySelector('.children-list'));
    return div;
}

/* ── Créer une ligne enfant ── */
function createChildRow(label, url) {
    const div = document.createElement('div');
    div.className = 'child-row d-flex align-items-center gap-2 mb-1';
    div.innerHTML = `
      <span class="child-handle px-2 text-muted" style="cursor:grab; font-size:11px;"><i class="fas fa-grip-vertical"></i></span>
      <span class="text-muted mr-1" style="font-size:13px;">↳</span>
      <input type="text" class="form-control form-control-sm child-label" placeholder="Label" value="${esc(label)}">
      <input type="text" class="form-control form-control-sm child-url" placeholder="/url" value="${esc(url)}">
      <button type="button" class="btn btn-sm btn-outline-danger btn-remove-child" title="Supprimer">
        <i class="fas fa-times"></i>
      </button>`;
    return div;
}

/* ── Pick depuis la modale ── */
function pickPage(label, url) {
    if (pickerTargetType === 'parent') {
        menuItems.appendChild(createParentBlock(label, url));
    } else if (pickerTargetBlock) {
        const list = pickerTargetBlock.querySelector('.children-list');
        const block = pickerTargetBlock.querySelector('.children-block');
        const icon  = pickerTargetBlock.querySelector('.chevron-icon');
        list.appendChild(createChildRow(label, url));
        block.classList.remove('d-none');
        icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
        updateChildCount(pickerTargetBlock);
    }
    $('#pagePickerModal').modal('hide');
}

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.pick-page');
    if (btn) pickPage(btn.dataset.label, btn.dataset.url);
});

document.getElementById('btnAddCustom').addEventListener('click', function() {
    const label = document.getElementById('customLabel').value.trim();
    const url   = document.getElementById('customUrl').value.trim();
    if (!label || !url) { alert('Label et URL requis.'); return; }
    pickPage(label, url);
    document.getElementById('customLabel').value = '';
    document.getElementById('customUrl').value   = '';
});

/* ── Mettre à jour badge compteur enfants ── */
function updateChildCount(block) {
    const n = block.querySelectorAll('.child-row').length;
    block.querySelector('.child-count').textContent = n;
}

/* ── Sérialiser en JSON avant envoi ── */
document.getElementById('menuForm').addEventListener('submit', function() {
    const items = [];
    document.querySelectorAll('#menuItems .menu-parent-block').forEach(block => {
        const label = block.querySelector('.parent-label').value.trim();
        const url   = block.querySelector('.parent-url').value.trim();
        const children = [];
        block.querySelectorAll('.child-row').forEach(row => {
            const cl = row.querySelector('.child-label').value.trim();
            const cu = row.querySelector('.child-url').value.trim();
            if (cl && cu) children.push({ label: cl, url: cu });
        });
        if (label && url) items.push({ label, url, children });
    });
    document.getElementById('nav_json').value = JSON.stringify(items);
});

/* ── Utilitaire escape html ── */
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
