<?php
// pages/dashboard.php (ancien tableau_bord.php pour pressing)

// -----------------------------------------------------------
// 1. Initialisation et Configuration
// -----------------------------------------------------------
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
session_start();

// -----------------------------------------------------------
// 2. Inclusions des Fichiers Nécessaires
// -----------------------------------------------------------
require_once '../fonctions/database.php';
require_once '../fonctions/gestion_utilisateurs.php';

// -----------------------------------------------------------
// 3. Vérification de l'authentification
// -----------------------------------------------------------


// -----------------------------------------------------------
// 4. Définition des Variables et Initialisation des Données
// -----------------------------------------------------------
$titre = 'Tableau de Bord Pressing/Commerce';
$current_page = basename(__FILE__);
$current_agence_id = $_SESSION['agence_id'] ?? 1; // ID de l'agence de l'utilisateur

// Initialize KPIs with default zero values
$kpis = [
    'total_tickets' => 0,
    'total_tickets_today' => 0,
    'total_clients' => 0,
    'total_revenue_today' => 0,
    'total_tickets_pending' => 0,
    'total_tickets_ready' => 0,
    'low_stock_count' => 0,
    'total_expenses' => 0,
];

// Initialize chart data structures
$chart_data = [
    'tickets_by_status' => ['labels' => [], 'data' => []],
    'tickets_by_day' => ['labels' => [], 'data' => []],
    'services_popularity' => ['labels' => [], 'data' => []],
    'revenue_by_category' => ['labels' => [], 'data' => []],
    'clients_by_month' => ['labels' => [], 'data' => []],
    'stock_alerts' => ['labels' => [], 'data' => []],
    'payment_methods' => ['labels' => [], 'data' => []],
    'expenses_by_category' => ['labels' => [], 'data' => []],
];

