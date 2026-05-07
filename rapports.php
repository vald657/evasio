<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['rh','admin']);
$user=utilisateurCourant(); $db=getDB();

$filtreDept  = (int)($_GET['dept']??0);
$filtreEmp   = (int)($_GET['emp']??0);
$filtreType  = (int)($_GET['type']??0);
$filtreDebut = $_GET['debut']??date('Y-01-01');
$filtreFin   = $_GET['fin']  ??date('Y-m-d');
$filtreStatut= $_GET['statut']??'';

$where="WHERE dc.date_debut>=? AND dc.date_fin<=?"; $params=[$filtreDebut,$filtreFin];
if($filtreDept) {$where.=" AND u.departement_id=?";$params[]=$filtreDept;}
if($filtreEmp)  {$where.=" AND dc.utilisateur_id=?";$params[]=$filtreEmp;}
if($filtreType) {$where.=" AND dc.type_conge_id=?";$params[]=$filtreType;}
if($filtreStatut){$where.=" AND dc.statut=?";$params[]=$filtreStatut;}

// KPIs
$stK=$db->prepare("SELECT COUNT(*) AS total, SUM(dc.nombre_jours) AS jours, SUM(dc.statut='approuve_rh') AS approuves, SUM(dc.statut IN ('refuse_manager','refuse_rh')) AS refuses FROM demandes_conge dc JOIN utilisateurs u ON u.id=dc.utilisateur_id $where");
$stK->execute($params); $kpi=$stK->fetch();

// Données tableau
$stmt=$db->prepare("SELECT dc.*,tc.nom AS type_nom,tc.couleur,u.nom,u.prenom,u.id AS uid,d.nom AS departement FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id JOIN utilisateurs u ON u.id=dc.utilisateur_id LEFT JOIN departements d ON d.id=u.departement_id $where ORDER BY dc.date_debut DESC LIMIT 200");
$stmt->execute($params); $demandes=$stmt->fetchAll();

$depts=$db->query("SELECT id,nom FROM departements ORDER BY nom")->fetchAll();
$types=$db->query("SELECT id,nom FROM types_conge ORDER BY nom")->fetchAll();
$empListe=$db->query("SELECT id,nom,prenom FROM utilisateurs WHERE actif=1 ORDER BY nom,prenom")->fetchAll();

$taux=($kpi['total']>0)?round($kpi['approuves']/$kpi['total']*100):0;
$pageTitle='Rapports & Statistiques'; $pageSubtitle='Analyse des congés sur la période sélectionnée';
require_once __DIR__.'/includes/header.php';
?>
<!-- Filtres -->
<div class="card mb-24">
  <div class="card-header"><div class="card-title"><i class="fas fa-filter" style="color:var(--primary);margin-right:8px"></i>Filtres</div></div>
  <div class="card-body">
    <form method="GET">
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
        <div class="form-group"><label class="form-label">Département</label>
          <select name="dept" class="form-control"><option value="">Tous</option>
          <?php foreach($depts as $d): ?><option value="<?=$d['id']?>" <?=$filtreDept==$d['id']?'selected':''?>><?=h($d['nom'])?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Employé</label>
          <select name="emp" class="form-control"><option value="">Tous</option>
          <?php foreach($empListe as $e): ?><option value="<?=$e['id']?>" <?=$filtreEmp==$e['id']?'selected':''?>><?=h($e['prenom'].' '.$e['nom'])?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Type de congé</label>
          <select name="type" class="form-control"><option value="">Tous</option>
          <?php foreach($types as $t): ?><option value="<?=$t['id']?>" <?=$filtreType==$t['id']?'selected':''?>><?=h($t['nom'])?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Date début</label><input type="date" name="debut" class="form-control" value="<?=h($filtreDebut)?>"/></div>
        <div class="form-group"><label class="form-label">Date fin</label><input type="date" name="fin" class="form-control" value="<?=h($filtreFin)?>"/></div>
        <div class="form-group"><label class="form-label">Statut</label>
          <select name="statut" class="form-control"><option value="">Tous</option>
          <?php foreach(['en_attente'=>'En attente','approuve_manager'=>'Validé Manager','approuve_rh'=>'Approuvé','refuse_manager'=>'Refusé Manager','refuse_rh'=>'Refusé RH','annule'=>'Annulé'] as $v=>$l): ?>
          <option value="<?=$v?>" <?=$filtreStatut===$v?'selected':''?>><?=$l?></option><?php endforeach; ?></select></div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px">
        <a href="<?=APP_URL?>/rapports.php" class="btn btn-ghost btn-sm">Réinit.</a>
        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Appliquer</button>
      </div>
    </form>
  </div>
