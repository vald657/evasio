<?php
require_once __DIR__ . '/includes/functions.php';
requireAuth();
$user  = utilisateurCourant();
$db    = getDB();
$annee = (int)date('Y');
$erreur = '';
$types = $db->query("SELECT * FROM types_conge WHERE actif=1 ORDER BY nom")->fetchAll();
$typesMap = []; foreach($types as $t) $typesMap[$t['id']] = $t;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $erreur = 'Token invalide.';
  } else {
    $typeId    = (int)($_POST['type_conge_id'] ?? 0);
    $dateDebut = sanitize($_POST['date_debut'] ?? '');
    $dateFin   = sanitize($_POST['date_fin'] ?? '');
    $motif     = sanitize($_POST['motif'] ?? '');
    if (!$typeId || !$dateDebut || !$dateFin) {
      $erreur = 'Champs obligatoires manquants.';
    } elseif ($dateDebut > $dateFin) {
      $erreur = 'Date début > date fin.';
    } elseif ($dateDebut < date('Y-m-d')) {
      $erreur = 'Date dans le passé.';
    } else {
      $nbJours = compterJoursOuvres($dateDebut, $dateFin, $annee);
      if ($nbJours <= 0) {
        $erreur = 'Aucun jour ouvré sur cette période.';
      } else {
        $solde = getSoldeUtilisateur((int)$user['id'], $typeId, $annee);
        if ($solde['jours_restants'] < $nbJours) {
          $erreur = "Solde insuffisant. Restant : {$solde['jours_restants']} j.";
        } else {
          $s = $db->prepare("SELECT COUNT(*) FROM demandes_conge WHERE utilisateur_id=? AND statut NOT IN ('refuse_manager','refuse_rh','annule') AND date_debut<=? AND date_fin>=?");
          $s->execute([$user['id'], $dateFin, $dateDebut]);
          if ($s->fetchColumn() > 0) {
            $erreur = 'Chevauchement avec une demande existante.';
          } else {
            $just = null;
            if (!empty($_FILES['justificatif']['name'])) {
              $ext = strtolower(pathinfo($_FILES['justificatif']['name'], PATHINFO_EXTENSION));
              if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                $erreur = 'Format non autorisé.';
              } elseif ($_FILES['justificatif']['size'] > 5 * 1024 * 1024) {
                $erreur = 'Fichier >5Mo.';
              } else {
                $dir = __DIR__ . '/assets/uploads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $just = uniqid('just_') . '.' . $ext;
                move_uploaded_file($_FILES['justificatif']['tmp_name'], $dir . $just);
              }
            }
            if (!$erreur) {
              $ref = genererReference();
              $db->prepare("INSERT INTO demandes_conge (reference,utilisateur_id,type_conge_id,date_debut,date_fin,nombre_jours,motif,justificatif,statut) VALUES (?,?,?,?,?,?,?,?,'en_attente')")
                ->execute([$ref, $user['id'], $typeId, $dateDebut, $dateFin, $nbJours, $motif ?: null, $just]);
              $did = $db->lastInsertId();
              // Notifier uniquement le manager direct
              $ms = $db->prepare("SELECT manager_id FROM utilisateurs WHERE id=?");
              $ms->execute([$user['id']]);
              $mid = $ms->fetchColumn();
              $lienDemande = APP_URL . '/detail_demande.php?id=' . $did;
              $msgNotif = $user['prenom'] . ' ' . $user['nom'] . ' a soumis une demande de congé (' . $nbJours . ' j) — votre validation est requise.';
              if ($mid) {
                  creerNotification((int)$mid, 'Nouvelle demande à valider', $msgNotif, 'info', $lienDemande);
              } else {
                  // Pas de manager assigné : notifier les RH directement
                  $rhs = $db->query("SELECT id FROM utilisateurs WHERE role IN ('rh','admin') AND actif=1")->fetchAll();
                  foreach($rhs as $rh) creerNotification((int)$rh['id'], 'Nouvelle demande (sans manager)', $msgNotif, 'warning', $lienDemande);
              }
              logActivite('demande_soumise', 'Demande ' . $ref . ' soumise', 'demandes_conge', $did);
              header('Location:' . APP_URL . '/mes_demandes.php?success=1');
              exit;
            }
          }
        }
      }
    }
  }
}
$pageTitle = 'Nouvelle demande';
$pageSubtitle = 'Soumettre une demande de congé';
require_once __DIR__ . '/includes/header.php';
?>
<div style="max-width:780px;margin:0 auto">
  <?php if ($erreur): ?>
    <div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?= h($erreur) ?></span></div>
  <?php endif; ?>
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:8px"></i>Nouvelle demande de congé</div>
        <div class="card-subtitle">Remplissez le formulaire ci-dessous</div>
      </div>
    </div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />
        <div class="form-group">
          <label class="form-label">Type de congé <span class="required">*</span></label>
          <select name="type_conge_id" id="type_id" class="form-control" required>
            <option value="">-- Sélectionner --</option>
            <?php foreach ($types as $t): ?>
              <option value="<?= $t['id'] ?>"
                data-justificatif="<?= $t['justificatif_requis'] ?>"
                <?= (($_POST['type_conge_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                <?= h($t['nom']) ?> (max <?= $t['jours_max_annuel'] ?> j/an)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="soldeInfo" style="display:none;margin-bottom:20px">
          <div class="alert alert-info"><i class="fas fa-wallet"></i><span id="soldeTexte"></span></div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Date de début <span class="required">*</span></label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= h($_POST['date_debut'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Date de fin <span class="required">*</span></label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= h($_POST['date_fin'] ?? '') ?>" required />
          </div>
        </div>
        <div id="dureeInfo" style="display:none;margin-bottom:20px;text-align:center">
          <div style="display:inline-flex;align-items:center;gap:10px;padding:12px 24px;background:var(--info-container);border-radius:var(--radius-full)">
            <i class="fas fa-calendar-check" style="color:var(--primary);font-size:18px"></i>
            <span style="font-weight:700;color:var(--primary);font-size:16px" id="dureeTexte"></span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Motif</label>
          <textarea name="motif" class="form-control" rows="3" placeholder="Raison de la demande..."><?= h($_POST['motif'] ?? '') ?></textarea>
        </div>
        <div class="form-group" id="justificatifSection" style="display:none">
          <label class="form-label">
            Pièce jointe <span class="required">*</span>
            <span style="font-weight:400;color:var(--outline);font-size:12px"> — requis pour ce type de congé</span>
          </label>
          <label class="file-upload" style="cursor:pointer">
            <input type="file" name="justificatif" id="justificatifInput" accept=".pdf,.jpg,.jpeg,.png"
              onchange="document.getElementById('fnom').textContent=this.files[0]?this.files[0].name:'Sélectionner un fichier'" />
            <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div>
              <div style="font-weight:600;font-size:13px" id="fnom">Cliquer pour sélectionner</div>
              <div style="font-size:12px;color:var(--outline)">PDF, JPG, PNG — max 5 Mo</div>
            </div>
          </label>
        </div>
        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px">
          <a href="<?= APP_URL ?>/mes_demandes.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Annuler</a>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Soumettre</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  function joursOuvres(d, f) {
    let a = new Date(d),
      b = new Date(f),
      j = 0;
    while (a <= b) {
      if (a.getDay() !== 0 && a.getDay() !== 6) j++;
      a.setDate(a.getDate() + 1);
    }
    return j;
  }

  function majDuree() {
    const d = document.getElementById('date_debut').value,
      f = document.getElementById('date_fin').value;
    if (d && f && d <= f) {
      document.getElementById('dureeTexte').textContent = joursOuvres(d, f) + ' jour(s) ouvré(s)';
      document.getElementById('dureeInfo').style.display = 'block';
    } else document.getElementById('dureeInfo').style.display = 'none';
  }
  document.getElementById('type_id').addEventListener('change', function() {
    const justSec = document.getElementById('justificatifSection');
    const justInput = document.getElementById('justificatifInput');
    if (!this.value) {
      document.getElementById('soldeInfo').style.display = 'none';
      justSec.style.display = 'none';
      justInput.removeAttribute('required');
      return;
    }
    // Afficher/masquer justificatif selon le type
    const opt = this.options[this.selectedIndex];
    const needsJust = opt.dataset.justificatif === '1';
    justSec.style.display = needsJust ? 'block' : 'none';
    if (needsJust) justInput.setAttribute('required','required');
    else justInput.removeAttribute('required');

    fetch('<?= APP_URL ?>/api/demandes.php?action=solde&type_id=' + this.value + '&annee=<?= $annee ?>')
      .then(r => r.json()).then(d => {
        if (d.success) {
          document.getElementById('soldeTexte').textContent = 'Solde : ' + d.restants + ' j restant(s) sur ' + d.alloues + ' alloué(s)';
          document.getElementById('soldeInfo').style.display = 'block';
        }
      });
  });
  // Si un type est déjà sélectionné au chargement (retour sur erreur)
  (function(){ const s = document.getElementById('type_id'); if(s.value) s.dispatchEvent(new Event('change')); })();
  document.getElementById('date_debut').addEventListener('change', function() {
    const f = document.getElementById('date_fin');
    if (!f.value || f.value < this.value) f.value = this.value;
    majDuree();
  });
  document.getElementById('date_fin').addEventListener('change', majDuree);
  majDuree();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>