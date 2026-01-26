<?php
// templates/navigation.php

// 1. Start output buffering to prevent "headers already sent" errors
ob_start();

// 2. Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (strpos($errstr, 'headers already sent') !== false) {
        ob_clean();
        echo "<p style='color:red; text-align:center;'>Erreur : Une erreur de session est survenue. Veuillez cliquer sur Déconnecter et vous reconnecter.</p>";
        exit();
    }
    return false;
});

// 3. Session management and inactivity check
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier l'inactivité (30 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > 1800) {
    session_unset();
    session_destroy();
    header('Location: /pressing_manager/index.php');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 4. Core navigation logic
$estConnecte = isset($_SESSION['utilisateur_id']);

// Obtenir le mois en cours en français
$moisEnCours = "Non défini";
if (class_exists('IntlDateFormatter')) {
    $formatter = new IntlDateFormatter(
        'fr_FR',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'Europe/Paris',
        IntlDateFormatter::GREGORIAN,
        'MMMM yyyy'
    );
    $moisEnCours = $formatter->format(time());
} else {
    $monthNamesFr = [
        'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars', 
        'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
        'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
        'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
    ];
    $englishMonth = date('F Y');
    $moisEnCours = strtr($englishMonth, $monthNamesFr);
}

// Fonction pour générer les URLs
function generateUrl($path) {
    return '/pressing_manager/' . ltrim($path, '/');
}

// Récupérer l'URI courante
$current_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/pressing_manager/';
$relative_uri = str_replace($base_path, '', $current_uri);
$current_page_basename = basename($_SERVER['PHP_SELF']);

// Fonction pour vérifier si un lien est actif
function isActive($link_path, $current_uri_relative) {
    $link_uri = parse_url(generateUrl($link_path), PHP_URL_PATH);
    $link_uri_relative = str_replace('/pressing_manager/', '', $link_uri);

    if ($current_uri_relative === $link_uri_relative) {
        return true;
    }

    $parent_folder = dirname($link_path);
    if (strpos($current_uri_relative, $parent_folder . '/') === 0 && !empty($parent_folder) && $parent_folder !== '.') {
        return true;
    }

    return false;
}

// 5. Database and permissions setup
require_once(__DIR__ . '/../fonctions/database.php');

