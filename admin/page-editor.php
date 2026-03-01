<?php
require_once __DIR__ . '/auth.php';
requirePermission('themes');
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/config.php';

$csrf    = getCsrfToken();
$rootDir = __DIR__ . '/..';

// ─── Fichiers éditables (liste blanche stricte) ───────────────────────────────
$editableFiles = [
    'index.php'         => ['label' => 'index.php',         'icon' => 'fa-home',          'desc' => 'Page d\'accueil (homepage)'],
    'page.php'          => ['label' => 'page.php',          'icon' => 'fa-file-alt',       'desc' => 'Routeur CMS (toutes les pages dynamiques)'],
    '_contact'      => ['label' => '_contact',      'icon' => 'fa-envelope',       'desc' => 'Template page Contact'],
    '_realisations.php' => ['label' => '_realisations.php', 'icon' => 'fa-paint-roller',   'desc' => 'Template page Réalisations'],
];

// ─── Fichier actif ────────────────────────────────────────────────────────────
$activeFile = $_GET['file'] ?? 'index.php';
if (!array_key_exists($activeFile, $editableFiles)) $activeFile = 'index.php';

$absPath     = $rootDir . '/' . $activeFile;
$fileContent = file_exists($absPath) ? file_get_contents($absPath) : '';
$fileExists  = file_exists($absPath);

// ─── URL de prévisualisation ──────────────────────────────────────────────────
$previewUrls = [
    'index.php'         => BASE_URL . '/',
    'page.php'          => BASE_URL . '/',
    '_contact'      => BASE_URL . '/contact',
    '_realisations.php' => BASE_URL . '/realisations',
];
$previewUrl = $previewUrls[$activeFile] ?? BASE_URL . '/';

