<?php
session_start();
require_once('../../fonctions/database.php');
require_once('../../fonctions/gestion_services.php');

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../index.php");
    exit();
}

$categories = getAllCategories($pdo);
$id_cat_filtre = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;
$services = ($id_cat_filtre > 0) ? getServicesByCategorie($pdo, $id_cat_filtre) : getAllServices($pdo);

include('../../templates/header.php'); 
include('../../templates/navigation.php'); 
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tshirt"></i> Catalogue des Services</h2>
        <a href="service_add.php" class="btn btn-primary">
            + Ajouter un service
        </a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label>Filtrer par catégorie :</label>
                </div>
                <div class="col-md-4">
                    <select name="categorie" class="form-select" onchange="this.form.submit()">
                        <option value="0">Toutes les catégories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id_categorie'] ?>" <?= $id_cat_filtre == $cat['id_categorie'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom_categorie']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Service</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Unité</th>
                    <th>Durée</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($services as $s): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['nom_service']) ?></strong></td>
                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($s['nom_categorie']) ?></span></td>
                    <td><?= number_format($s['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                    <td><?= htmlspecialchars($s['unite_mesure']) ?></td>
                    <td><?= $s['duree_estimee'] ?> min</td>
                    <td>
                        <span class="badge <?= $s['est_disponible'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $s['est_disponible'] ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                    <td>
                        <a href="service_edit.php?id=<?= $s['id_service'] ?>" class="btn btn-sm btn-warning">
                          Modifier
                        </a>
                        <a href="../../controllers/ServiceController.php?action=delete&id=<?= $s['id_service'] ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Supprimer ce service ?');">
                            Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../../templates/footer.php'); ?>