// Fonction de connexion à la base de données
function getDatabaseConnection() {
    try {
        $db = new PDO('mysql:host=localhost;dbname=pressing_manager;charset=utf8', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        error_log("Erreur de connexion à la base de données: " . $e->getMessage());
        return null;
    }
}

// Fonction pour vérifier les permissions selon le rôle
function hasPermission($role, $requiredPermission) {
    $permissions = [
        'patron' => ['all', 'dashboard', 'gestion_tickets', 'gestion_clients', 'gestion_stock', 'gestion_caisse', 'rapports', 'administration', 'gestion_comptes', 'gestion_factures', 'gestion_fournisseurs', 'gestion_agences', 'audit', 'abonnements', 'atelier', 'boutique', 'client_portal', 'consommables', 'environnement', 'fidelite', 'intelligent_dashboard', 'livraison', 'maintenance', 'marketing', 'notifications', 'paiements_online', 'partenariats', 'qualite', 'reservation_online', 'tracabilite', 'urgences'],
        'Responsable' => ['dashboard', 'gestion_tickets', 'gestion_clients', 'gestion_stock', 'gestion_caisse', 'rapports', 'gestion_comptes', 'gestion_factures', 'abonnements', 'atelier', 'consommables', 'fidelite', 'intelligent_dashboard', 'livraison', 'maintenance', 'qualite', 'tracabilite', 'urgences'],
        'Réceptionniste' => ['gestion_tickets', 'gestion_clients', 'caisse', 'abonnements', 'fidelite', 'reservation_online', 'tracabilite', 'urgences'],
        'Technicien' => ['gestion_tickets', 'gestion_stock', 'atelier', 'maintenance', 'tracabilite'],
        'Caissier' => ['caisse', 'gestion_tickets', 'paiements_online', 'fidelite'],
        'gestionnaire_stock' => ['dashboard', 'gestion_stock', 'consommables', 'atelier'],
        'employe_pressing' => ['gestion_tickets', 'atelier', 'tracabilite']
    ];
    
    if (!isset($permissions[$role])) {
        return false;
    }
    
    if (in_array('all', $permissions[$role])) {
        return true;
    }
    
    return in_array($requiredPermission, $permissions[$role]);
}

$version = "2.0.0";
$roleUtilisateur = $_SESSION['nom_role'] ?? $_SESSION['role'] ?? 'Réceptionniste';

if ($estConnecte):
    // Récupérer les informations utilisateur
    $user_info = [];
    try {
        $db = getDatabaseConnection();
        if ($db) {
            $user_id = $_SESSION['utilisateur_id'];
            $stmt = $db->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des informations utilisateur: " . $e->getMessage());
        $user_info = ['nom' => 'Utilisateur', 'prenom' => '', 'email' => ''];
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Navigation Pressing Manager</title>
    
    <!-- Bootstrap CSS local -->
    <link rel="stylesheet" href="<?= generateUrl('css/bootstrap.min.css') ?>">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?= generateUrl('css/style.css') ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= generateUrl('css/font-awesome.min.css') ?>">
    
    <style>
        /* Variables CSS */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --transition-speed: 0.3s;
        }

        /* Reset et base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-bg);
            transition: padding-left var(--transition-speed);
            padding-left: var(--sidebar-width);
            min-height: 100vh;
        }

        body.nav-collapsed {
            padding-left: var(--sidebar-collapsed-width);
        }

        /* Sidebar principale */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, #1a252f 100%);
            color: white;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            transition: width var(--transition-speed);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        /* Header sidebar */
        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 80px;
        }

        .app-title {
            font-size: 1.2rem;
            font-weight: 600;
            white-space: nowrap;
            opacity: 1;
            transition: opacity var(--transition-speed);
        }

        .sidebar.collapsed .app-title {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        /* Bouton toggle */
        .toggle-btn {
            background: var(--secondary-color);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-speed);
            flex-shrink: 0;
        }

        .toggle-btn:hover {
            background: #2980b9;
            transform: rotate(180deg);
        }

        .toggle-btn .icon {
            font-size: 18px;
            transition: transform var(--transition-speed);
        }

        /* Navigation */
        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-title {
            padding: 10px 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            font-weight: 600;
            white-space: nowrap;
            opacity: 1;
            transition: opacity var(--transition-speed);
        }

        .sidebar.collapsed .nav-title {
            opacity: 0;
            height: 0;
            overflow: hidden;
            padding: 0;
            margin: 0;
        }

        /* Items de navigation */
        .nav-item {
            position: relative;
            margin: 2px 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--secondary-color);
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .nav-text {
            margin-left: 0;
            opacity: 1;
            transition: opacity var(--transition-speed);
            font-size: 0.95rem;
            flex: 1;
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            margin: 0;
        }

        /* Indicateur pour dropdown */
        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown > .nav-link::after {
            content: '▾';
            margin-left: auto;
            font-size: 14px;
            transition: transform var(--transition-speed);
        }

        .nav-dropdown.open > .nav-link::after {
            transform: rotate(180deg);
        }

        /* Sous-menu */
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            margin: 5px 15px;
        }

        .nav-dropdown.open .submenu {
            max-height: 500px;
        }

        .submenu-item {
            margin: 2px 0;
        }

        .submenu-link {
            display: flex;
            align-items: center;
            padding: 10px 15px 10px 45px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.9rem;
            position: relative;
        }

        .submenu-link:hover {
            background: rgba(255,255,255,0.05);
            color: white;
            padding-left: 50px;
        }

        .submenu-link.active {
            color: white;
            background: rgba(52, 152, 219, 0.2);
            font-weight: 500;
        }

        .submenu-link.active::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background: var(--secondary-color);
            border-radius: 50%;
        }

        .submenu-icon {
            width: 16px;
            text-align: center;
            font-size: 14px;
            margin-right: 8px;
        }

        /* Footer sidebar */
        .sidebar-footer {
            padding: 20px 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
            position: sticky;
            bottom: 0;
        }

        .user-info {
            margin-bottom: 15px;
        }

        .user-name {
            font-weight: 600;
            color: white;
            font-size: 0.95rem;
            white-space: nowrap;
            opacity: 1;
            transition: opacity var(--transition-speed);
        }

        .user-role {
            color: var(--secondary-color);
            font-size: 0.85rem;
            margin-top: 3px;
        }

        .sidebar-info {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            margin-bottom: 2px;
        }

        .info-value {
            color: var(--secondary-color);
        }

        .sidebar.collapsed .user-name,
        .sidebar.collapsed .user-role,
        .sidebar.collapsed .sidebar-info {
            opacity: 0;
            height: 0;
            overflow: hidden;
            margin: 0;
        }

        /* Badge pour nouvelles fonctionnalités */
        .nav-badge {
            background: var(--accent-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: auto;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            :root {
                --sidebar-width: 240px;
            }
        }

        @media (max-width: 992px) {
            :root {
                --sidebar-width: 220px;
                --sidebar-collapsed-width: 70px;
            }
            
            body {
                padding-left: var(--sidebar-width);
            }
            
            body.nav-collapsed {
                padding-left: var(--sidebar-collapsed-width);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: fixed;
                bottom: 0;
                top: auto;
                left: 0;
                right: 0;
                border-top: 1px solid rgba(255,255,255,0.1);
                transform: translateY(calc(100% - 60px));
                transition: transform 0.3s;
                z-index: 1001;
            }
            
            .sidebar.open {
                transform: translateY(0);
            }
            
            body {
                padding-left: 0;
                padding-bottom: 60px;
            }
            
            body.nav-collapsed {
                padding-left: 0;
            }
            
            .sidebar-nav {
                max-height: 50vh;
                overflow-y: auto;
                padding: 10px 0;
            }
            
            .nav-section {
                margin-bottom: 15px;
            }
            
            .nav-title {
                padding: 8px 15px;
                font-size: 0.7rem;
            }
            
            .sidebar-header {
                display: none;
            }
            
            .sidebar-footer {
                display: none;
            }
            
            .submenu-link {
                padding: 8px 15px 8px 35px;
            }
            
            .mobile-toggle {
                position: fixed;
                bottom: 10px;
                right: 10px;
                z-index: 1002;
                background: var(--secondary-color);
                color: white;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                border: none;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .nav-link {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .submenu-link {
                font-size: 0.85rem;
            }
        }

        /* Scrollbar personnalisée */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }

        /* États de chargement */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Tooltip pour sidebar réduite */
        .sidebar.collapsed .nav-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            z-index: 1001;
            margin-left: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            pointer-events: none;
        }

        .sidebar.collapsed .nav-link:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Animation d'entrée */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .nav-item {
            animation: slideIn 0.3s ease-out;
            animation-fill-mode: both;
        }

        .nav-item:nth-child(1) { animation-delay: 0.1s; }
        .nav-item:nth-child(2) { animation-delay: 0.15s; }
        .nav-item:nth-child(3) { animation-delay: 0.2s; }
        .nav-item:nth-child(4) { animation-delay: 0.25s; }
        .nav-item:nth-child(5) { animation-delay: 0.3s; }
    </style>
