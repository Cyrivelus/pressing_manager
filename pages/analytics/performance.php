<?php
session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once '../../fonctions/database.php';

// Configuration de la page
$titre = "Analyse de Performance - Tableau de Bord";
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Initialisation préventive pour éviter les "Undefined variable"
$kpis = [
    'total_commandes' => 0, 'ca_total' => 0, 'panier_moyen' => 0, 'total_clients' => 0,
    'taux_recuperation' => 0, 'depenses_total' => 0, 'profit_net' => 0, 'marge_brute' => 0,
    'nb_paiements' => 0, 'tickets_encours' => 0, 'ca_boutique' => 0, 'nb_ventes_boutique' => 0,
    'total_articles' => 0
];
$performanceAgences = [];
$topServices = [];
$topClients = [];
$evolutionCA = [];
$depensesParCategorie = [];
$statutTickets = [];
$paiementsParMode = [];
$error = null;

// Gestion des accès agences
$userAgencies = $_SESSION['agences_access'] ?? [];
if (empty($userAgencies)) {
    $stmt = $pdo->query("SELECT id_agence FROM agences");
    $userAgencies = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
$agenciesList = !empty($userAgencies) ? implode(',', array_map('intval', $userAgencies)) : "0";

try {
    // 1. CHIFFRES CLÉS - CORRIGÉ AVEC LES BONNES COLONNES
    $sqlKpi = "SELECT 
                COUNT(DISTINCT t.id_ticket) as total_commandes,
                COALESCE(SUM(t.total_ttc), 0) as ca_total,
                COALESCE(SUM(t.nombre_articles), 0) as total_articles,
                COUNT(DISTINCT t.id_client) as total_clients,
                COUNT(DISTINCT CASE WHEN t.statut IN ('recupere', 'pret') THEN t.id_ticket END) as tickets_termines,
                COUNT(DISTINCT CASE WHEN t.statut IN ('en_attente', 'en_traitement') THEN t.id_ticket END) as tickets_encours
               FROM tickets t
               WHERE t.date_depot BETWEEN ? AND ? 
               AND t.id_agence IN ($agenciesList) 
               AND t.statut NOT IN ('annule')";
    
    $stmt = $pdo->prepare($sqlKpi);
    $stmt->execute([$startDate, $endDate]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($res) {
        $kpis['total_commandes'] = $res['total_commandes'];
        $kpis['ca_total'] = $res['ca_total'];
        $kpis['total_articles'] = $res['total_articles'];
        $kpis['panier_moyen'] = ($res['total_commandes'] > 0) ? $res['ca_total'] / $res['total_commandes'] : 0;
        $kpis['total_clients'] = $res['total_clients'];
        $kpis['taux_recuperation'] = ($res['total_commandes'] > 0) ? ($res['tickets_termines'] / $res['total_commandes']) * 100 : 0;
        $kpis['tickets_encours'] = $res['tickets_encours'];
    }

    // 2. PERFORMANCE PAR AGENCE
    $sqlAgences = "SELECT a.id_agence, a.nom_agence, 
                    COALESCE(SUM(t.total_ttc), 0) as CA,
                    COUNT(DISTINCT t.id_ticket) as nb_commandes,
                    COUNT(DISTINCT t.id_client) as nb_clients,
                    COALESCE(AVG(t.total_ttc), 0) as panier_moyen
                   FROM agences a 
                   LEFT JOIN tickets t ON a.id_agence = t.id_agence 
                   AND t.date_depot BETWEEN ? AND ? 
                   AND t.statut NOT IN ('annule')
                   WHERE a.id_agence IN ($agenciesList) 
                   GROUP BY a.id_agence, a.nom_agence 
                   ORDER BY CA DESC";
    
    $stmt = $pdo->prepare($sqlAgences);
    $stmt->execute([$startDate, $endDate]);
    $performanceAgences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. TOP 10 SERVICES - CORRIGÉ (lignes_ticket n'a pas prix_total)
    // On calcule le revenu en multipliant quantité par prix unitaire du service
    $sqlServices = "SELECT 
                    s.nom_service,
                    s.prix_unitaire,
                    COUNT(lt.id_ligne) as nb_utilisations,
                    COALESCE(SUM(lt.quantite), 0) as total_quantite,
                    COALESCE(SUM(lt.quantite * s.prix_unitaire), 0) as revenu_total
                   FROM services s
                   JOIN lignes_ticket lt ON s.id_service = lt.id_service
                   JOIN tickets t ON lt.id_ticket = t.id_ticket
                   WHERE t.date_depot BETWEEN ? AND ? 
                   AND t.id_agence IN ($agenciesList)
                   AND t.statut NOT IN ('annule')
                   GROUP BY s.id_service, s.nom_service, s.prix_unitaire
                   ORDER BY revenu_total DESC 
                   LIMIT 10";
    
    $stmt = $pdo->prepare($sqlServices);
    $stmt->execute([$startDate, $endDate]);
    $topServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. TOP 10 CLIENTS
    $sqlClients = "SELECT 
                    c.id_client,
                    CONCAT(c.nom_client, ' ', COALESCE(c.prenom_client, '')) as nom_complet,
                    c.telephone,
                    c.email,
                    COUNT(DISTINCT t.id_ticket) as nb_tickets,
                    COALESCE(SUM(t.total_ttc), 0) as ca_total,
                    MAX(t.date_depot) as derniere_commande
                   FROM clients c
                   JOIN tickets t ON c.id_client = t.id_client
                   WHERE t.date_depot BETWEEN ? AND ? 
                   AND t.id_agence IN ($agenciesList)
                   AND t.statut NOT IN ('annule')
                   GROUP BY c.id_client, c.nom_client, c.prenom_client, c.telephone, c.email
                   ORDER BY ca_total DESC 
                   LIMIT 10";
    
    $stmt = $pdo->prepare($sqlClients);
    $stmt->execute([$startDate, $endDate]);
    $topClients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. DÉPENSES PAR CATÉGORIE
    $sqlDepenses = "SELECT 
                    categorie,
                    COUNT(id_depense) as nb_depenses,
                    COALESCE(SUM(montant), 0) as total_depenses,
                    COALESCE(AVG(montant), 0) as montant_moyen
                   FROM depenses
                   WHERE date_depense BETWEEN ? AND ? 
                   AND id_agence IN ($agenciesList)
                   GROUP BY categorie
                   ORDER BY total_depenses DESC";
    
    $stmt = $pdo->prepare($sqlDepenses);
    $stmt->execute([$startDate, $endDate]);
    $depensesParCategorie = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul du total des dépenses
    $totalDepenses = 0;
    foreach ($depensesParCategorie as $depense) {
        $totalDepenses += $depense['total_depenses'];
    }
    $kpis['depenses_total'] = $totalDepenses;
    $kpis['profit_net'] = $kpis['ca_total'] - $totalDepenses;
    $kpis['marge_brute'] = ($kpis['ca_total'] > 0) ? (($kpis['ca_total'] - $totalDepenses) / $kpis['ca_total']) * 100 : 0;

    // 6. PAIEMENTS ET MODES DE PAIEMENT
    $sqlPaiements = "SELECT 
                     mode_paiement,
                     COUNT(id_paiement) as nb_paiements,
                     COALESCE(SUM(montant), 0) as total_paye
                    FROM paiements
                    WHERE date_paiement BETWEEN ? AND ? 
                    AND id_ticket IN (
                        SELECT id_ticket FROM tickets WHERE id_agence IN ($agenciesList)
                    )
                    GROUP BY mode_paiement
                    ORDER BY total_paye DESC";
    
    $stmt = $pdo->prepare($sqlPaiements);
    $stmt->execute([$startDate, $endDate]);
    $paiementsParMode = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $kpis['nb_paiements'] = array_sum(array_column($paiementsParMode, 'nb_paiements'));

    // 7. ÉVOLUTION DU CA PAR JOUR
    $sqlEvolution = "SELECT 
                     DATE(t.date_depot) as jour,
                     COUNT(DISTINCT t.id_ticket) as nb_tickets,
                     COALESCE(SUM(t.total_ttc), 0) as ca_journalier
                    FROM tickets t
                    WHERE t.date_depot BETWEEN ? AND ? 
                    AND t.id_agence IN ($agenciesList)
                    AND t.statut NOT IN ('annule')
                    GROUP BY DATE(t.date_depot)
                    ORDER BY jour ASC";
    
    $stmt = $pdo->prepare($sqlEvolution);
    $stmt->execute([$startDate, $endDate]);
    $evolutionCA = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. STATUT DES TICKETS
    $sqlStatuts = "SELECT 
                   statut,
                   COUNT(id_ticket) as nb_tickets,
                   COALESCE(SUM(total_ttc), 0) as valeur_totale
                  FROM tickets
                  WHERE date_depot BETWEEN ? AND ? 
                  AND id_agence IN ($agenciesList)
                  AND statut NOT IN ('annule')
                  GROUP BY statut
                  ORDER BY nb_tickets DESC";
    
    $stmt = $pdo->prepare($sqlStatuts);
    $stmt->execute([$startDate, $endDate]);
    $statutTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 9. VENTES BOUTIQUE (si table existe)
    try {
        $sqlBoutique = "SELECT 
                        COUNT(DISTINCT id_vente) as nb_ventes,
                        COALESCE(SUM(montant_total), 0) as ca_boutique
                       FROM ventes_boutique
                       WHERE date_vente BETWEEN ? AND ? 
                       AND id_agence IN ($agenciesList)";
        
        $stmt = $pdo->prepare($sqlBoutique);
        $stmt->execute([$startDate, $endDate]);
        $ventesBoutique = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ventesBoutique) {
            $kpis['ca_boutique'] = $ventesBoutique['ca_boutique'] ?? 0;
            $kpis['nb_ventes_boutique'] = $ventesBoutique['nb_ventes'] ?? 0;
        }
    } catch (Exception $e) {
        // Table peut ne pas exister - on ignore l'erreur
    }

} catch (Exception $e) { 
    $error = "Erreur lors de la récupération des données : " . $e->getMessage(); 
}

require_once '../../templates/header.php'; 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pressing Manager | <?= htmlspecialchars($titre) ?></title>
    
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #2ecc71;
            --dark-color: #2c3e50;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background-color: var(--dark-color);
            min-height: 100vh;
        }
        
        .card-kpi {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-kpi:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.12);
        }
        
        .kpi-icon {
            font-size: 2.5rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .kpi-value {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 5px;
        }
        
        .kpi-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .kpi-subtext {
            font-size: 0.8rem;
            opacity: 0.6;
        }
        
        .card-header-custom {
            background-color: white;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: var(--dark-color);
            padding: 1rem 1.25rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        .table-analytics {
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .table-analytics thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 0.75rem 1rem;
        }
        
        .table-analytics tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .badge-statut {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 20px;
        }
        
        .progress-analytics {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
        
        .statut-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .statut-en_attente { background-color: #f8d7da; color: #721c24; }
        .statut-en_traitement { background-color: #fff3cd; color: #856404; }
        .statut-pret { background-color: #d1ecf1; color: #0c5460; }
        .statut-recupere { background-color: #d4edda; color: #155724; }
        
        @media (max-width: 768px) {
            .kpi-value {
                font-size: 1.5rem;
            }
            
            .card-kpi {
                margin-bottom: 1rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <br> <br> <br>
    <div class="container-fluid">
        <div class="row">
            <!-- Navigation latérale -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar sidebar-expand-lg">
                <?php require_once '../../templates/navigation.php'; ?>
            </nav>

            <!-- Contenu principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- En-tête avec filtres -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center pb-3 mb-4 border-bottom bg-white p-3 rounded shadow-sm">
                    <div class="mb-3 mb-md-0">
                        <h1 class="h3 mb-1 fw-bold text-dark">
                            <i class="bi bi-graph-up me-2"></i>Tableau de Bord Performance
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-calendar me-1"></i>
                            Période du <?= date('d/m/Y', strtotime($startDate)) ?> au <?= date('d/m/Y', strtotime($endDate)) ?>
                        </p>
                    </div>
                    
                    <!-- Filtres de période -->
                    <div class="bg-light p-3 rounded">
                        <form action="" method="GET" class="row g-2 align-items-center">
                            <div class="col-auto">
                                <label class="form-label mb-0 small fw-bold">Filtrer par période</label>
                            </div>
                            <div class="col-auto">
                                <input type="date" name="start_date" class="form-control form-control-sm" 
                                       value="<?= htmlspecialchars($startDate) ?>" required>
                            </div>
                            <div class="col-auto">
                                <span class="text-muted">à</span>
                            </div>
                            <div class="col-auto">
                                <input type="date" name="end_date" class="form-control form-control-sm" 
                                       value="<?= htmlspecialchars($endDate) ?>" required>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="bi bi-funnel me-1"></i>Appliquer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Messages d'erreur -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- KPI Cards -->
                <div class="row g-3 mb-4">
                    <!-- CA Total -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-kpi bg-primary text-white">
                            <div class="card-body p-3">
                                <div class="kpi-icon">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div class="kpi-value">
                                    <?= number_format($kpis['ca_total'], 0, ',', ' ') ?> FCFA
                                </div>
                                <div class="kpi-label">Chiffre d'Affaires</div>
                                <div class="kpi-subtext">
                                    <?= $kpis['total_commandes'] ?> commandes
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tickets -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-kpi bg-success text-white">
                            <div class="card-body p-3">
                                <div class="kpi-icon">
                                    <i class="bi bi-ticket-detailed"></i>
                                </div>
                                <div class="kpi-value"><?= $kpis['total_commandes'] ?></div>
                                <div class="kpi-label">Tickets</div>
                                <div class="kpi-subtext">
                                    <?= $kpis['tickets_encours'] ?> en cours
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panier Moyen -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-kpi bg-info text-white">
                            <div class="card-body p-3">
                                <div class="kpi-icon">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                                <div class="kpi-value">
                                    <?= number_format($kpis['panier_moyen'], 0, ',', ' ') ?> FCFA
                                </div>
                                <div class="kpi-label">Panier Moyen</div>
                                <div class="kpi-subtext">
                                    <?= number_format($kpis['total_articles'], 0, ',', ' ') ?> articles
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profit Net -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-kpi bg-warning text-dark">
                            <div class="card-body p-3">
                                <div class="kpi-icon">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                                <div class="kpi-value">
                                    <?= number_format($kpis['profit_net'], 0, ',', ' ') ?> FCFA
                                </div>
                                <div class="kpi-label">Profit Net</div>
                                <div class="kpi-subtext">
                                    Marge: <?= number_format($kpis['marge_brute'], 1) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row g-4 mb-4">
                    <!-- Évolution du CA -->
                    <div class="col-12 col-lg-8">
                        <div class="card-kpi bg-white">
                            <div class="card-header-custom">
                                Évolution du Chiffre d'Affaires (Journalier)
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartEvolutionCA"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statuts des Tickets -->
                    <div class="col-12 col-lg-4">
                        <div class="card-kpi bg-white">
                            <div class="card-header-custom">
                                Répartition des Tickets
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartStatuts"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php foreach($statutTickets as $statut): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="statut-badge statut-<?= htmlspecialchars($statut['statut']) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $statut['statut'])) ?>
                                            </span>
                                            <span class="fw-bold"><?= $statut['nb_tickets'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Services et Clients -->
                <div class="row g-4 mb-4">
                    <!-- Top Services -->
                    <div class="col-12 col-lg-6">
                        <div class="card-kpi bg-white">
                            <div class="card-header-custom d-flex justify-content-between align-items-center">
                                <span>Top 10 Services</span>
                                <span class="badge bg-primary"><?= count($topServices) ?> services</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-analytics">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Service</th>
                                                <th class="text-center">Quantité</th>
                                                <th class="text-end pe-3">Revenu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($topServices as $service): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="fw-medium"><?= htmlspecialchars($service['nom_service']) ?></div>
                                                    <small class="text-muted">
                                                        <?= number_format($service['prix_unitaire'], 0, ',', ' ') ?> FCFA/un
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $service['total_quantite'] ?></span>
                                                </td>
                                                <td class="text-end pe-3 fw-bold">
                                                    <?= number_format($service['revenu_total'], 0, ',', ' ') ?> FCFA
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Clients -->
                    <div class="col-12 col-lg-6">
                        <div class="card-kpi bg-white">
                            <div class="card-header-custom d-flex justify-content-between align-items-center">
                                <span>Top 10 Clients</span>
                                <span class="badge bg-success"><?= count($topClients) ?> clients</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-analytics">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Client</th>
                                                <th class="text-center">Commandes</th>
                                                <th class="text-end pe-3">CA Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($topClients as $client): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="fw-medium"><?= htmlspecialchars($client['nom_complet']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($client['telephone']) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?= $client['nb_tickets'] ?></span>
                                                </td>
                                                <td class="text-end pe-3 fw-bold text-success">
                                                    <?= number_format($client['ca_total'], 0, ',', ' ') ?> FCFA
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dépenses et Performance Agences -->
                <div class="row g-4">
                    <!-- Dépenses -->
                    <div class="col-12 col-lg-6">
                        <div class="card-kpi bg-white">
                            <div class="card-header-custom">
                                Dépenses par Catégorie
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-analytics">
                                        <thead>
                                            <tr>
                                                <th>Catégorie</th>
                                                <th class="text-end">Montant</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($depensesParCategorie as $depense): 
                                                $percent = ($kpis['depenses_total'] > 0) ? ($depense['total_depenses'] / $kpis['depenses_total']) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td class="text-capitalize"><?= htmlspecialchars($depense['categorie']) ?></td>
                                                <td class="text-end fw-bold text-danger">
                                                    <?= number_format($depense['total_depenses'], 0, ',', ' ') ?> FCFA
                                                </td>
                                                <td class="text-end">
                                                    <?= number_format($percent, 1) ?>%
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (!empty($depensesParCategorie)): ?>
                                            <tr class="table-active fw-bold">
                                                <td>Total Dépenses</td>
                                                <td class="text-end">
                                                    <?= number_format($kpis['depenses_total'], 0, ',', ' ') ?> FCFA
                                                </td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Performance Agences -->
                    <div class="col-12 col-lg-6">
                        <div class="card-kpi bg-white">
                            <div class="card-header-custom d-flex justify-content-between align-items-center">
                                <span>Performance par Agence</span>
                                <span class="badge bg-warning"><?= count($performanceAgences) ?> agences</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-analytics">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Agence</th>
                                                <th class="text-center">CA</th>
                                                <th class="text-end pe-3">Part</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($performanceAgences as $agence): 
                                                $percent = ($kpis['ca_total'] > 0) ? ($agence['CA'] / $kpis['ca_total']) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td class="ps-3 fw-medium"><?= htmlspecialchars($agence['nom_agence']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $agence['nb_commandes'] ?></span>
                                                    <div class="small text-muted">
                                                        <?= number_format($agence['CA'], 0, ',', ' ') ?> FCFA
                                                    </div>
                                                </td>
                                                <td class="pe-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress progress-analytics flex-grow-1 me-2">
                                                            <div class="progress-bar bg-success" 
                                                                 style="width: <?= $percent ?>%">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold"><?= number_format($percent, 1) ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modes de Paiement -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card-kpi bg-white">
                            <div class="card-header-custom">
                                Modes de Paiement Utilisés
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach($paiementsParMode as $paiement): 
                                        $percent = ($kpis['ca_total'] > 0) ? ($paiement['total_paye'] / $kpis['ca_total']) * 100 : 0;
                                    ?>
                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                        <div class="text-center p-3 border rounded shadow-sm">
                                            <div class="fw-bold mb-2 text-capitalize">
                                                <?= htmlspecialchars($paiement['mode_paiement']) ?>
                                            </div>
                                            <div class="h5 mb-1 text-primary">
                                                <?= number_format($paiement['total_paye'], 0, ',', ' ') ?> FCFA
                                            </div>
                                            <div class="small text-muted mb-2">
                                                <?= $paiement['nb_paiements'] ?> paiements
                                            </div>
                                            <div class="progress progress-analytics">
                                                <div class="progress-bar bg-info" style="width: <?= $percent ?>%"></div>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <?= number_format($percent, 1) ?>%
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../js/bootstrap.bundle.min.js"></script>
    <script src="../../js/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Données pour les graphiques
            const evolutionLabels = <?= json_encode(array_column($evolutionCA, 'jour')) ?>;
            const evolutionData = <?= json_encode(array_column($evolutionCA, 'ca_journalier')) ?>;
            
            const statutLabels = <?= json_encode(array_column($statutTickets, 'statut')) ?>;
            const statutData = <?= json_encode(array_column($statutTickets, 'nb_tickets')) ?>;
            
            const statutColors = {
                'en_attente': '#f8d7da',
                'en_traitement': '#fff3cd', 
                'pret': '#d1ecf1',
                'recupere': '#d4edda'
            };
            
            const statutBgColors = statutLabels.map(label => statutColors[label] || '#e9ecef');

            // Graphique Évolution CA
            if (document.getElementById('chartEvolutionCA') && evolutionLabels.length > 0) {
                const ctx1 = document.getElementById('chartEvolutionCA').getContext('2d');
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: evolutionLabels,
                        datasets: [{
                            label: 'CA Journalier (FCFA)',
                            data: evolutionData,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#3498db',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'CA: ' + context.parsed.y.toLocaleString('fr-FR') + ' FCFA';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('fr-FR') + ' FCFA';
                                    }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            } else {
                document.getElementById('chartEvolutionCA').innerHTML = 
                    '<div class="text-center text-muted py-5"><i class="bi bi-bar-chart display-4"></i><p class="mt-2">Aucune donnée disponible</p></div>';
            }

            // Graphique Statuts
            if (document.getElementById('chartStatuts') && statutLabels.length > 0) {
                const ctx2 = document.getElementById('chartStatuts').getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: statutLabels.map(label => {
                            return label.charAt(0).toUpperCase() + label.slice(1).replace('_', ' ');
                        }),
                        datasets: [{
                            data: statutData,
                            backgroundColor: statutBgColors,
                            borderWidth: 1,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            } else {
                document.getElementById('chartStatuts').innerHTML = 
                    '<div class="text-center text-muted py-5"><i class="bi bi-pie-chart display-4"></i><p class="mt-2">Aucune donnée disponible</p></div>';
            }
        });
    </script>
</body>
</html>

<?php require_once '../../templates/footer.php'; ?>