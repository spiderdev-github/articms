<?php
    require_once __DIR__ . '/../auth.php';
    requireAdmin();
    $csrf = getCsrfToken();

    require_once __DIR__ . '/../../includes/db.php';
    $pdo = getPDO();
    $me = getCurrentAdmin();
    $newCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status='new'")->fetchColumn();

    function _avatarBgColor(string $str): string {
        $colors = ['#c0392b','#2980b9','#27ae60','#8e44ad','#d35400','#16a085','#2c3e50','#e67e22'];
        return $colors[abs(crc32($str)) % count($colors)];
    }
    $meDisplayName = $me['display_name'] ?: $me['username'];
    $meInitial = mb_strtoupper(mb_substr($meDisplayName, 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Joker Peintre</title>

  <!-- AdminLTE v3 style via CDN (Bootstrap 4) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">

  <style>
    .brand-link{background:#0b0c10;}
    .main-sidebar{background:#0b0c10;}
    .content-wrapper{background:#0f1116;}
    .card{border-radius:14px;}
    .small-box{border-radius:14px;}
    .badge{font-size:12px;}
    .table td{vertical-align:middle;}
  </style>
</head>

<body class="hold-transition sidebar-mini dark-mode">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?= BASE_URL ?>/" class="nav-link" target="_blank">Voir le site</a>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="gap:8px;">
          <div style="width:30px;height:30px;border-radius:50%;background:<?= _avatarBgColor($me['username']) ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;overflow:hidden;">
            <?php if (!empty($me['avatar'])): ?>
              <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars(basename($me['avatar'])) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
            <?php else: ?>
              <?= $meInitial ?>
            <?php endif; ?>
          </div>
          <span class="d-none d-sm-inline" style="font-size:13px;"><?= htmlspecialchars($meDisplayName) ?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
          <a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog mr-2"></i>Mon profil</a>
          <?php if (can('users')): ?>
          <a class="dropdown-item" href="users.php"><i class="fas fa-users mr-2"></i>Utilisateurs</a>
          <?php endif; ?>
          <div class="dropdown-divider"></div>
          <span class="dropdown-item text-muted" style="font-size:11px;">
            <span class="badge badge-<?= ROLES_COLORS[$me['role']] ?? 'secondary' ?>"><?= ROLES_LABELS[$me['role']] ?? $me['role'] ?></span>
          </span>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-right-from-bracket mr-2"></i>Déconnexion</a>
        </div>
      </li>
    </ul>
  </nav>

  <aside class="main-sidebar elevation-4">
    <a href="dashboard.php" class="brand-link">
      <span class="brand-text font-weight-bold">JokerPeintre Admin</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">


           <!-- DASHBOARD -->
            <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
            </a>
            </li>

          

          <?php if (can('crm')): ?>
            <li class="nav-header" style="color:rgba(255,255,255,.4); font-size:11px; padding:10px 8px 4px;">CRM</li>

              <!-- CONTACTS -->
              <?php if (can('contacts')): ?>
                <li class="nav-item">
                    <a href="contacts.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'contacts.php' ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-envelope"></i>
                    <p>
                        Contacts
                        <?php if($newCount > 0): ?>
                        <span class="badge badge-danger right"><?= $newCount ?></span>
                        <?php endif; ?>
                    </p>
                    </a>
                </li>
              <?php endif; ?>
            
             <li class="nav-item">
              <a href="crm-clients.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['crm-clients.php']) ? 'active' : '' ?>">
                <i class="nav-icon fas fa-users"></i>
                <p>Clients</p>
              </a>
            </li>

            <li class="nav-item">
              <a href="crm-devis.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['crm-devis.php']) ? 'active' : '' ?>">
                <i class="nav-icon fas fa-file-invoice"></i>
                <p>Devis &amp; Facture</p>
              </a>
            </li>
          <?php endif; ?>


            <!-- CMS -->
            <?php if (can('cms') || can('media') || can('menu') || can('themes') || can('forms') || can('settings')): ?>
            <li class="nav-header" style="color:rgba(255,255,255,.4); font-size:11px; padding:10px 8px 4px;">CMS</li>
            <?php endif; ?>

          <?php if (can('cms')): ?>
            <li class="nav-item">
              <a href="cms-pages.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['cms-pages.php']) ? 'active' : '' ?>">
                <i class="nav-icon fas fa-file-alt"></i>
                <p>Pages du site</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if (can('media')): ?>
            <li class="nav-item">
              <a href="media.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-photo-video"></i>
                <p>Médiathèque</p>
              </a>
            </li>
          <?php endif; ?>
          
          <?php if (can('forms')): ?>
            <li class="nav-item">
              <?php
                $formUnread = (int)$pdo->query("SELECT COUNT(*) FROM form_submissions WHERE is_read=0")->fetchColumn();
              ?>
              <a href="forms.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['forms.php','form-edit.php','form-submissions.php']) ? 'active' : '' ?>">
                <i class="nav-icon fas fa-clipboard-list"></i>
                <p>
                  Formulaires
                  <?php if ($formUnread > 0): ?>
                    <span class="badge badge-warning right"><?= $formUnread ?></span>
                  <?php endif; ?>
                </p>
              </a>
            </li>
          <?php endif; ?>

          <?php if (can('galleries')): ?>
            <li class="nav-item">
              <a href="galleries.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['galleries.php','gallery-edit.php']) ? 'active' : '' ?>">
                <i class="nav-icon fas fa-layer-group"></i>
                <p>Galeries</p>
              </a>
            </li>
          <?php endif; ?>
          
          <?php if (can('realisations')): ?>
            <li class="nav-item">
              <a href="realisations.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['realisations.php','realisation-edit.php','realisation-create.php','realisations-settings.php']) ? 'active' : '' ?>">
                <i class="nav-icon fas fa-images"></i>
                <p>Réalisations</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if (can('menu')): ?>
            <li class="nav-item">
              <a href="menu.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-list-ul"></i>
                <p>Menu navigation</p>
              </a>
            </li>
          <?php endif; ?>

          
          
          <?php if (can('themes')): ?>
            <?php $isThemePage = in_array(basename($_SERVER['PHP_SELF']), ['themes.php','theme-edit.php','homepage.php','page-editor.php']); ?>
            <li class="nav-item has-treeview <?= $isThemePage ? 'menu-open' : '' ?>">
              <a href="themes.php" class="nav-link <?= $isThemePage ? 'active' : '' ?>">
                <i class="nav-icon fas fa-paint-brush"></i>
                <p>Thèmes <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="themes.php" class="nav-link">
                    <i class="far <?= in_array(basename($_SERVER['PHP_SELF']), ['themes.php','theme-edit.php']) ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>Gérer les thèmes</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="homepage.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'homepage.php' ? 'active' : '' ?>">
                    <i class="far <?= basename($_SERVER['PHP_SELF']) === 'homepage.php' ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>Page d'accueil</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="page-editor.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'page-editor.php' ? 'active' : '' ?>">
                    <i class="far <?= basename($_SERVER['PHP_SELF']) === 'page-editor.php' ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>Templates HTML</p>
                  </a>
                </li>
              </ul>
            </li>
          <?php endif; ?>
          
          <?php if (can('settings')): ?>
            <li class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'menu-open' : '' ?>">
              <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-cog"></i>
                <p>Réglages <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="settings.php?tab=dashboard" class="nav-link">
                    <i class="far <?= (basename($_SERVER['PHP_SELF']) === 'settings.php' && ($_GET['tab'] ?? '') === 'dashboard') ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>Dashboard</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="settings.php?tab=company" class="nav-link">
                    <i class="far <?= (basename($_SERVER['PHP_SELF']) === 'settings.php' && ($_GET['tab'] ?? 'company') === 'company') ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>Entreprise</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="settings.php?tab=smtp" class="nav-link">
                    <i class="far <?= (basename($_SERVER['PHP_SELF']) === 'settings.php' && ($_GET['tab'] ?? '') === 'smtp') ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>SMTP</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="settings.php?tab=recaptcha" class="nav-link">
                    <i class="far <?= (basename($_SERVER['PHP_SELF']) === 'settings.php' && ($_GET['tab'] ?? '') === 'recaptcha') ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>reCAPTCHA</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="settings.php?tab=robots" class="nav-link">
                    <i class="far <?= (basename($_SERVER['PHP_SELF']) === 'settings.php' && ($_GET['tab'] ?? '') === 'robots') ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>robots.txt</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="settings.php?tab=sitemap" class="nav-link">
                    <i class="far <?= (basename($_SERVER['PHP_SELF']) === 'settings.php' && ($_GET['tab'] ?? '') === 'sitemap') ? 'fa-dot-circle' : 'fa-circle' ?> nav-icon"></i>
                    <p>Sitemap</p>
                  </a>
                </li>
              </ul>
            </li>
          <?php endif; ?>

          <!-- ADMIN SECTION -->
          <?php if (can('users')): ?>
            <li class="nav-header" style="color:rgba(255,255,255,.4); font-size:11px; padding:10px 8px 4px;">ADMINISTRATION</li>
            <li class="nav-item">
              <a href="users.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['users.php','user-edit.php']) ? 'active' : '' ?>">
                <i class="nav-icon fas fa-users-cog"></i>
                <p>Utilisateurs</p>
              </a>
            </li>
          <?php endif; ?>

          <!-- Profile always visible -->
          <li class="nav-item">
            <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-user-cog"></i>
              <p>Mon profil</p>
            </a>
          </li>

        </ul>
        </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">