</head>
<body>

<!-- Sidebar pour desktop -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2 class="app-title">Pressing Manager</h2>
        <button class="toggle-btn" id="toggleDesktop">
            <span class="icon">←</span>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Section Principale -->
        <div class="nav-section">
            <div class="nav-title">Navigation Principale</div>
            
            <?php if (hasPermission($roleUtilisateur, 'dashboard')): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/dashboard.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/dashboard.php') ?>"
                   data-tooltip="Tableau de Bord">
                    <i class="nav-icon fa fa-tachometer-alt"></i>
                    <span class="nav-text">Tableau de Bord</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'intelligent_dashboard')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/intelligent_dashboard/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Dashboard Intelligent">
                    <i class="nav-icon fa fa-chart-line"></i>
                    <span class="nav-text">Dashboard Intelligent</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/intelligent_dashboard/temps_reel.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/intelligent_dashboard/temps_reel.php') ?>">
                        <i class="submenu-icon fa fa-clock"></i>
                        <span>Temps Réel</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/intelligent_dashboard/alertes_automatiques.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/intelligent_dashboard/alertes_automatiques.php') ?>">
                        <i class="submenu-icon fa fa-bell"></i>
                        <span>Alertes Automatiques</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/intelligent_dashboard/analyse_rentabilite.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/intelligent_dashboard/analyse_rentabilite.php') ?>">
                        <i class="submenu-icon fa fa-chart-pie"></i>
                        <span>Analyse Rentabilité</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section Opérations -->
        <div class="nav-section">
            <div class="nav-title">Opérations</div>
            
            <?php if (hasPermission($roleUtilisateur, 'gestion_tickets')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/Tickets/') === 0 || strpos($relative_uri, 'pages/reception/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Gestion des Tickets">
                    <i class="nav-icon fa fa-ticket-alt"></i>
                    <span class="nav-text">Tickets</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/Tickets/create.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/Tickets/create.php') ?>">
                        <i class="submenu-icon fa fa-plus-circle"></i>
                        <span>Nouveau Ticket</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/Tickets/list.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/Tickets/list.php') ?>">
                        <i class="submenu-icon fa fa-list"></i>
                        <span>Liste des Tickets</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/Tickets/pending.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/Tickets/pending.php') ?>">
                        <i class="submenu-icon fa fa-clock"></i>
                        <span>En attente</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/reception/receptionniste.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reception/receptionniste.php') ?>">
                        <i class="submenu-icon fa fa-user-tie"></i>
                        <span>Réception</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'tracabilite')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/tracabilite/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Traçabilité">
                    <i class="nav-icon fa fa-qrcode"></i>
                    <span class="nav-text">Traçabilité</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/tracabilite/etiquettes_rfid.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/tracabilite/etiquettes_rfid.php') ?>">
                        <i class="submenu-icon fa fa-rfid"></i>
                        <span>Étiquettes RFID</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/tracabilite/scan_qrcode.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/tracabilite/scan_qrcode.php') ?>">
                        <i class="submenu-icon fa fa-qrcode"></i>
                        <span>Scan QR Code</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/tracabilite/photos_avant_apres.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/tracabilite/photos_avant_apres.php') ?>">
                        <i class="submenu-icon fa fa-camera"></i>
                        <span>Photos Avant/Après</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'atelier')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/atelier/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Atelier">
                    <i class="nav-icon fa fa-industry"></i>
                    <span class="nav-text">Atelier</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/atelier/planning_machines.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/atelier/planning_machines.php') ?>">
                        <i class="submenu-icon fa fa-calendar-alt"></i>
                        <span>Planning Machines</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/atelier/suivi_production.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/atelier/suivi_production.php') ?>">
                        <i class="submenu-icon fa fa-chart-line"></i>
                        <span>Suivi Production</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/atelier/controle_qualite.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/atelier/controle_qualite.php') ?>">
                        <i class="submenu-icon fa fa-check-circle"></i>
                        <span>Contrôle Qualité</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'maintenance')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/maintenance/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Maintenance">
                    <i class="nav-icon fa fa-tools"></i>
                    <span class="nav-text">Maintenance</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/maintenance/calendrier.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/maintenance/calendrier.php') ?>">
                        <i class="submenu-icon fa fa-calendar"></i>
                        <span>Planning Maintenance</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/maintenance/interventions.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/maintenance/interventions.php') ?>">
                        <i class="submenu-icon fa fa-wrench"></i>
                        <span>Suivi Interventions</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'qualite')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/qualite/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Qualité">
                    <i class="nav-icon fa fa-award"></i>
                    <span class="nav-text">Qualité</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/qualite/reclamations.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/qualite/reclamations.php') ?>">
                        <i class="submenu-icon fa fa-exclamation-circle"></i>
                        <span>Réclamations</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/qualite/satisfaction.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/qualite/satisfaction.php') ?>">
                        <i class="submenu-icon fa fa-smile"></i>
                        <span>Satisfaction Clients</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section Clients -->
        <div class="nav-section">
            <div class="nav-title">Clients & Ventes</div>
            
            <?php if (hasPermission($roleUtilisateur, 'gestion_clients')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/clients/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Gestion des Clients">
                    <i class="nav-icon fa fa-users"></i>
                    <span class="nav-text">Clients</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/clients/create.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/clients/create.php') ?>">
                        <i class="submenu-icon fa fa-user-plus"></i>
                        <span>Nouveau Client</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/clients/list.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/clients/list.php') ?>">
                        <i class="submenu-icon fa fa-list"></i>
                        <span>Liste Clients</span>
                    </a>
                    <?php if (hasPermission($roleUtilisateur, 'gestion_comptes')): ?>
                    <a class="submenu-link <?= isActive('pages/clients/comptes_gestion.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/clients/comptes_gestion.php') ?>">
                        <i class="submenu-icon fa fa-wallet"></i>
                        <span>Comptes Clients</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'abonnements')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/abonnements/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Abonnements">
                    <i class="nav-icon fa fa-calendar-check"></i>
                    <span class="nav-text">Abonnements</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/abonnements/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/abonnements/index.php') ?>">
                        <i class="submenu-icon fa fa-list"></i>
                        <span>Liste Abonnements</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/abonnements/ajouter.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/abonnements/ajouter.php') ?>">
                        <i class="submenu-icon fa fa-plus"></i>
                        <span>Nouvel Abonnement</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'fidelite')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/fidélite/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Programme Fidélité">
                    <i class="nav-icon fa fa-gift"></i>
                    <span class="nav-text">Fidélité</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/fidélite/cartes.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/fidélite/cartes.php') ?>">
                        <i class="submenu-icon fa fa-address-card"></i>
                        <span>Cartes Fidélité</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/fidélite/promotions.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/fidélite/promotions.php') ?>">
                        <i class="submenu-icon fa fa-percentage"></i>
                        <span>Promotions</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'caisse')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/caisse/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Caisse">
                    <i class="nav-icon fa fa-cash-register"></i>
                    <span class="nav-text">Caisse</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/caisse/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/caisse/index.php') ?>">
                        <i class="submenu-icon fa fa-home"></i>
                        <span>Accueil Caisse</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/caisse/encaisser.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/caisse/encaisser.php') ?>">
                        <i class="submenu-icon fa fa-money-bill-wave"></i>
                        <span>Encaisser</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/caisse/mon_compte.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/caisse/mon_compte.php') ?>">
                        <i class="submenu-icon fa fa-user-circle"></i>
                        <span>Mon Compte</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'gestion_factures')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/factures/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Factures">
                    <i class="nav-icon fa fa-file-invoice-dollar"></i>
                    <span class="nav-text">Factures</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/factures/creer.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/factures/creer.php') ?>">
                        <i class="submenu-icon fa fa-plus-circle"></i>
                        <span>Créer Facture</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/factures/liste.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/factures/liste.php') ?>">
                        <i class="submenu-icon fa fa-list"></i>
                        <span>Liste Factures</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section Stock -->
        <div class="nav-section">
            <div class="nav-title">Stock & Inventaire</div>
            
            <?php if (hasPermission($roleUtilisateur, 'gestion_stock')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/stock/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Gestion du Stock">
                    <i class="nav-icon fa fa-boxes"></i>
                    <span class="nav-text">Stock</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/stock/add_product.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/stock/add_product.php') ?>">
                        <i class="submenu-icon fa fa-plus-square"></i>
                        <span>Ajouter Produit</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/stock/inventory.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/stock/inventory.php') ?>">
                        <i class="submenu-icon fa fa-clipboard-list"></i>
                        <span>Inventaire</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/stock/stock_history.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/stock/stock_history.php') ?>">
                        <i class="submenu-icon fa fa-history"></i>
                        <span>Historique</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'consommables')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/consommables/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Consommables">
                    <i class="nav-icon fa fa-flask"></i>
                    <span class="nav-text">Consommables</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/consommables/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/consommables/index.php') ?>">
                        <i class="submenu-icon fa fa-list"></i>
                        <span>Liste Consommables</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/consommables/alertes.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/consommables/alertes.php') ?>">
                        <i class="submenu-icon fa fa-exclamation-triangle"></i>
                        <span>Alertes Stock</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'gestion_fournisseurs')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/Fournisseurs/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Fournisseurs">
                    <i class="nav-icon fa fa-truck-loading"></i>
                    <span class="nav-text">Fournisseurs</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/Fournisseurs/ajouter.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/Fournisseurs/ajouter.php') ?>">
                        <i class="submenu-icon fa fa-plus"></i>
                        <span>Ajouter Fournisseur</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/Fournisseurs/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/Fournisseurs/index.php') ?>">
                        <i class="submenu-icon fa fa-list"></i>
                        <span>Liste Fournisseurs</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section Services -->
        <div class="nav-section">
            <div class="nav-title">Services & Digital</div>
            
            <?php if (hasPermission($roleUtilisateur, 'reservation_online')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/reservation_en_ligne/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Réservation en Ligne">
                    <i class="nav-icon fa fa-calendar-alt"></i>
                    <span class="nav-text">Réservation</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/reservation_en_ligne/creneaux.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reservation_en_ligne/creneaux.php') ?>">
                        <i class="submenu-icon fa fa-clock"></i>
                        <span>Gestion Créneaux</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/reservation_en_ligne/rdv.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reservation_en_ligne/rdv.php') ?>">
                        <i class="submenu-icon fa fa-calendar-check"></i>
                        <span>Rendez-vous</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'client_portal')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/client_portal/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Portail Client">
                    <i class="nav-icon fa fa-user-circle"></i>
                    <span class="nav-text">Portail Client</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/client_portal/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/client_portal/index.php') ?>">
                        <i class="submenu-icon fa fa-home"></i>
                        <span>Accueil Portail</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/client_portal/suivi_commande.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/client_portal/suivi_commande.php') ?>">
                        <i class="submenu-icon fa fa-search"></i>
                        <span>Suivi Commande</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'paiements_online')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/paiements_en_ligne/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Paiements en Ligne">
                    <i class="nav-icon fa fa-credit-card"></i>
                    <span class="nav-text">Paiements Online</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/paiements_en_ligne/paiements.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/paiements_en_ligne/paiements.php') ?>">
                        <i class="submenu-icon fa fa-money-check-alt"></i>
                        <span>Interface Paiement</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/paiements_en_ligne/mobile_money.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/paiements_en_ligne/mobile_money.php') ?>">
                        <i class="submenu-icon fa fa-mobile-alt"></i>
                        <span>Mobile Money</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'livraison')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/livraison/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Livraison">
                    <i class="nav-icon fa fa-truck"></i>
                    <span class="nav-text">Livraison</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/livraison/tournees.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/livraison/tournees.php') ?>">
                        <i class="submenu-icon fa fa-route"></i>
                        <span>Planning Tournées</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/livraison/suivi_gps.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/livraison/suivi_gps.php') ?>">
                        <i class="submenu-icon fa fa-map-marker-alt"></i>
                        <span>Suivi GPS</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'urgences')): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/urgences/express.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/urgences/express.php') ?>"
                   data-tooltip="Services Urgences">
                    <i class="nav-icon fa fa-bolt"></i>
                    <span class="nav-text">Service Express</span>
                    <span class="nav-badge">NEW</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'boutique')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/boutique/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Boutique en Ligne">
                    <i class="nav-icon fa fa-store"></i>
                    <span class="nav-text">Boutique</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/boutique/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/boutique/index.php') ?>">
                        <i class="submenu-icon fa fa-shopping-bag"></i>
                        <span>Catalogue Produits</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/boutique/commandes.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/boutique/commandes.php') ?>">
                        <i class="submenu-icon fa fa-shopping-cart"></i>
                        <span>Gestion Commandes</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section Marketing -->
        <div class="nav-section">
            <div class="nav-title">Marketing & Communication</div>
            
            <?php if (hasPermission($roleUtilisateur, 'marketing')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/marketing/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Marketing">
                    <i class="nav-icon fa fa-bullhorn"></i>
                    <span class="nav-text">Marketing</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/marketing/emailing.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/marketing/emailing.php') ?>">
                        <i class="submenu-icon fa fa-envelope"></i>
                        <span>Campagnes Email</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/marketing/avis_clients.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/marketing/avis_clients.php') ?>">
                        <i class="submenu-icon fa fa-comment"></i>
                        <span>Avis Clients</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'notifications')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/notifications/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Notifications">
                    <i class="nav-icon fa fa-bell"></i>
                    <span class="nav-text">Notifications</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/notifications/sms.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/notifications/sms.php') ?>">
                        <i class="submenu-icon fa fa-sms"></i>
                        <span>Envoi SMS</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/notifications/email_automatiques.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/notifications/email_automatiques.php') ?>">
                        <i class="submenu-icon fa fa-envelope-open"></i>
                        <span>Emails Automatiques</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section Administration -->
        <?php if (hasPermission($roleUtilisateur, 'administration')): ?>
        <div class="nav-section">
            <div class="nav-title">Administration</div>
            
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/admin/') === 0 || strpos($relative_uri, 'pages/utilisateurs/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Administration">
                    <i class="nav-icon fa fa-cogs"></i>
                    <span class="nav-text">Administration</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/admin/utilisateurs/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/utilisateurs/index.php') ?>">
                        <i class="submenu-icon fa fa-users"></i>
                        <span>Utilisateurs</span>
                    </a>
                    <?php if (hasPermission($roleUtilisateur, 'gestion_agences')): ?>
                    <a class="submenu-link <?= isActive('pages/admin/agences/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/agences/index.php') ?>">
                        <i class="submenu-icon fa fa-building"></i>
                        <span>Agences</span>
                    </a>
                    <?php endif; ?>
                    <a class="submenu-link <?= isActive('pages/admin/services.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/services.php') ?>">
                        <i class="submenu-icon fa fa-concierge-bell"></i>
                        <span>Services</span>
                    </a>
                    <?php if (hasPermission($roleUtilisateur, 'audit')): ?>
                    <a class="submenu-link <?= isActive('pages/admin/audit/view_activity_log.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/audit/view_activity_log.php') ?>">
                        <i class="submenu-icon fa fa-clipboard-list"></i>
                        <span>Logs Activité</span>
                    </a>
                    <?php endif; ?>
                    <a class="submenu-link <?= isActive('pages/admin/configuration/backup.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/configuration/backup.php') ?>">
                        <i class="submenu-icon fa fa-database"></i>
                        <span>Sauvegarde</span>
                    </a>
                </div>
            </div>

            <?php if (hasPermission($roleUtilisateur, 'rapports')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/reports/') === 0 || strpos($relative_uri, 'pages/reporting/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Rapports">
                    <i class="nav-icon fa fa-chart-bar"></i>
                    <span class="nav-text">Rapports</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/reports/daily.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reports/daily.php') ?>">
                        <i class="submenu-icon fa fa-calendar-day"></i>
                        <span>Journalier</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/reporting/balance.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reporting/balance.php') ?>">
                        <i class="submenu-icon fa fa-balance-scale"></i>
                        <span>Balance</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/reporting/profit_loss.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reporting/profit_loss.php') ?>">
                        <i class="submenu-icon fa fa-chart-line"></i>
                        <span>Profit & Perte</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'gestion_comptes')): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/comptes/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Comptes">
                    <i class="nav-icon fa fa-wallet"></i>
                    <span class="nav-text">Comptes</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/comptes/comptes_gestion.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/comptes/comptes_gestion.php') ?>">
                        <i class="submenu-icon fa fa-cog"></i>
                        <span>Gestion Comptes</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/comptes/soldes.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/comptes/soldes.php') ?>">
                        <i class="submenu-icon fa fa-balance-scale"></i>
                        <span>Soldes Clients</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'partenariats')): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/partenariats/index.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/partenariats/index.php') ?>"
                   data-tooltip="Partenariats">
                    <i class="nav-icon fa fa-handshake"></i>
                    <span class="nav-text">Partenariats</span>
                    <span class="nav-badge">NEW</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasPermission($roleUtilisateur, 'environnement')): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/environnement/index.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/environnement/index.php') ?>"
                   data-tooltip="Environnement">
                    <i class="nav-icon fa fa-leaf"></i>
                    <span class="nav-text">Environnement</span>
                    <span class="nav-badge">NEW</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/settings/index.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/settings/index.php') ?>"
                   data-tooltip="Paramètres">
                    <i class="nav-icon fa fa-sliders-h"></i>
                    <span class="nav-text">Paramètres</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section Utilisateur -->
        <div class="nav-section">
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/utilisateurs/mon_compte.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/utilisateurs/mon_compte.php') ?>"
                   data-tooltip="Mon Compte">
                    <i class="nav-icon fa fa-user-cog"></i>
                    <span class="nav-text">Mon Compte</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a class="nav-link" href="<?= generateUrl('fonctions/deconnexion.php') ?>"
                   onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');"
                   data-tooltip="Déconnexion">
                    <i class="nav-icon fa fa-sign-out-alt" style="color: #e74c3c;"></i>
                    <span class="nav-text" style="color: #e74c3c;">Déconnexion</span>
                </a>
            </div>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars(($user_info['prenom'] ?? '') . ' ' . ($user_info['nom'] ?? 'Utilisateur')) ?></div>
            <div class="user-role"><?= htmlspecialchars($roleUtilisateur) ?></div>
        </div>
        
        <div class="sidebar-info">
            <div class="info-label">Période</div>
            <div class="info-value"><?= htmlspecialchars($moisEnCours) ?></div>
        </div>
        
        <div class="sidebar-info">
            <div class="info-label">Version</div>
            <div class="info-value"><?= htmlspecialchars($version) ?></div>
        </div>
    </div>
