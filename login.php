<?php
// =============================================
//  ÉVASIO — Page de connexion
// =============================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

demarrerSession();

// Déjà connecté → dashboard
if (estConnecte()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$erreur  = '';
$success = isset($_GET['mdp_reset']) ? 'Mot de passe modifié avec succès. Vous pouvez maintenant vous connecter.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT u.id, u.nom, u.prenom, u.email, u.mot_de_passe, u.role,
                   u.photo, u.actif,
                   d.nom AS departement
            FROM utilisateurs u
            LEFT JOIN departements d ON d.id = u.departement_id
            WHERE u.email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
            $erreur = 'Email ou mot de passe incorrect.';
        } elseif (!$user['actif']) {
            $erreur = 'Votre compte a été désactivé. Contactez l\'administrateur.';
        } else {
            // Créer la session
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nom']    = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email']  = $user['email'];
            $_SESSION['user_role']   = $user['role'];
            $_SESSION['user_photo']  = $user['photo'];
            $_SESSION['user_dept']   = $user['departement'];

            // Mettre à jour la dernière connexion
            $db->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?")
               ->execute([$user['id']]);

            logActivite('connexion', 'Connexion réussie', 'utilisateurs', $user['id']);

            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Connexion — Évasio</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css"/>
</head>
<body>

<div class="login-page">
  <div class="login-card">

    <!-- Logo -->
    <div class="login-logo">
      <svg width="48" height="48" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="40" height="40" rx="10" fill="#f5f2fe"/>
        <circle cx="20" cy="13" r="5.5" fill="none" stroke="#b4136d" stroke-width="2"/>
        <line x1="20" y1="7.5" x2="20" y2="4.5" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
        <line x1="24.9" y1="9.1" x2="27" y2="7" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
        <line x1="26.5" y1="13" x2="29.5" y2="13" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
        <line x1="15.1" y1="9.1" x2="13" y2="7" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
        <line x1="13.5" y1="13" x2="10.5" y2="13" stroke="#b4136d" stroke-width="2" stroke-linecap="round"/>
        <rect x="11" y="20" width="18" height="14" rx="3" fill="#4648d4"/>
        <polyline points="15,27 18.5,31 25,23" stroke="#b4136d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
      </svg>
      <span class="login-logo-text">Évasio</span>
    </div>

    <h1 class="login-title">Bienvenue 👋</h1>
    <p class="login-subtitle">Connectez-vous à votre espace de gestion des congés</p>

    <!-- Erreur -->
    <?php if ($erreur): ?>
      <div class="alert alert-error mb-16">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= h($erreur) ?></span>
      </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <form method="POST" action="login.php" id="loginForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>

      <div class="form-group">
        <label class="form-label" for="email">
          <i class="fas fa-envelope" style="margin-right:6px;color:var(--primary)"></i>
          Adresse email
        </label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control"
          placeholder="votre@email.com"
          value="<?= h($_POST['email'] ?? '') ?>"
          required
          autocomplete="email"
        />
      </div>

      <div class="form-group">
        <label class="form-label" for="mot_de_passe">
          <i class="fas fa-lock" style="margin-right:6px;color:var(--primary)"></i>
          Mot de passe
        </label>
        <div style="position:relative">
          <input
            type="password"
            id="mot_de_passe"
            name="mot_de_passe"
            class="form-control"
            placeholder="••••••••"
            required
            autocomplete="current-password"
            style="padding-right:46px"
          />
          <button type="button" id="togglePwd" style="
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:var(--outline); cursor:pointer; font-size:15px;
          ">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div style="text-align:right; margin-bottom:20px; margin-top:-8px;">
        <a href="mot_de_passe_oublie.php" style="font-size:13px;color:var(--primary);font-weight:600;">
          Mot de passe oublié ?
        </a>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
        <span id="btnText"><i class="fas fa-sign-in-alt"></i> Se connecter</span>
        <span id="btnSpinner" style="display:none"><span class="spinner"></span> Connexion...</span>
      </button>
    </form>

    <!-- Footer -->
    <div style="text-align:center; margin-top:28px; padding-top:20px; border-top:1px solid var(--outline-variant);">
      <p style="font-size:12px; color:var(--outline);">
        <i class="fas fa-shield-alt" style="margin-right:5px;color:var(--primary)"></i>
        Connexion sécurisée — Évasio v1.0
      </p>
    </div>

  </div>
</div>

<script>
// Toggle mot de passe
document.getElementById('togglePwd').addEventListener('click', function() {
  const input = document.getElementById('mot_de_passe');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
});

// Loader au submit
document.getElementById('loginForm').addEventListener('submit', function() {
  document.getElementById('btnText').style.display    = 'none';
  document.getElementById('btnSpinner').style.display = 'flex';
  document.getElementById('submitBtn').disabled = true;
});
</script>
</body>
</html>
