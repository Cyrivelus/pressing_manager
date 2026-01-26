<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Préparation de Commande Fournisseur";

// Récupération des articles en alerte ou sélectionnés manuellement
$sql = "SELECT * FROM consommables 
        WHERE stock_actuel <= stock_alerte 
        OR id_consommable IN (SELECT id_consommable FROM consommables WHERE stock_actuel < (stock_alerte * 2))
        ORDER BY fournisseur_habituel, nom_article ASC";
$articles_a_commander = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-shopping-cart text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Générez vos besoins de réapprovisionnement basés sur les seuils critiques.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour au stock
        </a>
    </div>

    <form action="generer_pdf_commande.php" method="POST" target="_blank">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Articles suggérés pour réapprovisionnement</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Article</th>
                            <th class="text-center">Stock Actuel</th>
                            <th class="text-center">Seuil Alerte</th>
                            <th>Fournisseur</th>
                            <th style="width: 200px;">Quantité à commander</th>
                            <th class="text-end pe-4">Total Est.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles_a_commander)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>
                                    Tous les stocks sont optimaux. Aucun article en alerte.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($articles_a_commander as $index => $a): ?>
                            <tr>
                                <td class="ps-4">
                                    <input type="hidden" name="items[<?= $index ?>][id]" value="<?= $a['id_consommable'] ?>">
                                    <span class="fw-bold"><?= htmlspecialchars($a['nom_article']) ?></span>
                                    <div class="small text-muted"><?= $a['conditionnement'] ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= ($a['stock_actuel'] <= $a['stock_alerte']) ? 'danger' : 'warning' ?> text-white">
                                        <?= $a['stock_actuel'] ?> <?= $a['unite_mesure'] ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted"><?= $a['stock_alerte'] ?></td>
                                <td><?= htmlspecialchars($a['fournisseur_habituel'] ?? 'Non défini') ?></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="items[<?= $index ?>][qty]" 
                                               class="form-control border-primary qty-input" 
                                               data-price="<?= $a['dernier_prix_achat'] ?>"
                                               value="<?= max(0, ceil($a['stock_alerte'] * 2 - $a['stock_actuel'])) ?>">
                                        <span class="input-group-text"><?= $a['unite_mesure'] ?></span>
                                    </div>
                                </td>
                                <td class="text-end pe-4 fw-bold">
                                    <span class="item-total">0</span> FCFA
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3">
                <div class="h5 mb-0">Total estimé de la commande : <span id="grand-total" class="fw-bold text-primary">0</span> FCFA</div>
                <button type="submit" class="btn btn-success btn-lg shadow-sm" <?= empty($articles_a_commander) ? 'disabled' : '' ?>>
                    <i class="fas fa-file-pdf"></i> Générer le Bon de Commande
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.qty-input');
    
    function calculateTotals() {
        let grandTotal = 0;
        inputs.forEach(input => {
            const qty = parseFloat(input.value) || 0;
            const price = parseFloat(input.dataset.price) || 0;
            const rowTotal = qty * price;
            grandTotal += rowTotal;
            
            // Mise à jour du total par ligne
            input.closest('tr').querySelector('.item-total').textContent = rowTotal.toLocaleString('fr-FR');
        });
        document.getElementById('grand-total').textContent = grandTotal.toLocaleString('fr-FR');
    }

    inputs.forEach(input => input.addEventListener('input', calculateTotals));
    calculateTotals(); // Calcul initial
});
</script>

<?php require_once  '../../templates/footer.php'; ?>