</aside>

<!-- Bouton mobile -->
<button class="mobile-toggle" id="toggleMobile" style="display: none;">
    ☰
</button>

<!-- jQuery local -->
<script src="<?= generateUrl('js/jquery-3.7.1.min.js') ?>"></script>

<script>
$(document).ready(function() {
    const sidebar = $('#sidebar');
    const body = $('body');
    const toggleDesktop = $('#toggleDesktop');
    const toggleMobile = $('#toggleMobile');
    
    // Vérifier si on est sur mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Gestion du toggle desktop
    toggleDesktop.on('click', function(e) {
        e.preventDefault();
        sidebar.toggleClass('collapsed');
        body.toggleClass('nav-collapsed');
        
        // Sauvegarder l'état dans localStorage
        localStorage.setItem('sidebarCollapsed', sidebar.hasClass('collapsed'));
    });
    
    // Gestion du toggle mobile
    toggleMobile.on('click', function(e) {
        e.preventDefault();
        sidebar.toggleClass('open');
    });
    
    // Gestion des dropdowns
    $('.nav-dropdown > .nav-link').on('click', function(e) {
        if (!isMobile() && !sidebar.hasClass('collapsed')) {
            e.preventDefault();
            const dropdown = $(this).parent();
            dropdown.toggleClass('open');
            
            // Fermer les autres dropdowns
            $('.nav-dropdown').not(dropdown).removeClass('open');
        }
    });
    
    // Fermer les dropdowns en cliquant à l'extérieur
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.nav-dropdown').length) {
            $('.nav-dropdown').removeClass('open');
        }
    });
    
    // Gestion responsive
    function handleResponsive() {
        if (isMobile()) {
            sidebar.removeClass('collapsed');
            body.removeClass('nav-collapsed');
            toggleDesktop.hide();
            toggleMobile.show();
            $('.nav-dropdown').removeClass('open');
        } else {
            toggleDesktop.show();
            toggleMobile.hide();
            sidebar.removeClass('open');
            
            // Restaurer l'état du sidebar depuis localStorage
            const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (wasCollapsed) {
                sidebar.addClass('collapsed');
                body.addClass('nav-collapsed');
            }
        }
    }
    
    // Initialiser le responsive
    handleResponsive();
    
    // Surveiller les changements de taille
    $(window).on('resize', handleResponsive);
    
    // Animation smooth pour les liens actifs
    $('.nav-link, .submenu-link').on('click', function() {
        const href = $(this).attr('href');
        if (href && href !== '#') {
            sidebar.addClass('loading');
            setTimeout(() => {
                sidebar.removeClass('loading');
            }, 300);
        }
    });
    
    // Gestion du hover pour desktop
    if (!isMobile()) {
        $('.nav-link').hover(
            function() {
                if (sidebar.hasClass('collapsed')) {
                    $(this).addClass('hover');
                }
            },
            function() {
                $(this).removeClass('hover');
            }
        );
    }
    
    // Empêcher la fermeture du sidebar sur mobile quand on clique à l'intérieur
    sidebar.on('click', function(e) {
        if (isMobile()) {
            e.stopPropagation();
        }
    });
    
    // Fermer le sidebar mobile en cliquant à l'extérieur
    $(document).on('click', function(e) {
        if (isMobile() && !$(e.target).closest('.sidebar').length && !$(e.target).is('#toggleMobile')) {
            sidebar.removeClass('open');
        }
    });
    
    // Initialiser les tooltips
    $('.nav-link[data-tooltip]').each(function() {
        const tooltip = $(this).attr('data-tooltip');
        $(this).attr('title', tooltip);
    });
});
</script>

<?php else: ?>
    <!-- Navigation pour utilisateurs non connectés -->
    <style>
        .guest-nav {
            background: var(--primary-color);
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .guest-nav nav {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .guest-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .guest-link:hover {
            background: var(--secondary-color);
        }
    </style>
    
    <div class="guest-nav">
        <nav>
            <a class="guest-link" href="<?= generateUrl('index.php') ?>">Accueil</a>
            <a class="guest-link" href="<?= generateUrl('pages/authentification.php') ?>">Connexion</a>
        </nav>
    </div>
<?php endif; ?>

<?php
// Restore error handler and flush buffer
restore_error_handler();
ob_end_flush();
?>
</body>
</html>