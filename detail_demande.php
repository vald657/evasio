<?php
require_once __DIR__.'/includes/functions.php';
requireAuth();
$user=utilisateurCourant(); $db=getDB();
$id=(int)($_GET['id']??0);
if(!$id){header('Location:'.APP_URL.'/mes_demandes.php');exit;}

$stmt=$db->prepare("
    SELECT dc.*,tc.nom AS type_nom,tc.couleur,
           u.nom AS emp_nom,u.prenom AS emp_prenom,u.email AS emp_email,u.photo AS emp_photo,u.id AS emp_id,
           d.nom AS departement,
           m.nom AS manager_nom,m.prenom AS manager_prenom,
           r.nom AS rh_nom,r.prenom AS rh_prenom
    FROM demandes_conge dc
    JOIN types_conge tc ON tc.id=dc.type_conge_id
    JOIN utilisateurs u ON u.id=dc.utilisateur_id
    LEFT JOIN departements d ON d.id=u.departement_id
    LEFT JOIN utilisateurs m ON m.id=dc.manager_id
    LEFT JOIN utilisateurs r ON r.id=dc.rh_id
    WHERE dc.id=?
");
$stmt->execute([$id]); $dem=$stmt->fetch();
if(!$dem){header('Location:'.APP_URL.'/mes_demandes.php');exit;}

$role=$user['role'];
$estProprietaire=($dem['utilisateur_id']==$user['id']);
$estAdmin=($role==='admin');
$estManager=($role==='manager');
$estRH=($role==='rh');
if(!$estProprietaire&&!$estManager&&!$estRH&&!$estAdmin){header('Location:'.APP_URL.'/dashboard.php');exit;}

// Ce que l'utilisateur courant peut faire selon le statut
$peutValiderNiv1 = ($estManager||$estAdmin) && $dem['statut']==='en_attente';
$peutValiderNiv2 = ($estRH||$estAdmin)      && $dem['statut']==='approuve_manager';
$peutRefuser     = $peutValiderNiv1 || $peutValiderNiv2;

$erreur=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $action=$_POST['action']??'';
    if($action==='approuver') {
        if($peutValiderNiv1) {
            $db->prepare("UPDATE demandes_conge SET statut='approuve_manager',manager_id=?,date_decision_manager=NOW() WHERE id=?")->execute([$user['id'],$id]);
            creerNotification((int)$dem['utilisateur_id'],'Demande validée par votre manager','Votre demande '.$dem['reference'].' a été validée. En attente d\'approbation RH.','info',APP_URL.'/detail_demande.php?id='.$id);
            $rhListe=$db->query("SELECT id FROM utilisateurs WHERE role IN ('rh','admin') AND actif=1")->fetchAll();
            foreach($rhListe as $rh) creerNotification((int)$rh['id'],'Demande à approuver (RH)',$dem['emp_prenom'].' '.$dem['emp_nom'].' — validation finale requise.','info',APP_URL.'/detail_demande.php?id='.$id);
            logActivite('demande_approuvee','Demande #'.$id.' validée (manager)','demandes_conge',$id);
            $success='Demande validée et transmise au RH.';
        } elseif($peutValiderNiv2) {
            $db->prepare("UPDATE demandes_conge SET statut='approuve_rh',rh_id=?,date_decision_rh=NOW() WHERE id=?")->execute([$user['id'],$id]);
            $db->prepare("UPDATE soldes_conge SET jours_pris=jours_pris+? WHERE utilisateur_id=? AND type_conge_id=? AND annee=?")->execute([$dem['nombre_jours'],$dem['utilisateur_id'],$dem['type_conge_id'],date('Y')]);
            creerNotification((int)$dem['utilisateur_id'],'Congé approuvé définitivement ✓','Votre demande '.$dem['reference'].' a été approuvée par le RH.','success',APP_URL.'/detail_demande.php?id='.$id);
            logActivite('demande_approuvee','Demande #'.$id.' approuvée (RH)','demandes_conge',$id);
            $success='Demande approuvée définitivement.';
        } else {
            $erreur='Action non autorisée pour ce statut.';
        }
        $stmt->execute([$id]); $dem=$stmt->fetch();
    } elseif($action==='refuser') {
        $commentaire=sanitize($_POST['commentaire']??'');
        if(!$commentaire){$erreur='Veuillez saisir un motif de refus.';}
        elseif(!$peutRefuser){$erreur='Action non autorisée.';}
        else {
            if($peutValiderNiv1) {
                $db->prepare("UPDATE demandes_conge SET statut='refuse_manager',manager_id=?,commentaire_manager=?,date_decision_manager=NOW() WHERE id=?")->execute([$user['id'],$commentaire,$id]);
            } else {
                $db->prepare("UPDATE demandes_conge SET statut='refuse_rh',rh_id=?,commentaire_rh=?,date_decision_rh=NOW() WHERE id=?")->execute([$user['id'],$commentaire,$id]);
            }
            creerNotification((int)$dem['utilisateur_id'],'Demande refusée','Votre demande '.$dem['reference'].' a été refusée. Motif : '.$commentaire,'error',APP_URL.'/detail_demande.php?id='.$id);
            logActivite('demande_refusee','Demande #'.$id.' refusée','demandes_conge',$id);
            $success='Demande refusée.'; $stmt->execute([$id]); $dem=$stmt->fetch();
        }
    } elseif($action==='annuler'&&$estProprietaire&&$dem['statut']==='en_attente') {
        $db->prepare("UPDATE demandes_conge SET statut='annule' WHERE id=?")->execute([$id]);
        logActivite('demande_annulee','Demande #'.$id.' annulée','demandes_conge',$id);
        header('Location:'.APP_URL.'/mes_demandes.php?cancelled=1');exit;
    }
}

