<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// 1. Récupération des statistiques rapides
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN date_fin >= CURDATE() AND statut = 'actif' THEN 1 ELSE 0 END) as actifs,
        SUM(CASE WHEN date_fin < CURDATE() THEN 1 ELSE 0 END) as expires
    FROM abonnements
")->fetch();

// 2. Récupération de la liste des abonnements avec infos clients
$query = "SELECT a.*, c.nom_client, c.prenom_client, c.telephone 
          FROM abonnements a 
          JOIN clients c ON a.id_client = c.id_client 
          ORDER BY a.date_fin ASC";
$abonnements = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Abonnements - BailCompta360</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/client_portal.css">
    <style>
        .card-stat { border-left: 4px solid #3498db; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        .badge-actif { background-color: #2ecc71; }
        .badge-expire { background-color: #e74c3c; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">📦 Gestion des Abonnements Mensuels</h1>
        <a href="ajouter.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel Abonnement
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-3">
                <div class="text-muted small">Total Abonnés</div>
                <div class="h4 mb-0"><?= $stats['total'] ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-3 border-success">
                <div class="text-muted small text-success">Contrats Actifs</div>
                <div class="h4 mb-0"><?= $stats['actifs'] ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-3 border-danger">
                <div class="text-muted small text-danger">Expirés / À renouveler</div>
                <div class="h4 mb-0"><?= $stats['expires'] ?></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Client</th>
                            <th>Type Forfait</th>
                            <th>Date Début</th>
                            <th>Échéance</th>
                            <th>Mensualité</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abonnements as $ab): 
                            $is_expired = strtotime($ab['date_fin']) < time();
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($ab['nom_client'] . ' ' . $ab['prenom_client']) ?></strong><br>
                                <small class="text-muted"><?= $ab['telephone'] ?></small>
                            </td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($ab['type_abonnement']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($ab['date_debut'])) ?></td>
                            <td>
                                <span class="<?= $is_expired ? 'text-danger fw-bold' : '' ?>">
                                    <?= date('d/m/Y', strtotime($ab['date_fin'])) ?>
                                </span>
                            </td>
                            <td><?= number_format($ab['forfait_mensuel'], 0, ',', ' ') ?> FCFA</td>
                            <td>
                                <?php if ($is_expired): ?>
                                    <span class="badge badge-expire">Expiré</span>
                                <?php else: ?>
                                    <span class="badge badge-actif">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="modifier.php?id=<?= $ab['id_abonnement'] ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                    <button class="btn btn-sm btn-outline-primary" onclick="relancerClient(<?= $ab['id_abonnement'] ?>)">Relancer</button>
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

<script>
function relancerClient(id) {
    if(confirm('Envoyer un rappel automatique (SMS/Email) à ce client ?')) {
        // Logique AJAX vers /api/v1/notifications/sms.php
        alert('Relance envoyée avec succès !');
    }
}
</script>

</body>
</html>