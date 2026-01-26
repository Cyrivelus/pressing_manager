<?php
// pages/admin/index.php

// Démarrer la session pour gérer l'authentification
session_start();

// Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'Admin') {
    // Si l'utilisateur n'est pas connecté ou n'est pas un administrateur,
    // le rediriger vers la page de connexion ou une page d'erreur.
    header("Location: ../index.php?error=Accès non autorisé");
    exit();
}

// Inclure les fichiers nécessaires
require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_utilisateurs.php';

$titre = 'Tableau de Bord de l\'Administration';
$current_page = basename($_SERVER['PHP_SELF']);

require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BailCompta 360 | Tableau de bord administrateur</title>
    <link rel="shortcut icon" href="../../images/logo_bailcompta.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/tableau.css">
    <style>
        .admin-dashboard-wrapper {
            margin-left: 250px; /* Marge pour la sidebar */
            width: calc(100% - 250px); /* Largeur moins la sidebar */
            min-height: 100vh;
            padding: 30px 20px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .page-header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
        }
        
        .panel {
            height: 100%;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
            background: white;
        }
        
        .panel:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .panel-heading {
            padding: 20px;
            border-radius: 10px 10px 0 0 !important;
            border-bottom: none;
            text-align: center;
        }
        
        .panel-body {
            padding: 25px 20px;
            height: auto;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .panel-body p {
            flex-grow: 1;
            margin-bottom: 20px;
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .panel-title {
            font-size: 16px;
            font-weight: bold;
            color: white;
            margin: 0;
            line-height: 1.3;
        }
        
        .btn-block {
            border-radius: 6px;
            padding: 10px 0;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-block:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Couleurs des panels */
        .panel-primary .panel-heading { background: linear-gradient(135deg, #3498db, #2980b9); }
        .panel-success .panel-heading { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .panel-info .panel-heading { background: linear-gradient(135deg, #1abc9c, #16a085); }
        .panel-warning .panel-heading { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .panel-danger .panel-heading { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .panel-default .panel-heading { background: linear-gradient(135deg, #7f8c8d, #34495e); }
        
        /* Couleurs des boutons */
        .btn-info { background: linear-gradient(135deg, #3498db, #2980b9); }
        .btn-success { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .btn-warning { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .btn-danger { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        .btn-info:hover { background: linear-gradient(135deg, #2980b9, #3498db); }
        .btn-success:hover { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .btn-warning:hover { background: linear-gradient(135deg, #e67e22, #f39c12); }
        .btn-danger:hover { background: linear-gradient(135deg, #c0392b, #e74c3c); }
        
        /* Responsive design */
        @media (max-width: 1200px) {
            .admin-dashboard-wrapper {
                margin-left: 220px;
                width: calc(100% - 220px);
                padding: 25px 15px;
            }
            
            .page-header {
                font-size: 24px;
            }
            
            .panel-body {
                padding: 20px 15px;
                min-height: 160px;
            }
        }
        
        @media (max-width: 992px) {
            .admin-dashboard-wrapper {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px 15px;
            }
            
            .page-header {
                font-size: 22px;
                margin-bottom: 25px;
            }
            
            .panel-body p {
                font-size: 13px;
            }
        }
        
        @media (max-width: 768px) {
            .admin-dashboard-wrapper {
                margin-left: 0;
                width: 100%;
                padding: 20px 15px;
            }
            
            .page-header {
                font-size: 20px;
                text-align: center;
                margin-bottom: 20px;
            }
            
            .panel {
                margin-bottom: 20px;
            }
            
            .panel-heading {
                padding: 15px;
            }
            
            .panel-body {
                padding: 15px;
                min-height: 140px;
            }
            
            .btn-block {
                padding: 8px 0;
                font-size: 13px;
            }
        }
        
        @media (max-width: 576px) {
            .admin-dashboard-wrapper {
                padding: 15px 10px;
            }
            
            .dashboard-container {
                padding: 0 10px;
            }
            
            .page-header {
                font-size: 18px;
                padding-bottom: 10px;
            }
            
            .panel-heading {
                padding: 12px;
            }
            
            .panel-body {
                padding: 12px;
                min-height: 130px;
            }
            
            .panel-body p {
                font-size: 12px;
                margin-bottom: 15px;
            }
            
            .btn-block {
                padding: 7px 0;
                font-size: 12px;
            }
        }
        
        /* Animation pour le chargement */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .row {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
</head>
<body>

<div class="admin-dashboard-wrapper">
    <div class="dashboard-container">
        <h2 class="page-header">Tableau de bord administrateur</h2>

        <div class="row">
            <!-- Première ligne -->
            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            Gestion des Utilisateurs
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p>Accédez à la gestion des comptes utilisateurs, création, modification, suppression.</p>
                        <a href="utilisateurs/" class="btn btn-info btn-block">
                            Gérer les<br>Utilisateurs
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            Gestion des Profils
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p>Gérez les différents profils d'accès et leurs permissions au sein de l'application.</p>
                        <a href="profils/" class="btn btn-success btn-block">
                            Gérer les Profils
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            Gestion des Habilitations
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p>Gérez les différents profils d'accès et leurs permissions au sein de l'application.</p>
                        <a href="habilitations/" class="btn btn-info btn-block">
                            Gérer les<br>Habilitations
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            Configuration
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p>Gérez les différentes configurations au sein de l'application.</p>
                        <a href="configuration/" class="btn btn-warning btn-block">
                            Accéder à la<br>Configuration
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="../js/jquery-3.6.0.js" defer></script>
<script src="../js/bootstrap-3.4.1.min.js" defer></script>
<script>
    $(document).ready(function() {
        // Animation au survol des panels
        $('.panel').hover(
            function() {
                $(this).css('cursor', 'pointer');
            },
            function() {
                $(this).css('cursor', 'default');
            }
        );
        
        // Ajouter une classe pour le chargement
        $('.admin-dashboard-wrapper').addClass('loaded');
        
        // Adapter la marge en fonction de la taille de l'écran
        function adjustMargin() {
            if ($(window).width() < 768) {
                $('.admin-dashboard-wrapper').css({
                    'margin-left': '0',
                    'width': '100%'
                });
            } else {
                // Si vous avez une sidebar de 250px
                $('.admin-dashboard-wrapper').css({
                    'margin-left': '250px',
                    'width': 'calc(100% - 250px)'
                });
            }
        }
        
        // Appeler la fonction au chargement et au redimensionnement
        adjustMargin();
        $(window).resize(adjustMargin);
    });
</script>

<?php
require_once('../../templates/footer.php');
?>