$pageTitle='Détail demande '.$dem['reference']; $pageSubtitle='Fiche complète de la demande';
require_once __DIR__.'/includes/header.php';
?>
<?php if($erreur): ?><div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?=h($erreur)?></span></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span><?=h($success)?></span></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

<!-- Colonne principale -->
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- Infos demande -->
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Informations de la demande</div><div class="card-subtitle">Référence : <?=h($dem['reference'])?></div></div>
      <span class="badge <?=classeStatut($dem['statut'])?>"><i class="fas <?=iconeStatut($dem['statut'])?>"></i> <?=labelStatut($dem['statut'])?></span>
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--outline);margin-bottom:5px">Type de congé</div>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="width:12px;height:12px;border-radius:50%;background:<?=h($dem['couleur'])?>"></span>
            <strong><?=h($dem['type_nom'])?></strong>
          </div>
        </div>
        <div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--outline);margin-bottom:5px">Durée</div>
          <strong><?=$dem['nombre_jours']?> jour(s) ouvré(s)</strong>
        </div>
        <div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--outline);margin-bottom:5px">Date de début</div>
          <strong><?=dateFr($dem['date_debut'])?></strong>
        </div>
        <div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--outline);margin-bottom:5px">Date de fin</div>
          <strong><?=dateFr($dem['date_fin'])?></strong>
        </div>
      </div>
      <?php if($dem['motif']): ?>
      <div style="margin-top:20px;padding:16px;background:var(--surface-low);border-radius:var(--radius-md)">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--outline);margin-bottom:6px">Motif</div>
        <p style="font-size:14px"><?=h($dem['motif'])?></p>
      </div>
      <?php endif; ?>
      <?php if($dem['justificatif']): ?>
      <div style="margin-top:16px">
        <a href="<?=APP_URL?>/assets/uploads/<?=h($dem['justificatif'])?>" target="_blank" class="btn btn-ghost btn-sm">
          <i class="fas fa-paperclip"></i> Voir le justificatif
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Timeline workflow -->
  <div class="card">
    <div class="card-header"><div class="card-title">Workflow d'approbation</div></div>
    <div class="card-body">
      <?php
      $steps=[
        ['label'=>'Demande soumise','icon'=>'fa-paper-plane','date'=>$dem['created_at'],'done'=>true,'color'=>'var(--primary)'],
        ['label'=>'Validation Manager','icon'=>'fa-user-tie','date'=>$dem['date_decision_manager'],'done'=>!empty($dem['date_decision_manager']),'color'=>in_array($dem['statut'],['refuse_manager'])?'var(--error)':'var(--primary)','refused'=>$dem['statut']==='refuse_manager'],
        ['label'=>'Approbation RH','icon'=>'fa-stamp','date'=>$dem['date_decision_rh'],'done'=>!empty($dem['date_decision_rh']),'color'=>in_array($dem['statut'],['refuse_rh'])?'var(--error)':'var(--success)','refused'=>$dem['statut']==='refuse_rh'],
      ];
      foreach($steps as $i=>$step): ?>
      <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:<?=$i<count($steps)-1?'20':'0'?>px">
        <div style="display:flex;flex-direction:column;align-items:center">
          <div style="width:36px;height:36px;border-radius:50%;background:<?=$step['done']?$step['color']:'var(--surface-high)'?>;display:flex;align-items:center;justify-content:center;color:<?=$step['done']?'white':'var(--outline)'?>;font-size:14px">
            <i class="fas <?=isset($step['refused'])&&$step['refused']?'fa-times-circle':($step['done']?'fa-check-circle':$step['icon'])?>"></i>
          </div>
          <?php if($i<count($steps)-1): ?><div style="width:2px;flex:1;background:<?=$step['done']?$step['color']:'var(--outline-variant)'?>;margin:6px 0;min-height:24px"></div><?php endif; ?>
        </div>
        <div style="padding-top:6px">
          <div style="font-weight:600;font-size:14px"><?=$step['label']?></div>
          <?php if($step['date']): ?>
          <div style="font-size:12px;color:var(--on-surface-variant);margin-top:2px"><?=(new DateTime($step['date']))->format('d/m/Y à H\hi')?></div>
          <?php elseif(!$step['done']): ?>
          <div style="font-size:12px;color:var(--outline);margin-top:2px">En attente</div>
          <?php endif; ?>
          <?php if($i===1&&$dem['commentaire_manager']): ?>
          <div style="margin-top:6px;padding:8px 12px;background:<?=$dem['statut']==='refuse_manager'?'var(--error-container)':'var(--success-container)'?>;border-radius:var(--radius-sm);font-size:12.5px"><?=h($dem['commentaire_manager'])?></div>
          <?php endif; ?>
          <?php if($i===2&&$dem['commentaire_rh']): ?>
          <div style="margin-top:6px;padding:8px 12px;background:<?=$dem['statut']==='refuse_rh'?'var(--error-container)':'var(--success-container)'?>;border-radius:var(--radius-sm);font-size:12.5px"><?=h($dem['commentaire_rh'])?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Colonne droite -->
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- Employé -->
  <div class="card">
    <div class="card-header"><div class="card-title">Employé</div></div>
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
        <div class="user-avatar-sm" style="width:48px;height:48px;font-size:16px;background:<?=couleurAvatar((int)$dem['emp_id'])?>">
          <?=initialesAvatar($dem['emp_prenom'],$dem['emp_nom'])?>
        </div>
        <div>
          <div style="font-weight:700;font-size:15px"><?=h($dem['emp_prenom'].' '.$dem['emp_nom'])?></div>
          <div style="font-size:13px;color:var(--on-surface-variant)"><?=h($dem['departement']??'—')?></div>
        </div>
      </div>
      <div style="font-size:13px;color:var(--on-surface-variant)"><?=h($dem['emp_email'])?></div>
    </div>
  </div>

  <!-- Actions -->
  <?php if($peutValiderNiv1||$peutValiderNiv2||($estProprietaire&&$dem['statut']==='en_attente')): ?>
  <div class="card">
    <div class="card-header">
      <div class="card-title">Actions</div>
      <?php if($estAdmin): ?>
      <span style="font-size:11px;padding:3px 10px;border-radius:var(--radius-full);background:var(--warning-container);color:var(--warning);font-weight:700">
        <?=$dem['statut']==='en_attente'?'Niveau Manager':'Niveau RH'?>
      </span>
      <?php endif; ?>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <?php if($peutValiderNiv1||$peutValiderNiv2): ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
        <input type="hidden" name="action" value="approuver"/>
        <button type="submit" class="btn btn-success btn-block">
          <i class="fas fa-check"></i>
          <?=$peutValiderNiv1?'Valider (Niveau Manager)':'Approuver définitivement (RH)'?>
        </button>
      </form>
      <button class="btn btn-danger btn-block" onclick="document.getElementById('modalRefus').classList.add('active')">
        <i class="fas fa-times"></i> Refuser la demande
      </button>
      <?php endif; ?>
      <?php if($estProprietaire&&$dem['statut']==='en_attente'): ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
        <input type="hidden" name="action" value="annuler"/>
        <button type="submit" class="btn btn-ghost btn-block" onclick="return confirm('Annuler cette demande ?')">
          <i class="fas fa-ban"></i> Annuler ma demande
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <a href="javascript:history.back()" class="btn btn-ghost btn-block"><i class="fas fa-arrow-left"></i> Retour</a>
</div>
</div>

<!-- Modal refus -->
<div class="modal-overlay" id="modalRefus">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><span class="modal-title"><i class="fas fa-times-circle" style="color:var(--error);margin-right:8px"></i>Refuser la demande</span>
    <button class="modal-close" onclick="fermerModal('modalRefus')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body">
      <form method="POST" id="formRefus">
        <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
        <input type="hidden" name="action" value="refuser"/>
        <div class="form-group">
          <label class="form-label">Motif du refus <span class="required">*</span></label>
          <textarea name="commentaire" class="form-control" rows="4" placeholder="Expliquez la raison du refus..." required></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="fermerModal('modalRefus')">Annuler</button>
      <button class="btn btn-danger" onclick="document.getElementById('formRefus').submit()"><i class="fas fa-times"></i> Confirmer le refus</button>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
