<?php
// =============================================
//  ÉVASIO — API Demandes de congé
// =============================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
header('Content-Type: application/json; charset=utf-8');

$user = utilisateurCourant();
$db = getDB();
$role = $user['role'];
$method = $_SERVER['REQUEST_METHOD'];

// GET : solde disponible
if ($method === 'GET') {
    if (($_GET['action'] ?? '') === 'solde') {
        $typeId = (int)($_GET['type_id'] ?? 0);
        $annee  = (int)($_GET['annee']   ?? date('Y'));
        $solde  = getSoldeUtilisateur((int)$user['id'], $typeId, $annee);
        echo json_encode(['success' => true, 'alloues' => $solde['jours_alloues'], 'pris' => $solde['jours_pris'], 'restants' => $solde['jours_restants']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
    }
    exit;
}

// POST : approuver / refuser
if ($method === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token invalide.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit;
    }

    // Charger la demande + infos employé
    $stmt = $db->prepare("
        SELECT dc.*, u.manager_id, u.prenom AS emp_prenom, u.nom AS emp_nom
        FROM demandes_conge dc
        JOIN utilisateurs u ON u.id = dc.utilisateur_id
        WHERE dc.id = ?
    ");
    $stmt->execute([$id]);
    $dem = $stmt->fetch();
    if (!$dem) {
        echo json_encode(['success' => false, 'message' => 'Demande introuvable.']);
        exit;
    }

    $lien      = APP_URL . '/detail_demande.php?id=' . $id;
    $nomEmp    = $dem['emp_prenom'] . ' ' . $dem['emp_nom'];
    $isAdmin   = ($role === 'admin');
    $isManager = ($role === 'manager');
    $isRH      = ($role === 'rh');

    // Déterminer le niveau d'action autorisé
    // Niveau 1 (manager) : statut en_attente
    // Niveau 2 (RH)      : statut approuve_manager
    // Admin               : les deux niveaux
    $niv1 = ($isManager || $isAdmin) && $dem['statut'] === 'en_attente';
    $niv2 = ($isRH      || $isAdmin) && $dem['statut'] === 'approuve_manager';
    $adminRefus = $isAdmin && in_array($dem['statut'], ['en_attente', 'approuve_manager']);

    if ($action === 'approuver') {
        if ($niv1) {
            // Validation niveau 1 → approuve_manager
            $db->prepare("UPDATE demandes_conge SET statut='approuve_manager', manager_id=?, date_decision_manager=NOW() WHERE id=?")
                ->execute([$user['id'], $id]);

            // Notifier l'employé
            creerNotification(
                (int)$dem['utilisateur_id'],
                'Demande validée par votre manager',
                'Votre demande a été validée. Elle est maintenant en attente d\'approbation RH.',
                'info',
                $lien
            );

            // Notifier tous les RH
            $rhs = $db->query("SELECT id FROM utilisateurs WHERE role IN ('rh','admin') AND actif=1")->fetchAll();
            foreach ($rhs as $rh) {
                creerNotification(
                    (int)$rh['id'],
                    'Demande à approuver — Niveau RH',
                    $nomEmp . ' — demande validée par le manager, en attente de votre approbation finale.',
                    'info',
                    $lien
                );
            }

            logActivite('demande_approuvee', 'Demande #' . $id . ' validée (niveau manager)', 'demandes_conge', $id);
            echo json_encode(['success' => true, 'message' => 'Demande validée et transmise au RH.']);
        } elseif ($niv2) {
            // Approbation finale niveau 2 → approuve_rh
            $db->prepare("UPDATE demandes_conge SET statut='approuve_rh', rh_id=?, date_decision_rh=NOW() WHERE id=?")
                ->execute([$user['id'], $id]);

            // Déduire du solde
            $db->prepare("UPDATE soldes_conge SET jours_pris = jours_pris + ? WHERE utilisateur_id=? AND type_conge_id=? AND annee=?")
                ->execute([$dem['nombre_jours'], $dem['utilisateur_id'], $dem['type_conge_id'], date('Y')]);

            // Notifier l'employé
            creerNotification(
                (int)$dem['utilisateur_id'],
                'Congé approuvé définitivement ✓',
                'Votre demande de congé a été approuvée par le RH.',
                'success',
                $lien
            );

            logActivite('demande_approuvee', 'Demande #' . $id . ' approuvée (niveau RH)', 'demandes_conge', $id);
            echo json_encode(['success' => true, 'message' => 'Demande approuvée définitivement.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée pour ce statut ou ce rôle.']);
        }
    } elseif ($action === 'refuser') {
        $com = sanitize($_POST['commentaire'] ?? '');
        if (!$com) {
            echo json_encode(['success' => false, 'message' => 'Le motif de refus est obligatoire.']);
            exit;
        }

        if ($niv1 || ($adminRefus && $dem['statut'] === 'en_attente')) {
            $db->prepare("UPDATE demandes_conge SET statut='refuse_manager', manager_id=?, commentaire_manager=?, date_decision_manager=NOW() WHERE id=?")
                ->execute([$user['id'], $com, $id]);
            creerNotification(
                (int)$dem['utilisateur_id'],
                'Demande refusée par votre manager',
                'Votre demande a été refusée. Motif : ' . $com,
                'error',
                $lien
            );
            logActivite('demande_refusee', 'Demande #' . $id . ' refusée (manager)', 'demandes_conge', $id);
            echo json_encode(['success' => true, 'message' => 'Demande refusée.']);
        } elseif ($niv2 || ($adminRefus && $dem['statut'] === 'approuve_manager')) {
            $db->prepare("UPDATE demandes_conge SET statut='refuse_rh', rh_id=?, commentaire_rh=?, date_decision_rh=NOW() WHERE id=?")
                ->execute([$user['id'], $com, $id]);
            creerNotification(
                (int)$dem['utilisateur_id'],
                'Demande refusée par le RH',
                'Votre demande a été refusée. Motif : ' . $com,
                'error',
                $lien
            );
            logActivite('demande_refusee', 'Demande #' . $id . ' refusée (RH)', 'demandes_conge', $id);
            echo json_encode(['success' => true, 'message' => 'Demande refusée.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée pour ce statut ou ce rôle.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
