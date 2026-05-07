<?php
require_once __DIR__.'/includes/functions.php';
requireAuth();
$user=utilisateurCourant(); $db=getDB(); $role=$user['role'];

$mois=(int)($_GET['mois']??date('n'));
$annee=(int)($_GET['annee']??date('Y'));
if($mois<1)  {$mois=12;$annee--;}
if($mois>12) {$mois=1; $annee++;}

$premierJour=mktime(0,0,0,$mois,1,$annee);
$nbJours=date('t',$premierJour);
$jourSemaine=(int)date('N',$premierJour); // 1=Lun

// Récupérer les absences du mois
if($role==='employe') {
    $stmt=$db->prepare("SELECT dc.*,tc.nom AS type_nom,tc.couleur,u.nom,u.prenom,u.id AS uid FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id JOIN utilisateurs u ON u.id=dc.utilisateur_id WHERE dc.statut='approuve_rh' AND dc.utilisateur_id=? AND ((dc.date_debut<=? AND dc.date_fin>=?) OR (MONTH(dc.date_debut)=? AND YEAR(dc.date_debut)=?))");
    $stmt->execute([$user['id'],date('Y-m-t',$premierJour),date('Y-m-01',$premierJour),$mois,$annee]);
} elseif($role==='manager') {
    $stmt=$db->prepare("SELECT dc.*,tc.nom AS type_nom,tc.couleur,u.nom,u.prenom,u.id AS uid FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id JOIN utilisateurs u ON u.id=dc.utilisateur_id WHERE dc.statut='approuve_rh' AND u.manager_id=? AND ((dc.date_debut<=? AND dc.date_fin>=?) OR (MONTH(dc.date_debut)=? AND YEAR(dc.date_debut)=?))");
    $stmt->execute([$user['id'],date('Y-m-t',$premierJour),date('Y-m-01',$premierJour),$mois,$annee]);
} else {
    $stmt=$db->prepare("SELECT dc.*,tc.nom AS type_nom,tc.couleur,u.nom,u.prenom,u.id AS uid FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id JOIN utilisateurs u ON u.id=dc.utilisateur_id WHERE dc.statut='approuve_rh' AND ((dc.date_debut<=? AND dc.date_fin>=?) OR (MONTH(dc.date_debut)=? AND YEAR(dc.date_debut)=?))");
    $stmt->execute([date('Y-m-t',$premierJour),date('Y-m-01',$premierJour),$mois,$annee]);
}
$absences=$stmt->fetchAll();

// Indexer par jour
$absParJour=[];
foreach($absences as $abs) {
    $d=new DateTime($abs['date_debut']); $f=new DateTime($abs['date_fin']); $f->modify('+1 day');
    while($d<$f) {
        if((int)$d->format('n')===$mois&&(int)$d->format('Y')===$annee) {
            $absParJour[(int)$d->format('j')][]=$abs;
        }
        $d->modify('+1 day');
    }
}

