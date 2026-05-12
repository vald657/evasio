<?php
require_once __DIR__.'/includes/functions.php';
requireRole(['admin']);
$user=utilisateurCourant(); $db=getDB();
$success='';

if($_SERVER['REQUEST_METHOD']==='POST'&&verifyCsrf($_POST['csrf_token']??'')) {
    $act=$_POST['form_action']??'config';

    if($act==='config') {
        $champs=['app_nom','app_slogan','jours_ouvres','annee_exercice'];
        foreach($champs as $c) {
            $val=sanitize($_POST[$c]??'');
            $chk=$db->prepare("SELECT id FROM parametres_systeme WHERE cle=?");$chk->execute([$c]);
            if($chk->fetchColumn()) $db->prepare("UPDATE parametres_systeme SET valeur=? WHERE cle=?")->execute([$val,$c]);
            else $db->prepare("INSERT INTO parametres_systeme (cle,valeur,label) VALUES (?,?,?)")->execute([$c,$val,$c]);
        }
        logActivite('config_modifiee','Paramètres système mis à jour');
        $success='Configuration sauvegardée avec succès.';

    } elseif($act==='questions_securite') {
        $raw=sanitize($_POST['questions_securite']??'');
        // Convertir les lignes en liste pipe-séparée, nettoyer les lignes vides
        $lines=array_filter(array_map('trim',explode("\n",$raw)),fn($l)=>$l!=='');
        $val=implode('|',$lines);
        $chk=$db->prepare("SELECT id FROM parametres_systeme WHERE cle='questions_securite'");$chk->execute();
        if($chk->fetchColumn()) $db->prepare("UPDATE parametres_systeme SET valeur=? WHERE cle='questions_securite'")->execute([$val]);
        else $db->prepare("INSERT INTO parametres_systeme (cle,valeur,label) VALUES ('questions_securite',?,?)")->execute([$val,'Questions de sécurité disponibles']);
        logActivite('config_modifiee','Questions de sécurité mises à jour');
        $success='Questions de sécurité enregistrées.';

    } elseif($act==='ajouter_ferie') {
        $date=sanitize($_POST['date_ferie']??'');
        $label=sanitize($_POST['label_ferie']??'Jour férié');
        if($date && preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) {
            $existing=getParam('jours_feries','[]');
            $feries=json_decode($existing,true)??[];
            $feries[]=['date'=>$date,'label'=>$label];
            usort($feries,fn($a,$b)=>strcmp($a['date'],$b['date']));
            $json=json_encode($feries,JSON_UNESCAPED_UNICODE);
            $chk=$db->prepare("SELECT id FROM parametres_systeme WHERE cle='jours_feries'");$chk->execute();
            if($chk->fetchColumn()) $db->prepare("UPDATE parametres_systeme SET valeur=? WHERE cle='jours_feries'")->execute([$json]);
            else $db->prepare("INSERT INTO parametres_systeme (cle,valeur,label) VALUES ('jours_feries',?,?)")->execute([$json,'Jours fériés (JSON)']);
            $success='Jour férié ajouté.';
        }
    } elseif($act==='supprimer_ferie') {
        $date=sanitize($_POST['date_ferie']??'');
        $existing=getParam('jours_feries','[]');
        $feries=json_decode($existing,true)??[];
        $feries=array_values(array_filter($feries,fn($f)=>$f['date']!==$date));
        $json=json_encode($feries,JSON_UNESCAPED_UNICODE);
        $db->prepare("UPDATE parametres_systeme SET valeur=? WHERE cle='jours_feries'")->execute([$json]);
        $success='Jour férié supprimé.';
    }
}

$params=[]; $rows=$db->query("SELECT cle,valeur FROM parametres_systeme")->fetchAll();
foreach($rows as $r) $params[$r['cle']]=$r['valeur'];

// Questions de sécurité actuelles (pipe → lignes)
$questionsRaw=$params['questions_securite']??'';
$questionsTextarea=$questionsRaw?implode("\n",explode('|',$questionsRaw)):'';

$pageTitle='Paramètres système'; $pageSubtitle='Configuration de l\'application IUC';
require_once __DIR__.'/includes/header.php';
?>
<?php if($success): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span><?=h($success)?></span></div><?php endif; ?>

