<?php
require_once __DIR__ . '/partials/header.php';
requirePermission('forms');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);
$form = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([$id]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$form) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Formulaire introuvable.'];
        header('Location: forms.php');
        exit;
    }
    $form['fields']   = json_decode($form['fields'],   true) ?: [];
    $form['settings'] = json_decode($form['settings'], true) ?: [];
}

// Initial state
$initSteps = $form ? ($form['fields']['steps'] ?? []) : [[
    'label'  => 'Coordonnées',
    'fields' => [
        ['id'=>uniqid('f'),'type'=>'text', 'name'=>'name', 'label'=>'Nom complet','required'=>true, 'placeholder'=>''],
        ['id'=>uniqid('f'),'type'=>'email','name'=>'email','label'=>'Email',      'required'=>true, 'placeholder'=>''],
        ['id'=>uniqid('f'),'type'=>'tel',  'name'=>'phone','label'=>'Téléphone',  'required'=>false,'placeholder'=>''],
    ],
]];

// Ensure each field has a unique id
foreach ($initSteps as &$step) {
    foreach ($step['fields'] as &$f) {
        if (empty($f['id'])) $f['id'] = uniqid('f');
    }
}
unset($step, $f);

$initSettings = $form['settings'] ?? [
    'email_to'        => getSetting('company_email', ''),
    'email_subject'   => 'Nouveau message - Joker Peintre',
    'success_message' => 'Merci. Nous vous recontactons rapidement.',
    'redirect_url'    => '',
    'use_recaptcha'   => true,
    'save_submission' => true,
    'submit_label'    => 'Envoyer',
];

$initName  = htmlspecialchars($form['name']        ?? '');
$initSlug  = htmlspecialchars($form['slug']        ?? '');
$initDesc  = htmlspecialchars($form['description'] ?? '');

