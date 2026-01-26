<?php
// Initialisation de la session et connexion
session_start();
require_once(__DIR__ . '/../../fonctions/database.php'); 

// 1. Vérification de l'ID du service
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: services.php?err=missing_id');
    exit();
}

$id_service = (int)$_GET['id'];
$message = "";
$status = "";

// 2. Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $nom = htmlspecialchars($_POST['nom_service']);
    $id_cat = $_POST['id_categorie'];
    $prix = $_POST['prix_unitaire'];
    $duree = $_POST['duree_estimee'];
    $unite = $_POST['unite_measure'];
    $dispo = isset($_POST['est_disponible']) ? 1 : 0;
    $desc = htmlspecialchars($_POST['description']);

    try {
        $sql = "UPDATE services SET 
                nom_service = ?, id_categorie = ?, prix_unitaire = ?, 
                duree_estimee = ?, unite_mesure = ?, est_disponible = ?, description = ?
                WHERE id_service = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom, $id_cat, $prix, $duree, $unite, $dispo, $desc, $id_service]);
        
        $message = "Service mis à jour avec succès !";
        $status = "success";
    } catch (PDOException $e) {
        $message = "Erreur lors de la mise à jour : " . $e->getMessage();
        $status = "danger";
    }
}

// 3. Récupération des données actuelles du service
$stmt = $pdo->prepare("SELECT * FROM services WHERE id_service = ?");
$stmt->execute([$id_service]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: services_list.php?err=not_found');
    exit();
}

// 4. Récupération des catégories pour le menu déroulant
$categories = $pdo->query("SELECT id_categorie, nom_categorie FROM categories_service ORDER BY nom_categorie")->fetchAll();

include '../../templates/header.php';
include '../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le Service - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<br> <br> <br>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Modifier le Service : <?php echo $service['nom_service']; ?></h5>
                    <a href="javascript:history.back()" class="btn btn-sm btn-light">← Retour</a>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $status; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom du Service</label>
                                <input type="text" name="nom_service" class="form-control" value="<?php echo $service['nom_service']; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Catégorie</label>
                                <select name="id_categorie" class="form-select" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id_categorie']; ?>" <?php echo ($cat['id_categorie'] == $service['id_categorie']) ? 'selected' : ''; ?>>
                                            <?php echo $cat['nom_categorie']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prix Unitaire (FCFA)</label>
                                <input type="number" step="0.01" name="prix_unitaire" class="form-control" value="<?php echo $service['prix_unitaire']; ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Durée Estimée (min)</label>
                                <input type="number" name="duree_estimee" class="form-control" value="<?php echo $service['duree_estimee']; ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Unité de mesure</label>
                                <input type="text" name="unite_measure" class="form-control" value="<?php echo $service['unite_mesure']; ?>" placeholder="ex: pièce, kg">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $service['description']; ?></textarea>
                        </div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="est_disponible" id="dispo" <?php echo ($service['est_disponible']) ? 'checked' : ''; ?>>
                            <br><label class="form-check-label" for="dispo">Service disponible à la vente</label>
                        </div>

                        <hr>
                        <div class="d-grid">
                            <button type="submit" name="update_service" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php include '../../templates/footer.php'; ?>