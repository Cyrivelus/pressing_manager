<?php
session_start();
require_once '../../fonctions/database.php';

// Sécurité : Vérifier si l'utilisateur est connecté et a le bon rôle
if (!isset($_SESSION['utilisateur_id']) || ($_SESSION['role'] !== 'receptionniste' && $_SESSION['role'] !== 'employe_pressing')) {
    header('Location: ../../index.php?error=Accès refusé');
    exit;
}

$agence_id = $_SESSION['agence_id'] ?? 1;

// Récupération des statistiques du jour pour l'agence
$today = date('Y-m-d');
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(id_ticket) as total_tickets,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'pret' THEN 1 ELSE 0 END) as pret_retrait
    FROM tickets 
    WHERE id_agence = ? AND DATE(date_depot) = ?
");
$stats_query->execute([$agence_id, $today]);
$stats = $stats_query->fetch();

// Récupération des 10 derniers articles à traiter (lignes_ticket)
$articles_query = $pdo->prepare("
    SELECT l.*, s.nom_service, t.numero_ticket, c.nom_client 
    FROM lignes_ticket l
    JOIN tickets t ON l.id_ticket = t.id_ticket
    JOIN services s ON l.id_service = s.id_service
    JOIN clients c ON t.id_client = c.id_client
    WHERE t.id_agence = ? AND l.statut_article != 'livre'
    ORDER BY t.date_retrait_prevue ASC
    LIMIT 10
");
$articles_query->execute([$agence_id]);
$articles = $articles_query->fetchAll();
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord Réception - <?= htmlspecialchars($_SESSION['username']) ?></title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .status-badge { font-size: 0.8rem; padding: 5px 10px; border-radius: 20px; }
        .stat-card { border: none; border-radius: 15px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">
<br> <br> <br> 


<div class="container">
    <div class="row mb-4 text-center">
        <div class="col-md-6">
            <a href="../tickets/create.php" class="btn btn-primary btn-lg w-100 py-3 shadow-sm">
                ➕ Nouveau Dépôt (Client)
            </a>
        </div>
        <div class="col-md-6">
            <a href="../clients/ajouter_client.php" class="btn btn-success btn-lg w-100 py-3 shadow-sm">
                👥 Enregistrer Nouveau Client
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-white shadow-sm p-3">
                <small class="text-muted">Tickets aujourd'hui</small>
                <h2 class="text-primary"><?= $stats['total_tickets'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white shadow-sm p-3">
                <small class="text-muted">En attente de traitement</small>
                <h2 class="text-warning"><?= $stats['en_attente'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white shadow-sm p-3">
                <small class="text-muted">Prêt pour retrait</small>
                <h2 class="text-success"><?= $stats['pret_retrait'] ?? 0 ?></h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 font-weight-bold">Flux des articles en cours</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Ticket</th>
                            <th>Client</th>
                            <th>Article / Service</th>
                            <th>Statut Actuel</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($articles as $art): ?>
                        <tr>
                            <td><strong>#<?= $art['numero_ticket'] ?></strong></td>
                            <td><?= htmlspecialchars($art['nom_client']) ?></td>
                            <td><?= htmlspecialchars($art['nom_service']) ?></td>
                            <td>
                                <span class="badge bg-info text-dark"><?= $art['statut_article'] ?></span>
                            </td>
                          
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php require_once '../../templates/footer.php'; ?>