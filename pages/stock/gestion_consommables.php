<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Accès réservé au staff/administration
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Consommables & Stocks";

try {
 
    
    // 2. Récupération des consommables avec indicateur d'alerte
    // On calcule 'alerte' si le stock actuel est inférieur ou égal au seuil
    $sql = "SELECT *, (stock_actuel <= stock_alerte) as en_alerte 
            FROM consommables 
            ORDER BY en_alerte DESC, nom_article ASC";
    $stmt = $pdo->query($sql);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Statistiques rapides pour les compteurs
    $total_articles = count($articles);
    $articles_alerte = count(array_filter($articles, function($a) { return $a['en_alerte']; }));

} catch (PDOException $e) {
    die("<div class='alert alert-danger m-5'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</div>");
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .status-indicator { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .bg-alerte { background-color: #dc3545; box-shadow: 0 0 8px #dc3545; }
    .bg-ok { background-color: #28a745; }
    .card-stat { border-radius: 12px; transition: transform 0.2s; }
    .card-stat:hover { transform: translateY(-3px); }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><?= $titre ?></h2>
            <p class="text-muted">Suivi des produits chimiques, emballages et fournitures d'atelier.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="http://localhost/pressing_manager/pages/stock/add_product.php" class="btn btn-outline-primary">
                 Nouvel Article
            </a>
            <a href="http://localhost/pressing_manager/pages/consommables/passer_commande.php?id=1" class="btn btn-primary shadow-sm">
                Commander Fournisseur
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-light-primary text-primary rounded-circle p-3 me-3">
                      
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Total Articles</h6>
                        <span class="h4 fw-bold"><?= $total_articles ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm bg-white p-3 border-start border-danger border-4">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-light-danger text-danger rounded-circle p-3 me-3">
                       
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Stocks Critiques</h6>
                        <span class="h4 fw-bold text-danger"><?= $articles_alerte ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">État</th>
                            <th>Désignation de l'Article</th>
                            <th>Catégorie</th>
                            <th class="text-center">Stock Actuel</th>
                            <th>Seuil d'Alerte</th>
                            <th>Dernier Prix</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                   
                                    Aucun article en stock.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($articles as $a): ?>
                                <tr class="<?= $a['en_alerte'] ? 'table-light' : '' ?>">
                                    <td class="ps-4">
                                        <span class="status-indicator <?= $a['en_alerte'] ? 'bg-alerte' : 'bg-ok' ?>" 
                                              title="<?= $a['en_alerte'] ? 'Stock Critique' : 'Stock suffisant' ?>"></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($a['nom_article']) ?></span>
                                        <br><small class="text-muted"><?= htmlspecialchars($a['conditionnement'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light border text-dark fw-normal"><?= htmlspecialchars($a['categorie']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="h6 fw-bold <?= $a['en_alerte'] ? 'text-danger font-blink' : 'text-success' ?>">
                                            <?= number_format($a['stock_actuel'], 2) ?> <?= htmlspecialchars($a['unite_mesure']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?= number_format($a['stock_alerte'], 2) ?> <?= htmlspecialchars($a['unite_mesure']) ?></td>
                                    <td><?= number_format($a['dernier_prix_achat'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm">
                                            <a href="http://localhost/pressing_manager/pages/stock/ajuster_stock.php?id=<?= $a['id_consommable'] ?>" 
                                               class="btn btn-sm btn-white border" title="Ajuster manuellement">
                                                Ajuster
                                            </a>
                                            <a href="http://localhost/pressing_manager/pages/consommables/historique.php?id=<?= $a['id_consommable'] ?>" 
                                               class="btn btn-sm btn-white border text-primary" title="Voir l'historique">
                                               Voir l'historique
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>