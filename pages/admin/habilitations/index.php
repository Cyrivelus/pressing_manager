<?php
// pages/admin/habilitations/index.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Inclure d'abord le fichier qui contient generateUrl() et la configuration de base
// Ajustez le chemin vers votre fichier de fonctions générales
require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_habilitations.php');

/** * Note : Si generateUrl n'existe toujours pas dans vos fichiers fonctions, 
 * voici une version de secours pour éviter l'erreur fatale.
 */


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

// Configuration de la page
$titre = "Gestion des Habilitations (Rôles)";

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
        .habilitations-container {
            margin-left: 230px; /* Aligné avec votre index précédent */
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        body.collapsed-sidebar .habilitations-container { margin-left: 70px; }
        @media (max-width: 992px) { .habilitations-container { margin-left: 0 !important; } }
        
        .card { border-radius: 10px; border: 1px solid #e0e0e0; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .badge-permission { font-size: 0.75em; padding: 5px 10px; border-radius: 20px; }
        .permission-1 { background-color: #6c757d; color: white; }
        .permission-2 { background-color: #28a745; color: white; }
        .permission-3 { background-color: #ffc107; color: black; }
        .permission-4 { background-color: #dc3545; color: white; }
        .permission-5 { background-color: #6610f2; color: white; }
        
        .description-cell { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .stats-card { transition: transform 0.2s; border: none; }
        .stats-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

<div class="habilitations-container">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/dashboard.php') ?>">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Habilitations</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-shield text-primary me-2"></i>Habilitations & Rôles</h2>
                <div>
                    <a href="<?= generateUrl('pages/admin/profils/ajouter.php') ?>" class="btn btn-primary">
                        Nouveau Rôle
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($flash_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php 
    // On suppose que cette fonction existe dans vos fichiers inclus
    $stats = function_exists('getStatistiquesHabilitations') ? getStatistiquesHabilitations($pdo) : ['total_roles'=>0,'utilisateurs_avec_role'=>0,'utilisateurs_sans_role'=>0,'total_utilisateurs'=>0]; 
    ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white stats-card p-3">
                <small>Total Rôles</small>
                <h3 class="mb-0"><?= $stats['total_roles'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white stats-card p-3">
                <small>Utilisateurs assignés</small>
                <h3 class="mb-0"><?= $stats['utilisateurs_avec_role'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark stats-card p-3">
                <small>Sans rôle</small>
                <h3 class="mb-0"><?= $stats['utilisateurs_sans_role'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white stats-card p-3">
                <small>Total Personnel</small>
                <h3 class="mb-0"><?= $stats['total_utilisateurs'] ?></h3>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white">
            <h5 class="mb-0">Profils de permissions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nom du Rôle</th>
                        <th>Description</th>
                        <th>Niveau</th>
                        <th>Membres</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($habilitationsProfils as $role): 
                        $niv = $role['niveau_permission'] ?? 1;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($role['Nom_Profil']) ?></strong></td>
                        <td class="description-cell" title="<?= htmlspecialchars($role['description']) ?>">
                            <?= htmlspecialchars($role['description']) ?>
                        </td>
                        <td>
                            <span class="badge badge-permission permission-<?= $niv ?>">Niveau <?= $niv ?></span>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= $role['nombre_utilisateurs'] ?? 0 ?> membre(s)</span></td>
                        <td class="text-end">
                            <a href="<?= generateUrl('pages/admin/profils/modifier.php?id=' . $role['ID_Habilitation_Profil']) ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                            <?php if (($role['nombre_utilisateurs'] ?? 0) == 0): ?>
                                <a href="<?= generateUrl('pages/admin/profils/supprimer.php?id=' . $role['ID_Habilitation_Profil']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce rôle ?')">Supprimer</a>
                            <?php endif; ?>
                            <td class="text-end">
    <a href="<?= generateUrl('pages/admin/habilitations/afficher_details.php?id=' . $role['ID_Habilitation_Profil']) ?>" 
       class="btn btn-sm btn-outline-info">
       Afficher les détails
    </a>
    
    <a href="<?= generateUrl('pages/admin/profils/modifier.php?id=' . $role['ID_Habilitation_Profil']) ?>" 
       class="btn btn-sm btn-outline-primary">
       
    </a>

    <?php if (($role['nombre_utilisateurs'] ?? 0) == 0): ?>
        <a href="<?= generateUrl('pages/admin/profils/supprimer.php?id=' . $role['ID_Habilitation_Profil']) ?>" 
           class="btn btn-sm btn-outline-danger" 
           onclick="return confirm('Supprimer ce rôle ?')">
           
        </a>
    <?php endif; ?>
</td>
                        </td>
                        
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-light border shadow-sm">
        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Aide aux niveaux de permissions</h6>
        <div class="row small">
            <div class="col-md-4"><strong>Niv 1-2 :</strong> Personnel de production</div>
            <div class="col-md-4"><strong>Niv 3 :</strong> Réception & Caisse</div>
            <div class="col-md-4"><strong>Niv 4-5 :</strong> Direction & Admin</div>
        </div>
    </div>
</div>

<script src="<?= generateUrl('../js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
<?php include(__DIR__ . '/../../../templates/footer.php'); ?>