<?php
// DÉBUT AJOUTÉ - Démarrer le tampon de sortie
ob_start();

// pages/admin/agences/agence_stats.php
session_start();

// Vérifier l'authentification et les permissions
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    ob_end_flush(); // AJOUTÉ
    exit();
}

require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_agences.php');
// Récupérer l'ID de l'agence depuis l'URL
$id_agence = isset($_GET['id']) ? intval($_GET['id']) : 0;



// Récupérer l'agence avec vérification de null
$agence = getAgenceById($pdo, $id_agence);


// Récupérer la période
$periode = $_GET['periode'] ?? 'mois';
$periode_options = [
    'jour' => 'Aujourd\'hui',
    'semaine' => '7 derniers jours',
    'mois' => 'Ce mois',
    'annee' => 'Cette année'
];

// Définir les dates selon la période
$now = new DateTime();
switch ($periode) {
    case 'jour':
        $date_debut = $now->format('Y-m-d');
        $date_fin = $now->format('Y-m-d');
        $titre_periode = 'Aujourd\'hui';
        break;
    case 'semaine':
        $date_debut = $now->modify('-7 days')->format('Y-m-d');
        $date_fin = date('Y-m-d');
        $titre_periode = '7 derniers jours';
        break;
    case 'mois':
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-t');
        $titre_periode = date('F Y');
        break;
    case 'annee':
        $date_debut = date('Y-01-01');
        $date_fin = date('Y-12-31');
        $titre_periode = date('Y');
        break;
    default:
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-t');
        $titre_periode = date('F Y');
}

// Récupérer les statistiques de l'agence avec vérification
$stats_agence = getPerformancesAgence($pdo, $id_agence, $periode);
if (!$stats_agence) {
    $stats_agence = [
        'total_tickets' => 0,
        'total_ventes' => 0,
        'tickets_en_cours' => 0,
        'tickets_termines' => 0,
        'ventes_par_jour' => []
    ];
}

// Récupérer les statistiques globales pour comparaison
$all_agences = getAllAgences($pdo);
$active_agences = array_filter($all_agences, function($a) { 
    return isset($a['est_actif']) && $a['est_actif'] == 1; 
});

// Calculer les moyennes globales
$total_ca_global = 0;
$total_tickets_global = 0;
foreach ($active_agences as $a) {
    if (isset($a['id_agence']) && $a['id_agence'] != $id_agence) {
        $stats_temp = getPerformancesAgence($pdo, $a['id_agence'], $periode);
        if ($stats_temp) {
            $total_ca_global += $stats_temp['total_ventes'] ?? 0;
            $total_tickets_global += $stats_temp['total_tickets'] ?? 0;
        }
    }
}

$nb_agences_comparaison = count($active_agences) - 1;
if ($nb_agences_comparaison < 1) $nb_agences_comparaison = 1;
$moyenne_ca_global = $nb_agences_comparaison > 0 ? $total_ca_global / $nb_agences_comparaison : 0;
$moyenne_tickets_global = $nb_agences_comparaison > 0 ? $total_tickets_global / $nb_agences_comparaison : 0;

// Récupérer les meilleurs services
$top_services = getTopServicesAgence($pdo, $id_agence, $date_debut, $date_fin);

// Récupérer les performances par jour
$performances_jour = $stats_agence['ventes_par_jour'] ?? [];

// Récupérer les clients les plus fidèles
$top_clients = getTopClientsAgence($pdo, $id_agence, $date_debut, $date_fin);

