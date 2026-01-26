<?php
// pages/admin/edit_service.php
session_start();
ob_start();

// Vérification de sécurité
if (!isset($_SESSION['utilisateur_id']) || ($_SESSION['role'] !== 'patron' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../../index.php?error=Accès non autorisé');
    exit();
}

require_once '../../fonctions/database.php';

$id_service = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_service <= 0) {
    header('Location: services.php?error=Service invalide');
    exit();
}

// Récupérer les catégories
$stmtCat = $pdo->query("SELECT * FROM categories_service ORDER BY nom_categorie");
$categories = $stmtCat->fetchAll();

// Récupérer le service
$stmt = $pdo->prepare("SELECT * FROM services WHERE id_service = ?");
$stmt->execute([$id_service]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: services.php?error=Service non trouvé');
    exit();
}

// Traitement de la mise à jour
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_service') {
    $nom_service = trim($_POST['nom_service'] ?? '');
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $prix_unitaire = floatval($_POST['prix_unitaire'] ?? 0);
    $unite_mesure = trim($_POST['unite_mesure'] ?? 'pièce');
    $duree_estimee = intval($_POST['duree_estimee'] ?? 60);
    $description = trim($_POST['description'] ?? '');
    $est_disponible = isset($_POST['est_disponible']) ? 1 : 0;

    if (!empty($nom_service) && $id_categorie > 0 && $prix_unitaire > 0) {
        try {
            $sql = "UPDATE services 
                    SET nom_service = :nom, 
                        id_categorie = :categorie, 
                        description = :desc, 
                        prix_unitaire = :prix, 
                        duree_estimee = :duree, 
                        unite_mesure = :unite,
                        est_disponible = :dispo
                    WHERE id_service = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom_service,
                ':categorie' => $id_categorie,
                ':desc' => $description,
                ':prix' => $prix_unitaire,
                ':duree' => $duree_estimee,
                ':unite' => $unite_mesure,
                ':dispo' => $est_disponible,
                ':id' => $id_service
            ]);
            
            $message = "Service mis à jour avec succès!";
            $message_type = "success";
            
            // Recharger les données
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id_service = ?");
            $stmt->execute([$id_service]);
            $service = $stmt->fetch();
            
        } catch (PDOException $e) {
            $message = "Erreur lors de la mise à jour: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires";
        $message_type = "warning";
    }
}

$title = "Modifier le Service";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Pressing Manager</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/all.min.css">
</head>
<body>
<?php 
ob_end_flush();
include('../../templates/header.php'); 
include('../../templates/navigation.php'); 
?>

<br><br><br>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Modifier le Service #<?= $service['id_service'] ?></h4>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> m-3">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_service">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom du service *</label>
                            <input type="text" name="nom_service" class="form-control" 
                                   value="<?= htmlspecialchars($service['nom_service']) ?>" 
                                   required maxlength="100">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Catégorie *</label>
                                <select name="id_categorie" class="form-select" required>
                                    <option value="">Choisir une catégorie...</option>
                                    <?php foreach($categories as $c): ?>
                                        <option value="<?= $c['id_categorie'] ?>" 
                                            <?= $service['id_categorie'] == $c['id_categorie'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nom_categorie']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Prix (FCFA) *</label>
                                <div class="input-group">
                                    <input type="number" name="prix_unitaire" class="form-control" 
                                           value="<?= $service['prix_unitaire'] ?>" 
                                           min="0" step="0.01" required>
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Unité</label>
                                <select name="unite_mesure" class="form-select">
                                    <option value="pièce" <?= $service['unite_mesure'] == 'pièce' ? 'selected' : '' ?>>Pièce</option>
                                    <option value="kg" <?= $service['unite_mesure'] == 'kg' ? 'selected' : '' ?>>Kilogramme (Kg)</option>
                                    <option value="paire" <?= $service['unite_mesure'] == 'paire' ? 'selected' : '' ?>>Paire</option>
                                    <option value="mètre" <?= $service['unite_mesure'] == 'mètre' ? 'selected' : '' ?>>Mètre</option>
                                    <option value="lot" <?= $service['unite_mesure'] == 'lot' ? 'selected' : '' ?>>Lot</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Durée estimée (minutes)</label>
                                <input type="number" name="duree_estimee" class="form-control" 
                                       value="<?= $service['duree_estimee'] ?>" min="1">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      maxlength="255"><?= htmlspecialchars($service['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="est_disponible" 
                                       id="est_disponible" value="1" 
                                       <?= $service['est_disponible'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="est_disponible">
                                    Service disponible
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="services.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.bundle.min.js"></script>

<?php include('../../templates/footer.php'); ?>
</body>
</html>