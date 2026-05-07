<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['rh','admin']);
$user=utilisateurCourant(); $db=getDB();
$annee=(int)($_GET['annee']??date('Y'));
$empId=(int)($_GET['emp']??0);
$success=''; $erreur='';

if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $act=$_POST['action']??'';
    if($act==='sauvegarder_soldes') {
        $eid=(int)($_POST['employe_id']??0);
        $an=(int)($_POST['annee']??date('Y'));
        $alloues=$_POST['alloues']??[];
        foreach($alloues as $typeId=>$valeur) {
            $j=max(0,(float)$valeur);
            $chk=$db->prepare("SELECT id FROM soldes_conge WHERE utilisateur_id=? AND type_conge_id=? AND annee=?");
            $chk->execute([$eid,(int)$typeId,$an]);
            if($chk->fetchColumn()) {
                $db->prepare("UPDATE soldes_conge SET jours_alloues=? WHERE utilisateur_id=? AND type_conge_id=? AND annee=?")->execute([$j,$eid,(int)$typeId,$an]);
            } else {
                $db->prepare("INSERT INTO soldes_conge (utilisateur_id,type_conge_id,annee,jours_alloues,jours_pris) VALUES (?,?,?,?,0)")->execute([$eid,(int)$typeId,$an,$j]);
            }
        }
        logActivite('solde_modifie','Soldes employé #'.$eid.' modifiés pour '.$an,'utilisateurs',$eid);
        $success='Soldes mis à jour avec succès.';
        $empId=$eid; $annee=$an;
    }
}

$employes=$db->query("SELECT id,nom,prenom FROM utilisateurs WHERE actif=1 AND role='employe' ORDER BY nom,prenom")->fetchAll();
$types=$db->query("SELECT * FROM types_conge WHERE actif=1 ORDER BY nom")->fetchAll();
$soldesData=[];
if($empId) {
    foreach($types as $t) {
        $s=getSoldeUtilisateur($empId,$t['id'],$annee);
        $soldesData[$t['id']]=array_merge($t,$s);
    }
}

$pageTitle='Gestion des soldes'; $pageSubtitle='Attribuer les soldes de congés par employé';
require_once __DIR__.'/includes/header.php';
?>
<?php if($success): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span><?=h($success)?></span></div><?php endif; ?>
<?php if($erreur): ?><div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?=h($erreur)?></span></div><?php endif; ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start">

<!-- Sélection employé -->
<div class="card">
  <div class="card-header"><div class="card-title">Sélection</div></div>
  <div class="card-body">
    <form method="GET">
      <div class="form-group"><label class="form-label">Employé</label>
      <select name="emp" class="form-control" onchange="this.form.submit()">
        <option value="">-- Choisir --</option>
        <?php foreach($employes as $e): ?>
        <option value="<?=$e['id']?>" <?=$empId==$e['id']?'selected':''?>><?=h($e['prenom'].' '.$e['nom'])?></option>
        <?php endforeach; ?>
      </select></div>
      <div class="form-group"><label class="form-label">Année</label>
      <select name="annee" class="form-control" onchange="this.form.submit()">
        <?php for($a=date('Y')+1;$a>=2020;$a--): ?>
        <option value="<?=$a?>" <?=$annee==$a?'selected':''?>><?=$a?></option>
        <?php endfor; ?>
      </select></div>
    </form>
  </div>
</div>

<!-- Soldes -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title">Soldes de congés — <?=$annee?></div>
    <?php if($empId): $ep=$db->prepare("SELECT nom,prenom FROM utilisateurs WHERE id=?");$ep->execute([$empId]);$ep=$ep->fetch(); ?>
    <div class="card-subtitle"><?=h($ep['prenom'].' '.$ep['nom'])?></div><?php endif; ?></div>
  </div>
  <?php if(!$empId): ?>
  <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-hand-pointer"></i></div><h3>Sélectionnez un employé</h3><p>Choisissez un employé dans le panneau gauche pour gérer ses soldes.</p></div>
  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
    <input type="hidden" name="action" value="sauvegarder_soldes"/>
    <input type="hidden" name="employe_id" value="<?=$empId?>"/>
    <input type="hidden" name="annee" value="<?=$annee?>"/>
    <div class="table-wrapper"><table>
      <thead><tr><th>Type de congé</th><th>Jours alloués</th><th>Jours pris</th><th>Solde restant</th><th>Barre</th></tr></thead>
      <tbody>
        <?php foreach($soldesData as $tid=>$s): $pct=$s['jours_alloues']>0?min(100,round($s['jours_pris']/$s['jours_alloues']*100)):0; ?>
        <tr>
          <td><span style="display:flex;align-items:center;gap:8px"><span style="width:10px;height:10px;border-radius:50%;background:<?=h($s['couleur'])?>"></span><strong><?=h($s['nom'])?></strong></span></td>
          <td><input type="number" name="alloues[<?=$tid?>]" class="form-control" style="width:90px;border-radius:var(--radius-full);padding:6px 12px" min="0" max="365" step="0.5" value="<?=$s['jours_alloues']?>"/></td>
          <td style="color:var(--on-surface-variant)"><?=$s['jours_pris']?> j</td>
          <td><strong style="color:<?=$s['jours_restants']>0?'var(--primary)':'var(--error)'?>"><?=$s['jours_restants']?> j</strong></td>
          <td style="min-width:120px"><div class="solde-bar"><div class="solde-fill" style="width:<?=$pct?>%;background:<?=h($s['couleur'])?>"></div></div></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <div style="padding:16px 24px;display:flex;justify-content:flex-end">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les soldes</button>
    </div>
  </form>
  <?php endif; ?>
</div>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
