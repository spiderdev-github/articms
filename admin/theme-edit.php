<?php
require_once __DIR__ . '/auth.php';
requirePermission('themes');
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/config.php';

$csrf        = getCsrfToken();
$themeId     = basename($_GET['theme'] ?? 'default');
$themesDir   = __DIR__ . '/../themes';
$themeDir    = $themesDir . '/' . $themeId;
$activeTheme = getSetting('active_theme', 'default');

if (!is_dir($themeDir)) {
    header("Location: themes.php"); exit;
}

// ─── Charger les métadonnées ──────────────────────────────────────────────────
$metaFile = $themeDir . '/theme.json';
$meta     = file_exists($metaFile) ? (json_decode(file_get_contents($metaFile), true) ?: []) : [];
$themeName = $meta['name'] ?? $themeId;

// ─── Liste des fichiers éditables ────────────────────────────────────────────
$editableFiles = [
    'variables.css'         => ['label' => 'variables.css',       'icon' => 'fa-palette',   'lang' => 'css'],
    'style.css'             => ['label' => 'style.css',           'icon' => 'fa-css3',      'lang' => 'css'],
    'responsive.css'        => ['label' => 'responsive.css',      'icon' => 'fa-mobile',    'lang' => 'css'],
    'partials/home.php'     => ['label' => 'home.php',            'icon' => 'fa-home',       'lang' => 'php'],
    // 'partials/realisations.php'     => ['label' => 'realisations.php',            'icon' => 'fa-images',       'lang' => 'php'],
    // 'partials/contact.php'     => ['label' => 'contact.php',            'icon' => 'fa-envelope',       'lang' => 'php'],
    // 'partials/page.php'     => ['label' => 'page.php',            'icon' => 'fa-file',       'lang' => 'php'],
    'partials/header.php'   => ['label' => 'header.php',          'icon' => 'fa-heading',   'lang' => 'php'],
    'partials/footer.php'   => ['label' => 'footer.php',          'icon' => 'fa-shoe-prints','lang' => 'php'],
    'theme.json'            => ['label' => 'theme.json',          'icon' => 'fa-info-circle','lang' => 'json'],
    'partials/home.json'    => ['label' => 'home.json',           'icon' => 'fa-file-alt','lang' => 'json'],
];

// ─── Fichier actif ────────────────────────────────────────────────────────────
$activeFile = $_GET['file'] ?? 'variables.css';
if (!array_key_exists($activeFile, $editableFiles)) $activeFile = 'variables.css';

$absoluteFilePath = $themeDir . '/' . $activeFile;
$fileContent      = file_exists($absoluteFilePath) ? file_get_contents($absoluteFilePath) : '';
$fileExists       = file_exists($absoluteFilePath);

// ─── Lire variables.css pour color pickers ──────────────────────────────────
$varsCssPath    = $themeDir . '/variables.css';
$varsCssContent = file_exists($varsCssPath) ? file_get_contents($varsCssPath) : '';

// Extraire les variables CSS avec valeur hexadécimale
$colorVars = [];
if (preg_match_all('/--([a-z0-9\-]+)\s*:\s*(#[0-9a-fA-F]{3,8})/', $varsCssContent, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
        $colorVars[$m[1]] = $m[2];
    }
}

include __DIR__ . '/partials/header.php';
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle mr-1"></i> Fichier enregistré avec succès.
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 m-0" style="font-size:14px;">
      <li class="breadcrumb-item"><a href="themes.php"><i class="fas fa-palette mr-1"></i>Thèmes</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($themeName) ?></li>
    </ol>
  </nav>
  <div class="d-flex" style="gap:8px;">
    <?php if ($themeId === $activeTheme): ?>
    <span class="badge badge-success p-2"><i class="fas fa-check mr-1"></i>Thème actif</span>
    <?php else: ?>
    <form method="POST" action="actions/theme-activate.php" class="d-inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="theme" value="<?= htmlspecialchars($themeId) ?>">
      <button class="btn btn-sm btn-success"><i class="fas fa-toggle-on mr-1"></i>Activer ce thème</button>
    </form>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/?_theme_preview=<?= urlencode($themeId) ?>" target="_blank"
       class="btn btn-sm btn-outline-info">
      <i class="fas fa-eye mr-1"></i>Aperçu live
    </a>
  </div>
</div>