$pageTitle = 'Éditeur de templates';
require_once __DIR__ . '/partials/header.php';
?>
<div class="content-wrapper" style="margin: -15px;">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h5 class="m-0"><i class="fas fa-code mr-2"></i>Éditeur de templates</h5>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="themes.php">Thèmes</a></li>
            <li class="breadcrumb-item"><a href="homepage.php">Page d'accueil</a></li>
            <li class="breadcrumb-item active">Templates HTML</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php if (isset($_GET['saved'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> <strong><?= htmlspecialchars($activeFile) ?></strong> enregistré avec succès.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
      <?php endif; ?>

      <?php
      if (isset($_GET['error']) && $_GET['error'] === 'syntax' && !empty($_SESSION['flash'])):
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
      ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-times-circle mr-1"></i> <?= $flash['msg'] ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
      <?php endif; ?>

      <!-- Avertissement -->
      <div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Attention :</strong> vous éditez directement les fichiers source PHP du site.
        Une erreur de syntaxe peut rendre le site inaccessible. Utilisez <strong>Ctrl+S</strong> pour sauvegarder.
      </div>

      <div class="row">

        <!-- ── Sidebar : liste des fichiers ─────────────────────────────── -->
        <div class="col-lg-3 col-md-4 mb-3">

          <div class="card card-outline card-primary mb-3">
            <div class="card-header">
              <h3 class="card-title" style="font-size:13px;"><i class="fas fa-folder-open mr-2"></i>Fichiers templates</h3>
            </div>
            <div class="card-body p-1">
              <?php foreach ($editableFiles as $fname => $finfo):
                $exists   = file_exists($rootDir . '/' . $fname);
                $isActive = ($fname === $activeFile);
              ?>
              <a href="page-editor.php?file=<?= urlencode($fname) ?>"
                 class="d-flex align-items-start p-2 rounded mb-1 <?= $isActive ? 'bg-primary text-white' : '' ?>"
                 style="text-decoration:none;gap:10px;">
                <i class="fas <?= $finfo['icon'] ?> mt-1 fa-fw" style="font-size:12px;flex-shrink:0;<?= $isActive ? '' : 'opacity:.6;' ?>"></i>
                <div>
                  <div style="font-size:13px;font-weight:<?= $isActive ? '600' : '400' ?>;"><?= $finfo['label'] ?></div>
                  <div style="font-size:11px;opacity:.65;"><?= $finfo['desc'] ?></div>
                </div>
                <?php if (!$exists): ?>
                <span class="badge badge-secondary ml-auto mt-1" style="font-size:9px;flex-shrink:0;">absent</span>
                <?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Aide raccourcis clavier -->
          <div class="card card-body p-3" style="font-size:12px;">
            <strong class="d-block mb-2 text-muted" style="letter-spacing:.05em;text-transform:uppercase;font-size:10px;">Raccourcis</strong>
            <div class="mb-1"><kbd>Ctrl</kbd> + <kbd>S</kbd> — Enregistrer</div>
            <div class="mb-1"><kbd>Tab</kbd> — Indenter (2 espaces)</div>
            <div class="mb-1"><kbd>Shift</kbd>+<kbd>Tab</kbd> — Dés-indenter</div>
            <div class="mt-3">
              <a href="<?= htmlspecialchars($previewUrl) ?>" target="_blank" class="btn btn-sm btn-outline-info btn-block">
                <i class="fas fa-external-link-alt mr-1"></i> Voir la page live
              </a>
            </div>
          </div>

        </div><!-- /.sidebar -->

        <!-- ── Éditeur + Aperçu ──────────────────────────────────────────── -->
        <div class="col-lg-9 col-md-8">

          <form method="POST" action="actions/page-editor-save.php" id="pageEditorForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="file" value="<?= htmlspecialchars($activeFile) ?>">

            <!-- Header éditeur -->
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center" style="gap:8px;">
                <i class="fas <?= $editableFiles[$activeFile]['icon'] ?> text-muted"></i>
                <strong style="font-size:14px;"><?= htmlspecialchars($activeFile) ?></strong>
                <?php if (!$fileExists): ?>
                <span class="badge badge-warning" style="font-size:10px;">Fichier absent</span>
                <?php else: ?>
                <span class="badge badge-secondary" style="font-size:10px;" id="charCount"></span>
                <?php endif; ?>
              </div>
              <div class="d-flex" style="gap:6px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatContent()" title="Normaliser les fins de ligne">
                  <i class="fas fa-align-left"></i>
                </button>
                <button type="submit" class="btn btn-sm btn-primary">
                  <i class="fas fa-save mr-1"></i>Enregistrer
                </button>
              </div>
            </div>

            <!-- Textarea éditeur -->
            <textarea name="content" id="codeEditor"
                      spellcheck="false"
                      style="width:100%;min-height:480px;font-family:'JetBrains Mono','Fira Code','Courier New',monospace;font-size:13px;line-height:1.6;tab-size:2;background:#1a1d23;color:#e0e4ef;border:1px solid #3a3d4a;border-radius:6px;padding:14px;resize:vertical;outline:none;"><?= htmlspecialchars($fileContent) ?></textarea>

            <div class="mt-2 d-flex justify-content-between align-items-center">
              <small class="text-muted" id="lineInfo" style="font-size:11px;"></small>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>Enregistrer les modifications
              </button>
            </div>
          </form>

          <!-- ── Aperçu iframe ─────────────────────────────────────────── -->
          <div class="mt-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="font-weight-bold" style="font-size:14px;"><i class="fas fa-desktop mr-2"></i>Aperçu</span>
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="setPreviewWidth('100%')" title="Desktop"><i class="fas fa-desktop"></i></button>
                <button type="button" class="btn btn-outline-secondary" onclick="setPreviewWidth('768px')" title="Tablette"><i class="fas fa-tablet-alt"></i></button>
                <button type="button" class="btn btn-outline-secondary" onclick="setPreviewWidth('390px')" title="Mobile"><i class="fas fa-mobile-alt"></i></button>
                <button type="button" class="btn btn-outline-secondary" onclick="reloadPreview()" title="Recharger"><i class="fas fa-redo"></i></button>
              </div>
            </div>
            <div style="border:1px solid rgba(255,255,255,.12);border-radius:10px;overflow:hidden;background:#000;">
              <div id="previewWrap" style="width:100%;margin:0 auto;transition:width .3s;">
                <iframe id="previewFrame"
                        src="<?= htmlspecialchars($previewUrl) ?>"
                        style="width:100%;height:520px;border:none;display:block;"
                        loading="lazy"
                        title="Aperçu de la page"></iframe>
              </div>
            </div>
          </div>

        </div><!-- /.col editor -->
      </div><!-- /.row -->

    </div><!-- /.container-fluid -->
  </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<script>
const editor   = document.getElementById('codeEditor');
const counter  = document.getElementById('charCount');
const lineInfo = document.getElementById('lineInfo');

function updateCount() {
    const n     = editor.value.length;
    const lines = editor.value.split('\n').length;
    if (counter)  counter.textContent  = lines + ' lignes · ' + n.toLocaleString() + ' car.';
    if (lineInfo) lineInfo.textContent = lines + ' lignes · ' + n.toLocaleString() + ' caractères';
}

editor.addEventListener('input', updateCount);
updateCount();

// ── Tab / Shift+Tab ──────────────────────────────────────────────────────────
editor.addEventListener('keydown', function(e) {
    if (e.key === 'Tab') {
        e.preventDefault();
        const s = this.selectionStart, end = this.selectionEnd;
        if (e.shiftKey) {
            // Dés-indenter : retirer 2 espaces au début de la ligne
            const before = this.value.substring(0, s);
            const lineStart = before.lastIndexOf('\n') + 1;
            if (this.value.substring(lineStart, lineStart + 2) === '  ') {
                this.value = this.value.substring(0, lineStart) + this.value.substring(lineStart + 2);
                this.selectionStart = this.selectionEnd = Math.max(lineStart, s - 2);
            }
        } else {
            this.value = this.value.substring(0, s) + '  ' + this.value.substring(end);
            this.selectionStart = this.selectionEnd = s + 2;
        }
        updateCount();
    }
});

function formatContent() {
    editor.value = editor.value.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    updateCount();
}

// ── Ctrl+S ───────────────────────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('pageEditorForm').submit();
    }
});

// ── Aperçu responsive ────────────────────────────────────────────────────────
function setPreviewWidth(w) {
    document.getElementById('previewWrap').style.width = w;
}
function reloadPreview() {
    const f = document.getElementById('previewFrame');
    f.src = f.src;
}

// ── Highlight couleur fond sur focus ─────────────────────────────────────────
editor.addEventListener('focus', function() {
    this.style.borderColor = '#3b82f6';
    this.style.boxShadow   = '0 0 0 2px rgba(59,130,246,.3)';
});
editor.addEventListener('blur', function() {
    this.style.borderColor = '#3a3d4a';
    this.style.boxShadow   = 'none';
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
