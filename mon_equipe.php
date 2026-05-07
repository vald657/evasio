<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['manager']);
$user=utilisateurCourant(); $db=getDB(); $annee=(int)date('Y');

// Membres de l'équipe avec leurs infos et soldes
$stmt=$db->prepare("
    SELECT u.*, d.nom AS departement,
           (SELECT COUNT(*) FROM demandes_conge dc WHERE dc.utilisateur_id=u.id AND dc.statut='en_attente') AS nb_attente,
           (SELECT COUNT(*) FROM demandes_conge dc WHERE dc.utilisateur_id=u.id AND dc.statut='approuve_rh' AND dc.date_debut<=CURDATE() AND dc.date_fin>=CURDATE()) AS absent_aujourd_hui
    FROM utilisateurs u
    LEFT JOIN departements d ON d.id=u.departement_id
    WHERE u.manager_id=? AND u.actif=1
    ORDER BY u.nom,u.prenom
");
$stmt->execute([$user['id']]);
$membres=$stmt->fetchAll();

$pageTitle='Mon équipe'; $pageSubtitle=count($membres).' membre(s) sous ma responsabilité';
require_once __DIR__.'/includes/header.php';
?>
<div class="kpi-grid" style="margin-bottom:24px">
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon indigo"><i class="fas fa-users"></i></div></div>
    <div class="kpi-value"><?=count($membres)?></div>
    <div class="kpi-label">Membres de l'équipe</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon orange"><i class="fas fa-hourglass-half"></i></div></div>
    <div class="kpi-value"><?=array_sum(array_column($membres,'nb_attente'))?></div>
    <div class="kpi-label">Demandes en attente</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon pink"><i class="fas fa-user-clock"></i></div></div>
    <div class="kpi-value"><?=array_sum(array_column($membres,'absent_aujourd_hui'))?></div>
    <div class="kpi-label">Absent(s) aujourd'hui</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><div class="kpi-icon green"><i class="fas fa-user-check"></i></div></div>
    <div class="kpi-value"><?=count($membres)-array_sum(array_column($membres,'absent_aujourd_hui'))?></div>
    <div class="kpi-label">Présent(s) aujourd'hui</div>
  </div>
</div>

<?php if(empty($membres)): ?>
<div class="card">
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-users-slash"></i></div>
    <h3>Aucun membre dans votre équipe</h3>
    <p>Aucun employé ne vous est rattaché en tant que manager.</p>
  </div>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px">
<?php foreach($membres as $m):
  $soldes=tousLesSoldes((int)$m['id'],$annee);
  $congeActif=null;
  $stmtC=$db->prepare("SELECT dc.*,tc.nom AS type_nom,tc.couleur FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id WHERE dc.utilisateur_id=? AND dc.statut='approuve_rh' AND dc.date_debut<=CURDATE() AND dc.date_fin>=CURDATE() LIMIT 1");
  $stmtC->execute([$m['id']]); $congeActif=$stmtC->fetch();
?>
<div class="card">
  <div class="card-body">
    <!-- En-tête membre -->
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
      <div class="user-avatar-sm" style="width:52px;height:52px;font-size:18px;background:<?=couleurAvatar((int)$m['id'])?>;flex-shrink:0">
        <?php if($m['photo']): ?>
        <img src="<?=APP_URL?>/assets/img/photos/<?=h($m['photo'])?>" alt="photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%"/>
        <?php else: ?><?=initialesAvatar($m['prenom'],$m['nom'])?><?php endif; ?>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:15px"><?=h($m['prenom'].' '.$m['nom'])?></div>
        <div style="font-size:12px;color:var(--outline)"><?=h($m['poste']??'—')?></div>
        <?php if($congeActif): ?>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:<?=h($congeActif['couleur'])?>22;color:<?=h($congeActif['couleur'])?>;margin-top:4px">
          <i class="fas fa-umbrella-beach"></i> En congé — <?=h($congeActif['type_nom'])?>
        </span>
        <?php else: ?>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:var(--success-container);color:var(--success);margin-top:4px">
          <i class="fas fa-circle" style="font-size:7px"></i> Présent
        </span>
        <?php endif; ?>
      </div>
      <div style="display:flex;flex-direction:column;gap:4px;text-align:right">
        <?php if($m['nb_attente']>0): ?>
        <span style="background:var(--warning-container);color:var(--warning);padding:3px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:700">
          <?=$m['nb_attente']?> en attente
        </span>
        <?php endif; ?>
        <a href="<?=APP_URL?>/fiche_employe.php?id=<?=$m['id']?>" class="btn btn-ghost btn-sm" style="font-size:11px">
          <i class="fas fa-eye"></i> Fiche
        </a>
      </div>
    </div>

    <!-- Soldes rapides -->
    <?php if(!empty($soldes)): ?>
    <div style="border-top:1px solid var(--surface-high);padding-top:12px;margin-top:4px">
      <div style="font-size:11px;font-weight:700;color:var(--outline);text-transform:uppercase;margin-bottom:10px">Soldes <?=$annee?></div>
      <?php foreach(array_slice($soldes,0,3) as $s):
        $pct=$s['jours_alloues']>0?min(100,round($s['jours_pris']/$s['jours_alloues']*100)):0; ?>
      <div class="solde-item" style="margin-bottom:10px">
        <div class="solde-header">
          <span class="solde-label">
            <span style="width:8px;height:8px;border-radius:50%;background:<?=h($s['couleur'])?>;display:inline-block"></span>
            <?=h($s['type_nom'])?>
          </span>
          <span class="solde-value" style="font-size:12px"><?=$s['jours_restants']?>/<?=$s['jours_alloues']?> j</span>
        </div>
        <div class="solde-bar"><div class="solde-fill" style="width:<?=$pct?>%;background:<?=h($s['couleur'])?>"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div style="display:flex;gap:8px;margin-top:12px;border-top:1px solid var(--surface-high);padding-top:12px">
      <a href="<?=APP_URL?>/approbation.php?emp=<?=$m['id']?>" class="btn btn-outline btn-sm" style="flex:1;text-align:center">
        <i class="fas fa-stamp"></i> Demandes
      </a>
      <a href="<?=APP_URL?>/fiche_employe.php?id=<?=$m['id']?>" class="btn btn-ghost btn-sm" style="flex:1;text-align:center">
        <i class="fas fa-id-card"></i> Fiche complète
      </a>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__.'/includes/footer.php'; ?>
