<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['rh','admin','manager']);
$user=utilisateurCourant(); $db=getDB();
$id=(int)($_GET['id']??0);
if(!$id){header('Location:'.APP_URL.'/employes.php');exit;}

$stmt=$db->prepare("SELECT u.*,d.nom AS departement,m.nom AS manager_nom,m.prenom AS manager_prenom FROM utilisateurs u LEFT JOIN departements d ON d.id=u.departement_id LEFT JOIN utilisateurs m ON m.id=u.manager_id WHERE u.id=?");
$stmt->execute([$id]); $employe=$stmt->fetch();
if(!$employe){header('Location:'.APP_URL.'/employes.php');exit;}

// Un manager ne peut voir que les fiches de son équipe
if($user['role']==='manager' && (int)$employe['manager_id']!==(int)$user['id']){
    header('Location:'.APP_URL.'/mon_equipe.php');exit;
}

$annee=(int)($_GET['annee']??date('Y'));
$soldes=tousLesSoldes($id,$annee);

$page=max(1,(int)($_GET['page']??1)); $perPage=10; $offset=($page-1)*$perPage;
$stC=$db->prepare("SELECT COUNT(*) FROM demandes_conge WHERE utilisateur_id=?");$stC->execute([$id]);$total=(int)$stC->fetchColumn();
$pages=max(1,(int)ceil($total/$perPage));
$stmt2=$db->prepare("SELECT dc.*,tc.nom AS type_nom,tc.couleur FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id WHERE dc.utilisateur_id=? ORDER BY dc.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt2->execute([$id]); $demandes=$stmt2->fetchAll();

$pageTitle=h($employe['prenom'].' '.$employe['nom']); $pageSubtitle='Fiche employé — '.h($employe['departement']??'');
require_once __DIR__.'/includes/header.php';
?>
<div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start">

<!-- Fiche identité -->
<div style="display:flex;flex-direction:column;gap:16px">
  <div class="card">
    <div class="card-body" style="text-align:center">
      <div class="user-avatar-sm" style="width:72px;height:72px;font-size:26px;background:<?=couleurAvatar($id)?>;margin:0 auto 14px">
        <?=initialesAvatar($employe['prenom'],$employe['nom'])?>
      </div>
      <div style="font-family:var(--font-title);font-size:17px;font-weight:700"><?=h($employe['prenom'].' '.$employe['nom'])?></div>
      <div style="color:var(--on-surface-variant);font-size:13px;margin-top:4px"><?=h($employe['poste']??'—')?></div>
      <span class="role-badge role-<?=h($employe['role'])?>" style="margin-top:10px;display:inline-block"><?=ucfirst($employe['role'])?></span>
    </div>
    <div class="card-footer" style="font-size:13px">
      <?php $infos=[
        ['fa-building','Département',$employe['departement']??'—'],
        ['fa-user-tie','Manager',$employe['manager_nom']?h($employe['manager_prenom'].' '.$employe['manager_nom']):'—'],
        ['fa-envelope','Email',$employe['email']],
        ['fa-phone','Téléphone',$employe['telephone']??'—'],
        ['fa-calendar-day','Embauche',$employe['date_embauche']?formatDate($employe['date_embauche'],'d/m/Y'):'—'],
        ['fa-clock','Dernière connexion',$employe['derniere_connexion']?(new DateTime($employe['derniere_connexion']))->format('d/m/Y H:i'):'Jamais'],
      ]; foreach($infos as $inf): ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <i class="fas <?=$inf[0]?>" style="width:16px;color:var(--primary)"></i>
        <div><div style="font-size:10px;color:var(--outline);text-transform:uppercase;font-weight:700"><?=$inf[1]?></div>
        <div style="font-weight:500"><?=$inf[2]?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <a href="<?=APP_URL?>/utilisateurs.php?action=modifier&id=<?=$id?>" class="btn btn-outline btn-block"><i class="fas fa-edit"></i> Modifier</a>
  <a href="<?=APP_URL?>/gestion_soldes.php?emp=<?=$id?>" class="btn btn-secondary btn-block"><i class="fas fa-wallet"></i> Gérer les soldes</a>
  <a href="<?=APP_URL?>/employes.php" class="btn btn-ghost btn-block"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<!-- Contenu principal -->
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- Soldes -->
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Soldes de congés</div><div class="card-subtitle">Exercice <?=$annee?></div></div>
      <form method="GET" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="id" value="<?=$id?>"/>
        <select name="annee" class="form-control" style="width:auto;padding:7px 14px;border-radius:var(--radius-full)" onchange="this.form.submit()">
          <?php for($a=date('Y')+1;$a>=2020;$a--): ?><option value="<?=$a?>" <?=$annee==$a?'selected':''?>><?=$a?></option><?php endfor; ?>
        </select>
      </form>
    </div>
    <div class="card-body">
      <?php if(empty($soldes)): ?>
      <p style="color:var(--on-surface-variant)">Aucun solde configuré pour <?=$annee?>.</p>
      <?php else: foreach($soldes as $s): $pct=$s['jours_alloues']>0?min(100,round($s['jours_pris']/$s['jours_alloues']*100)):0; ?>
      <div class="solde-item">
        <div class="solde-header">
          <span class="solde-label"><span style="width:10px;height:10px;border-radius:50%;background:<?=h($s['couleur'])?>"></span><?=h($s['type_nom'])?></span>
          <span class="solde-value"><?=$s['jours_restants']?> / <?=$s['jours_alloues']?> j</span>
        </div>
        <div class="solde-bar"><div class="solde-fill" style="width:<?=$pct?>%;background:<?=h($s['couleur'])?>"></div></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Historique demandes -->
  <div class="card">
    <div class="card-header"><div class="card-title">Historique des demandes</div><div class="card-subtitle"><?=$total?> demande(s)</div></div>
    <?php if(empty($demandes)): ?>
    <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-inbox"></i></div><h3>Aucune demande</h3></div>
    <?php else: ?>
    <div class="table-wrapper"><table>
      <thead><tr><th>Référence</th><th>Type</th><th>Période</th><th>Durée</th><th>Statut</th></tr></thead>
      <tbody>
        <?php foreach($demandes as $d): ?>
        <tr>
          <td><a href="<?=APP_URL?>/detail_demande.php?id=<?=$d['id']?>" style="color:var(--primary);font-family:monospace;font-size:12px;font-weight:600"><?=h($d['reference'])?></a></td>
          <td><span style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:50%;background:<?=h($d['couleur'])?>"></span><?=h($d['type_nom'])?></span></td>
          <td style="font-size:12.5px"><?=formatDate($d['date_debut'])?> – <?=formatDate($d['date_fin'])?></td>
          <td><?=$d['nombre_jours']?> j</td>
          <td><span class="badge <?=classeStatut($d['statut'])?>"><i class="fas <?=iconeStatut($d['statut'])?>"></i> <?=labelStatut($d['statut'])?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php if($pages>1): ?><div class="pagination"><?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?id=<?=$id?>&annee=<?=$annee?>&page=<?=$i?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?><span class="page-info"><?=$total?> demande(s)</span></div><?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
