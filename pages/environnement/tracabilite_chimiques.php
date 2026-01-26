<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Traçabilité des Produits Chimiques";

try {
    // 2. Récupération des données
    $query = "SELECT * FROM produits_chimiques ORDER BY degre_dangerosite DESC";
    $stmt = $pdo->query($query);
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_error = "Table non initialisée ou erreur SQL : " . $e->getMessage();
    $produits = []; // Évite l'erreur dans le foreach
}

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <div>
            <h2 class="fw-bold m-0 text-success"><i class="fas fa-flask me-2"></i><?= $titre ?></h2>
            <p class="text-muted small">Registre de sécurité conforme aux normes environnementales</p>
        </div>
        <div class="btn-group shadow-sm">
            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-exclamation-triangle"></i> Signalement</button>
            <button class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nouveau Produit</button>
        </div>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fas fa-tools me-2"></i> <b>Action requise :</b> La table n'a pas été trouvée. Veuillez exécuter le script SQL fourni.
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-danger text-white rounded-4">
                <small class="opacity-75 fw-bold">SOLVANTS CRITIQUES</small>
                <h3 class="fw-bold m-0">
                    <?php 
                        $total_solvant = array_sum(array_map(function($p) { return $p['type_usage'] == 'Solvant Machine' ? $p['stock_actuel'] : 0; }, $produits));
                        echo number_format($total_solvant, 0);
                    ?> L
                </h3>
                <small class="mt-2 d-block"><i class="fas fa-warehouse me-1"></i> Zone de stockage A</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-4 border-warning rounded-4 h-100">
                <small class="text-muted fw-bold">ALERTES FDS</small>
                <h3 class="fw-bold m-0 text-dark">
                    <?= count(array_filter($produits, function($p) { return !$p['fds_valide']; })) ?>
                </h3>
                <small class="text-danger small mt-2 d-block"><i class="fas fa-sync me-1"></i> Mises à jour requises</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-dark text-white rounded-4 h-100">
                <small class="opacity-75 fw-bold">DERNIÈRE COLLECTE BSDD</small>
                <h3 class="fw-bold m-0">12/01</h3>
                <small class="text-info small mt-2 d-block"><i class="fas fa-truck me-1"></i> Eco-Clean Service</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Substance</th>
                        <th>Usage</th>
                        <th>Quantité</th>
                        <th>Sécurité FDS</th>
                        <th>MàJ</th>
                        <th class="text-end pe-4">Fiche PDF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                        <tr><td colspan="6" class="text-center py-5">Aucun produit chimique répertorié.</td></tr>
                    <?php else: ?>
                        <?php foreach($produits as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas <?= $p['icon_danger'] ?> text-<?= $p['couleur_alerte'] ?> fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($p['nom_produit']) ?></div>
                                        <span class="badge bg-light text-muted border-0 small p-0"><?= $p['code_interne'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $p['type_usage'] ?></span></td>
                            <td class="fw-bold"><?= $p['stock_actuel'] ?> <small class="text-muted"><?= $p['unite'] ?></small></td>
                            <td>
                                <?php if($p['fds_valide']): ?>
                                    <span class="badge bg-success-soft text-success"><i class="fas fa-check me-1"></i> Valide</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-soft text-danger"><i class="fas fa-times me-1"></i> Expirée</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('d/m/y', strtotime($p['date_derniere_maj'])) ?></td>
                            <td class="text-end pe-4">
                                <a href="<?= $p['url_fds'] ?>" class="btn btn-sm btn-light border" target="_blank">
                                    <i class="fas fa-file-pdf text-danger"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
</style>

<?php require_once  '../../templates/footer.php'; ?>