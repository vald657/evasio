<?php
require_once __DIR__.'/includes/functions.php';
requireAuth();
$user=utilisateurCourant(); $db=getDB(); $annee=(int)date('Y');
$erreur=''; $success='';

$stmt=$db->prepare("SELECT u.*,d.nom AS departement,m.nom AS manager_nom,m.prenom AS manager_prenom FROM utilisateurs u LEFT JOIN departements d ON d.id=u.departement_id LEFT JOIN utilisateurs m ON m.id=u.manager_id WHERE u.id=?");
$stmt->execute([$user['id']]); $profil=$stmt->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $action=$_POST['action']??'';

    if($action==='infos') {
        $tel=sanitize($_POST['telephone']??'');
        $prn=sanitize($_POST['prenom']??'');
        $nmn=sanitize($_POST['nom']??'');
        $db->prepare("UPDATE utilisateurs SET telephone=?,prenom=?,nom=?,updated_at=NOW() WHERE id=?")->execute([$tel,$prn?:$profil['prenom'],$nmn?:$profil['nom'],$user['id']]);
        if($prn) { $_SESSION['user_prenom']=$prn; }
        if($nmn) { $_SESSION['user_nom']=$nmn; }
        // Photo
        if(!empty($_FILES['photo']['name'])) {
            $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','webp'])&&$_FILES['photo']['size']<=2*1024*1024) {
                $dir=__DIR__.'/assets/img/photos/';
                if(!is_dir($dir))mkdir($dir,0755,true);
                $fname=$user['id'].'_'.time().'.'.$ext;
                move_uploaded_file($_FILES['photo']['tmp_name'],$dir.$fname);
                $db->prepare("UPDATE utilisateurs SET photo=? WHERE id=?")->execute([$fname,$user['id']]);
                $_SESSION['user_photo']=$fname;
            }
        }
        logActivite('profil_modifie','Profil mis à jour','utilisateurs',$user['id']);
        $success='Profil mis à jour avec succès.';
        $stmt->execute([$user['id']]); $profil=$stmt->fetch();
    }

    if($action==='password') {
        $ancien=$_POST['ancien_mdp']??'';
        $nouveau=$_POST['nouveau_mdp']??'';
        $confirm=$_POST['confirmer_mdp']??'';
        if(!password_verify($ancien,$profil['mot_de_passe'])) {
            $erreur='Ancien mot de passe incorrect.';
        } elseif(strlen($nouveau)<8) {
            $erreur='Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif($nouveau!==$confirm) {
            $erreur='Les mots de passe ne correspondent pas.';
        } else {
            $db->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?")->execute([password_hash($nouveau,PASSWORD_DEFAULT),$user['id']]);
            logActivite('mdp_change','Mot de passe modifié','utilisateurs',$user['id']);
            $success='Mot de passe modifié avec succès.';
        }
    }

    if($action==='securite') {
        $question=sanitize($_POST['question_securite']??'');
        $reponse=trim($_POST['reponse_securite']??'');
        if(!$question) { $erreur='Veuillez choisir une question.'; }
        elseif(strlen($reponse)<2) { $erreur='La réponse est trop courte.'; }
        else {
            $hash=password_hash(mb_strtolower($reponse),PASSWORD_DEFAULT);
            $db->prepare("UPDATE utilisateurs SET question_securite=?,reponse_securite=? WHERE id=?")->execute([$question,$hash,$user['id']]);
            logActivite('securite_definie','Question de sécurité configurée','utilisateurs',$user['id']);
            $success='Question de sécurité enregistrée.';
            $stmt->execute([$user['id']]); $profil=$stmt->fetch();
        }
    }
}

$soldes=tousLesSoldes((int)$user['id'],$annee);

// Charger la liste des questions configurées
$qRaw=getParam('questions_securite','');
$questionsDisponibles=$qRaw?array_filter(array_map('trim',explode('|',$qRaw))):[];

