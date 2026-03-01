<?php
/**
 * Layout Admin — AdminLTE 3
 * Variables disponibles :
 *   $content        — HTML de la vue courante
 *   $currentAdmin   — tableau de l'admin connecté
 *   $pageTitle      — titre de la page (optionnel)
 *   $csrf           — token CSRF
 */
use App\Core\Auth;

$me            = $currentAdmin ?? Auth::user();
$meDisplayName = $me['display_name'] ?: $me['username'];
$meInitial     = mb_strtoupper(mb_substr($meDisplayName, 0, 1));
$base          = defined('BASE_URL') ? BASE_URL : '';
$currentUri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

function _avatarBgColor(string $str): string {
    $colors = ['#c0392b','#2980b9','#27ae60','#8e44ad','#d35400','#16a085','#2c3e50','#e67e22'];
    return $colors[abs(crc32($str)) % count($colors)];
}

// Compteurs pour les badges
$pdo       = \App\Core\Database::getInstance();
$newCount  = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status='new'")->fetchColumn();
$formUnread= (int)$pdo->query("SELECT COUNT(*) FROM form_submissions WHERE is_read=0")->fetchColumn();

function isActive(string $currentUri, string ...$paths): string {
    foreach ($paths as $p) {
        if (str_starts_with($currentUri, $p)) return 'active';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — JokerPeintre</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
  <style>
    .brand-link,.main-sidebar{background:#0b0c10;}
    .content-wrapper{background:#0f1116;}
    .card{border-radius:14px;}
    .small-box{border-radius:14px;}
    .badge{font-size:12px;}
    .table td{vertical-align:middle;}
  </style>
</head>
<body class="hold-transition sidebar-mini dark-mode">
<div class="wrapper">

  <!-- Barre de navigation top -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?= $base ?>/" class="nav-link" target="_blank">Voir le site</a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
           id="userDropdown" data-toggle="dropdown" style="gap:8px;">
          <div style="width:30px;height:30px;border-radius:50%;background:<?= _avatarBgColor($me['username']) ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;overflow:hidden;">
            <?php if (!empty($me['avatar'])): ?>
              <img src="<?= $base ?>/uploads/avatars/<?= htmlspecialchars(basename($me['avatar'])) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
            <?php else: ?>
              <?= $meInitial ?>
            <?php endif; ?>
          </div>
          <span class="d-none d-sm-inline" style="font-size:13px;"><?= htmlspecialchars($meDisplayName) ?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
          <a class="dropdown-item" href="<?= $base ?>/admin/profile"><i class="fas fa-user-cog mr-2"></i>Mon profil</a>
          <?php if (Auth::can('users')): ?>
          <a class="dropdown-item" href="<?= $base ?>/admin/users"><i class="fas fa-users mr-2"></i>Utilisateurs</a>
          <?php endif; ?>
          <div class="dropdown-divider"></div>
          <span class="dropdown-item text-muted" style="font-size:11px;">
            <span class="badge badge-<?= Auth::ROLES_COLORS[$me['role']] ?? 'secondary' ?>"><?= Auth::ROLES_LABELS[$me['role']] ?? $me['role'] ?></span>
          </span>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger" href="<?= $base ?>/admin/logout"><i class="fas fa-right-from-bracket mr-2"></i>Déconnexion</a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar elevation-4">
    <a href="<?= $base ?>/admin/dashboard" class="brand-link">
      <span class="brand-text font-weight-bold">JokerPeintre Admin</span>
    </a>
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

          <li class="nav-item">
            <a href="<?= $base ?>/admin/dashboard" class="nav-link <?= isActive($currentUri, $base.'/admin/dashboard') ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
            </a>
          </li>

          <?php if (Auth::can('crm')): ?>
          <li class="nav-header" style="color:rgba(255,255,255,.4);font-size:11px;padding:10px 8px 4px;">CRM</li>
          <?php if (Auth::can('contacts')): ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/contacts" class="nav-link <?= isActive($currentUri, $base.'/admin/contacts') ?>">
              <i class="nav-icon fas fa-envelope"></i>
              <p>Contacts <?php if ($newCount > 0): ?><span class="badge badge-danger right"><?= $newCount ?></span><?php endif; ?></p>
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/crm/clients" class="nav-link <?= isActive($currentUri, $base.'/admin/crm/clients') ?>">
              <i class="nav-icon fas fa-users"></i><p>Clients</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/crm/devis" class="nav-link <?= isActive($currentUri, $base.'/admin/crm/devis') ?>">
              <i class="nav-icon fas fa-file-invoice"></i><p>Devis &amp; Facture</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('cms') || Auth::can('media') || Auth::can('forms') || Auth::can('galleries') || Auth::can('realisations') || Auth::can('menu') || Auth::can('themes') || Auth::can('settings')): ?>
          <li class="nav-header" style="color:rgba(255,255,255,.4);font-size:11px;padding:10px 8px 4px;">CMS</li>
          <?php endif; ?>

          <?php if (Auth::can('cms')): ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/cms" class="nav-link <?= isActive($currentUri, $base.'/admin/cms') ?>">
              <i class="nav-icon fas fa-file-alt"></i><p>Pages du site</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('media')): ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/media" class="nav-link <?= isActive($currentUri, $base.'/admin/media') ?>">
              <i class="nav-icon fas fa-photo-video"></i><p>Médiathèque</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('forms')): ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/forms" class="nav-link <?= isActive($currentUri, $base.'/admin/forms') ?>">
              <i class="nav-icon fas fa-clipboard-list"></i>
              <p>Formulaires <?php if ($formUnread > 0): ?><span class="badge badge-warning right"><?= $formUnread ?></span><?php endif; ?></p>
            </a>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('galleries')): ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/galleries" class="nav-link <?= isActive($currentUri, $base.'/admin/galleries') ?>">
              <i class="nav-icon fas fa-layer-group"></i><p>Galeries</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('realisations')): ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/realisations" class="nav-link <?= isActive($currentUri, $base.'/admin/realisations') ?>">
              <i class="nav-icon fas fa-images"></i><p>Réalisations</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('themes')): ?>
          <li class="nav-item has-treeview <?= isActive($currentUri, $base.'/admin/themes', $base.'/admin/homepage', $base.'/admin/page-editor') ? 'menu-open' : '' ?>">
            <a href="<?= $base ?>/admin/themes" class="nav-link <?= isActive($currentUri, $base.'/admin/themes', $base.'/admin/homepage', $base.'/admin/page-editor') ?>">
              <i class="nav-icon fas fa-paint-brush"></i><p>Thèmes <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="<?= $base ?>/admin/themes" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Gérer les thèmes</p></a></li>
              <li class="nav-item"><a href="<?= $base ?>/admin/homepage" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Page d'accueil</p></a></li>
              <li class="nav-item"><a href="<?= $base ?>/admin/page-editor" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Templates HTML</p></a></li>
            </ul>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('settings')): ?>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/settings" class="nav-link <?= isActive($currentUri, $base.'/admin/settings') ?>">
              <i class="nav-icon fas fa-cog"></i><p>Réglages</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if (Auth::can('users')): ?>
          <li class="nav-header" style="color:rgba(255,255,255,.4);font-size:11px;padding:10px 8px 4px;">ADMINISTRATION</li>
          <li class="nav-item">
            <a href="<?= $base ?>/admin/users" class="nav-link <?= isActive($currentUri, $base.'/admin/users') ?>">
              <i class="nav-icon fas fa-users-cog"></i><p>Utilisateurs</p>
            </a>
          </li>
          <?php endif; ?>

          <li class="nav-item">
            <a href="<?= $base ?>/admin/profile" class="nav-link <?= isActive($currentUri, $base.'/admin/profile') ?>">
              <i class="nav-icon fas fa-user-cog"></i><p>Mon profil</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>

  <!-- Contenu principal -->
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">

        <?= $content ?>

      </div>
    </section>
  </div>

  <footer class="main-footer text-sm">
    <strong>JokerPeintre</strong> — Admin by <a href="#" target="_blank">Spiderdev</a> &copy; <?= date('Y') ?>
    <span style="float:right;">CMS v<?= defined('CMS_VERSION') ? CMS_VERSION : '1.0.0' ?></span>
  </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
<script>$(function(){ if(window.bsCustomFileInput){ bsCustomFileInput.init(); } });</script>
</body>
</html>
