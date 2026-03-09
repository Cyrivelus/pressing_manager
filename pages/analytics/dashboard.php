<?php
// pages/analytics/dashboard.php

session_start();

// Vérification de l'authentification
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once '../../fonctions/database.php';

// Configuration de la page
$titre = 'Tableau de Bord Pressing';
$current_agence_id = $_SESSION['agence_id'] ?? 1;

// Initialisation des KPI
$kpis = [
    'total_tickets' => 0,
    'total_tickets_today' => 0,
    'total_clients' => 0,
    'total_revenue_today' => 0,
    'total_tickets_pending' => 0,
    'total_tickets_ready' => 0,
    'low_stock_count' => 0,
    'total_expenses' => 0,
    'total_tickets_treated' => 0,
    'average_ticket_value' => 0,
];

// Initialisation des données des graphiques
$chart_data = [
    'tickets_by_status' => ['labels' => [], 'data' => []],
    'tickets_by_day' => ['labels' => [], 'data' => []],
    'services_popularity' => ['labels' => [], 'data' => []],
    'payment_methods' => ['labels' => [], 'data' => []],
    'stock_alerts' => ['labels' => [], 'data' => []],
];

// Récupération des données
if ($pdo instanceof PDO) {
    try {
        // Dates importantes
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        $last7days = date('Y-m-d', strtotime('-6 days'));
        
        // KPI 1: Tickets du jour
        $sql = "SELECT COUNT(id_ticket) as total FROM tickets 
                WHERE id_agence = ? AND DATE(date_depot) = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id, $today]);
        $res = $stmt->fetch();
        $kpis['total_tickets_today'] = $res['total'] ?? 0;
        
        // KPI 2: CA du jour
        $sql = "SELECT COALESCE(SUM(total_ttc), 0) as total FROM tickets 
                WHERE id_agence = ? AND DATE(date_depot) = ? AND statut != 'annule'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id, $today]);
        $res = $stmt->fetch();
        $kpis['total_revenue_today'] = $res['total'] ?? 0;
        
        // KPI 3: Tickets en attente
        $sql = "SELECT COUNT(id_ticket) as total FROM tickets 
                WHERE id_agence = ? AND statut = 'en_attente'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id]);
        $res = $stmt->fetch();
        $kpis['total_tickets_pending'] = $res['total'] ?? 0;
        
        // KPI 4: Tickets prêts
        $sql = "SELECT COUNT(id_ticket) as total FROM tickets 
                WHERE id_agence = ? AND statut = 'pret'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id]);
        $res = $stmt->fetch();
        $kpis['total_tickets_ready'] = $res['total'] ?? 0;
        
        // KPI 5: Total tickets
        $sql = "SELECT COUNT(id_ticket) as total FROM tickets WHERE id_agence = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id]);
        $res = $stmt->fetch();
        $kpis['total_tickets'] = $res['total'] ?? 0;
        
        // KPI 6: Total clients
        $sql = "SELECT COUNT(id_client) as total FROM clients 
                WHERE (id_agence = ? OR id_agence IS NULL) AND est_actif = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id]);
        $res = $stmt->fetch();
        $kpis['total_clients'] = $res['total'] ?? 0;
        
        // KPI 7: Alertes stock
        $sql = "SELECT COUNT(id_produit) as total FROM produits 
                WHERE quantite_stock <= seuil_alerte AND est_actif = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetch();
        $kpis['low_stock_count'] = $res['total'] ?? 0;
        
        // KPI 8: Dépenses du mois
        $sql = "SELECT COALESCE(SUM(montant), 0) as total FROM depenses 
                WHERE id_agence = ? AND DATE_FORMAT(date_depense, '%Y-%m') = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id, $currentMonth]);
        $res = $stmt->fetch();
        $kpis['total_expenses'] = $res['total'] ?? 0;
        
        // KPI 9: Tickets traités aujourd'hui
        $sql = "SELECT COUNT(id_ticket) as total FROM tickets 
                WHERE id_agence = ? AND DATE(date_depot) = ? 
                AND statut IN ('pret', 'recupere', 'en_traitement')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id, $today]);
        $res = $stmt->fetch();
        $kpis['total_tickets_treated'] = $res['total'] ?? 0;
        
        // KPI 10: Valeur moyenne des tickets
        $sql = "SELECT COALESCE(AVG(total_ttc), 0) as moyenne FROM tickets 
                WHERE id_agence = ? AND statut != 'annule' AND DATE(date_depot) = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id, $today]);
        $res = $stmt->fetch();
        $kpis['average_ticket_value'] = round($res['moyenne'] ?? 0, 0);
        
        // Graphique: Tickets par statut
        $sql = "SELECT statut, COUNT(*) as count FROM tickets 
                WHERE id_agence = ? AND statut != 'annule' GROUP BY statut";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status_labels = [
                'en_attente' => 'En attente',
                'en_traitement' => 'En traitement',
                'pret' => 'Prêt',
                'recupere' => 'Récupéré'
            ];
            $chart_data['tickets_by_status']['labels'][] = $status_labels[$row['statut']] ?? $row['statut'];
            $chart_data['tickets_by_status']['data'][] = (int)$row['count'];
        }
        
        // Graphique: Activité des 7 derniers jours
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[$date] = 0;
            $chart_data['tickets_by_day']['labels'][] = date('d/m', strtotime($date));
        }
        
        $sql = "SELECT DATE(date_depot) as jour, COUNT(*) as count FROM tickets 
                WHERE id_agence = ? AND DATE(date_depot) >= ? 
                GROUP BY DATE(date_depot) ORDER BY jour ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id, $last7days]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dates[$row['jour']] = (int)$row['count'];
        }
        $chart_data['tickets_by_day']['data'] = array_values($dates);
        
        // Graphique: Top 5 services
        $sql = "SELECT s.nom_service, COUNT(lt.id_ligne) as count 
                FROM lignes_ticket lt
                JOIN tickets t ON lt.id_ticket = t.id_ticket
                JOIN services s ON lt.id_service = s.id_service
                WHERE t.id_agence = ?
                GROUP BY lt.id_service
                ORDER BY count DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $chart_data['services_popularity']['labels'][] = htmlspecialchars($row['nom_service']);
            $chart_data['services_popularity']['data'][] = (int)$row['count'];
        }
        
        // Graphique: Mode de paiement
        $sql = "SELECT mode_paiement, COUNT(*) as count FROM tickets 
                WHERE id_agence = ? AND mode_paiement IS NOT NULL 
                GROUP BY mode_paiement";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_agence_id]);
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
        
        // Alertes stock
        $sql = "SELECT nom_produit, quantite_stock, seuil_alerte 
                FROM produits 
                WHERE quantite_stock <= seuil_alerte AND est_actif = 1 
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $percentage = $row['seuil_alerte'] > 0 ? 
                round(($row['quantite_stock'] / $row['seuil_alerte']) * 100, 0) : 0;
            $chart_data['stock_alerts']['labels'][] = htmlspecialchars($row['nom_produit']);
            $chart_data['stock_alerts']['data'][] = $percentage;
        }
        
    } catch (Exception $e) {
        $db_error = "Erreur lors du chargement des données: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pressing Manager | <?= htmlspecialchars($titre) ?></title>
    
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .dashboard-content {
            background-color: #f8f9fa;
            min-height: calc(100vh - 120px);
            padding: 20px;
        }
        
        .card-dashboard {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .card-header-dashboard {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .card-body-dashboard {
            padding: 20px;
        }
        
        .kpi-box {
            text-align: center;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }
        
        .kpi-box.success {
            border-left-color: #28a745;
        }
        
        .kpi-box.warning {
            border-left-color: #ffc107;
        }
        
        .kpi-box.danger {
            border-left-color: #dc3545;
        }
        
        .kpi-box.info {
            border-left-color: #17a2b8;
        }
        
        .kpi-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .kpi-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .quick-access-item {
            display: block;
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            text-decoration: none;
            color: #212529;
            transition: all 0.2s;
        }
        
        .quick-access-item:hover {
            background-color: #f8f9fa;
            text-decoration: none;
            color: #212529;
            border-color: #adb5bd;
        }
        
        .quick-access-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .quick-access-desc {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .badge-count {
            background-color: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                padding: 15px;
            }
            
            .kpi-value {
                font-size: 1.5rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 576px) {
            .kpi-value {
                font-size: 1.3rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .card-body-dashboard {
                padding: 15px;
            }
        }
        
        .today-summary {
            background-color: #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .today-stat {
            text-align: center;
            padding: 10px;
        }
        
        .today-stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .today-stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
    </style>
</head>
<body>

<!-- Inclusion de l'en-tête et navigation -->
<?php 
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>
<br><br><br>
<div class="dashboard-content">
    <div class="container-fluid">
        <!-- En-tête du tableau de bord -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-dashboard">
                    <div class="card-header-dashboard">
                        Tableau de Bord - Agence #<?= $current_agence_id ?>
                    </div>
                    <div class="card-body-dashboard">
                        <div class="row">
                            <div class="col-md-8">
                                <h4>Bienvenue dans votre espace de gestion</h4>
                                <p class="text-muted mb-0">Aperçu de l'activité de votre pressing</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="text-muted"><?= date('d/m/Y') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Messages d'erreur -->
        <?php if (isset($db_error)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($db_error) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques du jour -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="today-summary">
                    <div class="row">
                        <div class="col-6 col-md-3">
                            <div class="today-stat">
                                <div class="today-stat-value"><?= number_format($kpis['total_tickets_today'], 0, ',', ' ') ?></div>
                                <div class="today-stat-label">Tickets du jour</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="today-stat">
                                <div class="today-stat-value"><?= number_format($kpis['total_revenue_today'], 0, ',', ' ') ?> F</div>
                                <div class="today-stat-label">Chiffre d'affaires</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="today-stat">
                                <div class="today-stat-value"><?= number_format($kpis['average_ticket_value'], 0, ',', ' ') ?> F</div>
                                <div class="today-stat-label">Ticket moyen</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="today-stat">
                                <div class="today-stat-value"><?= number_format($kpis['total_tickets_treated'], 0, ',', ' ') ?></div>
                                <div class="today-stat-label">Tickets traités</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- KPI Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="kpi-box warning">
                    <div class="kpi-value"><?= number_format($kpis['total_tickets_pending'], 0, ',', ' ') ?></div>
                    <div class="kpi-label">À traiter</div>
                    <div class="mt-2">
                        <span class="badge bg-warning">En attente</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="kpi-box success">
                    <div class="kpi-value"><?= number_format($kpis['total_tickets_ready'], 0, ',', ' ') ?></div>
                    <div class="kpi-label">Prêts à récupérer</div>
                    <div class="mt-2">
                        <span class="badge bg-success">Prêt</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="kpi-box info">
                    <div class="kpi-value"><?= number_format($kpis['total_clients'], 0, ',', ' ') ?></div>
                    <div class="kpi-label">Clients actifs</div>
                    <div class="mt-2">
                        <span class="badge bg-info">Base clients</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="kpi-box danger">
                    <?php if ($kpis['low_stock_count'] > 0): ?>
                        <div class="position-absolute top-0 end-0 mt-2 me-2">
                            <span class="badge bg-danger"><?= $kpis['low_stock_count'] ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="kpi-value"><?= number_format($kpis['low_stock_count'], 0, ',', ' ') ?></div>
                    <div class="kpi-label">Alertes stock</div>
                    <div class="mt-2">
                        <span class="badge bg-danger">Attention</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Graphiques -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card-dashboard">
                    <div class="card-header-dashboard">
                        Répartition des tickets par statut
                    </div>
                    <div class="card-body-dashboard">
                        <div class="chart-container">
                            <canvas id="ticketsByStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card-dashboard">
                    <div class="card-header-dashboard">
                        Activité des 7 derniers jours
                    </div>
                    <div class="card-body-dashboard">
                        <div class="chart-container">
                            <canvas id="ticketsByDayChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Services et paiements -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card-dashboard">
                    <div class="card-header-dashboard">
                        Services les plus demandés
                    </div>
                    <div class="card-body-dashboard">
                        <div class="chart-container">
                            <canvas id="servicesPopularityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card-dashboard">
                    <div class="card-header-dashboard">
                        Modes de paiement utilisés
                    </div>
                    <div class="card-body-dashboard">
                        <div class="chart-container">
                            <canvas id="paymentMethodsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Accès rapides -->
        <div class="row">
            <div class="col-12">
                <div class="card-dashboard">
                    <div class="card-header-dashboard">
                        Accès rapides
                    </div>
                    <div class="card-body-dashboard">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <a href="../tickets/create.php" class="quick-access-item">
                                    <div class="quick-access-title">Nouveau ticket</div>
                                    <div class="quick-access-desc">Créer un nouveau ticket</div>
                                    <span class="badge-count">Nouveau</span>
                                </a>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <a href="../tickets/list.php?status=en_attente" class="quick-access-item">
                                    <div class="quick-access-title">Tickets en attente</div>
                                    <div class="quick-access-desc"><?= $kpis['total_tickets_pending'] ?> à traiter</div>
                                    <span class="badge bg-warning"><?= $kpis['total_tickets_pending'] ?></span>
                                </a>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <a href="../tickets/list.php?status=pret" class="quick-access-item">
                                    <div class="quick-access-title">Prêts à récupérer</div>
                                    <div class="quick-access-desc"><?= $kpis['total_tickets_ready'] ?> terminés</div>
                                    <span class="badge bg-success"><?= $kpis['total_tickets_ready'] ?></span>
                                </a>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <a href="../stock/inventory.php" class="quick-access-item">
                                    <div class="quick-access-title">Gestion stock</div>
                                    <div class="quick-access-desc">Vérifier les stocks</div>
                                    <?php if ($kpis['low_stock_count'] > 0): ?>
                                        <span class="badge bg-danger"><?= $kpis['low_stock_count'] ?> alertes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">OK</span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <a href="../clients/ajouter_client.php" class="quick-access-item">
                                    <div class="quick-access-title">Nouveau client</div>
                                    <div class="quick-access-desc">Ajouter un client</div>
                                    <span class="badge-count">Ajouter</span>
                                </a>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <a href="../caisse/index.php" class="quick-access-item">
                                    <div class="quick-access-title">Caisse du jour</div>
                                    <div class="quick-access-desc">Gérer les encaissements</div>
                                    <span class="badge-count">CA: <?= number_format($kpis['total_revenue_today'], 0, ',', ' ') ?> F</span>
                                </a>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <a href="../reports/daily.php" class="quick-access-item">
                                    <div class="quick-access-title">Rapport journalier</div>
                                    <div class="quick-access-desc">Synthèse du jour</div>
                                    <span class="badge-count">Rapport</span>
                                </a>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <a href="../settings/index.php" class="quick-access-item">
                                    <div class="quick-access-title">Paramètres</div>
                                    <div class="quick-access-desc">Configuration</div>
                                    <span class="badge bg-secondary">Admin</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bouton de rafraîchissement -->
    <button class="refresh-btn" id="refreshDashboard" title="Rafraîchir">
        ↻
    </button>
</div>

<!-- Inclusion du pied de page -->
<?php require_once '../../templates/footer.php'; ?>

<!-- Scripts -->
<script src="../../js/bootstrap.bundle.min.js"></script>
<script src="../../js/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données PHP converties en JSON
    const chartData = <?= json_encode($chart_data) ?>;
    
    // Fonction pour créer un graphique
    function createChart(canvasId, type, dataKey, label) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;
        
        const data = chartData[dataKey];
        if (!data || !data.data || data.data.length === 0) {
            ctx.closest('.card-body-dashboard').innerHTML = 
                '<div class="text-center text-muted py-4">Aucune donnée disponible</div>';
            return null;
        }
        
        // Couleurs selon le type de graphique
        let colors = [];
        if (type === 'doughnut' || type === 'pie') {
            colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d'];
        } else if (type === 'bar') {
            colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8'];
        } else if (type === 'line') {
            colors = ['#007bff'];
        }
        
        return new Chart(ctx, {
            type: type,
            data: {
                labels: data.labels,
                datasets: [{
                    label: label,
                    data: data.data,
                    backgroundColor: type === 'line' ? 'rgba(0, 123, 255, 0.1)' : colors.slice(0, data.data.length),
                    borderColor: type === 'line' ? '#007bff' : colors.slice(0, data.data.length),
                    borderWidth: type === 'line' ? 2 : 1,
                    fill: type === 'line',
                    tension: type === 'line' ? 0.3 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: type !== 'pie' && type !== 'doughnut' ? {
                    y: {
                        beginAtZero: true
                    }
                } : {}
            }
        });
    }
    
    // Initialisation des graphiques
    createChart('ticketsByStatusChart', 'doughnut', 'tickets_by_status', 'Tickets');
    createChart('ticketsByDayChart', 'line', 'tickets_by_day', 'Nombre de tickets');
    createChart('servicesPopularityChart', 'bar', 'services_popularity', 'Utilisations');
    createChart('paymentMethodsChart', 'pie', 'payment_methods', 'Paiements');
    
    // Bouton de rafraîchissement
    document.getElementById('refreshDashboard').addEventListener('click', function() {
        location.reload();
    });
    
    // Auto-refresh toutes les 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
});
</script>

</body>
</html>