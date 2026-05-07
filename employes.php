<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['rh','admin']);
$user=utilisateurCourant(); $db=getDB();
$filtreDept=(int)($_GET['dept']??0); $filtreRole=$_GET['role']??''; $filtreActif=$_GET['actif']??'1';
$search=sanitize($_GET['q']??'');
$page=max(1,(int)($_GET['page']??1)); $perPage=15; $offset=($page-1)*$perPage;

// Actions POST
if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $act=$_POST['action']??'';
    $eid=(int)($_POST['employe_id']??0);
    if($act==='toggle_actif'&&$eid) {
        $s=$db->prepare("SELECT actif FROM utilisateurs WHERE id=?");$s->execute([$eid]);
        $cur=(int)$s->fetchColumn();
        $db->prepare("UPDATE utilisateurs SET actif=? WHERE id=?")->execute([($cur?0:1),$eid]);
        logActivite('employe_'.($cur?'desactive':'active'),'Employé #'.$eid.' '.($cur?'désactivé':'activé'),'utilisateurs',$eid);
    }
    header('Location:'.APP_URL.'/employes.php?'.http_build_query(['dept'=>$filtreDept,'role'=>$filtreRole,'actif'=>$filtreActif,'q'=>$search])); exit;
}

$where="WHERE 1=1"; $params=[];
if($filtreDept) {$where.=" AND u.departement_id=?";$params[]=$filtreDept;}
if($filtreRole) {$where.=" AND u.role=?";$params[]=$filtreRole;}
if($filtreActif!=='') {$where.=" AND u.actif=?";$params[]=(int)$filtreActif;}
if($search)     {$where.=" AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}

$stC=$db->prepare("SELECT COUNT(*) FROM utilisateurs u $where");$stC->execute($params);$total=(int)$stC->fetchColumn();
$pages=max(1,(int)ceil($total/$perPage));

$stmt=$db->prepare("SELECT u.*,d.nom AS departement FROM utilisateurs u LEFT JOIN departements d ON d.id=u.departement_id $where ORDER BY u.nom,u.prenom LIMIT $perPage OFFSET $offset");
$stmt->execute($params); $employes=$stmt->fetchAll();
$depts=$db->query("SELECT id,nom FROM departements ORDER BY nom")->fetchAll();

$pageTitle='Gestion des employés'; $pageSubtitle=$total.' employé(s) trouvé(s)';
require_once __DIR__.'/includes/header.php';
?>
<div class="card">
  <div class="card-header">
    <div><div class="card-title">Liste des employés</div><div class="card-subtitle"><?=$total?> résultat(s)</div></div>
    <a href="<?=APP_URL?>/utilisateurs.php?action=nouveau" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Ajouter</a>
  </div>
  <!-- Filtres -->
  <div style="padding:14px 24px;border-bottom:1px solid var(--surface-high)">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <div class="search-input-wrapper" style="min-width:220px"><i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="Rechercher..." value="<?=h($search)?>"/></div>
      <select name="dept" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Tous départements</option>
        <?php foreach($depts as $d): ?><option value="<?=$d['id']?>" <?=$filtreDept==$d['id']?'selected':''?>><?=h($d['nom'])?></option><?php endforeach; ?>
      </select>
      <select name="role" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Tous rôles</option>
        <?php foreach(['employe'=>'Employé','manager'=>'Manager','rh'=>'RH','admin'=>'Admin'] as $v=>$l): ?>
        <option value="<?=$v?>" <?=$filtreRole===$v?'selected':''?>><?=$l?></option>
        <?php endforeach; ?>
      </select>
      <select name="actif" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="1" <?=$filtreActif==='1'?'selected':''?>>Actifs</option>
        <option value="0" <?=$filtreActif==='0'?'selected':''?>>Inactifs</option>
        <option value="" <?=$filtreActif===''?'selected':''?>>Tous</option>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filtrer</button>
      <a href="<?=APP_URL?>/employes.php" class="btn btn-ghost btn-sm">Réinit.</a>
    </form>
  </div>

  <?php if(empty($employes)): ?>
  <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-users"></i></div><h3>Aucun employé trouvé</h3></div>
  <?php else: ?>
  <div class="table-wrapper"><table>
    <thead><tr><th>Employé</th><th>Email</th><th>Département</th><th>Poste</th><th>Rôle</th><th>Embauche</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($employes as $e): ?>
      <tr>
        <td><div class="user-cell">
          <div class="user-avatar-sm" style="background:<?=couleurAvatar((int)$e['id'])?>"><?=initialesAvatar($e['prenom'],$e['nom'])?></div>
          <div><div class="user-name"><?=h($e['prenom'].' '.$e['nom'])?></div></div>
        </div></td>
        <td style="font-size:12.5px"><?=h($e['email'])?></td>
        <td style="font-size:13px"><?=h($e['departement']??'—')?></td>
        <td style="font-size:13px"><?=h($e['poste']??'—')?></td>
        <td><span class="role-badge role-<?=h($e['role'])?>"><?=ucfirst($e['role'])?></span></td>
        <td style="font-size:12.5px"><?=$e['date_embauche']?formatDate($e['date_embauche'],'d/m/Y'):'—'?></td>
        <td>
          <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:<?=$e['actif']?'var(--success-container)':'var(--error-container)'?>;color:<?=$e['actif']?'var(--success)':'var(--error)'?>">
            <i class="fas <?=$e['actif']?'fa-circle-check':'fa-circle-xmark'?>"></i><?=$e['actif']?'Actif':'Inactif'?>
          </span>
        </td>
        <td><div class="action-btns">
          <a href="<?=APP_URL?>/fiche_employe.php?id=<?=$e['id']?>" class="btn-icon" title="Fiche"><i class="fas fa-eye"></i></a>
          <a href="<?=APP_URL?>/utilisateurs.php?action=modifier&id=<?=$e['id']?>" class="btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
          <form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
          <input type="hidden" name="action" value="toggle_actif"/><input type="hidden" name="employe_id" value="<?=$e['id']?>"/>
          <button type="submit" class="btn-icon <?=$e['actif']?'refuse':''?>" title="<?=$e['actif']?'Désactiver':'Activer'?>"
                  onclick="return confirm('<?=$e['actif']?'Désactiver':'Activer'?> cet employé ?')">
            <i class="fas <?=$e['actif']?'fa-user-slash':'fa-user-check'?>"></i></button></form>
        </div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if($pages>1): ?><div class="pagination"><?php for($i=1;$i<=$pages;$i++): ?>
  <a href="?page=<?=$i?>&dept=<?=$filtreDept?>&role=<?=urlencode($filtreRole)?>&actif=<?=$filtreActif?>&q=<?=urlencode($search)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
  <?php endfor; ?><span class="page-info"><?=$total?> employé(s)</span></div><?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
