<?php
require_once __DIR__ . '/partials/header.php';
requirePermission('users');

$pdo = getPDO();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($id > 0) {
    $st = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $st->execute([$id]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Utilisateur introuvable.'];
        header('Location: users.php'); exit;
    }
}

$title = $user ? 'Modifier ' . htmlspecialchars($user['display_name'] ?: $user['username']) : 'Nouvel utilisateur';
$me = getCurrentAdmin();
?>

<div class="d-flex align-items-center mb-3" style="gap:10px;">
  <a href="users.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h4 class="m-0"><?= $title ?></h4>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><h5 class="m-0">Informations</h5></div>
      <div class="card-body">
        <form method="POST" action="actions/user-save.php">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <?php if ($user): ?>
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
          <?php endif; ?>

          <!-- Identifiant -->
          <div class="form-group">
            <label>Nom d'utilisateur <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required
                   value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                   pattern="[a-zA-Z0-9_-]+" title="Lettres, chiffres, - et _ uniquement">
            <small class="text-muted">Utilisé pour la connexion. Lettres, chiffres, _ et - uniquement.</small>
          </div>

          <!-- Nom affiché -->
          <div class="form-group">
            <label>Nom affiché</label>
            <input type="text" name="display_name" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars($user['display_name'] ?? '') ?>"
                   placeholder="Prénom Nom">
          </div>

          <!-- Email -->
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" maxlength="180"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
          </div>

          <!-- Rôle -->
          <div class="form-group">
            <label>Rôle <span class="text-danger">*</span></label>
            <?php
            $myRoleIndex = array_search(getAdminRole(), ROLES);
            ?>
            <select name="role" class="form-control" id="roleSelect">
              <?php foreach (ROLES as $r):
                $rIndex = array_search($r, ROLES);
                // Can only assign roles <= own role (super_admin can assign any)
                $disabled = ($rIndex < $myRoleIndex) ? 'disabled' : '';
                $selected = ($user['role'] ?? 'editor') === $r ? 'selected' : '';
              ?>
                <option value="<?= $r ?>" <?= $selected ?> <?= $disabled ?>>
                  <?= ROLES_LABELS[$r] ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted" id="roleDesc"></small>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label><?= $user ? 'Nouveau mot de passe' : 'Mot de passe' ?> <?= !$user ? '<span class="text-danger">*</span>' : '' ?></label>
            <div class="input-group">
              <input type="password" name="password" id="pwd" class="form-control"
                     <?= !$user ? 'required' : '' ?> minlength="8"
                     placeholder="<?= $user ? 'Laisser vide pour ne pas changer' : 'Minimum 8 caractères' ?>">
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" id="togglePwd">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="password_confirm" id="pwdConfirm" class="form-control"
                   placeholder="Répéter le mot de passe">
            <small class="text-danger d-none" id="pwdMismatch">Les mots de passe ne correspondent pas.</small>
          </div>

          <!-- Active -->
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="isActive"
                     <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
              <label class="custom-control-label" for="isActive">Compte actif</label>
            </div>
          </div>

          <hr>
          <div class="d-flex justify-content-between">
            <a href="users.php" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save mr-1"></i><?= $user ? 'Enregistrer' : 'Créer l\'utilisateur' ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Info sidebar -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><h5 class="m-0">Permissions par rôle</h5></div>
      <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
          <thead><tr><th>Rôle</th><th>Accès</th></tr></thead>
          <tbody>
          <?php
          $permLabels = [
            'dashboard'=>'Dashboard','contacts'=>'Contacts','realisations'=>'Réalisations',
            'galleries'=>'Galeries','cms'=>'CMS','media'=>'Médias','menu'=>'Menu',
            'themes'=>'Thèmes','forms'=>'Formulaires','settings'=>'Réglages','users'=>'Utilisateurs',
          ];
          foreach (ROLES as $r): ?>
            <tr>
              <td><span class="badge badge-<?= ROLES_COLORS[$r] ?>"><?= ROLES_LABELS[$r] ?></span></td>
              <td style="font-size:11px;"><?= implode(', ', array_map(fn($p)=>$permLabels[$p]??$p, ROLE_PERMISSIONS[$r])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if ($user && $user['last_login']): ?>
    <div class="card mt-3">
      <div class="card-body py-2">
        <small class="text-muted">Dernière connexion : <strong><?= date('d/m/Y à H:i', strtotime($user['last_login'])) ?></strong></small><br>
        <small class="text-muted">Compte créé le : <strong><?= date('d/m/Y', strtotime($user['created_at'])) ?></strong></small>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Toggle pwd visibility
document.getElementById('togglePwd').addEventListener('click', function(){
  const f = document.getElementById('pwd');
  f.type = f.type === 'password' ? 'text' : 'password';
  this.querySelector('i').classList.toggle('fa-eye');
  this.querySelector('i').classList.toggle('fa-eye-slash');
});

// Password match check
const pwdField = document.getElementById('pwd');
const confirmField = document.getElementById('pwdConfirm');
const mismatch = document.getElementById('pwdMismatch');
function checkMatch(){
  if (confirmField.value && pwdField.value !== confirmField.value) {
    mismatch.classList.remove('d-none');
  } else {
    mismatch.classList.add('d-none');
  }
}
pwdField.addEventListener('input', checkMatch);
confirmField.addEventListener('input', checkMatch);
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
