<?php
// pages/dashboard.php - Tableau de Bord Personnalisé par Rôle
// -----------------------------------------------------------

// 1. Initialisation et Configuration
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
session_start();

// 2. Vérification de l'authentification
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../index.php");
    exit();
}

// 3. Récupération du rôle utilisateur
$user_role = mb_strtolower(trim($_SESSION['role'] ?? ''), 'UTF-8');
$user_name = $_SESSION['nom_complet'] ?? 'Utilisateur';
$user_agence = $_SESSION['code_agence'] ?? 'Non défini';

// 4. Inclusions des Fichiers Nécessaires
require_once '../fonctions/database.php';
require_once '../fonctions/gestion_utilisateurs.php';

// 5. Configuration par Rôle
$role_config = [
    // Administration
    'admin' => [
        'title' => 'Tableau de Bord Administrateur',
        'kpis' => ['revenue', 'tickets', 'clients', 'expenses', 'stock', 'employees'],
        'charts' => ['revenue_by_category', 'tickets_by_status', 'clients_by_month', 'expenses', 'payment_methods', 'stock'],
        'quick_actions' => ['new_ticket', 'manage_users', 'reports', 'settings', 'stock', 'clients']
    ],
    'directeur' => [
        'title' => 'Tableau de Bord Direction',
        'kpis' => ['revenue', 'profit', 'tickets', 'clients', 'expenses', 'stock_value'],
        'charts' => ['revenue_by_category', 'tickets_trend', 'clients_growth', 'expenses_analysis', 'payment_methods', 'stock_value'],
        'quick_actions' => ['financial_reports', 'performance', 'settings', 'analytics', 'stock', 'users']
    ],
    'patron' => [
        'title' => 'Tableau de Bord Propriétaire',
        'kpis' => ['revenue', 'profit_margin', 'tickets', 'clients', 'expenses', 'employee_performance'],
        'charts' => ['revenue_by_category', 'tickets_trend', 'clients_growth', 'expenses_analysis', 'profit_analysis', 'stock_value'],
        'quick_actions' => ['financial_reports', 'performance', 'settings', 'analytics', 'stock', 'users']
    ],
    
    // Réception
    'receptionniste' => [
        'title' => 'Tableau de Bord Réception',
        'kpis' => ['tickets_today', 'pending_tickets', 'ready_tickets', 'new_clients', 'revenue_today', 'average_ticket'],
        'charts' => ['tickets_by_status', 'tickets_by_day', 'services_popularity', 'clients_by_hour'],
        'quick_actions' => ['new_ticket', 'pending_tickets', 'ready_tickets', 'new_client', 'quick_search', 'ticket_list']
    ],
    'réceptionniste' => [
        'title' => 'Tableau de Bord Réception',
        'kpis' => ['tickets_today', 'pending_tickets', 'ready_tickets', 'new_clients', 'revenue_today', 'average_ticket'],
        'charts' => ['tickets_by_status', 'tickets_by_day', 'services_popularity', 'clients_by_hour'],
        'quick_actions' => ['new_ticket', 'pending_tickets', 'ready_tickets', 'new_client', 'quick_search', 'ticket_list']
    ],
    'reception_hotel' => [
        'title' => 'Tableau de Bord Réception Hôtel',
        'kpis' => ['tickets_today', 'pending_tickets', 'ready_tickets', 'room_occupancy', 'revenue_today', 'guest_satisfaction'],
        'charts' => ['tickets_by_status', 'room_status', 'service_requests', 'guest_arrivals'],
        'quick_actions' => ['new_ticket', 'check_in', 'room_management', 'service_requests', 'guest_list', 'housekeeping']
    ],
    
    // Caisse
    'caissier' => [
        'title' => 'Tableau de Bord Caisse',
        'kpis' => ['revenue_today', 'transactions_today', 'average_transaction', 'pending_payments', 'cash_in_hand', 'card_transactions'],
        'charts' => ['payment_methods', 'revenue_by_hour', 'transaction_types', 'daily_trend'],
        'quick_actions' => ['new_sale', 'view_transactions', 'cash_close', 'payment_reconciliation', 'quick_payment', 'refunds']
    ],
    'caissière' => [
        'title' => 'Tableau de Bord Caisse',
        'kpis' => ['revenue_today', 'transactions_today', 'average_transaction', 'pending_payments', 'cash_in_hand', 'card_transactions'],
        'charts' => ['payment_methods', 'revenue_by_hour', 'transaction_types', 'daily_trend'],
        'quick_actions' => ['new_sale', 'view_transactions', 'cash_close', 'payment_reconciliation', 'quick_payment', 'refunds']
    ],
    'caissier_boutique' => [
        'title' => 'Tableau de Bord Caisse Boutique',
        'kpis' => ['revenue_today', 'sales_today', 'average_sale', 'best_selling', 'inventory_value', 'customer_count'],
        'charts' => ['sales_by_category', 'payment_methods', 'hourly_sales', 'top_products'],
        'quick_actions' => ['new_sale', 'inventory_check', 'customer_service', 'sales_report', 'product_search', 'discounts']
    ],
    
    // Gestion
    'gestionnaire_stock' => [
        'title' => 'Tableau de Bord Gestion Stock',
        'kpis' => ['low_stock_count', 'total_products', 'stock_value', 'recent_orders', 'expiring_products', 'turnover_rate'],
        'charts' => ['stock_levels', 'category_distribution', 'reorder_alerts', 'stock_movement'],
        'quick_actions' => ['inventory_check', 'new_order', 'stock_adjustment', 'supplier_management', 'reports', 'categories']
    ],
    'gestion_hotel' => [
        'title' => 'Tableau de Bord Gestion Hôtel',
        'kpis' => ['room_occupancy', 'revenue_today', 'bookings_today', 'check_ins', 'check_outs', 'guest_satisfaction'],
        'charts' => ['room_status', 'revenue_by_room', 'booking_trend', 'guest_demographics'],
        'quick_actions' => ['room_management', 'new_booking', 'guest_services', 'housekeeping', 'reports', 'settings']
    ],
    'gestion_commerce' => [
        'title' => 'Tableau de Bord Gestion Commerce',
        'kpis' => ['revenue_today', 'sales_today', 'profit_margin', 'customer_count', 'inventory_value', 'employee_performance'],
        'charts' => ['sales_by_category', 'revenue_trend', 'customer_acquisition', 'profit_analysis'],
        'quick_actions' => ['sales_dashboard', 'inventory', 'staff_management', 'customer_insights', 'financial_reports', 'marketing']
    ],
    
    // Service
    'employe_pressing' => [
        'title' => 'Tableau de Bord Employé Pressing',
        'kpis' => ['tickets_to_process', 'completed_today', 'average_time', 'quality_score', 'machine_utilization', 'pending_quality_check'],
        'charts' => ['workload_distribution', 'completion_rate', 'service_types', 'efficiency_trend'],
        'quick_actions' => ['process_tickets', 'quality_check', 'machine_status', 'work_report', 'supplies_check', 'maintenance']
    ],
    'service_chambre' => [
        'title' => 'Tableau de Bord Service Chambre',
        'kpis' => ['rooms_to_clean', 'rooms_cleaned', 'cleaning_time', 'supplies_used', 'guest_requests', 'inspection_score'],
        'charts' => ['room_status', 'cleaning_schedule', 'request_types', 'productivity'],
        'quick_actions' => ['room_cleaning', 'supply_request', 'maintenance_report', 'guest_services', 'inventory_check', 'schedule']
    ],
    
    // Commercial
    'vendeur_boutique' => [
        'title' => 'Tableau de Bord Vendeur',
        'kpis' => ['sales_today', 'commission', 'conversion_rate', 'customer_interactions', 'average_sale', 'target_progress'],
        'charts' => ['sales_performance', 'product_preferences', 'customer_segments', 'hourly_sales'],
        'quick_actions' => ['new_sale', 'customer_profile', 'product_catalog', 'sales_target', 'commission_report', 'client_followup']
    ]
];