$fieldTypeLabels = [
    'text'=>'Texte','email'=>'Email','tel'=>'Téléphone','number'=>'Nombre',
    'select'=>'Liste déroulante','textarea'=>'Zone de texte','checkbox'=>'Case à cocher','radio'=>'Boutons radio',
];
$fieldTypeIcons = [
    'text'=>'fa-font','email'=>'fa-at','tel'=>'fa-phone','number'=>'fa-hashtag',
    'select'=>'fa-list','textarea'=>'fa-align-left','checkbox'=>'fa-check-square','radio'=>'fa-dot-circle',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">
    <i class="fas fa-clipboard-list mr-2"></i>
    <?= $id ? 'Modifier le formulaire' : 'Nouveau formulaire' ?>
  </h4>
  <a href="forms.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Retour</a>
</div>

<form id="formBuilder" method="POST" action="actions/form-save.php">
  <input type="hidden" name="csrf_token"   value="<?= $csrf ?>">
  <input type="hidden" name="form_id"      value="<?= $id ?>">
  <input type="hidden" name="fields_json"  id="fieldsJson"   value="">
  <input type="hidden" name="settings_json" id="settingsJson" value="">

  <div class="row">
    <!-- ═══ LEFT — Builder ═══════════════════════════════════════════════ -->
    <div class="col-lg-8 mb-4">

      <!-- Form identity -->
      <div class="card mb-3">
        <div class="card-header border-0 py-2 px-3" style="background:rgba(255,255,255,.04);">
          <b>Identité du formulaire</b>
        </div>
        <div class="card-body py-3">
          <div class="row">
            <div class="col-md-5 mb-2">
              <label class="mb-1 small">Nom <span class="text-danger">*</span></label>
              <input type="text" name="form_name" class="form-control form-control-sm" value="<?= $initName ?>" required id="inputName" placeholder="Ex: Formulaire contact">
            </div>
            <div class="col-md-3 mb-2">
              <label class="mb-1 small">Slug <span class="text-danger">*</span></label>
              <input type="text" name="form_slug" class="form-control form-control-sm" value="<?= $initSlug ?>" required id="inputSlug" pattern="[a-z0-9\-]+" placeholder="contact">
              <small class="text-muted">Lettres, chiffres, tirets</small>
            </div>
            <div class="col-md-4 mb-2">
              <label class="mb-1 small">Description</label>
              <input type="text" name="form_description" class="form-control form-control-sm" value="<?= $initDesc ?>" placeholder="Usage interne...">
            </div>
          </div>
          <div class="mt-1">
            <small class="text-muted">Shortcode : <code id="shortcodePreview">[form:<?= $initSlug ?: 'slug' ?>]</code></small>
          </div>
        </div>
      </div>

      <!-- Steps + Fields -->
      <div id="stepsContainer"></div>

      <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btnAddStep">
        <i class="fas fa-plus mr-1"></i>Ajouter une étape
      </button>

    </div>

    <!-- ═══ RIGHT — Settings ══════════════════════════════════════════════ -->
    <div class="col-lg-4 mb-4">
      <div class="card mb-3" style="position:sticky;top:80px;">
        <div class="card-header border-0 py-2 px-3" style="background:rgba(255,255,255,.04);">
          <b>Paramètres</b>
        </div>
        <div class="card-body py-3" id="settingsPanel">
          <div class="mb-3">
            <label class="mb-1 small">Envoyer à (email)</label>
            <input type="email" class="form-control form-control-sm" id="s_email_to" placeholder="dest@mail.fr">
          </div>
          <div class="mb-3">
            <label class="mb-1 small">Sujet email</label>
            <input type="text" class="form-control form-control-sm" id="s_email_subject" placeholder="Nouveau message">
          </div>
          <div class="mb-3">
            <label class="mb-1 small">Message de succès</label>
            <textarea class="form-control form-control-sm" id="s_success_message" rows="2" placeholder="Merci, nous vous recontactons."></textarea>
          </div>
          <div class="mb-3">
            <label class="mb-1 small">URL de redirection après envoi</label>
            <input type="text" class="form-control form-control-sm" id="s_redirect_url" placeholder="contact (laisser vide = même page)">
          </div>
          <div class="mb-3">
            <label class="mb-1 small">Texte bouton soumission</label>
            <input type="text" class="form-control form-control-sm" id="s_submit_label" placeholder="Envoyer">
          </div>
          <div class="mb-3">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="s_use_recaptcha">
              <label class="custom-control-label small" for="s_use_recaptcha">reCAPTCHA v3</label>
            </div>
          </div>
          <div class="mb-3">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="s_save_submission">
              <label class="custom-control-label small" for="s_save_submission">Enregistrer les soumissions</label>
            </div>
          </div>
          <div class="mb-3">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="s_is_active" checked>
              <label class="custom-control-label small" for="s_is_active">Formulaire actif</label>
            </div>
          </div>
        </div>
        <div class="card-footer border-0 pt-0">
          <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save mr-1"></i>Enregistrer
          </button>
        </div>
      </div>
    </div>
  </div><!-- /row -->
</form>

<!-- ═════════ Field Edit Modal ═════════════════════════════════════════════ -->
<div class="modal fade" id="modalField" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="background:#1a1d23;border:1px solid rgba(255,255,255,.12);">
      <div class="modal-header border-0">
        <h5 class="modal-title">Éditer le champ</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="mFieldId">
        <input type="hidden" id="mStepIdx">

        <div class="row mb-3">
          <div class="col-6">
            <label class="small mb-1">Type</label>
            <select class="form-control form-control-sm" id="mType">
              <?php foreach ($fieldTypeLabels as $t => $lbl): ?>
                <option value="<?= $t ?>"><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 d-flex align-items-end">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="mRequired">
              <label class="custom-control-label" for="mRequired">Obligatoire</label>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-6">
            <label class="small mb-1">Label</label>
            <input type="text" class="form-control form-control-sm" id="mLabel" placeholder="Nom complet">
          </div>
          <div class="col-6">
            <label class="small mb-1">Nom (name=)</label>
            <input type="text" class="form-control form-control-sm" id="mName" placeholder="name">
          </div>
        </div>

        <div class="mb-3" id="mPlaceholderRow">
          <label class="small mb-1">Placeholder</label>
          <input type="text" class="form-control form-control-sm" id="mPlaceholder" placeholder="">
        </div>

        <div class="mb-3" id="mRowsRow" style="display:none;">
          <label class="small mb-1">Nombre de lignes</label>
          <input type="number" class="form-control form-control-sm" id="mRows" value="5" min="2" max="20">
        </div>

        <div class="mb-3" id="mCheckboxLabelRow" style="display:none;">
          <label class="small mb-1">Texte de la case</label>
          <input type="text" class="form-control form-control-sm" id="mCheckboxLabel">
        </div>

        <div id="mOptionsRow" style="display:none;">
          <label class="small mb-1">Options</label>
          <div id="mOptionsList"></div>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnAddOption">
            <i class="fas fa-plus mr-1"></i>Ajouter une option
          </button>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-primary" id="btnSaveField">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════════════════
   FORM BUILDER STATE
═══════════════════════════════════════════════════════════════════════════ */
var state = {
  steps: <?= json_encode($initSteps, JSON_UNESCAPED_UNICODE) ?>
};

var settings = <?= json_encode($initSettings, JSON_UNESCAPED_UNICODE) ?>;
var isActive = <?= $form ? (int)($form['is_active'] ?? 1) : 1 ?>;

var fieldTypeIcons = {
  text:'fa-font', email:'fa-at', tel:'fa-phone', number:'fa-hashtag',
  select:'fa-list', textarea:'fa-align-left', checkbox:'fa-check-square', radio:'fa-dot-circle'
};
var fieldTypeLabels = {
  text:'Texte', email:'Email', tel:'Téléphone', number:'Nombre',
  select:'Liste déroulante', textarea:'Zone de texte', checkbox:'Case à cocher', radio:'Boutons radio'
};

/* ── Unique ID ─────────────────────────────────────────────────────────── */
function uid(){ return 'f'+(Math.random()*1e8|0).toString(36); }

/* ── Slug helper ────────────────────────────────────────────────────────── */
function slugify(s){ return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''); }

