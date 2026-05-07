<?php
require_once __DIR__ . '/includes/functions.php';
demarrerSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    logActivite('deconnexion', 'Déconnexion de l\'utilisateur');
    session_destroy();
}

header('Location: ' . APP_URL . '/login.php');
exit;