<div class="row">

  <!-- ══════ Colonne gauche : fichiers + color pickers ══════════════════════ -->
  <div class="col-md-3">

    <!-- Liste des fichiers -->
    <div class="card card-outline card-primary mb-3">
      <div class="card-header">
        <h3 class="card-title" style="font-size:13px;"><i class="fas fa-folder-open mr-2"></i>Fichiers templates</h3>
      </div>
      <div class="card-body p-1">
          <?php foreach ($editableFiles as $fname => $finfo):
        $fpath    = $themeDir . '/' . $fname;
        $exists   = file_exists($fpath);
        $isActive = ($fname === $activeFile);
      ?>
      <a href="theme-edit.php?theme=<?= urlencode($themeId) ?>&file=<?= urlencode($fname) ?>"
         class="d-flex align-items-center p-2 rounded mb-1 <?= $isActive ? 'bg-primary' : 'text-muted' ?>"
         style="font-size:13px;text-decoration:none;gap:8px;<?= $isActive ? '' : 'background:transparent;' ?>">
        <i class="fas <?= $finfo['icon'] ?> fa-fw" style="font-size:12px;"></i>
        <span><?= $finfo['label'] ?></span>
        <?php if (!$exists): ?>
        <span class="badge badge-secondary ml-auto" style="font-size:9px;">absent</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
      </div>
    </div>
    
    <!-- Color pickers (uniquement si on édite variables.css) -->
    <?php if ($activeFile === 'variables.css' && !empty($colorVars)): ?>
    <div class="card card-body p-2">
      <small class="text-muted text-uppercase font-weight-bold mb-2 d-block px-1" style="font-size:10px;letter-spacing:.08em;">Couleurs rapides</small>
      <?php foreach ($colorVars as $varName => $hexValue): ?>
      <div class="d-flex align-items-center mb-2" style="gap:8px;">
        <input type="color" id="cp_<?= htmlspecialchars($varName) ?>"
               value="<?= htmlspecialchars(strlen($hexValue) < 5 ? $hexValue . $hexValue : $hexValue) ?>"
               style="width:30px;height:24px;border:none;border-radius:6px;padding:2px;cursor:pointer;background:transparent;"
               data-var="--<?= htmlspecialchars($varName) ?>">
        <code style="font-size:11px;flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">--<?= htmlspecialchars($varName) ?></code>
        <span id="cpv_<?= htmlspecialchars($varName) ?>" style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($hexValue) ?></span>
      </div>
      <?php endforeach; ?>
      <small class="text-muted" style="font-size:10px;">Les changements mettent à jour le textarea. N'oublie pas d'enregistrer.</small>
    </div>
    <?php endif; ?>
    
     <!-- Aide raccourcis clavier -->
    <div class="card card-body p-3" style="font-size:12px;">
      <strong class="d-block mb-2 text-muted" style="letter-spacing:.05em;text-transform:uppercase;font-size:10px;">Raccourcis</strong>
      <div class="mb-1"><kbd>Ctrl</kbd> + <kbd>S</kbd> — Enregistrer</div>
      <div class="mb-1"><kbd>Tab</kbd> — Indenter (2 espaces)</div>
      <div class="mb-1"><kbd>Shift</kbd>+<kbd>Tab</kbd> — Dés-indenter</div>
      <!-- <div class="mt-3">
        <a href="<?= htmlspecialchars($previewUrl) ?>" target="_blank" class="btn btn-sm btn-outline-info btn-block">
          <i class="fas fa-external-link-alt mr-1"></i> Voir la page live
        </a>
      </div> -->
    </div>
  </div>

  <!-- ══════ Colonne droite : éditeur ══════════════════════════════════════ -->
  <div class="col-md-9">
    <form method="POST" action="actions/theme-save.php" id="themeEditForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="theme" value="<?= htmlspecialchars($themeId) ?>">
      <input type="hidden" name="file" value="<?= htmlspecialchars($activeFile) ?>">

      <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
          <span class="badge badge-<?= $fileExists ? 'info' : 'warning' ?> mr-1">
            <?= $fileExists ? 'Existant' : 'Nouveau fichier' ?>
          </span>
          <code style="font-size:12px;"><?= htmlspecialchars($themeId . '/' . $activeFile) ?></code>
        </div>
        <div class="d-flex" style="gap:6px;">
          <?php if ($fileExists && $activeFile !== 'theme.json'): ?>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatContent()">
            <i class="fas fa-magic mr-1"></i>Formater
          </button>
          <?php endif; ?>
          <button class="btn btn-sm btn-primary">
            <i class="fas fa-save mr-1"></i>Enregistrer
          </button>
        </div>
      </div>

      <!-- Infos contextuelles par fichier -->
      <?php if ($activeFile === 'Variables.css' || $activeFile === 'variables.css'): ?>
      <div class="alert alert-info py-2 mb-2" style="font-size:12px;">
        <i class="fas fa-info-circle mr-1"></i>
        Ce fichier définit toutes les <strong>variables CSS</strong> du thème (<code>--bg</code>, <code>--brand-red</code>, etc.).
        Les color pickers ci-contre permettent de modifier les valeurs hex en direct.
      </div>
      <?php elseif (str_contains($activeFile, 'header.php') || str_contains($activeFile, 'footer.php')): ?>
      <div class="alert alert-warning py-2 mb-2" style="font-size:12px;">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Ce fichier <strong>remplace</strong> le partial HTML du thème par défaut.
        Variables disponibles : <code>$cmsName</code>, <code>$cmsPhone</code>, <code>$cmsPhoneDisplay</code>,
        <code>$navItems</code>, <code>BASE_URL</code>.
        <?php if (!$fileExists): ?>
        <br>Ce fichier <strong>n'existe pas encore</strong> — si tu l'enregistres vide, le partial par défaut sera utilisé.
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <textarea name="content" id="codeEditor"
                class="form-control font-monospace"
                rows="28"
                spellcheck="false"
                style="font-size:13px;line-height:1.6;tab-size:2;background:#1a1d23;color:#e0e4ef;border-color:#3a3d4a;resize:vertical;"><?= htmlspecialchars($fileContent) ?></textarea>

      <div class="mt-2 d-flex justify-content-between align-items-center">
        <small class="text-muted" id="charCount" style="font-size:11px;"></small>
        <button class="btn btn-primary">
          <i class="fas fa-save mr-1"></i>Enregistrer
        </button>
      </div>
    </form>

    <!-- ══ Aperçu iframe ══════════════════════════════════════════════════ -->
    <div class="mt-4">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="font-weight-bold" style="font-size:14px;"><i class="fas fa-desktop mr-1"></i>Aperçu</span>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-outline-secondary" onclick="setPreviewWidth('100%')" title="Desktop">
            <i class="fas fa-desktop"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary" onclick="setPreviewWidth('768px')" title="Tablette">
            <i class="fas fa-tablet-alt"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary" onclick="setPreviewWidth('390px')" title="Mobile">
            <i class="fas fa-mobile-alt"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary" onclick="reloadPreview()" title="Recharger">
            <i class="fas fa-redo"></i>
          </button>
        </div>
      </div>
      <div style="border:1px solid rgba(255,255,255,.12);border-radius:12px;overflow:hidden;background:#000;">
        <div id="previewWrap" style="width:100%;margin:0 auto;transition:width .3s;">
          <iframe id="previewFrame"
                  src="<?= BASE_URL ?>/?_theme_preview=<?= urlencode($themeId) ?>"
                  style="width:100%;height:520px;border:none;display:block;"
                  loading="lazy"
                  title="Aperçu thème"></iframe>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
