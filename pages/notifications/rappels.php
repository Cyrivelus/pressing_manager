<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Rappels & Linge en Souffrance";

// 1. Récupération des tickets prêts non retirés depuis plus de 3 jours
$sql = "SELECT t.id_ticket, t.numero_ticket, t.date_pret, c.nom_client, c.telephone, c.email,
        DATEDIFF(CURDATE(), t.date_pret) as jours_retard,
        (SELECT SUM(montant_total) FROM tickets WHERE id_ticket = t.id_ticket) as total_du
        FROM tickets t
        JOIN clients c ON t.id_client = c.id_client
        WHERE t.statut = 'pret' AND t.date_retrait IS NULL
        HAVING jours_retard >= 3
        ORDER BY jours_retard DESC";
$en_souffrance = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><i class="fas fa-hourglass-half me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Gérez les commandes non retirées et optimisez l'espace de stockage</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger"><i class="fas fa-bullhorn"></i> Relance Massive (SMS)</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-danger">
                <small class="text-muted fw-bold text-uppercase">Tickets en attente (>3j)</small>
                <h2 class="fw-bold m-0"><?= count($en_souffrance) ?></h2>
                <small class="text-danger">占 Occupation inutile des convoyeurs</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-warning">
                <small class="text-muted fw-bold text-uppercase">Trésorerie Immobilisée</small>
                <?php 
                    $total_immo = array_sum(array_column($en_souffrance, 'total_du'));
                ?>
                <h2 class="fw-bold m-0 text-warning"><?= number_format($total_immo, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <small class="text-muted">Paiements attendus au retrait</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold text-uppercase">Retard Record</small>
                <h2 class="fw-bold m-0"><?= count($en_souffrance) > 0 ? $en_souffrance[0]['jours_retard'] : 0 ?> Jours</h2>
                <small class="text-info">Client : <?= count($en_souffrance) > 0 ? htmlspecialchars($en_souffrance[0]['nom_client']) : 'Aucun' ?></small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between">
            <h6 class="fw-bold mb-0">Commandes prêtes en attente de retrait</h6>
            <span class="badge bg-light text-dark">Filtrer par quartier</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Ticket</th>
                        <th>Client</th>
                        <th class="text-center">Prêt depuis</th>
                        <th class="text-center">Retard</th>
                        <th class="text-center">Statut Relance</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($en_souffrance as $s): 
                        $alerte = ($s['jours_retard'] > 15) ? 'danger' : (($s['jours_retard'] > 7) ? 'warning' : 'secondary');
                    ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold">#<?= $s['numero_ticket'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($s['nom_client']) ?></div>
                            <small class="text-muted"><?= $s['telephone'] ?></small>
                        </td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($s['date_pret'])) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $alerte ?>-soft text-<?= $alerte ?>">
                                <?= $s['jours_retard'] ?> jours
                            </span>
                        </td>
                        <td class="text-center">
                            <small class="text-muted italic">Dernière relance : Hier (SMS)</small>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-success" title="Relancer par WhatsApp"><i class="fab fa-whatsapp"></i></button>
                                <button class="btn btn-sm btn-outline-primary" title="Appeler"><i class="fas fa-phone"></i></button>
                                <button class="btn btn-sm btn-outline-danger" title="Avis de vente après délai"><i class="fas fa-gavel"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>