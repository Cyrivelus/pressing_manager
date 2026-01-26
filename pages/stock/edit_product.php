<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// 1. SÉCURITÉ : Seul le patron peut accéder à cette page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../dashboard/index.php?error=Accès refusé");
    exit();
}

require_once(__DIR__ . '/../../fonctions/database.php');

// 2. VÉRIFICATION DE L'ID
$id_produit = $_GET['id'] ?? null;
if (!$id_produit) {
    header("Location: inventory.php");
    exit();
}

$message = "";

// 3. TRAITEMENT DU FORMULAIRE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE produits SET 
                nom_produit = ?, id_fournisseur = ?, categorie = ?, 
                unite_mesure = ?, seuil_alerte = ?, prix_unitaire = ?, 
                emplacement = ?, est_actif = ?
                WHERE id_produit = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nom_produit'],
            $_POST['id_fournisseur'] ?: null, // Gère le cas vide
            $_POST['categorie'],
            $_POST['unite_mesure'],
            $_POST['seuil_alerte'],
            $_POST['prix_unitaire'],
            $_POST['emplacement'],
            isset($_POST['est_actif']) ? 1 : 0,
            $id_produit
        ]);
        
        // Redirection avec succès
        header("Location: inventory.php?success=L'article a été mis à jour");
        exit();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger shadow-sm'>
                        <i class='fas fa-exclamation-triangle me-2'></i> 
                        Erreur lors de la mise à jour : " . htmlspecialchars($e->getMessage()) . "
                    </div>";
    }
}

// 4. RÉCUPÉRATION DES DONNÉES (Pour remplir le formulaire)
$stmt = $pdo->prepare("SELECT * FROM produits WHERE id_produit = ?");
$stmt->execute([$id_produit]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: inventory.php?error=Produit introuvable");
    exit();
}

$fournisseurs = $pdo->query("SELECT id_fournisseur, nom_fournisseur FROM fournisseurs WHERE est_actif = 1 ORDER BY nom_fournisseur ASC")->fetchAll();

// 5. INCLUSION DU HEADER ET NAV
require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');
?>

<br> <br> <br>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="inventory.php">Stock</a></li>
                    <li class="breadcrumb-item active">Modifier le produit</li>
                </ol>
            </nav>

            <div class="card shadow-lg border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0">
                        Modifier : <?= htmlspecialchars($product['nom_produit']) ?>
                    </h5>
                </div>
                
                <div class="card-body p-4">
                    <?= $message ?>

                    <form method="POST" class="needs-validation">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-secondary">Nom du produit</label>
                                <div class="input-group">
                                    <span class="input-group-text"></span>
                                    <input type="text" name="nom_produit" class="form-control" value="<?= htmlspecialchars($product['nom_produit']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-secondary">Catégorie</label>
                                <select name="categorie" class="form-select" required>
                                    <?php 
                                    $cats = ['lessive', 'detachant', 'cintre', 'sac', 'etiquette', 'autre'];
                                    foreach($cats as $cat): ?>
                                        <option value="<?= $cat ?>" <?= $product['categorie'] == $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-secondary">Fournisseur</label>
                                <select name="id_fournisseur" class="form-select">
                                    <option value="">-- Aucun fournisseur --</option>
                                    <?php foreach($fournisseurs as $f): ?>
                                        <option value="<?= $f['id_fournisseur'] ?>" <?= $product['id_fournisseur'] == $f['id_fournisseur'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($f['nom_fournisseur']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-secondary">Prix Unitaire (FCFA)</label>
                                <div class="input-group">
                                    <input type="number" name="prix_unitaire" class="form-control" step="0.01" value="<?= $product['prix_unitaire'] ?>" required>
                                    <span class="input-group-text">CFA</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Seuil d'alerte</label>
                                <input type="number" name="seuil_alerte" class="form-control" value="<?= $product['seuil_alerte'] ?>" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Unité (ex: Litre, Kg)</label>
                                <input type="text" name="unite_mesure" class="form-control" value="<?= htmlspecialchars($product['unite_mesure']) ?>" placeholder="ex: Pièce">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Emplacement</label>
                                <input type="text" name="emplacement" class="form-control" value="<?= htmlspecialchars($product['emplacement']) ?>" placeholder="Rayon A1">
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="est_actif" id="est_actif" <?= $product['est_actif'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="est_actif">
                                 <br>   Produit disponible à l'utilisation
                                </label>
                            </div>
                            <small class="text-muted">Si décoché, le produit n'apparaîtra plus dans les nouvelles sorties de stock.</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <a href="inventory.php" onclick="window.history.back(); return false;" class="btn btn-outline-secondary px-4">
    <- Retour
</a>
                            <button type="submit" class="btn btn-success px-5 shadow-sm">
                               Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>