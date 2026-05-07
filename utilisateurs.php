<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['admin']);
$user=utilisateurCourant(); $db=getDB();
$erreur=''; $success='';
$action=$_GET['action']??'liste';
$editId=(int)($_GET['id']??0);

if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $act=$_POST['action']??'';
    if($act==='toggle_actif') {
        $tid=(int)($_POST['id']??0);
        if($tid && $tid!==$user['id']) { // empêcher de se désactiver soi-même
            $cur=$db->prepare("SELECT actif FROM utilisateurs WHERE id=?");$cur->execute([$tid]);$curActif=(int)$cur->fetchColumn();
            $db->prepare("UPDATE utilisateurs SET actif=? WHERE id=?")->execute([$curActif?0:1,$tid]);
            $success=$curActif?'Compte désactivé.':'Compte réactivé.';
        }
    } elseif($act==='supprimer') {
        $tid=(int)($_POST['id']??0);
        if($tid && $tid!==$user['id']) {
            $db->prepare("DELETE FROM utilisateurs WHERE id=?")->execute([$tid]);
            logActivite('utilisateur_supprime','Utilisateur #'.$tid.' supprimé','utilisateurs',$tid);
            $success='Utilisateur supprimé.';
        }
    } elseif($act==='sauvegarder') {
        $id=(int)($_POST['id']??0);
        $nom=sanitize($_POST['nom']??''); $prenom=sanitize($_POST['prenom']??'');
        $email=sanitize($_POST['email']??''); $role=sanitize($_POST['role']??'employe');
        $deptId=(int)($_POST['departement_id']??0); $managerId=(int)($_POST['manager_id']??0);
        $poste=sanitize($_POST['poste']??''); $embauche=sanitize($_POST['date_embauche']??'');
        if(!$nom||!$prenom||!$email){$erreur='Nom, prénom et email sont obligatoires.';}
        else {
            $chk=$db->prepare("SELECT id FROM utilisateurs WHERE email=? AND id!=?");$chk->execute([$email,$id]);
            if($chk->fetchColumn()){$erreur='Cet email est déjà utilisé.';}
            else {
                if($id) {
                    $db->prepare("UPDATE utilisateurs SET nom=?,prenom=?,email=?,role=?,departement_id=?,manager_id=?,poste=?,date_embauche=? WHERE id=?")
                       ->execute([$nom,$prenom,$email,$role,$deptId?:null,$managerId?:null,$poste?:null,$embauche?:null,$id]);
                    if($_POST['nouveau_mdp']??'') {
                        $db->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?")->execute([password_hash($_POST['nouveau_mdp'],PASSWORD_DEFAULT),$id]);
                    }
                    $success='Utilisateur modifié.';
                } else {
                    $mdp=password_hash($_POST['mot_de_passe']??'Evasio@2026',PASSWORD_DEFAULT);
                    $db->prepare("INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,role,departement_id,manager_id,poste,date_embauche) VALUES (?,?,?,?,?,?,?,?,?)")
                       ->execute([$nom,$prenom,$email,$mdp,$role,$deptId?:null,$managerId?:null,$poste?:null,$embauche?:null]);
                    $nid=(int)$db->lastInsertId();
                    // Créer soldes de base
                    $tps=$db->query("SELECT id,jours_max_annuel FROM types_conge WHERE actif=1")->fetchAll();
                    foreach($tps as $tp) {
                        $db->prepare("INSERT IGNORE INTO soldes_conge (utilisateur_id,type_conge_id,annee,jours_alloues,jours_pris) VALUES (?,?,?,?,0)")
                           ->execute([$nid,$tp['id'],date('Y'),$tp['jours_max_annuel']]);
                    }
                    logActivite('employe_cree','Nouvel utilisateur créé : '.$prenom.' '.$nom,'utilisateurs',$nid);
                    $success='Utilisateur créé avec succès.';
                }
            }
        }
    }
}

$depts=$db->query("SELECT id,nom FROM departements ORDER BY nom")->fetchAll();
$managers=$db->query("SELECT id,nom,prenom FROM utilisateurs WHERE role IN ('manager','admin') AND actif=1 ORDER BY nom")->fetchAll();
$editData=null;
if($editId){$s=$db->prepare("SELECT * FROM utilisateurs WHERE id=?");$s->execute([$editId]);$editData=$s->fetch();}

$pageTitle=($action==='nouveau')?'Nouvel utilisateur':(($editId)?'Modifier utilisateur':'Gestion utilisateurs');
$pageSubtitle='Administration des comptes';
require_once __DIR__.'/includes/header.php';
?>
<?php if($erreur): ?><div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?=h($erreur)?></span></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span><?=h($success)?></span></div><?php endif; ?>

