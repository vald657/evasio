<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/functions.php';
demarrerSession();
if(estConnecte()){header('Location:'.APP_URL.'/dashboard.php');exit;}

// Lien "Recommencer" depuis l'étape 2
if(isset($_GET['reset'])){unset($_SESSION['mdp_uid'],$_SESSION['mdp_verifie']);header('Location:'.APP_URL.'/mot_de_passe_oublie.php');exit;}

$db=getDB();
$erreur=''; $etape=1;

// Déterminer l'étape courante depuis la session
if(!empty($_SESSION['mdp_verifie']) && !empty($_SESSION['mdp_uid'])) {
    $etape=3;
} elseif(!empty($_SESSION['mdp_uid'])) {
    $etape=2;
}

// ── ÉTAPE 1 : saisie de l'email ──────────────────────────────────────────────
if($etape===1 && $_SERVER['REQUEST_METHOD']==='POST') {
    $email=sanitize($_POST['email']??'');
    if(!$email||!filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $erreur='Adresse email invalide.';
    } else {
        $stmt=$db->prepare("SELECT id,question_securite FROM utilisateurs WHERE email=? AND actif=1");
        $stmt->execute([$email]); $row=$stmt->fetch();
        if($row && !empty($row['question_securite'])) {
            $_SESSION['mdp_uid']=(int)$row['id'];
            unset($_SESSION['mdp_verifie']);
            header('Location:'.APP_URL.'/mot_de_passe_oublie.php'); exit;
        } else {
            // On ne révèle pas si l'email existe ou si la question est absente
            $erreur='Aucun compte correspondant ou question de sécurité non configurée pour cet email.';
        }
    }
}

// ── ÉTAPE 2 : vérification de la réponse ─────────────────────────────────────
if($etape===2 && $_SERVER['REQUEST_METHOD']==='POST') {
    $reponse=trim($_POST['reponse']??'');
    $uid=(int)$_SESSION['mdp_uid'];
    $stmt=$db->prepare("SELECT reponse_securite,question_securite FROM utilisateurs WHERE id=?");
    $stmt->execute([$uid]); $row=$stmt->fetch();
    if($row && $reponse && password_verify(mb_strtolower($reponse),$row['reponse_securite'])) {
        $_SESSION['mdp_verifie']=true;
        header('Location:'.APP_URL.'/mot_de_passe_oublie.php'); exit;
    } else {
        $erreur='Réponse incorrecte. Veuillez réessayer.';
    }
}

// ── ÉTAPE 3 : nouveau mot de passe ───────────────────────────────────────────
if($etape===3 && $_SERVER['REQUEST_METHOD']==='POST') {
    $nouveau=trim($_POST['nouveau_mdp']??'');
    $confirm=trim($_POST['confirmer_mdp']??'');
    $uid=(int)$_SESSION['mdp_uid'];
    if(strlen($nouveau)<8) {
        $erreur='Le mot de passe doit contenir au moins 8 caractères.';
    } elseif($nouveau!==$confirm) {
        $erreur='Les mots de passe ne correspondent pas.';
    } else {
        $db->prepare("UPDATE utilisateurs SET mot_de_passe=?,token_reset=NULL,token_expiry=NULL WHERE id=?")
           ->execute([password_hash($nouveau,PASSWORD_DEFAULT),$uid]);
        logActivite('mdp_reset','Mot de passe réinitialisé via question de sécurité','utilisateurs',$uid);
        unset($_SESSION['mdp_uid'],$_SESSION['mdp_verifie']);
        header('Location:'.APP_URL.'/login.php?mdp_reset=1'); exit;
    }
}

