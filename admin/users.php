<?php
require_once __DIR__ . '/partials/header.php';
requirePermission('users');

$pdo   = getPDO();
$users = $pdo->query("SELECT id, username, email, display_name, avatar, role, is_active, last_login, created_at FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0"><i class="fas fa-users mr-2"></i>Gestion des utilisateurs</h4>
  <a href="user-edit.php" class="btn btn-primary btn-sm"><i class="fas fa-user-plus mr-1"></i>Nouvel utilisateur</a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<!-- Roles legend -->
<div class="card mb-3">
  <div class="card-body py-2">
    <div class="d-flex align-items-center flex-wrap" style="gap:16px;">
      <small class="text-muted mr-2 font-weight-bold">Rôles :</small>
      <?php foreach (ROLES_LABELS as $r => $lbl): ?>
        <span class="badge badge-<?= ROLES_COLORS[$r] ?> mr-1"><?= $lbl ?></span>
        <small class="text-muted" style="font-size:11px;">
          <?php
          $perms = ROLE_PERMISSIONS[$r];
          $permLabels = [
            'dashboard'=>'Dashboard','contacts'=>'Contacts','realisations'=>'Réalisations',
            'galleries'=>'Galeries','cms'=>'CMS','media'=>'Médias','menu'=>'Menu',
            'themes'=>'Thèmes','forms'=>'Formulaires','settings'=>'Réglages','users'=>'Utilisateurs',
          ];
          echo implode(', ', array_map(fn($p)=>$permLabels[$p]??$p, $perms));
          ?>
        </small>
        <?php if ($r !== array_key_last(ROLES_LABELS)): ?><span class="text-muted">·</span><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-dark table-hover mb-0">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Utilisateur</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Dernière connexion</th>
          <th>Créé le</th>
          <th style="width:50px;">Statut</th>
          <th style="width:130px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $me = getCurrentAdmin();
        foreach ($users as $u):
          $isMe = ($u['id'] == $me['id']);
          $displayName = $u['display_name'] ?: $u['username'];
        ?>
        <tr class="<?= !$u['is_active'] ? 'text-muted' : '' ?>">
          <td><small><?= $u['id'] ?></small></td>
          <td>
            <div class="d-flex align-items-center" style="gap:10px;">
              <div class="user-avatar-sm" style="width:32px;height:32px;border-radius:50%;background:<?= stringToColor($u['username']) ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;overflow:hidden;">
                <?php if (!empty($u['avatar'])): ?>
                  <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars(basename($u['avatar'])) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                <?php else: ?>
                  <?= strtoupper(mb_substr($displayName, 0, 1)) ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="font-weight-bold" style="font-size:14px;"><?= htmlspecialchars($displayName) ?></div>
                <div class="small text-muted">@<?= htmlspecialchars($u['username']) ?><?= $isMe ? ' <span class="badge badge-success" style="font-size:10px;">Vous</span>' : '' ?></div>
              </div>
            </div>
          </td>
          <td><small><?= htmlspecialchars($u['email'] ?: '—') ?></small></td>
          <td>
            <span class="badge badge-<?= ROLES_COLORS[$u['role']] ?? 'secondary' ?>">
              <?= ROLES_LABELS[$u['role']] ?? $u['role'] ?>
            </span>
          </td>
          <td><small class="text-muted"><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais' ?></small></td>
          <td><small class="text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></small></td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="badge badge-success">Actif</span>
            <?php else: ?>
              <span class="badge badge-danger">Inactif</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex" style="gap:4px;">
              <a href="user-edit.php?id=<?= $u['id'] ?>" class="btn btn-xs btn-outline-primary" title="Modifier">
                <i class="fas fa-edit"></i>
              </a>
              <?php if (!$isMe): ?>
                <form method="POST" action="actions/user-toggle.php" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="user_id"    value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-xs btn-outline-<?= $u['is_active']?'warning':'success' ?>"
                          title="<?= $u['is_active']?'Désactiver':'Activer' ?>">
                    <i class="fas fa-<?= $u['is_active']?'ban':'check' ?>"></i>
                  </button>
                </form>
                <form method="POST" action="actions/user-delete.php" class="d-inline"
                      onsubmit="return confirm('Supprimer l\'utilisateur <?= htmlspecialchars(addslashes($displayName)) ?> ?')">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="user_id"    value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-xs btn-outline-danger" title="Supprimer">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              <?php else: ?>
                <a href="profile.php" class="btn btn-xs btn-outline-secondary" title="Mon profil">
                  <i class="fas fa-user-cog"></i>
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
function stringToColor(string $str): string {
    $colors = ['#c0392b','#2980b9','#27ae60','#8e44ad','#d35400','#16a085','#2c3e50','#e67e22'];
    return $colors[abs(crc32($str)) % count($colors)];
}
?>

<style>
.btn-xs{padding:2px 7px;font-size:11px;line-height:1.5;border-radius:4px;}
</style>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