// -----------------------------------------------------------
// 5. Récupération des Données du Tableau de Bord (KPIs et Graphiques)
// -----------------------------------------------------------
if ($pdo instanceof PDO) {
    try {
        // KPIS: Total des tickets
        $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets WHERE id_agence = :agence_id");
        $stmt->execute([':agence_id' => $current_agence_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['total_tickets'] = (int)$res['total'];
        }

        // KPIS: Tickets du jour
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets 
                               WHERE id_agence = :agence_id AND DATE(date_depot) = :today");
        $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['total_tickets_today'] = (int)$res['total'];
        }

        // KPIS: Total clients
        $stmt = $pdo->prepare("SELECT COUNT(id_client) as total FROM clients 
                               WHERE id_agence = :agence_id AND est_actif = TRUE");
        $stmt->execute([':agence_id' => $current_agence_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['total_clients'] = (int)$res['total'];
        }

        // KPIS: Chiffre d'affaires du jour
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant_total), 0) as total FROM tickets 
                               WHERE id_agence = :agence_id AND DATE(date_depot) = :today 
                               AND statut != 'annule'");
        $stmt->execute([':agence_id' => $current_agence_id, ':today' => $today]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['total_revenue_today'] = (float)$res['total'];
        }

        // KPIS: Tickets en attente
        $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets 
                               WHERE id_agence = :agence_id AND statut = 'en_attente'");
        $stmt->execute([':agence_id' => $current_agence_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['total_tickets_pending'] = (int)$res['total'];
        }

        // KPIS: Tickets prêts à récupérer
        $stmt = $pdo->prepare("SELECT COUNT(id_ticket) as total FROM tickets 
                               WHERE id_agence = :agence_id AND statut = 'pret'");
        $stmt->execute([':agence_id' => $current_agence_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['total_tickets_ready'] = (int)$res['total'];
        }

        // KPIS: Alertes stock
        $stmt = $pdo->prepare("SELECT COUNT(id_produit) as total FROM produits 
                               WHERE quantite_stock <= seuil_alerte AND est_actif = TRUE");
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['low_stock_count'] = (int)$res['total'];
        }

        // KPIS: Dépenses du mois
        $currentMonth = date('Y-m');
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM depenses 
                               WHERE id_agence = :agence_id AND DATE_FORMAT(date_depense, '%Y-%m') = :month");
        $stmt->execute([':agence_id' => $current_agence_id, ':month' => $currentMonth]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $kpis['total_expenses'] = (float)$res['total'];
        }

        // Graphique: Tickets par statut
        $stmt = $pdo->prepare("SELECT statut, COUNT(*) as count FROM tickets 
                               WHERE id_agence = :agence_id GROUP BY statut ORDER BY statut");
        $stmt->execute([':agence_id' => $current_agence_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status_labels = [
                'en_attente' => 'En attente',
                'en_traitement' => 'En traitement',
                'pret' => 'Prêt',
                'recupere' => 'Récupéré',
                'annule' => 'Annulé'
            ];
            $chart_data['tickets_by_status']['labels'][] = $status_labels[$row['statut']] ?? $row['statut'];
            $chart_data['tickets_by_status']['data'][] = (int)$row['count'];
        }

        // Graphique: Tickets par jour (7 derniers jours)
        $last7days = date('Y-m-d', strtotime('-6 days'));
        $stmt = $pdo->prepare("SELECT DATE(date_depot) as jour, COUNT(*) as count FROM tickets 
                               WHERE id_agence = :agence_id AND DATE(date_depot) >= :last_7_days 
                               GROUP BY DATE(date_depot) ORDER BY jour ASC");
        $stmt->execute([':agence_id' => $current_agence_id, ':last_7_days' => $last7days]);
        
        // Créer un tableau pour les 7 derniers jours
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[$date] = 0;
            $chart_data['tickets_by_day']['labels'][] = date('d/m', strtotime($date));
        }
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dates[$row['jour']] = (int)$row['count'];
        }
        
        $chart_data['tickets_by_day']['data'] = array_values($dates);

        // Graphique: Services les plus populaires (Top 5)
        $stmt = $pdo->prepare("SELECT s.nom_service, COUNT(lt.id_service) as count 
                               FROM lignes_ticket lt
                               JOIN tickets t ON lt.id_ticket = t.id_ticket
                               JOIN services s ON lt.id_service = s.id_service
                               WHERE t.id_agence = :agence_id
                               GROUP BY lt.id_service
                               ORDER BY count DESC
                               LIMIT 5");
        $stmt->execute([':agence_id' => $current_agence_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $chart_data['services_popularity']['labels'][] = htmlspecialchars($row['nom_service']);
            $chart_data['services_popularity']['data'][] = (int)$row['count'];
        }

        // Graphique: CA par catégorie
        $stmt = $pdo->prepare("SELECT cs.nom_categorie, COALESCE(SUM(lt.sous_total), 0) as total
                               FROM lignes_ticket lt
                               JOIN tickets t ON lt.id_ticket = t.id_ticket
                               JOIN services s ON lt.id_service = s.id_service
                               JOIN categories_service cs ON s.id_categorie = cs.id_categorie
                               WHERE t.id_agence = :agence_id AND t.statut != 'annule'
                               GROUP BY cs.id_categorie
                               ORDER BY total DESC");
        $stmt->execute([':agence_id' => $current_agence_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $chart_data['revenue_by_category']['labels'][] = htmlspecialchars($row['nom_categorie']);
            $chart_data['revenue_by_category']['data'][] = (float)$row['total'];
        }

        // Graphique: Nouveaux clients par mois (6 derniers mois)
        $last6months = date('Y-m-01', strtotime('-5 months'));
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(date_inscription, '%Y-%m') as mois, COUNT(*) as count 
                               FROM clients 
                               WHERE id_agence = :agence_id 
                               AND DATE(date_inscription) >= :last_6_months 
                               GROUP BY DATE_FORMAT(date_inscription, '%Y-%m') 
                               ORDER BY mois ASC");
        $stmt->execute([':agence_id' => $current_agence_id, ':last_6_months' => $last6months]);
        
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $months[$month] = 0;
            $chart_data['clients_by_month']['labels'][] = date('M Y', strtotime($month . '-01'));
        }
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $months[$row['mois']] = (int)$row['count'];
        }
        
        $chart_data['clients_by_month']['data'] = array_values($months);

        // Graphique: Alertes stock
        $stmt = $pdo->prepare("SELECT nom_produit, quantite_stock, seuil_alerte 
                               FROM produits 
                               WHERE quantite_stock <= seuil_alerte 
                               AND est_actif = TRUE 
                               ORDER BY (quantite_stock/seuil_alerte) ASC 
                               LIMIT 5");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $percentage = round(($row['quantite_stock'] / $row['seuil_alerte']) * 100, 1);
            $chart_data['stock_alerts']['labels'][] = htmlspecialchars($row['nom_produit']);
            $chart_data['stock_alerts']['data'][] = $percentage;
        }

        // Graphique: Modes de paiement
        $stmt = $pdo->prepare("SELECT mode_paiement, COUNT(*) as count FROM tickets 
                               WHERE id_agence = :agence_id AND mode_paiement IS NOT NULL 
                               GROUP BY mode_paiement");
        $stmt->execute([':agence_id' => $current_agence_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payment_labels = [
                'especes' => 'Espèces',
                'carte' => 'Carte',
                'cheque' => 'Chèque',
                'mobile' => 'Mobile',
                'autre' => 'Autre'
            ];
            $chart_data['payment_methods']['labels'][] = $payment_labels[$row['mode_paiement']] ?? $row['mode_paiement'];
            $chart_data['payment_methods']['data'][] = (int)$row['count'];
        }

        // Graphique: Dépenses par catégorie (mois en cours)
        $stmt = $pdo->prepare("SELECT categorie, COALESCE(SUM(montant), 0) as total 
                               FROM depenses 
                               WHERE id_agence = :agence_id 
                               AND DATE_FORMAT(date_depense, '%Y-%m') = :current_month 
                               GROUP BY categorie");
        $stmt->execute([':agence_id' => $current_agence_id, ':current_month' => $currentMonth]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $expense_labels = [
                'loyer' => 'Loyer',
                'salaires' => 'Salaires',
                'electricite' => 'Électricité',
                'eau' => 'Eau',
                'produits' => 'Produits',
                'maintenance' => 'Maintenance',
                'autre' => 'Autre'
            ];
            $chart_data['expenses_by_category']['labels'][] = $expense_labels[$row['categorie']] ?? $row['categorie'];
            $chart_data['expenses_by_category']['data'][] = (float)$row['total'];
        }

    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération des données du tableau de bord: " . $e->getMessage());
        $db_error = "Erreur lors du chargement des données des statistiques : " . htmlspecialchars($e->getMessage());
    }
} else {
    $db_error = "La connexion à la base de données n'a pas pu être établie. Vérifiez votre configuration.";
}

// -----------------------------------------------------------
// 6. Affichage de la Vue
// -----------------------------------------------------------
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
    <link rel="stylesheet" href="../css/font-awesome.min.css">
    <script src="../js/chart.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
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
            --gray-medium: #6c757d;
            --gray-light: #e9ecef;
            --transition-speed: 0.3s;
        }

        /* Structure principale avec décalage pour la sidebar */
        .dashboard-wrapper {
            margin-left: var(--sidebar-width);
            padding: 2rem 1.5rem;
            min-height: 100vh;
            background-color: var(--light-color);
            transition: all var(--transition-speed) ease;
            width: calc(100% - var(--sidebar-width));
        }

        .dashboard-container { 
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .page-header { 
            border-bottom: 0.2rem solid var(--primary-color);
            padding-bottom: 1rem;
            margin: 1.5rem 0 2rem;
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Panel Styling */
        .panel {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.1rem 0.3rem rgba(0, 0, 0, 0.08);
            border-radius: 0.6rem;
            border: 0.1rem solid var(--border-color);
            background: white;
            transition: all var(--transition-speed) ease;
            height: 100%;
            overflow: hidden;
        }
        
        .panel:hover {
            transform: translateY(-0.1rem);
            box-shadow: 0 0.3rem 0.6rem rgba(0, 0, 0, 0.1);
        }
        
        .panel-heading {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom: none;
            font-weight: 600;
            font-size: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .panel-body { 
            padding: 1.5rem;
        }

        /* KPI Specific Styling */
        .kpi-value {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.2;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .kpi-label {
            font-size: 0.85rem;
            color: var(--gray-medium);
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.05rem;
            line-height: 1.2;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* KPI Panel Color Variations */
        .panel-primary {
            border-left: 0.4rem solid var(--accent-color);
        }
        
        .panel-warning {
            border-left: 0.4rem solid var(--warning-color);
        }
        
        .panel-success {
            border-left: 0.4rem solid var(--success-color);
        }
        
        .panel-danger {
            border-left: 0.4rem solid var(--danger-color);
        }
        
        .panel-info {
            border-left: 0.4rem solid #17a2b8;
        }

        /* Chart Specific Styling */
        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
            margin: auto;
        }
        
        canvas {
            max-width: 100%;
            height: 100% !important;
        }

        /* Quick Access Buttons */
        .quick-access-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all var(--transition-speed) ease;
            border-radius: 0.4rem;
            width: 100%;
            display: block;
            text-align: center;
            text-decoration: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .quick-access-btn:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-0.1rem);
            color: white;
            box-shadow: 0 0.2rem 0.4rem rgba(0, 0, 0, 0.15);
            text-decoration: none;
        }

        .quick-access-btn.warning {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
        }
        
        .quick-access-btn.success {
            background: linear-gradient(135deg, var(--success-color), #27ae60);
        }
        
        .quick-access-btn.danger {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
        }

        /* Alert Styling */
        .alert {
            border-radius: 0.6rem;
            border: none;
            box-shadow: 0 0.1rem 0.3rem rgba(0,0,0,0.1);
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 0.4rem solid #dc3545;
            color: #dc3545;
        }

        /* No Data State */
        .no-data {
            text-align: center;
            padding: 2.5rem 1.5rem;
            color: var(--gray-medium);
            font-style: italic;
            font-size: 0.9rem;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Badge for alerts */
        .badge-alert {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Status colors */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-en_attente { background-color: #ffc107; color: #000; }
        .status-en_traitement { background-color: #17a2b8; color: #fff; }
        .status-pret { background-color: #28a745; color: #fff; }
        .status-recupere { background-color: #6c757d; color: #fff; }
        .status-annule { background-color: #dc3545; color: #fff; }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-wrapper {
                margin-left: 220px;
                width: calc(100% - 220px);
                padding: 1.5rem;
            }
            
            .kpi-value {
                font-size: 1.4rem;
            }
            
            .chart-container {
                height: 240px;
            }
            
            .page-header {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
            
            .page-header {
                font-size: 1.5rem;
                margin: 1rem 0 1.5rem;
                text-align: center;
            }
            
            .kpi-value {
                font-size: 1.3rem;
            }
            
            .chart-container {
                height: 220px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-wrapper {
                padding: 1rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .panel-body {
                padding: 1rem;
            }
            
            .panel-heading {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .chart-container {
                height: 180px;
            }
            
            .kpi-value {
                font-size: 1.2rem;
            }
        }

        /* Animation pour le chargement */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(1rem); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        /* Section spacing */
        .section-spacing {
            margin-bottom: 2rem;
        }
        
        /* Today's date */
        .today-date {
            color: var(--gray-medium);
            font-size: 0.9rem;
            margin-top: -0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<!-- Structure principale avec sidebar et contenu -->
<div class="dashboard-wrapper fade-in">
    <div class="dashboard-container">
    </BR> </BR>
        <h2 class="page-header">Tableau de Bord Pressing/Commerce</h2>
        <div class="today-date"><?= date('d/m/Y') ?></div>

        <?php if (isset($db_error)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Erreur :</strong> <?= htmlspecialchars($db_error) ?>
            </div>
        <?php endif; ?>

        <!-- Section KPIs -->
        <div class="row section-spacing">
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Tickets du Jour</h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: var(--accent-color);"><?= number_format($kpis['total_tickets_today'], 0, ',', ' ') ?></div>
                        <div class="kpi-label">Nouveaux tickets aujourd'hui</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">Chiffre d'Affaires</h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: var(--success-color);"><?= number_format($kpis['total_revenue_today'], 0, ',', ' ') ?> FCFA</div>
                        <div class="kpi-label">CA du jour</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title">À Traiter</h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: var(--warning-color);"><?= number_format($kpis['total_tickets_pending'], 0, ',', ' ') ?></div>
                        <div class="kpi-label">Tickets en attente</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Prêts à Récupérer</h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: #17a2b8;"><?= number_format($kpis['total_tickets_ready'], 0, ',', ' ') ?></div>
                        <div class="kpi-label">Tickets terminés</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row section-spacing">
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Clients Actifs</h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: var(--accent-color);"><?= number_format($kpis['total_clients'], 0, ',', ' ') ?></div>
                        <div class="kpi-label">Clients enregistrés</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            Alertes Stock
                            <?php if ($kpis['low_stock_count'] > 0): ?>
                                <span class="badge-alert"><?= $kpis['low_stock_count'] ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: var(--warning-color);"><?= number_format($kpis['low_stock_count'], 0, ',', ' ') ?></div>
                        <div class="kpi-label">Produits en rupture</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <h3 class="panel-title">Dépenses du Mois</h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: var(--danger-color);"><?= number_format($kpis['total_expenses'], 0, ',', ' ') ?> FCFA</div>
                        <div class="kpi-label">Total dépenses ce mois</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Total Tickets</h3>
                    </div>
                    <div class="panel-body">
                        <div class="kpi-value" style="color: #17a2b8;"><?= number_format($kpis['total_tickets'], 0, ',', ' ') ?></div>
                        <div class="kpi-label">Tous tickets confondus</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Graphiques -->
        <div class="row section-spacing">
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Répartition des Tickets par Statut</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="ticketsByStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Activité des 7 Derniers Jours</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="ticketsByDayChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row section-spacing">
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Top 5 Services les Plus Demandés</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="servicesPopularityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Chiffre d'Affaires par Catégorie</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="revenueByCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row section-spacing">
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Nouveaux Clients (6 Derniers Mois)</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="clientsByMonthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Alertes Stock (Niveau Bas)</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="stockAlertsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row section-spacing">
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Répartition des Modes de Paiement</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="paymentMethodsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Dépenses par Catégorie (Mois en Cours)</h3>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="expensesByCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Accès Rapides -->
        <h3 class="page-header">Accès Rapides</h3>
        <div class="row">
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Nouveau Ticket</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/tickets/create.php" class="btn quick-access-btn success">
                            Créer Ticket
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Tickets en Attente</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/tickets/list.php?status=en_attente" class="btn quick-access-btn warning">
                             Voir <?= $kpis['total_tickets_pending'] ?> tickets 
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Prêts à Récupérer</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/tickets/list.php?status=pret" class="btn quick-access-btn info">
                             Voir <?= $kpis['total_tickets_ready'] ?> tickets
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Gestion Stock</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/stock/inventory.php" class="btn quick-access-btn danger">
                            <i class="fas fa-boxes"></i> Vérifier stocks
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Nouveau Client</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/clients/create.php" class="btn quick-access-btn">
                            Ajouter client
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Rapport Journalier</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/reports/daily.php" class="btn quick-access-btn">
                            <i class="fas fa-chart-bar"></i> Rapport du jour
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Caisse du Jour</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/caisse/index.php" class="btn quick-access-btn">
                          Gérer caisse
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title">Paramètres</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="../pages/settings/index.php" class="btn quick-access-btn">
                             Paramètres
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // PHP variables are encoded as JSON and passed to JavaScript
    const chartData = <?= json_encode($chart_data) ?>;

    // Professional color palette
    const professionalColors = [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
        '#1abc9c', '#d35400', '#c0392b', '#16a085', '#8e44ad',
        '#2980b9', '#27ae60', '#8e44ad', '#f1c40f', '#e67e22'
    ];

    // Status colors for tickets
    const statusColors = {
        'En attente': '#ffc107',
        'En traitement': '#17a2b8',
        'Prêt': '#28a745',
        'Récupéré': '#6c757d',
        'Annulé': '#dc3545'
    };

    // Configuration des graphiques
    function renderChart(id, type, dataKey, title, customColors = null) {
        const ctx = document.getElementById(id);
        if (!ctx) {
            console.warn(`Canvas element with ID '${id}' not found.`);
            return null;
        }

        // Check if data exists
        if (!chartData[dataKey] || !chartData[dataKey].data || chartData[dataKey].data.length === 0) {
            showNoDataMessage(id);
            return null;
        }

        // Choose colors based on chart type and data
        let colors = [];
        const dataLength = chartData[dataKey].data.length;
        const labels = chartData[dataKey].labels;
        
        if (customColors && Array.isArray(customColors)) {
            colors = customColors.slice(0, Math.min(dataLength, customColors.length));
        } else if (type === 'pie' || type === 'doughnut') {
            // Use status colors for tickets by status chart
            if (id === 'ticketsByStatusChart') {
                colors = labels.map(label => statusColors[label] || professionalColors[0]);
            } else {
                colors = professionalColors.slice(0, Math.min(dataLength, professionalColors.length));
            }
        } else if (type === 'line' || type === 'bar') {
            colors = [professionalColors[0]];
        } else {
            colors = [professionalColors[0]];
        }

        const chart = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    data: chartData[dataKey].data,
                    backgroundColor: type === 'pie' || type === 'doughnut' ? colors : 
                                  type === 'bar' ? colors[0] : 'rgba(52, 152, 219, 0.1)',
                    borderColor: type === 'bar' ? colors[0] : professionalColors[0],
                    borderWidth: 2,
                    fill: type === 'line' ? {
                        target: 'origin',
                        above: 'rgba(52, 152, 219, 0.1)',
                        below: 'rgba(52, 152, 219, 0.1)'
                    } : true,
                    tension: type === 'line' ? 0.3 : 0,
                    pointBackgroundColor: professionalColors[0],
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            },
                            color: '#2c3e50',
                            padding: 10,
                            usePointStyle: true
                        }
                    },
                    title: {
                        display: false,
                        text: title
                    },
                    tooltip: {
                        backgroundColor: 'rgba(44, 62, 80, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: professionalColors[0],
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    if (id.includes('revenue') || id.includes('expenses') || id.includes('CA') || 
                                        dataKey.includes('revenue') || dataKey.includes('expense')) {
                                        label += new Intl.NumberFormat('fr-FR', { 
                                            style: 'currency', 
                                            currency: 'XAF',
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0
                                        }).format(context.parsed);
                                    } else if (id.includes('stockAlerts') || dataKey.includes('stock')) {
                                        label += context.parsed + '%';
                                    } else {
                                        label += new Intl.NumberFormat('fr-FR').format(context.parsed);
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                                size: 11
                            },
                            color: '#6c757d',
                            padding: 8,
                            callback: function(value) {
                                if (id.includes('revenue') || id.includes('expenses') || id.includes('CA') || 
                                    dataKey.includes('revenue') || dataKey.includes('expense')) {
                                    if (value >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return (value / 1000).toFixed(1) + 'K';
                                    }
                                    return value;
                                }
                                if (value >= 1000) {
                                    return (value / 1000).toFixed(1) + 'K';
                                }
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                                size: 11
                            },
                            color: '#6c757d',
                            padding: 8,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    line: {
                        tension: 0.3
                    }
                }
            }
        });

        return chart;
    }

    function showNoDataMessage(chartId) {
        const canvas = document.getElementById(chartId);
        if (canvas) {
            const container = canvas.closest('.chart-container');
            if (container) {
                container.innerHTML = '<div class="no-data">Aucune donnée disponible pour ce graphique</div>';
            }
        }
    }

    // Initialisation des graphiques
    try {
        // Diagramme circulaire pour les tickets par statut
        if (chartData.tickets_by_status.data.length > 0) {
            renderChart('ticketsByStatusChart', 'doughnut', 'tickets_by_status', 'Tickets par Statut');
        } else {
            showNoDataMessage('ticketsByStatusChart');
        }

        // Ligne pour l'activité des 7 derniers jours
        if (chartData.tickets_by_day.data.length > 0) {
            renderChart('ticketsByDayChart', 'line', 'tickets_by_day', 'Tickets par Jour');
        } else {
            showNoDataMessage('ticketsByDayChart');
        }

        // Diagramme en barres pour les services populaires
        if (chartData.services_popularity.data.length > 0) {
            renderChart('servicesPopularityChart', 'bar', 'services_popularity', 'Services les Plus Demandés');
        } else {
            showNoDataMessage('servicesPopularityChart');
        }

        // Diagramme circulaire pour le CA par catégorie
        if (chartData.revenue_by_category.data.length > 0) {
            renderChart('revenueByCategoryChart', 'pie', 'revenue_by_category', 'CA par Catégorie');
        } else {
            showNoDataMessage('revenueByCategoryChart');
        }

        // Barres pour les nouveaux clients
        if (chartData.clients_by_month.data.length > 0) {
            renderChart('clientsByMonthChart', 'bar', 'clients_by_month', 'Nouveaux Clients');
        } else {
            showNoDataMessage('clientsByMonthChart');
        }

        // Barres pour les alertes stock
        if (chartData.stock_alerts.data.length > 0) {
            // Use danger colors for stock alerts
            const dangerColors = chartData.stock_alerts.data.map(value => 
                value < 20 ? '#e74c3c' : 
                value < 50 ? '#f39c12' : 
                '#3498db'
            );
            renderChart('stockAlertsChart', 'bar', 'stock_alerts', 'Niveau de Stock (%)', dangerColors);
        } else {
            showNoDataMessage('stockAlertsChart');
        }

        // Diagramme circulaire pour les modes de paiement
        if (chartData.payment_methods.data.length > 0) {
            renderChart('paymentMethodsChart', 'doughnut', 'payment_methods', 'Modes de Paiement');
        } else {
            showNoDataMessage('paymentMethodsChart');
        }

        // Diagramme circulaire pour les dépenses
        if (chartData.expenses_by_category.data.length > 0) {
            renderChart('expensesByCategoryChart', 'pie', 'expenses_by_category', 'Dépenses par Catégorie');
        } else {
            showNoDataMessage('expensesByCategoryChart');
        }

    } catch (error) {
        console.error('Erreur lors du rendu des graphiques:', error);
    }

    // Gestion du responsive
    $(window).on('resize', function() {
        // Chart.js gère automatiquement le redimensionnement
    });
    
    // Auto-refresh every 5 minutes (300000 ms)
    setTimeout(function() {
        location.reload();
    }, 300000);
});
</script>
</body>
</html>
<?php 
require_once('../templates/footer.php');
?>