<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion Mobile Money (Momo/OM)";

// 1. Statistiques par opérateur (Simulation)
$stats_momo = ['total' => 450000, 'count' => 28, 'color' => '#ffcc00']; // MTN
$stats_om = ['total' => 620000, 'count' => 42, 'color' => '#ff6600'];   // Orange

// 2. Récupération des transactions Mobile Money uniquement
$sql = "SELECT p.*, t.numero_ticket, c.nom_client 
        FROM paiements p
        JOIN tickets t ON p.id_ticket = t.id_ticket
        JOIN clients c ON t.id_client = c.id_client
        WHERE p.mode_paiement = 'mobile'
        ORDER BY p.date_paiement DESC LIMIT 10";
$transactions = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-mobile-alt text-warning me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Passerelle de paiement MTN MoMo & Orange Money</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalRequestPayment">
                <i class="fas fa-paper-plane"></i> Nouvelle demande Push
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="d-flex">
                    <div style="width: 10px; background: <?= $stats_momo['color'] ?>;"></div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">MTN MOBILE MONEY</small>
                                <h3 class="fw-bold mb-0"><?= number_format($stats_momo['total'], 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                            </div>
                            <img src="/assets/img/mtn_momo.png" height="40" alt="MTN">
                        </div>
                        <div class="mt-3 small text-muted">
                            <i class="fas fa-history me-1"></i> <?= $stats_momo['count'] ?> transactions ce mois
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="d-flex">
                    <div style="width: 10px; background: <?= $stats_om['color'] ?>;"></div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">ORANGE MONEY</small>
                                <h3 class="fw-bold mb-0"><?= number_format($stats_om['total'], 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                            </div>
                            <img src="/assets/img/orange_money.png" height="40" alt="Orange">
                        </div>
                        <div class="mt-3 small text-muted">
                            <i class="fas fa-history me-1"></i> <?= $stats_om['count'] ?> transactions ce mois
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0">Dernières transactions sécurisées</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small">
                    <tr>
                        <th class="ps-4">ID Transaction</th>
                        <th>Client / Ticket</th>
                        <th class="text-center">Mode</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center pe-4">Confirmation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $t): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="text-monospace small"><?= $t['reference'] ?></span><br>
                            <small class="text-muted"><?= date('H:i', strtotime($t['date_paiement'])) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($t['nom_client']) ?></div>
                            <small class="badge bg-light text-dark">Ticket #<?= $t['numero_ticket'] ?></small>
                        </td>
                        <td class="text-center">
                            <i class="fas fa-shield-alt text-success me-1"></i> SMS API
                        </td>
                        <td class="text-end fw-bold"><?= number_format($t['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-center pe-4">
                            <i class="fas fa-check-circle text-success" title="Validé par l'opérateur"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRequestPayment" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-warning text-dark">
                <h5 class="fw-bold m-0"><i class="fas fa-bolt me-2"></i>Lancer un Push USSD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Numéro du client (Momo/OM)</label>
                    <input type="text" class="form-control form-control-lg" placeholder="ex: 699000000">
                </div>
                <div class="mb-3">
                    <label class="form-label">Montant à prélever (FCFA)</label>
                    <input type="number" class="form-control form-control-lg fw-bold text-primary" value="5000">
                </div>
                <div class="alert alert-secondary small">
                    <i class="fas fa-info-circle me-1"></i> Le client recevra un message sur son téléphone lui demandant de saisir son code secret pour valider le paiement.
                </div>
                <button type="submit" class="btn btn-warning w-100 py-2 fw-bold shadow">
                    DÉCLENCHER LE PAIEMENT
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>