<?php
require_once __DIR__ . '/auth.php';
requirePermission('themes');
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/config.php';

$csrf        = getCsrfToken();
$activeTheme = getSetting('active_theme', 'default');
$themesDir   = __DIR__ . '/../themes';
$notice      = $_GET['notice'] ?? '';

// ─── Scanner les dossiers de thèmes ──────────────────────────────────────────
$themes = [];
foreach (glob($themesDir . '/*/theme.json') as $jsonFile) {
    $folder = basename(dirname($jsonFile));
    $meta   = json_decode(file_get_contents($jsonFile), true) ?: [];
    $themes[$folder] = [
        'id'          => $folder,
        'name'        => $meta['name']        ?? $folder,
        'description' => $meta['description'] ?? '',
        'version'     => $meta['version']     ?? '1.0',
        'author'      => $meta['author']      ?? '',
        'preview_bg'  => $meta['preview_bg']  ?? '#0b0c10',
        'preview_accent' => $meta['preview_accent'] ?? '#b11226',
        'preview_gold'   => $meta['preview_gold']   ?? '#f2b705',
        'is_active'   => ($folder === $activeTheme),
        'files'       => array_map('basename', glob(dirname($jsonFile) . '/*.{css,php}', GLOB_BRACE)),
    ];
}

// S'il n'y a aucun thème "default" (installation fraîche), créer le dossier
if (!isset($themes['default'])) {
    $themes = ['default' => [
        'id' => 'default', 'name' => 'Joker Peintre – Original',
        'description' => 'Thème par défaut.', 'version' => '1.0', 'author' => '',
        'preview_bg' => '#0b0c10', 'preview_accent' => '#b11226', 'preview_gold' => '#f2b705',
        'is_active' => true, 'files' => [],
    ]] + $themes;
}

include __DIR__ . '/partials/header.php';
?>

<?php if ($notice === 'activated'): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle mr-1"></i> Thème activé avec succès.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php elseif ($notice === 'deleted'): ?>
<div class="alert alert-info alert-dismissible fade show">
  <i class="fas fa-trash mr-1"></i> Thème supprimé.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php elseif ($notice === 'created'): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-copy mr-1"></i> Thème dupliqué avec succès.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="m-0"><i class="fas fa-palette mr-2"></i>Gestion des thèmes</h4>
  <div class="d-flex" style="gap:6px;">
    <a href="homepage.php" class="btn btn-info btn-sm">
      <i class="fas fa-home mr-1"></i> Page d'accueil
    </a>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNewTheme">
      <i class="fas fa-plus mr-1"></i> Nouveau thème
    </button>
  </div>
</div>

