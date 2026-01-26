<?php
session_start();

// Inclusion des fichiers de configuration et fonctions
require_once "../../fonctions/database.php"; // Votre fichier de connexion PDO
require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');

// Vérification de l'ID du produit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='container mt-5 alert alert-danger'>ID Produit manquant.</div>";
    exit;
}

$id_produit = (int)$_GET['id'];

try {
    // Connexion à la base de données (supposons que $db est créée dans database.php)
    // Correction de la requête : f.telephone au lieu de f.telephone_fournisseur
    $query = "SELECT p.*, f.nom_fournisseur, f.contact, f.telephone as tel_fournisseur, f.email as email_fournisseur
              FROM produits p
              LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id_fournisseur
              WHERE p.id_produit = :id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id_produit]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        echo "<div class='container mt-5 alert alert-warning'>Produit introuvable.</div>";
        exit;
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>
<br> <br> <br>
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="inventory.php">Stock</a></li>
            <li class="breadcrumb-item active">Détails du produit</li>
        </ol>
    </nav>

    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-box"></i> <?= htmlspecialchars($produit['nom_produit']) ?></h3>
            <span class="badge bg-<?= $produit['quantite_stock'] <= $produit['seuil_alerte'] ? 'danger' : 'success' ?>">
                Stock : <?= $produit['quantite_stock'] ?> <?= htmlspecialchars($produit['unite_mesure']) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-end">
                    <h5 class="text-muted border-bottom pb-2">Informations Générales</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th>Catégorie:</th>
                            <td><span class="badge bg-info text-dark"><?= ucfirst($produit['categorie']) ?></span></td>
                        </tr>
                        <tr>
                            <th>Prix Unitaire:</th>
                            <td><strong><?= number_format($produit['prix_unitaire'], 2, ',', ' ') ?> FCFA</strong></td>
                        </tr>
                        <tr>
                            <th>Seuil d'alerte:</th>
                            <td><?= $produit['seuil_alerte'] ?> <?= $produit['unite_mesure'] ?></td>
                        </tr>
                        <tr>
                            <th>Emplacement:</th>
                            <td><?= htmlspecialchars($produit['emplacement'] ?? 'Non défini') ?></td>
                        </tr>
                        <tr>
                            <th>Statut:</th>
                            <td><?= $produit['est_actif'] ? '<span class="text-success">Actif</span>' : '<span class="text-danger">Inactif</span>' ?></td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h5 class="text-muted border-bottom pb-2">Détails Fournisseur</h5>
                    <?php if ($produit['id_fournisseur']): ?>
                        <table class="table table-borderless">
                            <tr>
                                <th>Nom:</th>
                                <td><?= htmlspecialchars($produit['nom_fournisseur']) ?></td>
                            </tr>
                            <tr>
                                <th>Contact:</th>
                                <td><?= htmlspecialchars($produit['contact'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th>Téléphone:</th>
                                <td><?= htmlspecialchars($produit['tel_fournisseur'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?= htmlspecialchars($produit['email_fournisseur'] ?? 'N/A') ?></td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-muted mt-4">Aucun fournisseur associé.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light">
           <a href="inventory.php" onclick="if(document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }" class="btn btn-secondary">
    <- Retour à la liste
</a>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patron'): ?>
                <a href="edit_product.php?id=<?= $produit['id_produit'] ?>" class="btn btn-warning">Modifier</a>
                <button onclick="confirmDelete(<?= $produit['id_produit'] ?>)" class="btn btn-danger">Supprimer</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce produit ?")) {
        window.location.href = "delete_product.php?id=" + id;
    }
}
</script>

<?php require_once(__DIR__ . '/../../templates/footer.php'); ?>