<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../fonctions/database.php');

$message = "";

// 1. Récupérer la liste des produits pour le menu déroulant
$produits = $pdo->query("SELECT id_produit, nom_produit, quantite_stock, unite_mesure FROM produits WHERE est_actif = 1 ORDER BY nom_produit")->fetchAll();

// 2. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produit = $_POST['id_produit'];
    $quantite_a_retirer = floatval($_POST['quantite']);
    $notes = $_POST['notes'];
    $id_utilisateur = $_SESSION['user_id'] ?? 1; // ID de l'utilisateur connecté

    try {
        $pdo->beginTransaction();

        // Vérifier le stock actuel
        $stmt = $pdo->prepare("SELECT quantite_stock FROM produits WHERE id_produit = ? FOR UPDATE");
        $stmt->execute([$id_produit]);
        $produit = $stmt->fetch();

        if ($produit && $produit['quantite_stock'] >= $quantite_a_retirer) {
            $nouveau_stock = $produit['quantite_stock'] - $quantite_a_retirer;

            // A. Mise à jour de la table produits
            $update = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE id_produit = ?");
            $update->execute([$nouveau_stock, $id_produit]);

            // B. Enregistrement du mouvement
            $log = $pdo->prepare("INSERT INTO mouvements_stock (id_produit, type_mouvement, quantite, date_mouvement, id_utilisateur, notes, quantite_apres) VALUES (?, 'sortie', ?, NOW(), ?, ?, ?)");
            $log->execute([$id_produit, $quantite_a_retirer, $id_utilisateur, $notes, $nouveau_stock]);

            $pdo->commit();
            header("Location: inventory.php?success=stock_out");
            exit();
        } else {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>Stock insuffisant ! Stock disponible : " . ($produit['quantite_stock'] ?? 0) . "</div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
    }
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>
<br><br><br><br><br><br>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Sortie de Stock (Utilisation)</h5>
                </div>
                <div class="card-body">
                    <?= $message ?>
                    
                    <form method="POST" id="formStockOut">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Produit à sortir</label>
                            <select name="id_produit" id="select_produit" class="form-select select2" required>
                                <option value="">Choisir un produit...</option>
                                <?php foreach($produits as $p): ?>
                                    <option value="<?= $p['id_produit'] ?>" data-stock="<?= $p['quantite_stock'] ?>" data-unite="<?= $p['unite_mesure'] ?>">
                                        <?= htmlspecialchars($p['nom_produit']) ?> (Dispo: <?= $p['quantite_stock'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantité à retirer</label>
                            <div class="input-group">
                                <input type="number" name="quantite" id="quantite_input" class="form-control" step="0.01" min="0.01" required>
                                <span class="input-group-text" id="unite_label">Unité</span>
                            </div>
                            <small class="text-muted" id="stock_info"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Motif / Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Ex: Nettoyage commande #452"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">Confirmer la sortie</button>
                            <a href="inventory.php" class="btn btn-light">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamisme du formulaire : afficher l'unité et vérifier le stock en temps réel
document.getElementById('select_produit').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const stock = selected.getAttribute('data-stock');
    const unite = selected.getAttribute('data-unite');
    
    if(selected.value) {
        document.getElementById('unite_label').textContent = unite;
        document.getElementById('stock_info').textContent = "Maximum autorisé : " + stock + " " + unite;
        document.getElementById('quantite_input').setAttribute('max', stock);
    }
});
</script>

<?php include '../../templates/footer.php'; ?>