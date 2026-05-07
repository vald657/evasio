<?php
require_once __DIR__.'/includes/functions.php';
requireAuth();
$user=utilisateurCourant(); $db=getDB();

if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $action=$_POST['action']??'';
    if($action==='lire_tout') {
        $db->prepare("UPDATE notifications SET lu=1 WHERE utilisateur_id=?")->execute([$user['id']]);
    } elseif($action==='supprimer_tout') {
        $db->prepare("DELETE FROM notifications WHERE utilisateur_id=?")->execute([$user['id']]);
    } elseif($action==='lire'&&isset($_POST['id'])) {
        $db->prepare("UPDATE notifications SET lu=1 WHERE id=? AND utilisateur_id=?")->execute([(int)$_POST['id'],$user['id']]);
    } elseif($action==='supprimer'&&isset($_POST['id'])) {
        $db->prepare("DELETE FROM notifications WHERE id=? AND utilisateur_id=?")->execute([(int)$_POST['id'],$user['id']]);
    }
    header('Location:'.APP_URL.'/notifications.php'); exit;
}

$stmt=$db->prepare("SELECT * FROM notifications WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user['id']]); $notifs=$stmt->fetchAll();
$nonLues=$db->prepare("SELECT COUNT(*) FROM notifications WHERE utilisateur_id=? AND lu=0");
$nonLues->execute([$user['id']]); $nbNonLues=(int)$nonLues->fetchColumn();

$icones=['info'=>['fa-info-circle','var(--info)'],'success'=>['fa-check-circle','var(--success)'],'warning'=>['fa-exclamation-triangle','var(--warning)'],'error'=>['fa-times-circle','var(--error)']];
$pageTitle='Notifications'; $pageSubtitle=$nbNonLues.' non lue(s)';
require_once __DIR__.'/includes/header.php';
?>
<div style="max-width:800px;margin:0 auto">
<div class="card">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-bell" style="color:var(--primary);margin-right:8px"></i>Mes notifications</div>
    <div class="card-subtitle"><?=count($notifs)?> notification(s) · <?=$nbNonLues?> non lue(s)</div></div>
    <div style="display:flex;gap:8px">
    <?php if($nbNonLues>0): ?>
    <form method="POST" style="display:inline">
      <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
      <input type="hidden" name="action" value="lire_tout"/>
      <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-check-double"></i> Tout marquer lu</button>
    </form>
    <?php endif; ?>
    <?php if(!empty($notifs)): ?>
    <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer toutes les notifications ?')">
      <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
      <input type="hidden" name="action" value="supprimer_tout"/>
      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--error)"><i class="fas fa-trash-alt"></i> Tout supprimer</button>
    </form>
    <?php endif; ?>
    </div>
  </div>

  <?php if(empty($notifs)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-bell-slash"></i></div>
    <h3>Aucune notification</h3><p>Vous n'avez aucune notification pour le moment.</p>
  </div>
  <?php else: ?>
  <div style="divide-y:1px solid var(--surface-high)">
    <?php foreach($notifs as $n):
      $ico=$icones[$n['type']]??['fa-circle','var(--primary)'];
      $bg=$n['lu']?'white':'var(--surface-low)';
    ?>
    <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 24px;background:<?=$bg?>;border-bottom:1px solid var(--surface-high)">
      <div style="width:38px;height:38px;border-radius:var(--radius-md);background:<?=$n['type']==='success'?'var(--success-container)':($n['type']==='error'?'var(--error-container)':($n['type']==='warning'?'var(--warning-container)':'var(--info-container)'))?>
;display:flex;align-items:center;justify-content:center;color:<?=$ico[1]?>;font-size:16px;flex-shrink:0">
        <i class="fas <?=$ico[0]?>"></i>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:<?=$n['lu']?'500':'700'?>;font-size:14px"><?=h($n['titre'])?></div>
        <div style="font-size:13px;color:var(--on-surface-variant);margin-top:3px"><?=h($n['message'])?></div>
        <div style="font-size:11px;color:var(--outline);margin-top:6px"><i class="fas fa-clock" style="margin-right:4px"></i><?=(new DateTime($n['created_at']))->format('d/m/Y à H\hi')?></div>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0">
        <?php if($n['lien']): ?><a href="<?=h($n['lien'])?>" class="btn-icon" title="Voir"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>
        <?php if(!$n['lu']): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/><input type="hidden" name="action" value="lire"/><input type="hidden" name="id" value="<?=$n['id']?>"/>
        <button type="submit" class="btn-icon" title="Marquer lu"><i class="fas fa-check"></i></button></form>
        <?php endif; ?>
        <form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/><input type="hidden" name="action" value="supprimer"/><input type="hidden" name="id" value="<?=$n['id']?>"/>
        <button type="submit" class="btn-icon refuse" title="Supprimer" onclick="return confirm('Supprimer cette notification ?')"><i class="fas fa-trash"></i></button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
