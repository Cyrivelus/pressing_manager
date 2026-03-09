<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité et Authentification
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../login.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = 'Compte de Résultat (P&L)';
$message = '';
$messageType = '';

// Initialisation des dates (Défaut : mois en cours)
$startDate = $_POST['start_date'] ?? date('Y-m-01');
$endDate = $_POST['end_date'] ?? date('Y-m-t');

$revenues = [];
$expenses = [];
$netProfitLoss = 0;
$currentTotalRevenues = 0;
$currentTotalExpenses = 0;

// On déclenche le calcul si on arrive sur la page ou si on clique sur le bouton
if (isset($_POST['start_date']) || isset($_POST['generate_report'])) {
    if ($startDate > $endDate) {
        $message = "La date de début ne peut pas être postérieure à la date de fin.";
        $messageType = 'danger';
    } else {
        try {
            // --- 1. REVENUS (Tickets) ---
            $stmtRevenues = $pdo->prepare("
                SELECT 
                    DATE(date_depot) as date_periode,
                    COUNT(id_ticket) as nb_tickets,
                    SUM(montant_total) as Total_Amount
                FROM tickets 
                WHERE DATE(date_depot) BETWEEN :start_date AND :end_date
                GROUP BY DATE(date_depot)
                ORDER BY date_periode ASC
            ");
            $stmtRevenues->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $revenues = $stmtRevenues->fetchAll(PDO::FETCH_ASSOC);

            // --- 2. DÉPENSES ---
            $stmtExpenses = $pdo->prepare("
                SELECT 
                    categorie as Nom_Compte,
                    SUM(montant) as Total_Amount,
                    COUNT(id_depense) as nb_depenses,
                    GROUP_CONCAT(DISTINCT description SEPARATOR ', ') as descriptions
                FROM depenses 
                WHERE DATE(date_depense) BETWEEN :start_date AND :end_date
                GROUP BY categorie
                ORDER BY Total_Amount DESC
            ");
            $stmtExpenses->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);

            // Calcul des totaux
            $currentTotalRevenues = array_sum(array_column($revenues, 'Total_Amount'));
            $currentTotalExpenses = array_sum(array_column($expenses, 'Total_Amount'));
            $netProfitLoss = $currentTotalRevenues - $currentTotalExpenses;

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    /* Design écran */
    .stat-card { border: none; border-radius: 15px; padding: 25px; transition: transform 0.3s; color: white; }
    .bg-revenue { background: linear-gradient(45deg, #2ecc71, #27ae60); }
    .bg-expense { background: linear-gradient(45deg, #e74c3c, #c0392b); }
    .bg-net { background: linear-gradient(45deg, #3498db, #2980b9); }
    .stat-value { font-size: 1.8rem; font-weight: 800; display: block; }
    .table-pnl thead { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }

    /* --- GESTION DE L'IMPRESSION --- */
    @media print {
        /* Masquer tous les éléments de navigation et UI */
        header, footer, nav, .navbar, .sidebar, #navigation, .no-print, .btn, .card-header-form {
            display: none !important;
        }
        
        /* Ajuster le conteneur */
        .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        body { background-color: white !important; font-size: 12pt; }
        
        /* Garder les couleurs des cartes à l'impression */
        .stat-card {
            border: 1px solid #ccc !important;
            color: black !important;
            background: white !important;
            box-shadow: none !important;
            break-inside: avoid;
        }
        .stat-value { color: black !important; }
        .text-white { color: black !important; }
        
        /* Titre d'impression */
        .print-only-header { display: block !important; margin-bottom: 20px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
    }
    .print-only-header { display: none; }
</style>
<br> <br> <br>
<div class="container py-4">
    
    <div class="print-only-header">
        <h1>RAPPORT FINANCIER - KAYADE PRESSING</h1>
        <p>Période du <?= date('d/m/Y', strtotime($startDate)) ?> au <?= date('d/m/Y', strtotime($endDate)) ?></p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-5 no-print">
        <h2 class="fw-bold text-dark m-0">Rapport Financier</h2>
        <div>
            <button onclick="window.print()" class="btn btn-dark fw-bold rounded-pill shadow-sm">
                Imprimer le rapport
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase">Début de période</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase">Fin de période</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="generate_report" class="btn btn-primary w-100 fw-bold py-2">
                        ACTUALISER LES DONNÉES
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> shadow-sm"><?= $message ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card bg-revenue shadow-sm">
                <small class="text-uppercase fw-bold opacity-75">Revenus (Tickets)</small>
                <span class="stat-value"><?= number_format($currentTotalRevenues, 0, '.', ' ') ?> <small>CFA</small></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-expense shadow-sm">
                <small class="text-uppercase fw-bold opacity-75">Dépenses (Charges)</small>
                <span class="stat-value"><?= number_format($currentTotalExpenses, 0, '.', ' ') ?> <small>CFA</small></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-net shadow-sm">
                <small class="text-uppercase fw-bold opacity-75">Profit Net</small>
                <span class="stat-value"><?= number_format($netProfitLoss, 0, '.', ' ') ?> <small>CFA</small></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 fw-bold border-0">Historique des Revenus</div>
                <div class="table-responsive">
                    <table class="table table-hover table-pnl mb-0">
                        <thead>
                            <tr><th>Date</th><th class="text-center">Tickets</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($revenues)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">Aucune donnée de vente</td></tr>
                            <?php else: ?>
                                <?php foreach ($revenues as $r): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($r['date_periode'])) ?></td>
                                        <td class="text-center"><?= $r['nb_tickets'] ?></td>
                                        <td class="text-end fw-bold"><?= number_format($r['Total_Amount'], 0, '.', ' ') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 fw-bold text-danger border-0">Répartition des Dépenses</div>
                <div class="table-responsive">
                    <table class="table table-hover table-pnl mb-0">
                        <thead>
                            <tr><th>Catégorie / Détails</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($expenses)): ?>
                                <tr><td colspan="2" class="text-center text-muted py-4">Aucune dépense enregistrée</td></tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $e): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark text-capitalize"><?= htmlspecialchars($e['Nom_Compte']) ?></div>
                                            <div class="small text-muted italic"><?= htmlspecialchars($e['descriptions']) ?></div>
                                        </td>
                                        <td class="text-end fw-bold text-danger"><?= number_format($e['Total_Amount'], 0, '.', ' ') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>