<?php if($action==='nouveau'||$editId): ?>
<div style="max-width:760px;margin:0 auto">
<div class="card">
  <div class="card-header">
    <div class="card-title"><?=$editId?'Modifier l\'utilisateur':'Nouvel utilisateur'?></div>
    <a href="<?=APP_URL?>/utilisateurs.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
      <input type="hidden" name="action" value="sauvegarder"/>
      <input type="hidden" name="id" value="<?=$editId?>"/>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Prénom <span class="required">*</span></label>
        <input type="text" name="prenom" class="form-control" value="<?=h($editData?$editData['prenom']:'')?>" required/></div>
        <div class="form-group"><label class="form-label">Nom <span class="required">*</span></label>
        <input type="text" name="nom" class="form-control" value="<?=h($editData?$editData['nom']:'')?>" required/></div>
        <div class="form-group"><label class="form-label">Email <span class="required">*</span></label>
        <input type="email" name="email" class="form-control" value="<?=h($editData?$editData['email']:'')?>" required/></div>
        <div class="form-group"><label class="form-label">Rôle</label>
        <select name="role" class="form-control">
          <?php foreach(['employe'=>'Employé','manager'=>'Manager','rh'=>'RH','admin'=>'Administrateur'] as $v=>$l): ?>
          <option value="<?=$v?>" <?=($editData&&$editData['role']===$v)?'selected':''?>><?=$l?></option>
          <?php endforeach; ?>
        </select></div>
        <div class="form-group"><label class="form-label">Département</label>
        <select name="departement_id" class="form-control"><option value="">— Aucun —</option>
        <?php foreach($depts as $d): ?><option value="<?=$d['id']?>" <?=($editData&&$editData['departement_id']==$d['id'])?'selected':''?>><?=h($d['nom'])?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label class="form-label">Manager direct</label>
        <select name="manager_id" class="form-control"><option value="">— Aucun —</option>
        <?php foreach($managers as $m): ?><option value="<?=$m['id']?>" <?=($editData&&$editData['manager_id']==$m['id'])?'selected':''?>><?=h($m['prenom'].' '.$m['nom'])?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label class="form-label">Poste</label>
        <input type="text" name="poste" class="form-control" value="<?=h($editData?($editData['poste']??''):'')?>"/></div>
        <div class="form-group"><label class="form-label">Date d'embauche</label>
        <input type="date" name="date_embauche" class="form-control" value="<?=h($editData?($editData['date_embauche']??''):'')?>"/></div>
      </div>
      <?php if(!$editId): ?>
      <div class="form-group"><label class="form-label">Mot de passe initial</label>
      <input type="text" name="mot_de_passe" class="form-control" value="Evasio@2026" placeholder="Laissez vide pour défaut : Evasio@2026"/></div>
      <?php else: ?>
      <div class="form-group"><label class="form-label">Nouveau mot de passe <span style="color:var(--outline);font-weight:400">(laisser vide pour ne pas changer)</span></label>
      <input type="password" name="nouveau_mdp" class="form-control" placeholder="••••••••"/></div>
      <?php endif; ?>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
        <a href="<?=APP_URL?>/utilisateurs.php" class="btn btn-ghost">Annuler</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?=$editId?'Modifier':'Créer'?></button>
      </div>
    </form>
  </div>
</div>
</div>

<?php else: // LISTE
$stU=$db->query("SELECT u.*,d.nom AS departement FROM utilisateurs u LEFT JOIN departements d ON d.id=u.departement_id ORDER BY u.role,u.nom LIMIT 100");
$users=$stU->fetchAll();
?>
<div class="card">
  <div class="card-header"><div class="card-title">Tous les utilisateurs</div>
  <a href="?action=nouveau" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Nouvel utilisateur</a></div>
  <div class="table-wrapper"><table>
    <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Département</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td><div class="user-cell"><div class="user-avatar-sm" style="background:<?=couleurAvatar((int)$u['id'])?>"><?=initialesAvatar($u['prenom'],$u['nom'])?></div>
        <div class="user-name"><?=h($u['prenom'].' '.$u['nom'])?></div></div></td>
        <td style="font-size:12.5px"><?=h($u['email'])?></td>
        <td><span class="role-badge role-<?=h($u['role'])?>"><?=ucfirst($u['role'])?></span></td>
        <td style="font-size:13px"><?=h($u['departement']??'—')?></td>
        <td><span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:<?=$u['actif']?'var(--success-container)':'var(--error-container)'?>;color:<?=$u['actif']?'var(--success)':'var(--error)'?>"><?=$u['actif']?'Actif':'Inactif'?></span></td>
        <td>
          <div class="action-btns">
            <a href="?action=modifier&id=<?=$u['id']?>" class="btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
              <input type="hidden" name="action" value="toggle_actif"/>
              <input type="hidden" name="id" value="<?=$u['id']?>"/>
              <button type="submit" class="btn-icon <?=$u['actif']?'':'approve'?>" title="<?=$u['actif']?'Désactiver':'Activer'?>">
                <i class="fas <?=$u['actif']?'fa-user-slash':'fa-user-check'?>"></i>
              </button>
            </form>
            <?php if($u['id']!==$user['id']): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')">
              <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
              <input type="hidden" name="action" value="supprimer"/>
              <input type="hidden" name="id" value="<?=$u['id']?>"/>
              <button type="submit" class="btn-icon refuse" title="Supprimer"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<?php require_once __DIR__.'/includes/footer.php'; ?>
