<?php
// pages/stock/stock_out.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../fonctions/database.php');

$message = "";

// 1. Récupérer la liste des produits actifs
$produits = $pdo->query("SELECT id_produit, nom_produit, quantite_stock, unite_mesure FROM produits WHERE est_actif = 1 ORDER BY nom_produit")->fetchAll();

// 2. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produit = $_POST['id_produit'];
    $quantite_a_retirer = floatval($_POST['quantite']);
    $notes = $_POST['notes'];
    $id_utilisateur = $_SESSION['user_id'] ?? 1;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT quantite_stock FROM produits WHERE id_produit = ? FOR UPDATE");
        $stmt->execute([$id_produit]);
        $produit = $stmt->fetch();

        if ($produit && $produit['quantite_stock'] >= $quantite_a_retirer) {
            $nouveau_stock = $produit['quantite_stock'] - $quantite_a_retirer;

            $update = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE id_produit = ?");
            $update->execute([$nouveau_stock, $id_produit]);

            $log = $pdo->prepare("INSERT INTO mouvements_stock (id_produit, type_mouvement, quantite, date_mouvement, id_utilisateur, notes, quantite_apres) VALUES (?, 'sortie', ?, NOW(), ?, ?, ?)");
            $log->execute([$id_produit, $quantite_a_retirer, $id_utilisateur, $notes, $nouveau_stock]);

            $pdo->commit();
            header("Location: inventory.php?success=stock_out");
            exit();
        } else {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger border-0 rounded-0'>Attention : Stock insuffisant (Disponible : " . ($produit['quantite_stock'] ?? 0) . ")</div>";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='alert alert-danger border-0 rounded-0'>Erreur système : " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($TITRE_PAGE) ?> | Sortie Stock</title>
    <link rel="stylesheet" href="../../css/select2.min.css">
    <link rel="stylesheet" href="../../css/select2-bootstrap.min.css">
    <style>
        :root {
            --danger-accent: #c0392b;
            --neutral-grey: #6c757d;
            --bg-page: #f4f7f6;
        }

        body { 
            background-color: var(--bg-page);
            font-family: 'Inter', -apple-system, sans-serif;
        }

        .card {
            border: none;
            border-top: 4px solid var(--danger-accent);
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .card-header {
            background-color: transparent;
            padding: 1.5rem 1.5rem 0.5rem 1.5rem;
            border: none;
        }

        .card-header h5 {
            color: var(--danger-accent);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--neutral-grey);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 2px;
            border: 1px solid #ddd;
            padding: 0.6rem;
        }

        .form-control:focus {
            border-color: var(--danger-accent);
            box-shadow: none;
        }

        .btn-danger {
            background-color: var(--danger-accent);
            border: none;
            border-radius: 2px;
            font-weight: 600;
            padding: 12px;
            transition: opacity 0.2s;
        }

        .btn-danger:hover {
            background-color: #a93226;
            opacity: 0.9;
        }

        .btn-light {
            background-color: #fff;
            border: 1px solid #ddd;
            color: var(--neutral-grey);
            border-radius: 2px;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: var(--neutral-grey);
            font-size: 0.85rem;
        }

        #stock_info {
            display: block;
            margin-top: 5px;
            font-size: 0.8rem;
            color: #888;
        }

        /* Select2 Style Correction */
        .select2-container--bootstrap .select2-selection {
            border-radius: 2px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="container" style="margin-top: 100px; margin-bottom: 50px;">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bon de Sortie Stock</h5>
                </div>
                
                <div class="card-body p-4">
                    <?= $message ?>
                    
                    <form method="POST" id="formStockOut">
                        <div class="mb-4">
                            <label class="form-label text-uppercase">Référence article</label>
                            <select name="id_produit" id="select_produit" class="form-control select2-enable" required>
                                <option value="">Choisir un produit...</option>
                                <?php foreach($produits as $p): ?>
                                    <option value="<?= $p['id_produit'] ?>" 
                                            data-stock="<?= (float)$p['quantite_stock'] ?>" 
                                            data-unite="<?= htmlspecialchars($p['unite_mesure'] ?? '') ?>">
                                        <?= htmlspecialchars($p['nom_produit']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-uppercase">Quantité à prélever</label>
                            <div class="input-group">
                                <input type="number" name="quantite" id="quantite_input" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                                <span class="input-group-text" id="unite_label">---</span>
                            </div>
                            <small id="stock_info">Veuillez sélectionner un article</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-uppercase">Motif d'utilisation</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Ex: Utilisation service interne, maintenance, etc."></textarea>
                        </div>

                        <div class="d-grid gap-2 pt-2">
                            <button type="submit" class="btn btn-danger">Valider le déstockage</button>
                            <a href="inventory.php" class="btn btn-light">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script src="../../js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Select2 Custom Rendering
    function formatProduct(repo) {
        if (!repo.id) return repo.text;
        const stock = parseFloat($(repo.element).data('stock'));
        const unite = $(repo.element).data('unite');
        
        let color = '#27ae60'; // Vert
        if (stock <= 5) color = '#e74c3c'; // Rouge
        else if (stock <= 15) color = '#f39c12'; // Orange

        return $(`
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span>${repo.text}</span>
                <span style="font-size:0.75rem; font-weight:bold; color:${color}">
                    Dispo: ${stock} ${unite}
                </span>
            </div>
        `);
    }

    $('#select_produit').select2({
        theme: "bootstrap",
        placeholder: "Rechercher un article...",
        allowClear: true,
        width: '100%',
        templateResult: formatProduct
    }).on('change', function() {
        const selected = this.options[this.selectedIndex];
        const stock = selected.getAttribute('data-stock');
        const unite = selected.getAttribute('data-unite');
        
        if(selected.value) {
            $('#unite_label').text(unite);
            $('#stock_info').html(`Stock disponible : <b>${stock} ${unite}</b>`);
            $('#quantite_input').attr('max', stock);
        } else {
            $('#unite_label').text('---');
            $('#stock_info').text('Veuillez sélectionner un article');
        }
    });

    // Validation sécurité avant envoi
    $('#formStockOut').on('submit', function(e) {
        const qty = parseFloat($('#quantite_input').val());
        const max = parseFloat($('#quantite_input').attr('max'));
        
        if (qty > max) {
            e.preventDefault();
            alert("Erreur : La quantité demandée dépasse le stock disponible.");
        }
    });
});
</script>

<?php include '../../templates/footer.php'; ?>
</body>
</html>