<?php
// =============================================
//  ÉVASIO — Header HTML (head + topbar)
//  Paramètres attendus :
//    $pageTitle  : titre de la page
//    $pageSubtitle (optionnel)
// =============================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();

$user          = utilisateurCourant();
$nbNotifs      = compterNotificationsNonLues((int)$user['id']);
$today         = dateFr(date('Y-m-d'));
$pageTitle     = $pageTitle     ?? 'Évasio';
$pageSubtitle  = $pageSubtitle  ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= h($pageTitle) ?> — Évasio</title>
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/favicon.svg"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css"/>
  <?php if (isset($extraCSS)): ?>
    <?= $extraCSS ?>
  <?php endif; ?>
</head>
<body>
<div class="app-layout">

<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

<!-- Overlay mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-wrapper">
  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <!-- Bouton hamburger (mobile uniquement) -->
      <button id="menuToggle" class="topbar-btn" style="display:none" aria-label="Menu">
        <i class="fas fa-bars"></i>
      </button>
      <h1 class="topbar-title"><?= h($pageTitle) ?></h1>
      <?php if ($pageSubtitle): ?>
        <p class="topbar-subtitle"><?= h($pageSubtitle) ?></p>
      <?php endif; ?>
    </div>
    <div class="topbar-right">
      <span class="topbar-date">
        <i class="fas fa-calendar"></i><?= $today ?>
      </span>
      <a href="<?= APP_URL ?>/notifications.php" class="topbar-btn" title="Notifications">
        <i class="fas fa-bell"></i>
        <?php if ($nbNotifs > 0): ?>
          <span class="notif-dot"></span>
        <?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/profil.php" class="topbar-btn" title="Mon profil">
        <i class="fas fa-user-circle"></i>
      </a>
      <?php if (in_array($user['role'], ['employe', 'manager'])): ?>
      <a href="<?= APP_URL ?>/demande_conge.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Nouvelle demande
      </a>
      <?php endif; ?>
    </div>
  </header>
  <!-- FIN TOPBAR -->

  <!-- Toast container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Contenu de la page -->
  <main class="page-content">
