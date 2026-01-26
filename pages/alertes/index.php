<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Alertes & Notifications";

// Récupérer les produits en seuil critique
$sql = "SELECT * FROM consommables WHERE quantite_stock <= seuil_alerte AND est_actif = 1";
$alertes_stock = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-bell text-danger me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Surveillance en temps réel de votre exploitation</p>
        </div>
        <a href="parametres_alertes.php" class="btn btn-outline-primary shadow-sm">
            <i class="fas fa-cog me-1"></i> Configurer les seuils
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-danger text-white">
                <h6 class="text-uppercase opacity-75 small fw-bold">Stocks Critiques</h6>
                <h2 class="fw-bold"><?= count($alertes_stock) ?></h2>
                <p class="mb-0 small">Produits nécessitant un réapprovisionnement immédiat</p>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Alertes de Stock Actives</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th class="ps-4">Produit</th>
                                <th class="text-center">Stock Actuel</th>
                                <th class="text-center">Seuil Alerte</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($alertes_stock)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Aucune alerte pour le moment. Tout est sous contrôle !</td></tr>
                            <?php endif; ?>
                            <?php foreach($alertes_stock as $a): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($a['nom_produit']) ?></div>
                                    <small class="text-muted"><?= $a['categorie'] ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger-soft text-danger fw-bold fs-6">
                                        <?= $a['quantite_stock'] ?> <?= $a['unite'] ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted"><?= $a['seuil_alerte'] ?> <?= $a['unite'] ?></td>
                                <td class="text-end pe-4">
                                    <a href="../stock/approvisionner.php?id=<?= $a['id_produit'] ?>" class="btn btn-sm btn-dark">Réapprovisionner</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>