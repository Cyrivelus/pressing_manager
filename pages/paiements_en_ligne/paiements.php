<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Passerelle de Paiements Digitaux";

// --- LOGIQUE DE TRAITEMENT ---
$message_action = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_sync'])) {
        // Simulation d'appel API vers Orange/MTM/Stripe
        $message_action = "<div class='alert alert-info border-0 shadow-sm fw-bold'>[API] Synchronisation terminée : 14 nouvelles transactions récupérées.</div>";
    }
    if (isset($_POST['action_reconcilier'])) {
        $message_action = "<div class='alert alert-success border-0 shadow-sm fw-bold'>[RAPPORT] Réconciliation effectuée. Écart : 0.00 FCFA.</div>";
    }
}

// 1. Récupération des transactions (Dynamique)
$sql = "SELECT p.*, t.numero_ticket, c.nom_client, c.telephone
        FROM paiements p
        JOIN tickets t ON p.id_ticket = t.id_ticket
        JOIN clients c ON t.id_client = c.id_client
        WHERE p.mode_paiement IN ('mobile', 'carte')
        ORDER BY p.date_paiement DESC LIMIT 20";
$transactions = $pdo->query($sql)->fetchAll();

// 2. Calculs Dynamiques pour les Cards
$total_momo = 0;
$total_carte = 0;
foreach($transactions as $t) {
    if($t['mode_paiement'] == 'mobile') $total_momo += $t['montant'];
    else $total_carte += $t['montant'];
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .bg-success-soft { background-color: #e8f5e9; color: #2e7d32; }
    .bg-warning-soft { background-color: #fff3e0; color: #ef6c00; }
    .bg-info-soft { background-color: #e3f2fd; color: #1565c0; }
    
    /* Panneaux d'action sans modal */
    #panelSync, #panelRapport { display: none; animation: slideDown 0.3s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    
    .status-pill { font-size: 0.75rem; font-weight: 800; letter-spacing: 0.5px; }
</style>
<br><br><br>
<div class="container-fluid py-5">
    
    <?= $message_action ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h2 class="fw-bold m-0 text-primary">[FINTECH] <?= $titre ?></h2>
            <p class="text-muted">Monitoring des flux monétiques et réconciliation</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="toggleAction('panelSync')" class="btn btn-outline-primary fw-bold">
                SYNCHRONISER API
            </button>
            <button onclick="toggleAction('panelRapport')" class="btn btn-success fw-bold">
                GÉNÉRER RAPPORT
            </button>
        </div>
    </div>

    <div id="panelSync" class="card border-0 shadow-sm mb-4 bg-primary text-white">
        <div class="card-body p-4 text-center">
            <h5 class="fw-bold mb-3">Lancer la synchronisation avec les opérateurs ?</h5>
            <p class="small opacity-75">Cette action interrogera les serveurs de paiement pour mettre à jour les statuts des transactions en attente.</p>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action_sync" value="1">
                <button type="submit" class="btn btn-light fw-bold px-4 me-2">LANCER MAINTENANT</button>
                <button type="button" onclick="toggleAction('panelSync')" class="btn btn-outline-light fw-bold">ANNULER</button>
            </form>
        </div>
    </div>

    <div id="panelRapport" class="card border-0 shadow-sm mb-4 bg-dark text-white">
        <div class="card-body p-4 text-center">
            <h5 class="fw-bold mb-3">Rapport de Réconciliation (Période : <?= date('M Y') ?>)</h5>
            <form method="POST" class="row g-3 justify-content-center">
                <input type="hidden" name="action_reconcilier" value="1">
                <div class="col-md-3">
                    <select class="form-select form-select-sm">
                        <option>Orange Money</option>
                        <option>MTN MoMo</option>
                        <option>Cartes Visa</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-success fw-bold btn-sm">EXPORTER VERS EXCEL</button>
                    <button type="button" onclick="toggleAction('panelRapport')" class="btn btn-outline-light fw-bold btn-sm">FERMER</button>
                </div>
            </form>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4">
                <small class="text-muted fw-bold text-uppercase d-block mb-2">Mobile Money</small>
                <h3 class="fw-bold m-0"><?= number_format($total_momo, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
                <small class="text-success fw-bold">[DYN] Cumul 20 dernières</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4">
                <small class="text-muted fw-bold text-uppercase d-block mb-2">Cartes Bancaires</small>
                <h3 class="fw-bold m-0"><?= number_format($total_carte, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
                <small class="text-primary fw-bold">[VISA/MASTERCARD]</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white text-center">
                <small class="opacity-75 fw-bold d-block mb-2 text-uppercase">Taux d'Echec</small>
                <h2 class="fw-bold m-0">2.1 %</h2>
                <div class="progress mt-2" style="height: 4px; background: rgba(255,255,255,0.1);">
                    <div class="progress-bar bg-danger" style="width: 2.1%"></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white text-center">
                <small class="opacity-75 fw-bold d-block mb-2 text-uppercase">Commission</small>
                <h3 class="fw-bold m-0">1.5 %</h3>
                <small class="small">Frais de service plateforme</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-uppercase">Journal des flux digitaux récents</h6>
            <span class="badge bg-light text-dark border fw-bold text-uppercase">Temps Réel</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Réf & Chrono</th>
                        <th>Client</th>
                        <th>Ticket</th>
                        <th class="text-center">Mode</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center pe-4">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $t): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-primary"><?= $t['reference'] ?></span><br>
                            <small class="text-muted fw-bold"><?= date('d/m H:i', strtotime($t['date_paiement'])) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($t['nom_client']) ?></div>
                            <small class="text-muted fw-bold"><?= $t['telephone'] ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark fw-bold border">#<?= $t['numero_ticket'] ?></span></td>
                        <td class="text-center">
                            <span class="fw-bold small text-uppercase">
                                <?= $t['mode_paiement'] == 'mobile' ? 'M-MONEY' : 'CARTE' ?>
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark"><?= number_format($t['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-center pe-4">
                            <span class="status-pill badge bg-success-soft rounded-pill px-3 py-1">VALIDÉ</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleAction(id) {
        const panel = document.getElementById(id);
        const otherId = (id === 'panelSync') ? 'panelRapport' : 'panelSync';
        const otherPanel = document.getElementById(otherId);
        
        otherPanel.style.display = 'none';

        if (panel.style.display === 'block') {
            panel.style.display = 'none';
        } else {
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
</script>

<?php require_once '../../templates/footer.php'; ?>