<?php
require_once __DIR__ . '/includes/functions.php';
requireRole(['rh', 'admin']);
$user = utilisateurCourant();
$db = getDB();
$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'sauvegarder') {
        $id = (int)($_POST['id'] ?? 0);
        $nom = sanitize($_POST['nom'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');
        $couleur = $_POST['couleur'] ?? '#4648d4';
        $maxJ = (int)($_POST['jours_max_annuel'] ?? 30);
        $just = (int)($_POST['justificatif_requis'] ?? 0);
        if (!$nom) {
            $erreur = 'Le nom est obligatoire.';
        } else {
            if ($id) {
                $db->prepare("UPDATE types_conge SET nom=?,description=?,couleur=?,jours_max_annuel=?,justificatif_requis=? WHERE id=?")
                    ->execute([$nom, $desc, $couleur, $maxJ, $just, $id]);
                $success = 'Type modifié avec succès.';
            } else {
                $db->prepare("INSERT INTO types_conge (nom,description,couleur,jours_max_annuel,justificatif_requis) VALUES (?,?,?,?,?)")
                    ->execute([$nom, $desc, $couleur, $maxJ, $just]);
                $success = 'Type créé avec succès.';
            }
            logActivite('type_conge_' . ($id ? 'modifie' : 'cree'), 'Type ' . $nom . ' ' . ($id ? 'modifié' : 'créé'), 'types_conge', $id ?: (int)$db->lastInsertId());
        }
    } elseif ($action === 'toggle' && $_POST['id']) {
        $id = (int)$_POST['id'];
        $s = $db->prepare("SELECT actif FROM types_conge WHERE id=?");
        $s->execute([$id]);
        $cur = (int)$s->fetchColumn();
        $db->prepare("UPDATE types_conge SET actif=? WHERE id=?")->execute([($cur ? 0 : 1), $id]);
        $success = 'Statut modifié.';
    }
}

$types = $db->query("SELECT * FROM types_conge ORDER BY nom")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM types_conge WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$pageTitle = 'Types de congé';
$pageSubtitle = 'Gérer les catégories de congés';
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($erreur): ?><div class="alert alert-error mb-16"><i class="fas fa-exclamation-circle"></i><span><?= h($erreur) ?></span></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i><span><?= h($success) ?></span></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

    <div class="card">
        <div class="card-header">
            <div class="card-title">Liste des types de congé</div>
            <div class="card-subtitle"><?= count($types) ?> type(s)</div>
        </div>
        <?php if (empty($types)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-tags"></i></div>
                <h3>Aucun type configuré</h3>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Couleur</th>
                            <th>Nom</th>
                            <th>Jours max/an</th>
                            <th>Justificatif</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $t): ?>
                            <tr>
                                <td><span style="display:inline-block;width:24px;height:24px;border-radius:50%;background:<?= h($t['couleur']) ?>"></span></td>
                                <td><strong><?= h($t['nom']) ?></strong><?php if ($t['description']): ?><div style="font-size:12px;color:var(--on-surface-variant)"><?= h($t['description']) ?></div><?php endif; ?></td>
                                <td><?= $t['jours_max_annuel'] ?> j</td>
                                <td><?= $t['justificatif_requis'] ? '<span style="color:var(--success)"><i class="fas fa-check"></i> Oui</span>' : '<span style="color:var(--outline)">Non</span>' ?></td>
                                <td><span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:<?= $t['actif'] ? 'var(--success-container)' : 'var(--error-container)' ?>;color:<?= $t['actif'] ? 'var(--success)' : 'var(--error)' ?>"><?= $t['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="?edit=<?= $t['id'] ?>" class="btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
                                        <form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" /><input type="hidden" name="action" value="toggle" /><input type="hidden" name="id" value="<?= $t['id'] ?>" />
                                            <button type="submit" class="btn-icon <?= $t['actif'] ? 'refuse' : '' ?>" title="<?= $t['actif'] ? 'Désactiver' : 'Activer' ?>"><i class="fas <?= $t['actif'] ? 'fa-toggle-off' : 'fa-toggle-on' ?>"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Formulaire -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= $edit ? 'Modifier le type' : 'Nouveau type de congé' ?></div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />
                <input type="hidden" name="action" value="sauvegarder" />
                <input type="hidden" name="id" value="<?= $edit ? $edit['id'] : 0 ?>" />
                <div class="form-group"><label class="form-label">Nom <span class="required">*</span></label>
                    <input type="text" name="nom" class="form-control" value="<?= h($edit ? $edit['nom'] : '') ?>" required placeholder="Ex: Congé annuel" />
                </div>
                <div class="form-group"><label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Description optionnelle"><?= h($edit ? $edit['description'] : '') ?></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Couleur</label>
                        <input type="color" name="couleur" class="form-control" style="height:44px;padding:4px 8px;border-radius:var(--radius-full)" value="<?= h($edit ? $edit['couleur'] : '#4648d4') ?>" />
                    </div>
                    <div class="form-group"><label class="form-label">Jours max/an</label>
                        <input type="number" name="jours_max_annuel" class="form-control" min="1" max="365" value="<?= $edit ? $edit['jours_max_annuel'] : 30 ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="checkbox" name="justificatif_requis" value="1" <?= ($edit && $edit['justificatif_requis']) ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--primary)" />
                        <span class="form-label" style="margin:0">Justificatif obligatoire</span>
                    </label>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <?php if ($edit): ?><a href="<?= APP_URL ?>/types_conge.php" class="btn btn-ghost">Annuler</a><?php endif; ?>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $edit ? 'Modifier' : 'Créer' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>