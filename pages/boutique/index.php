<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Boutique & Produits";

try {
    // 2. Récupération des catégories
    $categories = $pdo->query("SELECT DISTINCT categorie FROM boutique_produits WHERE categorie IS NOT NULL ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

    // 3. Récupération des produits (Recherche + Filtre catégorie)
    $recherche = $_GET['recherche'] ?? '';
    $cat_filter = $_GET['cat'] ?? '';

    $sql = "SELECT * FROM boutique_produits WHERE 1=1";
    $params = [];

    if ($recherche) {
        $sql .= " AND (nom LIKE :search OR description LIKE :search)";
        $params['search'] = "%$recherche%";
    }

    if ($cat_filter) {
        $sql .= " AND categorie = :cat";
        $params['cat'] = $cat_filter;
    }

    $sql .= " ORDER BY stock > 0 DESC, nom ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produits = $stmt->fetchAll();

} catch (PDOException $e) {
    // Si la table n'existe toujours pas ou erreur
    $produits = [];
    $categories = [];
    $db_error = "Erreur de base de données : " . $e->getMessage();
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .product-card { border: none; transition: 0.3s; border-radius: 12px; overflow: hidden; background: #fff; height: 100%; border: 1px solid #eee; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .product-img { height: 160px; object-fit: cover; background: #f8f9fa; width: 100%; }
    .badge-stock { position: absolute; top: 10px; right: 10px; z-index: 10; }
    .category-pill { cursor: pointer; transition: 0.2s; white-space: nowrap; text-decoration: none; }
    .cart-summary { position: sticky; top: 20px; border-radius: 15px; }
</style>

<div class="container-fluid py-5">
    <?php if (isset($db_error)): ?>
        <div class="alert alert-danger"><?= $db_error ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"><i class="fas fa-store text-primary me-2"></i><?= $titre ?></h2>
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <form action="" method="GET" class="d-flex gap-2">
                        <input type="text" name="recherche" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($recherche) ?>">
                        <button type="submit" class="btn btn-light border"><i class="fas fa-search"></i></button>
                    </form>
                    <button class="btn btn-primary"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <div class="d-flex gap-2 mb-4 overflow-auto pb-2">
                <a href="index.php" class="badge rounded-pill <?= !$cat_filter ? 'bg-primary' : 'bg-light text-dark border' ?> px-3 py-2 category-pill">Tous</a>
                <?php foreach($categories as $cat): ?>
                    <a href="index.php?cat=<?= urlencode($cat) ?>" class="badge rounded-pill <?= $cat_filter == $cat ? 'bg-primary' : 'bg-light text-dark border' ?> px-3 py-2 category-pill">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="row g-4">
                <?php if(empty($produits)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun produit trouvé.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($produits as $p): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card product-card shadow-sm">
                            <?php if($p['stock'] <= 0): ?>
                                <span class="badge bg-secondary badge-stock">Rupture</span>
                            <?php elseif($p['stock'] <= 5): ?>
                                <span class="badge bg-danger badge-stock">Stock bas: <?= $p['stock'] ?></span>
                            <?php endif; ?>
                            
                            <img src="<?= $p['photo'] ?? '../../assets/img/produit-default.png' ?>" class="card-img-top product-img" 
                                 onerror="this.src='https://placehold.co/400x300?text=Produit'">
                            
                            <div class="card-body">
                                <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($p['nom']) ?></h6>
                                <p class="text-muted small mb-2 text-truncate"><?= htmlspecialchars($p['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fw-bold text-primary"><?= number_format($p['prix'], 0, ',', ' ') ?> <small>FCFA</small></span>
                                    <button onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['nom']) ?>', <?= $p['prix'] ?>)" 
                                            class="btn btn-sm btn-primary <?= $p['stock'] <= 0 ? 'disabled' : '' ?>">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card cart-summary shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-shopping-basket me-2"></i>Vente en direct</h5>
                </div>
                <div class="card-body">
                    <div id="cartItems" class="mb-4" style="min-height: 100px; max-height: 400px; overflow-y: auto;">
                        <p class="text-center text-muted py-4" id="emptyCartMsg">Panier vide</p>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="fw-bold">TOTAL</h4>
                        <h4 class="fw-bold text-success" id="totalAmount">0 FCFA</h4>
                    </div>
                    <button class="btn btn-success w-100 py-3 fw-bold shadow" id="btnPayer" disabled onclick="validerVente()">
                        <i class="fas fa-cash-register me-2"></i> ENCAISSER
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

function addToCart(id, name, price) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1 });
    }
    updateCartUI();
}

function updateCartUI() {
    const container = document.getElementById('cartItems');
    const emptyMsg = document.getElementById('emptyCartMsg');
    
    if (cart.length === 0) {
        emptyMsg.style.display = 'block';
        container.innerHTML = '';
        document.getElementById('btnPayer').disabled = true;
    } else {
        emptyMsg.style.display = 'none';
        container.innerHTML = cart.map(item => `
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <div style="flex: 1;">
                    <div class="small fw-bold">${item.name}</div>
                    <div class="text-muted small">${item.price.toLocaleString()} x ${item.qty}</div>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" onclick="changeQty(${item.id}, -1)">-</button>
                    <button class="btn btn-outline-secondary" onclick="changeQty(${item.id}, 1)">+</button>
                </div>
            </div>
        `).join('');
        document.getElementById('btnPayer').disabled = false;
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    document.getElementById('totalAmount').innerText = total.toLocaleString() + ' FCFA';
}

function changeQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (item) {
        item.qty += delta;
        if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
        updateCartUI();
    }
}

function validerVente() {
    alert("Vente enregistrée avec succès ! (Fonctionnalité de sauvegarde à lier à votre API)");
    cart = [];
    updateCartUI();
}
</script>

<?php require_once  '../../templates/footer.php'; ?>