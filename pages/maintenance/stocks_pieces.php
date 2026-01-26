<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Inventaire des Pièces Détachées";

// 1. Récupération des pièces techniques et leur niveau d'alerte
$sql = "SELECT p.*, e.nom_equipement 
        FROM produits p 
        LEFT JOIN equipements e ON p.id_equipement_associe = e.id_equipement
        WHERE p.categorie IN ('maintenance', 'quincaillerie')
        ORDER BY p.quantite_stock ASC";
$pieces = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-secondary"><i class="fas fa-box-open me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Gestion des composants critiques et consommables techniques</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary"><i class="fas fa-barcode"></i> Scanner</button>
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalAddPiece">
                <i class="fas fa-plus"></i> Nouvelle Référence
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <?php 
            $alertes = array_filter($pieces, function($p) { return $p['quantite_stock'] <= $p['seuil_alerte']; });
            if (!empty($alertes)): 
            ?>
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center">
                <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                <div>
                    <strong>Attention :</strong> <?= count($alertes) ?> référence(s) sont en dessous du seuil critique. Commandez rapidement pour éviter un arrêt technique.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4">Pièce / Référence</th>
                                <th>Machine Associée</th>
                                <th class="text-center">Stock Actuel</th>
                                <th class="text-center">Seuil Alerte</th>
                                <th>Emplacement</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pieces as $p): 
                                $is_low = ($p['quantite_stock'] <= $p['seuil_alerte']);
                            ?>
                            <tr class="<?= $is_low ? 'table-light' : '' ?>">
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($p['nom_produit']) ?></div>
                                    <small class="text-muted">Réf: <?= $p['code_fournisseur'] ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= $p['nom_equipement'] ?? 'Universel' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold <?= $is_low ? 'text-danger' : 'text-success' ?>">
                                        <?= $p['quantite_stock'] ?> <?= $p['unite_mesure'] ?>
                                    </span>
                                    <?php if($is_low): ?> <i class="fas fa-arrow-down text-danger small"></i> <?php endif; ?>
                                </td>
                                <td class="text-center text-muted"><?= $p['seuil_alerte'] ?></td>
                                <td><i class="fas fa-layer-group me-1 text-muted"></i> <?= $p['emplacement'] ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-dark" title="Ajuster le stock"><i class="fas fa-sync"></i></button>
                                        <button class="btn btn-outline-dark" title="Commander"><i class="fas fa-shopping-cart"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-4 bg-white mb-4">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">Valeur de l'inventaire</h6>
                <h3 class="fw-bold mb-0">420 000 <small class="fs-6">FCFA</small></h3>
                <p class="text-muted small mt-2">Capital immobilisé en pièces techniques.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 bg-indigo text-white">
                <h6 class="fw-bold small text-uppercase opacity-75 mb-3">Dernière Entrée</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="d-block fw-bold">Joints de porte (x5)</span>
                        <small class="opacity-75">Le 21/01/2026</small>
                    </div>
                    <i class="fas fa-truck-loading fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddPiece" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="fw-bold">Nouvelle Référence Technique</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Désignation de la pièce</label>
                    <input type="text" class="form-control" placeholder="ex: Filtre à charbon actif">
                </div>
                <div class="mb-3">
                    <label class="form-label">Machine de destination</label>
                    <select class="form-select">
                        <option value="0">Toutes (Universel)</option>
                        <option value="1">Machine à Sec Union</option>
                        <option value="2">Chaudière 50L</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Stock Initial</label>
                        <input type="number" class="form-control" value="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Seuil d'Alerte</label>
                        <input type="number" class="form-control" value="2">
                    </div>
                </div>
                <button type="submit" class="btn btn-dark w-100">Enregistrer</button>
            </form>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>