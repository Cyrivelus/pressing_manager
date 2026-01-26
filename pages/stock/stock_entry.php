<?php
// pages/stock/stock_entry.php

// 1. Démarrer la session si nécessaire
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Inclure la base de données
require_once(__DIR__ . '/../../fonctions/database.php');

// 3. Initialiser les variables
$error = '';
$success = '';

// 4. Récupérer tous les produits actifs AVANT tout HTML
$stmtP = $pdo->query("SELECT id_produit, nom_produit, quantite_stock, unite_mesure FROM produits WHERE est_actif = TRUE ORDER BY nom_produit");
$produits = $stmtP->fetchAll();

// 5. Traitement du formulaire AVANT tout HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Valider les entrées
        $id_produit = filter_input(INPUT_POST, 'id_produit', FILTER_VALIDATE_INT);
        $quantite_ajoutee = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_FLOAT);
        
        if (!$id_produit || $id_produit <= 0) {
            throw new Exception("Produit invalide.");
        }
        
        if (!$quantite_ajoutee || $quantite_ajoutee <= 0) {
            throw new Exception("Quantité invalide. Doit être supérieure à 0.");
        }
        
        // Récupérer l'utilisateur
        $id_user = $_SESSION['utilisateur_id'] ?? 1; // Valeur par défaut si non connecté
        
        // Démarrer la transaction
        $pdo->beginTransaction();

        // 1. Récupérer le stock actuel pour le calcul "après"
        $stmtCurr = $pdo->prepare("SELECT quantite_stock FROM produits WHERE id_produit = ?");
        $stmtCurr->execute([$id_produit]);
        $old_stock = $stmtCurr->fetchColumn();
        
        if ($old_stock === false) {
            throw new Exception("Produit non trouvé.");
        }
        
        $new_stock = $old_stock + $quantite_ajoutee;

        // 2. Mettre à jour la table produits
        $upd = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE id_produit = ?");
        $upd->execute([$new_stock, $id_produit]);

        // 3. Enregistrer le mouvement
        $reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING) ?: '';
        $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?: '';
        
        $mov = $pdo->prepare("INSERT INTO mouvements_stock 
                              (id_produit, type_mouvement, quantite, date_mouvement, id_utilisateur, reference, notes, quantite_apres) 
                              VALUES (?, 'entree', ?, NOW(), ?, ?, ?, ?)");
        $mov->execute([
            $id_produit,
            $quantite_ajoutee,
            $id_user,
            $reference,
            $notes,
            $new_stock
        ]);

        $pdo->commit();
        
        // Redirection après succès
        header("Location: inventory.php?success=restocked&id=" . $id_produit);
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Erreur : " . $e->getMessage();
    }
}

// 6. MAINTENANT on peut inclure les templates
require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');
?>

</br></br></br></br>
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                Entrée de Stock / Réapprovisionnement
                            </h5>
                            <small class="opacity-75">Ajouter du stock pour un produit existant</small>
                       </div>
    <a href="javascript:history.back()" class="btn btn-sm btn-light">
        <- Retour
    </a>
