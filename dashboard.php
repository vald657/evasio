<?php
// =============================================
//  IUC — Dashboard (adaptatif par rôle)
// =============================================
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$user  = utilisateurCourant();
$role  = $user['role'];
$db    = getDB();
$annee = (int)date('Y');

// ---- KPIs selon le rôle ----------------------------------------
if ($role === 'employe') {

    // Demandes de l'employé
    $stmt = $db->prepare("SELECT COUNT(*) FROM demandes_conge WHERE utilisateur_id = ? AND YEAR(created_at) = ?");
    $stmt->execute([$user['id'], $annee]);
    $totalDemandes = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM demandes_conge WHERE utilisateur_id = ? AND statut = 'en_attente'");
    $stmt->execute([$user['id']]);
    $enAttente = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM demandes_conge WHERE utilisateur_id = ? AND statut = 'approuve_rh'");
    $stmt->execute([$user['id']]);
    $approuves = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM demandes_conge WHERE utilisateur_id = ? AND statut IN ('refuse_manager','refuse_rh')");
    $stmt->execute([$user['id']]);
    $refuses = (int)$stmt->fetchColumn();

    // Soldes
    $soldes = tousLesSoldes((int)$user['id'], $annee);

    // Dernières demandes
    $stmt = $db->prepare("
        SELECT dc.*, tc.nom AS type_nom, tc.couleur
        FROM demandes_conge dc
        JOIN types_conge tc ON tc.id = dc.type_conge_id
        WHERE dc.utilisateur_id = ?
        ORDER BY dc.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $dernieresDemandes = $stmt->fetchAll();

} elseif ($role === 'manager') {

    // Stats pour l'équipe du manager
    $stmt = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE manager_id = ? AND actif = 1");
    $stmt->execute([$user['id']]);
    $nbEquipe = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM demandes_conge dc
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        WHERE u.manager_id = ? AND dc.statut = 'en_attente'
    ");
    $stmt->execute([$user['id']]);
    $enAttenteManager = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM demandes_conge dc
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        WHERE u.manager_id = ? AND dc.statut IN ('approuve_manager','approuve_rh')
        AND MONTH(dc.date_debut) = MONTH(CURDATE())
    ");
    $stmt->execute([$user['id']]);
    $approuvesManager = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM demandes_conge dc
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        WHERE u.manager_id = ? AND dc.date_debut <= CURDATE() AND dc.date_fin >= CURDATE()
        AND dc.statut = 'approuve_rh'
    ");
    $stmt->execute([$user['id']]);
    $absentsAujourd = (int)$stmt->fetchColumn();

    // Demandes en attente
    $stmt = $db->prepare("
        SELECT dc.*, tc.nom AS type_nom, tc.couleur,
               u.nom, u.prenom, u.photo,
               d.nom AS departement
        FROM demandes_conge dc
        JOIN types_conge tc ON tc.id = dc.type_conge_id
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        LEFT JOIN departements d ON d.id = u.departement_id
        WHERE u.manager_id = ? AND dc.statut = 'en_attente'
        ORDER BY dc.created_at ASC
        LIMIT 8
    ");
    $stmt->execute([$user['id']]);
    $demandesEnAttente = $stmt->fetchAll();

} else {
    // RH & Admin : vue globale
    $stmt = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE actif = 1 AND role = 'employe'");
    $totalEmployes = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM demandes_conge WHERE statut = 'en_attente'");
    $enAttenteRH = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM demandes_conge WHERE statut = 'approuve_manager'");
    $enAttenteRHFinal = (int)$stmt->fetchColumn();

    $stmt = $db->query("
        SELECT COUNT(*) FROM demandes_conge
        WHERE statut = 'approuve_rh'
        AND date_debut <= CURDATE() AND date_fin >= CURDATE()
    ");
    $absentsToday = (int)$stmt->fetchColumn();

    $stmt = $db->query("
        SELECT COUNT(*) FROM demandes_conge
        WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
    ");
    $demandesMois = (int)$stmt->fetchColumn();

    // Dernières demandes tous employés
    $stmt = $db->query("
        SELECT dc.*, tc.nom AS type_nom, tc.couleur,
               u.nom, u.prenom, u.photo, u.id AS uid,
               d.nom AS departement
        FROM demandes_conge dc
        JOIN types_conge tc ON tc.id = dc.type_conge_id
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        LEFT JOIN departements d ON d.id = u.departement_id
        ORDER BY dc.created_at DESC
        LIMIT 8
    ");
    $dernieresDemandesGlobal = $stmt->fetchAll();

    // Stats par département
    $stmt = $db->query("
        SELECT d.nom, COUNT(dc.id) AS nb
        FROM demandes_conge dc
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        JOIN departements d ON d.id = u.departement_id
        WHERE YEAR(dc.created_at) = YEAR(CURDATE())
        GROUP BY d.id
        ORDER BY nb DESC
        LIMIT 5
    ");
    $statsDept = $stmt->fetchAll();
}

// ---- Activité récente (filtrée par rôle) --------------------------
if ($role === 'employe') {
    $stA = $db->prepare("SELECT la.*, u.nom, u.prenom, u.role FROM logs_activite la LEFT JOIN utilisateurs u ON u.id = la.utilisateur_id WHERE la.utilisateur_id = ? ORDER BY la.created_at DESC LIMIT 6");
    $stA->execute([$user['id']]);
} elseif ($role === 'manager') {
    // Ses propres actions + celles de son équipe
    $stA = $db->prepare("SELECT la.*, u.nom, u.prenom, u.role FROM logs_activite la LEFT JOIN utilisateurs u ON u.id = la.utilisateur_id WHERE la.utilisateur_id = ? OR la.utilisateur_id IN (SELECT id FROM utilisateurs WHERE manager_id = ?) ORDER BY la.created_at DESC LIMIT 6");
    $stA->execute([$user['id'], $user['id']]);
} elseif ($role === 'rh') {
    // Actions des employés et managers uniquement (pas admin/autres rh)
    $stA = $db->prepare("SELECT la.*, u.nom, u.prenom, u.role FROM logs_activite la LEFT JOIN utilisateurs u ON u.id = la.utilisateur_id WHERE u.role IN ('employe','manager') OR la.utilisateur_id = ? ORDER BY la.created_at DESC LIMIT 6");
    $stA->execute([$user['id']]);
} else {
    // Admin : tout
    $stA = $db->query("SELECT la.*, u.nom, u.prenom, u.role FROM logs_activite la LEFT JOIN utilisateurs u ON u.id = la.utilisateur_id ORDER BY la.created_at DESC LIMIT 6");
}
$activites = $stA->fetchAll();

// ---- Page setup ---------------------------------------------------
$pageTitle    = 'Tableau de bord';
$pageSubtitle = 'Bienvenue, ' . $user['prenom'] . ' — voici un résumé de l\'activité';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ========================= EMPLOYÉ ========================= -->
<?php if ($role === 'employe'): ?>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-header">
      <div class="kpi-icon indigo"><i class="fas fa-file-alt"></i></div>
    </div>
    <div class="kpi-value"><?= $totalDemandes ?></div>
    <div class="kpi-label">Mes demandes <?= $annee ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header">
      <div class="kpi-icon orange"><i class="fas fa-hourglass-half"></i></div>
    </div>
    <div class="kpi-value"><?= $enAttente ?></div>
    <div class="kpi-label">En attente</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header">
      <div class="kpi-icon green"><i class="fas fa-check-circle"></i></div>
    </div>
    <div class="kpi-value"><?= $approuves ?></div>
    <div class="kpi-label">Approuvées</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header">
      <div class="kpi-icon red"><i class="fas fa-times-circle"></i></div>
    </div>
    <div class="kpi-value"><?= $refuses ?></div>
    <div class="kpi-label">Refusées</div>
  </div>
</div>

<div class="section-grid">
  <!-- Mes soldes -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Mes soldes de congés</div>
        <div class="card-subtitle">Exercice <?= $annee ?></div>
      </div>
      <a href="<?= APP_URL ?>/demande_conge.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Demander
      </a>
    </div>
    <div class="card-body">
      <?php if (empty($soldes)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="fas fa-wallet"></i></div>
          <h3>Aucun solde configuré</h3>
          <p>Le RH n'a pas encore défini vos soldes de congés.</p>
        </div>
      <?php else: ?>
        <?php foreach ($soldes as $s):
          $pct = $s['jours_alloues'] > 0
            ? min(100, round($s['jours_pris'] / $s['jours_alloues'] * 100))
            : 0;
        ?>
        <div class="solde-item">
          <div class="solde-header">
            <span class="solde-label">
              <span style="width:10px;height:10px;border-radius:50%;background:<?= h($s['couleur']) ?>;display:inline-block"></span>
              <?= h($s['type_nom']) ?>
            </span>
            <span class="solde-value"><?= $s['jours_restants'] ?> / <?= $s['jours_alloues'] ?> j</span>
          </div>
          <div class="solde-bar">
            <div class="solde-fill" style="width:<?= $pct ?>%;background:<?= h($s['couleur']) ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Dernières demandes -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Mes dernières demandes</div>
      </div>
      <a href="<?= APP_URL ?>/mes_demandes.php" class="btn-link">Voir tout →</a>
    </div>
    <?php if (empty($dernieresDemandes)): ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
        <h3>Aucune demande</h3>
        <p>Vous n'avez pas encore soumis de demande.</p>
        <a href="<?= APP_URL ?>/demande_conge.php" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i> Première demande
        </a>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Type</th><th>Période</th><th>Jours</th><th>Statut</th></tr>
          </thead>
          <tbody>
            <?php foreach ($dernieresDemandes as $d): ?>
            <tr>
              <td>
                <span style="display:flex;align-items:center;gap:7px">
                  <span style="width:8px;height:8px;border-radius:50%;background:<?= h($d['couleur']) ?>;flex-shrink:0"></span>
                  <?= h($d['type_nom']) ?>
                </span>
              </td>
              <td style="font-size:12.5px">
                <?= formatDate($d['date_debut']) ?> – <?= formatDate($d['date_fin']) ?>
              </td>
              <td><?= $d['nombre_jours'] ?> j</td>
              <td>
                <span class="badge <?= classeStatut($d['statut']) ?>">
                  <i class="fas <?= iconeStatut($d['statut']) ?>"></i>
                  <?= labelStatut($d['statut']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ========================= MANAGER ========================= -->
<?php elseif ($role === 'manager'): ?>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon indigo"><i class="fas fa-users"></i></div></div>
    <div class="kpi-value"><?= $nbEquipe ?></div>
    <div class="kpi-label">Membres de mon équipe</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon orange"><i class="fas fa-hourglass-half"></i></div></div>
    <div class="kpi-value"><?= $enAttenteManager ?></div>
    <div class="kpi-label">Demandes à traiter</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon green"><i class="fas fa-check-circle"></i></div></div>
    <div class="kpi-value"><?= $approuvesManager ?></div>
    <div class="kpi-label">Approuvées ce mois</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon pink"><i class="fas fa-user-clock"></i></div></div>
    <div class="kpi-value"><?= $absentsAujourd ?></div>
    <div class="kpi-label">Absents aujourd'hui</div>
  </div>
</div>

<div class="card mb-24">
  <div class="card-header">
    <div>
      <div class="card-title">Demandes en attente de validation</div>
      <div class="card-subtitle"><?= count($demandesEnAttente) ?> demande(s) nécessitent votre approbation</div>
    </div>
    <a href="<?= APP_URL ?>/approbation.php" class="btn btn-primary btn-sm">
      <i class="fas fa-stamp"></i> Gérer
    </a>
  </div>
  <?php if (empty($demandesEnAttente)): ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-check-double"></i></div>
      <h3>Tout est traité !</h3>
      <p>Aucune demande en attente pour le moment.</p>
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Employé</th><th>Type</th><th>Période</th><th>Durée</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($demandesEnAttente as $d): ?>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar-sm" style="background:<?= couleurAvatar((int)$d['utilisateur_id']) ?>">
                  <?= initialesAvatar($d['prenom'], $d['nom']) ?>
                </div>
                <div>
                  <div class="user-name"><?= h($d['prenom'] . ' ' . $d['nom']) ?></div>
                  <div class="user-dept"><?= h($d['departement'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td><?= h($d['type_nom']) ?></td>
            <td style="font-size:12.5px">
              <?= formatDate($d['date_debut']) ?> – <?= formatDate($d['date_fin']) ?>
            </td>
            <td><?= $d['nombre_jours'] ?> j</td>
            <td>
              <div class="action-btns">
                <button class="btn-icon approve" title="Approuver"
                        onclick="approuverDemande(<?= $d['id'] ?>)">
                  <i class="fas fa-check"></i>
                </button>
                <button class="btn-icon refuse" title="Refuser"
                        onclick="ouvrirModalRefus(<?= $d['id'] ?>)">
                  <i class="fas fa-times"></i>
                </button>
                <a href="<?= APP_URL ?>/detail_demande.php?id=<?= $d['id'] ?>" class="btn-icon" title="Détail">
                  <i class="fas fa-eye"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- ========================= RH & ADMIN ========================= -->
<?php else: ?>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon indigo"><i class="fas fa-users"></i></div></div>
    <div class="kpi-value"><?= $totalEmployes ?></div>
    <div class="kpi-label">Employés actifs</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon orange"><i class="fas fa-hourglass-half"></i></div></div>
    <div class="kpi-value"><?= $enAttenteRH + $enAttenteRHFinal ?></div>
    <div class="kpi-label">En attente (total)</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon pink"><i class="fas fa-stamp"></i></div></div>
    <div class="kpi-value"><?= $enAttenteRHFinal ?></div>
    <div class="kpi-label">À approuver (RH)</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon green"><i class="fas fa-user-clock"></i></div></div>
    <div class="kpi-value"><?= $absentsToday ?></div>
    <div class="kpi-label">Absents aujourd'hui</div>
  </div>
</div>

<div class="section-grid">
  <!-- Dernières demandes globales -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Dernières demandes</div>
        <div class="card-subtitle"><?= $demandesMois ?> demandes ce mois</div>
      </div>
      <a href="<?= APP_URL ?>/approbation.php" class="btn-link">Voir tout →</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Employé</th><th>Type</th><th>Période</th><th>Statut</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($dernieresDemandesGlobal as $d): ?>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar-sm" style="background:<?= couleurAvatar((int)$d['uid']) ?>">
                  <?= initialesAvatar($d['prenom'], $d['nom']) ?>
                </div>
                <div>
                  <div class="user-name"><?= h($d['prenom'] . ' ' . $d['nom']) ?></div>
                  <div class="user-dept"><?= h($d['departement'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:12.5px"><?= h($d['type_nom']) ?></td>
            <td style="font-size:12px">
              <?= formatDate($d['date_debut']) ?> – <?= formatDate($d['date_fin']) ?>
            </td>
            <td>
              <span class="badge <?= classeStatut($d['statut']) ?>">
                <i class="fas <?= iconeStatut($d['statut']) ?>"></i>
                <?= labelStatut($d['statut']) ?>
              </span>
            </td>
            <td>
              <div class="action-btns">
                <?php if (in_array($d['statut'], ['en_attente','approuve_manager'])): ?>
                <button class="btn-icon approve" title="Approuver"
                        onclick="approuverDemande(<?= $d['id'] ?>)">
                  <i class="fas fa-check"></i>
                </button>
                <button class="btn-icon refuse" title="Refuser"
                        onclick="ouvrirModalRefus(<?= $d['id'] ?>)">
                  <i class="fas fa-times"></i>
                </button>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/detail_demande.php?id=<?= $d['id'] ?>" class="btn-icon" title="Détail">
                  <i class="fas fa-eye"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Stats par département -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Demandes par département</div>
      <div class="card-subtitle">Exercice <?= $annee ?></div>
    </div>
    <div class="card-body">
      <?php if (empty($statsDept)): ?>
        <div class="empty-state"><p>Aucune donnée disponible.</p></div>
      <?php else:
        $maxVal = max(array_column($statsDept, 'nb'));
        foreach ($statsDept as $sd):
          $pct = $maxVal > 0 ? round($sd['nb'] / $maxVal * 100) : 0;
      ?>
        <div class="solde-item">
          <div class="solde-header">
            <span class="solde-label"><?= h($sd['nom']) ?></span>
            <span class="solde-value"><?= $sd['nb'] ?> dem.</span>
          </div>
          <div class="solde-bar">
            <div class="solde-fill" style="width:<?= $pct ?>%;background:linear-gradient(to right,var(--primary),var(--secondary))"></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- ========================= ACTIVITÉ (tous) ========================= -->
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Activité récente</div>
      <div class="card-subtitle">Dernières actions sur la plateforme</div>
    </div>
    <?php if ($role === 'admin'): ?>
      <a href="<?= APP_URL ?>/logs.php" class="btn-link">Voir journal complet →</a>
    <?php endif; ?>
  </div>
  <div style="padding:4px 24px 20px">
    <?php if (empty($activites)): ?>
      <div class="empty-state"><p>Aucune activité enregistrée.</p></div>
    <?php else: ?>
      <?php
      $icones = [
        'connexion'           => ['fa-sign-in-alt',  'var(--info-container)',    'var(--primary)'],
        'deconnexion'         => ['fa-sign-out-alt',  'var(--surface-high)',      'var(--outline)'],
        'demande_soumise'     => ['fa-paper-plane',   'var(--info-container)',    'var(--primary)'],
        'demande_approuvee'   => ['fa-check',         'var(--success-container)', 'var(--success)'],
        'demande_refusee'     => ['fa-times',         'var(--error-container)',   'var(--error)'],
        'employe_cree'        => ['fa-user-plus',     'rgba(180,19,109,0.10)',    'var(--secondary)'],
        'solde_modifie'       => ['fa-wallet',        'var(--warning-container)', 'var(--tertiary)'],
      ];
      foreach ($activites as $act):
        $ico = $icones[$act['action']] ?? ['fa-circle', 'var(--surface-high)', 'var(--outline)'];
        $auteur = $act['prenom'] ? h($act['prenom'] . ' ' . $act['nom']) : 'Système';
      ?>
      <div class="activity-item">
        <div class="activity-icon" style="background:<?= $ico[1] ?>;color:<?= $ico[2] ?>">
          <i class="fas <?= $ico[0] ?>"></i>
        </div>
        <div>
          <div class="activity-text">
            <strong><?= $auteur ?></strong> — <?= h($act['description']) ?>
          </div>
          <div class="activity-time">
            <i class="fas fa-clock" style="margin-right:4px"></i>
            <?= (new DateTime($act['created_at']))->format('d/m/Y à H\hi') ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Modal refus -->
<div class="modal-overlay" id="modalRefus">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-times-circle" style="color:var(--error);margin-right:8px"></i>Refuser la demande</span>
      <button class="modal-close" onclick="fermerModal('modalRefus')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="refusDemandeId"/>
      <div class="form-group">
        <label class="form-label">Motif du refus <span class="required">*</span></label>
        <textarea id="refusCommentaire" class="form-control" rows="4"
                  placeholder="Expliquez la raison du refus..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="fermerModal('modalRefus')">Annuler</button>
      <button class="btn btn-danger" onclick="confirmerRefus()">
        <i class="fas fa-times"></i> Refuser
      </button>
    </div>
  </div>
</div>

<script>
function approuverDemande(id) {
  if (!confirm('Approuver cette demande ?')) return;
  fetch('<?= APP_URL ?>/api/demandes.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=approuver&id=' + id + '&csrf_token=<?= csrfToken() ?>'
  })
  .then(r => r.json())
  .then(data => {
    showToast(data.success ? 'success' : 'error', data.message);
    if (data.success) setTimeout(() => location.reload(), 1200);
  });
}

function ouvrirModalRefus(id) {
  document.getElementById('refusDemandeId').value = id;
  document.getElementById('refusCommentaire').value = '';
  document.getElementById('modalRefus').classList.add('active');
}

function confirmerRefus() {
  const id  = document.getElementById('refusDemandeId').value;
  const com = document.getElementById('refusCommentaire').value.trim();
  if (!com) { showToast('error', 'Veuillez saisir un motif de refus.'); return; }
  fetch('<?= APP_URL ?>/api/demandes.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=refuser&id=' + id + '&commentaire=' + encodeURIComponent(com) + '&csrf_token=<?= csrfToken() ?>'
  })
  .then(r => r.json())
  .then(data => {
    showToast(data.success ? 'success' : 'error', data.message);
    if (data.success) { fermerModal('modalRefus'); setTimeout(() => location.reload(), 1200); }
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
