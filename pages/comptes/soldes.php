<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Situation des Soldes & Trésorerie";

try {
    // 2. Récupération des soldes actuels par compte
    $sql_soldes = "SELECT * FROM comptes ORDER BY type_compte ASC";
    $soldes = $pdo->query($sql_soldes)->fetchAll();

    // 3. Calcul du total global
    $total_global = 0;
    foreach ($soldes as $s) {
        $total_global += $s['solde'];
    }

    // 4. Statistiques rapides (Répartition par type)
    $sql_repartition = "SELECT type_compte, SUM(solde) as total_type 
                        FROM comptes GROUP BY type_compte";
    $repartition = $pdo->query($sql_repartition)->fetchAll();

} catch (PDOException $e) {
    $db_error = "Erreur d'accès aux soldes : " . $e->getMessage();
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-chart-pie me-2 text-primary"></i>Situation de Trésorerie</h2>
            <p class="text-muted">Consultez la disponibilité de vos fonds en temps réel</p>
        </div>
        <div class="text-md-end">
            <h5 class="text-muted mb-0">Total Disponible Global</h5>
            <h1 class="fw-bold text-primary"><?= number_format($total_global, 0, ',', ' ') ?> <small class="fs-4">FCFA</small></h1>
        </div>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-danger shadow-sm rounded-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $db_error ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Détail par Compte / Mode de règlement</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4">Compte</th>
                                    <th>Type</th>
                                    <th>Dernière Mise à jour</th>
                                    <th class="text-end pe-4">Solde Actuel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($soldes)): ?>
                                    <tr><td colspan="4" class="text-center py-5">Aucun compte trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($soldes as $s): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-blue-soft text-primary rounded-circle p-2 me-3 text-center" style="width: 40px;">
                                                    <i class="fas <?= $s['type_compte'] == 'Banque' ? 'fa-university' : ($s['type_compte'] == 'Caisse' ? 'fa-cash-register' : 'fa-mobile-alt') ?>"></i>
                                                </div>
                                                <span class="fw-bold"><?= htmlspecialchars($s['nom_compte']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-light text-dark border"><?= $s['type_compte'] ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d/m/Y à H:i') ?> </td>
                                        <td class="text-end pe-4">
                                            <h5 class="fw-bold mb-0 <?= $s['solde'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                                <?= number_format($s['solde'], 0, ',', ' ') ?> 
                                            </h5>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-3 text-center">
                    <a href="comptes_gestion.php" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                        <i class="fas fa-exchange-alt me-1"></i> Gérer les mouvements
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Répartition des fonds</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($repartition as $rep): 
                        $pourcentage = ($total_global > 0) ? ($rep['total_type'] / $total_global) * 100 : 0;
                        $couleur = ($rep['type_compte'] == 'Caisse') ? 'bg-success' : (($rep['type_compte'] == 'Banque') ? 'bg-primary' : 'bg-warning');
                    ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold small"><?= $rep['type_compte'] ?></span>
                                <span class="small text-muted"><?= number_format($pourcentage, 1) ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar <?= $couleur ?> rounded-pill" style="width: <?= $pourcentage ?>%"></div>
                            </div>
                            <div class="mt-1 small text-end fw-bold"><?= number_format($rep['total_type'], 0, ',', ' ') ?> FCFA</div>
                        </div>
                    <?php endforeach; ?>

                    <hr class="my-4 opacity-25">

                    <div class="bg-light p-3 rounded-4">
                        <h6 class="fw-bold small mb-3 text-uppercase text-muted text-center">Notes de trésorerie</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2"><i class="fas fa-info-circle text-primary me-2"></i> Les soldes Mobile Money incluent les commissions en attente.</li>
                            <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> Pensez à faire votre versement bancaire si la caisse dépasse 100 000 FCFA.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-blue-soft { background-color: #e3f2fd; }
    .progress-bar { transition: width 1s ease-in-out; }
    .card { border: none !important; }
</style>

<?php require_once '../../templates/footer.php'; ?>