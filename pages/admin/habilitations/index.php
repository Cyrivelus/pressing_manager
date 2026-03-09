<?php
// pages/admin/habilitations/index.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_habilitations.php');

// Vérification des permissions
$roleUtilisateur = $_SESSION['role'] ?? 'Réceptionniste';
$allowedRoles = ['patron', 'Responsable'];
if (!in_array($roleUtilisateur, $allowedRoles)) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

// Récupérer les données
$habilitationsProfils = getHabilitationsProfilsAvecDetails($pdo);
$habilitationsUtilisateurs = getHabilitationsUtilisateursAvecDetails($pdo);

// Gérer les messages flash
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$titre = "Gestion des Habilitations";

include(__DIR__ . '/../../../templates/header.php');
include(__DIR__ . '/../../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?></title>
    <link rel="stylesheet" href="<?= generateUrl('../css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= generateUrl('../css/all.min.css') ?>">
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #6c757d;
            --admin-border: #dee2e6;
            --admin-bg: #f8f9fa;
        }

        body { background-color: var(--admin-bg); color: #333; font-family: 'Inter', sans-serif; }

        .habilitations-container {
            margin-left: 130px;
            padding: 30px;
            transition: all 0.3s;
        }
        
        /* Structure de carte professionnelle */
        .card { border-radius: 4px; border: 1px solid var(--admin-border); background: #fff; box-shadow: none; margin-bottom: 20px; }
        .card-header { background-color: #fff; border-bottom: 1px solid var(--admin-border); padding: 15px 20px; }
        
        /* Stats Cards épurées */
        .stats-card { border-left: 4px solid var(--admin-primary); padding: 15px; background: #fff; }
        .stats-card small { color: var(--admin-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .stats-card h3 { color: var(--admin-primary); font-weight: 700; margin-top: 5px; }
        .border-success { border-left-color: #28a745 !important; }
        .border-warning { border-left-color: #ffc107 !important; }

        /* Badges sobres */
        .badge-permission { 
            font-size: 0.8em; 
            padding: 4px 12px; 
            border-radius: 2px; 
            background: #f1f3f5; 
            color: var(--admin-primary); 
            border: 1px solid #dee2e6;
            font-weight: 500;
        }
        
        .table thead th { 
            background-color: #fcfcfc; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            color: var(--admin-secondary);
            border-bottom: 2px solid var(--admin-border);
        }

        .btn { border-radius: 2px; text-transform: uppercase; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.3px; }
        .btn-primary { background-color: var(--admin-primary); border-color: var(--admin-primary); }
        
        .description-cell { max-width: 300px; color: var(--admin-secondary); font-size: 0.9rem; }
        
        /* Breadcrumb */
        .breadcrumb { background: transparent; padding: 0; margin-bottom: 15px; }
        .breadcrumb-item a { color: var(--admin-secondary); text-decoration: none; }

        @media (max-width: 992px) { .habilitations-container { margin-left: 0 !important; } }
    </style>
</head>
<body>

<br> <br> <br>
<div class="habilitations-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/dashboard.php') ?>">Administration</a></li>
                    <li class="breadcrumb-item active">Habilitations</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark m-0">Gestion des Accès</h2>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?= generateUrl('pages/admin/profils/ajouter.php') ?>" class="btn btn-primary px-4 shadow-sm">
                Nouveau Rôle
            </a>
        </div>
    </div>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_type === 'error' ? 'danger' : 'light' ?> border alert-dismissible fade show">
            <?= htmlspecialchars($flash_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php 
    $stats = function_exists('getStatistiquesHabilitations') ? getStatistiquesHabilitations($pdo) : ['total_roles'=>0,'utilisateurs_avec_role'=>0,'utilisateurs_sans_role'=>0,'total_utilisateurs'=>0]; 
    ?>
    <div class="row mb-2">
        <div class="col-md-3">
            <div class="card stats-card">
                <small>Total Rôles</small>
                <h3 class="mb-0"><?= $stats['total_roles'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-success">
                <small>Assignés</small>
                <h3 class="mb-0"><?= $stats['utilisateurs_avec_role'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-warning">
                <small>Non-assignés</small>
                <h3 class="mb-0"><?= $stats['utilisateurs_sans_role'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card" style="border-left-color: #17a2b8;">
                <small>Total Personnel</small>
                <h3 class="mb-0"><?= $stats['total_utilisateurs'] ?></h3>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold">Profils de permissions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Nom du Rôle</th>
                        <th>Description</th>
                        <th>Niveau</th>
                        <th>Effectif</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($habilitationsProfils as $role): 
                        $niv = $role['niveau_permission'] ?? 1;
                    ?>
                    <tr>
                        <td class="ps-4"><strong><?= htmlspecialchars($role['Nom_Profil']) ?></strong></td>
                        <td class="description-cell"><?= htmlspecialchars($role['description']) ?></td>
                        <td>
                            <span class="badge-permission">Niveau <?= $niv ?></span>
                        </td>
                        <td><span class="text-muted small"><?= $role['nombre_utilisateurs'] ?? 0 ?> membre(s)</span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="<?= generateUrl('pages/admin/habilitations/afficher_details.php?id=' . $role['ID_Habilitation_Profil']) ?>" class="btn btn-sm btn-outline-secondary" title="Détails">Détails</a>
                                <a href="<?= generateUrl('pages/admin/habilitations/attribuer.php?id=' . $role['ID_Habilitation_Profil']) ?>" class="btn btn-sm btn-outline-secondary">Assigner</a>
                                <a href="<?= generateUrl('pages/admin/profils/modifier.php?id=' . $role['ID_Habilitation_Profil']) ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <?php if (($role['nombre_utilisateurs'] ?? 0) == 0): ?>
                                    <a href="<?= generateUrl('pages/admin/profils/supprimer.php?id=' . $role['ID_Habilitation_Profil']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce rôle ?')">Supprimer</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-light border rounded-0 small text-muted">
        
        <strong>Légende :</strong> 
        Niv 1-2 : Production | 
        Niv 3 : Réception & Caisse | 
        Niv 4-5 : Direction & Administration.
    </div>
</div>

<script src="<?= generateUrl('../js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
<?php include(__DIR__ . '/../../../templates/footer.php'); ?>