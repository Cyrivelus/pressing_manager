<?php
// pages/admin/index.php

// Démarrer la session pour gérer l'authentification
session_start();

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
    <title>BailCompta 360 | Administration</title>
    <link rel="shortcut icon" href="../../images/logo_bailcompta.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/bootstrap-3.4.1.min.css">
    <style>
        /* CONFIGURATION DES 3 COULEURS PRINCIPALES */
        :root {
            --primary-color: #2c3e50;    /* Bleu Marine Professionnel */
            --accent-color: #3498db;     /* Bleu Action */
            --bg-light: #f4f7f6;         /* Gris très clair de fond */
            --border-color: #e0e6ed;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .admin-dashboard-wrapper {
            margin-left: 150px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 40px;
            transition: all 0.3s ease;
        }
        
        .page-header {
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 20px;
            margin-bottom: 40px;
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* STYLE DES CARTES (EXIT LES DÉGRADÉS) */
        .panel-custom {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 30px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 280px;
            display: flex;
            flex-direction: column;
        }

        .panel-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .panel-custom .panel-heading {
            background: transparent;
            padding: 25px 20px 10px 20px;
            border: none;
        }

        .panel-custom .panel-title {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 600;
            text-align: left;
        }

        .panel-custom .panel-body {
            padding: 10px 20px 25px 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .panel-custom p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        /* BOUTONS ÉPURÉS */
        .btn-admin {
            background-color: #ffffff;
            color: var(--accent-color);
            border: 1.5px solid var(--accent-color);
            border-radius: 5px;
            padding: 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }

        .btn-admin:hover {
            background-color: var(--accent-color);
            color: #ffffff;
            text-decoration: none;
        }

        /* LIGNE DE DÉCORATION SOBRE AU SOMMET DE CHAQUE CARTE */
        .card-accent {
            height: 4px;
            width: 100%;
            background: var(--accent-color);
            border-radius: 8px 8px 0 0;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .admin-dashboard-wrapper { margin-left: 0; width: 100%; padding: 20px; }
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<br> <br> <br>
<div class="admin-dashboard-wrapper">
    <div class="container-fluid">
        <h2 class="page-header">Tableau de bord de gestion</h2>

        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="panel-custom">
                    <div class="card-accent"></div>
                    <div class="panel-heading">
                        <h3 class="panel-title">Utilisateurs</h3>
                    </div>
                    <div class="panel-body">
                        <p>Administration complète des comptes : création, droits d'accès et réinitialisations.</p>
                        <a href="utilisateurs/" class="btn btn-admin btn-block">Gérer les <br> membres</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="panel-custom">
                    <div class="card-accent"></div>
                    <div class="panel-heading">
                        <h3 class="panel-title">Profils</h3>
                    </div>
                    <div class="panel-body">
                        <p>Définition des rôles métiers et des niveaux de visibilité dans l'application.</p>
                        <a href="profils/" class="btn btn-admin btn-block">Gérer les profils</a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="panel-custom">
                    <div class="card-accent"></div>
                    <div class="panel-heading">
                        <h3 class="panel-title">Habilitations</h3>
                    </div>
                    <div class="panel-body">
                        <p>Contrôle précis des permissions par module et sécurité des accès critiques.</p>
                        <a href="habilitations/" class="btn btn-admin btn-block">Gérer les accès</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="panel-custom">
                    <div class="card-accent"></div>
                    <div class="panel-heading">
                        <h3 class="panel-title">Configuration</h3>
                    </div>
                    <div class="panel-body">
                        <p>Paramètres généraux du système, maintenance et variables globales.</p>
                        <a href="configuration/" class="btn btn-admin btn-block">Paramètres <br> système</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery-3.6.0.js" defer></script>
<script src="../js/bootstrap-3.4.1.min.js" defer></script>

<?php
require_once('../../templates/footer.php');
?>