<?php
/**
 * Form Renderer — renderForm($slug) + shortcode parser processFormShortcodes($html)
 */

function getFormBySlug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['fields']   = json_decode($row['fields'],   true) ?: [];
    $row['settings'] = json_decode($row['settings'], true) ?: [];
    return $row;
}

function getFormById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['fields']   = json_decode($row['fields'],   true) ?: [];
    $row['settings'] = json_decode($row['settings'], true) ?: [];
    return $row;
}

/**
 * Render a form by slug (or numeric id).
 * Outputs the full <form> HTML ready to use.
 */
function renderForm(string $slugOrId, array $extra = []): void {
    if (!function_exists('getPDO')) require_once __DIR__ . '/db.php';
    $pdo = getPDO();

    if (is_numeric($slugOrId)) {
        $form = getFormById($pdo, (int)$slugOrId);
    } else {
        $form = getFormBySlug($pdo, $slugOrId);
    }

    if (!$form) {
        echo '<p class="muted" style="color:rgba(255,255,255,.4);">Formulaire introuvable.</p>';
        return;
    }

    $s          = $form['settings'];
    $steps      = $form['fields']['steps'] ?? [];
    $formSlug   = htmlspecialchars($form['slug']);
    $formDbId   = (int)$form['id'];
    $actionUrl  = (defined('BASE_URL') ? BASE_URL : '') . '/forms/form-process.php';

    $useRecaptcha  = !empty($s['use_recaptcha']);
    $submitLabel   = htmlspecialchars($s['submit_label'] ?? 'Envoyer');
    $isMultiStep   = count($steps) > 1;
    $successMsg    = htmlspecialchars($s['success_message'] ?? 'Merci, votre message a été envoyé.');

    // Alert from GET params
    $success = isset($_GET['success']) && $_GET['success'] === '1'
               && (isset($_GET['form']) ? $_GET['form'] === $formSlug : true);
    $notice  = $_GET['notice'] ?? '';

    $stdMessages = [
        'missing' => ['type'=>'error','title'=>'Champs manquants','text'=>'Merci de compléter les champs obligatoires.'],
        'captcha' => ['type'=>'error','title'=>'Vérification anti-spam','text'=>'La vérification a échoué. Merci de réessayer.'],
        'error'   => ['type'=>'error','title'=>'Envoi impossible','text'=>'Une erreur est survenue. Merci de réessayer.'],
        'rate'    => ['type'=>'error','title'=>'Trop de demandes','text'=>'Merci de patienter 1 minute avant de renvoyer.'],
        'blocked' => ['type'=>'error','title'=>'Accès bloqué','text'=>'Votre demande a été bloquée. Merci de nous appeler.'],
    ];

    $alert = null;
    if ($success) {
        $alert = ['type'=>'success','title'=>'Message envoyé','text'=>$successMsg];
    } elseif (isset($stdMessages[$notice])) {
        $alert = $stdMessages[$notice];
    }

    $uniqId = 'frm_' . $formSlug . '_' . $formDbId;
    $captchaKey = function_exists('getSetting') ? getSetting('captcha_site_key', '') : '';
    ?>
    <?php if ($alert): ?>
      <div class="alert alert-<?= $alert['type'] ?>" id="<?= $uniqId ?>_alert" role="status" aria-live="polite">
        <div class="alert-title"><?= $alert['title'] ?></div>
        <div class="alert-text"><?= $alert['text'] ?></div>
      </div>
    <?php endif; ?>

    <?php if ($alert): ?>
    <script>
    (function(){
      var a = document.getElementById('<?= $uniqId ?>_alert');
      if(!a) return;
      setTimeout(function(){
        a.style.transition='opacity 250ms,transform 250ms';
        a.style.opacity='0';
        a.style.transform='translateY(-6px)';
        setTimeout(function(){ a&&a.remove(); },300);
      },6000);
    })();
    </script>
    <?php endif; ?>

    <form id="<?= $uniqId ?>" method="POST" action="<?= $actionUrl ?>" novalidate class="dyn-form">
      <input type="hidden" name="form_id"   value="<?= $formDbId ?>">
      <input type="hidden" name="form_slug" value="<?= $formSlug ?>">
      <?php if ($useRecaptcha && $captchaKey): ?>
        <input type="hidden" name="recaptcha_token" id="<?= $uniqId ?>_rcToken">
      <?php endif; ?>
      <!-- Anti-spam honeypot -->
      <input type="text" name="website" value="" autocomplete="off" tabindex="-1" aria-hidden="true" style="display:none !important;visibility:hidden;position:absolute;left:-9999px;">
      <input type="hidden" name="form_time" id="<?= $uniqId ?>_ft" value="">
      <script>document.getElementById('<?= $uniqId ?>_ft').value=Math.floor(Date.now()/1000);</script>

      <?php if ($isMultiStep): ?>
        <input type="hidden" name="step_completed" id="<?= $uniqId ?>_stepCompleted" value="0">
        <!-- Stepper header -->
        <div class="stepper">
          <?php foreach ($steps as $si => $step): ?>
            <button type="button" class="stepper-item <?= $si === 0 ? 'is-active' : '' ?>"
                    data-step="<?= $si + 1 ?>"
                    <?= $si > 0 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
              <span class="stepper-num"><?= $si + 1 ?></span>
              <span class="stepper-label"><?= htmlspecialchars($step['label'] ?? 'Étape ' . ($si+1)) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php foreach ($steps as $si => $step): ?>
        <div class="form-step <?= $si === 0 ? 'is-active' : '' ?>" data-step="<?= $si + 1 ?>">
          <div style="display:grid; gap:14px;">
            <?php renderFormFields($step['fields'] ?? []); ?>
            <div class="form-actions">
              <?php if ($isMultiStep): ?>
                <?php if ($si > 0): ?>
                  <button type="button" class="btn btn-ghost dyn-prev">Retour</button>
                <?php else: ?>
                  <div class="muted small">Étape <?= $si+1 ?>/<?= count($steps) ?></div>
                <?php endif; ?>
                <?php if ($si < count($steps) - 1): ?>
                  <button type="button" class="btn btn-primary dyn-next">Continuer</button>
                <?php else: ?>
                  <button type="submit" class="btn btn-primary dyn-submit"><?= $submitLabel ?></button>
                <?php endif; ?>
              <?php else: ?>
                <button type="submit" class="btn btn-primary dyn-submit"><?= $submitLabel ?></button>
              <?php endif; ?>
            </div>
            <?php if (!$isMultiStep || $si === count($steps) - 1): ?>
              <div class="muted small">Réponse rapide. Devis gratuit. Chantier propre.</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </form>

    <?php if ($useRecaptcha && $captchaKey): ?>
    <?php static $rcScriptPrinted = false; if (!$rcScriptPrinted): $rcScriptPrinted = true; ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($captchaKey) ?>" defer></script>
    <?php endif; ?>
    <script>
    (function(){
      var form = document.getElementById('<?= $uniqId ?>');
      var tf   = document.getElementById('<?= $uniqId ?>_rcToken');
      if (!form || !tf) return;
      function refreshRC(cb){
        if(typeof grecaptcha === 'undefined') { if(cb) cb(); return; }
        grecaptcha.ready(function(){
          grecaptcha.execute('<?= addslashes($captchaKey) ?>', {action:'contact'}).then(function(t){ tf.value=t; if(cb) cb(); });
        });
      }
      refreshRC();
      form.addEventListener('submit', function(e){
        if(!tf.value){ e.preventDefault(); refreshRC(function(){ form.submit(); }); }
      });
    })();
    </script>
    <?php endif; ?>

    <?php if ($isMultiStep): ?>
    <script>
    (function(){
      var form  = document.getElementById('<?= $uniqId ?>');
      if (!form) return;
      var steps  = form.querySelectorAll('.form-step');
      var dots   = form.querySelectorAll('.stepper-item');
      var curStep = 0;

      function showStep(n){
        steps.forEach(function(s,i){ s.classList.toggle('is-active', i===n); });
        dots.forEach(function(d,i){
          d.classList.toggle('is-active', i===n);
          d.classList.toggle('is-done', i<n);
          d.setAttribute('tabindex', i===n ? '0' : '-1');
          d.setAttribute('aria-disabled', i===n ? 'false' : 'true');
        });
        curStep = n;
        var sc = form.querySelector('#<?= $uniqId ?>_stepCompleted');
        if(sc) sc.value = n;
      }

      function validateStep(n){
        var inputs = steps[n].querySelectorAll('[required]');
        var ok = true;
        inputs.forEach(function(inp){
          if(!inp.value.trim()){ inp.classList.add('is-error'); ok=false; }
          else inp.classList.remove('is-error');
          inp.addEventListener('input', function(){ inp.classList.remove('is-error'); }, {once:true});
        });
        return ok;
      }

      form.addEventListener('click', function(e){
        if(e.target.closest('.dyn-next')){
          if(validateStep(curStep)) showStep(Math.min(curStep+1, steps.length-1));
        }
        if(e.target.closest('.dyn-prev')){ showStep(Math.max(curStep-1, 0)); }
      });
    })();
    </script>
    <?php endif; ?>
<?php
}

