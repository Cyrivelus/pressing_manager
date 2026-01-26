<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Grille Tarifaire Officielle";

// Récupération des services par catégorie
$sql = "SELECT s.*, cs.nom_categorie 
        FROM services s 
        JOIN categories_service cs ON s.id_categorie = cs.id_categorie 
        ORDER BY cs.nom_categorie, s.nom_service";
$services = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fas fa-tags text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Consultez et gérez les tarifs appliqués en agence</p>
        </div>
        <a href="configurer_tarifs.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-edit me-1"></i> Modifier les tarifs
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Service</th>
                        <th>Catégorie</th>
                        <th class="text-center">Unité</th>
                        <th class="text-end">Prix Unitaire</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($services as $s): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($s['nom_service']) ?></td>
                        <td><span class="badge bg-info-soft text-info"><?= $s['nom_categorie'] ?></span></td>
                        <td class="text-center text-muted small"><?= $s['unite_mesure'] ?></td>
                        <td class="text-end fw-bold"><?= number_format($s['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-end pe-4">
                            <a href="historique_tarifs.php?id=<?= $s['id_service'] ?>" class="btn btn-sm btn-outline-secondary" title="Historique">
                                <i class="fas fa-history"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>