</div>

<!-- KPIs -->
<div class="kpi-grid mb-24">
  <div class="kpi-card"><div class="kpi-header"><div class="kpi-icon indigo"><i class="fas fa-file-alt"></i></div></div><div class="kpi-value"><?=$kpi['total']?></div><div class="kpi-label">Total demandes</div></div>
  <div class="kpi-card"><div class="kpi-header"><div class="kpi-icon green"><i class="fas fa-check-circle"></i></div></div><div class="kpi-value"><?=$kpi['approuves']?></div><div class="kpi-label">Approuvées (<?=$taux?>%)</div></div>
  <div class="kpi-card"><div class="kpi-header"><div class="kpi-icon red"><i class="fas fa-times-circle"></i></div></div><div class="kpi-value"><?=$kpi['refuses']?></div><div class="kpi-label">Refusées</div></div>
  <div class="kpi-card"><div class="kpi-header"><div class="kpi-icon orange"><i class="fas fa-calendar-day"></i></div></div><div class="kpi-value"><?=number_format((float)($kpi['jours']??0),1)?></div><div class="kpi-label">Jours consommés</div></div>
</div>

<!-- Tableau + exports -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title">Détail des demandes</div><div class="card-subtitle"><?=count($demandes)?> résultat(s)</div></div>
    <div style="display:flex;gap:8px">
      <a href="<?=APP_URL?>/api/export.php?format=excel&<?=http_build_query(['dept'=>$filtreDept,'emp'=>$filtreEmp,'type'=>$filtreType,'debut'=>$filtreDebut,'fin'=>$filtreFin,'statut'=>$filtreStatut])?>" class="btn btn-ghost btn-sm"><i class="fas fa-file-excel" style="color:#1d6f42"></i> Excel</a>
      <a href="<?=APP_URL?>/api/export.php?format=pdf&<?=http_build_query(['dept'=>$filtreDept,'emp'=>$filtreEmp,'type'=>$filtreType,'debut'=>$filtreDebut,'fin'=>$filtreFin,'statut'=>$filtreStatut])?>" class="btn btn-ghost btn-sm"><i class="fas fa-file-pdf" style="color:#e53935"></i> PDF</a>
    </div>
  </div>
  <?php if(empty($demandes)): ?>
  <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-chart-bar"></i></div><h3>Aucune donnée</h3><p>Modifiez les filtres pour afficher des résultats.</p></div>
  <?php else: ?>
  <div class="table-wrapper"><table>
    <thead><tr><th>Référence</th><th>Employé</th><th>Département</th><th>Type</th><th>Début</th><th>Fin</th><th>Jours</th><th>Statut</th></tr></thead>
    <tbody>
      <?php foreach($demandes as $d): ?>
      <tr>
        <td><span style="font-family:monospace;font-size:12px;color:var(--primary)"><?=h($d['reference'])?></span></td>
        <td><div class="user-cell"><div class="user-avatar-sm" style="background:<?=couleurAvatar((int)$d['uid'])?>"><?=initialesAvatar($d['prenom'],$d['nom'])?></div><div class="user-name"><?=h($d['prenom'].' '.$d['nom'])?></div></div></td>
        <td style="font-size:12.5px"><?=h($d['departement']??'—')?></td>
        <td><span style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:50%;background:<?=h($d['couleur'])?>"></span><?=h($d['type_nom'])?></span></td>
        <td style="font-size:12.5px"><?=formatDate($d['date_debut'])?></td>
        <td style="font-size:12.5px"><?=formatDate($d['date_fin'])?></td>
        <td><strong><?=$d['nombre_jours']?></strong></td>
        <td><span class="badge <?=classeStatut($d['statut'])?>"><i class="fas <?=iconeStatut($d['statut'])?>"></i> <?=labelStatut($d['statut'])?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