<div class="row">
<?php foreach ($themes as $theme): ?>
  <div class="col-md-4 mb-4">
    <div class="card card-body h-100 d-flex flex-column <?= $theme['is_active'] ? 'border-success' : '' ?>">

      <!-- Swatch preview -->
      <div class="rounded mb-3 position-relative overflow-hidden"
           style="height:90px;background:<?= htmlspecialchars($theme['preview_bg']) ?>;">
        <!-- Radial glow -->
        <div style="position:absolute;inset:0;background:
          radial-gradient(60% 80% at 20% 20%, <?= htmlspecialchars($theme['preview_accent']) ?>55, transparent 70%),
          radial-gradient(50% 70% at 80% 10%, <?= htmlspecialchars($theme['preview_gold']) ?>33, transparent 70%);"></div>
        <!-- Mini brand strip -->
        <div style="position:absolute;bottom:10px;left:12px;right:12px;display:flex;gap:6px;align-items:center;">
          <div style="width:24px;height:24px;border-radius:8px;background:<?= htmlspecialchars($theme['preview_accent']) ?>;flex-shrink:0;"></div>
          <div style="flex:1;">
            <div style="height:7px;border-radius:4px;background:rgba(255,255,255,.25);margin-bottom:4px;width:60%;"></div>
            <div style="height:5px;border-radius:4px;background:rgba(255,255,255,.14);width:40%;"></div>
          </div>
          <div style="width:28px;height:16px;border-radius:4px;background:<?= htmlspecialchars($theme['preview_accent']) ?>;"></div>
        </div>
        <?php if ($theme['is_active']): ?>
        <span class="badge badge-success" style="position:absolute;top:8px;right:8px;font-size:11px;">
          <i class="fas fa-check mr-1"></i>Actif
        </span>
        <?php endif; ?>
      </div>

      <h5 class="mb-1"><?= htmlspecialchars($theme['name']) ?></h5>
      <small class="text-muted mb-2"><?= htmlspecialchars($theme['description']) ?></small>
      <div class="mb-3">
        <span class="badge badge-secondary">v<?= htmlspecialchars($theme['version']) ?></span>
        <span class="badge badge-dark ml-1"><?= count($theme['files']) ?> fichier(s)</span>
      </div>

      <div class="mt-auto d-flex" style="gap:6px;flex-wrap:wrap;">
        <a href="theme-edit.php?theme=<?= urlencode($theme['id']) ?>"
           class="btn btn-sm btn-outline-info">
          <i class="fas fa-edit mr-1"></i>Modifier
        </a>

        <?php if (!$theme['is_active']): ?>
        <form method="POST" action="actions/theme-activate.php" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="theme" value="<?= htmlspecialchars($theme['id']) ?>">
          <button class="btn btn-sm btn-success">
            <i class="fas fa-toggle-on mr-1"></i>Activer
          </button>
        </form>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/?_theme_preview=<?= urlencode($theme['id']) ?>" target="_blank"
           class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-eye mr-1"></i>Aperçu
        </a>
        <?php endif; ?>

        <button class="btn btn-sm btn-outline-warning"
                onclick="openDuplicate('<?= htmlspecialchars(addslashes($theme['id'])) ?>','<?= htmlspecialchars(addslashes($theme['name'])) ?>')">
          <i class="fas fa-copy mr-1"></i>Dupliquer
        </button>

        <?php if (!$theme['is_active']): ?>
        <form method="POST" action="actions/theme-delete.php" class="d-inline"
              onsubmit="return confirm('Supprimer ce thème ? Cette action est irréversible.')">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="theme" value="<?= htmlspecialchars($theme['id']) ?>">
          <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- ═══ Modal Nouveau thème ═══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewTheme">
  <div class="modal-dialog">
    <div class="modal-content bg-dark">
      <div class="modal-header border-secondary">
        <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Nouveau thème</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="actions/theme-create.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="modal-body">
          <div class="form-group">
            <label class="font-weight-bold">Nom du thème <span class="text-danger">*</span></label>
            <input type="text" name="theme_name" class="form-control" required placeholder="Mon thème" maxlength="60">
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Cloner depuis</label>
            <select name="clone_from" class="form-control">
              <?php foreach ($themes as $t): ?>
              <option value="<?= htmlspecialchars($t['id']) ?>" <?= $t['is_active'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['name']) ?> (<?= $t['id'] ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Description</label>
            <input type="text" name="theme_description" class="form-control" placeholder="Description courte" maxlength="160">
          </div>
        </div>
        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
          <button class="btn btn-primary"><i class="fas fa-copy mr-1"></i>Créer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ Modal Dupliquer ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalDuplicate">
  <div class="modal-dialog">
    <div class="modal-content bg-dark">
      <div class="modal-header border-secondary">
        <h5 class="modal-title"><i class="fas fa-copy mr-2"></i>Dupliquer le thème</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="actions/theme-create.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="clone_from" id="dupCloneFrom">
        <div class="modal-body">
          <div class="form-group">
            <label class="font-weight-bold">Nom du nouveau thème <span class="text-danger">*</span></label>
            <input type="text" name="theme_name" id="dupName" class="form-control" required maxlength="60">
          </div>
          <input type="hidden" name="theme_description" value="">
        </div>
        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
          <button class="btn btn-primary"><i class="fas fa-copy mr-1"></i>Dupliquer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openDuplicate(id, name) {
  document.getElementById('dupCloneFrom').value = id;
  document.getElementById('dupName').value = 'Copie de ' + name;
  $('#modalDuplicate').modal('show');
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
