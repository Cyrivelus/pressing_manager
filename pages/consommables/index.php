<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Accès réservé au staff/administration
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Consommables & Stocks Techniques";

// 2. Récupération des consommables avec alerte stock
$sql = "SELECT *, (stock_actuel <= stock_alerte) as alerte 
        FROM consommables 
        ORDER BY alerte DESC, nom_article ASC";
$stmt = $pdo->query($sql);
$articles = $stmt->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><?= $titre ?></h2>
            <p class="text-muted">Fournitures nécessaires à l'exploitation de l'atelier</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEntree">
                <i class="fas fa-plus-circle"></i> Entrée de stock
            </button>
            <button class="btn btn-primary">
                <i class="fas fa-file-invoice"></i> Commander Fournisseur
            </button>
        </div>
    </div>

    <?php 
    $alert_count = count(array_filter($articles, function($a) { return $a['alerte']; }));
    if ($alert_count > 0): 
    ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div>
            <h6 class="fw-bold mb-0">Attention : <?= $alert_count ?> article(s) en rupture ou stock critique !</h6>
            <small>Veuillez passer commande pour ne pas interrompre la production.</small>
        </div>
    </div>
    <?php endif; ?>

    

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Article</th>
                        <th>Catégorie</th>
                        <th class="text-center">Stock Actuel</th>
                        <th class="text-center">Seuil Alerte</th>
                        <th>Dernier Prix Unit.</th>
                        <th>Dernier Fournisseur</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($articles as $a): ?>
                    <tr class="<?= $a['alerte'] ? 'table-danger' : '' ?>">
                        <td class="ps-4">
                            <span class="fw-bold d-block"><?= htmlspecialchars($a['nom_article']) ?></span>
                            <small class="text-muted"><?= htmlspecialchars($a['conditionnement']) ?></small>
                        </td>
                        <td><span class="badge bg-outline-secondary border text-dark"><?= $a['categorie'] ?></span></td>
                        <td class="text-center">
                            <span class="h6 fw-bold <?= $a['alerte'] ? 'text-danger' : '' ?>">
                                <?= $a['stock_actuel'] ?> <?= $a['unite_mesure'] ?>
                            </span>
                        </td>
                        <td class="text-center text-muted"><?= $a['stock_alerte'] ?></td>
                        <td><?= number_format($a['dernier_prix_achat'], 0, ',', ' ') ?> FCFA</td>
                        <td><?= htmlspecialchars($a['fournisseur_habituel'] ?? 'Non défini') ?></td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-light border" title="Ajuster"><i class="fas fa-sync"></i></button>
                                <button class="btn btn-sm btn-light border text-primary" title="Historique achats"><i class="fas fa-history"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEntree" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="fw-bold">Réception de marchandises</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Article</label>
                    <select class="form-select">
                        <?php foreach($articles as $a): ?>
                            <option><?= $a['nom_article'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Quantité reçue</label>
                        <input type="number" class="form-control" placeholder="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Prix d'achat total</label>
                        <input type="number" class="form-control" placeholder="FCFA">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Valider l'entrée</button>
            </form>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>