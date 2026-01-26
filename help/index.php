<?php
// templates/header.php

// NE PAS DÉMARRER LA SESSION ICI - elle doit déjà être démarrée
// Vérifier que la session existe
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour générer les URLs
function generateUrl($path) {
    // Retirer le slash initial s'il existe
    $path = ltrim($path, '/');
    return '/pressing_manager/' . $path;
}

// Récupérer les données utilisateur
$username = $_SESSION['username'] ?? 'Utilisateur';
$role = $_SESSION['nom_role'] ?? $_SESSION['role'] ?? 'Receptionniste';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pressing Manager Pro</title>
    
    <!-- Meta tags -->
    <meta name="description" content="Système de gestion de pressing">
    <meta name="author" content="Pressing Manager">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= generateUrl('images/compta.ico') ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?= generateUrl('css/style.css') ?>">
    
    <!-- CSS spécifique à la page -->
    <?php if (isset($page_css)): ?>
    <link rel="stylesheet" href="<?= generateUrl($page_css) ?>">
    <?php endif; ?>
    
    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: #2c3e50 !important;
        }
        
        .user-info {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .user-role {
            background-color: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<!-- Barre de navigation supérieure -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= generateUrl('pages/dashboard.php') ?>">
            <i class="fas fa-tshirt me-2"></i>
            Pressing Manager Pro
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= htmlspecialchars($username) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li class="dropdown-item-text">
                            <div class="user-info">
                                <strong><?= htmlspecialchars($username) ?></strong><br>
                                <span class="user-role"><?= htmlspecialchars($role) ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= generateUrl('pages/utilisateurs/mon_compte.php') ?>">
                                <i class="fas fa-user me-2"></i>Mon compte
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= generateUrl('pages/help/index.php') ?>">
                                <i class="fas fa-question-circle me-2"></i>Aide
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= generateUrl('index.php?logout=1') ?>">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Conteneur principal -->
<div class="container-fluid">
    <div class="row">