<div style="max-width:760px;margin:0 auto">
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
  <input type="hidden" name="form_action" value="config"/>

  <!-- Général -->
  <div class="card mb-24">
    <div class="card-header"><div class="card-title"><i class="fas fa-sliders-h" style="color:var(--primary);margin-right:8px"></i>Général</div></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Nom de l'application</label>
        <input type="text" name="app_nom" class="form-control" value="<?=h($params['app_nom']??'IUC')?>"/></div>
        <div class="form-group"><label class="form-label">Slogan</label>
        <input type="text" name="app_slogan" class="form-control" value="<?=h($params['app_slogan']??'')?>"/></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Année d'exercice</label>
        <input type="number" name="annee_exercice" class="form-control" value="<?=h($params['annee_exercice']??date('Y'))?>" min="2020" max="2099"/></div>
        <div class="form-group">
          <label class="form-label">Jours ouvrés</label>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px">
            <?php
            $joursActifs=array_filter(array_map('trim',explode(',',$params['jours_ouvres']??'1,2,3,4,5')));
            $joursNoms=[1=>'Lun',2=>'Mar',3=>'Mer',4=>'Jeu',5=>'Ven',6=>'Sam',7=>'Dim'];
            foreach($joursNoms as $n=>$lbl):
              $checked=in_array((string)$n,$joursActifs)?'checked':'';
            ?>
            <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:13px;padding:5px 12px;border:1.5px solid <?=$checked?'var(--primary)':'var(--outline-variant)'?>;border-radius:var(--radius-full);background:<?=$checked?'var(--primary-container)':'transparent'?>;color:<?=$checked?'var(--on-primary-container)':'var(--on-surface-variant)'?>;transition:.15s" class="jour-toggle">
              <input type="checkbox" name="jours_ouvres_cb[]" value="<?=$n?>" <?=$checked?> style="display:none"/>
              <?=$lbl?>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="jours_ouvres" id="joursOuvresHidden" value="<?=h($params['jours_ouvres']??'1,2,3,4,5')?>"/>
        </div>
      </div>
    </div>
  </div>

  <div style="text-align:right;margin-bottom:24px">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Sauvegarder la configuration</button>
  </div>
</form>

<!-- Questions de sécurité -->
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
  <input type="hidden" name="form_action" value="questions_securite"/>
  <div class="card mb-24">
    <div class="card-header">
      <div>
        <div class="card-title"><i class="fas fa-shield-alt" style="color:var(--primary);margin-right:8px"></i>Questions de sécurité</div>
        <div class="card-subtitle">Utilisées pour la réinitialisation du mot de passe sans email</div>
      </div>
    </div>
    <div class="card-body">
      <div class="alert alert-info mb-16"><i class="fas fa-info-circle"></i><span>Saisissez une question par ligne. Les utilisateurs choisiront parmi ces questions dans leur profil pour sécuriser leur compte.</span></div>
      <div class="form-group">
        <label class="form-label">Questions disponibles (une par ligne)</label>
        <textarea name="questions_securite" class="form-control" rows="8" placeholder="Quel est le prénom de votre mère ?&#10;Quel est le nom de votre animal de compagnie ?&#10;Dans quelle ville êtes-vous né(e) ?"><?=h($questionsTextarea)?></textarea>
      </div>
      <div style="text-align:right">
        <button type="submit" class="btn btn-secondary"><i class="fas fa-save"></i> Enregistrer les questions</button>
      </div>
    </div>
  </div>
</form>

<!-- Jours fériés -->
<?php
$feriesJson=getParam('jours_feries','[]');
$feriesList=json_decode($feriesJson,true)??[];
?>
<div class="card mb-24">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-calendar-times" style="color:var(--secondary);margin-right:8px"></i>Jours fériés</div>
    <div class="card-subtitle">Ces jours seront désactivés sur tous les calendriers et demandes</div></div>
  </div>
  <div class="card-body">
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:20px">
      <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
      <input type="hidden" name="form_action" value="ajouter_ferie"/>
      <div class="form-group" style="margin:0;flex:0 0 180px">
        <label class="form-label">Date</label>
        <input type="date" name="date_ferie" class="form-control" required/>
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:180px">
        <label class="form-label">Libellé</label>
        <input type="text" name="label_ferie" class="form-control" placeholder="ex : Fête du Travail" value="Jour férié"/>
      </div>
      <button type="submit" class="btn btn-secondary"><i class="fas fa-plus"></i> Ajouter</button>
    </form>
    <?php if(empty($feriesList)): ?>
    <p style="color:var(--outline);font-size:13px"><i class="fas fa-info-circle" style="margin-right:6px"></i>Aucun jour férié configuré.</p>
    <?php else: ?>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach($feriesList as $f): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?=csrfToken()?>"/>
        <input type="hidden" name="form_action" value="supprimer_ferie"/>
        <input type="hidden" name="date_ferie" value="<?=h($f['date'])?>"/>
        <div style="display:flex;align-items:center;gap:8px;padding:6px 14px;background:var(--error-container);border-radius:var(--radius-full);font-size:13px;font-weight:600;color:var(--error)">
          <i class="fas fa-calendar-day"></i>
          <?=h($f['label'])?> — <?=h(date('d/m/Y',strtotime($f['date'])))?>
          <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--error);padding:0;font-size:12px;margin-left:4px" title="Supprimer"><i class="fas fa-times"></i></button>
        </div>
      </form>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<script>
// Jours ouvrés : cases à cocher visuelles → champ caché CSV
document.querySelectorAll('.jour-toggle').forEach(label => {
  label.addEventListener('click', function(e) {
    e.preventDefault();
    const cb = this.querySelector('input[type=checkbox]');
    cb.checked = !cb.checked;
    const isOn = cb.checked;
    this.style.borderColor   = isOn ? 'var(--primary)' : 'var(--outline-variant)';
    this.style.background    = isOn ? 'var(--primary-container)' : 'transparent';
    this.style.color         = isOn ? 'var(--on-primary-container)' : 'var(--on-surface-variant)';
    // Recalculer la valeur CSV
    const vals = [...document.querySelectorAll('.jour-toggle input:checked')].map(i => i.value);
    document.getElementById('joursOuvresHidden').value = vals.join(',');
  });
});
</script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
