<?php
// pages/stock/inventory.php

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// --- LOGIQUE DE SÉCURITÉ ET BDD ---
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../../index.php?error=Session expirée");
    exit();
}

require_once(__DIR__ . '/../../fonctions/database.php');

// --- RÉCUPÉRATION DES DONNÉES ---
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['categorie'] ?? '';

$query = "SELECT p.*, f.nom_fournisseur 
          FROM produits p 
          LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id_fournisseur 
          WHERE p.est_actif = TRUE";

$params = [];
if ($search) { $query .= " AND p.nom_produit LIKE ?"; $params[] = "%$search%"; }
if ($cat_filter) { $query .= " AND p.categorie = ?"; $params[] = $cat_filter; }

$query .= " ORDER BY p.quantite_stock ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$produits = $stmt->fetchAll();

$titre = "Gestion de Stock";
require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');
?>

<style>
    :root {
        --primary-color: #2c3e50;
        --accent-color: #3498db;
        --bg-light: #f8f9fa;
    }

    .inventory-container {
        margin-left: 250px;
        padding: 30px;
        background-color: #f4f7f6;
        min-height: 100vh;
    }

    /* Style des Onglets Professionnels */
    .nav-tabs-custom {
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 20px;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 12px 25px;
        transition: all 0.3s;
    }
    .nav-tabs-custom .nav-link.active {
        color: var(--accent-color);
        border-bottom: 3px solid var(--accent-color);
        background: transparent;
    }

    /* Table & Cards */
    .main-card {
        background: white;
        border: none;
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .table thead th {
        background-color: white;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        color: #95a5a6;
        border-top: none;
        padding: 15px;
    }

    .status-dot {
        height: 10px;
        width: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .btn-action {
        width: 35px;
        height: 35px;
        padding: 0;
        line-height: 35px;
        border-radius: 6px;
        transition: transform 0.2s;
    }
    .btn-action:hover { transform: translateY(-2px); }

    @media (max-width: 992px) { .inventory-container { margin-left: 0; } }
</style>
<br> <br> <br>
<div class="inventory-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Gestion du Stock</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventaire</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="add_product.php" class="btn btn-primary px-4 shadow-sm">
                Nouveau Produit
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs nav-tabs-custom">
        <li class="nav-item">
            <a class="nav-link active" href="inventory.php">Inventaire Général</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="stock_entry.php">Entrées Stock</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="stock_out.php">Sorties Stock</a>
        </li>
    </ul>

    <div class="main-card p-3 mb-4">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-merge">
                    <span class="input-group-text bg-light border-end-0"></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Rechercher une référence..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="categorie" class="form-select bg-light">
                    <option value="">Toutes les catégories</option>
                    <?php
                    $categories = ['lessive', 'detachant', 'cintre', 'sac', 'etiquette', 'autre'];
                    foreach($categories as $cat) {
                        $selected = ($cat_filter == $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>".ucfirst($cat)."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">Filtrer</button>
                <?php if($search || $cat_filter): ?>
                    <a href="inventory.php" class="btn btn-outline-secondary"></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="main-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Désignation</th>
                        <th>Catégorie</th>
                        <th>Stock Actuel</th>
                        <th>Prix Unitaire</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $p): 
                        $stock = (float)$p['quantite_stock'];
                        $seuil = (float)$p['seuil_alerte'];
                        
                        // Calcul du statut
                        if ($stock <= ($seuil * 0.5)) {
                            $status = '<span class="status-dot bg-danger"></span> Critique';
                            $textClass = 'text-danger fw-bold';
                        } elseif ($stock <= $seuil) {
                            $status = '<span class="status-dot bg-warning"></span> Faible';
                            $textClass = 'text-warning fw-bold';
                        } else {
                            $status = '<span class="status-dot bg-success"></span> Optimal';
                            $textClass = 'text-success';
                        }
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['nom_produit']) ?></div>
                            <small class="text-muted">Fournisseur : <?= htmlspecialchars($p['nom_fournisseur'] ?? 'N/A') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border fw-normal"><?= ucfirst($p['categorie']) ?></span>
                        </td>
                        <td>
                            <span class="<?= $textClass ?>"><?= number_format($stock, 0) ?></span> 
                            <small class="text-muted"><?= $p['unite_mesure'] ?></small>
                        </td>
                        <td><?= number_format($p['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                        <td><?= $status ?></td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="view_product.php?id=<?= $p['id_produit'] ?>" class="btn btn-outline-secondary btn-action" title="Voir">
                                    Voir le produit
                                </a>
                                <a href="edit_product.php?id=<?= $p['id_produit'] ?>" class="btn btn-outline-primary btn-action" title="Modifier">
                                 &nbsp;   &nbsp; &nbsp; &nbsp; &nbsp;   &nbsp; &nbsp; &nbsp; &nbsp;   Modifier 
                                </a>
                            &nbsp;   &nbsp; &nbsp; &nbsp; &nbsp;   &nbsp; &nbsp; &nbsp; &nbsp;   &nbsp; &nbsp; &nbsp;  &nbsp;   &nbsp;      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patron'): ?>
    <button type="button" 
            onclick="confirmDelete(<?= $p['id_produit'] ?>, '<?= addslashes(htmlspecialchars($p['nom_produit'])) ?>')" 
            class="btn btn-outline-danger btn-sm" 
            title="Supprimer définitivement">
        <i class="fas fa-trash-alt me-1"></i> Supprimer
    </button>
<?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                <h5 class="mb-2">Confirmation</h5>
                <p class="text-muted small">Voulez-vous vraiment archiver <br><strong id="productName"></strong> ?</p>
                <form method="POST">
                    <input type="hidden" name="delete_id" id="deleteInputForm">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">Confirmer l'archivage</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('productName').textContent = name;
    document.getElementById('deleteInputForm').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once(__DIR__ . '/../../templates/footer.php'); ?>