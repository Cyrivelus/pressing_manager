<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Accès réservé au staff/administration
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Consommables & Stocks Techniques";

// 2. Récupération des consommables avec alerte stock
try {
    
    $sql = "SELECT *, (stock_actuel <= stock_alerte) as alerte 
            FROM consommables 
            ORDER BY alerte DESC, nom_article ASC";
    $stmt = $pdo->query($sql);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div class='alert alert-danger m-5'>Erreur de base de données : " . $e->getMessage() . "</div>");
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><?= $titre ?></h2>
            <p class="text-muted">Fournitures nécessaires à l'exploitation de l'atelier</p>
        </div>
        <div class="d-flex gap-2">
            <a href="http://localhost/pressing_manager/pages/stock/add_product.php" class="btn btn-outline-primary">
                 Entrée de stock
            </a>
            <a href="http://localhost/pressing_manager/pages/consommables/passer_commande.php?id=1" class="btn btn-primary">
              Commander Fournisseur
            </a>
        </div>
    </div>

    <?php 
    $alert_count = count(array_filter($articles, function($a) { return $a['alerte']; }));
    if ($alert_count > 0): 
    ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
        
        <div>
            <h6 class="fw-bold mb-0">Attention : <?= $alert_count ?> article(s) en rupture ou stock critique !</h6>
            <small>Veuillez passer commande pour ne pas interrompre la production.</small>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Article</th>
                        <th>Catégorie</th>
                        <th class="text-center">Stock Actuel</th>
                        <th class="text-center">Seuil Alerte</th>
                        <th>Dernier Prix Unit.</th>
                        <th>Dernier Fournisseur</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Aucun consommable enregistré.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($articles as $a): ?>
                        <tr class="<?= $a['alerte'] ? 'table-danger' : '' ?>">
                            <td class="ps-4">
                                <span class="fw-bold d-block"><?= htmlspecialchars($a['nom_article']) ?></span>
                                <small class="text-muted"><?= htmlspecialchars($a['conditionnement'] ?? '') ?></small>
                            </td>
                            <td><span class="badge bg-light border text-dark"><?= htmlspecialchars($a['categorie'] ?? 'Divers') ?></span></td>
                            <td class="text-center">
                                <span class="h6 fw-bold <?= $a['alerte'] ? 'text-danger' : 'text-dark' ?>">
                                    <?= $a['stock_actuel'] ?> <?= htmlspecialchars($a['unite_mesure'] ?? '') ?>
                                </span>
                            </td>
                            <td class="text-center text-muted"><?= $a['stock_alerte'] ?></td>
                            <td><?= number_format($a['dernier_prix_achat'] ?? 0, 0, ',', ' ') ?> FCFA</td>
                            <td><?= htmlspecialchars($a['fournisseur_habituel'] ?? 'Non défini') ?></td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
    <a href="http://localhost/pressing_manager/pages/stock/ajuster_stock.php?id=<?= $a['id_consommable'] ?>" 
       class="btn btn-sm btn-light border" 
       title="Ajuster le stock">
        Ajuster
    </a>

    <a href="http://localhost/pressing_manager/pages/consommables/historique.php?id=<?= $a['id_consommable'] ?>" 
       class="btn btn-sm btn-light border text-primary" 
       title="Historique achats">
        Historisque
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

<?php require_once '../../templates/footer.php'; ?>