// Fonction pour obtenir les services les plus vendus
function getTopServicesAgence(PDO $pdo, int $id_agence, string $date_debut, string $date_fin): array {
    try {
        $sql = "SELECT 
                   s.nom_service,
                   s.categorie,
                   COUNT(lt.id_ligne) as quantite_vendue,
                   SUM(lt.sous_total) as chiffre_affaires,
                   AVG(lt.sous_total) as prix_moyen
                FROM lignes_ticket lt
                JOIN services s ON lt.id_service = s.id_service
                JOIN tickets t ON lt.id_ticket = t.id_ticket
                WHERE t.id_agence = :id_agence
                AND DATE(t.date_depot) BETWEEN :date_debut AND :date_fin
                AND t.statut != 'annule'
                GROUP BY s.id_service, s.nom_service, s.categorie
                ORDER BY quantite_vendue DESC
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_agence', $id_agence, PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des top services: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les clients les plus fidèles
function getTopClientsAgence(PDO $pdo, int $id_agence, string $date_debut, string $date_fin): array {
    try {
        $sql = "SELECT 
                   c.id_client,
                   c.nom_client,
                   c.prenom_client,
                   c.telephone,
                   COUNT(DISTINCT t.id_ticket) as nb_tickets,
                   SUM(t.montant_total) as montant_total,
                   MAX(t.date_depot) as dernier_passage
                FROM clients c
                JOIN tickets t ON c.id_client = t.id_client
                WHERE t.id_agence = :id_agence
                AND DATE(t.date_depot) BETWEEN :date_debut AND :date_fin
                AND t.statut != 'annule'
                GROUP BY c.id_client, c.nom_client, c.prenom_client, c.telephone
                ORDER BY montant_total DESC
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_agence', $id_agence, PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des top clients: " . $e->getMessage());
        return [];
    }
}

// Fonction pour calculer le pourcentage de variation
function calculerVariation($valeur, $moyenne): float {
    if ($moyenne == 0) return 0;
    return (($valeur - $moyenne) / $moyenne) * 100;
}

// Fonction pour formater la variation avec icône et couleur
function formaterVariation($variation): string {
    if ($variation > 0) {
        return '<span class="text-success"> +' . number_format($variation, 1) . '%</span>';
    } elseif ($variation < 0) {
        return '<span class="text-danger"> ' . number_format($variation, 1) . '%</span>';
    } else {
        return '<span class="text-muted"> 0%</span>';
    }
}

// Fonction pour formater les montants en FCFA
function formatFCFA($montant): string {
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

$title = "Statistiques - " . htmlspecialchars($agence['nom_agence'] ?? '');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BailCompta 360 | <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin_style.css">
    <script src="../../../js/jquery-1.12.4.min.js"></script>
    <script src="../../../js/bootstrap-3.4.1.min.js"></script>
    <style>
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            min-height: 150px;
        }
        
        .stat-card.ca {
            border-left-color: #27ae60;
        }
        
        .stat-card.tickets {
            border-left-color: #e74c3c;
        }
        
        .stat-card.moyenne {
            border-left-color: #9b59b6;
        }
        
        .stat-card.completion {
            border-left-color: #f39c12;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            line-height: 1.2;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .stat-comparison {
            font-size: 0.85em;
            margin-top: 5px;
            color: #6c757d;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 300px;
        }
        
        .table-responsive {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .period-selector {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .agence-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .export-buttons {
            margin-top: 20px;
            padding: 15px 0;
        }
        
        .metric-icon {
            font-size: 24px;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .chart-placeholder {
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        
        @media (max-width: 768px) {
            .stat-number {
                font-size: 20px;
            }
            
            .agence-header h1 {
                font-size: 24px;
            }
            
            .period-selector .btn-group {
                display: flex;
                flex-wrap: wrap;
            }
            
            .period-selector .btn {
                margin: 2px;
                flex: 1;
                min-width: 120px;
            }
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .stat-card, .chart-container, .table-responsive {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
            
            .btn-group {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/../../../templates/header.php'); ?>
    <?php include(__DIR__ . '/../../../templates/navigation.php'); ?>
    
<BR> <BR> <BR>
    <div class="container-fluid">
        <main class="col-md-12 ml-sm-auto col-lg-12 px-4">
            <!-- En-tête de l'agence avec vérifications -->
            <div class="agence-header">
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <h1><?= htmlspecialchars($agence['nom_agence'] ?? '') ?></h1>
                        <p class="lead">
                            
                            <?= htmlspecialchars($agence['adresse'] ?? '') ?>
                            <?php if (!empty($agence['telephone'])): ?>
                                | <?= htmlspecialchars($agence['telephone']) ?>
                            <?php endif; ?>
                            <?php if (!empty($agence['email'])): ?>
                                |  <?= htmlspecialchars($agence['email']) ?>
                            <?php endif; ?>
                        </p>
                        <p>
                            <strong>Responsable:</strong> <?= htmlspecialchars($agence['responsable_nom'] ?? '') ?>
                            | <strong>Ouverture:</strong> 
                            <?= (!empty($agence['date_ouverture']) && $agence['date_ouverture'] != '0000-00-00') ? 
                                date('d/m/Y', strtotime($agence['date_ouverture'])) : '' ?>
                            | <strong>Statut:</strong> 
                            <span class="label label-<?= (!empty($agence['est_actif']) && $agence['est_actif'] == 1) ? 'success' : 'danger' ?>">
                                <?= (!empty($agence['est_actif']) && $agence['est_actif'] == 1) ? 'Active' : '' ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-4 col-sm-12 text-right">
                        <div class="btn-group" role="group">
                            <a href="index.php" class="btn btn-default">
                               <- Retour
                            </a>
                            <a href="modifier_agence.php?id=<?= $id_agence ?>" class="btn btn-primary">
                                 Modifier
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sélecteur de période -->
            <div class="period-selector">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <h4>Période d'analyse: <strong><?= $titre_periode ?></strong></h4>
                        <p>Du <?= date('d/m/Y', strtotime($date_debut)) ?> au <?= date('d/m/Y', strtotime($date_fin)) ?></p>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="btn-group btn-group-justified" role="group">
                            <?php foreach ($periode_options as $key => $label): ?>
                                <a href="?id=<?= $id_agence ?>&periode=<?= $key ?>" 
                                   class="btn btn-sm btn-<?= $periode == $key ? 'primary' : 'default' ?>">
                                    <?= $label ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cartes de statistiques principales -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card ca">
                        <div class="metric-icon">
                           
                        </div>
                        <div class="stat-number text-success">
                            <?= formatFCFA($stats_agence['total_ventes'] ?? 0) ?>
                        </div>
                        <div class="stat-label">Chiffre d'affaires</div>
                        <div class="stat-comparison">
                            <?php 
                            $variation_ca = calculerVariation($stats_agence['total_ventes'] ?? 0, $moyenne_ca_global);
                            echo formaterVariation($variation_ca);
                            ?>
                            vs moyenne
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card tickets">
                        <div class="metric-icon">
                           
                        </div>
                        <div class="stat-number text-danger">
                            <?= $stats_agence['total_tickets'] ?? 0 ?>
                        </div>
                        <div class="stat-label">Tickets traités</div>
                        <div class="stat-comparison">
                            <?php 
                            $variation_tickets = calculerVariation($stats_agence['total_tickets'] ?? 0, $moyenne_tickets_global);
                            echo formaterVariation($variation_tickets);
                            ?>
                            vs moyenne
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card moyenne">
                        <div class="metric-icon">
                         
                        </div>
                        <div class="stat-number text-primary">
                            <?php 
                            $total_tickets = $stats_agence['total_tickets'] ?? 0;
                            $total_ventes = $stats_agence['total_ventes'] ?? 0;
                            if ($total_tickets > 0) {
                                echo formatFCFA($total_ventes / $total_tickets);
                            } else {
                                echo '0 FCFA';
                            }
                            ?>
                        </div>
                        <div class="stat-label">Panier moyen</div>
                        <div class="stat-comparison">
                            <?= $total_tickets ?> tickets
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card completion">
                        <div class="metric-icon">
                           
                        </div>
                        <div class="stat-number text-warning">
                            <?php 
                            $tickets_termines = $stats_agence['tickets_termines'] ?? 0;
                            $total_tickets = $stats_agence['total_tickets'] ?? 0;
                            $taux_completion = $total_tickets > 0 ? 
                                ($tickets_termines / $total_tickets) * 100 : 0;
                            echo number_format($taux_completion, 1) . '%';
                            ?>
                        </div>
                        <div class="stat-label">Taux de complétion</div>
                        <div class="stat-comparison">
                            <?= $tickets_termines ?> / <?= $total_tickets ?> tickets
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Graphiques et données -->
            <div class="row">
                <div class="col-md-8 col-sm-12">
                    <div class="chart-container">
                        <h4> Évolution du chiffre d'affaires</h4>
                        <?php if (!empty($performances_jour)): ?>
                            <canvas id="caChart" height="250"></canvas>
                        <?php else: ?>
                            <div class="chart-placeholder">
                                Aucune donnée disponible pour cette période
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4 col-sm-12">
                    <div class="chart-container">
                        <h4> Répartition des statuts</h4>
                        <?php if (($stats_agence['total_tickets'] ?? 0) > 0): ?>
                            <canvas id="statusChart" height="250"></canvas>
                        <?php else: ?>
                            <div class="chart-placeholder">
                                Aucun ticket sur cette période
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Top services et clients -->
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="table-responsive">
                        <h4> Services les plus vendus</h4>
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Catégorie</th>
                                    <th class="text-right">Quantité</th>
                                    <th class="text-right">CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_services)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                             Aucun service vendu sur cette période
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($top_services as $service): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($service['nom_service']) ?></td>
                                            <td>
                                                <span class="label label-default">
                                                    <?= htmlspecialchars($service['categorie'] ?? 'Non classé') ?>
                                                </span>
                                            </td>
                                            <td class="text-right"><?= $service['quantite_vendue'] ?></td>
                                            <td class="text-right text-success">
                                                <strong><?= formatFCFA($service['chiffre_affaires']) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6 col-sm-12">
                    <div class="table-responsive">
                        <h4>Clients les plus fidèles</h4>
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Téléphone</th>
                                    <th class="text-right">Tickets</th>
                                    <th class="text-right">Total dépensé</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_clients)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Aucun client sur cette période
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($top_clients as $client): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($client['nom_client'] ?? '') ?> <?= htmlspecialchars($client['prenom_client'] ?? '') ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($client['telephone'] ?? 'Non renseigné') ?></td>
                                            <td class="text-right"><?= $client['nb_tickets'] ?></td>
                                            <td class="text-right text-success">
                                                <strong><?= formatFCFA($client['montant_total']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php if (!empty($client['dernier_passage'])): ?>
                                                        Dernier: <?= date('d/m/Y', strtotime($client['dernier_passage'])) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Informations complémentaires -->
            <div class="row">
                <div class="col-md-12">
                    <div class="info-card">
                        <h4> Analyse et recommandations</h4>
                        <div class="row">
                            <div class="col-md-4 col-sm-12">
                                <p><strong>Performance relative:</strong></p>
                                <?php if (($stats_agence['total_ventes'] ?? 0) > $moyenne_ca_global): ?>
                                    <div class="alert alert-success">
                                      
                                        Performance <strong><?= number_format($variation_ca, 1) ?>%</strong> supérieure à la moyenne
                                    </div>
                                <?php elseif (($stats_agence['total_ventes'] ?? 0) < $moyenne_ca_global): ?>
                                    <div class="alert alert-warning">
                                      
                                        Performance <strong><?= number_format(abs($variation_ca), 1) ?>%</strong> inférieure à la moyenne
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                    
                                        Performance dans la moyenne
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 col-sm-12">
                                <p><strong>Recommandations:</strong></p>
                                <?php if (($stats_agence['total_tickets'] ?? 0) < 10): ?>
                                    <div class="alert alert-info">
                                      
                                        Augmenter la visibilité pour attirer plus de clients
                                    </div>
                                <?php elseif ($taux_completion < 80): ?>
                                    <div class="alert alert-warning">
                                       
                                        Optimiser le traitement des tickets en cours
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        
                                        Bonne performance globale
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 col-sm-12">
                                <p><strong>Actions rapides:</strong></p>
                                <div class="list-group">
                                   
                                    <a href="javascript:window.print()" class="list-group-item no-print">
                                       Imprimer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Boutons d'export -->
          
            
        </main>
    </div>
    
    <?php include(__DIR__ . '/../../../templates/footer.php'); ?>
    
    <script>
        // Données pour les graphiques
        const performancesJour = <?= json_encode($performances_jour) ?>;
        const statsAgence = <?= json_encode($stats_agence) ?>;
        
        // Graphique CA si données disponibles
        if (performancesJour && performancesJour.length > 0) {
            // Préparer les données pour le graphique CA
            const labels = performancesJour.map(p => {
                const date = new Date(p.jour);
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            });
            
            const caData = performancesJour.map(p => parseFloat(p.ventes) || 0);
            
            // Créer le graphique
            const caCtx = document.getElementById('caChart').getContext('2d');
            const caChart = new Chart(caCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Chiffre d\'affaires (FCFA)',
                        data: caData,
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.y;
                                    return 'CA: ' + value.toLocaleString('fr-FR') + ' FCFA';
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
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }
        
        // Graphique statuts si données disponibles
        if (statsAgence && (statsAgence.total_tickets || 0) > 0) {
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Terminés', 'En cours'],
                    datasets: [{
                        data: [
                            statsAgence.tickets_termines || 0,
                            statsAgence.tickets_en_cours || 0
                        ],
                        backgroundColor: [
                            '#27ae60',
                            '#e74c3c'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = statsAgence.total_tickets || 1;
                                    const value = context.parsed;
                                    const percentage = Math.round((value / total) * 100);
                                    return context.label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Auto-refresh optionnel (désactivé par défaut)
        /*
        setTimeout(function() {
            if (confirm('Voulez-vous rafraîchir les statistiques ?')) {
                window.location.reload();
            }
        }, 300000); // 5 minutes
        */
    </script>
</body>
</html>
<?php
// FIN AJOUTÉ - Nettoyer le tampon
ob_end_flush();
?>