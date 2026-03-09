<?php
// templates/navigation.php - Version Multi-Activités (Pressing/Commerce/Hôtel)

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

// Fonction pour vérifier les permissions selon le rôle (DYNAMIQUE depuis la BD)
function hasPermission($role, $requiredPermission) {
    // Si c'est le patron, il a toutes les permissions
    if ($role === 'patron') {
        return true;
    }
    
    // Sinon, vérifier dans la base de données
    try {
        $db = getDatabaseConnection();
        if (!$db) return false;
        
        // Récupérer l'ID du rôle
        $stmt = $db->prepare("SELECT id_role FROM roles WHERE nom_role = ?");
        $stmt->execute([$role]);
        $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$roleData) return false;
        
        $roleId = $roleData['id_role'];
        
        // Vérifier si le rôle a la permission
        $stmt = $db->prepare("
            SELECT COUNT(*) as has_permission 
            FROM role_permissions rp
            JOIN permissions p ON rp.id_permission = p.id_permission
            WHERE rp.id_role = ? AND p.code_permission = ?
        ");
        $stmt->execute([$roleId, $requiredPermission]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result && $result['has_permission'] > 0);
        
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification des permissions: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer toutes les permissions d'un rôle
function getUserPermissions($role) {
    $permissions = [];
    
    // Si c'est le patron, retourner toutes les permissions
    if ($role === 'patron') {
        $allPermissions = [
            'dashboard', 'gestion_tickets', 'gestion_clients', 'gestion_stock', 
            'gestion_caisse', 'rapports', 'administration', 'gestion_comptes', 
            'gestion_factures', 'gestion_fournisseurs', 'gestion_agences', 
            'audit', 'abonnements', 'atelier', 'boutique', 'client_portal', 
            'consommables', 'environnement', 'fidelite', 'intelligent_dashboard', 
            'livraison', 'maintenance', 'marketing', 'notifications', 
            'paiements_online', 'partenariats', 'qualite', 'reservation_online', 
            'tracabilite', 'urgences', 'hotellerie', 'reservations_hotel',
            'gestion_chambres', 'service_chambre', 'gestion_commerce'
        ];
        return array_fill_keys($allPermissions, true);
    }
    
    // Sinon, récupérer depuis la base de données
    try {
        $db = getDatabaseConnection();
        if (!$db) return $permissions;
        
        // Récupérer l'ID du rôle
        $stmt = $db->prepare("SELECT id_role FROM roles WHERE nom_role = ?");
        $stmt->execute([$role]);
        $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$roleData) return $permissions;
        
        $roleId = $roleData['id_role'];
        
        // Récupérer toutes les permissions du rôle
        $stmt = $db->prepare("
            SELECT p.code_permission 
            FROM role_permissions rp
            JOIN permissions p ON rp.id_permission = p.id_permission
            WHERE rp.id_role = ?
        ");
        $stmt->execute([$roleId]);
        $permissionCodes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        return array_fill_keys($permissionCodes, true);
        
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des permissions utilisateur: " . $e->getMessage());
        return $permissions;
    }
}

// Fonction pour récupérer l'activité configurée pour l'utilisateur
function getUserActivityType($userId) {
    try {
        $db = getDatabaseConnection();
        if (!$db) return 'pressing'; // Par défaut
        
        $stmt = $db->prepare("
            SELECT type_activite 
            FROM utilisateur_activites 
            WHERE id_utilisateur = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['type_activite'] : 'pressing';
        
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération du type d'activité: " . $e->getMessage());
        return 'pressing';
    }
}

// Fonction pour vérifier si l'activité est activée
function isActivityEnabled($activityType) {
    try {
        $db = getDatabaseConnection();
        if (!$db) return true; // Par défaut activé
        
        $stmt = $db->prepare("
            SELECT actif 
            FROM activites_config 
            WHERE type_activite = ?
        ");
        $stmt->execute([$activityType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (bool)$result['actif'] : true;
        
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de l'activité: " . $e->getMessage());
        return true;
    }
}

$version = "3.0.0";
$roleUtilisateur = $_SESSION['nom_role'] ?? $_SESSION['role'] ?? 'Réceptionniste';
$userId = $_SESSION['utilisateur_id'] ?? null;

// Récupérer le type d'activité de l'utilisateur
$userActivity = $userId ? getUserActivityType($userId) : 'pressing';

if ($estConnecte):
    // Récupérer les informations utilisateur
    $user_info = [];
    try {
        $db = getDatabaseConnection();
        if ($db) {
            $stmt = $db->prepare("SELECT nom_complet, email FROM utilisateurs WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des informations utilisateur: " . $e->getMessage());
        $user_info = ['nom_complet' => 'Utilisateur', 'email' => ''];
    }
    
    // Récupérer toutes les permissions de l'utilisateur courant
    $userPermissions = getUserPermissions($roleUtilisateur);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Navigation Kayade Manager</title>
    
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
            --accent-pressing: #e74c3c;
            --accent-commerce: #27ae60;
            --accent-hotel: #f39c12;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --sidebar-width: 300px;
            --sidebar-collapsed-width: 80px;
            --transition-speed: 0.3s;
        }

        /* Indicateur d'activité */
        .activity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 5px;
            text-transform: uppercase;
        }
        
        .activity-pressing {
            background-color: var(--accent-pressing);
            color: white;
        }
        
        .activity-commerce {
            background-color: var(--accent-commerce);
            color: white;
        }
        
        .activity-hotel {
            background-color: var(--accent-hotel);
            color: white;
        }

        /* Style différent pour chaque activité */
        .nav-section.pressing-section .nav-title {
            border-left: 4px solid var(--accent-pressing);
        }
        
        .nav-section.commerce-section .nav-title {
            border-left: 4px solid var(--accent-commerce);
        }
        
        .nav-section.hotel-section .nav-title {
            border-left: 4px solid var(--accent-hotel);
        }

        /* Badge d'activité dans le header */
        .user-activity {
            display: inline-block;
            margin-left: 10px;
            font-size: 0.8rem;
            opacity: 0.8;
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
            padding-left: 15px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            margin-right: 4px;
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
            background: var(--accent-pressing);
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
<br><br><br>
    <div class="sidebar-header">
    <h2 class="app-title">Kayade<span>Manager</span>
        <?php 
        // On récupère le rôle de l'utilisateur (par défaut 'caissier' s'il n'est pas défini pour plus de sécurité)
        $userRole = $_SESSION['role'] ?? 'caissier'; 

        // On n'affiche le badge QUE si le rôle n'est PAS 'caissier'
        if ($userRole !== 'caissier'): 
        ?>
            <span class="user-activity activity-badge activity-<?= htmlspecialchars($userActivity) ?>">
                <?= strtoupper(htmlspecialchars($userActivity)) ?>
            </span>
        <?php endif; ?>
    </h2>
    <button class="toggle-btn" id="toggleDesktop" title="Réduire le menu">
        <span class="icon">←</span>
    </button>
</div>
    <nav class="sidebar-nav">
        <!-- Section Principale -->
        <div class="nav-section">
            <div class="nav-title">Navigation Principale</div>
            
            <?php if (isset($userPermissions['dashboard'])): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/dashboard.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/dashboard.php') ?>"
                   data-tooltip="Tableau de Bord">
                   <i class="nav-icon">📊</i>
                    <span class="nav-text">Tableau de Bord</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['intelligent_dashboard'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/intelligent_dashboard/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Dashboard Intelligent">
                    <i class="nav-icon">🧠</i>
                    <span class="nav-text">Dashboard Intelligent</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/intelligent_dashboard/temps_reel.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/intelligent_dashboard/temps_reel.php') ?>">
                        <i class="submenu-icon">⚡</i>
                        <span>Temps Réel</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/intelligent_dashboard/alertes_automatiques.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/intelligent_dashboard/alertes_automatiques.php') ?>">
                        <i class="submenu-icon">🚨</i>
                        <span>Alertes Automatiques</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/intelligent_dashboard/analyse_rentabilite.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/intelligent_dashboard/analyse_rentabilite.php') ?>">
                        <i class="submenu-icon">💰</i>
                        <span>Analyse Rentabilité</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- SECTION PRESSING (Activité principale) -->
        <?php if (isActivityEnabled('pressing') && ($userActivity == 'pressing' || $userActivity == 'all')): ?>
        <div class="nav-section pressing-section">
            <div class="nav-title">Pressing & Blanchisserie</div>
            
            <?php if (isset($userPermissions['gestion_tickets'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/Tickets/') === 0 || strpos($relative_uri, 'pages/reception/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Gestion des Tickets">
                    <i class="nav-icon">🎫</i>
                    <span class="nav-text">Tickets Pressing</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/Tickets/create.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/Tickets/create.php') ?>">
                        <i class="submenu-icon">➕</i>
                        <span>Nouveau Ticket</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/Tickets/list.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/Tickets/list.php') ?>">
                        <i class="submenu-icon">📋</i>
                        <span>Liste des Tickets</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/reception/receptionniste.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reception/receptionniste.php') ?>">
                        <i class="submenu-icon">🏢</i>
                        <span>Réception</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['atelier'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/atelier/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Atelier">
                    <i class="nav-icon">⚙️</i>
                    <span class="nav-text">Atelier Pressing</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/atelier/planning_machines.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/atelier/planning_machines.php') ?>">
                        <i class="submenu-icon">📅</i>
                        <span>Planning Machines</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/atelier/suivi_production.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/atelier/suivi_production.php') ?>">
                        <i class="submenu-icon">📈</i>
                        <span>Suivi Production</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['tracabilite'])): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/tracabilite/scan_qrcode.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/tracabilite/scan_qrcode.php') ?>"
                   data-tooltip="Traçabilité">
                    <i class="nav-icon">🔍</i>
                    <span class="nav-text">Traçabilité</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['livraison'])): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/livraison/tournees.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/livraison/tournees.php') ?>"
                   data-tooltip="Livraison">
                    <i class="nav-icon">🚚</i>
                    <span class="nav-text">Livraison</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- SECTION COMMERCE -->
        <?php if (isActivityEnabled('commerce') && ($userActivity == 'commerce' || $userActivity == 'all')): ?>
        <div class="nav-section commerce-section">
            <div class="nav-title">Boutique & Commerce</div>
            
            <?php if (isset($userPermissions['gestion_commerce'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/boutique/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Boutique">
                    <i class="nav-icon">🛒</i>
                    <span class="nav-text">Boutique</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/boutique/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/boutique/index.php') ?>">
                        <i class="submenu-icon">📦</i>
                        <span>Catalogue Produits</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/boutique/commandes.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/boutique/commandes.php') ?>">
                        <i class="submenu-icon">📋</i>
                        <span>Gestion Commandes</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/boutique/statistiques.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/boutique/statistiques.php') ?>">
                        <i class="submenu-icon">📊</i>
                        <span>Statistiques Ventes</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['gestion_stock'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/stock/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Stock Commerce">
                    <i class="nav-icon">📦</i>
                    <span class="nav-text">Stock Commerce</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/stock/add_product.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/stock/add_product.php') ?>">
                        <i class="submenu-icon">➕</i>
                        <span>Ajouter Produit</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/stock/inventory.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/stock/inventory.php') ?>">
                        <i class="submenu-icon">📊</i>
                        <span>Inventaire</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- SECTION HÔTEL -->
        <?php if (isActivityEnabled('hotel') && ($userActivity == 'hotel' || $userActivity == 'all')): ?>
        <div class="nav-section hotel-section">
            <div class="nav-title">Hôtel & Services</div>
            
            <?php if (isset($userPermissions['hotellerie'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/partenariats/hotellerie/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Gestion Hôtel">
                    <i class="nav-icon">🏨</i>
                    <span class="nav-text">Hôtel</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/partenariats/hotellerie.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/partenariats/hotellerie.php') ?>">
                        <i class="submenu-icon">📋</i>
                        <span>Gestion Hôtel</span>
                    </a>
                    <?php if (isset($userPermissions['gestion_chambres'])): ?>
                    <a class="submenu-link <?= isActive('pages/hotel/chambres.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/hotel/chambres.php') ?>">
                        <i class="submenu-icon">🛏️</i>
                        <span>Chambres</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isset($userPermissions['reservations_hotel'])): ?>
                    <a class="submenu-link <?= isActive('pages/hotel/reservations.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/hotel/reservations.php') ?>">
                        <i class="submenu-icon">📅</i>
                        <span>Réservations</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['service_chambre'])): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/hotel/service_chambre.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/hotel/service_chambre.php') ?>"
                   data-tooltip="Service Chambre">
                    <i class="nav-icon">🧹</i>
                    <span class="nav-text">Service Chambre</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- SECTION CLIENTS (Commun à toutes les activités) -->
        <div class="nav-section">
            <div class="nav-title">Clients & Ventes</div>
            
            <?php if (isset($userPermissions['gestion_clients'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/clients/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Gestion des Clients">
                    <i class="nav-icon">👥</i>
                    <span class="nav-text">Clients</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/clients/ajouter_client.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/clients/ajouter_client.php') ?>">
                        <i class="submenu-icon">➕</i>
                        <span>Nouveau Client</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/clients/list.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/clients/list.php') ?>">
                        <i class="submenu-icon">📋</i>
                        <span>Liste Clients</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/clients/ajouter_client.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/clients/ajouter_client.php') ?>">
                        <i class="submenu-icon">🏷️</i>
                        <span>Catégoriser Client</span>
                        <span class="activity-badge activity-<?= $userActivity ?>"><?= strtoupper(substr($userActivity, 0, 1)) ?></span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['gestion_caisse'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/caisse/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Caisse">
                    <i class="nav-icon">💰</i>
                    <span class="nav-text">Caisse</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/caisse/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/caisse/index.php') ?>">
                        <i class="submenu-icon">🏠</i>
                        <span>Accueil Caisse</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/caisse/encaisser.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/caisse/encaisser.php') ?>">
                        <i class="submenu-icon">💵</i>
                        <span>Encaisser</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['gestion_factures'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/factures/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Factures">
                    <i class="nav-icon">🧾</i>
                    <span class="nav-text">Facturation</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/factures/creer.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/factures/creer.php') ?>">
                        <i class="submenu-icon">📝</i>
                        <span>Créer Facture</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/factures/liste.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/factures/liste.php') ?>">
                        <i class="submenu-icon">📋</i>
                        <span>Factures</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($userPermissions['paiements_online'])): ?>
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/paiements_en_ligne/paiements.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/paiements_en_ligne/paiements.php') ?>"
                   data-tooltip="Paiements">
                    <i class="nav-icon">💳</i>
                    <span class="nav-text">Paiements</span>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- SECTION ADMINISTRATION -->
        <?php if (isset($userPermissions['administration'])): ?>
        <div class="nav-section">
            <div class="nav-title">Administration</div>
            
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/admin/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Administration">
                    <i class="nav-icon">⚙️</i>
                    <span class="nav-text">Administration</span>
                </a>
                <div class="submenu">
                    <!-- Configuration des activités -->
                    <a class="submenu-link <?= isActive('pages/admin/configuration/activites.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/configuration/activites.php') ?>">
                        <i class="submenu-icon">🏢</i>
                        <span>Activités</span>
                    </a>
                    
                    <!-- Profils utilisateurs spécifiques -->
                    <a class="submenu-link <?= isActive('pages/admin/profils/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/profils/index.php') ?>">
                        <i class="submenu-icon">👤</i>
                        <span>Profils Métier</span>
                    </a>
                    
                    <!-- Tarification par activité -->
                    <a class="submenu-link <?= isActive('pages/admin/tarifs/activites.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/tarifs/activites.php') ?>">
                        <i class="submenu-icon">💰</i>
                        <span>Tarifs par Activité</span>
                    </a>
                    
                    <!-- Modèles de factures -->
                    <a class="submenu-link <?= isActive('pages/admin/factures/modeles.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/factures/modeles.php') ?>">
                        <i class="submenu-icon">🧾</i>
                        <span>Modèles Factures</span>
                    </a>
                    
                    <!-- Utilisateurs -->
                    <a class="submenu-link <?= isActive('pages/admin/utilisateurs/index.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/admin/utilisateurs/index.php') ?>">
                        <i class="submenu-icon">👥</i>
                        <span>Utilisateurs</span>
                    </a>
                </div>
            </div>

            <?php if (isset($userPermissions['rapports'])): ?>
            <div class="nav-item nav-dropdown <?= strpos($relative_uri, 'pages/reporting/') === 0 ? 'open' : '' ?>">
                <a class="nav-link" href="#" data-tooltip="Rapports">
                    <i class="nav-icon">📊</i>
                    <span class="nav-text">Rapports</span>
                </a>
                <div class="submenu">
                    <a class="submenu-link <?= isActive('pages/reporting/par_activite.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reporting/par_activite.php') ?>">
                        <i class="submenu-icon">🏢</i>
                        <span>Par Activité</span>
                    </a>
                    <a class="submenu-link <?= isActive('pages/reporting/balance.php', $relative_uri) ? 'active' : '' ?>" 
                       href="<?= generateUrl('pages/reporting/balance.php') ?>">
                        <i class="submenu-icon">⚖️</i>
                        <span>Balance</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/settings/index.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/settings/index.php') ?>"
                   data-tooltip="Paramètres">
                   <i class="nav-icon">⚙️</i>
                    <span class="nav-text">Paramètres</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- SECTION UTILISATEUR -->
        <div class="nav-section">
            <div class="nav-item">
                <a class="nav-link <?= isActive('pages/utilisateurs/mon_compte.php', $relative_uri) ? 'active' : '' ?>" 
                   href="<?= generateUrl('pages/utilisateurs/mon_compte.php') ?>"
                   data-tooltip="Mon Compte">
                   <i class="nav-icon">👤</i>
                    <span class="nav-text">Mon Compte</span>
                </a>
            </div>
           
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user_info['nom_complet'] ?? 'Utilisateur') ?></div>
            <div class="user-role"><?= htmlspecialchars($roleUtilisateur) ?></div>
        </div>
        <div class="sidebar-info">
            <div class="info-group">
                <span class="info-label">V.</span>
                <span class="info-value"><?= htmlspecialchars($version) ?></span>
            </div>
            <div class="info-group">
                <span class="info-label">Pér. :</span>
                <span class="info-value"><?= htmlspecialchars($moisEnCours) ?></span>
            </div>
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
    
    // Sélection automatique de l'activité
    function setActiveActivity() {
        const activity = '<?= $userActivity ?>';
        $('.activity-badge').removeClass('activity-all');
        $('.activity-badge').addClass('activity-' + activity);
    }
    
    // Initialiser l'activité
    setActiveActivity();
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
</html>cd