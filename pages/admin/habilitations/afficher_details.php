<?php
// pages/admin/habilitations/afficher_details.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_habilitations.php');

$id_role = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_role) {
    die("ID de rôle invalide.");
}

// Récupérer les infos du rôle
$role = getRoleParId($pdo, $id_role);

// Récupérer la liste des utilisateurs ayant ce rôle
$sql = "SELECT nom_complet, login_utilisateur, est_actif FROM utilisateurs WHERE id_role = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_role]);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titre = "Détails du Rôle";
include(__DIR__ . '/../../../templates/header.php');
include(__DIR__ . '/../../../templates/navigation.php');
?>
<br> <br> <br>
<div class="habilitations-container p-4" style="margin-left: 230px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h5 class="mb-0">Détails : <?= htmlspecialchars($role['nom_role']) ?></h5>
            <a href="index.php" class="btn btn-sm btn-light">Retour</a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Niveau de permission :</strong> 
                        <span class="badge bg-info"><?= getPermissionLevelText($role['niveau_permission']) ?></span>
                    </p>
                    <p><strong>Description :</strong><br>
                        <?= nl2br(htmlspecialchars($role['description'] ?? 'Aucune description')) ?>
                    </p>
                </div>
                <div class="col-md-6 border-start">
                    <h6>Utilisateurs assignés (<?= count($utilisateurs) ?>)</h6>
                    <?php if (empty($utilisateurs)): ?>
                        <p class="text-muted small">Aucun utilisateur n'est actuellement assigné à ce rôle.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($utilisateurs as $user): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($user['nom_complet']) ?>
                                    <span class="badge <?= $user['est_actif'] ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                        <?= $user['est_actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../../templates/footer.php'); ?>