$moisNoms=['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$joursNoms=['Lu','Ma','Me','Je','Ve','Sa','Di'];
$moisPrec=$mois-1; $anneePrec=$annee; if($moisPrec<1){$moisPrec=12;$anneePrec--;}
$moisSuiv=$mois+1; $anneeSuiv=$annee; if($moisSuiv>12){$moisSuiv=1;$anneeSuiv++;}

$types=$db->query("SELECT * FROM types_conge WHERE actif=1 ORDER BY nom")->fetchAll();

// Jours ouvrés configurés (1=Lun...7=Dim)
$joursOuvresParam=getParam('jours_ouvres','1,2,3,4,5');
$joursOuvresArr=array_map('intval',explode(',',$joursOuvresParam));
// Jours fériés configurés par l'admin
$feriesAdmin=json_decode(getParam('jours_feries','[]'),true)??[];
$feriesAdminDates=array_column($feriesAdmin,'date');
// Fusionner dates désactivées pour JS
$feriesAdminJson=json_encode($feriesAdminDates);
$joursOuvresJson=json_encode($joursOuvresArr);

$pageTitle='Calendrier'; $pageSubtitle=$moisNoms[$mois].' '.$annee;
require_once __DIR__.'/includes/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">

<div class="card">
  <div class="card-header">
    <div style="display:flex;align-items:center;gap:16px">
      <a href="?mois=<?=$moisPrec?>&annee=<?=$anneePrec?>" class="btn btn-ghost btn-sm"><i class="fas fa-chevron-left"></i></a>
      <div><div class="card-title"><?=$moisNoms[$mois]?> <?=$annee?></div></div>
      <a href="?mois=<?=$moisSuiv?>&annee=<?=$anneeSuiv?>" class="btn btn-ghost btn-sm"><i class="fas fa-chevron-right"></i></a>
    </div>
    <a href="?mois=<?=date('n')?>&annee=<?=date('Y')?>" class="btn btn-secondary btn-sm">Aujourd'hui</a>
  </div>
  <div class="card-body">
    <!-- En-têtes jours -->
    <div style="display:grid;grid-template-columns:repeat(7,1fr);margin-bottom:8px">
      <?php foreach($joursNoms as $j): ?>
      <div style="text-align:center;font-size:11px;font-weight:700;color:var(--on-surface-variant);padding:6px"><?=$j?></div>
      <?php endforeach; ?>
    </div>
    <!-- Grille jours -->
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px">
      <?php
      // Cases vides avant le 1er
      for($i=1;$i<$jourSemaine;$i++) echo '<div></div>';
      for($j=1;$j<=$nbJours;$j++):
        $dateStr=sprintf('%04d-%02d-%02d',$annee,$mois,$j);
        $dow=(int)date('N',mktime(0,0,0,$mois,$j,$annee));
        $isToday=($dateStr===date('Y-m-d'));
        $isNonWorkDay=!in_array($dow,$joursOuvresArr);
        $isFerie=in_array($dateStr,$feriesAdminDates);
        $nbAbs=count($absParJour[$j]??[]);
        if($isFerie) $bgColor='#fff0f3';
        elseif($isToday) $bgColor='var(--info-container)';
        elseif($isNonWorkDay) $bgColor='var(--surface-low)';
        else $bgColor='white';
      ?>
      <div style="min-height:80px;padding:4px;border:1px solid var(--surface-high);border-radius:var(--radius-sm);background:<?=$bgColor?>;cursor:pointer;<?=$isNonWorkDay||$isFerie?'opacity:0.7':''?>"
           onclick="afficherJour('<?=$dateStr?>','<?=addslashes(isset($absParJour[$j])?implode(',',array_column($absParJour[$j],'prenom')).' '.implode(',',array_column($absParJour[$j],'nom')):'')?>',<?=$nbAbs?>)"
           title="<?=$nbAbs?> absent(s)">
        <div style="text-align:right;font-size:12px;font-weight:<?=$isToday?'700':'500'?>;color:<?=$isToday?'var(--primary)':($isNonWorkDay||$isFerie?'var(--outline)':'var(--on-surface)')?>">
          <?=$j?>
          <?php if($isFerie): ?><div style="font-size:9px;color:var(--error);font-weight:700;text-transform:uppercase;line-height:1">Férié</div><?php endif; ?>
        </div>
        <?php if(isset($absParJour[$j])): ?>
          <?php foreach(array_slice($absParJour[$j],0,3) as $abs): ?>
          <div style="font-size:10px;background:<?=h($abs['couleur'])?>22;color:<?=h($abs['couleur'])?>;border-radius:3px;padding:1px 4px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?=h($abs['prenom'])?>
          </div>
          <?php endforeach; ?>
          <?php if(count($absParJour[$j])>3): ?>
          <div style="font-size:10px;color:var(--outline);margin-top:2px">+<?=count($absParJour[$j])-3?> autres</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- Légende + stats -->
<div style="display:flex;flex-direction:column;gap:16px">
  <div class="card">
    <div class="card-header"><div class="card-title">Types de congé</div></div>
    <div class="card-body" style="padding:16px">
      <?php foreach($types as $t): ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <span style="width:14px;height:14px;border-radius:50%;background:<?=h($t['couleur'])?>;flex-shrink:0"></span>
        <span style="font-size:13px"><?=h($t['nom'])?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Ce mois</div></div>
    <div class="card-body" style="padding:16px">
      <div style="text-align:center">
        <div style="font-size:36px;font-weight:700;color:var(--primary);font-family:var(--font-title)"><?=count($absences)?></div>
        <div style="font-size:13px;color:var(--on-surface-variant)">demande(s) approuvée(s)</div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Popup info jour -->
<div class="modal-overlay" id="modalJour">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><span class="modal-title" id="modalJourTitre">Absences du jour</span>
    <button class="modal-close" onclick="fermerModal('modalJour')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body" id="modalJourContenu"></div>
  </div>
</div>
<script>
const JOURS_OUVRES = <?=$joursOuvresJson?>;   // ex: [1,2,3,4,5]
const FERIES_ADMIN = <?=$feriesAdminJson?>;     // ex: ["2026-05-01","2026-12-25"]

function estJourDisabled(dateStr) {
  if (FERIES_ADMIN.includes(dateStr)) return true;
  const d = new Date(dateStr);
  const dow = d.getDay() === 0 ? 7 : d.getDay(); // 1=Lun...7=Dim
  return !JOURS_OUVRES.includes(dow);
}

// Désactiver les jours non ouvrés dans les inputs date du formulaire
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('input[type="date"]').forEach(function(inp) {
    inp.addEventListener('change', function() {
      if (estJourDisabled(this.value)) {
        showToast('warning', 'Ce jour est non ouvré ou férié. Veuillez choisir un autre jour.');
        this.value = '';
      }
    });
  });
});

function afficherJour(date,noms,nb){
  document.getElementById('modalJourTitre').textContent='Absences du '+date;
  let html = nb > 0 ? '<p>'+nb+' absent(s) ce jour.</p>' : '<p>Aucune absence ce jour.</p>';
  if (FERIES_ADMIN.includes(date)) html += '<p style="color:var(--error);font-weight:600"><i class="fas fa-calendar-times"></i> Jour férié</p>';
  if (estJourDisabled(date) && !FERIES_ADMIN.includes(date)) html += '<p style="color:var(--outline)"><i class="fas fa-moon"></i> Jour non ouvré</p>';
  document.getElementById('modalJourContenu').innerHTML=html;
  document.getElementById('modalJour').classList.add('active');
}
</script>
<?php require_once __DIR__.'/includes/footer.php'; ?>
