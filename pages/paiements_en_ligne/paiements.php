<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Passerelle de Paiements Digitaux";

// 1. Récupération des transactions récentes (Mobile Money, Carte, etc.)
$sql = "SELECT p.*, t.numero_ticket, c.nom_client, c.telephone
        FROM paiements p
        JOIN tickets t ON p.id_ticket = t.id_ticket
        JOIN clients c ON t.id_client = c.id_client
        WHERE p.mode_paiement IN ('mobile', 'carte')
        ORDER BY p.date_paiement DESC LIMIT 20";
$transactions = $pdo->query($sql)->fetchAll();

require_once '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-credit-card me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivez et gérez les paiements effectués via les plateformes en ligne</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="fas fa-sync"></i> Synchroniser API</button>
            <button class="btn btn-success"><i class="fas fa-file-excel"></i> Rapport de Réconciliation</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning-soft p-2 rounded me-3">
                        <i class="fas fa-mobile-alt text-warning fa-2x"></i>
                    </div>
                    <small class="text-muted fw-bold">MOBILE MONEY</small>
                </div>
                <h3 class="fw-bold m-0">850 000 <small class="fs-6">FCFA</small></h3>
                <small class="text-success fw-bold">+12% cette semaine</small>
            </div>
        </div>

        

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-info-soft p-2 rounded me-3">
                        <i class="fas fa-credit-card text-info fa-2x"></i>
                    </div>
                    <small class="text-muted fw-bold">CARTES BANCAIRES</small>
                </div>
                <h3 class="fw-bold m-0">320 000 <small class="fs-6">FCFA</small></h3>
                <small class="text-muted">Paiements Visa/Mastercard</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white text-center">
                <small class="opacity-75 fw-bold d-block mb-2">TAUX D'ABANDON PANIER</small>
                <h2 class="fw-bold m-0">14 %</h2>
                <div class="progress mt-2" style="height: 5px; background: rgba(255,255,255,0.1);">
                    <div class="progress-bar bg-warning" style="width: 14%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white text-center">
                <small class="opacity-75 fw-bold d-block mb-2">FRAIS DE PLATEFORME</small>
                <h3 class="fw-bold m-0">1.5 %</h3>
                <small>Commission moyenne prélevée</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between">
            <h6 class="fw-bold mb-0">Journal des flux digitaux</h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown">Tous les opérateurs</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Référence / Date</th>
                        <th>Client</th>
                        <th>N° Ticket</th>
                        <th class="text-center">Opérateur</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center pe-4">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $t): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold"><?= $t['reference'] ?></span><br>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($t['date_paiement'])) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($t['nom_client']) ?></div>
                            <small class="text-muted"><?= $t['telephone'] ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark">#<?= $t['numero_ticket'] ?></span></td>
                        <td class="text-center">
                            <?php if($t['mode_paiement'] == 'mobile'): ?>
                                <img src="/assets/img/momo_orange.png" height="25" alt="Momo">
                            <?php else: ?>
                                <i class="fab fa-cc-visa text-primary fa-lg"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold"><?= number_format($t['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-center pe-4">
                            <span class="badge bg-success-soft text-success rounded-pill px-3">Validé</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>