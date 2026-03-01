<?php
require_once __DIR__ . '/partials/header.php';
requireAdmin(); // all authenticated users

require_once __DIR__ . '/../classes/TOTP.php';

$pdo  = getPDO();
$me   = getCurrentAdmin();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Reload fresh from DB
$st = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$st->execute([$me['id']]);
$user = $st->fetch(PDO::FETCH_ASSOC);

// 2FA setup state from session
$twofa_pending = $_SESSION['2fa_pending_secret'] ?? null;

// 2FA extra data (recovery codes + trusted devices)
$recovery_codes_new  = $_SESSION['2fa_recovery_codes'] ?? null;
if ($recovery_codes_new) unset($_SESSION['2fa_recovery_codes']);
$recovery_codes_count = 0;
$trusted_devices      = [];
$current_device_hash  = '';
if ($user['totp_enabled']) {
    $st2 = $pdo->prepare("SELECT COUNT(*) FROM admin_recovery_codes WHERE admin_id = ? AND used_at IS NULL");
    $st2->execute([$me['id']]);
    $recovery_codes_count = (int)$st2->fetchColumn();
    $st3 = $pdo->prepare("SELECT id, token_hash, device_label, created_at, expires_at FROM admin_trusted_devices WHERE admin_id = ? AND expires_at > NOW() ORDER BY created_at DESC");
    $st3->execute([$me['id']]);
    $trusted_devices = $st3->fetchAll(PDO::FETCH_ASSOC);
    $cookieVal = $_COOKIE['jp_trusted_device'] ?? '';
    if ($cookieVal && str_contains($cookieVal, ':')) {
        [, $tok] = explode(':', $cookieVal, 2);
        $current_device_hash = hash('sha256', $tok);
    }
}
?>

