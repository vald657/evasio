<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['admin']);
$user=utilisateurCourant(); $db=getDB();
$filtreAction=$_GET['action_filtre']??'';
$filtreEmp=(int)($_GET['emp']??0);
$filtreDate=$_GET['date']??'';
$page=max(1,(int)($_GET['page']??1)); $perPage=20; $offset=($page-1)*$perPage;

$where="WHERE 1=1"; $params=[];
if($filtreAction){$where.=" AND la.action=?";$params[]=$filtreAction;}
if($filtreEmp)   {$where.=" AND la.utilisateur_id=?";$params[]=$filtreEmp;}
if($filtreDate)  {$where.=" AND DATE(la.created_at)=?";$params[]=$filtreDate;}

$stC=$db->prepare("SELECT COUNT(*) FROM logs_activite la $where");$stC->execute($params);$total=(int)$stC->fetchColumn();
$pages=max(1,(int)ceil($total/$perPage));
$stmt=$db->prepare("SELECT la.*,u.nom,u.prenom FROM logs_activite la LEFT JOIN utilisateurs u ON u.id=la.utilisateur_id $where ORDER BY la.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params); $logs=$stmt->fetchAll();

$actions=$db->query("SELECT DISTINCT action FROM logs_activite ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$empListe=$db->query("SELECT id,nom,prenom FROM utilisateurs ORDER BY nom")->fetchAll();

$pageTitle="Journal d'activité"; $pageSubtitle='Historique complet des actions système';
require_once __DIR__.'/includes/header.php';
?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-scroll" style="color:var(--primary);margin-right:8px"></i>Journal d'activité</div>
  <div class="card-subtitle"><?=$total?> entrée(s)</div></div>
  <!-- Filtres -->
  <div style="padding:14px 24px;border-bottom:1px solid var(--surface-high)">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select name="action_filtre" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Toutes actions</option>
        <?php foreach($actions as $a): ?><option value="<?=h($a)?>" <?=$filtreAction===$a?'selected':''?>><?=h(str_replace('_',' ',$a))?></option><?php endforeach; ?>
      </select>
      <select name="emp" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Tous utilisateurs</option>
        <?php foreach($empListe as $e): ?><option value="<?=$e['id']?>" <?=$filtreEmp==$e['id']?'selected':''?>><?=h($e['prenom'].' '.$e['nom'])?></option><?php endforeach; ?>
      </select>
      <input type="date" name="date" class="form-control" style="width:auto;border-radius:var(--radius-full);padding:8px 16px" value="<?=h($filtreDate)?>"/>
      <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filtrer</button>
      <a href="<?=APP_URL?>/logs.php" class="btn btn-ghost btn-sm">Réinit.</a>
    </form>
  </div>
  <?php if(empty($logs)): ?>
  <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-scroll"></i></div><h3>Aucune entrée</h3></div>
  <?php else: ?>
  <div class="table-wrapper"><table>
    <thead><tr><th>Date / Heure</th><th>Utilisateur</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
    <tbody>
      <?php foreach($logs as $l): ?>
      <tr>
        <td style="font-size:12px;white-space:nowrap"><?=(new DateTime($l['created_at']))->format('d/m/Y H:i:s')?></td>
        <td style="font-size:13px"><?=$l['prenom']?h($l['prenom'].' '.$l['nom']):'<span style="color:var(--outline)">Système</span>'?></td>
        <td><span style="font-family:monospace;font-size:11px;background:var(--surface-container);padding:3px 8px;border-radius:var(--radius-sm)"><?=h($l['action'])?></span></td>
        <td style="font-size:13px"><?=h($l['description']??'—')?></td>
        <td style="font-size:12px;color:var(--on-surface-variant)"><?=h($l['ip_address']??'—')?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if($pages>1): ?><div class="pagination"><?php for($i=1;$i<=$pages;$i++): ?>
  <a href="?page=<?=$i?>&action_filtre=<?=urlencode($filtreAction)?>&emp=<?=$filtreEmp?>&date=<?=urlencode($filtreDate)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
  <?php endfor; ?><span class="page-info"><?=$total?> entrée(s)</span></div><?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
