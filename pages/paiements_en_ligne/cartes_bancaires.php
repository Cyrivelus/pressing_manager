<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Flux Cartes Bancaires";

// 1. Statistiques des transactions par carte
$sql_stats = "SELECT 
                COUNT(*) as nb_trans, 
                SUM(montant) as total_ca,
                AVG(montant) as panier_moyen
              FROM paiements 
              WHERE mode_paiement = 'carte' AND date_paiement >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stats = $pdo->query($sql_stats)->fetch();

// 2. Liste des dernières transactions par carte
$sql_list = "SELECT p.*, t.numero_ticket, c.nom_client 
             FROM paiements p
             JOIN tickets t ON p.id_ticket = t.id_ticket
             JOIN clients c ON t.id_client = c.id_client
             WHERE p.mode_paiement = 'carte'
             ORDER BY p.date_paiement DESC LIMIT 15";
$transactions = $pdo->query($sql_list)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-credit-card me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivi des encaissements Visa, Mastercard et terminaux bancaires</p>
        </div>
        <div class="btn-group shadow-sm">
            <button class="btn btn-outline-primary"><i class="fas fa-print"></i> Relevé journalier</button>
            <button class="btn btn-primary"><i class="fas fa-plus"></i> Saisie manuelle TPE</button>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold text-uppercase">Volume (30j)</small>
                <h2 class="fw-bold m-0 text-primary"><?= number_format($stats['total_ca'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <p class="small text-muted mb-0 mt-2">Sur <?= $stats['nb_trans'] ?> transactions</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-info">
                <small class="text-muted fw-bold text-uppercase">Panier Moyen Carte</small>
                <h2 class="fw-bold m-0"><?= number_format($stats['panier_moyen'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <p class="small text-info mb-0 mt-2"><i class="fas fa-info-circle"></i> Supérieur de 25% aux espèces</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold text-uppercase">Statut Terminal (TPE)</small>
                <div class="d-flex align-items-center mt-2">
                    <span class="pulse-green me-2"></span>
                    <h5 class="mb-0">Connecté - OK</h5>
                </div>
                <small class="opacity-50 mt-2 d-block">Dernier télécollecte : Hier 22:30</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0">Transactions Bancaires Récentes</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small">
                    <tr>
                        <th class="ps-4">Date / Heure</th>
                        <th>Client</th>
                        <th>Ticket</th>
                        <th class="text-center">Type</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center pe-4">Référence Bancaire</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $t): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="small text-muted"><?= date('d/m/y H:i', strtotime($t['date_paiement'])) ?></span>
                        </td>
                        <td><span class="fw-bold"><?= htmlspecialchars($t['nom_client']) ?></span></td>
                        <td><span class="badge bg-light text-dark">#<?= $t['numero_ticket'] ?></span></td>
                        <td class="text-center">
                            <i class="fab fa-cc-visa text-primary fa-lg" title="Visa"></i>
                        </td>
                        <td class="text-end fw-bold"><?= number_format($t['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-center pe-4">
                            <code class="small text-uppercase"><?= $t['reference'] ?: 'TPE-LOCAL-' . $t['id_paiement'] ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .pulse-green {
        width: 12px; height: 12px; background: #28a745; border-radius: 50%;
        display: inline-block; box-shadow: 0 0 0 rgba(40, 167, 69, 0.4);
        animation: pulse 2s infinite;
    }
    @keyframes pulse { 
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }
</style>

<?php require_once $root . '/templates/footer.php'; ?>