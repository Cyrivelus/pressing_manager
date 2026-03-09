<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Boutique & Produits";

try {
    $categories = $pdo->query("SELECT DISTINCT categorie FROM boutique_produits WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

    $recherche = trim($_GET['recherche'] ?? '');
    $cat_filter = $_GET['cat'] ?? '';

    $sql = "SELECT * FROM boutique_produits WHERE 1=1";
    $params = [];

    if (!empty($recherche)) {
        $sql .= " AND (nom LIKE :search OR description LIKE :search)";
        $params['search'] = "%$recherche%";
    }
    if (!empty($cat_filter)) {
        $sql .= " AND categorie = :cat";
        $params['cat'] = $cat_filter;
    }

    $sql .= " ORDER BY (stock > 0) DESC, nom ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produits = $stmt->fetchAll();

} catch (PDOException $e) {
    $db_error = "Erreur de base de données : " . $e->getMessage();
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    :root { --primary: #4e73df; --success: #1cc88a; --bg-light: #f8f9fc; }
    body { background-color: var(--bg-light); }
    .product-card { border-radius: 15px; overflow: hidden; border: 1px solid transparent; transition: 0.25s; }
    .product-card:hover { border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .product-img-wrapper { height: 160px; background: #eee; position: relative; }
    .product-img { width: 100%; height: 100%; object-fit: cover; }
    .badge-stock { position: absolute; top: 10px; right: 10px; z-index: 2; }
    .category-scroll { display: flex; gap: 8px; overflow-x: auto; padding: 10px 0; scrollbar-width: none; }
    .cart-sticky { position: sticky; top: 20px; border-radius: 20px; border: none; }
    .svg-icon { width: 20px; height: 20px; fill: currentColor; vertical-align: middle; }
    .btn-qty { width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 6px; }
</style>

<svg style="display:none">
    <symbol id="icon-plus" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></symbol>
    <symbol id="icon-minus" viewBox="0 0 24 24"><path d="M19 13H5v-2h14v2z"/></symbol>
    <symbol id="icon-trash" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></symbol>
    <symbol id="icon-cart" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></symbol>
    <symbol id="icon-search" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></symbol>
</svg>
<br><br><br>
<div class="container-fluid py-4">
    <?php if (isset($db_error)): ?>
        <div class="alert alert-danger rounded-3 border-0 shadow-sm"><?= htmlspecialchars($db_error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <h1 class="h3 fw-bold mb-0 text-dark"><?= $titre ?></h1>
                <form method="GET" class="d-flex shadow-sm rounded-3 overflow-hidden">
                    <input type="text" name="recherche" class="form-control border-0 px-3" placeholder="Chercher un article..." value="<?= htmlspecialchars($recherche) ?>">
                    <button class="btn btn-white border-0 bg-white px-3" type="submit">
                        <svg class="svg-icon text-muted"><use xlink:href="#icon-search"></use></svg>
                    </button>
                </form>
            </div>

            <div class="category-scroll mb-4">
                <a href="index.php" class="btn btn-sm rounded-pill px-4 <?= !$cat_filter ? 'btn-primary' : 'btn-white shadow-sm' ?>">Tous</a>
                <?php foreach($categories as $cat): ?>
                    <a href="index.php?cat=<?= urlencode($cat) ?>" class="btn btn-sm rounded-pill px-4 shadow-sm <?= $cat_filter == $cat ? 'btn-primary' : 'btn-white' ?>">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach($produits as $p): ?>
                <div class="col">
                    <div class="card h-100 product-card shadow-sm border-0">
                        <div class="product-img-wrapper">
                            <?php if($p['stock'] <= 0): ?>
                                <span class="badge bg-dark badge-stock">Rupture</span>
                            <?php elseif($p['stock'] <= 5): ?>
                                <span class="badge bg-danger badge-stock"><?= $p['stock'] ?> restants</span>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($p['photo'] ?: 'https://placehold.co/400x300/e2e8f0/64748b?text=Produit') ?>" class="product-img" alt="produit">
                        </div>
                        <div class="card-body">
                            <small class="text-primary fw-semibold"><?= htmlspecialchars($p['categorie'] ?: 'Général') ?></small>
                            <h6 class="fw-bold text-dark mt-1"><?= htmlspecialchars($p['nom']) ?></h6>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="h5 fw-bold mb-0"><?= number_format($p['prix'], 0, '.', ' ') ?> <small>F</small></span>
                                <button 
                                    class="btn btn-primary rounded-pill btn-add-cart"
                                    data-id="<?= $p['id'] ?>"
                                    data-nom="<?= htmlspecialchars($p['nom']) ?>"
                                    data-prix="<?= $p['prix'] ?>"
                                    data-stock="<?= $p['stock'] ?>"
                                    <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <svg class="svg-icon"><use xlink:href="#icon-plus"></use></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card cart-sticky shadow-sm">
                <div class="card-header bg-white p-4 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Panier</h5>
                        <svg class="svg-icon text-primary"><use xlink:href="#icon-cart"></use></svg>
                    </div>
                </div>
                <div class="card-body px-4 pt-0">
                    <div id="cartContent" class="mb-4" style="min-height: 150px;">
                        <div class="text-center py-5 text-muted small">Aucun article sélectionné</div>
                    </div>
                    
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between h4 fw-bold mb-4">
                            <span>TOTAL</span>
                            <span id="cartTotal" class="text-success">0 F</span>
                        </div>
                        <button class="btn btn-success btn-lg w-100 rounded-3 shadow-sm fw-bold mb-2" id="btnCheckout" disabled>
                            VALIDER LA VENTE
                        </button>
                        <button class="btn btn-sm btn-light w-100 text-muted" id="btnClear">Vider le panier</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cart = [];
    const content = document.getElementById('cartContent');
    const totalEl = document.getElementById('cartTotal');
    const btnCheckout = document.getElementById('btnCheckout');

    // Gestion de l'ajout (Événement délégué pour performance)
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-add-cart');
        if (!btn) return;

        const prod = {
            id: parseInt(btn.dataset.id),
            nom: btn.dataset.nom,
            prix: parseFloat(btn.dataset.prix),
            stock: parseInt(btn.dataset.stock)
        };

        const found = cart.find(i => i.id === prod.id);
        if (found) {
            if (found.qty < prod.stock) found.qty++;
            else alert("Stock insuffisant");
        } else {
            cart.push({ ...prod, qty: 1 });
        }
        renderCart();
    });

    function renderCart() {
        if (cart.length === 0) {
            content.innerHTML = '<div class="text-center py-5 text-muted small">Panier vide</div>';
            totalEl.innerText = '0 F';
            btnCheckout.disabled = true;
            return;
        }

        content.innerHTML = cart.map(item => `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div style="max-width: 60%">
                    <div class="fw-bold text-truncate small">${item.nom}</div>
                    <small class="text-muted">${item.prix.toLocaleString()} F</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-light border btn-qty" onclick="window.updateQty(${item.id}, -1)">
                        <svg class="svg-icon" style="width:14px; height:14px"><use xlink:href="${item.qty > 1 ? '#icon-minus' : '#icon-trash'}"></use></svg>
                    </button>
                    <span class="fw-bold small">${item.qty}</span>
                    <button class="btn btn-light border btn-qty" onclick="window.updateQty(${item.id}, 1)">
                        <svg class="svg-icon" style="width:14px; height:14px"><use xlink:href="#icon-plus"></use></svg>
                    </button>
                </div>
            </div>
        `).join('');

        const total = cart.reduce((acc, i) => acc + (i.prix * i.qty), 0);
        totalEl.innerText = total.toLocaleString() + ' F';
        btnCheckout.disabled = false;
    }

    window.updateQty = (id, delta) => {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        
        if (delta > 0 && item.qty >= item.stock) return alert("Limite de stock atteinte");
        
        item.qty += delta;
        if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
        renderCart();
    };

    document.getElementById('btnClear').onclick = () => { if(confirm("Vider ?")) { cart = []; renderCart(); } };

    btnCheckout.onclick = async () => {
        btnCheckout.disabled = true;
        btnCheckout.innerText = "Traitement...";
        
        // Simulation d'envoi API
        console.log("Validation commande:", cart);
        
        setTimeout(() => {
            alert("Vente validée avec succès");
            cart = [];
            renderCart();
            btnCheckout.innerText = "VALIDER LA VENTE";
        }, 1000);
    };
});
</script>

<?php require_once '../../templates/footer.php'; ?>