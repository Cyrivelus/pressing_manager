<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Gestion Mobile Money (Momo/OM)";

// --- LOGIQUE DE TRAITEMENT PUSH ---
$notification = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_push'])) {
    $num = htmlspecialchars($_POST['numero_client']);
    $montant = htmlspecialchars($_POST['montant']);
    // Ici votre logique d'appel API (Orange/MTN)
    $notification = "<div class='alert alert-warning border-0 shadow-sm fw-bold'>[PUSH ENVOYÉ] Demande de $montant FCFA vers le $num initiée...</div>";
}

// 1. Statistiques (Dynamique ou simulation)
$stats_momo = ['total' => 450000, 'count' => 28, 'color' => '#ffcc00'];
$stats_om = ['total' => 620000, 'count' => 42, 'color' => '#ff6600'];

// 2. Récupération des transactions
$sql = "SELECT p.*, t.numero_ticket, c.nom_client 
        FROM paiements p
        JOIN tickets t ON p.id_ticket = t.id_ticket
        JOIN clients c ON t.id_client = c.id_client
        WHERE p.mode_paiement = 'mobile'
        ORDER BY p.date_paiement DESC LIMIT 10";
$transactions = $pdo->query($sql)->fetchAll();

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    /* Design sans icônes */
    .bg-momo { background-color: #ffcc00; }
    .bg-om { background-color: #ff6600; }
    #panelPush { display: none; animation: slideDown 0.4s ease-out; }
    @keyframes slideDown { from { opacity:0; transform: translateY(-20px); } to { opacity:1; transform: translateY(0); } }
    .status-badge { font-size: 0.7rem; letter-spacing: 1px; padding: 4px 10px; border-radius: 50px; font-weight: 900; }
</style>

<div class="container-fluid py-5">
    
    <?= $notification ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h2 class="fw-bold m-0 text-dark">[GATEWAY] <?= $titre ?></h2>
            <p class="text-muted">Interfaçage direct avec les API USSD Orange et MTN</p>
        </div>
        <button class="btn btn-dark fw-bold px-4 shadow-sm" onclick="togglePushPanel()">
            + NOUVELLE DEMANDE PUSH
        </button>
    </div>

    <div id="panelPush" class="card border-0 shadow-lg mb-5 bg-warning shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold m-0 text-dark">LANCER UN PUSH USSD (PAIEMENT DIRECT)</h4>
                <button type="button" class="btn-close" onclick="togglePushPanel()"></button>
            </div>
            <form method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action_push" value="1">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase">Numéro de téléphone</label>
                    <input type="text" name="numero_client" class="form-control form-control-lg border-0" placeholder="ex: 699000000" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase">Montant (FCFA)</label>
                    <input type="number" name="montant" class="form-control form-control-lg border-0 fw-bold" value="5000" required>
                </div>
                <div class="col-md-5">
                    <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold shadow">DÉCLENCHER LE PAIEMENT</button>
                </div>
                <div class="col-12 mt-2">
                    <p class="small mb-0 text-dark opacity-75 fw-bold">Note: Le client devra valider la transaction sur son écran de téléphone.</p>
                </div>
            </form>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="d-flex">
                    <div style="width: 12px;" class="bg-momo"></div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Volume MTN MoMo</small>
                                <h2 class="fw-bold mb-0"><?= number_format($stats_momo['total'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                            </div>
                            <span class="badge bg-light text-dark h-50 border fw-bold">OP: MTN</span>
                        </div>
                        <div class="mt-3 small fw-bold text-warning text-uppercase">
                            <?= $stats_momo['count'] ?> Transactions réussies
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="d-flex">
                    <div style="width: 12px;" class="bg-om"></div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Volume Orange Money</small>
                                <h2 class="fw-bold mb-0"><?= number_format($stats_om['total'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                            </div>
                            <span class="badge bg-light text-dark h-50 border fw-bold">OP: OCM</span>
                        </div>
                        <div class="mt-3 small fw-bold text-danger text-uppercase">
                            <?= $stats_om['count'] ?> Transactions réussies
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-dark text-white py-3 border-0">
            <h6 class="fw-bold mb-0 text-uppercase">Flux financier Mobile Money (Temps Réel)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Réf. Transaction</th>
                        <th>Client / Source</th>
                        <th class="text-center">Vérification</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center pe-4">État API</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $t): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="text-monospace fw-bold text-primary small"><?= $t['reference'] ?></span><br>
                            <small class="text-muted fw-bold">LE <?= date('d/m/Y', strtotime($t['date_paiement'])) ?> À <?= date('H:i', strtotime($t['date_paiement'])) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($t['nom_client']) ?></div>
                            <div class="small fw-bold text-muted">TICKET #<?= $t['numero_ticket'] ?></div>
                        </td>
                        <td class="text-center">
                            <span class="status-badge bg-light border text-success">VERIFIED_SMS</span>
                        </td>
                        <td class="text-end fw-bold text-dark fs-5"><?= number_format($t['montant'], 0, ',', ' ') ?> <small class="fs-6 text-muted">CFA</small></td>
                        <td class="text-center pe-4">
                            <span class="badge bg-success text-white px-3 py-1">SUCCÈS</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function togglePushPanel() {
        const panel = document.getElementById('panelPush');
        if (panel.style.display === 'block') {
            panel.style.display = 'none';
        } else {
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
</script>

<?php require_once '../../templates/footer.php'; ?>