<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['manager','rh','admin']);
$user=utilisateurCourant(); $db=getDB(); $role=$user['role'];

$filtreDept=(int)($_GET['dept']??0);
$filtreType=(int)($_GET['type']??0);
$page=max(1,(int)($_GET['page']??1)); $perPage=12; $offset=($page-1)*$perPage;

if($role==='manager') {
    $where="WHERE u.manager_id=? AND dc.statut='en_attente'"; $params=[$user['id']];
} elseif($role==='admin') {
    $where="WHERE dc.statut IN ('en_attente','approuve_manager')"; $params=[];
} else {
    // RH : uniquement les demandes validées par le manager
    $where="WHERE dc.statut='approuve_manager'"; $params=[];
}
if($filtreDept){$where.=" AND u.departement_id=?";$params[]=$filtreDept;}
if($filtreType) {$where.=" AND dc.type_conge_id=?";$params[]=$filtreType;}

$stC=$db->prepare("SELECT COUNT(*) FROM demandes_conge dc JOIN utilisateurs u ON u.id=dc.utilisateur_id $where");
$stC->execute($params); $total=(int)$stC->fetchColumn();
$pages=max(1,(int)ceil($total/$perPage));

$stmt=$db->prepare("
    SELECT dc.*,tc.nom AS type_nom,tc.couleur,
           u.nom,u.prenom,u.photo,u.id AS uid,
           d.nom AS departement
    FROM demandes_conge dc
    JOIN types_conge tc ON tc.id=dc.type_conge_id
    JOIN utilisateurs u ON u.id=dc.utilisateur_id
    LEFT JOIN departements d ON d.id=u.departement_id
    $where ORDER BY dc.created_at ASC LIMIT $perPage OFFSET $offset
");
$stmt->execute($params); $demandes=$stmt->fetchAll();
$depts=$db->query("SELECT id,nom FROM departements ORDER BY nom")->fetchAll();
$types=$db->query("SELECT id,nom FROM types_conge ORDER BY nom")->fetchAll();

$pageTitle='Approbations'; $pageSubtitle=($role==='manager')?'Demandes en attente de votre équipe':'Demandes validées par les managers';
require_once __DIR__.'/includes/header.php';
?>
<div class="card mb-24" style="background:linear-gradient(135deg,var(--primary),var(--primary-container));border:none">
  <div class="card-body" style="display:flex;gap:24px;align-items:center">
    <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:24px;color:white"><i class="fas fa-stamp"></i></div>
    <div>
      <div style="font-family:var(--font-title);font-size:20px;font-weight:700;color:white"><?=$total?> demande(s) en attente</div>
      <div style="color:rgba(255,255,255,.8);font-size:13.5px">
      <?php if($role==='manager'): ?>Niveau 1 — Votre validation est requise
      <?php elseif($role==='admin'): ?>Tous niveaux — Vue administrateur
      <?php else: ?>Niveau 2 — Approbation finale RH<?php endif; ?>
    </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div><div class="card-title">Liste des demandes</div></div>
  </div>
  <!-- Filtres -->
  <div style="padding:14px 24px;border-bottom:1px solid var(--surface-high)">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select name="dept" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Tous départements</option>
        <?php foreach($depts as $d): ?><option value="<?=$d['id']?>" <?=$filtreDept==$d['id']?'selected':''?>><?=h($d['nom'])?></option><?php endforeach; ?>
      </select>
      <select name="type" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Tous types</option>
        <?php foreach($types as $t): ?><option value="<?=$t['id']?>" <?=$filtreType==$t['id']?'selected':''?>><?=h($t['nom'])?></option><?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filtrer</button>
      <a href="<?=APP_URL?>/approbation.php" class="btn btn-ghost btn-sm">Réinit.</a>
    </form>
  </div>

  <?php if(empty($demandes)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-check-double"></i></div>
    <h3>Tout est traité !</h3><p>Aucune demande en attente.</p>
  </div>
  <?php else: ?>
  <div class="table-wrapper"><table>
    <thead><tr><th>Employé</th><th>Département</th><th>Type</th><th>Période</th><th>Durée</th><th>Soumise le</th><?php if($role==='admin'): ?><th>Niveau</th><?php endif; ?><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($demandes as $d): ?>
      <tr>
        <td><div class="user-cell">
          <div class="user-avatar-sm" style="background:<?=couleurAvatar((int)$d['uid'])?>"><?=initialesAvatar($d['prenom'],$d['nom'])?></div>
          <div><div class="user-name"><?=h($d['prenom'].' '.$d['nom'])?></div></div>
        </div></td>
        <td style="font-size:13px;color:var(--on-surface-variant)"><?=h($d['departement']??'—')?></td>
        <td><span style="display:flex;align-items:center;gap:7px"><span style="width:9px;height:9px;border-radius:50%;background:<?=h($d['couleur'])?>"></span><?=h($d['type_nom'])?></span></td>
        <td style="font-size:12.5px"><?=formatDate($d['date_debut'])?> – <?=formatDate($d['date_fin'])?></td>
        <td><strong><?=$d['nombre_jours']?></strong> j</td>
        <td style="font-size:12px;color:var(--on-surface-variant)"><?=formatDate($d['created_at'],'d/m/Y')?></td>
        <?php if($role==='admin'): ?>
        <td>
          <?php if($d['statut']==='en_attente'): ?>
          <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:var(--radius-full);background:var(--warning-container);color:var(--warning)">Niv. Manager</span>
          <?php else: ?>
          <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:var(--radius-full);background:var(--info-container);color:var(--primary)">Niv. RH</span>
          <?php endif; ?>
        </td>
        <?php endif; ?>
        <td><div class="action-btns">
          <button class="btn-icon approve" title="Approuver" onclick="approuver(<?=$d['id']?>)"><i class="fas fa-check"></i></button>
          <button class="btn-icon refuse"  title="Refuser"   onclick="ouvrirRefus(<?=$d['id']?>)"><i class="fas fa-times"></i></button>
          <a href="<?=APP_URL?>/detail_demande.php?id=<?=$d['id']?>" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
        </div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if($pages>1): ?><div class="pagination"><?php for($i=1;$i<=$pages;$i++): ?>
  <a href="?page=<?=$i?>&dept=<?=$filtreDept?>&type=<?=$filtreType?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
  <?php endfor; ?><span class="page-info"><?=$total?> demande(s)</span></div><?php endif; ?>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="modalRefus">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><span class="modal-title"><i class="fas fa-times-circle" style="color:var(--error);margin-right:8px"></i>Refuser la demande</span>
    <button class="modal-close" onclick="fermerModal('modalRefus')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body"><input type="hidden" id="refusId"/>
      <div class="form-group"><label class="form-label">Motif du refus <span class="required">*</span></label>
      <textarea id="refusCom" class="form-control" rows="4" placeholder="Raison du refus..."></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="fermerModal('modalRefus')">Annuler</button>
      <button class="btn btn-danger" onclick="confirmerRefus()"><i class="fas fa-times"></i> Refuser</button>
    </div>
  </div>
</div>
<script>
const csrf='<?=csrfToken()?>';
function approuver(id){
  if(!confirm('Approuver cette demande ?'))return;
  fetch('<?=APP_URL?>/api/demandes.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=approuver&id='+id+'&csrf_token='+csrf})
  .then(r=>r.json()).then(d=>{showToast(d.success?'success':'error',d.message);if(d.success)setTimeout(()=>location.reload(),1200);});
}
function ouvrirRefus(id){document.getElementById('refusId').value=id;document.getElementById('refusCom').value='';document.getElementById('modalRefus').classList.add('active');}
function confirmerRefus(){
  const id=document.getElementById('refusId').value,com=document.getElementById('refusCom').value.trim();
  if(!com){showToast('error','Veuillez saisir un motif.');return;}
  fetch('<?=APP_URL?>/api/demandes.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=refuser&id='+id+'&commentaire='+encodeURIComponent(com)+'&csrf_token='+csrf})
  .then(r=>r.json()).then(d=>{showToast(d.success?'success':'error',d.message);if(d.success){fermerModal('modalRefus');setTimeout(()=>location.reload(),1200);}});
}
</script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