<div class="d-flex align-items-center mb-3" style="gap:10px;">
  <h4 class="m-0"><i class="fas fa-user-cog mr-2"></i>Mon profil</h4>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<div class="row">
  <!-- Left: profile form -->
  <div class="col-lg-7">
    <div class="card card-outline card-success">
      <div class="card-header"><h5 class="m-0">Informations générales</h5></div>
      <div class="card-body">
        <form method="POST" action="actions/profile-save.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <!-- Avatar preview + upload -->
          <div class="form-group text-center">
            <div class="mb-2">
              <div id="avatarPreview" style="width:80px;height:80px;border-radius:50%;background:<?= stringToColor($user['username']) ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:28px;margin:auto;overflow:hidden;">
                <?php if ($user['avatar']): ?>
                  <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars(basename($user['avatar'])) ?>" style="width:100%;height:100%;object-fit:cover;" alt="Avatar">
                <?php else: ?>
                  <?= strtoupper(mb_substr($user['display_name'] ?: $user['username'], 0, 1)) ?>
                <?php endif; ?>
              </div>
            </div>
            <label class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-camera mr-1"></i>Changer l'avatar
              <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none">
            </label>
            <?php if ($user['avatar']): ?>
              <div class="mt-1">
                <small><a href="actions/profile-save.php?remove_avatar=1&csrf_token=<?= $csrf ?>" class="text-danger" onclick="return confirm('Supprimer l\'avatar ?')">Supprimer l'avatar</a></small>
              </div>
            <?php endif; ?>
          </div>

          <!-- Display name -->
          <div class="form-group">
            <label>Nom affiché</label>
            <input type="text" name="display_name" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars($user['display_name'] ?? '') ?>"
                   placeholder="Prénom Nom">
          </div>

          <!-- Email -->
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
          </div>

          <!-- Username (read-only) -->
          <div class="form-group">
            <label>Nom d'utilisateur</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
            <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié ici.</small>
          </div>

          <!-- Role (read-only) -->
          <div class="form-group">
            <label>Rôle</label>
            <div>
              <span class="badge badge-<?= ROLES_COLORS[$user['role']] ?? 'secondary' ?> badge-lg p-2">
                <?= ROLES_LABELS[$user['role']] ?? $user['role'] ?>
              </span>
            </div>
          </div>

          <hr>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i>Enregistrer
          </button>
        </form>
      </div>
    </div>



    <!-- ── Double Authentification (2FA) ─────────────────────────────── -->
    <div class="card card-outline card-warning mt-3">
      <div class="card-header d-flex align-items-center" style="gap:10px;">
        <h5 class="m-0"><i class="fas fa-shield-alt mr-2"></i>Double authentification (2FA)</h5>
        <?php if ($user['totp_enabled']): ?>
          <span class="badge badge-success ml-auto"><i class="fas fa-check mr-1"></i>Activée</span>
        <?php else: ?>
          <span class="badge badge-secondary ml-auto">Désactivée</span>
        <?php endif; ?>
      </div>
      <div class="card-body">

        <?php if ($twofa_pending): ?>
        <!-- ── STEP 2 : Scan QR + confirm code ──────────────────────── -->
        <p class="text-muted mb-3" style="font-size:.9rem;">
          <strong>Étape 1</strong> — Scannez ce QR code avec <em>Google Authenticator</em>, <em>Authy</em> ou une autre application TOTP.<br>
          <strong>Étape 2</strong> — Saisissez le code à 6 chiffres affiché pour confirmer l'activation.
        </p>
        <div class="text-center mb-3">
          <img src="<?= TOTP::getQRUrl('Joker Peintre Admin', htmlspecialchars($user['username']), $twofa_pending) ?>"
               alt="QR Code 2FA" style="border:6px solid #fff;border-radius:6px;">
          <br>
          <small class="text-muted mt-1 d-block">Clé manuelle :&nbsp;
            <code style="letter-spacing:2px;font-size:.95rem;"><?= chunk_split(htmlspecialchars($twofa_pending), 4, ' ') ?></code>
          </small>
        </div>
        <form method="POST" action="actions/profile-save.php">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="2fa_confirm">
          <div class="form-group">
            <label>Code de vérification</label>
            <div class="input-group" style="max-width:220px;">
              <input type="text" name="totp_code" class="form-control text-center" maxlength="6"
                     pattern="\d{6}" placeholder="000000" autocomplete="one-time-code"
                     autofocus style="font-size:1.3rem;letter-spacing:6px;">
              <div class="input-group-append">
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-check mr-1"></i>Confirmer
                </button>
              </div>
            </div>
            <small class="text-muted">Entrez le code actuellement affiché dans votre application.</small>
          </div>
        </form>
        <form method="POST" action="actions/profile-save.php" class="mt-1">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="2fa_cancel">
          <button type="submit" class="btn btn-link btn-sm text-muted p-0">
            <i class="fas fa-times mr-1"></i>Annuler la configuration
          </button>
        </form>

        <?php elseif (!$user['totp_enabled']): ?>
        <!-- ── NOT ENABLED ──────────────────────────────────────────── -->
        <p class="text-muted" style="font-size:.9rem;">La double authentification ajoute une couche de sécurité supplémentaire : à chaque connexion, un code à 6 chiffres vous sera demandé en plus de votre mot de passe.</p>
        <form method="POST" action="actions/profile-save.php">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="2fa_start">
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-shield-alt mr-2"></i>Configurer la double authentification
          </button>
        </form>

        <?php else: ?>
        <!-- ── ENABLED ──────────────────────────────────────────────── -->
        <div class="d-flex align-items-center mb-3" style="gap:12px;">
          <i class="fas fa-check-circle text-success" style="font-size:2rem;"></i>
          <div>
            <strong>La 2FA est activée sur votre compte.</strong><br>
            <small class="text-muted">Chaque connexion nécessite votre application d'authentification.</small>
          </div>
        </div>

        <!-- Codes de récupération -->
        <hr class="border-secondary">
        <h6><i class="fas fa-key mr-2 text-warning"></i>Codes de récupération</h6>

        <?php if ($recovery_codes_new): ?>
        <div class="alert alert-warning">
          <strong><i class="fas fa-exclamation-triangle mr-1"></i>Sauvegardez ces codes maintenant !</strong><br>
          <small>Ils ne seront affichés qu'une seule fois. Conservez-les en lieu sûr (gestionnaire de mots de passe, coffre-fort papier, etc.)</small>
        </div>
        <div class="bg-dark border border-warning rounded p-3 mb-2" id="recoveryCodes">
          <div class="row">
            <?php foreach ($recovery_codes_new as $code): ?>
            <div class="col-6 mb-1">
              <code style="font-size:1rem;letter-spacing:2px;"><?= htmlspecialchars($code) ?></code>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3 d-flex" style="gap:8px;">
          <button type="button" class="btn btn-warning btn-sm" onclick="copyRecoveryCodes()">
            <i class="fas fa-copy mr-1"></i>Copier tous les codes
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="printRecoveryCodes()">
            <i class="fas fa-print mr-1"></i>Imprimer
          </button>
        </div>
        <?php else: ?>
        <p class="text-muted mb-2" style="font-size:.9rem;">
          <?php if ($recovery_codes_count > 0): ?>
            <i class="fas fa-check-circle text-success mr-1"></i>
            <strong><?= $recovery_codes_count ?></strong> code<?= $recovery_codes_count > 1 ? 's' : '' ?> utilisable<?= $recovery_codes_count > 1 ? 's' : '' ?> restant<?= $recovery_codes_count > 1 ? 's' : '' ?>.
          <?php else: ?>
            <i class="fas fa-exclamation-triangle text-danger mr-1"></i>
            <strong>Aucun code de récupération disponible !</strong> Générez-en de nouveaux.
          <?php endif; ?>
        </p>
        <button class="btn btn-outline-warning btn-sm" data-toggle="collapse" data-target="#regenCodesForm">
          <i class="fas fa-sync mr-1"></i>Générer de nouveaux codes
        </button>
        <div class="collapse mt-2" id="regenCodesForm">
          <div class="card card-body bg-dark border-warning" style="max-width:380px;">
            <p class="text-warning mb-2" style="font-size:.82rem;"><i class="fas fa-exclamation-triangle mr-1"></i>Les codes actuels seront invalidés.</p>
            <form method="POST" action="actions/profile-save.php">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="action" value="2fa_regen_codes">
              <div class="form-group mb-2">
                <input type="password" name="current_password" class="form-control form-control-sm"
                       placeholder="Confirmez votre mot de passe" required>
              </div>
              <button type="submit" class="btn btn-warning btn-sm">
                <i class="fas fa-sync mr-1"></i>Générer
              </button>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <!-- Appareils de confiance -->
        <hr class="border-secondary">
        <h6><i class="fas fa-laptop mr-2 text-info"></i>Appareils de confiance</h6>
        <?php if (empty($trusted_devices)): ?>
          <p class="text-muted" style="font-size:.9rem;">Aucun appareil enregistré. Lors de la prochaine connexion 2FA, cochez « Se souvenir 30 jours ».</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-dark table-sm mb-2">
            <thead><tr><th>Appareil</th><th>Enregistré</th><th>Expire</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($trusted_devices as $dev): ?>
            <?php
                  $isCurrent = ($current_device_hash !== '' && $current_device_hash === $dev['token_hash']);
            ?>
            <tr>
              <td>
                <small><?= htmlspecialchars(mb_strimwidth($dev['device_label'] ?? 'Inconnu', 0, 50, '…')) ?></small>
                <?php if ($isCurrent): ?><span class="badge badge-info ml-1">Actuel</span><?php endif; ?>
              </td>
              <td><small><?= date('d/m/Y', strtotime($dev['created_at'])) ?></small></td>
              <td><small><?= date('d/m/Y', strtotime($dev['expires_at'])) ?></small></td>
              <td>
                <form method="POST" action="actions/profile-save.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="2fa_revoke_device">
                  <input type="hidden" name="device_id" value="<?= $dev['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-xs p-1" title="Révoquer"
                          onclick="return confirm('Révoquer cet appareil ?')">
                    <i class="fas fa-times"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <!-- Désactiver 2FA -->
        <hr class="border-secondary">
        <button class="btn btn-outline-danger btn-sm" data-toggle="collapse" data-target="#disable2faForm">
          <i class="fas fa-times mr-1"></i>Désactiver la 2FA
        </button>
        <div class="collapse mt-3" id="disable2faForm">
          <div class="card card-body bg-dark border-danger" style="max-width:380px;">
            <p class="text-warning mb-2" style="font-size:.85rem;"><i class="fas fa-exclamation-triangle mr-1"></i>Confirmez avec votre mot de passe et un code 2FA valide.</p>
            <form method="POST" action="actions/profile-save.php">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="action" value="2fa_disable">
              <div class="form-group mb-2">
                <input type="password" name="current_password" class="form-control form-control-sm"
                       placeholder="Mot de passe actuel" required>
              </div>
              <div class="form-group mb-2">
                <input type="text" name="totp_code" class="form-control form-control-sm text-center"
                       maxlength="6" pattern="\d{6}" placeholder="Code 2FA (6 chiffres)"
                       autocomplete="one-time-code" style="letter-spacing:4px;">
              </div>
              <button type="submit" class="btn btn-danger btn-sm">
                <i class="fas fa-shield-alt mr-1"></i>Désactiver la 2FA
              </button>
            </form>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- Right: summary -->
  <div class="col-lg-5">
    <div class="card card-outline card-primary">
      <div class="card-header"><h5 class="m-0">Informations du compte</h5></div>
      <div class="card-body">
        <table class="table table-dark table-sm mb-0">
          <tr><th style="width:140px;">Identifiant</th><td><code>@<?= htmlspecialchars($user['username']) ?></code></td></tr>
          <tr><th>Rôle</th><td><span class="badge badge-<?= ROLES_COLORS[$user['role']] ?>"><?= ROLES_LABELS[$user['role']] ?></span></td></tr>
          <tr><th>Statut</th><td><?= $user['is_active'] ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-danger">Inactif</span>' ?></td></tr>
          <tr><th>Dernière connexion</th><td><small><?= $user['last_login'] ? date('d/m/Y à H:i', strtotime($user['last_login'])) : 'Jamais' ?></small></td></tr>
          <tr><th>Membre depuis</th><td><small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small></td></tr>
        </table>
      </div>
    </div>
    <!-- Password change -->
    <div class="card card-outline card-danger mt-3">
      <div class="card-header"><h5 class="m-0"><i class="fas fa-lock mr-2"></i>Changer le mot de passe</h5></div>
      <div class="card-body">
        <form method="POST" action="actions/profile-save.php">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="change_password" value="1">

          <div class="form-group">
            <label>Mot de passe actuel <span class="text-danger">*</span></label>
            <input type="password" name="current_password" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Nouveau mot de passe <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="new_password" id="newPwd" class="form-control" required minlength="8"
                     placeholder="Minimum 8 caractères">
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" id="toggleNewPwd">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Confirmer le nouveau mot de passe</label>
            <input type="password" name="confirm_password" id="confirmPwd" class="form-control" placeholder="Répéter">
            <small class="text-danger d-none" id="pwdMismatch">Les mots de passe ne correspondent pas.</small>
          </div>

          <button type="submit" class="btn btn-warning">
            <i class="fas fa-key mr-1"></i>Changer le mot de passe
          </button>
        </form>
      </div>
    </div>
    
    <div class="card card-outline card-default mt-3">
      <div class="card-header"><h5 class="m-0">Mes permissions</h5></div>
      <div class="card-body">
        <?php
        $permLabels = [
          'dashboard'=>'Dashboard','contacts'=>'Contacts','realisations'=>'Réalisations',
          'galleries'=>'Galeries','cms'=>'CMS','media'=>'Médias','menu'=>'Menu',
          'themes'=>'Thèmes','forms'=>'Formulaires','settings'=>'Réglages','users'=>'Utilisateurs',
        ];
        $allPerms = array_keys($permLabels);
        foreach ($allPerms as $p): ?>
          <span class="badge badge-<?= can($p)?'success':'secondary' ?> mb-1" style="font-size:12px;">
            <i class="fas fa-<?= can($p)?'check':'times' ?> mr-1"></i><?= $permLabels[$p] ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?php