$pageTitle='Mon profil'; $pageSubtitle='Gérez vos informations personnelles';
require_once __DIR__.'/includes/header.php';
?>
<?php if($erreur): ?><div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?=h($erreur)?></span></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span><?=h($success)?></span></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- Infos personnelles -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-user" style="color:var(--primary);margin-right:8px"></i>Informations personnelles</div></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
        <input type="hidden" name="action" value="infos"/>
        <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;padding:20px;background:var(--surface-low);border-radius:var(--radius-md)">
          <div style="position:relative">
            <div class="user-avatar-sm" style="width:72px;height:72px;font-size:24px;background:<?=couleurAvatar((int)$user['id'])?>">
              <?php if($profil['photo']): ?>
              <img src="<?=APP_URL?>/assets/img/photos/<?=h($profil['photo'])?>" alt="photo"/>
              <?php else: ?><?=initialesAvatar($profil['prenom'],$profil['nom'])?><?php endif; ?>
            </div>
          </div>
          <div>
            <div style="font-family:var(--font-title);font-size:18px;font-weight:700"><?=h($profil['prenom'].' '.$profil['nom'])?></div>
            <div style="color:var(--on-surface-variant);font-size:13px;margin-top:2px"><?=h($profil['poste']??'—')?> · <?=h($profil['departement']??'—')?></div>
            <div style="margin-top:10px">
              <label class="btn btn-ghost btn-sm" style="cursor:pointer">
                <i class="fas fa-camera"></i> Changer la photo
                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" style="display:none"/>
              </label>
            </div>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Prénom</label>
            <input type="text" name="prenom" class="form-control" value="<?=h($profil['prenom'])?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">Nom</label>
            <input type="text" name="nom" class="form-control" value="<?=h($profil['nom'])?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" value="<?=h($profil['email'])?>" readonly style="background:var(--surface-low);cursor:not-allowed" title="L'email est géré par l'administrateur"/>
          </div>
          <div class="form-group">
            <label class="form-label">Téléphone</label>
            <input type="tel" name="telephone" class="form-control" value="<?=h($profil['telephone']??'')?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">Département</label>
            <input type="text" class="form-control" value="<?=h($profil['departement']??'—')?>" readonly style="background:var(--surface-low);cursor:not-allowed" title="Géré par l'administrateur"/>
          </div>
          <div class="form-group">
            <label class="form-label">Rôle</label>
            <input type="text" class="form-control" value="<?=h(ucfirst($profil['role']))?>" readonly style="background:var(--surface-low);cursor:not-allowed" title="Géré par l'administrateur"/>
          </div>
          <?php if($profil['manager_nom']): ?>
          <div class="form-group">
            <label class="form-label"><i class="fas fa-user-tie" style="color:var(--primary);margin-right:5px"></i>Mon manager</label>
            <input type="text" class="form-control" value="<?=h($profil['manager_prenom'].' '.$profil['manager_nom'])?>" readonly style="background:var(--surface-low);cursor:not-allowed"/>
          </div>
          <?php endif; ?>
        </div>
        <div style="text-align:right"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button></div>
      </form>
    </div>
  </div>

  <!-- Changer mot de passe -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-lock" style="color:var(--secondary);margin-right:8px"></i>Changer le mot de passe</div></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
        <input type="hidden" name="action" value="password"/>
        <div class="form-group"><label class="form-label">Ancien mot de passe <span class="required">*</span></label>
        <input type="password" name="ancien_mdp" class="form-control" placeholder="••••••••"/></div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Nouveau mot de passe <span class="required">*</span></label>
          <input type="password" name="nouveau_mdp" class="form-control" placeholder="min. 8 caractères"/></div>
          <div class="form-group"><label class="form-label">Confirmer <span class="required">*</span></label>
          <input type="password" name="confirmer_mdp" class="form-control" placeholder="••••••••"/></div>
        </div>
        <div style="text-align:right"><button type="submit" class="btn btn-outline"><i class="fas fa-key"></i> Modifier</button></div>
      </form>
    </div>
  </div>

  <!-- Question de sécurité -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-shield-alt" style="color:var(--primary);margin-right:8px"></i>Question de sécurité</div>
    </div>
    <div class="card-body">
      <?php if(empty($questionsDisponibles)): ?>
      <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><span>L'administrateur n'a pas encore configuré les questions de sécurité.</span></div>
      <?php else: ?>
      <?php if($profil['question_securite']??false): ?>
      <div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span>Question configurée : <strong><?=h($profil['question_securite'])?></strong></span></div>
      <?php else: ?>
      <div class="alert alert-warning mb-16"><i class="fas fa-exclamation-triangle"></i><span>Aucune question de sécurité définie. Configurez-en une pour pouvoir récupérer votre mot de passe.</span></div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
        <input type="hidden" name="action" value="securite"/>
        <div class="form-group">
          <label class="form-label">Question <span class="required">*</span></label>
          <select name="question_securite" class="form-control" required>
            <option value="">— Choisir une question —</option>
            <?php foreach($questionsDisponibles as $q): ?>
            <option value="<?=h($q)?>" <?=($profil['question_securite']??'')===$q?'selected':''?>><?=h($q)?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Votre réponse <span class="required">*</span></label>
          <input type="text" name="reponse_securite" class="form-control" placeholder="Réponse (insensible à la casse)" autocomplete="off"/>
          <small style="color:var(--outline);font-size:11px;margin-top:4px;display:block">La réponse est enregistrée de façon sécurisée. La casse n'a pas d'importance.</small>
        </div>
        <div style="text-align:right"><button type="submit" class="btn btn-outline"><i class="fas fa-shield-alt"></i> Enregistrer</button></div>
      </form>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Colonne droite : soldes -->
<div style="display:flex;flex-direction:column;gap:16px">
  <div class="card">
    <div class="card-header"><div class="card-title">Mes soldes <?=$annee?></div></div>
    <div class="card-body">
      <?php if(empty($soldes)): ?>
      <p style="color:var(--on-surface-variant);font-size:13px">Aucun solde configuré pour cette année.</p>
      <?php else: foreach($soldes as $s):
        $pct=$s['jours_alloues']>0?min(100,round($s['jours_pris']/$s['jours_alloues']*100)):0; ?>
      <div class="solde-item">
        <div class="solde-header">
          <span class="solde-label"><span style="width:10px;height:10px;border-radius:50%;background:<?=h($s['couleur'])?>"></span><?=h($s['type_nom'])?></span>
          <span class="solde-value"><?=$s['jours_restants']?>/<?=$s['jours_alloues']?> j</span>
        </div>
        <div class="solde-bar"><div class="solde-fill" style="width:<?=$pct?>%;background:<?=h($s['couleur'])?>"></div></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Informations</div></div>
    <div class="card-body" style="font-size:13px">
      <div style="margin-bottom:10px"><span style="color:var(--outline);font-size:11px;font-weight:700;text-transform:uppercase">Date d'embauche</span><br/><strong><?=$profil['date_embauche']?formatDate($profil['date_embauche'],'d/m/Y'):'—'?></strong></div>
      <div style="margin-bottom:10px"><span style="color:var(--outline);font-size:11px;font-weight:700;text-transform:uppercase">Dernière connexion</span><br/><strong><?=$profil['derniere_connexion']?(new DateTime($profil['derniere_connexion']))->format('d/m/Y à H\hi'):'—'?></strong></div>
      <div><span style="color:var(--outline);font-size:11px;font-weight:700;text-transform:uppercase">Compte créé le</span><br/><strong><?=formatDate($profil['created_at'],'d/m/Y')?></strong></div>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