/* ── Render ─────────────────────────────────────────────────────────────── */
function render(){
  var c = document.getElementById('stepsContainer');
  if(!c) return;
  c.innerHTML = '';

  state.steps.forEach(function(step, si){
    var div = document.createElement('div');
    div.className = 'card mb-3';
    div.dataset.stepIdx = si;

    var headerBtns = '<div class="d-flex" style="gap:6px;">'
      + (si > 0 ? '<button type="button" class="btn btn-xs btn-outline-secondary step-up" data-si="'+si+'" title="Monter"><i class="fas fa-arrow-up"></i></button>' : '')
      + (si < state.steps.length-1 ? '<button type="button" class="btn btn-xs btn-outline-secondary step-dn" data-si="'+si+'" title="Descendre"><i class="fas fa-arrow-down"></i></button>' : '')
      + (state.steps.length > 1 ? '<button type="button" class="btn btn-xs btn-outline-danger step-del" data-si="'+si+'" title="Supprimer l\'étape"><i class="fas fa-times"></i></button>' : '')
      + '</div>';

    div.innerHTML = '<div class="card-header d-flex justify-content-between align-items-center py-2 px-3" style="background:rgba(255,255,255,.04);">'
      + '<div class="d-flex align-items-center">'
      + (state.steps.length > 1 ? '<span class="badge badge-primary mr-2">Étape '+(si+1)+'</span>' : '')
      + '<input type="text" class="form-control form-control-sm step-label" data-si="'+si+'" value="'+esc(step.label||('Étape '+(si+1)))+'" style="background:transparent;border:none;color:#fff;width:160px;font-weight:600;">'
      + '</div>'
      + headerBtns
      + '</div>'
      + '<div class="card-body p-2">'
      + '<div class="fields-list" data-si="'+si+'"></div>'
      + '<div class="mt-2 px-1">'
      + '<div class="dropdown">'
      + '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown"><i class="fas fa-plus mr-1"></i>Ajouter un champ</button>'
      + '<div class="dropdown-menu" style="background:#1a1d23;">'
      + Object.entries(fieldTypeLabels).map(function(e){ return '<a class="dropdown-item add-field" data-si="'+si+'" data-type="'+e[0]+'" href="#"><i class="fas '+fieldTypeIcons[e[0]]+' mr-2 fa-fw" style="width:16px;"></i>'+e[1]+'</a>'; }).join('')
      + '</div></div></div>'
      + '</div>';

    c.appendChild(div);

    // Render fields
    var list = div.querySelector('.fields-list');
    step.fields.forEach(function(f){
      list.appendChild(makeFieldRow(f, si));
    });
  });
}