function stringToColor(string $str): string {
    $colors = ['#c0392b','#2980b9','#27ae60','#8e44ad','#d35400','#16a085','#2c3e50','#e67e22'];
    return $colors[abs(crc32($str)) % count($colors)];
}
?>

<script>
// Avatar preview
document.getElementById('avatarInput').addEventListener('change', function(){
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const preview = document.getElementById('avatarPreview');
    preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
  };
  reader.readAsDataURL(file);
});

// Toggle password visibility
document.getElementById('toggleNewPwd').addEventListener('click', function(){
  const f = document.getElementById('newPwd');
  f.type = f.type === 'password' ? 'text' : 'password';
  this.querySelector('i').classList.toggle('fa-eye');
  this.querySelector('i').classList.toggle('fa-eye-slash');
});

// Password mismatch check
const np = document.getElementById('newPwd');
const cp = document.getElementById('confirmPwd');
const mm = document.getElementById('pwdMismatch');
[np, cp].forEach(el => el.addEventListener('input', () => {
  if (cp.value && np.value !== cp.value) mm.classList.remove('d-none');
  else mm.classList.add('d-none');
}));

// Recovery codes helpers
function copyRecoveryCodes() {
  const el = document.getElementById('recoveryCodes');
  if (!el) return;
  const codes = [...el.querySelectorAll('code')].map(c => c.textContent.trim()).join('\n');
  navigator.clipboard.writeText(codes).then(() => {
    const btn = event.target.closest('button');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copié !';
    btn.classList.replace('btn-warning','btn-success');
    setTimeout(() => { btn.innerHTML = orig; btn.classList.replace('btn-success','btn-warning'); }, 2000);
  });
}
function printRecoveryCodes() {
  const el = document.getElementById('recoveryCodes');
  if (!el) return;
  const codes = [...el.querySelectorAll('code')].map(c => c.textContent.trim()).join('\n');
  const w = window.open('', '_blank', 'width=400,height=500');
  w.document.write('<html><body style="font-family:monospace;padding:20px"><h3>Codes de récupération 2FA — Joker Peintre Admin</h3><pre style="font-size:1.2rem;line-height:2rem">' + codes + '</pre><p style="font-size:12px;color:#666">Conservez ces codes en lieu sûr.</p></body></html>');
  w.document.close(); w.print();
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
