<?php
// pages/stock/stock_entry.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../fonctions/database.php');

$error = '';
$success = '';

// Récupération des produits
$stmtP = $pdo->query("SELECT id_produit, nom_produit, quantite_stock, unite_mesure FROM produits WHERE est_actif = TRUE ORDER BY nom_produit");
$produits = $stmtP->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_produit = filter_input(INPUT_POST, 'id_produit', FILTER_VALIDATE_INT);
        $quantite_ajoutee = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_FLOAT);
        
        if (!$id_produit || $id_produit <= 0) throw new Exception("Produit invalide.");
        if (!$quantite_ajoutee || $quantite_ajoutee <= 0) throw new Exception("Quantité invalide.");
        
        $id_user = $_SESSION['utilisateur_id'] ?? 1;
        
        $pdo->beginTransaction();

        $stmtCurr = $pdo->prepare("SELECT quantite_stock FROM produits WHERE id_produit = ?");
        $stmtCurr->execute([$id_produit]);
        $old_stock = $stmtCurr->fetchColumn();
        
        if ($old_stock === false) throw new Exception("Produit non trouvé.");
        
        $new_stock = $old_stock + $quantite_ajoutee;

        $upd = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE id_produit = ?");
        $upd->execute([$new_stock, $id_produit]);

        $reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING) ?: '';
        $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?: '';
        
        $mov = $pdo->prepare("INSERT INTO mouvements_stock 
                              (id_produit, type_mouvement, quantite, date_mouvement, id_utilisateur, reference, notes, quantite_apres) 
                              VALUES (?, 'entree', ?, NOW(), ?, ?, ?, ?)");
        $mov->execute([$id_produit, $quantite_ajoutee, $id_user, $reference, $notes, $new_stock]);

        $pdo->commit();
        header("Location: inventory.php?success=restocked&id=" . $id_produit);
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($TITRE_PAGE) ?> | Stock</title>
    <link rel="stylesheet" href="../../css/select2.min.css">
    <link rel="stylesheet" href="../../css/select2-bootstrap.min.css">
    <style>
        :root {
            --primary-action: #198754;
            --neutral-dark: #343a40;
            --border-soft: #dee2e6;
        }

        body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        
        .card { border: 1px solid var(--border-soft); border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        .card-header { 
            background-color: #fff; 
            border-bottom: 2px solid var(--primary-action); 
            color: var(--neutral-dark);
            padding: 1.5rem;
        }

        .btn-success { 
            background-color: var(--primary-action); 
            border: none; 
            border-radius: 2px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-light { background: #fff; border: 1px solid var(--border-soft); color: var(--neutral-dark); }

        .form-label { font-size: 0.85rem; text-transform: uppercase; color: #6c757d; letter-spacing: 0.3px; }
        
        .form-control, .select2-container--bootstrap .select2-selection {
            border-radius: 2px !important;
            border: 1px solid var(--border-soft) !important;
        }

        .form-control:focus {
            border-color: var(--primary-action) !important;
            box-shadow: none !important;
        }

        .bg-readonly { background-color: #f8f9fa !important; border-style: dashed !important; }

        .required::after { content: " *"; color: #dc3545; }

        .stats-title { border-left: 4px solid var(--primary-action); padding-left: 10px; margin-bottom: 20px; }
        
        /* Nettoyage select2 custom */
        .select2-results__option--highlighted { background-color: var(--primary-action) !important; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">RÉAPPROVISIONNEMENT STOCK</h5>
                        <small class="text-muted">Enregistrement d'une nouvelle entrée de marchandise</small>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-sm btn-light">
                        &larr; Retour
                    </a>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <?php if($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="stockEntryForm">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-bold required">Référence Produit</label>
                                <select name="id_produit" class="form-control select2-product" id="productSelect" required>
                                    <option value="">-- Rechercher dans l'inventaire --</option>
                                    <?php foreach($produits as $p): ?>
                                        <option value="<?= $p['id_produit'] ?>" 
                                                data-stock="<?= (float)$p['quantite_stock'] ?>"
                                                data-unit="<?= htmlspecialchars($p['unite_mesure']) ?>">
                                            <?= htmlspecialchars($p['nom_produit']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">Quantité à ajouter</label>
                                <div class="input-group">
                                    <input type="number" name="quantite" class="form-control" step="0.01" required min="0.01" id="quantityInput" placeholder="0.00">
                                    <span class="input-group-text bg-white" id="unitDisplay">--</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nouveau stock théorique</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-readonly" readonly id="stockAfterInput" value="0.00">
                                    <span class="input-group-text bg-readonly" id="afterUnitDisplay">--</span>
                                </div>
                                <div class="small mt-1 text-end">
                                    Variation : <span id="increaseText" class="fw-bold">0.00</span>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold">N° de Pièce / Référence</label>
                                <input type="text" name="reference" class="form-control" placeholder="Ex: BC-2024-001 ou Facture Fournisseur">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold">Observations</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Détails supplémentaires sur la livraison..."></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-5 pt-3 border-top">
                            <div class="row align-items-center">
                                <div class="col-md-7 mb-3 mb-md-0">
                                    <p class="small text-muted mb-0">
                                        L'action mettra à jour le stock en temps réel et créera une entrée dans le journal des mouvements.
                                    </p>
                                </div>
                                <div class="col-md-5 d-grid">
                                    <button type="submit" class="btn btn-success py-3">
                                        Enregistrer l'entrée
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card bg-white border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="stats-title fw-bold text-dark">RAPPEL : STOCKS CRITIQUES</h6>
                    <?php
                    $lowStock = array_filter($produits, function($p) { return $p['quantite_stock'] <= 10; });
                    ?>
                    <?php if(count($lowStock) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Désignation</th>
                                        <th class="text-center">Stock</th>
                                        <th>Unité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($lowStock as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['nom_produit']) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $p['quantite_stock'] <= 5 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                                    <?= (float)$p['quantite_stock'] ?>
                                                </span>
                                            </td>
                                            <td class="text-muted"><?= htmlspecialchars($p['unite_mesure']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-success mb-0 small">Tous les niveaux de stock sont actuellement au-dessus du seuil d'alerte.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script src="../../js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('productSelect');
    const quantityInput = document.getElementById('quantityInput');
    const unitDisplay = document.getElementById('unitDisplay');
    const afterUnitDisplay = document.getElementById('afterUnitDisplay');
    const stockAfterInput = document.getElementById('stockAfterInput');
    const increaseText = document.getElementById('increaseText');
    
    function updateCalculations() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if(!selectedOption.value) return;

        const currentStock = parseFloat(selectedOption.getAttribute('data-stock') || 0);
        const unit = selectedOption.getAttribute('data-unit') || 'unité';
        const quantity = parseFloat(quantityInput.value) || 0;
        
        unitDisplay.textContent = unit;
        afterUnitDisplay.textContent = unit;
        
        const newStock = currentStock + quantity;
        stockAfterInput.value = newStock.toLocaleString('fr-FR', {minimumFractionDigits: 2});
        
        increaseText.textContent = `+${quantity.toFixed(2)} ${unit}`;
        increaseText.style.color = quantity > 0 ? '#198754' : '#6c757d';
    }
    
    productSelect.addEventListener('change', updateCalculations);
    quantityInput.addEventListener('input', updateCalculations);
    
    document.getElementById('stockEntryForm').addEventListener('submit', function(e) {
        if (!productSelect.value || (parseFloat(quantityInput.value) || 0) <= 0) {
            e.preventDefault();
            alert('Données incomplètes ou invalides.');
            return;
        }
        if (!confirm("Voulez-vous valider cet ajout de stock ?")) e.preventDefault();
    });

    // Select2 Integration
    $('#productSelect').select2({
        theme: "bootstrap",
        placeholder: "-- Rechercher un produit --",
        allowClear: true,
        width: '100%'
    }).on('change', updateCalculations);
});
</script>

<?php require_once(__DIR__ . '/../../templates/footer.php'); ?>
</body>
</html>