// Configuration par défaut si rôle non trouvé
$current_config = $role_config[$user_role] ?? [
    'title' => 'Tableau de Bord',
    'kpis' => ['tickets_today', 'pending_tickets', 'ready_tickets', 'revenue_today'],
    'charts' => ['tickets_by_status', 'tickets_by_day'],
    'quick_actions' => ['new_ticket', 'ticket_list', 'clients', 'settings']
];

// 6. Variables globales
$titre = $current_config['title'];
$current_page = basename(__FILE__);
$current_agence_id = $_SESSION['agence_id'] ?? 1;

// 7. Initialisation des données
$kpis = [];
$chart_data = [];
$db_error = null;

// 8. Récupération des données selon le rôle
if ($pdo instanceof PDO) {
    try {
        // Données communes à tous les rôles
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        
        // Tickets du jour (commun)
        $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets 
                               WHERE id_agence = :agence_id AND DATE(date_depot) = :today");
        $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $kpis['tickets_today'] = $res ? (int)$res['total'] : 0;
        
        // CA du jour (commun)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant_total), 0) as total FROM tickets 
                               WHERE id_agence = :agence_id AND DATE(date_depot) = :today 
                               AND statut != 'annule'");
        $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $kpis['revenue_today'] = $res ? (float)$res['total'] : 0;
        
        // Tickets en attente (commun)
        $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets 
                               WHERE id_agence = :agence_id AND statut = 'en_attente'");
        $stmt->execute([':agence_id' => $current_agence_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $kpis['pending_tickets'] = $res ? (int)$res['total'] : 0;
        
        // Tickets prêts (commun)
        $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets 
                               WHERE id_agence = :agence_id AND statut = 'pret'");
        $stmt->execute([':agence_id' => $current_agence_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $kpis['ready_tickets'] = $res ? (int)$res['total'] : 0;
        
        // Alertes stock (commun si nécessaire)
        if (in_array('stock', $current_config['kpis']) || in_array('low_stock_count', $current_config['kpis'])) {
            $stmt = $pdo->prepare("SELECT COUNT(id_produit) as total FROM produits 
                                   WHERE quantite_stock <= seuil_alerte AND est_actif = TRUE");
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $kpis['low_stock_count'] = $res ? (int)$res['total'] : 0;
        }
        
        // Données spécifiques par rôle
        switch ($user_role) {
            case 'admin':
            case 'directeur':
            case 'patron':
                // Données administratives
                $stmt = $pdo->prepare("SELECT COUNT(id_utilisateur) as total FROM utilisateurs 
                                       WHERE est_actif = TRUE");
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['total_employees'] = $res ? (int)$res['total'] : 0;
                
                $stmt = $pdo->prepare("SELECT COUNT(id_client) as total FROM clients 
                                       WHERE est_actif = TRUE");
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['total_clients'] = $res ? (int)$res['total'] : 0;
                
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM depenses 
                                       WHERE DATE_FORMAT(date_depense, '%Y-%m') = :month");
                $stmt->execute([':month' => $currentMonth]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['monthly_expenses'] = $res ? (float)$res['total'] : 0;
                
                // Stock value - CORRECTION ICI : prix_unitaire au lieu de prix_achat
                $stmt = $pdo->prepare("SELECT SUM(quantite_stock * prix_unitaire) as total FROM produits 
                                       WHERE est_actif = TRUE");
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['stock_value'] = $res ? round((float)$res['total'], 2) : 0;
                
                // Graphiques spécifiques
                $chart_data = getAdministrationCharts($pdo, $current_agence_id);
                break;
                
            case 'receptionniste':
            case 'réceptionniste':
            case 'reception_hotel':
                // Données réception
                $stmt = $pdo->prepare("SELECT COUNT(id_client) as total FROM clients 
                                       WHERE DATE(date_inscription) = :today");
                $stmt->execute([':today' => $today]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['new_clients_today'] = $res ? (int)$res['total'] : 0;
                
                // Moyenne ticket
                $stmt = $pdo->prepare("SELECT AVG(montant_total) as avg FROM tickets 
                                       WHERE id_agence = :agence_id AND DATE(date_depot) = :today 
                                       AND statut != 'annule'");
                $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['average_ticket'] = $res ? round((float)$res['avg'], 2) : 0;
                
                $chart_data = getReceptionCharts($pdo, $current_agence_id);
                break;
                
            case 'caissier':
            case 'caissière':
            case 'caissier_boutique':
                // Données caisse
                $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets 
                                       WHERE id_agence = :agence_id AND DATE(date_depot) = :today 
                                       AND statut != 'annule'");
                $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['transactions_today'] = $res ? (int)$res['total'] : 0;
                
                $stmt = $pdo->prepare("SELECT mode_paiement, COUNT(*) as count FROM tickets 
                                       WHERE id_agence = :agence_id AND DATE(date_depot) = :today 
                                       AND mode_paiement IS NOT NULL GROUP BY mode_paiement");
                $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
                $payment_counts = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $payment_counts[$row['mode_paiement']] = (int)$row['count'];
                }
                
                $kpis['cash_transactions'] = $payment_counts['especes'] ?? 0;
                $kpis['card_transactions'] = $payment_counts['carte'] ?? 0;
                
                $chart_data = getCashierCharts($pdo, $current_agence_id);
                break;
                
            case 'gestionnaire_stock':
                // Données stock
                $stmt = $pdo->prepare("SELECT COUNT(id_produit) as total FROM produits 
                                       WHERE est_actif = TRUE");
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['total_products'] = $res ? (int)$res['total'] : 0;
                
                // CORRECTION ICI : prix_unitaire au lieu de prix_achat
                $stmt = $pdo->prepare("SELECT SUM(quantite_stock * prix_unitaire) as total FROM produits 
                                       WHERE est_actif = TRUE");
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['stock_value'] = $res ? round((float)$res['total'], 2) : 0;
                
                $chart_data = getStockCharts($pdo);
                break;
                
            case 'employe_pressing':
                // Données employé pressing
                $stmt = $pdo->prepare("SELECT COUNT(lt.id_ligne) as total FROM lignes_ticket lt
                                       JOIN tickets t ON lt.id_ticket = t.id_ticket
                                       WHERE t.id_agence = :agence_id 
                                       AND t.statut = 'en_traitement'
                                       AND lt.statut_ligne = 'en_cours'");
                $stmt->execute([':agence_id' => $current_agence_id]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['tickets_to_process'] = $res ? (int)$res['total'] : 0;
                
                $stmt = $pdo->prepare("SELECT COUNT(lt.id_ligne) as total FROM lignes_ticket lt
                                       JOIN tickets t ON lt.id_ticket = t.id_ticket
                                       WHERE t.id_agence = :agence_id 
                                       AND DATE(lt.date_fin_traitement) = :today
                                       AND lt.statut_ligne = 'termine'");
                $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $kpis['completed_today'] = $res ? (int)$res['total'] : 0;
                
                $chart_data = getEmployeeCharts($pdo, $current_agence_id);
                break;
                
            default:
                // Données par défaut
                $chart_data = getDefaultCharts($pdo, $current_agence_id);
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération des données du tableau de bord: " . $e->getMessage());
        $db_error = "Erreur lors du chargement des données des statistiques : " . htmlspecialchars($e->getMessage());
    }
}

// 9. Fonctions de récupération des graphiques par rôle
function getAdministrationCharts($pdo, $agence_id) {
    $data = [];
    // Implémentation des graphiques pour l'administration
    return $data;
}

function getReceptionCharts($pdo, $agence_id) {
    $data = [];
    // Implémentation des graphiques pour la réception
    return $data;
}

function getCashierCharts($pdo, $agence_id) {
    $data = [];
    // Implémentation des graphiques pour la caisse
    return $data;
}

function getStockCharts($pdo) {
    $data = [];
    try {
        // Distribution par catégorie
        $stmt = $pdo->prepare("SELECT categorie, COUNT(*) as count, SUM(quantite_stock) as total_qty 
                               FROM produits WHERE est_actif = TRUE 
                               GROUP BY categorie ORDER BY total_qty DESC LIMIT 10");
        $stmt->execute();
        $data['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Niveaux de stock
        $stmt = $pdo->prepare("SELECT nom_produit, quantite_stock, seuil_alerte 
                               FROM produits WHERE est_actif = TRUE 
                               ORDER BY quantite_stock ASC LIMIT 10");
        $stmt->execute();
        $data['stock_levels'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur dans getStockCharts: " . $e->getMessage());
    }
    return $data;
}

function getEmployeeCharts($pdo, $agence_id) {
    $data = [];
    // Implémentation des graphiques pour les employés
    return $data;
}

function getDefaultCharts($pdo, $agence_id) {
    $data = [];
    try {
        // Tickets par statut
        $stmt = $pdo->prepare("SELECT statut, COUNT(*) as count FROM tickets 
                               WHERE id_agence = :agence_id 
                               GROUP BY statut");
        $stmt->execute([':agence_id' => $agence_id]);
        $data['tickets_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tickets par jour (7 derniers jours)
        $stmt = $pdo->prepare("SELECT DATE(date_depot) as date, COUNT(*) as count 
                               FROM tickets 
                               WHERE id_agence = :agence_id 
                               AND date_depot >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                               GROUP BY DATE(date_depot) 
                               ORDER BY date");
        $stmt->execute([':agence_id' => $agence_id]);
        $data['tickets_by_day'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur dans getDefaultCharts: " . $e->getMessage());
    }
    return $data;
}

// 10. Affichage
require_once('../templates/header.php');
require_once('../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pressing Manager | <?= htmlspecialchars($titre) ?></title>
    <link rel="shortcut icon" href="../images/logo_pressing.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/chart.js" defer></script>
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --warning-color: #f39c12;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #f8f9fa;
            --border-color: #dee2e6;
        }

        .dashboard-wrapper {
            margin-left: var(--sidebar-width);
            padding: 2rem 1.5rem;
            min-height: 100vh;
            background-color: var(--light-color);
            width: calc(100% - var(--sidebar-width));
        }

        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 0.6rem;
            margin-bottom: 2rem;
            box-shadow: 0 0.3rem 0.6rem rgba(0,0,0,0.1);
        }

        .welcome-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .welcome-header .subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05rem;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: white;
            border-radius: 0.6rem;
            padding: 1.5rem;
            box-shadow: 0 0.1rem 0.3rem rgba(0,0,0,0.08);
            border-left: 0.4rem solid var(--accent-color);
            transition: all 0.3s ease;
        }

        .kpi-card:hover {
            transform: translateY(-0.2rem);
            box-shadow: 0 0.3rem 0.6rem rgba(0,0,0,0.1);
        }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .kpi-label {
            font-size: 0.9rem;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.05rem;
        }

        .kpi-icon {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--accent-color);
            display: inline-block;
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            border-radius: 50%;
            background: rgba(52, 152, 219, 0.1);
        }

        .chart-container {
            background: white;
            border-radius: 0.6rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.1rem 0.3rem rgba(0,0,0,0.08);
        }

        .chart-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            background: white;
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
            padding: 1rem;
            border-radius: 0.6rem;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            transform: translateY(-0.1rem);
        }

        .action-icon {
            font-size: 1.2rem;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
            font-style: italic;
        }

        .stat-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-primary { background-color: var(--accent-color); color: white; }
        .badge-success { background-color: var(--success-color); color: white; }
        .badge-warning { background-color: var(--warning-color); color: white; }
        .badge-danger { background-color: var(--danger-color); color: white; }
        .badge-info { background-color: #17a2b8; color: white; }

        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
            
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        
        <!-- En-tête personnalisé -->
        <div class="welcome-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><?= htmlspecialchars($current_config['title']) ?></h1>
                    <div class="subtitle">
                        Bienvenue, <strong><?= htmlspecialchars($user_name) ?></strong>
                        <span class="role-badge ml-2"><?= htmlspecialchars($user_role) ?></span>
                        <span class="ml-2">Agence: <?= htmlspecialchars($user_agence) ?></span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-white"><?= date('d/m/Y H:i') ?></div>
                    <small>Dernière mise à jour: <?= date('H:i') ?></small>
                </div>
            </div>
        </div>

        <?php if (isset($db_error)): ?>
            <div class="alert alert-danger">
                <strong>Erreur :</strong> <?= htmlspecialchars($db_error) ?>
            </div>
        <?php endif; ?>

        <!-- Section KPIs dynamiques -->
        <div class="kpi-grid">
            <?php 
            // Affichage dynamique des KPIs selon le rôle
            $kpi_display = [
                'tickets_today' => ['Tickets du Jour', '📋', 'primary'],
                'revenue_today' => ['CA du Jour', '💰', 'success'],
                'pending_tickets' => ['En Attente', '⏱️', 'warning'],
                'ready_tickets' => ['Prêts', '✅', 'info'],
                'low_stock_count' => ['Alertes Stock', '⚠️', 'danger'],
                'new_clients_today' => ['Nouveaux Clients', '👥', 'primary'],
                'total_employees' => ['Employés', '👨‍💼', 'info'],
                'total_clients' => ['Clients Totaux', '📇', 'success'],
                'monthly_expenses' => ['Dépenses Mois', '📉', 'danger'],
                'transactions_today' => ['Transactions', '💱', 'primary'],
                'average_ticket' => ['Moyenne Ticket', '🧮', 'info'],
                'total_products' => ['Produits', '📦', 'success'],
                'stock_value' => ['Valeur Stock', '💎', 'warning'],
                'tickets_to_process' => ['À Traiter', '⚙️', 'primary'],
                'completed_today' => ['Terminés', '✔️', 'success'],
                'cash_transactions' => ['Espèces', '💵', 'warning'],
                'card_transactions' => ['Cartes', '💳', 'info']
            ];
            
            foreach ($current_config['kpis'] as $kpi_key) {
                if (isset($kpis[$kpi_key]) && isset($kpi_display[$kpi_key])) {
                    $kpi_info = $kpi_display[$kpi_key];
                    $value = $kpis[$kpi_key];
                    $formatted_value = is_numeric($value) ? number_format($value, 0, ',', ' ') : $value;
                    $color_class = $kpi_info[2];
                    ?>
                    <div class="kpi-card">
                        <div class="kpi-icon">
                            <?= $kpi_info[1] ?>
                        </div>
                        <div class="kpi-value">
                            <?= $formatted_value ?>
                            <?php if (strpos($kpi_key, 'revenue') !== false || strpos($kpi_key, 'expense') !== false || strpos($kpi_key, 'value') !== false): ?>
                                <small>FCFA</small>
                            <?php endif; ?>
                        </div>
                        <div class="kpi-label"><?= $kpi_info[0] ?></div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>

        <!-- Section Graphiques -->
        <?php if (!empty($current_config['charts'])): ?>
        <div class="chart-container">
            <h3 class="chart-title">Statistiques et Analyses</h3>
            <div class="row">
                <?php foreach ($current_config['charts'] as $chart_index => $chart_key): ?>
                <div class="col-md-6 mb-4">
                    <div class="chart-placeholder">
                        <h5><?= ucfirst(str_replace('_', ' ', $chart_key)) ?></h5>
                        <div class="chart-canvas" id="chart-<?= $chart_index ?>" style="height: 300px;">
                            <div class="no-data p-4">
                                Graphique <?= $chart_index + 1 ?>: <?= ucfirst(str_replace('_', ' ', $chart_key)) ?>
                                <div class="mt-3 text-muted">
                                    Données à afficher selon configuration
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section Actions Rapides -->
        <div class="quick-actions">
            <?php 
            $actions_display = [
                'new_ticket' => ['Nouveau Ticket', '➕', 'tickets/create.php'],
                'pending_tickets' => ['Tickets En Attente', '⏱️', 'tickets/list.php?status=en_attente'],
                'ready_tickets' => ['Tickets Prêts', '✅', 'tickets/list.php?status=pret'],
                'new_client' => ['Nouveau Client', '👤', 'clients/create.php'],
                'ticket_list' => ['Liste Tickets', '📋', 'tickets/list.php'],
                'stock' => ['Gestion Stock', '📦', 'stock/inventory.php'],
                'settings' => ['Paramètres', '⚙️', 'settings/index.php'],
                'reports' => ['Rapports', '📊', 'reports/daily.php'],
                'manage_users' => ['Gestion Utilisateurs', '👥', 'admin/users.php'],
                'financial_reports' => ['Rapports Financiers', '💰', 'reports/financial.php'],
                'performance' => ['Performance', '📈', 'analytics/performance.php'],
                'analytics' => ['Analyses', '🔍', 'analytics/dashboard.php'],
                'process_tickets' => ['Traiter Tickets', '⚙️', 'processing/tickets.php'],
                'quality_check' => ['Contrôle Qualité', '✅', 'quality/check.php'],
                'new_sale' => ['Nouvelle Vente', '🛒', 'sales/create.php'],
                'view_transactions' => ['Transactions', '💱', 'caisse/transactions.php'],
                'cash_close' => ['Clôture Caisse', '🏦', 'caisse/close.php'],
                'inventory_check' => ['Vérifier Stock', '📝', 'stock/check.php'],
                'room_cleaning' => ['Nettoyage Chambres', '🧹', 'housekeeping/rooms.php'],
                'sales_dashboard' => ['Tableau Ventes', '🏪', 'sales/dashboard.php']
            ];
            
            foreach ($current_config['quick_actions'] as $action_key) {
                if (isset($actions_display[$action_key])) {
                    $action_info = $actions_display[$action_key];
                    ?>
                    <a href="../pages/<?= $action_info[2] ?>" class="action-btn">
                        <span class="action-icon"><?= $action_info[1] ?></span>
                        <span><?= $action_info[0] ?></span>
                    </a>
                    <?php
                }
            }
            ?>
        </div>

    </div>
</div>

<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialisation des graphiques avec les données réelles
    const colors = [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
        '#1abc9c', '#d35400', '#c0392b', '#16a085', '#8e44ad'
    ];
    
    // Exemple de création de graphiques
    <?php foreach ($current_config['charts'] as $chart_index => $chart_key): ?>
    try {
        const ctx<?= $chart_index ?> = document.getElementById('chart-<?= $chart_index ?>');
        if (ctx<?= $chart_index ?>) {
            // Récupérer les données PHP si disponibles
            const chartData = <?= json_encode($chart_data[$chart_key] ?? []) ?>;
            
            if (chartData && chartData.length > 0) {
                // Créer le graphique avec les données réelles
                new Chart(ctx<?= $chart_index ?>, {
                    type: 'bar',
                    data: {
                        labels: chartData.map(item => item.label || item.date || item.categorie),
                        datasets: [{
                            label: '<?= ucfirst(str_replace('_', ' ', $chart_key)) ?>',
                            data: chartData.map(item => item.count || item.total || item.value),
                            backgroundColor: colors,
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                // Graphique de démonstration si pas de données
                new Chart(ctx<?= $chart_index ?>, {
                    type: 'bar',
                    data: {
                        labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                        datasets: [{
                            label: 'Données de démonstration',
                            data: [12, 19, 8, 15, 22, 18],
                            backgroundColor: colors,
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }
    } catch (e) {
        console.error('Erreur création graphique:', e);
        document.getElementById('chart-<?= $chart_index ?>').innerHTML = 
            '<div class="alert alert-warning p-3">Erreur de chargement du graphique</div>';
    }
    <?php endforeach; ?>
    
    // Auto-refresh toutes les 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
    
    // Mettre à jour l'heure en temps réel
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('fr-FR');
        document.querySelector('.welcome-header .text-white').innerHTML = 
            now.toLocaleDateString('fr-FR') + ' ' + timeString;
    }
    setInterval(updateTime, 1000);
});
</script>
</body>
</html>
<?php 
require_once('../templates/footer.php');
?>