// ── Compteur de caractères ───────────────────────────────────────────────────
const editor = document.getElementById('codeEditor');
const counter = document.getElementById('charCount');
function updateCount() {
    const n = editor.value.length;
    const lines = editor.value.split('\n').length;
    counter.textContent = lines + ' lignes · ' + n.toLocaleString() + ' caractères';
}
editor.addEventListener('input', updateCount);
updateCount();

// ── Tab → 2 espaces dans le textarea ────────────────────────────────────────
editor.addEventListener('keydown', function(e) {
    if (e.key === 'Tab') {
        e.preventDefault();
        const s = this.selectionStart, end = this.selectionEnd;
        this.value = this.value.substring(0, s) + '  ' + this.value.substring(end);
        this.selectionStart = this.selectionEnd = s + 2;
        updateCount();
    }
});

// ── Color pickers → mise à jour textarea ────────────────────────────────────
document.querySelectorAll('input[type="color"][data-var]').forEach(function(picker) {
    picker.addEventListener('input', function() {
        const varName = this.getAttribute('data-var');
        const hex = this.value.toLowerCase();
        const varId = varName.replace('--', '');
        const valueSpan = document.getElementById('cpv_' + varId);
        if (valueSpan) valueSpan.textContent = hex;

        // Remplacer dans le textarea (regex: --var-name: #xxxx)
        const re = new RegExp('(' + escapeRe(varName) + '\\s*:\\s*)(#[0-9a-fA-F]{3,8})', 'g');
        editor.value = editor.value.replace(re, '$1' + hex);
        updateCount();
    });
});

function escapeRe(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// ── Formater (indent basique) ────────────────────────────────────────────────
function formatContent() {
    // Juste normaliser les fins de ligne pour l'instant
    editor.value = editor.value
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n');
    updateCount();
}

// ── Aperçu taille ────────────────────────────────────────────────────────────
function setPreviewWidth(w) {
    document.getElementById('previewWrap').style.width = w;
}
function reloadPreview() {
    const f = document.getElementById('previewFrame');
    f.src = f.src;
}

// ── Ctrl+S pour sauvegarder ──────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('themeEditForm').submit();
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
