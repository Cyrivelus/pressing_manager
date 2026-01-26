<?php
// pages/admin/Rapports/agence_stats.php

session_start();

// Vérifier l'authentification et les permissions
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_agences.php');
require_once(__DIR__ . '/../../../fonctions/gestion_reports.php');

/**
 * NOTE : La fonction getTopServicesAgence() a été retirée d'ici 
 * car elle est déjà déclarée dans fonctions/gestion_agences.php
 */

// Récupérer l'ID de l'agence depuis l'URL
$id_agence = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupérer l'agence
$agence = getAgenceById($pdo, $id_agence);
if (!$agence) {
    header("Location: index.php?error=Agence non trouvée");
    exit();
}

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

// Récupérer les statistiques de l'agence
$stats_agence = getPerformancesAgence($pdo, $id_agence, $periode);

// Récupérer les statistiques globales pour comparaison
$all_agences = getAllAgences($pdo);
$active_agences = array_filter($all_agences, function($a) { return $a['est_actif'] == 1; });

// Calculer les moyennes globales
$total_ca_global = 0;
$total_tickets_global = 0;
foreach ($active_agences as $a) {
    if ($a['id_agence'] != $id_agence) {
        $stats_temp = getPerformancesAgence($pdo, $a['id_agence'], $periode);
        $total_ca_global += ($stats_temp['total_ventes'] ?? 0);
        $total_tickets_global += ($stats_temp['total_tickets'] ?? 0);
    }
}

$nb_agences_comparaison = count($active_agences) - 1;
$moyenne_ca_global = $nb_agences_comparaison > 0 ? $total_ca_global / $nb_agences_comparaison : 0;
$moyenne_tickets_global = $nb_agences_comparaison > 0 ? $total_tickets_global / $nb_agences_comparaison : 0;

// Récupérer les données nécessaires (Fonctions issues de gestion_agences.php ou gestion_reports.php)
$top_services = getTopServicesAgence($pdo, $id_agence, $date_debut, $date_fin);
$performances_jour = $stats_agence['ventes_par_jour'] ?? [];


$top_clients = getTopClientsAgence($pdo, $id_agence, $date_debut, $date_fin);

// Fonctions utilitaires d'affichage
function calculerVariation($valeur, $moyenne): float {
    if ($moyenne == 0) return 0;
    return (($valeur - $moyenne) / $moyenne) * 100;
}



$title = "Statistiques - " . htmlspecialchars($agence['nom_agence']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BailCompta 360 | <?= $title ?></title>
    <link rel="stylesheet" href="../../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin_style.css">
    <script src="../../../js/jquery-1.12.4.min.js"></script>
    <script src="../../../js/bootstrap-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #3498db; }
        .stat-card.ca { border-left-color: #27ae60; }
        .stat-card.tickets { border-left-color: #e74c3c; }
        .stat-card.moyenne { border-left-color: #9b59b6; }
        .stat-card.completion { border-left-color: #f39c12; }
        .stat-number { font-size: 24px; font-weight: bold; margin: 10px 0; }
        .agence-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; }
        .chart-container { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/../../../templates/header.php'); ?>
    <?php include(__DIR__ . '/../../../templates/navigation.php'); ?>
    
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12">
                <div class="agence-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h1><?= htmlspecialchars($agence['nom_agence']) ?></h1>
                            <p><?= htmlspecialchars($agence['adresse'] ?? 'Pas d\'adresse') ?> | <?= htmlspecialchars($agence['telephone'] ?? '') ?></p>
                            <span class="label label-<?= $agence['est_actif'] ? 'success' : 'danger' ?>">
                                <?= $agence['est_actif'] ? 'Agence Active' : 'Agence Inactive' ?>
                            </span>
                        </div>
                        <div class="col-md-4 text-right no-print">
                            <a href="index.php" class="btn btn-default"><i class="glyphicon glyphicon-arrow-left"></i> Retour</a>
                            <a href="modifier_agence.php?id=<?= $id_agence ?>" class="btn btn-primary"><i class="glyphicon glyphicon-edit"></i> Modifier</a>
                        </div>
                    </div>
                </div>

                <div class="well no-print">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 style="margin:0">Analyse : <strong><?= $titre_periode ?></strong></h4>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="btn-group">
                                <?php foreach ($periode_options as $key => $label): ?>
                                    <a href="?id=<?= $id_agence ?>&periode=<?= $key ?>" class="btn btn-sm <?= $periode == $key ? 'btn-primary' : 'btn-default' ?>"><?= $label ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card ca">
                            <div class="stat-label">Chiffre d'affaires</div>
                            <div class="stat-number text-success"><?= number_format($stats_agence['total_ventes'] ?? 0, 0, ',', ' ') ?> FCFA</div>
                            <div class="stat-comparison"><?= formaterVariation(calculerVariation($stats_agence['total_ventes'] ?? 0, $moyenne_ca_global)) ?> vs réseau</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card tickets">
                            <div class="stat-label">Tickets</div>
                            <div class="stat-number text-danger"><?= $stats_agence['total_tickets'] ?? 0 ?></div>
                            <div class="stat-comparison"><?= formaterVariation(calculerVariation($stats_agence['total_tickets'] ?? 0, $moyenne_tickets_global)) ?> vs réseau</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card moyenne">
                            <div class="stat-label">Panier moyen</div>
                            <div class="stat-number text-primary">
                                <?= $stats_agence['total_tickets'] > 0 ? number_format($stats_agence['total_ventes'] / $stats_agence['total_tickets'], 0, ',', ' ') : 0 ?> FCFA
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card completion">
                            <div class="stat-label">Taux de complétion</div>
                            <div class="stat-number text-warning">
                                <?php $taux = ($stats_agence['total_tickets'] > 0) ? (($stats_agence['tickets_termines'] ?? 0) / $stats_agence['total_tickets']) * 100 : 0; ?>
                                <?= number_format($taux, 1) ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h4>Évolution du CA</h4>
                            <canvas id="caChart" height="100"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h4>Répartition Tickets</h4>
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">Services les plus vendus</div>
                            <table class="table">
                                <thead><tr><th>Service</th><th>Qté</th><th>CA</th></tr></thead>
                                <tbody>
                                    <?php foreach ($top_services as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['nom_service']) ?></td>
                                        <td><?= $s['quantite_vendue'] ?></td>
                                        <td><?= number_format($s['chiffre_affaires'], 0, ',', ' ') ?> FCFA</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">Top Clients</div>
                            <table class="table">
                                <thead><tr><th>Client</th><th>Tickets</th><th>Dépenses</th></tr></thead>
                                <tbody>
                                    <?php foreach ($top_clients as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['nom_client']) ?></td>
                                        <td><?= $c['nb_tickets'] ?></td>
                                        <td><?= number_format($c['montant_total'], 0, ',', ' ') ?> FCFA</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const performancesJour = <?= json_encode($performances_jour) ?>;
        const statsAgence = <?= json_encode($stats_agence) ?>;

        new Chart(document.getElementById('caChart'), {
            type: 'line',
            data: {
                labels: performancesJour.map(p => p.jour),
                datasets: [{
                    label: 'Ventes (FCFA)',
                    data: performancesJour.map(p => p.ventes),
                    borderColor: '#27ae60',
                    fill: true,
                    backgroundColor: 'rgba(39, 174, 96, 0.1)'
                }]
            }
        });

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Terminés', 'En cours'],
                datasets: [{
                    data: [statsAgence.tickets_termines || 0, statsAgence.tickets_en_cours || 0],
                    backgroundColor: ['#27ae60', '#e74c3c']
                }]
            }
        });
    </script>
</body>
</html>