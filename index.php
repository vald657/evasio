<?php
// Redirection vers login ou dashboard
require_once __DIR__ . '/includes/functions.php';
demarrerSession();
if (estConnecte()) {
    header('Location: ' . APP_URL . '/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/login.php');
}
exit;