/**
 * Render a list of field definitions as HTML.
 */
function renderFormFields(array $fields): void {
    // Group pairs for two-col layout (only for adjacent short fields)
    $i = 0;
    while ($i < count($fields)) {
        $f = $fields[$i];
        $next = $fields[$i+1] ?? null;

        // Two-col: pair two short fields (text/email/tel/number/select) side by side
        $shortTypes = ['text','email','tel','number','select'];
        $isShort  = in_array($f['type'], $shortTypes);
        $nextShort = $next && in_array($next['type'], $shortTypes);
        if ($isShort && $nextShort) {
            echo '<div class="two-cols">';
            renderSingleField($f);
            renderSingleField($next);
            echo '</div>';
            $i += 2;
        } else {
            renderSingleField($f);
            $i++;
        }
    }
}

function renderSingleField(array $f): void {
    $name  = htmlspecialchars($f['name'] ?? '');
    $label = htmlspecialchars($f['label'] ?? '');
    $req   = !empty($f['required']);
    $ph    = htmlspecialchars($f['placeholder'] ?? '');
    $type  = $f['type'] ?? 'text';

    echo '<div>';
    if ($label) echo '<label>' . $label . ($req ? ' *' : '') . '</label>';

    switch ($type) {
        case 'textarea':
            $rows = (int)($f['rows'] ?? 5);
            echo '<textarea name="' . $name . '" rows="' . $rows . '" class="input"' . ($req ? ' required' : '') . ($ph ? ' placeholder="'.$ph.'"' : '') . '></textarea>';
            break;
        case 'select':
            echo '<select name="' . $name . '" class="input"' . ($req ? ' required' : '') . '>';
            echo '<option value="">Choisir</option>';
            foreach ($f['options'] ?? [] as $opt) {
                echo '<option value="' . htmlspecialchars($opt) . '">' . htmlspecialchars($opt) . '</option>';
            }
            echo '</select>';
            break;
        case 'checkbox':
            echo '<label class="checkbox-label"><input type="checkbox" name="' . $name . '" value="1"' . ($req ? ' required' : '') . '> ' . htmlspecialchars($f['checkbox_label'] ?? $label) . '</label>';
            break;
        case 'radio':
            foreach ($f['options'] ?? [] as $opt) {
                $ov = htmlspecialchars($opt);
                echo '<label class="radio-label"><input type="radio" name="' . $name . '" value="'.$ov.'"' . ($req ? ' required' : '') . '> '.$ov.'</label>';
            }
            break;
        default:
            echo '<input type="' . htmlspecialchars($type) . '" name="' . $name . '" class="input"' . ($req ? ' required' : '') . ($ph ? ' placeholder="'.$ph.'"' : '') . '>';
    }
    echo '</div>';
}

/**
 * Parse shortcodes [form:slug] inside HTML content.
 * Used by CMS page renderer.
 */
function processFormShortcodes(string $html): string {
    return preg_replace_callback('/\[form:([a-z0-9_\-]+)\]/i', function($m) {
        ob_start();
        renderForm($m[1]);
        return ob_get_clean();
    }, $html);
}
