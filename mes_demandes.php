<?php
require_once __DIR__.'/includes/functions.php';
requireAuth();
$user=utilisateurCourant(); $db=getDB();
$filtreStatut=$_GET['statut']??''; $filtreType=(int)($_GET['type']??0);
$filtreAnnee=(int)($_GET['annee']??date('Y')); $page=max(1,(int)($_GET['page']??1));
$perPage=10; $offset=($page-1)*$perPage;

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='annuler'&&verifyCsrf($_POST['csrf_token']??'')) {
    $did=(int)($_POST['demande_id']??0);
    $s=$db->prepare("UPDATE demandes_conge SET statut='annule' WHERE id=? AND utilisateur_id=? AND statut='en_attente'");
    $s->execute([$did,$user['id']]);
    if($s->rowCount()>0){logActivite('demande_annulee','Demande #'.$did.' annulée','demandes_conge',$did);}
    header('Location:'.APP_URL.'/mes_demandes.php?cancelled=1');exit;
}

$where="WHERE dc.utilisateur_id=?"; $params=[$user['id']];
if($filtreStatut){$where.=" AND dc.statut=?";$params[]=$filtreStatut;}
if($filtreType)  {$where.=" AND dc.type_conge_id=?";$params[]=$filtreType;}
if($filtreAnnee) {$where.=" AND YEAR(dc.date_debut)=?";$params[]=$filtreAnnee;}

$stC=$db->prepare("SELECT COUNT(*) FROM demandes_conge dc $where");$stC->execute($params);$total=(int)$stC->fetchColumn();
$pages=max(1,(int)ceil($total/$perPage));
$stmt=$db->prepare("SELECT dc.*,tc.nom AS type_nom,tc.couleur FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id $where ORDER BY dc.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params); $demandes=$stmt->fetchAll();
$types=$db->query("SELECT id,nom FROM types_conge ORDER BY nom")->fetchAll();
$stA=$db->prepare("SELECT DISTINCT YEAR(date_debut) AS a FROM demandes_conge WHERE utilisateur_id=? ORDER BY a DESC");
$stA->execute([$user['id']]); $anneesList=$stA->fetchAll(PDO::FETCH_COLUMN);

$pageTitle='Mes demandes'; $pageSubtitle='Historique de toutes vos demandes';
require_once __DIR__.'/includes/header.php';
?>
<?php if(isset($_GET['success'])): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span>Demande soumise avec succès !</span></div><?php endif; ?>
<?php if(isset($_GET['cancelled'])): ?><div class="alert alert-warning mb-16"><i class="fas fa-ban"></i><span>Demande annulée.</span></div><?php endif; ?>
<div class="card">
  <div class="card-header">
    <div><div class="card-title">Mes demandes de congé</div><div class="card-subtitle"><?=$total?> demande(s)</div></div>
    <a href="<?=APP_URL?>/demande_conge.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nouvelle</a>
  </div>
  <div style="padding:14px 24px;border-bottom:1px solid var(--surface-high)">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select name="statut" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Tous statuts</option>
        <?php foreach(['en_attente'=>'En attente','approuve_manager'=>'Validé Manager','approuve_rh'=>'Approuvé','refuse_manager'=>'Refusé (Manager)','refuse_rh'=>'Refusé (RH)','annule'=>'Annulé'] as $v=>$l): ?>
        <option value="<?=$v?>" <?=$filtreStatut===$v?'selected':''?>><?=$l?></option>
        <?php endforeach; ?>
      </select>
      <select name="type" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <option value="">Tous types</option>
        <?php foreach($types as $t): ?><option value="<?=$t['id']?>" <?=$filtreType==$t['id']?'selected':''?>><?=h($t['nom'])?></option><?php endforeach; ?>
      </select>
      <select name="annee" class="form-control" style="width:auto;padding:8px 16px;border-radius:var(--radius-full)">
        <?php $al=array_unique(array_merge($anneesList,[date('Y')])); rsort($al); foreach($al as $a): ?>
        <option value="<?=$a?>" <?=$filtreAnnee==$a?'selected':''?>><?=$a?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filtrer</button>
      <a href="<?=APP_URL?>/mes_demandes.php" class="btn btn-ghost btn-sm">Réinit.</a>
    </form>
  </div>
  <?php if(empty($demandes)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
    <h3>Aucune demande</h3><p>Aucune demande ne correspond aux filtres.</p>
    <a href="<?=APP_URL?>/demande_conge.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nouvelle demande</a>
  </div>
  <?php else: ?>
  <div class="table-wrapper"><table>
    <thead><tr><th>Référence</th><th>Type</th><th>Période</th><th>Durée</th><th>Statut</th><th>Soumise le</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($demandes as $d): ?>
      <tr>
        <td><span style="font-family:monospace;font-size:12px;color:var(--primary);font-weight:600"><?=h($d['reference'])?></span></td>
        <td><span style="display:flex;align-items:center;gap:7px"><span style="width:9px;height:9px;border-radius:50%;background:<?=h($d['couleur'])?>"></span><?=h($d['type_nom'])?></span></td>
        <td style="font-size:12.5px"><?=formatDate($d['date_debut'])?> – <?=formatDate($d['date_fin'])?></td>
        <td><strong><?=$d['nombre_jours']?></strong> j</td>
        <td><span class="badge <?=classeStatut($d['statut'])?>"><i class="fas <?=iconeStatut($d['statut'])?>"></i> <?=labelStatut($d['statut'])?></span></td>
        <td style="font-size:12px;color:var(--on-surface-variant)"><?=formatDate($d['created_at'],'d/m/Y')?></td>
        <td><div class="action-btns">
          <a href="<?=APP_URL?>/detail_demande.php?id=<?=$d['id']?>" class="btn-icon" title="Détail"><i class="fas fa-eye"></i></a>
          <?php if($d['statut']==='en_attente'): ?>
          <button class="btn-icon refuse" title="Annuler" onclick="annulerDemande(<?=$d['id']?>)"><i class="fas fa-ban"></i></button>
          <?php endif; ?>
        </div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if($pages>1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?=$i?>&statut=<?=urlencode($filtreStatut)?>&type=<?=$filtreType?>&annee=<?=$filtreAnnee?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
    <span class="page-info"><?=$total?> résultat(s)</span>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="modalAnnuler">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><span class="modal-title"><i class="fas fa-ban" style="color:var(--error);margin-right:8px"></i>Annuler la demande</span>
    <button class="modal-close" onclick="fermerModal('modalAnnuler')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body"><p>Êtes-vous sûr de vouloir annuler cette demande ? Cette action est irréversible.</p></div>
    <div class="modal-footer">
      <form method="POST"><input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
      <input type="hidden" name="action" value="annuler"/><input type="hidden" name="demande_id" id="annulerId"/>
      <button type="button" class="btn btn-ghost" onclick="fermerModal('modalAnnuler')">Retour</button>
      <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Confirmer</button></form>
    </div>
  </div>
</div>
<script>function annulerDemande(id){document.getElementById('annulerId').value=id;document.getElementById('modalAnnuler').classList.add('active');}</script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
