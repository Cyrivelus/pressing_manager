<?php
session_start();

// 1. Vérifier l'authentification
if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role'])) {
    header('Location: ../../login.php');
    exit;
}

// 2. Inclusion des dépendances
require_once '../../fonctions/database.php';
// Note : Le header.php contient probablement le début du HTML (<!DOCTYPE html>... <head>)
require_once '../../templates/header.php'; 

$titre = 'Flux de Trésorerie';
$message = '';
$messageType = '';

// Dates par défaut
$startDate = $_POST['start_date'] ?? date('Y-01-01');
$endDate = $_POST['end_date'] ?? date('Y-12-31');

// Initialisation des données
$cashFlowData = [
    'operating' => ['encaissements' => 0, 'depenses' => 0, 'autres_entrees' => 0, 'autres_sorties' => 0],
    'investing' => ['achats_actifs' => 0, 'ventes_actifs' => 0],
    'financing' => ['apports' => 0, 'emprunts' => 0, 'remboursements' => 0],
    'beginning_balance' => 0, 'ending_balance' => 0, 'net_change' => 0
];

// Récupérer les agences accessibles
$userAgencies = $_SESSION['agences_access'] ?? [];
if (empty($userAgencies)) {
    $stmt = $pdo->query("SELECT id_agence FROM agences");
    $userAgencies = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
$agenciesList = implode(',', array_map('intval', $userAgencies));

// 3. Traitement du formulaire
if (isset($_POST['generate_report'])) {
    try {
        // Calcul Solde Initial
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE date_paiement < ? 
                               AND id_ticket IN (SELECT id_ticket FROM tickets WHERE id_agence IN ($agenciesList))");
        $stmt->execute([$startDate]);
        $cashFlowData['beginning_balance'] = $stmt->fetchColumn();

        // Flux Exploitation (Entrées)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(p.montant), 0) FROM paiements p 
                               INNER JOIN tickets t ON p.id_ticket = t.id_ticket 
                               WHERE p.date_paiement BETWEEN ? AND ? AND t.id_agence IN ($agenciesList)");
        $stmt->execute([$startDate, $endDate]);
        $cashFlowData['operating']['encaissements'] = $stmt->fetchColumn();

        // Dépenses (Exploitation + Investissement)
        $stmt = $pdo->prepare("SELECT 
            SUM(CASE WHEN categorie IN ('salaires','electricite','eau','telephone','internet','entretien','fournitures','impots','assurance') THEN montant ELSE 0 END) as op,
            SUM(CASE WHEN categorie IN ('loyer','equipement') THEN montant ELSE 0 END) as inv,
            SUM(CASE WHEN categorie NOT IN ('salaires','electricite','eau','telephone','internet','entretien','fournitures','impots','assurance','loyer','equipement') THEN montant ELSE 0 END) as aut
            FROM depenses WHERE date_depense BETWEEN ? AND ? AND id_agence IN ($agenciesList)");
        $stmt->execute([$startDate, $endDate]);
        $exp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $cashFlowData['operating']['depenses'] = $exp['op'] ?? 0;
        $cashFlowData['investing']['achats_actifs'] = $exp['inv'] ?? 0;
        $cashFlowData['operating']['autres_sorties'] = $exp['aut'] ?? 0;

        // Calculs finaux
        $totalOp = $cashFlowData['operating']['encaissements'] - $cashFlowData['operating']['depenses'] - $cashFlowData['operating']['autres_sorties'];
        $totalInv = $cashFlowData['investing']['ventes_actifs'] - $cashFlowData['investing']['achats_actifs'];
        $cashFlowData['net_change'] = $totalOp + $totalInv;
        $cashFlowData['ending_balance'] = $cashFlowData['beginning_balance'] + $cashFlowData['net_change'];

        $message = "Rapport actualisé.";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $messageType = 'danger';
    }
}
require_once '../../templates/header.php'; 
require_once '../../templates/navigation.php'; 
?>

<style>
    .cash-flow-card { background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 25px; }
    .section-title { border-left: 4px solid #3498db; padding-left: 15px; margin: 20px 0; color: #2c3e50; }
    .amount-pos { color: #27ae60; font-weight: 600; }
    .amount-neg { color: #e74c3c; font-weight: 600; }
    .row-item { border-bottom: 1px solid #f1f1f1; padding: 10px 0; }
    .total-line { background: #f8f9fa; font-weight: bold; padding: 12px; border-radius: 5px; }
</style>
<br> <br> <br>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <?php require_once '../../templates/navigation.php'; ?>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-graph-up-arrow"></i> <?= $titre ?></h1>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body bg-light">
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold">Du</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold">Au</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" name="generate_report" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Analyser la trésorerie
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (isset($_POST['generate_report'])): ?>
                <div class="cash-flow-card">
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-7">
                            <h4 class="mb-0">Rapport de Flux</h4>
                            <small class="text-muted">Période : <?= date('d/m/Y', strtotime($startDate)) ?> au <?= date('d/m/Y', strtotime($endDate)) ?></small>
                        </div>
                        <div class="col-md-5 text-md-end mt-3 mt-md-0">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-uppercase text-muted d-block">Solde Initial</small>
                                <span class="h4 mb-0"><?= number_format($cashFlowData['beginning_balance'], 0, '.', ' ') ?> FCFA</span>
                            </div>
                        </div>
                    </div>

                    <h5 class="section-title">Activités d'exploitation</h5>
                    <div class="row-item d-flex justify-content-between">
                        <span>Encaissements Clients</span>
                        <span class="amount-pos">+ <?= number_format($cashFlowData['operating']['encaissements'], 0, '.', ' ') ?></span>
                    </div>
                    <div class="row-item d-flex justify-content-between">
                        <span>Dépenses Opérationnelles</span>
                        <span class="amount-neg">- <?= number_format($cashFlowData['operating']['depenses'], 0, '.', ' ') ?></span>
                    </div>
                    <div class="total-line d-flex justify-content-between mt-2">
                        <span>Flux net d'exploitation</span>
                        <span><?= number_format($totalOp, 0, '.', ' ') ?> FCFA</span>
                    </div>

                    <h5 class="section-title">Activités d'investissement</h5>
                    <div class="row-item d-flex justify-content-between">
                        <span>Achats d'équipements / Immobilisations</span>
                        <span class="amount-neg">- <?= number_format($cashFlowData['investing']['achats_actifs'], 0, '.', ' ') ?></span>
                    </div>
                    <div class="total-line d-flex justify-content-between mt-2">
                        <span>Flux net d'investissement</span>
                        <span><?= number_format($totalInv, 0, '.', ' ') ?> FCFA</span>
                    </div>

                    <div class="mt-5 p-4 bg-dark text-white rounded shadow">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <p class="mb-1 opacity-75">Variation nette sur la période</p>
                                <h3 class="<?= $cashFlowData['net_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= ($cashFlowData['net_change'] >= 0 ? '+' : '') . number_format($cashFlowData['net_change'], 0, '.', ' ') ?> FCFA
                                </h3>
                            </div>
                            <div class="col-md-6 text-md-end border-md-start border-secondary ps-md-4">
                                <p class="mb-1 opacity-75">SOLDE DE TRÉSORERIE FINAL</p>
                                <h2 class="fw-bold"><?= number_format($cashFlowData['ending_balance'], 0, '.', ' ') ?> FCFA</h2>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-check display-1 text-light"></i>
                    <p class="text-muted mt-3">Veuillez sélectionner une période pour générer le rapport.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php 
require_once '../../templates/footer.php'; 
?>