function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function makeFieldRow(f, si){
  var row = document.createElement('div');
  row.className = 'field-row d-flex align-items-center p-2 mb-1';
  row.dataset.fieldId  = f.id;
  row.dataset.stepIdx  = si;
  row.style.cssText    = 'background:rgba(255,255,255,.04);border-radius:8px;gap:8px;';

  var ico = fieldTypeIcons[f.type] || 'fa-font';

  row.innerHTML = '<i class="fas fa-grip-vertical text-muted" style="cursor:grab;"></i>'
    + '<i class="fas '+ico+' fa-fw text-muted" style="width:18px;"></i>'
    + '<span class="flex-grow-1 small">'+esc(f.label||f.name)+'<span class="text-muted ml-1" style="font-size:11px;">['+esc(f.name)+']</span>'+(f.required?'<span class="text-danger ml-1">*</span>':'')+'</span>'
    + '<span class="badge badge-secondary mr-1" style="font-size:10px;">'+esc(fieldTypeLabels[f.type]||f.type)+'</span>'
    + '<button type="button" class="btn btn-xs btn-outline-secondary field-up" title="Monter"><i class="fas fa-arrow-up"></i></button>'
    + '<button type="button" class="btn btn-xs btn-outline-secondary field-dn" title="Descendre"><i class="fas fa-arrow-down"></i></button>'
    + '<button type="button" class="btn btn-xs btn-outline-info field-edit" title="Éditer"><i class="fas fa-edit"></i></button>'
    + '<button type="button" class="btn btn-xs btn-outline-danger field-del" title="Supprimer"><i class="fas fa-times"></i></button>';

  return row;
}

/* ── Delegated Events ───────────────────────────────────────────────────── */
document.getElementById('stepsContainer').addEventListener('click', function(e){
  var t = e.target.closest('[class*=" field-"], [class$=" field-"], .field-up,.field-dn,.field-edit,.field-del,.add-field,.step-up,.step-dn,.step-del');
  if (!t) return;
  var row  = t.closest('.field-row');
  var si   = parseInt(row ? row.dataset.stepIdx : (t.dataset.si ?? 0));
  var fid  = row ? row.dataset.fieldId : null;
  var fi   = fid ? state.steps[si].fields.findIndex(function(x){ return x.id===fid; }) : -1;

  if (t.classList.contains('field-edit')){
    openFieldModal(si, fi);
  } else if (t.classList.contains('field-del')){
    if (confirm('Supprimer ce champ ?')){ state.steps[si].fields.splice(fi,1); render(); }
  } else if (t.classList.contains('field-up') && fi > 0){
    var tmp=state.steps[si].fields[fi]; state.steps[si].fields[fi]=state.steps[si].fields[fi-1]; state.steps[si].fields[fi-1]=tmp; render();
  } else if (t.classList.contains('field-dn') && fi < state.steps[si].fields.length-1){
    var tmp=state.steps[si].fields[fi]; state.steps[si].fields[fi]=state.steps[si].fields[fi+1]; state.steps[si].fields[fi+1]=tmp; render();
  } else if (t.classList.contains('add-field')){
    var type = t.dataset.type||'text';
    var label = fieldTypeLabels[type]||'Champ';
    var names = state.steps.flatMap(function(s){ return s.fields.map(function(f){ return f.name; }); });
    var baseName = type; var n=1;
    while(names.indexOf(baseName+(n>1?n:''))>=0) n++;
    state.steps[si].fields.push({ id:uid(), type:type, name:baseName+(n>1?n:''), label:label, required:false, placeholder:'', options:['Option 1','Option 2'], rows:5 });
    render();
  } else if (t.classList.contains('step-up') && si > 0){
    var tmp=state.steps[si]; state.steps[si]=state.steps[si-1]; state.steps[si-1]=tmp; render();
  } else if (t.classList.contains('step-dn') && si < state.steps.length-1){
    var tmp=state.steps[si]; state.steps[si]=state.steps[si+1]; state.steps[si+1]=tmp; render();
  } else if (t.classList.contains('step-del')){
    if (confirm('Supprimer cette étape et ses champs ?')){ state.steps.splice(si,1); render(); }
  }
});

