<?php
// pages/admin/profils/index.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}



// Vérification des permissions
$roleUtilisateur = $_SESSION['role'] ?? 'Réceptionniste';
$allowedRoles = ['patron', 'Responsable', 'Administrateur'];
if (!in_array($roleUtilisateur, $allowedRoles)) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

require_once('../../../fonctions/database.php');
require_once('../../../fonctions/gestion_profils.php');

$title = "Gestion des Rôles";

$profils = [];
$errorMessage = $_SESSION['admin_message_error'] ?? null;
$successMessage = $_SESSION['admin_message_success'] ?? null;
unset($_SESSION['admin_message_error'], $_SESSION['admin_message_success']);

try {
    $profils = getTousLesProfils($pdo);
} catch (Exception $e) {
    $errorMessage = "Erreur : " . htmlspecialchars($e->getMessage());
}

include('../../../templates/header.php');
include('../../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= generateUrl('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= generateUrl('assets/css/all.min.css') ?>">
    <style>
        :root { --primary-color: #2c3e50; --accent-color: #3498db; }
        body { background-color: #f4f7f6; }
        .main-content { margin-left: 230px; padding: 30px; transition: all 0.3s; }
        body.collapsed-sidebar .main-content { margin-left: 70px; }
        @media (max-width: 992px) { .main-content { margin-left: 0 !important; } }
        .table-custom { background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-action { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 5px; margin: 0 2px; }
    </style>
</head>
<body>

<br> <br>
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-shield me-2"></i>Rôles & Permissions</h2>
        <a href="<?= generateUrl('pages/admin/profils/ajouter.php') ?>" class="btn btn-primary">
             Nouveau Rôle
        </a>
    </div>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= $successMessage ?></div>
    <?php endif; ?>

    <div class="table-responsive table-custom">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nom du Rôle</th>
                    <th>Niveau</th>
                    <th>Utilisateurs</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profils as $profil): 
                    $niv = $profil['niveau_permission'] ?? 1;
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($profil['nom_role']) ?></strong></td>
                    <td><span class="badge bg-info text-dark">Niveau <?= $niv ?></span></td>
                    <td><?= $profil['nombre_utilisateurs'] ?> membre(s)</td>
                    <td class="text-end">
                        <a href="<?= generateUrl('pages/admin/profils/dupliquer.php?id=' . $profil['id_role']) ?>" class="btn-action btn btn-outline-success" title="Dupliquer">
                            Dupliquer
                        </a>
                        <a href="<?= generateUrl('pages/admin/profils/modifier.php?id=' . $profil['id_role']) ?>" class="btn-action btn btn-outline-primary" title="Modifier">
                           &nbsp;&nbsp; &nbsp; &nbsp; &nbsp;Modifier
                        </a>
                        <a href="<?= generateUrl('pages/admin/profils/assigner_utilisateurs.php?id=' . $profil['id_role']) ?>" class="btn-action btn btn-outline-warning" title="Assigner des membres">
                           &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assigner
                        </a>
                        <?php if ($profil['nombre_utilisateurs'] == 0): ?>
                        <a href="<?= generateUrl('pages/admin/profils/supprimer.php?id=' . $profil['id_role']) ?>" class="btn-action btn btn-outline-danger" onclick="return confirm('Supprimer ?')">
                          &nbsp;  &nbsp; &nbsp; &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Supprimer
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="<?= generateUrl('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>