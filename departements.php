<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['admin']);
$user=utilisateurCourant(); $db=getDB();
$erreur=''; $success='';

if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $act=$_POST['action']??'';
    if($act==='sauvegarder') {
        $id=(int)($_POST['id']??0); $nom=sanitize($_POST['nom']??'');
        $desc=sanitize($_POST['description']??''); $respId=(int)($_POST['responsable_id']??0);
        if(!$nom){$erreur='Le nom est obligatoire.';}
        else {
            if($id) {
                $db->prepare("UPDATE departements SET nom=?,description=?,responsable_id=? WHERE id=?")->execute([$nom,$desc,$respId?:null,$id]);
                $success='Département modifié.';
            } else {
                $db->prepare("INSERT INTO departements (nom,description,responsable_id) VALUES (?,?,?)")->execute([$nom,$desc,$respId?:null]);
                $success='Département créé.';
            }
        }
    } elseif($act==='supprimer'&&$_POST['dept_id']) {
        $id=(int)$_POST['dept_id'];
        $chk=$db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE departement_id=?");$chk->execute([$id]);
        if($chk->fetchColumn()>0){$erreur='Impossible : des employés sont rattachés à ce département.';}
        else{$db->prepare("DELETE FROM departements WHERE id=?")->execute([$id]);$success='Département supprimé.';}
    }
}

$depts=$db->query("SELECT d.*,u.nom AS resp_nom,u.prenom AS resp_prenom,(SELECT COUNT(*) FROM utilisateurs WHERE departement_id=d.id AND actif=1) AS nb_employes FROM departements d LEFT JOIN utilisateurs u ON u.id=d.responsable_id ORDER BY d.nom")->fetchAll();
$managers=$db->query("SELECT id,nom,prenom FROM utilisateurs WHERE role IN ('manager','admin') AND actif=1 ORDER BY nom")->fetchAll();
$editData=null; if(isset($_GET['edit'])){$s=$db->prepare("SELECT * FROM departements WHERE id=?");$s->execute([(int)$_GET['edit']]);$editData=$s->fetch();}

$pageTitle='Départements'; $pageSubtitle='Gérer les unités organisationnelles';
require_once __DIR__.'/includes/header.php';
?>
<?php if($erreur): ?><div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?=h($erreur)?></span></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span><?=h($success)?></span></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
<div class="card">
  <div class="card-header"><div class="card-title">Liste des départements</div><div class="card-subtitle"><?=count($depts)?> département(s)</div></div>
  <?php if(empty($depts)): ?>
  <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-sitemap"></i></div><h3>Aucun département</h3></div>
  <?php else: ?>
  <div class="table-wrapper"><table>
    <thead><tr><th>Département</th><th>Responsable</th><th>Employés</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($depts as $d): ?>
      <tr>
        <td><div style="font-weight:600"><?=h($d['nom'])?></div><?php if($d['description']): ?><div style="font-size:12px;color:var(--on-surface-variant)"><?=h($d['description'])?></div><?php endif; ?></td>
        <td style="font-size:13px"><?=$d['resp_nom']?h($d['resp_prenom'].' '.$d['resp_nom']):'<span style="color:var(--outline)">—</span>'?></td>
        <td><span style="font-weight:700;color:var(--primary)"><?=$d['nb_employes']?></span></td>
        <td><div class="action-btns">
          <a href="?edit=<?=$d['id']?>" class="btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
          <form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
          <input type="hidden" name="action" value="supprimer"/><input type="hidden" name="dept_id" value="<?=$d['id']?>"/>
          <button type="submit" class="btn-icon refuse" title="Supprimer" onclick="return confirm('Supprimer ce département ?')"><i class="fas fa-trash"></i></button></form>
        </div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><?=$editData?'Modifier':'Nouveau département'?></div></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
      <input type="hidden" name="action" value="sauvegarder"/>
      <input type="hidden" name="id" value="<?=$editData?$editData['id']:0?>"/>
      <div class="form-group"><label class="form-label">Nom <span class="required">*</span></label>
      <input type="text" name="nom" class="form-control" value="<?=h($editData?$editData['nom']:'')?>" required/></div>
      <div class="form-group"><label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="2"><?=h($editData?$editData['description']:'')?></textarea></div>
      <div class="form-group"><label class="form-label">Responsable</label>
      <select name="responsable_id" class="form-control"><option value="">— Aucun —</option>
      <?php foreach($managers as $m): ?><option value="<?=$m['id']?>" <?=($editData&&$editData['responsable_id']==$m['id'])?'selected':''?>><?=h($m['prenom'].' '.$m['nom'])?></option><?php endforeach; ?>
      </select></div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <?php if($editData): ?><a href="<?=APP_URL?>/departements.php" class="btn btn-ghost">Annuler</a><?php endif; ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?=$editData?'Modifier':'Créer'?></button>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