document.getElementById('stepsContainer').addEventListener('input', function(e){
  if (e.target.classList.contains('step-label')){
    var si = parseInt(e.target.dataset.si);
    state.steps[si].label = e.target.value;
  }
});

document.getElementById('btnAddStep').addEventListener('click', function(){
  state.steps.push({ label: 'Étape '+(state.steps.length+1), fields:[] });
  render();
});

/* ── Field Modal ─────────────────────────────────────────────────────────── */
function openFieldModal(si, fi){
  var f = state.steps[si].fields[fi];
  document.getElementById('mFieldId').value = f.id;
  document.getElementById('mStepIdx').value = si;

  document.getElementById('mType').value         = f.type||'text';
  document.getElementById('mLabel').value         = f.label||'';
  document.getElementById('mName').value          = f.name||'';
  document.getElementById('mRequired').checked    = !!f.required;
  document.getElementById('mPlaceholder').value   = f.placeholder||'';
  document.getElementById('mRows').value          = f.rows||5;
  document.getElementById('mCheckboxLabel').value = f.checkbox_label||f.label||'';

  // Rebuild options list
  renderOptions(f.options||[]);
  updateModalByType(f.type||'text');

  $('#modalField').modal('show');
}

document.getElementById('mType').addEventListener('change', function(){
  updateModalByType(this.value);
});

function updateModalByType(type){
  var hasPlaceholder = ['text','email','tel','number','textarea'].indexOf(type)>=0;
  var hasRows        = type==='textarea';
  var hasOptions     = ['select','radio'].indexOf(type)>=0;
  var hasCheckbox    = type==='checkbox';

  document.getElementById('mPlaceholderRow').style.display  = hasPlaceholder ? '' : 'none';
  document.getElementById('mRowsRow').style.display         = hasRows        ? '' : 'none';
  document.getElementById('mOptionsRow').style.display      = hasOptions     ? '' : 'none';
  document.getElementById('mCheckboxLabelRow').style.display= hasCheckbox    ? '' : 'none';
}

function renderOptions(opts){
  var list = document.getElementById('mOptionsList');
  list.innerHTML = '';
  opts.forEach(function(opt, i){
    var row = document.createElement('div');
    row.className = 'input-group input-group-sm mb-1';
    row.innerHTML = '<input type="text" class="form-control opt-val" value="'+esc(opt)+'">'
      +'<div class="input-group-append">'
      +'<button type="button" class="btn btn-outline-danger opt-del" data-i="'+i+'"><i class="fas fa-times"></i></button>'
      +'</div>';
    list.appendChild(row);
  });
}

document.getElementById('mOptionsList').addEventListener('click', function(e){
  var btn = e.target.closest('.opt-del');
  if (!btn) return;
  btn.closest('.input-group').remove();
});

document.getElementById('btnAddOption').addEventListener('click', function(){
  var list = document.getElementById('mOptionsList');
  var row = document.createElement('div');
  row.className='input-group input-group-sm mb-1';
  var i=list.querySelectorAll('.opt-val').length;
  row.innerHTML='<input type="text" class="form-control opt-val" value="Option '+(i+1)+'">'
    +'<div class="input-group-append"><button type="button" class="btn btn-outline-danger opt-del"><i class="fas fa-times"></i></button></div>';
  list.appendChild(row);
  row.querySelector('input').focus();
  row.querySelector('input').select();
});

