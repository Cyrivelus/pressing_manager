<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// 1. Répartition des types d'abonnements (Camembert)
$repartition = $pdo->query("
    SELECT type_abonnement, COUNT(*) as nb 
    FROM abonnements 
    WHERE statut = 'actif' 
    GROUP BY type_abonnement
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Évolution du CA Abonnements sur les 6 derniers mois (Barres)
$evolution_ca = $pdo->query("
    SELECT DATE_FORMAT(date_debut, '%M %Y') as mois, SUM(forfait_mensuel) as total
    FROM abonnements
    WHERE date_debut > DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois
    ORDER BY date_debut ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Top 5 des clients les plus fidèles (Abonnements cumulés)
$top_clients = $pdo->query("
    SELECT c.nom_client, c.prenom_client, COUNT(a.id_abonnement) as nb_souscriptions, SUM(a.forfait_mensuel) as total_depense
    FROM abonnements a
    JOIN clients c ON a.id_client = c.id_client
    GROUP BY a.id_client
    ORDER BY total_depense DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Analytics Abonnements - BailCompta360</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">📊 Statistiques & Performance</h1>
        <a href="index.php" class="btn btn-outline-secondary">Retour à la liste</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <h5 class="card-title">Répartition des Forfaits</h5>
                    <canvas id="chartType"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">Chiffre d'Affaires (6 derniers mois)</h5>
                    <canvas id="chartCA"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white font-weight-bold">🏆 Top 5 des Clients (Abonnements)</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th class="text-center">Abonnements souscrits</th>
                        <th class="text-right">Total versé (FCFA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_clients as $client): ?>
                    <tr>
                        <td><?= htmlspecialchars($client['nom_client'] . ' ' . $client['prenom_client']) ?></td>
                        <td class="text-center"><?= $client['nb_souscriptions'] ?></td>
                        <td class="text-right fw-bold"><?= number_format($client['total_depense'], 0, ',', ' ') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<script>
// Graphique Camembert : Répartition
const ctxType = document.getElementById('chartType').getContext('2d');
new Chart(ctxType, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($repartition, 'type_abonnement')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($repartition, 'nb')) ?>,
            backgroundColor: ['#3498db', '#2ecc71', '#f1c40f', '#e67e22', '#9b59b6']
        }]
    }
});

// Graphique Barres : Évolution CA
const ctxCA = document.getElementById('chartCA').getContext('2d');
new Chart(ctxCA, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($evolution_ca, 'mois')) ?>,
        datasets: [{
            label: 'Revenus Abonnements (FCFA)',
            data: <?= json_encode(array_column($evolution_ca, 'total')) ?>,
            backgroundColor: 'rgba(52, 152, 219, 0.7)',
            borderColor: '#3498db',
            borderWidth: 1
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>