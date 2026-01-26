<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Alertes & Réapprovisionnement";

// 2. Récupération uniquement des articles en alerte (Stock Actuel <= Stock Alerte)
$sql = "SELECT * FROM consommables 
        WHERE stock_actuel <= stock_alerte 
        ORDER BY (stock_actuel / stock_alerte) ASC"; // Les plus critiques en premier
$stmt = $pdo->query($sql);
$alertes = $stmt->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .critical-low { border-left: 5px solid #e74c3c !important; }
    .warning-low { border-left: 5px solid #f39c12 !important; }
    .progress-stock { height: 10px; border-radius: 5px; }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><i class="fas fa-exclamation-circle"></i> <?= $titre ?></h2>
            <p class="text-muted">Articles nécessitant une commande immédiate</p>
        </div>
        <button onclick="window.print()" class="btn btn-dark">
            <i class="fas fa-print"></i> Liste de courses
        </button>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <?php if (empty($alertes)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <div class="text-success mb-3"><i class="fas fa-check-circle fa-4x"></i></div>
                    <h5>Tous les stocks sont optimaux !</h5>
                    <p class="text-muted">Aucun consommable n'a atteint son seuil d'alerte.</p>
                </div>
            <?php else: ?>
                <?php foreach ($alertes as $a): 
                    $ratio = ($a['stock_actuel'] / $a['stock_alerte']) * 100;
                    $status_class = ($a['stock_actuel'] == 0) ? 'critical-low' : 'warning-low';
                    $bar_color = ($a['stock_actuel'] == 0) ? 'bg-danger' : 'bg-warning';
                ?>
                <div class="card border-0 shadow-sm mb-3 <?= $status_class ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($a['nom_article']) ?></h6>
                                <small class="text-muted">Catégorie : <?= $a['categorie'] ?></small>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Niveau : <?= $a['stock_actuel'] ?> / <?= $a['stock_alerte'] ?></span>
                                    <span class="fw-bold"><?= round($ratio) ?>%</span>
                                </div>
                                <div class="progress progress-stock">
                                    <div class="progress-bar <?= $bar_color ?>" style="width: <?= $ratio ?>%"></div>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="passer_commande.php?id=<?= $a['id_consommable'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-shopping-cart"></i> Commander
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <h5 class="fw-bold mb-4">Commandes Groupées</h5>
                <?php
                // Simulation de groupement par fournisseur
                $sql_f = "SELECT fournisseur_habituel, COUNT(*) as nb FROM consommables WHERE stock_actuel <= stock_alerte GROUP BY fournisseur_habituel";
                $groupes = $pdo->query($sql_f)->fetchAll();
                
                foreach($groupes as $g):
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded-3 shadow-sm">
                    <div>
                        <span class="fw-bold d-block"><?= htmlspecialchars($g['fournisseur_habituel'] ?? 'Inconnu') ?></span>
                        <small class="text-muted"><?= $g['nb'] ?> article(s) à commander</small>
                    </div>
                    <i class="fas fa-chevron-right text-primary"></i>
                </div>
                <?php endforeach; ?>
                
                <hr>
                <p class="small text-muted">
                    <i class="fas fa-info-circle"></i> Les seuils d'alerte sont basés sur une consommation moyenne de 15 jours de production.
                </p>
            </div>
        </div>
    </div>
</div>



<?php require_once  '../../templates/footer.php'; ?>