document.getElementById('btnSaveField').addEventListener('click', function(){
  var si  = parseInt(document.getElementById('mStepIdx').value);
  var fid = document.getElementById('mFieldId').value;
  var fi  = state.steps[si].fields.findIndex(function(x){ return x.id===fid; });
  if (fi < 0) return;

  var opts = Array.from(document.querySelectorAll('#mOptionsList .opt-val')).map(function(i){ return i.value.trim(); }).filter(Boolean);

  state.steps[si].fields[fi] = {
    id:             fid,
    type:           document.getElementById('mType').value,
    label:          document.getElementById('mLabel').value.trim(),
    name:           slugify(document.getElementById('mName').value.trim()||document.getElementById('mLabel').value),
    required:       document.getElementById('mRequired').checked,
    placeholder:    document.getElementById('mPlaceholder').value,
    rows:           parseInt(document.getElementById('mRows').value)||5,
    options:        opts,
    checkbox_label: document.getElementById('mCheckboxLabel').value,
  };

  $('#modalField').modal('hide');
  render();
});

// Auto-slug label→name
document.getElementById('mLabel').addEventListener('input', function(){
  document.getElementById('mName').value = slugify(this.value);
});

/* ── Slug preview ───────────────────────────────────────────────────────── */
var inputName = document.getElementById('inputName');
var inputSlug = document.getElementById('inputSlug');
var shortcodePreview = document.getElementById('shortcodePreview');

inputName.addEventListener('input', function(){
  if (!<?= $id ? 'true' : 'false' ?>) {
    inputSlug.value = slugify(this.value);
  }
  shortcodePreview.textContent = '[form:' + (inputSlug.value||'slug') + ']';
});
inputSlug.addEventListener('input', function(){
  shortcodePreview.textContent = '[form:' + (this.value||'slug') + ']';
});

/* ── Settings panel binding ─────────────────────────────────────────────── */
function bindSettings(){
  document.getElementById('s_email_to').value       = settings.email_to||'';
  document.getElementById('s_email_subject').value  = settings.email_subject||'';
  document.getElementById('s_success_message').value= settings.success_message||'';
  document.getElementById('s_redirect_url').value   = settings.redirect_url||'';
  document.getElementById('s_submit_label').value   = settings.submit_label||'Envoyer';
  document.getElementById('s_use_recaptcha').checked = !!settings.use_recaptcha;
  document.getElementById('s_save_submission').checked= !!settings.save_submission;
  document.getElementById('s_is_active').checked    = !!isActive;
}
bindSettings();

/* ── Form submit: serialize ─────────────────────────────────────────────── */
document.getElementById('formBuilder').addEventListener('submit', function(e){
  // Collect settings
  var s = {
    email_to:        document.getElementById('s_email_to').value,
    email_subject:   document.getElementById('s_email_subject').value,
    success_message: document.getElementById('s_success_message').value,
    redirect_url:    document.getElementById('s_redirect_url').value,
    submit_label:    document.getElementById('s_submit_label').value||'Envoyer',
    use_recaptcha:   document.getElementById('s_use_recaptcha').checked,
    save_submission: document.getElementById('s_save_submission').checked,
  };
  document.getElementById('settingsJson').value = JSON.stringify(s);
  document.getElementById('fieldsJson').value   = JSON.stringify({steps: state.steps});

  // is_active via hidden
  var ia = document.createElement('input');
  ia.type='hidden'; ia.name='is_active'; ia.value= document.getElementById('s_is_active').checked ? '1':'0';
  this.appendChild(ia);
});

/* ── Init ───────────────────────────────────────────────────────────────── */
render();
</script>

<style>
.btn-xs{padding:2px 7px;font-size:11px;line-height:1.5;border-radius:4px;}
.field-row:hover{background:rgba(255,255,255,.08) !important;}
#stepsContainer .dropdown-item{color:#ccc;font-size:13px;}
#stepsContainer .dropdown-item:hover{background:rgba(255,255,255,.1);color:#fff;}
</style>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
