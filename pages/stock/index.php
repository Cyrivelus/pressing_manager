<?php
// pages/stock/index.php
ob_start();
require_once(__DIR__ . '/../../templates/navigation.php');

// 1. Vérification des permissions
if (!isset($_SESSION['role']) || !hasPermission($_SESSION['role'], 'gestion_stock')) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

$produits = [];
$totalArticles = 0;
$alerteStock = 0;

try {
    // Stats
    $totalArticles = $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
    $alerteStock = $pdo->query("SELECT COUNT(*) FROM produits WHERE quantite_stock <= seuil_alerte")->fetchColumn();

    // 2. Requête corrigée (Jointure sur id_fournisseur et nom_fournisseur)
    $query = "
        SELECT p.*, f.nom_fournisseur as fournisseur_nom 
        FROM produits p 
        LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id_fournisseur 
        ORDER BY p.nom_produit ASC
    ";
    $stmt = $pdo->query($query);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur = "Erreur SQL : " . $e->getMessage();
}


require_once(__DIR__ . '/../../templates/header.php');
?>

<style>
    /* Styles pour aligner le contenu avec la navigation */
    .main-content-wrapper {
        margin-left: 250px; /* Largeur de la sidebar */
        padding: 20px;
        transition: margin-left 0.3s;
        min-height: 100vh;
        background-color: #f8f9fc;
    }
    
    /* Quand la sidebar est repliée */
    body.sidebar-toggled .main-content-wrapper {
        margin-left: 90px;
    }
    
    /* Pour les petits écrans */
    @media (max-width: 768px) {
        .main-content-wrapper {
            margin-left: 0 !important;
            padding: 15px;
        }
        
        .mobile-margin-top {
            margin-top: 80px; /* Pour compenser la navbar fixe sur mobile */
        }
    }
    
    /* Cartes améliorées */
    .stat-card {
        border: none;
        border-radius: 10px;
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }
    
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
    
    .border-left-danger {
        border-left: 4px solid #e74a3b !important;
    }
    
    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }
    
    .icon-circle {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
    
    /* Table responsive */
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8f9fc;
        border-top: none;
    }
    
    .table td {
        vertical-align: middle;
        border-top: 1px solid #f0f3f7;
    }
    
    /* Boutons d'action */
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    /* Badge amélioré */
    .badge-light-border {
        border: 1px solid #e3e6f0;
        background-color: #fff;
        color: #6c757d;
        font-weight: 500;
    }
    
    /* Animation d'entrée */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="main-content-wrapper fade-in">
    <!-- Conteneur principal avec marge -->
    <div class="container-fluid">
        
        <!-- En-tête avec titre et boutons -->
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 mobile-margin-top">
            <div class="mb-3 mb-md-0">
                <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                   Gestion de l'Inventaire
                </h1>
                <p class="text-muted mb-0 small">
                    Gestion complète des stocks et alertes
                </p>
            </div>
            
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= generateUrl('pages/stock/add_product.php') ?>" class="btn btn-primary btn-sm shadow-sm">
                     Nouveau Produit
                </a>
                <a href="<?= generateUrl('pages/stock/stock_entry.php') ?>" class="btn btn-success btn-sm shadow-sm">
                     Entrée de Stock
                </a>
                <button class="btn btn-info btn-sm shadow-sm" data-toggle="modal" data-target="#exportModal">
                    Exporter
                </button>
            </div>
        </div>

        <?php if (isset($erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($erreur) ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Cartes de statistiques -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-left-primary shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <?php
// 1. Compter les produits créés ce mois-ci
$queryCrt = "SELECT COUNT(*) as total FROM produits WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
// Exécutez votre requête ici (dépend de votre connexion PDO ou MySQLi)
$nbMoisCrt = 4; // Exemple de résultat

// 2. Compter les produits créés le mois dernier
$queryPrec = "SELECT COUNT(*) as total FROM produits WHERE MONTH(created_at) = MONTH(STR_TO_DATE(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-%d'))";
$nbMoisPrec = 2; // Exemple de résultat

// 3. Calcul du pourcentage d'évolution
$evolution = 0;
if ($nbMoisPrec > 0) {
    $evolution = (($nbMoisCrt - $nbMoisPrec) / $nbMoisPrec) * 100;
} elseif ($nbMoisCrt > 0) {
    $evolution = 100; // Si 0 le mois dernier mais des produits ce mois-ci
}

// Définition de la couleur et de l'icône
$evolutionClass = $evolution >= 0 ? 'text-success' : 'text-danger';
$evolutionIcon = $evolution >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
?>
                            <div class="col mr-2">
    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
       &nbsp; &nbsp;&nbsp;Produits Référencés
    </div>
    
    <div class="h5 mb-0 font-weight-bold text-gray-800">
        <?= number_format($totalArticles, 0, ',', ' ') ?>
    </div>
    
    <div class="mt-2 mb-0 text-muted text-xs">
        <span class="<?= $evolutionClass ?> font-weight-bold mr-2">
            <i class="fas <?= $evolutionIcon ?>"></i> 
            <?= number_format(abs($evolution), 2) ?>%
        </span>
        <span>Depuis le mois dernier</span>
    </div>
</div>
                            <div class="col-auto">
                                <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-left-danger shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                  &nbsp;&nbsp;&nbsp;  Alertes Stock
                                </div>
                                <div class="h5 mb-0 font-weight-bold <?= $alerteStock > 0 ? 'text-danger' : 'text-gray-800' ?>">
                                    <?= number_format($alerteStock, 0) ?>
                                </div>
                                <div class="mt-2 mb-0 text-muted text-xs">
                                    &nbsp;&nbsp;&nbsp;&nbsp;Produits à réapprovisionner
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-left-success shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                 &nbsp; &nbsp;   Stock Optimal
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= number_format($totalArticles - $alerteStock, 0) ?>
                                </div>
                                <div class="mt-2 mb-0 text-muted text-xs">
                                  &nbsp;&nbsp; &nbsp;   Produits en bon niveau
                                </div>
                            </div>
                            <div class="col-auto">
                           
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-left-warning shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                   &nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp; Valeur Totale
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                &nbsp;&nbsp;   &nbsp; <?php 
                                    $totalValue = 0;
                                    foreach ($produits as $p) {
                                        $totalValue += $p['quantite_stock'] * $p['prix_unitaire'];
                                    }
                                    echo number_format($totalValue, 0) . ' FCFA';
                                    ?>
                                </div>
                                <div class="mt-2 mb-0 text-muted text-xs">
                                &nbsp; &nbsp; &nbsp;   Valeur estimée du stock
                                </div>
                            </div>
                            <div class="col-auto">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des stocks -->
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark font-weight-bold">
                   État des stocks
                    </h5>
                    
                    <div class="d-flex align-items-center">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" class="form-control border" placeholder="Rechercher un produit..." id="searchInput">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                  Rechercher
                                </button>
                            </div>
                        </div>
                        
                        <div class="dropdown ml-2">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                 Filtrer
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#" onclick="filterTable('all')">Tous les produits</a>
                                <a class="dropdown-item" href="#" onclick="filterTable('low')">Stock faible</a>
                                <a class="dropdown-item" href="#" onclick="filterTable('optimal')">Stock optimal</a>
                                <div class="dropdown-divider"></div>
                                <?php 
                                // Récupérer les catégories uniques
                                $categories = [];
                                foreach ($produits as $p) {
                                    $categories[$p['categorie']] = $p['categorie'];
                                }
                                foreach ($categories as $cat): 
                                ?>
                                <a class="dropdown-item" href="#" onclick="filterByCategory('<?= $cat ?>')">
                                    <?= htmlspecialchars($cat) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="stockTable">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0 pl-4" style="width: 30%;">Produit</th>
                                <th class="border-0" style="width: 15%;">Catégorie</th>
                                <th class="border-0" style="width: 20%;">Fournisseur</th>
                                <th class="border-0 text-center" style="width: 10%;">Quantité</th>
                                <th class="border-0" style="width: 15%;">Statut</th>
                                <th class="border-0 pr-4 text-right" style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produits)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Aucun produit trouvé</p>
                                            <a href="<?= generateUrl('pages/stock/ajouter.php') ?>" class="btn btn-primary btn-sm mt-3">
                                               Ajouter votre premier produit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($produits as $p): 
                                    $isLow = ($p['quantite_stock'] <= $p['seuil_alerte']);
                                    $stockPercent = min(100, ($p['quantite_stock'] / ($p['seuil_alerte'] * 3)) * 100);
                                ?>
                                    <tr class="product-row" data-category="<?= htmlspecialchars($p['categorie']) ?>" data-stock-status="<?= $isLow ? 'low' : 'optimal' ?>">
                                        <td class="pl-4">
                                            <div class="font-weight-bold text-dark">
                                                <?= htmlspecialchars($p['nom_produit']) ?>
                                            </div>
                                            <div class="d-flex align-items-center mt-1">
                                                <small class="text-muted mr-3">
                                                    <i class="fas fa-hashtag mr-1"></i>#<?= $p['id_produit'] ?>
                                                </small>
                                                <?php if ($p['emplacement']): ?>
                                                    <small class="text-info">
                                                        <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($p['emplacement']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <span class="badge badge-light-border px-3 py-1">
                                                <?= htmlspecialchars($p['categorie'] ?? 'Divers') ?>
                                            </span>
                                        </td>
                                        
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="mr-2">
                                                    
                                                </div>
                                                <div>
                                                    <div class="text-dark small font-weight-medium">
                                                        <?= htmlspecialchars($p['fournisseur_nom'] ?? 'Non spécifié') ?>
                                                    </div>
                                                    <?php if ($p['date_peremption']): ?>
                                                        <small class="text-warning">
                                                            <i class="far fa-calendar-alt mr-1"></i>
                                                            <?= date('d/m/Y', strtotime($p['date_peremption'])) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="text-center">
                                            <div class="font-weight-bold <?= $isLow ? 'text-danger' : 'text-dark' ?>">
                                                <?= number_format($p['quantite_stock'], 0) ?>
                                            </div>
                                            <div class="progress mt-1" style="height: 4px;">
                                                <div class="progress-bar <?= $isLow ? 'bg-danger' : 'bg-success' ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $stockPercent ?>%">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                Seuil: <?= $p['seuil_alerte'] ?>
                                            </small>
                                        </td>
                                        
                                        <td>
                                            <?php if ($isLow): ?>
                                                <span class="badge badge-danger px-3 py-1">
                                          > Critique
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-success px-3 py-1">
                                                    Optimal
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="pr-4 text-right">
                                            <div class="btn-group">
                                                <a href="<?= generateUrl('pages/stock/view_product.php?id='.$p['id_produit']) ?>" 
                                                   class="btn btn-sm btn-outline-info border" 
                                                   title="Détails">
                                                    Voir
                                                </a>
                                                <a href="<?= generateUrl('pages/stock/edit_product.php?id='.$p['id_produit']) ?>" 
                                                   class="btn btn-sm btn-outline-primary border ml-1" 
                                                   title="Modifier">
                                                    Modifier
                                                </a>
                                                <button onclick="deleteConfirm(<?= $p['id_produit'] ?>, '<?= htmlspecialchars(addslashes($p['nom_produit'])) ?>')" 
        class="btn btn-sm btn-outline-danger border ml-1" 
        title="Supprimer">
    Supprimer
</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($produits)): ?>
            <div class="card-footer bg-white py-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <?= count($produits) ?> produit(s) affiché(s)
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="printStock()">
                           Imprimer
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour la confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
               Confirmation de suppression
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le produit :</p>
                <p class="font-weight-bold text-danger" id="productNameToDelete"></p>
                <p class="text-muted small">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Supprimer</a>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction de confirmation de suppression améliorée
function deleteConfirm(productId, productName) {
    document.getElementById('productNameToDelete').textContent = productName;
    // Utilisation d'un chemin relatif si generateUrl pose problème
    document.getElementById('confirmDeleteBtn').href = "delete_product.php?id=" + productId; 
    $('#deleteModal').modal('show');
}

// Filtrage du tableau
function filterTable(status) {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        if (status === 'all' || row.getAttribute('data-stock-status') === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterByCategory(category) {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        if (row.getAttribute('data-category') === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Recherche en temps réel
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.product-row');
    
    rows.forEach(row => {
        const productName = row.querySelector('.font-weight-bold.text-dark').textContent.toLowerCase();
        const category = row.querySelector('.badge-light-border').textContent.toLowerCase();
        const supplier = row.querySelector('.text-dark.small').textContent.toLowerCase();
        
        if (productName.includes(searchTerm) || category.includes(searchTerm) || supplier.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Fonction d'impression
function printStock() {
    window.print();
}

// Animation de chargement
window.addEventListener('load', function() {
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = 1;
    });
});
</script>

<?php
require_once(__DIR__ . '/../../templates/footer.php');
ob_end_flush();
?>