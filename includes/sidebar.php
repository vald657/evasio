<?php
// =============================================
//  ÉVASIO — Sidebar navigation
// =============================================
$user     = utilisateurCourant();
$role     = $user['role'];
$initials = initialesAvatar($user['prenom'], $user['nom']);
$couleur  = couleurAvatar((int)$user['id']);
$nbNotifs = compterNotificationsNonLues((int)$user['id']);

// Page active
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

function navItem(string $href, string $icon, string $label, string $currentPage, int $badge = 0): string {
    $page    = basename($href, '.php');
    $active  = ($page === $currentPage) ? ' active' : '';
    $badgeHtml = $badge > 0
        ? '<span class="nav-badge">' . $badge . '</span>'
        : '';
    return '<a class="nav-item' . $active . '" href="' . $href . '">
              <i class="fas ' . $icon . '"></i> ' . $label . $badgeHtml . '
            </a>';
}

// Compter demandes en attente selon le rôle
$db = getDB();
$pendingCount = 0;
if ($role === 'manager') {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM demandes_conge dc
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        WHERE u.manager_id = ? AND dc.statut = 'en_attente'
    ");
    $stmt->execute([$user['id']]);
    $pendingCount = (int)$stmt->fetchColumn();
} elseif ($role === 'rh') {
    $stmt = $db->query("SELECT COUNT(*) FROM demandes_conge WHERE statut = 'approuve_manager'");
    $pendingCount = (int)$stmt->fetchColumn();
} elseif ($role === 'admin') {
    $stmt = $db->query("SELECT COUNT(*) FROM demandes_conge WHERE statut IN ('en_attente','approuve_manager')");
    $pendingCount = (int)$stmt->fetchColumn();
}
?>
<aside class="sidebar" id="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect width="40" height="40" rx="10" fill="#f5f2fe"/>
      <circle cx="20" cy="13" r="5.5" fill="none" stroke="#b4136d" stroke-width="2"/>
      <line x1="20" y1="7.5" x2="20" y2="4.5" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
      <line x1="24.9" y1="9.1" x2="27" y2="7" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
      <line x1="26.5" y1="13" x2="29.5" y2="13" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
      <line x1="15.1" y1="9.1" x2="13" y2="7" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
      <line x1="13.5" y1="13" x2="10.5" y2="13" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
      <rect x="11" y="20" width="18" height="14" rx="3" fill="#4648d4"/>
      <polyline points="15,27 18.5,31 25,23" stroke="#b4136d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
    </svg>
    <span class="sidebar-logo-text">Évasio</span>
  </div>

  <!-- User card -->
  <div class="sidebar-user">
    <div class="sidebar-user-avatar" style="background:<?= $couleur ?>">
      <?php if ($user['photo']): ?>
        <img src="<?= APP_URL ?>/assets/img/photos/<?= h($user['photo']) ?>" alt="photo"/>
      <?php else: ?>
        <?= h($initials) ?>
      <?php endif; ?>
    </div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= h($user['prenom'] . ' ' . $user['nom']) ?></div>
      <div class="sidebar-user-role"><?= h(ucfirst($role)) ?></div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <!-- Principal -->
    <div class="nav-section-title">Principal</div>
    <?= navItem(APP_URL.'/dashboard.php',    'fa-chart-pie',    'Tableau de bord', $currentPage) ?>
    <?= navItem(APP_URL.'/calendrier.php',   'fa-calendar-alt', 'Calendrier',      $currentPage) ?>
    <?= navItem(APP_URL.'/notifications.php','fa-bell',         'Notifications',   $currentPage, $nbNotifs) ?>

    <!-- Congés -->
    <div class="nav-section-title">Congés</div>
    <?php if (in_array($role, ['employe','manager','rh','admin'])): ?>
      <?= navItem(APP_URL.'/demande_conge.php', 'fa-plus-circle',  'Nouvelle demande', $currentPage) ?>
      <?= navItem(APP_URL.'/mes_demandes.php',  'fa-list-check',   'Mes demandes',     $currentPage) ?>
    <?php endif; ?>
    <?php if (in_array($role, ['manager','rh','admin'])): ?>
      <?= navItem(APP_URL.'/approbation.php', 'fa-stamp', 'Approbations', $currentPage, $pendingCount) ?>
    <?php endif; ?>
    <?php if ($role === 'manager'): ?>
      <?= navItem(APP_URL.'/mon_equipe.php', 'fa-users', 'Mon équipe', $currentPage) ?>
    <?php endif; ?>

    <!-- RH -->
    <?php if (in_array($role, ['rh','admin'])): ?>
    <div class="nav-section-title">Ressources Humaines</div>
    <?= navItem(APP_URL.'/employes.php',      'fa-users',     'Employés',       $currentPage) ?>
    <?= navItem(APP_URL.'/types_conge.php',   'fa-tags',      'Types de congé', $currentPage) ?>
    <?= navItem(APP_URL.'/gestion_soldes.php','fa-wallet',    'Soldes',         $currentPage) ?>
    <?= navItem(APP_URL.'/rapports.php',      'fa-chart-bar', 'Rapports',       $currentPage) ?>
    <?php endif; ?>

    <!-- Administration -->
    <?php if ($role === 'admin'): ?>
    <div class="nav-section-title">Administration</div>
    <?= navItem(APP_URL.'/utilisateurs.php', 'fa-user-shield', 'Utilisateurs',      $currentPage) ?>
    <?= navItem(APP_URL.'/departements.php', 'fa-sitemap',     'Départements',      $currentPage) ?>
    <?= navItem(APP_URL.'/configuration.php','fa-cog',         'Paramètres',        $currentPage) ?>
    <?= navItem(APP_URL.'/logs.php',         'fa-scroll',      "Journal d'activité",$currentPage) ?>
    <?php endif; ?>

    <!-- Compte -->
    <div class="nav-section-title">Mon compte</div>
    <?= navItem(APP_URL.'/profil.php', 'fa-user-circle', 'Mon profil', $currentPage) ?>

  </nav>

  <!-- Footer -->
  <div class="sidebar-footer">
    <form action="<?= APP_URL ?>/logout.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
      <button type="submit" class="btn-logout">
        <i class="fas fa-sign-out-alt"></i> Déconnexion
      </button>
    </form>
  </div>

</aside>