</div>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                          
                            <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                           
                            <strong>Succès !</strong> L'entrée de stock a été enregistrée.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="stockEntryForm">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold required">
                                    Sélectionner le produit
                                </label>
                                <select name="id_produit" class="form-select" required id="productSelect">
                                    <option value="">-- Rechercher un produit --</option>
                                    <?php foreach($produits as $p): ?>
                                        <?php 
                                        $stock_class = $p['quantite_stock'] <= 5 ? 'text-danger' : 
                                                      ($p['quantite_stock'] <= 10 ? 'text-warning' : 'text-success');
                                        ?>
                                        <option value="<?= $p['id_produit'] ?>" 
                                                data-stock="<?= $p['quantite_stock'] ?>"
                                                data-unit="<?= htmlspecialchars($p['unite_mesure']) ?>">
                                            <?= htmlspecialchars($p['nom_produit']) ?> 
                                            <span class="<?= $stock_class ?>">
                                                (Stock: <?= (float)$p['quantite_stock'] ?> <?= htmlspecialchars($p['unite_mesure']) ?>)
                                            </span>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    Le produit sélectionné sera réapprovisionné.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">
                                   Quantité reçue
                                </label>
                                <div class="input-group">
                                    <input type="number" name="quantite" class="form-control" 
                                           step="0.01" required min="0.01" id="quantityInput"
                                           placeholder="0.00">
                                    <span class="input-group-text" id="unitDisplay">unité</span>
                                </div>
                                <div class="form-text">
                                    Quantité à ajouter au stock actuel.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Stock après ajout
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light" readonly 
                                           id="stockAfterInput" value="0.00">
                                    <span class="input-group-text bg-light" id="afterUnitDisplay">unité</span>
                                </div>
                                <div class="small mt-1">
                                    <span class="text-success" id="increaseText">+0.00</span>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                   Référence
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        
                                    </span>
                                    <input type="text" name="reference" class="form-control" 
                                           placeholder="Ex: BL-2024-001, Facture #1234">
                                </div>
                                <div class="form-text">
                                    N° de bon de livraison, facture ou commande.
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                    Notes / Commentaires
                                </label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Ex: Livraison fournisseur XYZ, qualité vérifiée..."></textarea>
                                <div class="form-text">
                                    Informations supplémentaires sur cet approvisionnement.
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center">
                                     
                                        <div>
                                            <strong>Vérifiez :</strong>
                                            <ul class="mb-0 ps-3">
                                                <li>Produit correct</li>
                                                <li>Quantité exacte</li>
                                                <li>Référence fournie</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg py-3">
                                        Valider l'entrée en stock
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="window.location.href='inventory.php'">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Statistiques rapides -->
            <div class="card mt-4 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-bold text-success mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Statistiques des stocks bas
                    </h6>
                    <?php
                    $lowStock = array_filter($produits, function($p) {
                        return $p['quantite_stock'] <= 10;
                    });
                    ?>
                    <?php if(count($lowStock) > 0): ?>
                        <div class="alert alert-warning">
                            <strong>Attention :</strong>
                            <?= count($lowStock) ?> produit(s) ont un stock ≤ 10 unités.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Stock actuel</th>
                                        <th>Unité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($lowStock as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['nom_produit']) ?></td>
                                            <td class="<?= $p['quantite_stock'] <= 5 ? 'text-danger fw-bold' : 'text-warning' ?>">
                                                <?= (float)$p['quantite_stock'] ?>
                                            </td>
                                            <td><?= htmlspecialchars($p['unite_mesure']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            
                            Tous les produits ont un stock suffisant (> 10 unités).
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('productSelect');
    const quantityInput = document.getElementById('quantityInput');
    const unitDisplay = document.getElementById('unitDisplay');
    const afterUnitDisplay = document.getElementById('afterUnitDisplay');
    const stockAfterInput = document.getElementById('stockAfterInput');
    const increaseText = document.getElementById('increaseText');
    
    // Mettre à jour les unités et calculs
    function updateCalculations() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const currentStock = parseFloat(selectedOption.getAttribute('data-stock') || 0);
        const unit = selectedOption.getAttribute('data-unit') || 'unité';
        const quantity = parseFloat(quantityInput.value) || 0;
        
        // Mettre à jour les unités
        unitDisplay.textContent = unit;
        afterUnitDisplay.textContent = unit;
        
        // Calculer le nouveau stock
        const newStock = currentStock + quantity;
        stockAfterInput.value = newStock.toFixed(2);
        
        // Mettre à jour l'augmentation
        increaseText.textContent = `+${quantity.toFixed(2)} ${unit}`;
        increaseText.className = quantity > 0 ? 'text-success fw-bold' : 'text-muted';
    }
    
    // Écouter les changements
    productSelect.addEventListener('change', updateCalculations);
    quantityInput.addEventListener('input', updateCalculations);
    
    // Validation du formulaire
    document.getElementById('stockEntryForm').addEventListener('submit', function(e) {
        if (!productSelect.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un produit.');
            productSelect.focus();
            return false;
        }
        
        const quantity = parseFloat(quantityInput.value);
        if (!quantity || quantity <= 0) {
            e.preventDefault();
            alert('Veuillez entrer une quantité valide (supérieure à 0).');
            quantityInput.focus();
            return false;
        }
        
        // Confirmation
        const productName = productSelect.options[productSelect.selectedIndex].text;
        const confirmMessage = `Confirmer l'ajout de ${quantity} ${unitDisplay.textContent} pour :\n${productName}`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Focus sur le champ quantité
    quantityInput.focus();
    
    // Initialiser les calculs
    updateCalculations();
});
</script>

<style>
.card {
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
}

.form-control:focus, .form-select:focus {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.required::after {
    content: " *";
    color: #dc3545;
}

.btn-success {
    background: linear-gradient(135deg, #198754, #157347);
    border: none;
    transition: all 0.3s;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(25, 135, 84, 0.4);
}

.table-sm th, .table-sm td {
    padding: 0.5rem;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeIn 0.5s ease-out;
}
</style>

<?php 
require_once(__DIR__ . '/../../templates/footer.php');
?>