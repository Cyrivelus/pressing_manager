<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Pilotage des Campagnes Marketing";

// 1. Récupération des campagnes en cours et passées
$sql = "SELECT 
            id_campagne, nom_campagne, date_debut, date_fin, budget_alloue, statut,
            (SELECT COUNT(*) FROM tickets WHERE code_promo = c.code_promo) as utilisations,
            (SELECT SUM(montant_total) FROM tickets WHERE code_promo = c.code_promo) as ca_genere
        FROM campagnes_marketing c
        ORDER BY date_debut DESC";
$campagnes = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-bullhorn text-danger me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Analysez l'efficacité de vos investissements publicitaires</p>
        </div>
        <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouvelleCampagne">
            <i class="fas fa-plus-circle"></i> Lancer une Campagne
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold mb-4 text-uppercase small text-muted">Évolution du ROI (Retour sur Investissement)</h6>
                
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100 d-flex flex-column justify-content-center">
                <small class="opacity-75 fw-bold">CHIFFRE D'AFFAIRES "CAMPAGNES"</small>
                <h2 class="fw-bold m-0 text-warning">2 450 000 <small class="fs-6">FCFA</small></h2>
                <hr class="opacity-25">
                <div class="d-flex justify-content-between small">
                    <span>Budget Total Engagé</span>
                    <span class="fw-bold">350 000 FCFA</span>
                </div>
                <div class="d-flex justify-content-between small text-success mt-2">
                    <span>Multiplicateur (ROI)</span>
                    <span class="fw-bold">x 7.0</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Nom de la Campagne</th>
                        <th>Période</th>
                        <th class="text-center">Utilisations</th>
                        <th class="text-center">Budget</th>
                        <th class="text-center">CA Généré</th>
                        <th class="text-end pe-4">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($campagnes as $c): 
                        $roi = ($c['budget_alloue'] > 0) ? ($c['ca_genere'] / $c['budget_alloue']) : 0;
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($c['nom_campagne']) ?></div>
                            <code class="small text-danger">CODE: <?= $c['code_promo'] ?></code>
                        </td>
                        <td class="small">
                            Du <?= date('d/m/y', strtotime($c['date_debut'])) ?><br>
                            Au <?= date('d/m/y', strtotime($c['date_fin'])) ?>
                        </td>
                        <td class="text-center fw-bold"><?= $c['utilisations'] ?></td>
                        <td class="text-center"><?= number_format($c['budget_alloue'], 0, ',', ' ') ?></td>
                        <td class="text-center text-primary fw-bold"><?= number_format($c['ca_genere'], 0, ',', ' ') ?></td>
                        <td class="text-end pe-4">
                            <?php if($roi >= 5): ?>
                                <span class="badge bg-success">Excellente (x<?= round($roi, 1) ?>)</span>
                            <?php elseif($roi >= 2): ?>
                                <span class="badge bg-info">Rentable</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">À optimiser</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNouvelleCampagne" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="fw-bold m-0">Paramétrer une nouvelle action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nom de l'opération</label>
                    <input type="text" class="form-control" placeholder="ex: Promo Fin d'Année 2026">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Code Promo</label>
                        <input type="text" class="form-control" placeholder="NOEL26">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Budget (FCFA)</label>
                        <input type="number" class="form-control" value="50000">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Date Début</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Date Fin</label>
                        <input type="date" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Objectif principal</label>
                    <select class="form-select">
                        <option>Acquisition nouveaux clients</option>
                        <option>Réactivation clients inactifs</option>
                        <option>Augmentation panier moyen</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold">Lancer la campagne</button>
            </form>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>