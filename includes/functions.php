<?php
// =============================================
//  IUC — Fonctions globales
// =============================================

require_once dirname(__DIR__) . '/config/database.php';

// --- SESSION -------------------------------------------------------
function demarrerSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function estConnecte(): bool {
    demarrerSession();
    return isset($_SESSION['user_id']);
}

function requireAuth(): void {
    if (!estConnecte()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireAuth();
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: ' . APP_URL . '/dashboard.php?erreur=acces_interdit');
        exit;
    }
}

function utilisateurCourant(): array {
    demarrerSession();
    return [
        'id'     => $_SESSION['user_id']     ?? null,
        'nom'    => $_SESSION['user_nom']    ?? '',
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'email'  => $_SESSION['user_email']  ?? '',
        'role'   => $_SESSION['user_role']   ?? '',
        'photo'  => $_SESSION['user_photo']  ?? null,
        'dept'   => $_SESSION['user_dept']   ?? '',
    ];
}

// --- SÉCURITÉ -------------------------------------------------------
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function sanitize(string $str): string {
    return trim(strip_tags($str));
}

function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function csrfToken(): string {
    demarrerSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    demarrerSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --- DATES ----------------------------------------------------------
function joursFeries(int $annee): array {
    $paques = easter_date($annee);
    return [
        "$annee-01-01", // Jour de l'An
        date('Y-m-d', $paques - 2 * 86400),  // Vendredi Saint
        date('Y-m-d', $paques),               // Pâques
        date('Y-m-d', $paques + 86400),       // Lundi de Pâques
        "$annee-05-01", // Fête du Travail
        "$annee-05-20", // Journée nationale (Cameroun)
        date('Y-m-d', $paques + 39 * 86400),  // Ascension
        "$annee-08-15", // Assomption
        "$annee-12-25", // Noël
    ];
}

function compterJoursOuvres(string $debut, string $fin, int $annee): int {
    $feries = joursFeries($annee);
    $d = new DateTime($debut);
    $f = new DateTime($fin);
    $f->modify('+1 day');
    $jours = 0;
    while ($d < $f) {
        $dow = (int)$d->format('N'); // 1=Lun, 7=Dim
        $date = $d->format('Y-m-d');
        if ($dow < 6 && !in_array($date, $feries)) {
            $jours++;
        }
        $d->modify('+1 day');
    }
    return $jours;
}

function formatDate(string $date, string $format = 'd/m/Y'): string {
    return (new DateTime($date))->format($format);
}

function dateFr(string $date): string {
    $jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
    $mois  = ['janvier','février','mars','avril','mai','juin',
               'juillet','août','septembre','octobre','novembre','décembre'];
    $d = new DateTime($date);
    return $jours[(int)$d->format('N') - 1] . ' '
         . $d->format('j') . ' '
         . $mois[(int)$d->format('n') - 1] . ' '
         . $d->format('Y');
}

// --- RÉFÉRENCE DEMANDE ---------------------------------------------
function genererReference(): string {
    return 'EVS-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
}

// --- NOTIFICATIONS -------------------------------------------------
function creerNotification(int $userId, string $titre, string $message, string $type = 'info', string $lien = ''): void {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO notifications (utilisateur_id, titre, message, type, lien)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $titre, $message, $type, $lien]);
}

function compterNotificationsNonLues(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lu = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// --- LOGS ----------------------------------------------------------
function logActivite(string $action, string $description, string $table = '', int $idCible = 0): void {
    $user = utilisateurCourant();
    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO logs_activite (utilisateur_id, action, description, table_cible, id_cible, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'] ?? null,
        $action,
        $description,
        $table ?: null,
        $idCible ?: null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

// --- AVATARS -------------------------------------------------------
function initialesAvatar(string $prenom, string $nom): string {
    return strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
}

function couleurAvatar(int $userId): string {
    $couleurs = [
        'linear-gradient(135deg,#a70016,#cc2229)',
        'linear-gradient(135deg,#1b6d24,#2e7d32)',
        'linear-gradient(135deg,#1a7a4a,#34c97a)',
        'linear-gradient(135deg,#00567b,#00709e)',
        'linear-gradient(135deg,#a70016,#1b6d24)',
    ];
    return $couleurs[$userId % count($couleurs)];
}

// --- STATUTS -------------------------------------------------------
function labelStatut(string $statut): string {
    return match($statut) {
        'en_attente'       => 'En attente',
        'approuve_manager' => 'Validé Manager',
        'approuve_rh'      => 'Approuvé',
        'refuse_manager'   => 'Refusé (Manager)',
        'refuse_rh'        => 'Refusé (RH)',
        'annule'           => 'Annulé',
        default            => $statut,
    };
}

function classeStatut(string $statut): string {
    return match($statut) {
        'en_attente'       => 'badge-pending',
        'approuve_manager' => 'badge-manager',
        'approuve_rh'      => 'badge-approved',
        'refuse_manager',
        'refuse_rh'        => 'badge-refused',
        'annule'           => 'badge-cancelled',
        default            => '',
    };
}

function iconeStatut(string $statut): string {
    return match($statut) {
        'en_attente'       => 'fa-hourglass-half',
        'approuve_manager' => 'fa-thumbs-up',
        'approuve_rh'      => 'fa-check-circle',
        'refuse_manager',
        'refuse_rh'        => 'fa-times-circle',
        'annule'           => 'fa-ban',
        default            => 'fa-circle',
    };
}

// --- SOLDES --------------------------------------------------------
function getSoldeUtilisateur(int $userId, int $typeCongeId, int $annee): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM soldes_conge
        WHERE utilisateur_id = ? AND type_conge_id = ? AND annee = ?
    ");
    $stmt->execute([$userId, $typeCongeId, $annee]);
    return $stmt->fetch() ?: ['jours_alloues' => 0, 'jours_pris' => 0, 'jours_restants' => 0];
}

function tousLesSoldes(int $userId, int $annee): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT sc.*, tc.nom AS type_nom, tc.couleur
        FROM soldes_conge sc
        JOIN types_conge tc ON tc.id = sc.type_conge_id
        WHERE sc.utilisateur_id = ? AND sc.annee = ?
        ORDER BY tc.nom
    ");
    $stmt->execute([$userId, $annee]);
    return $stmt->fetchAll();
}

// --- PARAMÈTRES SYSTÈME --------------------------------------------
function getParam(string $cle, string $defaut = ''): string {
    static $params = null;
    if ($params === null) {
        $db = getDB();
        $rows = $db->query("SELECT cle, valeur FROM parametres_systeme")->fetchAll();
        foreach ($rows as $r) {
            $params[$r['cle']] = $r['valeur'];
        }
    }
    return $params[$cle] ?? $defaut;
}