// Charger la question à afficher en étape 2
$questionAffichee='';
if($etape===2) {
    $stmt=$db->prepare("SELECT question_securite FROM utilisateurs WHERE id=?");
    $stmt->execute([(int)$_SESSION['mdp_uid']]); $questionAffichee=$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Mot de passe oublié — Évasio</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="<?=APP_URL?>/assets/css/style.css"/></head>
<body>
<div class="login-page">
<div class="login-card">

  <div class="login-logo">
    <svg width="44" height="44" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="10" fill="#f5f2fe"/>
    <circle cx="20" cy="13" r="5.5" fill="none" stroke="#b4136d" stroke-width="2"/>
    <rect x="11" y="20" width="18" height="14" rx="3" fill="#4648d4"/>
    <polyline points="15,27 18.5,31 25,23" stroke="#b4136d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
    <span class="login-logo-text">Évasio</span>
  </div>

  <!-- Indicateur d'étapes -->
  <div style="display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:24px">
    <?php foreach([1,2,3] as $s):
      $done=$s<$etape; $active=$s===$etape;
      $bg=$done?'var(--primary)':($active?'var(--primary)':'var(--outline-variant)');
      $col=$done||$active?'white':'var(--on-surface-variant)';
    ?>
    <div style="display:flex;align-items:center;gap:0">
      <div style="width:30px;height:30px;border-radius:50%;background:<?=$bg?>;color:<?=$col?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;transition:.2s">
        <?php if($done): ?><i class="fas fa-check" style="font-size:11px"></i><?php else: ?><?=$s?><?php endif; ?>
      </div>
      <?php if($s<3): ?>
      <div style="width:40px;height:2px;background:<?=$done?'var(--primary)':'var(--outline-variant)'?>"></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if($erreur): ?><div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?=h($erreur)?></span></div><?php endif; ?>

  <?php if($etape===1): ?>
  <!-- ── ÉTAPE 1 ── -->
  <h1 class="login-title">Mot de passe oublié</h1>
  <p class="login-subtitle">Renseignez votre email pour retrouver votre compte</p>
  <form method="POST" novalidate>
    <div class="form-group">
      <label class="form-label"><i class="fas fa-envelope" style="margin-right:6px;color:var(--primary)"></i>Adresse email</label>
      <input type="email" name="email" class="form-control" placeholder="votre@email.com" required autofocus/>
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-arrow-right"></i> Continuer</button>
  </form>

  <?php elseif($etape===2): ?>
  <!-- ── ÉTAPE 2 ── -->
  <h1 class="login-title">Question de sécurité</h1>
  <p class="login-subtitle">Répondez à votre question personnelle pour confirmer votre identité</p>
  <div style="background:var(--primary-container);border-radius:var(--radius-md);padding:14px 18px;margin-bottom:20px;font-size:14px;font-weight:600;color:var(--on-primary-container)">
    <i class="fas fa-question-circle" style="margin-right:8px"></i><?=h($questionAffichee)?>
  </div>
  <form method="POST" novalidate>
    <div class="form-group">
      <label class="form-label"><i class="fas fa-pen" style="margin-right:6px;color:var(--primary)"></i>Votre réponse</label>
      <input type="text" name="reponse" class="form-control" placeholder="Saisissez votre réponse" required autofocus autocomplete="off"/>
      <small style="color:var(--outline);font-size:11px;margin-top:4px;display:block">La casse n'a pas d'importance</small>
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-check"></i> Valider</button>
  </form>
  <div style="text-align:center;margin-top:16px">
    <a href="<?=APP_URL?>/mot_de_passe_oublie.php?reset=1" style="font-size:12px;color:var(--outline)">
      <i class="fas fa-arrow-left" style="margin-right:4px"></i>Recommencer
    </a>
  </div>

  <?php else: ?>
  <!-- ── ÉTAPE 3 ── -->
  <h1 class="login-title">Nouveau mot de passe</h1>
  <p class="login-subtitle">Choisissez un nouveau mot de passe sécurisé</p>
  <form method="POST" novalidate>
    <div class="form-group">
      <label class="form-label"><i class="fas fa-lock" style="margin-right:6px;color:var(--primary)"></i>Nouveau mot de passe</label>
      <input type="password" name="nouveau_mdp" class="form-control" placeholder="min. 8 caractères" required autofocus/>
    </div>
    <div class="form-group">
      <label class="form-label"><i class="fas fa-lock" style="margin-right:6px;color:var(--primary)"></i>Confirmer le mot de passe</label>
      <input type="password" name="confirmer_mdp" class="form-control" placeholder="••••••••" required/>
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-save"></i> Enregistrer le mot de passe</button>
  </form>
  <?php endif; ?>

  <div style="text-align:center;margin-top:24px">
    <a href="<?=APP_URL?>/login.php" style="font-size:13px;color:var(--primary);font-weight:600">
      <i class="fas fa-arrow-left" style="margin-right:5px"></i>Retour à la connexion
    </a>
  </div>
</div>
</div>
</body>
</html>
