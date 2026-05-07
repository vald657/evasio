<?php
// =============================================
//  ÉVASIO — Export PDF / Excel
// =============================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireRole(['rh', 'admin']);

$db = getDB();
$format = $_GET['format'] ?? 'excel';
$filtreDept = (int)($_GET['dept'] ?? 0);
$filtreEmp = (int)($_GET['emp'] ?? 0);
$filtreType = (int)($_GET['type'] ?? 0);
$filtreDebut = $_GET['debut'] ?? date('Y-01-01');
$filtreFin = $_GET['fin'] ?? date('Y-m-d');
$filtreStatut = $_GET['statut'] ?? '';

$where = "WHERE dc.date_debut>=? AND dc.date_fin<=?";
$params = [$filtreDebut, $filtreFin];
if ($filtreDept) {
    $where .= " AND u.departement_id=?";
    $params[] = $filtreDept;
}
if ($filtreEmp) {
    $where .= " AND dc.utilisateur_id=?";
    $params[] = $filtreEmp;
}
if ($filtreType) {
    $where .= " AND dc.type_conge_id=?";
    $params[] = $filtreType;
}
if ($filtreStatut) {
    $where .= " AND dc.statut=?";
    $params[] = $filtreStatut;
}

$stmt = $db->prepare("SELECT dc.reference,u.nom,u.prenom,d.nom AS departement,tc.nom AS type_nom,dc.date_debut,dc.date_fin,dc.nombre_jours,dc.statut,dc.motif FROM demandes_conge dc JOIN types_conge tc ON tc.id=dc.type_conge_id JOIN utilisateurs u ON u.id=dc.utilisateur_id LEFT JOIN departements d ON d.id=u.departement_id $where ORDER BY dc.date_debut DESC");
$stmt->execute($params);
$demandes = $stmt->fetchAll();

logActivite('export', 'Export ' . strtoupper($format) . ' généré (' . count($demandes) . ' lignes)');

if ($format === 'excel') {
    // Export CSV (compatible Excel)
    $filename = 'evasio_conges_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['Référence', 'Prénom', 'Nom', 'Département', 'Type de congé', 'Date début', 'Date fin', 'Nb jours', 'Statut', 'Motif'], ';');
    foreach ($demandes as $d) {
        fputcsv($out, [
            $d['reference'],
            $d['prenom'],
            $d['nom'],
            $d['departement'] ?? '—',
            $d['type_nom'],
            formatDate($d['date_debut']),
            formatDate($d['date_fin']),
            $d['nombre_jours'],
            labelStatut($d['statut']),
            $d['motif'] ?? ''
        ], ';');
    }
    fclose($out);
} elseif ($format === 'pdf') {
    // Export HTML → impression PDF navigateur
    $titre = 'Rapport congés — ' . formatDate($filtreDebut) . ' au ' . formatDate($filtreFin);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"/>
    <title>' . $titre . '</title>
    <style>
      body{font-family:Arial,sans-serif;font-size:11pt;color:#1b1b23;margin:20px}
      h1{color:#4648d4;font-size:16pt;margin-bottom:4px}
      p{color:#767586;font-size:10pt;margin-bottom:20px}
      table{width:100%;border-collapse:collapse;font-size:9.5pt}
      th{background:#4648d4;color:white;padding:8px 10px;text-align:left;font-size:9pt}
      td{padding:7px 10px;border-bottom:1px solid #e4e1ed}
      tr:nth-child(even) td{background:#f5f2fe}
      .badge{padding:2px 8px;border-radius:20px;font-size:8.5pt;font-weight:bold}
      .approved{background:#d6f5e3;color:#1a7a4a}
      .refused {background:#ffdad6;color:#ba1a1a}
      .pending  {background:#ffdcc5;color:#904900}
      .footer{margin-top:24px;font-size:9pt;color:#767586;text-align:right}
      @media print{body{margin:0}button{display:none}}
    </style></head><body>
    <button onclick="window.print()" style="margin-bottom:16px;padding:8px 20px;background:#4648d4;color:white;border:none;border-radius:20px;cursor:pointer;font-size:12px">
      🖨️ Imprimer / Enregistrer en PDF
    </button>
    <h1>' . $titre . '</h1>
    <p>Généré le ' . date('d/m/Y à H:i') . ' · ' . count($demandes) . ' demande(s)</p>
    <table><thead><tr><th>Référence</th><th>Employé</th><th>Département</th><th>Type</th><th>Début</th><th>Fin</th><th>Jours</th><th>Statut</th></tr></thead><tbody>';
    foreach ($demandes as $d) {
        $cls = in_array($d['statut'], ['approuve_rh']) ? 'approved' : (in_array($d['statut'], ['refuse_manager', 'refuse_rh']) ? 'refused' : 'pending');
        echo '<tr>
            <td><code>' . $d['reference'] . '</code></td>
            <td>' . h($d['prenom'] . ' ' . $d['nom']) . '</td>
            <td>' . h($d['departement'] ?? '—') . '</td>
            <td>' . h($d['type_nom']) . '</td>
            <td>' . formatDate($d['date_debut']) . '</td>
            <td>' . formatDate($d['date_fin']) . '</td>
            <td><strong>' . $d['nombre_jours'] . '</strong></td>
            <td><span class="badge ' . $cls . '">' . labelStatut($d['statut']) . '</span></td>
        </tr>';
    }
    echo '</tbody></table>
    <div class="footer">Évasio — Application de gestion des congés</div>
    </body></html>';
} else {
    header('Location:' . APP_URL . '/rapports.php');
    exit;
}
