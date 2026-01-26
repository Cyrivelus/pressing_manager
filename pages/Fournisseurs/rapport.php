<?php
// pages/Fournisseurs/rapport.php

ob_start();
require_once(__DIR__ . '/../../templates/navigation.php');

// 1. Vérification des permissions
if (!isset($roleUtilisateur) || !hasPermission($roleUtilisateur, 'gestion_fournisseurs')) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

// 2. Récupération de l'ID et des dates de filtrage
$fournisseur_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$date_debut = $_GET['date_debut'] ?? date('Y-m-01'); // Par défaut : début du mois
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');     // Par défaut : aujourd'hui

if (!$fournisseur_id) {
    header('Location: ' . generateUrl('pages/Fournisseurs/index.php'));
    exit();
}

try {
    // Infos du fournisseur
    $stmt = $pdo->prepare("SELECT * FROM fournisseurs WHERE id = ?");
    $stmt->execute([$fournisseur_id]);
    $fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fournisseur) {
        header('Location: ' . generateUrl('pages/Fournisseurs/index.php'));
        exit();
    }

    // STATISTIQUES POUR LE RAPPORT
    
    // A. Nombre total d'entrées de stock sur la période
    $stmtStat = $pdo->prepare("
        SELECT COUNT(h.id) as nb_mouvements, SUM(h.quantite) as total_quantite
        FROM historique_stock h
        JOIN produits p ON h.produit_id = p.id
        WHERE p.fournisseur_id = ? 
        AND h.type_mouvement = 'entree'
        AND DATE(h.date_mouvement) BETWEEN ? AND ?
    ");
    $stmtStat->execute([$fournisseur_id, $date_debut, $date_fin]);
    $stats = $stmtStat->fetch(PDO::FETCH_ASSOC);

    // B. Liste des produits les plus fournis
    $stmtTopProd = $pdo->prepare("
        SELECT p.nom_produit, SUM(h.quantite) as total_qte
        FROM historique_stock h
        JOIN produits p ON h.produit_id = p.id
        WHERE p.fournisseur_id = ? 
        AND h.type_mouvement = 'entree'
        AND DATE(h.date_mouvement) BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY total_qte DESC
    ");
    $stmtTopProd->execute([$fournisseur_id, $date_debut, $date_fin]);
    $topProduits = $stmtTopProd->fetchAll(PDO::FETCH_ASSOC);

    // C. Historique détaillé
    $stmtLogs = $pdo->prepare("
        SELECT h.*, p.nom_produit
        FROM historique_stock h
        JOIN produits p ON h.produit_id = p.id
        WHERE p.fournisseur_id = ? 
        AND DATE(h.date_mouvement) BETWEEN ? AND ?
        ORDER BY h.date_mouvement DESC
    ");
    $stmtLogs->execute([$fournisseur_id, $date_debut, $date_fin]);
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur = "Erreur de base de données : " . $e->getMessage();
}

require_once(__DIR__ . '/../../templates/header.php');
?>

<div class="container-fluid mt-4 pb-5">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Rapport : <?= htmlspecialchars($fournisseur['nom']) ?></h1>
        <button onclick="window.print()" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-print fa-sm text-white-50"></i> Imprimer le Rapport
        </button>
    </div>

    <div class="card shadow mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <input type="hidden" name="id" value="<?= $fournisseur_id ?>">
                <label class="mr-2">Période du :</label>
                <input type="date" name="date_debut" class="form-control form-control-sm mr-2" value="<?= $date_debut ?>">
                <label class="mr-2">au :</label>
                <input type="date" name="date_fin" class="form-control form-control-sm mr-2" value="<?= $date_fin ?>">
                <button type="submit" class="btn btn-sm btn-info">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Nombre de Livraisons (Période)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int)$stats['nb_mouvements'] ?> fois</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck-loading fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Articles Reçus</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int)$stats['total_quantite'] ?> unités</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 bg-dark">
                    <h6 class="m-0 font-weight-bold text-white">Top Produits Livrés</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-right">Qté Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($topProduits as $tp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tp['nom_produit']) ?></td>
                                    <td class="text-right font-weight-bold"><?= $tp['total_qte'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card shadow text-responsive">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Journal détaillé (Période)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Produit</th>
                                    <th>Qté</th>
                                    <th>Par</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($logs)): ?>
                                    <tr><td colspan="4" class="text-center py-3">Aucune donnée trouvée.</td></tr>
                                <?php else: ?>
                                    <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td><small><?= date('d/m/y H:i', strtotime($log['date_mouvement'])) ?></small></td>
                                        <td><?= htmlspecialchars($log['nom_produit']) ?></td>
                                        <td>
                                            <span class="text-<?= $log['type_mouvement'] == 'entree' ? 'success' : 'danger' ?>">
                                                <?= $log['type_mouvement'] == 'entree' ? '+' : '-' ?><?= $log['quantite'] ?>
                                            </span>
                                        </td>
                                        <td><small><?= htmlspecialchars($log['utilisateur'] ?? 'Admin') ?></small></td>
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
</div>

<style>
/* Style pour l'impression */
@media print {
    .no-print, .navbar, .sidebar, .breadcrumb, .btn {
        display: none !important;
    }
    .container-fluid {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php
require_once(__DIR__ . '/../../templates/footer.php');
ob_end_flush();
?>