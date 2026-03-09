<?php
require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');


// 1. Définition de la date (Aujourd'hui par défaut ou date choisie)
$date_rapport = $_GET['date'] ?? date('Y-m-d');

// 2. Requête pour les Recettes (Paiements encaissés)
$sql_recettes = "SELECT mode_paiement, SUM(montant) as total 
                 FROM paiements 
                 WHERE DATE(date_paiement) = :date 
                 GROUP BY mode_paiement";
$stmt = $pdo->prepare($sql_recettes);
$stmt->execute([':date' => $date_rapport]);
$paiements = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retourne [mode => total]

// 3. Requête pour les Dépenses
$sql_depenses = "SELECT categorie, SUM(montant) as total 
                 FROM depenses 
                 WHERE date_depense = :date 
                 GROUP BY categorie";
$stmt = $pdo->prepare($sql_depenses);
$stmt->execute([':date' => $date_rapport]);
$depenses = $stmt->fetchAll();

// 4. Statistiques des Tickets du jour
$sql_tickets = "SELECT 
                    COUNT(id_ticket) as nb_tickets, 
                    SUM(montant_total) as CA_prevu,
                    SUM(montant_verse) as total_encaisse
                 FROM tickets 
                 WHERE DATE(date_depot) = :date";
$stmt = $pdo->prepare($sql_tickets);
$stmt->execute([':date' => $date_rapport]);
$stats_tickets = $stmt->fetch();

// Calculs totaux
$total_recettes = array_sum($paiements);
$total_depenses = array_sum(array_column($depenses, 'total'));
$solde_net = $total_recettes - $total_depenses;
?>
</BR></BR></BR></BR>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3"><i class="fas fa-chart-line text-primary"></i> Rapport Journalier</h2>
        <form class="d-flex align-items-center bg-white p-2 rounded shadow-sm">
            <label class="me-2 mb-0">Date :</label>
            <input type="date" name="date" class="form-control form-control-sm me-2" value="<?= $date_rapport ?>">
            <button type="submit" class="btn btn-sm btn-primary">Consulter</button>
        </form>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Recettes Totales</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_recettes, 0, ',', ' ') ?> FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Dépenses Totales</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_depenses, 0, ',', ' ') ?> FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Bénéfice Net (Caisse)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($solde_net, 0, ',', ' ') ?> FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Nouveaux Tickets</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_tickets['nb_tickets'] ?: 0 ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition des Recettes</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Mode</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($paiements)): ?>
                                <tr><td colspan="2" class="text-center text-muted">Aucun encaissement</td></tr>
                            <?php else: ?>
                                <?php foreach($paiements as $mode => $montant): ?>
                                <tr>
                                    <td class="text-capitalize"><?= $mode ?></td>
                                    <td class="text-end font-weight-bold"><?= number_format($montant, 0, ',', ' ') ?> FCFA</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-danger">Détail des Dépenses</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Catégorie</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($depenses)): ?>
                                <tr><td colspan="2" class="text-center text-muted">Aucune dépense enregistrée</td></tr>
                            <?php else: ?>
                                <?php foreach($depenses as $d): ?>
                                <tr>
                                    <td class="text-capitalize"><?= $d['categorie'] ?></td>
                                    <td class="text-end font-weight-bold"><?= number_format($d['total'], 0, ',', ' ') ?> FCFA</td>
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

<style>
    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
    .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
    .border-left-danger { border-left: 0.25rem solid #e74a3b !important; }
    .text-xs { font-size: .7rem; }
</style>
<?php
require_once(__DIR__ . '/../../templates/footer.php');
?>